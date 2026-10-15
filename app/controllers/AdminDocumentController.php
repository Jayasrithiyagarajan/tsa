<?php

final class AdminDocumentController extends Controller
{
    public function certificates(): void
    {
        $status = (string) input('status', '');
        $q = trim((string) input('q', ''));
        $type = (int) input('type', 0);
        $where = ['1=1'];
        $p = [];
        if ($status !== '') { $where[] = 'c.status = ?'; $p[] = $status; }
        if ($type) { $where[] = 'sj.inspection_type_id = ?'; $p[] = $type; }
        if ($q !== '') { $where[] = '(c.certificate_no LIKE ? OR cl.name LIKE ? OR e.equipment_no LIKE ? OR sj.sub_job_no LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
        $sql = "SELECT c.*, sj.sub_job_no, sj.job_id, t.name type_name, cl.name client_name, e.description eq_description, e.equipment_no,
                       i.name inspector_name, r.name reviewer_name
                FROM certificates c JOIN sub_jobs sj ON sj.id = c.sub_job_id JOIN jobs j ON j.id = sj.job_id JOIN clients cl ON cl.id = j.client_id
                JOIN inspection_types t ON t.id = sj.inspection_type_id LEFT JOIN equipment e ON e.id = sj.equipment_id
                LEFT JOIN users i ON i.id = c.inspector_id LEFT JOIN users r ON r.id = c.reviewer_id
                WHERE " . implode(' AND ', $where) . ' ORDER BY c.id DESC';
        if ($this->wantsCsv()) {
            csv_export('certificates', DB::all($sql, $p), ['certificate_no' => 'Certificate No', 'status' => 'Status', 'result' => 'Result', 'client_name' => 'Client', 'type_name' => 'Inspection',
                'eq_description' => 'Equipment', 'equipment_no' => 'Equipment ID', 'inspection_date' => 'Inspection Date', 'issue_date' => 'Issue Date', 'next_inspection_date' => 'Next Due',
                'inspector_name' => 'Inspector', 'reviewer_name' => 'Reviewed By']);
        }
        [$rows, $meta] = paginate($sql, $p);
        $counts = array_column(DB::all('SELECT status, COUNT(*) n FROM certificates GROUP BY status'), 'n', 'status');
        $this->view('admin/documents/certificates', compact('rows', 'meta', 'status', 'q', 'type', 'counts') + [
            'types' => DB::all('SELECT id, name FROM inspection_types ORDER BY name'),
            'title' => 'Certificates', 'crumbs' => ['Certificates' => null],
        ]);
    }

    public function revoke(string $id): void
    {
        $this->attempt(fn () => CertificateService::revoke((int) $id, (string) input('reason', '')), 'Certificate revoked. Its sticker was voided and the sub-job returned for rework.');
    }

    public function stickers(): void
    {
        $status = (string) input('status', '');
        $q = trim((string) input('q', ''));
        $where = ['1=1'];
        $p = [];
        if ($status !== '') { $where[] = 's.status = ?'; $p[] = $status; }
        if ($q !== '') { $where[] = '(s.sticker_no LIKE ? OR s.batch_no LIKE ? OR c.certificate_no LIKE ? OR e.equipment_no LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
        $sql = "SELECT s.*, c.certificate_no, sj.sub_job_no, sj.job_id, e.description eq_description, e.equipment_no, cl.name client_name, u.name inspector_name
                FROM stickers s LEFT JOIN certificates c ON c.id = s.certificate_id LEFT JOIN sub_jobs sj ON sj.id = s.sub_job_id
                LEFT JOIN equipment e ON e.id = s.equipment_id LEFT JOIN clients cl ON cl.id = e.client_id LEFT JOIN users u ON u.id = s.inspector_id
                WHERE " . implode(' AND ', $where) . " ORDER BY FIELD(s.status,'reserved','issued','in_stock','void'), s.sticker_no";
        if ($this->wantsCsv()) {
            csv_export('stickers', DB::all($sql, $p), ['sticker_no' => 'Sticker No', 'batch_no' => 'Batch', 'color' => 'Color', 'status' => 'Status', 'sub_job_no' => 'Sub-job',
                'certificate_no' => 'Certificate', 'client_name' => 'Client', 'equipment_no' => 'Equipment ID', 'issued_at' => 'Issued', 'expiry_date' => 'Expiry', 'void_reason' => 'Void Reason']);
        }
        [$rows, $meta] = paginate($sql, $p, 30);
        $counts = array_column(DB::all('SELECT status, COUNT(*) n FROM stickers GROUP BY status'), 'n', 'status');
        $this->view('admin/documents/stickers', compact('rows', 'meta', 'status', 'q', 'counts') + ['title' => 'Stickers', 'crumbs' => ['Stickers' => null]]);
    }

    public function addStickers(): void
    {
        $this->attempt(function () {
            $n = StickerService::addBatch((string) input('prefix', 'TE-'), (int) input('from'), (int) input('to'), input('color') === 'red' ? 'red' : 'green', nullable(input('batch_no')));
            flash('success', "$n stickers added to stock (duplicates skipped).");
        });
    }

    public function voidSticker(string $id): void
    {
        $this->attempt(fn () => StickerService::void((int) $id, (string) input('reason', '')), 'Sticker voided.');
    }

    public function audit(): void
    {
        $q = trim((string) input('q', ''));
        $entity = (string) input('entity', '');
        $user = (int) input('user', 0);
        $where = ['1=1'];
        $p = [];
        if ($q !== '') { $where[] = '(a.action LIKE ? OR a.details LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
        if ($entity !== '') { $where[] = 'a.entity_type = ?'; $p[] = $entity; }
        if ($user) { $where[] = 'a.user_id = ?'; $p[] = $user; }
        $sql = "SELECT a.*, u.name user_name, u.role user_role FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE " . implode(' AND ', $where) . ' ORDER BY a.id DESC';
        if ($this->wantsCsv()) {
            csv_export('audit-log', DB::all($sql . ' LIMIT 20000', $p), ['created_at' => 'Time', 'user_name' => 'User', 'action' => 'Action', 'entity_type' => 'Entity', 'entity_id' => 'Entity ID', 'details' => 'Details', 'ip_address' => 'IP']);
        }
        [$rows, $meta] = paginate($sql, $p, 40);
        $this->view('admin/documents/audit', compact('rows', 'meta', 'q', 'entity', 'user') + [
            'entities' => array_column(DB::all('SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type'), 'entity_type'),
            'users' => DB::all("SELECT id, name FROM users WHERE role <> 'customer' ORDER BY name"),
            'title' => 'Audit Log', 'crumbs' => ['Audit Log' => null],
        ]);
    }

    public function settings(): void
    {
        $this->view('admin/documents/settings', ['title' => 'Settings', 'crumbs' => ['Settings' => null]]);
    }

    public function saveSettings(): void
    {
        $this->attempt(function () {
            $pattern = (string) input('certificate_pattern');
            if (!str_contains($pattern, '{SEQ}') || !str_contains($pattern, '{JOB}')) {
                throw new DomainException('The certificate pattern must contain {JOB} and {SEQ} so numbers stay unique.');
            }
            foreach (['company_name', 'company_cr', 'company_email', 'company_phone', 'certificate_pattern'] as $k) {
                DB::run('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$k, mb_substr((string) input($k), 0, 255)]);
            }
            Audit::log('settings.updated', 'settings');
        }, 'Settings saved.');
    }
}
