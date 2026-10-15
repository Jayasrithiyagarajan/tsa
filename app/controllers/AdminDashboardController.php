<?php

final class AdminDashboardController extends Controller
{
    public function index(): void
    {
        $k = DB::one("SELECT
            (SELECT COUNT(*) FROM service_requests WHERE status IN ('new','under_review')) new_requests,
            (SELECT COUNT(*) FROM service_requests WHERE status = 'new') untouched_requests,
            (SELECT COUNT(*) FROM jobs WHERE status = 'pending_assignment') jobs_unassigned,
            (SELECT COUNT(*) FROM sub_jobs WHERE status = 'pending') subjobs_unassigned,
            (SELECT COUNT(*) FROM sub_jobs WHERE status IN ('assigned','in_progress','rework')) subjobs_active,
            (SELECT COUNT(*) FROM sub_jobs WHERE status = 'in_progress') in_progress,
            (SELECT COUNT(*) FROM sub_jobs WHERE status = 'submitted') awaiting_qc,
            (SELECT COUNT(*) FROM certificates WHERE status = 'issued') certs_issued,
            (SELECT COUNT(*) FROM certificates WHERE status IN ('draft','pending_qc')) certs_pending,
            (SELECT COUNT(*) FROM checklists WHERE status = 'completed') checklists_done,
            (SELECT COUNT(*) FROM stickers WHERE status = 'in_stock') stickers_stock,
            (SELECT COUNT(*) FROM stickers WHERE status = 'closed') stickers_issued,
            (SELECT COUNT(*) FROM stickers WHERE status = 'allocated') stickers_reserved,
            (SELECT COUNT(*) FROM jobs WHERE status = 'completed') jobs_completed,
            (SELECT COUNT(*) FROM jobs WHERE status NOT IN ('completed','cancelled')) jobs_open,
            (SELECT COUNT(*) FROM equipment WHERE next_inspection_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY) due_soon");

        $funnel = DB::all("SELECT status, COUNT(*) n FROM sub_jobs GROUP BY status");
        $months = DB::all("SELECT DATE_FORMAT(issue_date, '%Y-%m') m, COUNT(*) n FROM certificates
                           WHERE status = 'issued' AND issue_date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH) GROUP BY m");
        $requests = DB::all("SELECT * FROM service_requests ORDER BY id DESC LIMIT 6");
        $attention = DB::all("SELECT j.*, c.name client_name,
                                 (SELECT COUNT(*) FROM sub_jobs s WHERE s.job_id = j.id AND s.status = 'pending') pending_n,
                                 (SELECT COUNT(*) FROM sub_jobs s WHERE s.job_id = j.id AND s.status <> 'cancelled') total_n
                              FROM jobs j JOIN clients c ON c.id = j.client_id
                              WHERE j.status = 'pending_assignment' OR EXISTS (SELECT 1 FROM sub_jobs s WHERE s.job_id = j.id AND s.status = 'pending')
                              ORDER BY FIELD(j.priority,'urgent','high','normal','low'), j.scheduled_date LIMIT 6");
        $workload = JobService::inspectorsFor();
        $activity = DB::all("SELECT a.*, u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 8");

        $this->view('admin/dashboard', compact('k', 'funnel', 'months', 'requests', 'attention', 'workload', 'activity') + [
            'title' => 'Dashboard', 'crumbs' => ['Dashboard' => null],
        ]);
    }

    public function search(): void
    {
        $q = trim((string) input('q', ''));
        $like = '%' . $q . '%';
        $res = ['jobs' => [], 'subjobs' => [], 'certs' => [], 'clients' => [], 'requests' => []];
        if (mb_strlen($q) >= 2) {
            $res['jobs'] = DB::all("SELECT j.id, j.job_no, j.title, j.status, c.name client_name FROM jobs j JOIN clients c ON c.id = j.client_id WHERE j.job_no LIKE ? OR j.title LIKE ? OR j.po_no LIKE ? LIMIT 10", [$like, $like, $like]);
            $res['subjobs'] = DB::all(JobService::SUBJOB_SQL . " WHERE sj.sub_job_no LIKE ? OR e.equipment_no LIKE ? OR e.serial_no LIKE ? OR e.description LIKE ? LIMIT 10", [$like, $like, $like, $like]);
            $res['certs'] = DB::all("SELECT c.certificate_no, c.status, c.sub_job_id, sj.sub_job_no FROM certificates c JOIN sub_jobs sj ON sj.id = c.sub_job_id WHERE c.certificate_no LIKE ? LIMIT 10", [$like]);
            $res['clients'] = DB::all("SELECT id, code, name, city FROM clients WHERE name LIKE ? OR code LIKE ? OR email LIKE ? LIMIT 10", [$like, $like, $like]);
            $res['requests'] = DB::all("SELECT id, request_no, company_name, status FROM service_requests WHERE request_no LIKE ? OR company_name LIKE ? OR email LIKE ? LIMIT 10", [$like, $like, $like]);
        }
        $this->view('admin/search', ['title' => 'Search', 'q' => $q, 'res' => $res, 'crumbs' => ['Search' => null]]);
    }
}
