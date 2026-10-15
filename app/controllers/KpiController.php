<?php

/** KPI scorecards for Admin, Inspector and QC portals, each with an Excel (.xlsx) export. */
final class KpiController extends Controller
{
    // ------------------------------------------------------------------ admin
    public function admin(): void
    {
        $P = KpiService::period();
        $data = KpiService::admin($P);
        if (($_GET['export'] ?? '') === 'xlsx') $this->exportAdmin($P, $data);
        $this->view('kpi/admin', $data + ['P' => $P, 'title' => 'KPI Dashboard', 'crumbs' => ['KPI & Reports' => null]]);
    }

    /** Admin drill-down into one inspector's scorecard. */
    public function adminInspector(string $id): void
    {
        $u = DB::one("SELECT id, name FROM users WHERE id = ? AND role = 'inspector'", [(int) $id]);
        if (!$u) $this->notFound();
        $this->renderInspector((int) $u['id'], $u['name'], ['KPI & Reports' => 'admin/kpi', $u['name'] => null]);
    }

    public function adminQc(string $id): void
    {
        $u = DB::one("SELECT id, name FROM users WHERE id = ? AND role = 'qc'", [(int) $id]);
        if (!$u) $this->notFound();
        $this->renderQc((int) $u['id'], $u['name'], ['KPI & Reports' => 'admin/kpi', $u['name'] => null]);
    }

    public function targets(): void
    {
        $this->attempt(function () {
            foreach (array_keys(KpiService::CATALOG) as $k) {
                if (!array_key_exists($k, $_POST['t'] ?? [])) continue;
                $v = trim((string) $_POST['t'][$k]);
                if ($v !== '' && !is_numeric($v)) throw new DomainException('Targets must be numbers.');
                DB::run('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', ['kpi_target_' . $k, $v === '' ? null : $v]);
            }
            $sla = max(0, min(60, (int) input('sla_days', 3)));
            DB::run('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', ['kpi_sla_days', (string) $sla]);
            Audit::log('kpi.targets_updated', 'settings');
        }, 'KPI targets saved.');
    }

    // ------------------------------------------------------------------ inspector
    public function inspector(): void
    {
        $this->renderInspector((int) Auth::id(), Auth::user()['name'], ['My KPIs' => null]);
    }

    private function renderInspector(int $uid, string $name, array $crumbs): void
    {
        $P = KpiService::period();
        $data = KpiService::inspector($uid, $P);
        if (($_GET['export'] ?? '') === 'xlsx') $this->exportInspector($P, $data, $name);
        $this->view('kpi/inspector', $data + ['P' => $P, 'who' => $name, 'self' => $uid === Auth::id(), 'title' => 'KPIs · ' . $name, 'crumbs' => $crumbs]);
    }

    // ------------------------------------------------------------------ QC
    public function qc(): void
    {
        $this->renderQc((int) Auth::id(), Auth::user()['name'], ['QC KPIs' => null]);
    }

    private function renderQc(int $uid, string $name, array $crumbs): void
    {
        $P = KpiService::period();
        $data = KpiService::qc($uid, $P);
        if (($_GET['export'] ?? '') === 'xlsx') $this->exportQc($P, $data, $name);
        $this->view('kpi/qc', $data + ['P' => $P, 'who' => $name, 'self' => $uid === Auth::id(), 'title' => 'QC KPIs · ' . $name, 'crumbs' => $crumbs]);
    }

    // ------------------------------------------------------------------ Excel exports
    private function summarySheet(Xlsx $x, string $title, array $P, array $cards): void
    {
        $rows = [];
        foreach ($cards as $c) {
            $isPct = $c['unit'] === '%';
            $rows[] = [
                $c['label'],
                $c['value'] === null ? null : ($isPct ? $c['value'] / 100 : $c['value']),
                $c['prev'] === null ? null : ($isPct ? $c['prev'] / 100 : $c['prev']),
                $c['target'] === null ? null : ($isPct ? $c['target'] / 100 : $c['target']),
                ['%' => 'percent', 'h' => 'hours', 'd' => 'days'][$c['unit']] ?? 'count',
                ['up' => 'Higher is better', 'down' => 'Lower is better'][$c['dir']] ?? 'Information',
                $c['status'] === 'none' ? '' : KpiService::statusLabel($c['status']),
                $c['desc'],
            ];
        }
        // mixed units in one column: write percentages as fractions with % shown in the Unit column
        $x->sheet('Summary', ['KPI' => 'bold', 'Value' => 'num', 'Previous period' => 'num', 'Target' => 'num', 'Unit' => 'text',
            'Direction' => 'text', 'Status' => 'rag', 'Definition' => 'text'], array_map(function ($r) {
                if ($r[4] === 'percent') foreach ([1, 2, 3] as $i) if ($r[$i] !== null) $r[$i] = round($r[$i] * 100, 1);
                return $r;
            }, $rows), [
                'title' => $title,
                'subtitle' => $P['label'] . ' (' . fmt_date($P['from']) . ' – ' . fmt_date($P['to']) . ') · previous period ' . fmt_date($P['prev_from']) . ' – ' . fmt_date($P['prev_to']) . ' · generated ' . date('d-M-Y H:i'),
                'widths' => [32, 12, 16, 10, 10, 18, 12, 70], 'filter' => false,
            ]);
    }

    private function exportAdmin(array $P, array $d): void
    {
        $x = new Xlsx();
        $this->summarySheet($x, 'TUV EAST · Operations KPI scorecard', $P, $d['cards']);
        $x->sheet('Inspectors', ['Inspector' => 'text', 'Completed' => 'int', 'Avg inspection (h)' => 'dec', 'First-pass yield' => 'pct',
            'Rework returns' => 'int', 'Submitted' => 'int', 'On-time submission' => 'pct', 'Open now' => 'int'],
            array_map(fn ($r) => [$r['name'], (int) $r['completed'], $r['insp_hours'] === null ? null : round((float) $r['insp_hours'], 1),
                (int) $r['completed'] ? (int) $r['fpy_n'] / (int) $r['completed'] : null, (int) $r['rework'], (int) $r['submitted'],
                (int) $r['submitted'] ? (int) $r['ontime_n'] / (int) $r['submitted'] : null, (int) $r['open_now']], $d['inspectors']),
            ['title' => 'Inspector productivity & quality', 'subtitle' => $P['label']]);
        $x->sheet('Clients', ['Client' => 'text', 'Code' => 'text', 'Requests' => 'int', 'Inspections completed' => 'int', 'Certificates' => 'int', 'Open sub-jobs now' => 'int'],
            array_map(fn ($r) => [$r['client'], $r['code'], (int) $r['requests'], (int) $r['completed'], (int) $r['certificates'], (int) $r['open_now']], $d['clients']),
            ['title' => 'Client volume', 'subtitle' => $P['label']]);
        $x->sheet('Request channels', ['Channel' => 'text', 'Requests' => 'int', 'Converted' => 'int', 'Conversion' => 'pct'],
            array_map(fn ($r) => [Workflow::label($r['channel']), (int) $r['n'], (int) $r['converted'], (int) $r['n'] ? (int) $r['converted'] / (int) $r['n'] : null], $d['channels']),
            ['title' => 'Requests by channel', 'subtitle' => $P['label']]);
        $x->sheet('Inspection types', ['Inspection type' => 'text', 'Completed' => 'int', 'First-pass yield' => 'pct'],
            array_map(fn ($r) => [$r['type'], (int) $r['n'], (int) $r['n'] ? (int) $r['fpy'] / (int) $r['n'] : null], $d['types']),
            ['title' => 'Completed inspections by type', 'subtitle' => $P['label']]);
        $x->sheet('Monthly trend', ['Month' => 'text', 'Inspections completed' => 'int', 'Certificates issued' => 'int', 'First-pass yield' => 'pct100'],
            array_map(fn ($r) => [$r['month'], $r['completed'], $r['certificates'], $r['fpy']], $d['monthly']), ['title' => 'Last 6 months', 'filter' => false]);
        [$a, $b] = [$P['from'] . ' 00:00:00', $P['to'] . ' 23:59:59'];
        $x->sheet('Inspection detail', ['Sub-job' => 'text', 'Client' => 'text', 'Inspection' => 'text', 'Inspector' => 'text', 'Created' => 'datetime',
            'Assigned' => 'datetime', 'Started' => 'datetime', 'Submitted' => 'datetime', 'Approved' => 'datetime', 'Inspection (h)' => 'dec',
            'Created → approved (d)' => 'dec', 'QC reworks' => 'int', 'Result' => 'text', 'Certificate' => 'text'],
            array_map(fn ($r) => [$r['sub_job_no'], $r['client_name'], $r['type_name'], $r['inspector_name'], $r['created_at'], $r['assigned_at'], $r['started_at'],
                $r['submitted_at'], $r['approved_at'], $r['insp_h'] === null ? null : round((float) $r['insp_h'], 1),
                round((float) $r['cycle_d'], 1), (int) $r['reworks'], $r['result'] ? Workflow::label($r['result']) : '', $r['certificate_no']],
                DB::all("SELECT s.sub_job_no, c.name client_name, t.name type_name, u.name inspector_name, s.created_at, s.started_at, s.submitted_at, s.approved_at, s.result,
                        (SELECT MIN(assigned_at) FROM job_assignments ja WHERE ja.sub_job_id = s.id) assigned_at,
                        TIMESTAMPDIFF(MINUTE, s.started_at, s.submitted_at) / 60 insp_h, TIMESTAMPDIFF(HOUR, s.created_at, s.approved_at) / 24 cycle_d,
                        (SELECT COUNT(*) FROM qc_reviews q WHERE q.sub_job_id = s.id AND q.decision = 'rework') reworks, ce.certificate_no
                    FROM sub_jobs s JOIN jobs j ON j.id = s.job_id JOIN clients c ON c.id = j.client_id JOIN inspection_types t ON t.id = s.inspection_type_id
                    LEFT JOIN users u ON u.id = s.inspector_id LEFT JOIN certificates ce ON ce.sub_job_id = s.id
                    WHERE s.status = 'approved' AND s.approved_at BETWEEN ? AND ? ORDER BY s.approved_at", [$a, $b])),
            ['title' => 'Approved inspections — workflow timestamps', 'subtitle' => $P['label']]);
        Audit::log('export.xlsx', 'report', null, ['file' => 'admin-kpis', 'period' => $P['key']]);
        $x->download('admin-kpi-' . $P['from'] . '-to-' . $P['to']);
    }

    private function exportInspector(array $P, array $d, string $name): void
    {
        $x = new Xlsx();
        $this->summarySheet($x, 'TUV EAST · Inspector KPI scorecard — ' . $name, $P, $d['cards']);
        $b = $d['bench'];
        $x->sheet('Team benchmark', ['Measure' => 'text', 'Team average' => 'dec'], [
            ['Inspections approved per inspector', $b['completed']], ['Average inspection time (h)', $b['insp_hours']], ['First-pass yield (%)', $b['fpy']],
            ['Rank by approved inspections', $b['rank'] ? $b['rank'] . ' of ' . $b['of'] : '—'],
        ], ['title' => 'Team benchmark (anonymous)', 'subtitle' => $P['label'], 'filter' => false]);
        $x->sheet('My inspections', ['Sub-job' => 'text', 'Inspection' => 'text', 'Client' => 'text', 'Scheduled' => 'date', 'Started' => 'datetime',
            'Submitted' => 'datetime', 'Approved' => 'datetime', 'Inspection (h)' => 'dec', 'QC reworks' => 'int', 'Status' => 'text', 'Result' => 'text'],
            array_map(fn ($r) => [$r['sub_job_no'], $r['type_name'], $r['client_name'], $r['scheduled_date'], $r['started_at'], $r['submitted_at'], $r['approved_at'],
                $r['insp_hours'] === null ? null : round((float) $r['insp_hours'], 1), (int) $r['reworks'], Workflow::label($r['status']),
                $r['result'] ? Workflow::label($r['result']) : ''], $d['recent']),
            ['title' => 'Inspections submitted / approved', 'subtitle' => $P['label']]);
        $x->sheet('By inspection type', ['Inspection type' => 'text', 'Approved' => 'int', 'Avg inspection (h)' => 'dec'],
            array_map(fn ($r) => [$r['type'], (int) $r['n'], $r['hours'] === null ? null : round((float) $r['hours'], 1)], $d['by_type']), ['title' => 'Approved by type', 'subtitle' => $P['label']]);
        $x->sheet('QC returns', ['Date' => 'datetime', 'Sub-job' => 'text', 'Reviewer' => 'text', 'Comments' => 'text'],
            array_map(fn ($r) => [$r['created_at'], $r['sub_job_no'], $r['reviewer'], $r['comments']], $d['reworks']), ['title' => 'Rework requests from QC', 'subtitle' => $P['label'], 'widths' => [18, 22, 18, 90]]);
        $x->sheet('Trend', ['Month' => 'text', 'Approved' => 'int', 'First-pass yield' => 'pct100'],
            array_map(fn ($r) => [$r['month'], $r['completed'], $r['fpy']], $d['monthly']), ['title' => 'Last 6 months', 'filter' => false]);
        Audit::log('export.xlsx', 'report', null, ['file' => 'inspector-kpis', 'inspector' => $name]);
        $x->download('inspector-kpi-' . preg_replace('/\W+/', '-', strtolower($name)) . '-' . $P['from'] . '-to-' . $P['to']);
    }

    private function exportQc(array $P, array $d, string $name): void
    {
        $x = new Xlsx();
        $this->summarySheet($x, 'TUV EAST · QC KPI scorecard — ' . $name, $P, $d['cards']);
        $x->sheet('My reviews', ['Decided' => 'datetime', 'Sub-job' => 'text', 'Inspection' => 'text', 'Inspector' => 'text', 'Submitted' => 'datetime',
            'Review time (h)' => 'dec', 'Within 24 h' => 'text', 'Decision' => 'text', 'Override' => 'text', 'Comments' => 'text'],
            array_map(fn ($r) => [$r['created_at'], $r['sub_job_no'], $r['type_name'], $r['inspector_name'], $r['submitted_at'], $r['review_hours'],
                $r['review_hours'] === null ? '' : ($r['review_hours'] <= 24 ? 'Yes' : 'No'), Workflow::label($r['decision'] === 'approved' ? 'approved' : 'rework'),
                $r['override_by'] ? 'Yes' : '', $r['comments']], $d['reviews']),
            ['title' => 'Review decisions', 'subtitle' => $P['label'], 'widths' => [18, 20, 26, 18, 18, 14, 11, 16, 9, 70]]);
        $x->sheet('Turnaround', ['Review time' => 'text', 'Reviews' => 'int'], array_map(fn ($k, $v) => [$k, $v], array_keys($d['buckets']), $d['buckets']),
            ['title' => 'Review turnaround distribution', 'subtitle' => $P['label'], 'filter' => false]);
        $x->sheet('Inspector quality', ['Inspector' => 'text', 'Reviews' => 'int', 'Approvals' => 'int', 'Reworks' => 'int', 'Rework rate' => 'pct'],
            array_map(fn ($r) => [$r['inspector'], (int) $r['reviews'], (int) $r['approvals'], (int) $r['reworks'], (int) $r['reviews'] ? (int) $r['reworks'] / (int) $r['reviews'] : null], $d['by_inspector']),
            ['title' => 'Inspector quality (all QC reviewers)', 'subtitle' => $P['label']]);
        $x->sheet('QC team', ['Reviewer' => 'text', 'Reviews' => 'int', 'Approvals' => 'int'],
            array_map(fn ($r) => [$r['reviewer'], (int) $r['reviews'], (int) $r['approvals']], $d['reviewers']), ['title' => 'QC team workload', 'subtitle' => $P['label']]);
        Audit::log('export.xlsx', 'report', null, ['file' => 'qc-kpis', 'reviewer' => $name]);
        $x->download('qc-kpi-' . preg_replace('/\W+/', '-', strtolower($name)) . '-' . $P['from'] . '-to-' . $P['to']);
    }
}
