<?php

/**
 * JSON API v1 — session-authenticated (same cookie as the web portals); state-changing calls
 * require the X-CSRF-Token header. Responses: { data: … } or { error: "message" } with HTTP status.
 */
final class ApiController extends Controller
{
    public function me(): void
    {
        $u = Auth::user();
        json_out(['data' => ['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role'], 'client_id' => $u['client_id'] ? (int) $u['client_id'] : null]]);
    }

    public function dashboard(): void
    {
        switch (Auth::role()) {
            case 'admin':
                $d = DB::one("SELECT (SELECT COUNT(*) FROM service_requests WHERE status IN ('new','under_review')) new_requests,
                    (SELECT COUNT(*) FROM sub_jobs WHERE status='pending') awaiting_assignment,
                    (SELECT COUNT(*) FROM sub_jobs WHERE status IN ('assigned','in_progress','rework')) in_inspection,
                    (SELECT COUNT(*) FROM sub_jobs WHERE status='submitted') awaiting_qc,
                    (SELECT COUNT(*) FROM checklists WHERE status='completed') checklists_completed,
                    (SELECT COUNT(*) FROM certificates WHERE status='issued') certificates_issued,
                    (SELECT COUNT(*) FROM certificates WHERE status IN ('draft','pending_qc')) certificates_pending,
                    (SELECT COUNT(*) FROM stickers WHERE status='in_stock') stickers_in_stock,
                    (SELECT COUNT(*) FROM stickers WHERE status='issued') stickers_issued,
                    (SELECT COUNT(*) FROM jobs WHERE status NOT IN ('completed','cancelled')) jobs_pending,
                    (SELECT COUNT(*) FROM jobs WHERE status='completed') jobs_completed");
                break;
            case 'inspector':
                $d = DB::one("SELECT SUM(status='assigned') assigned, SUM(status='in_progress') in_progress, SUM(status='rework') rework,
                    SUM(status='submitted') submitted, SUM(status='approved') approved FROM sub_jobs WHERE inspector_id = ?", [Auth::id()]);
                break;
            case 'qc':
                $d = DB::one("SELECT COUNT(*) awaiting_review FROM sub_jobs WHERE status='submitted'");
                break;
            default:
                $d = DB::one("SELECT (SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status NOT IN ('completed','cancelled')) active_jobs,
                    (SELECT COUNT(*) FROM equipment WHERE client_id = ? AND next_inspection_date <= CURDATE() + INTERVAL 30 DAY) due_30_days", [Auth::clientId(), Auth::clientId()]);
        }
        json_out(['data' => array_map(fn ($v) => (int) $v, $d ?: [])]);
    }

    public function jobs(): void
    {
        $p = [];
        $w = '1=1';
        if (Auth::is('inspector')) { $w = 'EXISTS (SELECT 1 FROM sub_jobs s WHERE s.job_id = j.id AND s.inspector_id = ?)'; $p[] = Auth::id(); }
        if ($s = input('status')) { $w .= ' AND j.status = ?'; $p[] = $s; }
        $limit = max(1, min(200, (int) input('limit', 50)));
        $rows = DB::all("SELECT j.id, j.job_no, j.title, j.status, j.priority, j.scheduled_date, j.location, c.name client
                         FROM jobs j JOIN clients c ON c.id = j.client_id WHERE $w ORDER BY j.id DESC LIMIT $limit", $p);
        json_out(['data' => $rows]);
    }

    public function job(string $id): void
    {
        $job = DB::one('SELECT j.*, c.name client_name FROM jobs j JOIN clients c ON c.id = j.client_id WHERE j.id = ?', [(int) $id]);
        if (!$job) json_out(['error' => 'Not found'], 404);
        $subs = JobService::subJobsOf((int) $id);
        if (Auth::is('inspector')) {
            $subs = array_values(array_filter($subs, fn ($s) => (int) $s['inspector_id'] === Auth::id()));
            if (!$subs) json_out(['error' => 'Forbidden'], 403);
        }
        $job['progress'] = JobService::progress((int) $id);
        $job['sub_jobs'] = array_map(fn ($s) => [
            'id' => (int) $s['id'], 'sub_job_no' => $s['sub_job_no'], 'seq' => (int) $s['seq'], 'status' => $s['status'], 'result' => $s['result'],
            'inspection_type' => $s['type_code'], 'equipment' => $s['eq_description'], 'equipment_no' => $s['equipment_no'], 'inspector' => $s['inspector_name'],
        ], $subs);
        json_out(['data' => $job]);
    }

    public function inspectors(): void
    {
        json_out(['data' => JobService::inspectorsFor(((int) input('type')) ?: null)]);
    }

    public function clientEquipment(string $id): void
    {
        json_out(['data' => DB::all('SELECT id, description, equipment_no, serial_no, inspection_type_id FROM equipment WHERE client_id = ? AND status <> ? ORDER BY description', [(int) $id, 'retired'])]);
    }

    public function saveChecklist(string $id): void
    {
        try {
            $stats = InspectionService::saveChecklist((int) $id, (array) ($_POST['r'] ?? []), [
                'serial_no' => (string) ($_POST['serial_no'] ?? ''), 'inspection_date' => $_POST['inspection_date'] ?? null, 'remarks' => $_POST['cl_remarks'] ?? null, 'data' => $_POST['cl_data'] ?? null,
            ]);
            json_out(['data' => ['saved' => true], 'stats' => $stats]);
        } catch (DomainException $e) {
            json_out(['error' => $e->getMessage()], 422);
        }
    }

    public function saveCertificate(string $id): void
    {
        try {
            InspectionService::saveCertificate((int) $id, $_POST);
            json_out(['data' => ['saved' => true]]);
        } catch (DomainException $e) {
            json_out(['error' => $e->getMessage()], 422);
        }
    }

    public function validation(string $id): void
    {
        $sj = JobService::subJob((int) $id);
        if (!$sj) json_out(['error' => 'Not found'], 404);
        if (Auth::is('inspector') && (int) $sj['inspector_id'] !== Auth::id()) json_out(['error' => 'Forbidden'], 403);
        $issues = InspectionService::issues((int) $id);
        json_out(['data' => ['ready' => !$issues, 'issues' => $issues]]);
    }

    public function notifications(): void
    {
        json_out(['data' => DB::all('SELECT id, title, body, link, read_at, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 20', [Auth::id()]),
            'unread' => (int) DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [Auth::id()])]);
    }

    /** Public: validity by certificate number (issued / revoked only). */
    public function verify(string $certificateNo): void
    {
        $c = DB::one("SELECT c.certificate_no, c.status, c.result, c.inspection_date, c.issue_date, c.next_inspection_date
                      FROM certificates c WHERE c.certificate_no = ? AND c.status IN ('issued','revoked')", [$certificateNo]);
        if (!$c) json_out(['error' => 'Not found'], 404);
        $c['valid'] = $c['status'] === 'issued' && $c['next_inspection_date'] >= date('Y-m-d');
        json_out(['data' => $c]);
    }
}
