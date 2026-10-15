<?php

/**
 * KPI engine for the Admin, Inspector and QC portals.
 *
 * Every KPI = value for the selected period + previous period of the same length (trend arrow) +
 * target with direction (higher / lower is better) → RAG status:
 *   green = meets target · amber = within 10 % of target · red = worse.
 * Durations are measured from real workflow timestamps: request → first response, sub-job created →
 * assigned, started → submitted, submitted → QC decision (taken from the audit trail so re-submissions
 * after rework are measured correctly), job created → completed.
 */
final class KpiService
{
    /** key => [label, unit, target, direction(up|down|none), description] — targets are overridable in Settings */
    public const CATALOG = [
        // admin / operations
        'requests_received'   => ['Requests received', '', null, 'none', 'Client requests from all channels in the period'],
        'conversion_rate'     => ['Request → job conversion', '%', 80, 'up', 'Requests created in the period that became jobs'],
        'response_hours'      => ['Request response time', 'h', 24, 'down', 'Average hours from request receipt to first status change / conversion'],
        'assignment_hours'    => ['Assignment lead time', 'h', 24, 'down', 'Average hours from sub-job creation to inspector assignment'],
        'inspections_done'    => ['Inspections completed', '', null, 'none', 'Sub-jobs approved by QC in the period'],
        'certificates_issued' => ['Certificates issued', '', null, 'none', 'Certificates released by QC in the period'],
        'on_time_rate'        => ['On-time completion', '%', 90, 'up', 'Approved inspections completed by the due date (or scheduled date + SLA days)'],
        'job_cycle_days'      => ['Job cycle time', 'd', 7, 'down', 'Average days from job creation to job completion'],
        'first_pass_yield'    => ['QC first-pass yield', '%', 90, 'up', 'Approved inspections that were never returned for rework'],
        'backlog_unassigned'  => ['Unassigned backlog', '', 5, 'down', 'Sub-jobs currently waiting for an inspector (now)'],
        'overdue_open'        => ['Overdue inspections', '', 0, 'down', 'Open sub-jobs past their scheduled date (now)'],
        'equipment_due_30'    => ['Re-inspections due (30 d)', '', null, 'none', 'Client equipment reaching its next inspection date within 30 days (now)'],
        // inspector
        'my_completed'        => ['Inspections approved', '', 20, 'up', 'Your inspections approved by QC in the period'],
        'my_submitted'        => ['Inspections submitted', '', null, 'none', 'Inspections you submitted to QC in the period'],
        'my_inspection_hours' => ['Avg. inspection time', 'h', 8, 'down', 'Average hours from Start inspection to Submit'],
        'my_turnaround_days'  => ['Assignment → approval', 'd', 5, 'down', 'Average days from assignment to QC approval'],
        'my_fpy'              => ['First-pass QC approval', '%', 90, 'up', 'Your inspections approved without any rework'],
        'my_rework'           => ['Rework returns', '', 2, 'down', 'Times QC returned your work for correction in the period'],
        'my_on_time'          => ['On-time submission', '%', 90, 'up', 'Submitted on or before the scheduled date (+1 day grace)'],
        'my_open'             => ['Open workload', '', null, 'none', 'Assigned, in progress and rework items (now)'],
        'my_overdue'          => ['Overdue items', '', 0, 'down', 'Your open items past their scheduled date (now)'],
        // QC
        'qc_reviews'          => ['Reviews completed', '', null, 'none', 'QC decisions you made in the period'],
        'qc_review_hours'     => ['Avg. review time', 'h', 24, 'down', 'Average hours from submission to your decision'],
        'qc_sla'              => ['Reviewed within 24 h', '%', 90, 'up', 'Share of your reviews decided within 24 hours of submission'],
        'qc_approval_rate'    => ['Approval rate', '%', null, 'none', 'Share of your decisions that were approvals'],
        'qc_rework_rate'      => ['Rework rate', '%', null, 'none', 'Share of your decisions returned for rework'],
        'qc_certificates'     => ['Certificates issued', '', null, 'none', 'Certificates you released in the period'],
        'qc_queue'            => ['Queue now', '', 5, 'down', 'Inspections currently waiting for QC (now)'],
        'qc_oldest_hours'     => ['Oldest waiting', 'h', 24, 'down', 'Age of the oldest item in the QC queue (now)'],
    ];

    // ------------------------------------------------------------------ period handling
    public static function period(): array
    {
        $p = (string) ($_GET['period'] ?? '30d');
        $today = new DateTimeImmutable('today');
        switch ($p) {
            case '7d':      $from = $today->modify('-6 days'); $to = $today; $label = 'Last 7 days'; break;
            case 'month':   $from = $today->modify('first day of this month'); $to = $today; $label = 'This month'; break;
            case 'lmonth':  $from = $today->modify('first day of last month'); $to = $today->modify('last day of last month'); $label = 'Last month'; break;
            case 'quarter': $q = intdiv((int) $today->format('n') - 1, 3) * 3 + 1;
                            $from = $today->setDate((int) $today->format('Y'), $q, 1); $to = $today; $label = 'This quarter'; break;
            case 'ytd':     $from = $today->setDate((int) $today->format('Y'), 1, 1); $to = $today; $label = 'Year to date'; break;
            case '90d':     $from = $today->modify('-89 days'); $to = $today; $label = 'Last 90 days'; break;
            case 'custom':
                $f = strtotime((string) ($_GET['from'] ?? '')); $t = strtotime((string) ($_GET['to'] ?? ''));
                if ($f && $t && $f <= $t) {
                    $from = (new DateTimeImmutable())->setTimestamp($f)->setTime(0, 0); $to = (new DateTimeImmutable())->setTimestamp($t)->setTime(0, 0);
                    $label = $from->format('d-M-Y') . ' – ' . $to->format('d-M-Y'); break;
                }
                // fall through to default
            default:        $p = '30d'; $from = $today->modify('-29 days'); $to = $today; $label = 'Last 30 days';
        }
        $days = (int) $from->diff($to)->days + 1;
        return [
            'key' => $p, 'label' => $label, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'days' => $days,
            'prev_from' => $from->modify("-$days days")->format('Y-m-d'), 'prev_to' => $from->modify('-1 day')->format('Y-m-d'),
        ];
    }

    private static function range(string $from, string $to): array
    {
        return [$from . ' 00:00:00', $to . ' 23:59:59'];
    }

    public static function target(string $key)
    {
        $v = setting('kpi_target_' . $key);
        return $v !== null && $v !== '' && is_numeric($v) ? (float) $v : self::CATALOG[$key][2];
    }

    /** Build a card: value, previous value, target, RAG, delta. */
    public static function card(string $key, $value, $prev = null): array
    {
        [$label, $unit, , $dir, $desc] = self::CATALOG[$key];
        $target = self::target($key);
        $status = 'none';
        if ($value !== null && $target !== null && $dir !== 'none') {
            $v = (float) $value; $t = (float) $target;
            $good = $dir === 'up' ? $v >= $t : $v <= $t;
            if ($good) $status = 'green';
            else {
                $tol = max(abs($t) * 0.10, $dir === 'down' && $t == 0 ? 1 : 0.0001);
                $status = ($dir === 'up' ? $v >= $t - $tol : $v <= $t + $tol) ? 'amber' : 'red';
            }
        }
        $delta = ($value !== null && $prev !== null) ? round((float) $value - (float) $prev, 1) : null;
        $better = $delta === null || $delta == 0 || $dir === 'none' ? null : (($dir === 'up') === ($delta > 0));
        return compact('key', 'label', 'unit', 'dir', 'desc', 'value', 'prev', 'target', 'status', 'delta', 'better');
    }

    private static function avg(string $sql, array $p): ?float
    {
        $v = DB::value($sql, $p);
        return $v === null ? null : round((float) $v, 1);
    }

    private static function pct(?int $num, ?int $den): ?float
    {
        return $den ? round($num / $den * 100, 1) : null;
    }

    // ------------------------------------------------------------------ ADMIN
    public static function adminMetrics(string $from, string $to): array
    {
        [$a, $b] = self::range($from, $to);
        $sla = (int) (setting('kpi_sla_days') ?: 3);
        $req = DB::one("SELECT COUNT(*) n, SUM(status = 'converted') conv FROM service_requests WHERE created_at BETWEEN ? AND ?", [$a, $b]);
        $resp = self::avg("SELECT AVG(TIMESTAMPDIFF(MINUTE, r.created_at, x.first_at)) / 60 FROM service_requests r
                           JOIN (SELECT entity_id, MIN(created_at) first_at FROM audit_logs WHERE entity_type = 'service_request' AND action IN ('request.status','request.converted') GROUP BY entity_id) x ON x.entity_id = r.id
                           WHERE r.created_at BETWEEN ? AND ?", [$a, $b]);
        $assign = self::avg("SELECT AVG(TIMESTAMPDIFF(MINUTE, s.created_at, x.first_at)) / 60 FROM sub_jobs s
                             JOIN (SELECT sub_job_id, MIN(assigned_at) first_at FROM job_assignments GROUP BY sub_job_id) x ON x.sub_job_id = s.id
                             WHERE s.created_at BETWEEN ? AND ?", [$a, $b]);
        $done = DB::one("SELECT COUNT(*) n,
                            SUM(DATE(s.approved_at) <= COALESCE(j.due_date, DATE_ADD(COALESCE(s.scheduled_date, j.scheduled_date, DATE(s.created_at)), INTERVAL ? DAY))) ontime,
                            SUM(NOT EXISTS (SELECT 1 FROM qc_reviews q WHERE q.sub_job_id = s.id AND q.decision = 'rework')) fpy
                         FROM sub_jobs s JOIN jobs j ON j.id = s.job_id WHERE s.status = 'approved' AND s.approved_at BETWEEN ? AND ?", [$sla, $a, $b]);
        $certs = (int) DB::value("SELECT COUNT(*) FROM certificates WHERE status IN ('issued','revoked') AND issue_date BETWEEN ? AND ?", [$from, $to]);
        $cycle = self::avg("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) / 24 FROM jobs WHERE status = 'completed' AND completed_at BETWEEN ? AND ?", [$a, $b]);
        return [
            'requests_received' => (int) $req['n'],
            'conversion_rate' => self::pct((int) $req['conv'], (int) $req['n']),
            'response_hours' => $resp,
            'assignment_hours' => $assign,
            'inspections_done' => (int) $done['n'],
            'certificates_issued' => $certs,
            'on_time_rate' => self::pct((int) $done['ontime'], (int) $done['n']),
            'job_cycle_days' => $cycle,
            'first_pass_yield' => self::pct((int) $done['fpy'], (int) $done['n']),
        ];
    }

    public static function admin(array $P): array
    {
        $cur = self::adminMetrics($P['from'], $P['to']);
        $prev = self::adminMetrics($P['prev_from'], $P['prev_to']);
        $now = DB::one("SELECT
            (SELECT COUNT(*) FROM sub_jobs s JOIN jobs j ON j.id = s.job_id WHERE s.status = 'pending' AND j.status <> 'cancelled') backlog,
            (SELECT COUNT(*) FROM sub_jobs s JOIN jobs j ON j.id = s.job_id WHERE s.status IN ('pending','assigned','in_progress','rework')
                AND COALESCE(s.scheduled_date, j.scheduled_date) < CURDATE()) overdue,
            (SELECT COUNT(*) FROM equipment WHERE status = 'active' AND next_inspection_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY) due30");
        $cards = [];
        foreach ($cur as $k => $v) $cards[] = self::card($k, $v, $prev[$k] ?? null);
        $cards[] = self::card('backlog_unassigned', (int) $now['backlog']);
        $cards[] = self::card('overdue_open', (int) $now['overdue']);
        $cards[] = self::card('equipment_due_30', (int) $now['due30']);

        [$a, $b] = self::range($P['from'], $P['to']);
        return [
            'cards' => $cards,
            'inspectors' => self::inspectorTable($a, $b),
            'clients' => DB::all("SELECT c.name client, c.code,
                    COUNT(DISTINCT CASE WHEN s.status = 'approved' AND s.approved_at BETWEEN ? AND ? THEN s.id END) completed,
                    COUNT(DISTINCT CASE WHEN ce.status IN ('issued','revoked') AND ce.issue_date BETWEEN ? AND ? THEN ce.id END) certificates,
                    COUNT(DISTINCT CASE WHEN s.status IN ('pending','assigned','in_progress','rework','submitted') THEN s.id END) open_now,
                    (SELECT COUNT(*) FROM service_requests r WHERE r.client_id = c.id AND r.created_at BETWEEN ? AND ?) requests
                FROM clients c LEFT JOIN jobs j ON j.client_id = c.id LEFT JOIN sub_jobs s ON s.job_id = j.id LEFT JOIN certificates ce ON ce.sub_job_id = s.id
                GROUP BY c.id ORDER BY completed DESC, open_now DESC, c.name", [$a, $b, $P['from'], $P['to'], $a, $b]),
            'channels' => DB::all("SELECT channel, COUNT(*) n, SUM(status = 'converted') converted FROM service_requests WHERE created_at BETWEEN ? AND ? GROUP BY channel ORDER BY n DESC", [$a, $b]),
            'types' => DB::all("SELECT t.name type, COUNT(*) n,
                    SUM(NOT EXISTS (SELECT 1 FROM qc_reviews q WHERE q.sub_job_id = s.id AND q.decision = 'rework')) fpy
                FROM sub_jobs s JOIN inspection_types t ON t.id = s.inspection_type_id
                WHERE s.status = 'approved' AND s.approved_at BETWEEN ? AND ? GROUP BY t.id ORDER BY n DESC", [$a, $b]),
            'monthly' => self::monthly(null),
            'qc_team' => self::qcMetrics(null, $P['from'], $P['to']),
            'qc_people' => DB::all("SELECT u.id, u.name, COUNT(q.id) reviews, SUM(q.decision = 'rework') reworks
                FROM users u LEFT JOIN qc_reviews q ON q.reviewer_id = u.id AND q.created_at BETWEEN ? AND ?
                WHERE u.role = 'qc' AND u.status = 'active' GROUP BY u.id ORDER BY reviews DESC", [$a, $b]),
        ];
    }

    /** Per-inspector productivity / quality for the period. */
    public static function inspectorTable(string $a, string $b): array
    {
        $sla = 1;
        return DB::all("SELECT u.id, u.name,
                SUM(s.status = 'approved' AND s.approved_at BETWEEN ? AND ?) completed,
                AVG(CASE WHEN s.submitted_at BETWEEN ? AND ? AND s.started_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, s.started_at, s.submitted_at) / 60 END) insp_hours,
                SUM(s.status = 'approved' AND s.approved_at BETWEEN ? AND ? AND NOT EXISTS (SELECT 1 FROM qc_reviews q WHERE q.sub_job_id = s.id AND q.decision = 'rework')) fpy_n,
                (SELECT COUNT(*) FROM qc_reviews q JOIN sub_jobs s2 ON s2.id = q.sub_job_id WHERE s2.inspector_id = u.id AND q.decision = 'rework' AND q.created_at BETWEEN ? AND ?) rework,
                SUM(s.submitted_at BETWEEN ? AND ? AND DATE(s.submitted_at) <= DATE_ADD(COALESCE(s.scheduled_date, DATE(s.created_at)), INTERVAL $sla DAY)) ontime_n,
                SUM(s.submitted_at BETWEEN ? AND ?) submitted,
                SUM(s.status IN ('assigned','in_progress','rework')) open_now
            FROM users u LEFT JOIN sub_jobs s ON s.inspector_id = u.id
            WHERE u.role = 'inspector' AND u.status = 'active'
            GROUP BY u.id ORDER BY completed DESC, u.name", [$a, $b, $a, $b, $a, $b, $a, $b, $a, $b, $a, $b]);
    }

    /** Last 6 calendar months: completed, certificates, FPY — optionally for one inspector. */
    public static function monthly(?int $inspectorId): array
    {
        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = new DateTimeImmutable("first day of -$i month");
            [$a, $b] = self::range($m->format('Y-m-01'), $m->format('Y-m-t'));
            $w = $inspectorId ? ' AND s.inspector_id = ' . (int) $inspectorId : '';
            $r = DB::one("SELECT COUNT(*) n, SUM(NOT EXISTS (SELECT 1 FROM qc_reviews q WHERE q.sub_job_id = s.id AND q.decision = 'rework')) fpy
                          FROM sub_jobs s WHERE s.status = 'approved' AND s.approved_at BETWEEN ? AND ?$w", [$a, $b]);
            $certs = (int) DB::value("SELECT COUNT(*) FROM certificates c JOIN sub_jobs s ON s.id = c.sub_job_id
                                      WHERE c.status IN ('issued','revoked') AND c.issue_date BETWEEN ? AND ?$w", [$m->format('Y-m-01'), $m->format('Y-m-t')]);
            $out[] = ['month' => $m->format('M Y'), 'short' => $m->format('M'), 'completed' => (int) $r['n'], 'certificates' => $certs, 'fpy' => self::pct((int) $r['fpy'], (int) $r['n'])];
        }
        return $out;
    }

    // ------------------------------------------------------------------ INSPECTOR
    public static function inspectorMetrics(int $uid, string $from, string $to): array
    {
        [$a, $b] = self::range($from, $to);
        $r = DB::one("SELECT
                SUM(status = 'approved' AND approved_at BETWEEN ? AND ?) completed,
                SUM(submitted_at BETWEEN ? AND ?) submitted,
                AVG(CASE WHEN submitted_at BETWEEN ? AND ? AND started_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, started_at, submitted_at) / 60 END) insp_h,
                SUM(status = 'approved' AND approved_at BETWEEN ? AND ? AND NOT EXISTS (SELECT 1 FROM qc_reviews q WHERE q.sub_job_id = sub_jobs.id AND q.decision = 'rework')) fpy_n,
                SUM(submitted_at BETWEEN ? AND ? AND DATE(submitted_at) <= DATE_ADD(COALESCE(scheduled_date, DATE(created_at)), INTERVAL 1 DAY)) ontime_n
              FROM sub_jobs WHERE inspector_id = ?", [$a, $b, $a, $b, $a, $b, $a, $b, $a, $b, $uid]);
        $turn = self::avg("SELECT AVG(TIMESTAMPDIFF(HOUR, x.first_at, s.approved_at)) / 24 FROM sub_jobs s
                           JOIN (SELECT sub_job_id, MIN(assigned_at) first_at FROM job_assignments WHERE inspector_id = ? GROUP BY sub_job_id) x ON x.sub_job_id = s.id
                           WHERE s.inspector_id = ? AND s.status = 'approved' AND s.approved_at BETWEEN ? AND ?", [$uid, $uid, $a, $b]);
        $rework = (int) DB::value("SELECT COUNT(*) FROM qc_reviews q JOIN sub_jobs s ON s.id = q.sub_job_id WHERE s.inspector_id = ? AND q.decision = 'rework' AND q.created_at BETWEEN ? AND ?", [$uid, $a, $b]);
        return [
            'my_completed' => (int) $r['completed'],
            'my_submitted' => (int) $r['submitted'],
            'my_inspection_hours' => $r['insp_h'] === null ? null : round((float) $r['insp_h'], 1),
            'my_turnaround_days' => $turn,
            'my_fpy' => self::pct((int) $r['fpy_n'], (int) $r['completed']),
            'my_rework' => $rework,
            'my_on_time' => self::pct((int) $r['ontime_n'], (int) $r['submitted']),
        ];
    }

    public static function inspector(int $uid, array $P): array
    {
        $cur = self::inspectorMetrics($uid, $P['from'], $P['to']);
        $prev = self::inspectorMetrics($uid, $P['prev_from'], $P['prev_to']);
        $now = DB::one("SELECT SUM(s.status IN ('assigned','in_progress','rework')) open_n,
                               SUM(s.status IN ('assigned','in_progress','rework') AND COALESCE(s.scheduled_date, j.scheduled_date) < CURDATE()) overdue
                        FROM sub_jobs s JOIN jobs j ON j.id = s.job_id WHERE s.inspector_id = ?", [$uid]);
        $cards = [];
        foreach ($cur as $k => $v) $cards[] = self::card($k, $v, $prev[$k] ?? null);
        $cards[] = self::card('my_open', (int) $now['open_n']);
        $cards[] = self::card('my_overdue', (int) $now['overdue']);

        // team benchmark (average of active inspectors with activity) — anonymous
        [$a, $b] = self::range($P['from'], $P['to']);
        $team = self::inspectorTable($a, $b);
        $active = array_filter($team, fn ($t) => (int) $t['completed'] > 0 || (int) $t['submitted'] > 0);
        $avgOf = fn ($f) => $active ? round(array_sum(array_map($f, $active)) / count($active), 1) : null;
        $bench = [
            'completed' => $avgOf(fn ($t) => (int) $t['completed']),
            'insp_hours' => $avgOf(fn ($t) => (float) $t['insp_hours']),
            'fpy' => $avgOf(fn ($t) => (int) $t['completed'] ? (int) $t['fpy_n'] / (int) $t['completed'] * 100 : 0),
            'rank' => null, 'of' => count($team),
        ];
        foreach (array_values($team) as $i => $t) if ((int) $t['id'] === $uid) $bench['rank'] = $i + 1;

        return [
            'cards' => $cards,
            'bench' => $bench,
            'monthly' => self::monthly($uid),
            'weekly' => self::weekly("SELECT COUNT(*) FROM sub_jobs WHERE inspector_id = $uid AND status = 'approved' AND approved_at BETWEEN ? AND ?"),
            'recent' => DB::all("SELECT s.id, s.sub_job_no, t.name type_name, c.name client_name, s.status, s.result, s.started_at, s.submitted_at, s.approved_at, s.scheduled_date,
                    TIMESTAMPDIFF(MINUTE, s.started_at, s.submitted_at) / 60 insp_hours,
                    (SELECT COUNT(*) FROM qc_reviews q WHERE q.sub_job_id = s.id AND q.decision = 'rework') reworks
                FROM sub_jobs s JOIN jobs j ON j.id = s.job_id JOIN clients c ON c.id = j.client_id JOIN inspection_types t ON t.id = s.inspection_type_id
                WHERE s.inspector_id = ? AND ((s.submitted_at BETWEEN ? AND ?) OR (s.approved_at BETWEEN ? AND ?))
                ORDER BY COALESCE(s.approved_at, s.submitted_at) DESC", [$uid, $a, $b, $a, $b]),
            'reworks' => DB::all("SELECT q.created_at, q.comments, s.sub_job_no, s.id sub_job_id, u.name reviewer FROM qc_reviews q JOIN sub_jobs s ON s.id = q.sub_job_id JOIN users u ON u.id = q.reviewer_id
                WHERE s.inspector_id = ? AND q.decision = 'rework' AND q.created_at BETWEEN ? AND ? ORDER BY q.id DESC LIMIT 10", [$uid, $a, $b]),
            'by_type' => DB::all("SELECT t.name type, COUNT(*) n, AVG(TIMESTAMPDIFF(MINUTE, s.started_at, s.submitted_at) / 60) hours
                FROM sub_jobs s JOIN inspection_types t ON t.id = s.inspection_type_id
                WHERE s.inspector_id = ? AND s.status = 'approved' AND s.approved_at BETWEEN ? AND ? GROUP BY t.id ORDER BY n DESC", [$uid, $a, $b]),
        ];
    }

    // ------------------------------------------------------------------ QC
    /** Reviews with the submission they decided on (latest inspection.submitted event before the decision). */
    private static function reviewSql(): string
    {
        return "SELECT q.*, s.sub_job_no, s.inspector_id, s.override_by, t.name type_name, u.name inspector_name,
                    (SELECT MAX(a.created_at) FROM audit_logs a WHERE a.entity_type = 'sub_job' AND a.entity_id = q.sub_job_id
                        AND a.action = 'inspection.submitted' AND a.created_at <= q.created_at) submitted_at
                FROM qc_reviews q JOIN sub_jobs s ON s.id = q.sub_job_id JOIN inspection_types t ON t.id = s.inspection_type_id
                LEFT JOIN users u ON u.id = s.inspector_id";
    }

    public static function qcMetrics(?int $uid, string $from, string $to): array
    {
        [$a, $b] = self::range($from, $to);
        $w = $uid ? ' AND q.reviewer_id = ' . (int) $uid : '';
        $rows = DB::all(self::reviewSql() . " WHERE q.created_at BETWEEN ? AND ?$w", [$a, $b]);
        $n = count($rows);
        $hours = array_values(array_filter(array_map(fn ($r) => $r['submitted_at'] ? (strtotime($r['created_at']) - strtotime($r['submitted_at'])) / 3600 : null, $rows), fn ($h) => $h !== null));
        $approved = count(array_filter($rows, fn ($r) => $r['decision'] === 'approved'));
        $certs = (int) DB::value("SELECT COUNT(*) FROM certificates WHERE status IN ('issued','revoked') AND issue_date BETWEEN ? AND ?" . ($uid ? ' AND reviewer_id = ' . (int) $uid : ''), [$from, $to]);
        return [
            'qc_reviews' => $n,
            'qc_review_hours' => $hours ? round(array_sum($hours) / count($hours), 1) : null,
            'qc_sla' => $hours ? round(count(array_filter($hours, fn ($h) => $h <= 24)) / count($hours) * 100, 1) : null,
            'qc_approval_rate' => self::pct($approved, $n),
            'qc_rework_rate' => self::pct($n - $approved, $n),
            'qc_certificates' => $certs,
        ];
    }

    public static function qc(int $uid, array $P): array
    {
        $cur = self::qcMetrics($uid, $P['from'], $P['to']);
        $prev = self::qcMetrics($uid, $P['prev_from'], $P['prev_to']);
        $q = DB::one("SELECT COUNT(*) n, MAX(TIMESTAMPDIFF(MINUTE, submitted_at, NOW())) / 60 oldest FROM sub_jobs WHERE status = 'submitted'");
        $cards = [];
        foreach ($cur as $k => $v) $cards[] = self::card($k, $v, $prev[$k] ?? null);
        $cards[] = self::card('qc_queue', (int) $q['n']);
        $cards[] = self::card('qc_oldest_hours', $q['oldest'] === null ? null : round((float) $q['oldest'], 1));

        [$a, $b] = self::range($P['from'], $P['to']);
        $mine = DB::all(self::reviewSql() . ' WHERE q.reviewer_id = ? AND q.created_at BETWEEN ? AND ? ORDER BY q.id DESC', [$uid, $a, $b]);
        $buckets = ['< 4 h' => 0, '4–24 h' => 0, '1–3 days' => 0, '> 3 days' => 0];
        foreach ($mine as &$r) {
            $r['review_hours'] = $r['submitted_at'] ? round((strtotime($r['created_at']) - strtotime($r['submitted_at'])) / 3600, 1) : null;
            $h = $r['review_hours'];
            if ($h === null) continue;
            $buckets[$h < 4 ? '< 4 h' : ($h <= 24 ? '4–24 h' : ($h <= 72 ? '1–3 days' : '> 3 days'))]++;
        }
        unset($r);
        $team = self::qcMetrics(null, $P['from'], $P['to']);
        return [
            'cards' => $cards,
            'team' => $team,
            'reviews' => $mine,
            'buckets' => $buckets,
            'by_inspector' => DB::all("SELECT u.name inspector, COUNT(*) reviews, SUM(q.decision = 'rework') reworks,
                    SUM(q.decision = 'approved') approvals
                FROM qc_reviews q JOIN sub_jobs s ON s.id = q.sub_job_id JOIN users u ON u.id = s.inspector_id
                WHERE q.created_at BETWEEN ? AND ? GROUP BY u.id ORDER BY reworks DESC, reviews DESC", [$a, $b]),
            'reviewers' => DB::all("SELECT u.name reviewer, COUNT(q.id) reviews, SUM(q.decision = 'approved') approvals
                FROM users u LEFT JOIN qc_reviews q ON q.reviewer_id = u.id AND q.created_at BETWEEN ? AND ?
                WHERE u.role = 'qc' AND u.status = 'active' GROUP BY u.id ORDER BY reviews DESC", [$a, $b]),
            'weekly' => self::weekly("SELECT COUNT(*) FROM qc_reviews WHERE reviewer_id = $uid AND created_at BETWEEN ? AND ?"),
        ];
    }

    /** Last 12 ISO weeks for a COUNT query with two datetime placeholders. */
    private static function weekly(string $countSql): array
    {
        $out = [];
        $monday = new DateTimeImmutable('monday this week');
        for ($i = 11; $i >= 0; $i--) {
            $s = $monday->modify("-$i weeks");
            $e = $s->modify('+6 days');
            $out[] = ['label' => $s->format('d M'), 'n' => (int) DB::value($countSql, [$s->format('Y-m-d 00:00:00'), $e->format('Y-m-d 23:59:59')])];
        }
        return $out;
    }

    // ------------------------------------------------------------------ formatting
    public static function fmt($value, string $unit): string
    {
        if ($value === null) return '—';
        if ($unit === '%') return rtrim(rtrim(number_format((float) $value, 1), '0'), '.') . '%';
        if ($unit === 'h' || $unit === 'd') return number_format((float) $value, 1) . ' ' . $unit;
        return number_format((float) $value, is_float($value) && floor($value) != $value ? 1 : 0);
    }

    public static function statusLabel(string $s): string
    {
        return ['green' => 'On target', 'amber' => 'Watch', 'red' => 'Off target'][$s] ?? '—';
    }
}
