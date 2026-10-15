<?php

final class AdminRequestController extends Controller
{
    public function index(): void
    {
        $status = (string) input('status', 'open');
        $channel = (string) input('channel', '');
        $q = trim((string) input('q', ''));
        $where = ['1=1'];
        $p = [];
        if ($status === 'open') {
            $where[] = "r.status IN ('new','under_review','quoted')";
        } elseif ($status !== 'all' && $status !== '') {
            $where[] = 'r.status = ?';
            $p[] = $status;
        }
        if ($channel) { $where[] = 'r.channel = ?'; $p[] = $channel; }
        if ($q !== '') {
            $where[] = '(r.request_no LIKE ? OR r.company_name LIKE ? OR r.contact_name LIKE ? OR r.email LIKE ?)';
            array_push($p, "%$q%", "%$q%", "%$q%", "%$q%");
        }
        $sort = ['newest' => 'r.id DESC', 'oldest' => 'r.id ASC', 'company' => 'r.company_name ASC', 'date' => 'r.preferred_date IS NULL, r.preferred_date ASC'][input('sort', 'newest')] ?? 'r.id DESC';
        $sql = "SELECT r.*, (SELECT COALESCE(SUM(quantity),0) FROM service_request_items i WHERE i.request_id = r.id) items, j.job_no
                FROM service_requests r LEFT JOIN jobs j ON j.id = r.converted_job_id WHERE " . implode(' AND ', $where) . " ORDER BY $sort";
        if ($this->wantsCsv()) {
            csv_export('requests', DB::all($sql, $p), ['request_no' => 'Request No', 'channel' => 'Channel', 'company_name' => 'Company', 'contact_name' => 'Contact',
                'email' => 'Email', 'phone' => 'Phone', 'location' => 'Location', 'preferred_date' => 'Preferred Date', 'items' => 'Items', 'status' => 'Status', 'job_no' => 'Job', 'created_at' => 'Received']);
        }
        [$rows, $meta] = paginate($sql, $p);
        $counts = array_column(DB::all('SELECT status, COUNT(*) n FROM service_requests GROUP BY status'), 'n', 'status');
        $this->view('admin/requests/index', compact('rows', 'meta', 'status', 'channel', 'q', 'counts') + [
            'title' => 'Client Requests', 'crumbs' => ['Client Requests' => null],
        ]);
    }

    public function create(): void
    {
        $this->view('admin/requests/create', [
            'title' => 'Log Request', 'crumbs' => ['Client Requests' => 'admin/requests', 'Log request' => null],
            'types' => DB::all("SELECT id, name FROM inspection_types WHERE status='active' ORDER BY name"),
            'clients' => DB::all("SELECT id, code, name, contact_person, email, phone FROM clients WHERE status='active' ORDER BY name"),
        ]);
    }

    /** Email / phone channel — logged by the admin on behalf of the client. */
    public function store(): void
    {
        $data = $_POST;
        if (!empty($data['client_id']) && ($c = DB::one('SELECT * FROM clients WHERE id = ?', [(int) $data['client_id']]))) {
            $data['company_name'] = $data['company_name'] ?: $c['name'];
            $data['contact_name'] = $data['contact_name'] ?: $c['contact_person'];
            $data['email'] = $data['email'] ?: $c['email'];
            $data['phone'] = $data['phone'] ?: $c['phone'];
        }
        $errors = validate($data, [
            'channel' => 'required|in:email,phone', 'company_name' => 'required|max:190', 'contact_name' => 'required|max:120',
            'email' => 'email', 'phone' => 'phone', 'preferred_date' => 'date',
        ]);
        if (empty($data['email']) && empty($data['phone'])) $errors['phone'] = 'Provide at least an email or a phone number.';
        $items = array_values(array_filter($_POST['items'] ?? [], fn ($i) => is_array($i) && trim($i['equipment_description'] ?? '') !== ''));
        if (!$items) $errors['items'] = 'Add at least one equipment item.';
        if ($errors) $this->failValidation($errors);
        $id = RequestService::create($data, $items, $data['channel']);
        flash('success', 'Request logged.');
        redirect('admin/requests/' . $id);
    }

    public function show(string $id): void
    {
        $r = DB::one('SELECT r.*, u.name received_name, s.name submitted_name, j.job_no FROM service_requests r
                      LEFT JOIN users u ON u.id = r.received_by LEFT JOIN users s ON s.id = r.submitted_by
                      LEFT JOIN jobs j ON j.id = r.converted_job_id WHERE r.id = ?', [(int) $id]);
        if (!$r) $this->notFound();
        $items = DB::all('SELECT i.*, t.name type_name FROM service_request_items i LEFT JOIN inspection_types t ON t.id = i.inspection_type_id WHERE i.request_id = ?', [$r['id']]);
        $history = DB::all("SELECT a.*, u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE a.entity_type = 'service_request' AND a.entity_id = ? ORDER BY a.id", [$r['id']]);
        $match = $r['client_id'] ? null : DB::one('SELECT id, name, code FROM clients WHERE name LIKE ? OR (email IS NOT NULL AND email = ?) LIMIT 1', ['%' . $r['company_name'] . '%', $r['email']]);
        $this->view('admin/requests/show', compact('r', 'items', 'history', 'match') + [
            'title' => $r['request_no'], 'crumbs' => ['Client Requests' => 'admin/requests', $r['request_no'] => null],
        ]);
    }

    public function status(string $id): void
    {
        $this->attempt(fn () => RequestService::changeStatus((int) $id, (string) input('status'), input('note')), 'Request status updated.');
    }

    public function convertForm(string $id): void
    {
        $r = DB::one('SELECT * FROM service_requests WHERE id = ?', [(int) $id]);
        if (!$r) $this->notFound();
        if (!Workflow::can('request', $r['status'], 'converted')) {
            flash('error', 'This request can no longer be converted.');
            redirect('admin/requests/' . $id);
        }
        $items = DB::all('SELECT * FROM service_request_items WHERE request_id = ?', [$r['id']]);
        $this->view('admin/jobs/form', [
            'title' => 'Convert ' . $r['request_no'], 'crumbs' => ['Client Requests' => 'admin/requests', $r['request_no'] => 'admin/requests/' . $r['id'], 'Convert to job' => null],
            'request' => $r, 'items' => $items,
            'types' => DB::all("SELECT id, code, name FROM inspection_types WHERE status='active' ORDER BY name"),
            'clients' => DB::all("SELECT id, code, name FROM clients WHERE status='active' ORDER BY name"),
            'inspectors' => JobService::inspectorsFor(),
            'action' => url('admin/requests/' . $r['id'] . '/convert'),
        ]);
    }

    public function convert(string $id): void
    {
        $this->attempt(function () use ($id) {
            $jobId = RequestService::convert((int) $id, $_POST, $this->subJobRows(), ['code' => input('new_client_code'), 'city' => input('new_client_city')]);
            flash('success', 'Request converted. Job ' . DB::value('SELECT job_no FROM jobs WHERE id = ?', [$jobId]) . ' created.');
            return 'admin/jobs/' . $jobId;
        });
    }

    private function subJobRows(): array
    {
        return array_values(array_filter($_POST['subjobs'] ?? [], fn ($s) => is_array($s) && !empty($s['inspection_type_id'])));
    }
}
