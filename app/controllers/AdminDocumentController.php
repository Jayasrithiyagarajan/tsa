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

    /** Bulk sticker stock: generated stickers in the office or in the inspectors' hands (not yet on a job). */
    public function stickers(): void
    {
        $status = (string) input('status', '');
        $q = trim((string) input('q', ''));
        $insp = (int) input('inspector', 0);
        $where = ["(s.status IN ('in_stock','allocated') OR (s.status = 'void' AND s.sub_job_id IS NULL))"];
        $p = [];
        if (in_array($status, ['in_stock', 'allocated', 'void'], true)) { $where[] = 's.status = ?'; $p[] = $status; } else { $status = ''; }
        if ($insp) { $where[] = 's.inspector_id = ?'; $p[] = $insp; }
        if ($q !== '') { $where[] = '(s.sticker_no LIKE ? OR s.batch_no LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
        $sql = "SELECT s.*, u.name inspector_name, ab.name allocated_by_name
                FROM stickers s LEFT JOIN users u ON u.id = s.inspector_id LEFT JOIN users ab ON ab.id = s.allocated_by
                WHERE " . implode(' AND ', $where) . " ORDER BY FIELD(s.status,'allocated','in_stock','void'), s.sticker_no";
        if ($this->wantsCsv()) {
            csv_export('sticker-stock', DB::all($sql, $p), ['sticker_no' => 'Sticker No', 'batch_no' => 'Batch', 'color' => 'Color', 'status' => 'Status',
                'inspector_name' => 'Allocated To', 'allocated_at' => 'Allocated', 'allocated_by_name' => 'Allocated By', 'created_at' => 'Generated', 'void_reason' => 'Void Reason']);
        }
        [$rows, $meta] = paginate($sql, $p, 50);
        $counts = DB::one("SELECT SUM(status = 'in_stock') in_stock, SUM(status = 'allocated') allocated, SUM(status = 'void' AND sub_job_id IS NULL) void,
                                  SUM(status = 'closed') linked FROM stickers");
        $inspectors = DB::all("SELECT u.id, u.name, COALESCE(SUM(s.status = 'allocated'), 0) in_hand, COALESCE(SUM(s.status = 'allocated' AND s.color = 'red'), 0) in_hand_red,
                                      COALESCE(SUM(s.status = 'closed'), 0) used, MAX(s.allocated_at) last_allocated
                               FROM users u LEFT JOIN stickers s ON s.inspector_id = u.id
                               WHERE u.role = 'inspector' AND u.status = 'active' GROUP BY u.id, u.name ORDER BY u.name");
        $batches = array_column(DB::all('SELECT DISTINCT batch_no FROM stickers WHERE batch_no IS NOT NULL ORDER BY batch_no DESC'), 'batch_no');
        $series = StickerService::series();
        $this->view('admin/documents/stickers', compact('rows', 'meta', 'status', 'q', 'insp', 'counts', 'inspectors', 'batches', 'series')
            + ['title' => 'Sticker Stock', 'crumbs' => ['Sticker Stock' => null]]);
    }

    /** Print Sticker: every sticker still in office stock; Super Admin prints / downloads a number range from here. */
    public function printStickers(): void
    {
        $q = trim((string) input('q', ''));
        $prefix = (string) input('series', '');
        $where = ["s.status = 'in_stock'"];
        $p = [];
        if ($prefix !== '') { $where[] = 's.sticker_no LIKE ?'; $p[] = $prefix . '%'; }
        if ($q !== '') { $where[] = '(s.sticker_no LIKE ? OR s.batch_no LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
        [$rows, $meta] = paginate('SELECT s.id, s.sticker_no, s.color, s.batch_no, s.created_at FROM stickers s WHERE ' . implode(' AND ', $where) . ' ORDER BY s.sticker_no', $p, 50);
        $series = array_values(array_filter(StickerService::series(), fn ($sr) => (int) $sr['in_stock'] > 0));
        $total = (int) DB::value("SELECT COUNT(*) FROM stickers WHERE status = 'in_stock'");
        $this->view('admin/documents/print_stickers', compact('rows', 'meta', 'q', 'prefix', 'series', 'total')
            + ['title' => 'Print Sticker', 'crumbs' => ['Print Sticker' => null]]);
    }

    /** Stickers linked to projects (jobs): closed, or voided after being linked. */
    public function projectStickers(): void
    {
        $status = in_array(input('status'), ['closed', 'void'], true) ? (string) input('status') : '';
        $result = in_array(input('result'), ['accepted', 'failed', 'pending'], true) ? (string) input('result') : '';
        $q = trim((string) input('q', ''));
        $insp = (int) input('inspector', 0);
        $client = (int) input('client', 0);
        $where = ["s.sub_job_id IS NOT NULL AND s.status IN ('closed','void')"];
        $p = [];
        if ($status !== '') { $where[] = 's.status = ?'; $p[] = $status; }
        if ($insp) { $where[] = 's.inspector_id = ?'; $p[] = $insp; }
        if ($client) { $where[] = 'j.client_id = ?'; $p[] = $client; }
        if ($result === 'accepted') $where[] = "s.color <> 'red'";
        if ($result === 'failed') $where[] = "s.color = 'red'";
        if ($result === 'pending') $where[] = "sj.status <> 'approved'";
        if ($q !== '') { $where[] = '(s.sticker_no LIKE ? OR sj.sub_job_no LIKE ? OR j.job_no LIKE ? OR c.certificate_no LIKE ? OR e.equipment_no LIKE ? OR cl.name LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%"); }
        $sql = "SELECT s.*, sj.sub_job_no, sj.job_id, sj.status sj_status, j.job_no, j.title job_title, c.certificate_no, c.result, c.status cert_status,
                       t.name type_name, e.description eq_description, e.equipment_no, cl.name client_name, u.name inspector_name
                FROM stickers s JOIN sub_jobs sj ON sj.id = s.sub_job_id JOIN jobs j ON j.id = sj.job_id JOIN clients cl ON cl.id = j.client_id
                LEFT JOIN inspection_types t ON t.id = sj.inspection_type_id LEFT JOIN certificates c ON c.sub_job_id = sj.id
                LEFT JOIN equipment e ON e.id = s.equipment_id LEFT JOIN users u ON u.id = s.inspector_id
                WHERE " . implode(' AND ', $where) . ' ORDER BY s.linked_at DESC, s.id DESC';
        if ($this->wantsCsv()) {
            csv_export('project-stickers', DB::all($sql, $p), ['sticker_no' => 'Sticker No', 'color' => 'Color', 'status' => 'Status', 'job_no' => 'Job', 'sub_job_no' => 'Sub-job',
                'client_name' => 'Client', 'type_name' => 'Inspection', 'eq_description' => 'Equipment', 'equipment_no' => 'Equipment ID', 'inspector_name' => 'Inspector',
                'linked_at' => 'Linked', 'certificate_no' => 'Certificate', 'result' => 'Result', 'expiry_date' => 'Valid Until', 'void_reason' => 'Void Reason']);
        }
        [$rows, $meta] = paginate($sql, $p, 50);
        $counts = DB::one("SELECT SUM(s.status = 'closed') closed, SUM(s.status = 'void') void, SUM(s.status = 'closed' AND s.color <> 'red') accepted,
                                  SUM(s.status = 'closed' AND s.color = 'red') failed, SUM(s.status = 'closed' AND sj.status <> 'approved') pending
                           FROM stickers s JOIN sub_jobs sj ON sj.id = s.sub_job_id WHERE s.status IN ('closed','void')");
        $inspectors = DB::all("SELECT id, name FROM users WHERE role = 'inspector' ORDER BY name");
        $clients = DB::all('SELECT DISTINCT cl.id, cl.name FROM stickers s JOIN sub_jobs sj ON sj.id = s.sub_job_id JOIN jobs j ON j.id = sj.job_id JOIN clients cl ON cl.id = j.client_id ORDER BY cl.name');
        $this->view('admin/documents/sticker_projects', compact('rows', 'meta', 'status', 'result', 'q', 'insp', 'client', 'counts', 'inspectors', 'clients')
            + ['title' => 'Project Stickers', 'crumbs' => ['Project Stickers' => null]]);
    }

    public function addStickers(): void
    {
        $this->attempt(function () {
            $insp = (int) input('inspector_id', 0) ?: null;
            $ids = StickerService::addBatch((string) input('prefix', 'TE-'), (int) input('from'), (int) input('to'), input('color') === 'red' ? 'red' : 'green', nullable(input('batch_no')), $insp);
            flash('success', count($ids) . ' stickers generated' . ($insp ? ' and allocated' : '') . '.');
            if ($ids && !empty($_POST['print'])) {
                $nos = DB::one('SELECT MIN(sticker_no) a, MAX(sticker_no) b FROM stickers WHERE id IN (' . implode(',', $ids) . ')');
                return 'admin/stickers?' . http_build_query(['print_from' => $nos['a'], 'print_to' => $nos['b']]);
            }
            return 'admin/stickers';
        });
    }

    /** Selected stickers (ids[]), the next N in stock from a number (from + qty) or a number range (from / to). */
    private function selection(): array
    {
        if (!empty($_POST['ids'])) return (array) $_POST['ids'];
        if ((int) input('qty', 0) > 0) return StickerService::next((string) input('from', ''), (int) input('qty'));
        return StickerService::range((string) input('from', ''), (string) input('to', ''));
    }

    public function allocateStickers(): void
    {
        $this->attempt(function () {
            [$n, $skipped] = StickerService::allocate($this->selection(), (int) input('inspector_id', 0));
            $name = DB::value('SELECT name FROM users WHERE id = ?', [(int) input('inspector_id', 0)]);
            flash($n ? 'success' : 'error', "$n sticker(s) allocated to $name." . ($skipped ? " $skipped skipped (already linked to a job or void)." : ''));
        });
    }

    public function returnStickers(): void
    {
        $this->attempt(function () {
            $n = StickerService::returnToStock($this->selection());
            flash('success', "$n sticker(s) returned to office stock.");
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
