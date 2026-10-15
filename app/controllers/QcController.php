<?php

final class QcController extends Controller
{
    public function dashboard(): void
    {
        $me = Auth::id();
        $k = DB::one("SELECT
            (SELECT COUNT(*) FROM sub_jobs WHERE status = 'submitted') queue,
            (SELECT COUNT(*) FROM sub_jobs WHERE status = 'submitted' AND submitted_at < NOW() - INTERVAL 1 DAY) overdue,
            (SELECT COUNT(*) FROM qc_reviews WHERE reviewer_id = ? AND decision = 'approved' AND created_at >= CURDATE() - INTERVAL 30 DAY) approved30,
            (SELECT COUNT(*) FROM qc_reviews WHERE reviewer_id = ? AND decision = 'rework' AND created_at >= CURDATE() - INTERVAL 30 DAY) rework30,
            (SELECT COUNT(*) FROM sub_jobs WHERE status = 'submitted' AND override_by IS NOT NULL) overrides,
            (SELECT COUNT(*) FROM certificates WHERE status = 'issued' AND issue_date = CURDATE()) issued_today", [$me, $me]);
        $queue = DB::all(JobService::SUBJOB_SQL . " WHERE sj.status = 'submitted' ORDER BY sj.submitted_at LIMIT 8");
        $byType = DB::all("SELECT t.name, COUNT(*) n FROM sub_jobs s JOIN inspection_types t ON t.id = s.inspection_type_id WHERE s.status = 'submitted' GROUP BY t.name ORDER BY n DESC");
        $this->view('qc/dashboard', compact('k', 'queue', 'byType') + ['title' => 'QC Dashboard', 'crumbs' => ['Dashboard' => null]]);
    }

    public function queue(): void
    {
        $type = (int) input('type', 0);
        $p = [];
        $w = "sj.status = 'submitted'";
        if ($type) { $w .= ' AND sj.inspection_type_id = ?'; $p[] = $type; }
        $rows = DB::all(JobService::SUBJOB_SQL . " WHERE $w ORDER BY FIELD(j.priority,'urgent','high','normal','low'), sj.submitted_at", $p);
        foreach ($rows as &$r) {
            $r['cert'] = DB::one('SELECT certificate_no, result FROM certificates WHERE sub_job_id = ?', [$r['id']]);
            $cl = DB::value('SELECT id FROM checklists WHERE sub_job_id = ?', [$r['id']]);
            $r['stats'] = $cl ? InspectionService::checklistStats((int) $cl) : null;
        }
        unset($r);
        $this->view('qc/queue', compact('rows', 'type') + [
            'types' => DB::all('SELECT id, name FROM inspection_types ORDER BY name'),
            'title' => 'Review Queue', 'crumbs' => ['Review Queue' => null],
        ]);
    }

    public function review(string $id): void
    {
        $sj = JobService::subJob((int) $id);
        if (!$sj) $this->notFound();
        $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ?', [$sj['id']]);
        $checklist = DB::one('SELECT * FROM checklists WHERE sub_job_id = ?', [$sj['id']]);
        $stats = $checklist ? InspectionService::checklistStats((int) $checklist['id']) : null;
        $failed = $checklist ? DB::all("SELECT i.label, i.section, r.result, r.remarks FROM checklist_responses r JOIN checklist_template_items i ON i.id = r.item_id
                                         WHERE r.checklist_id = ? AND (r.result = 'fail' OR (r.remarks IS NOT NULL AND r.remarks <> '')) ORDER BY i.sort_order", [$checklist['id']]) : [];
        $issues = InspectionService::issues((int) $sj['id']);
        $attachments = DB::all('SELECT * FROM attachments WHERE sub_job_id = ? ORDER BY id', [$sj['id']]);
        $sticker = DB::one("SELECT * FROM stickers WHERE sub_job_id = ? AND status = 'closed' LIMIT 1", [$sj['id']]);
        $reviews = DB::all('SELECT q.*, u.name reviewer FROM qc_reviews q JOIN users u ON u.id = q.reviewer_id WHERE q.sub_job_id = ? ORDER BY q.id DESC', [$sj['id']]);
        $override = $sj['override_by'] ? DB::value('SELECT name FROM users WHERE id = ?', [$sj['override_by']]) : null;
        $this->view('qc/review', compact('sj', 'cert', 'checklist', 'stats', 'failed', 'issues', 'attachments', 'sticker', 'reviews', 'override') + [
            'title' => 'Review ' . $sj['sub_job_no'],
            'crumbs' => Auth::is('admin') ? ['Jobs & Sub-jobs' => 'admin/jobs', $sj['job_no'] => 'admin/jobs/' . $sj['job_id'], 'QC review' => null] : ['Review Queue' => 'qc/queue', $sj['sub_job_no'] => null],
        ]);
    }

    public function decide(string $id): void
    {
        $this->attempt(function () use ($id) {
            if (input('decision') === 'approve') {
                QcService::approve((int) $id, nullable(input('comments')));
                flash('success', 'Approved. Once every inspection of the job passes QC, an admin gives the final approval and the client receives the certificates.');
            } elseif (input('decision') === 'rework') {
                QcService::rework((int) $id, (string) input('comments', ''));
                flash('success', 'Returned to the inspector for rework.');
            } else {
                throw new DomainException('Choose approve or return for rework.');
            }
            if (Auth::is('admin')) return 'admin/jobs/' . DB::value('SELECT job_id FROM sub_jobs WHERE id = ?', [(int) $id]);
            $next = DB::value("SELECT id FROM sub_jobs WHERE status = 'submitted' ORDER BY submitted_at LIMIT 1");
            return $next ? 'qc/review/' . $next : 'qc/queue';
        });
    }

    public function history(): void
    {
        [$rows, $meta] = paginate("SELECT q.*, sj.sub_job_no, sj.job_id, t.name type_name, c.name client_name, u.name inspector_name, ce.certificate_no
                                   FROM qc_reviews q JOIN sub_jobs sj ON sj.id = q.sub_job_id JOIN jobs j ON j.id = sj.job_id JOIN clients c ON c.id = j.client_id
                                   JOIN inspection_types t ON t.id = sj.inspection_type_id LEFT JOIN users u ON u.id = sj.inspector_id
                                   LEFT JOIN certificates ce ON ce.sub_job_id = sj.id WHERE q.reviewer_id = ? ORDER BY q.id DESC", [Auth::id()]);
        $this->view('qc/history', compact('rows', 'meta') + ['title' => 'My Decisions', 'crumbs' => ['My Decisions' => null]]);
    }

    public function certificates(): void
    {
        $q = trim((string) input('q', ''));
        $p = [];
        $w = "c.status IN ('issued','revoked')";
        if ($q !== '') { $w .= ' AND (c.certificate_no LIKE ? OR cl.name LIKE ? OR e.equipment_no LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
        [$rows, $meta] = paginate("SELECT c.*, t.name type_name, cl.name client_name, e.description eq_description, e.equipment_no, u.name reviewer_name
                                   FROM certificates c JOIN sub_jobs sj ON sj.id = c.sub_job_id JOIN jobs j ON j.id = sj.job_id JOIN clients cl ON cl.id = j.client_id
                                   JOIN inspection_types t ON t.id = sj.inspection_type_id LEFT JOIN equipment e ON e.id = sj.equipment_id LEFT JOIN users u ON u.id = c.reviewer_id
                                   WHERE $w ORDER BY c.issue_date DESC, c.id DESC", $p);
        $this->view('qc/certificates', compact('rows', 'meta', 'q') + ['title' => 'Issued Certificates', 'crumbs' => ['Issued Certificates' => null]]);
    }
}
