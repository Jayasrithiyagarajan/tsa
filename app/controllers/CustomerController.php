<?php

/** Customer portal — every query is scoped to the signed-in user's client. */
final class CustomerController extends Controller
{
    private function cid(): int
    {
        $c = Auth::clientId();
        if (!$c) $this->forbid();
        return $c;
    }

    public function dashboard(): void
    {
        $c = $this->cid();
        $k = DB::one("SELECT
            (SELECT COUNT(*) FROM service_requests WHERE client_id = ? AND status IN ('new','under_review','quoted')) open_requests,
            (SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status NOT IN ('completed','cancelled')) active_jobs,
            (SELECT COUNT(*) FROM certificates ce JOIN sub_jobs s ON s.id = ce.sub_job_id JOIN jobs j ON j.id = s.job_id WHERE j.client_id = ? AND ce.status = 'issued' AND j.status = 'completed') certs,
            (SELECT COUNT(*) FROM equipment WHERE client_id = ? AND next_inspection_date <= CURDATE() + INTERVAL 30 DAY) due,
            (SELECT COUNT(*) FROM equipment WHERE client_id = ? AND next_inspection_date < CURDATE()) overdue", [$c, $c, $c, $c, $c]);
        $jobs = DB::all("SELECT * FROM jobs WHERE client_id = ? AND status NOT IN ('cancelled') ORDER BY FIELD(status,'completed') , id DESC LIMIT 5", [$c]);
        foreach ($jobs as &$j) $j['progress'] = JobService::progress((int) $j['id']);
        unset($j);
        $certs = $this->certQuery($c, 'ORDER BY ce.issue_date DESC, ce.id DESC LIMIT 5');
        $due = DB::all('SELECT * FROM equipment WHERE client_id = ? AND next_inspection_date IS NOT NULL ORDER BY next_inspection_date LIMIT 6', [$c]);
        $this->view('customer/dashboard', compact('k', 'jobs', 'certs', 'due') + ['title' => 'Overview', 'crumbs' => ['Overview' => null]]);
    }

    private function certQuery(int $c, string $tail, array $extraWhere = [], array $p = []): array
    {
        $w = implode('', array_map(fn ($x) => " AND $x", $extraWhere));
        return DB::all("SELECT ce.*, s.sub_job_no, s.job_id, t.name type_name, e.description eq_description, e.equipment_no
                        FROM certificates ce JOIN sub_jobs s ON s.id = ce.sub_job_id JOIN jobs j ON j.id = s.job_id
                        JOIN inspection_types t ON t.id = s.inspection_type_id LEFT JOIN equipment e ON e.id = s.equipment_id
                        WHERE j.client_id = ? AND ce.status IN ('issued','revoked') AND s.status = 'approved' AND j.status = 'completed' $w $tail", array_merge([$c], $p));
    }

    public function requests(): void
    {
        $c = $this->cid();
        $rows = DB::all("SELECT r.*, j.job_no, (SELECT COALESCE(SUM(quantity),0) FROM service_request_items i WHERE i.request_id = r.id) items
                         FROM service_requests r LEFT JOIN jobs j ON j.id = r.converted_job_id WHERE r.client_id = ? ORDER BY r.id DESC", [$c]);
        $this->view('customer/requests', compact('rows') + ['title' => 'Inspection Requests', 'crumbs' => ['Inspection Requests' => null]]);
    }

    public function newRequest(): void
    {
        $this->cid();
        $this->view('customer/request_form', [
            'types' => DB::all("SELECT id, name FROM inspection_types WHERE status='active' ORDER BY name"),
            'client' => DB::one('SELECT * FROM clients WHERE id = ?', [Auth::clientId()]),
            'title' => 'New Request', 'crumbs' => ['Inspection Requests' => 'portal/requests', 'New' => null],
        ]);
    }

    public function storeRequest(): void
    {
        $c = $this->cid();
        $client = DB::one('SELECT * FROM clients WHERE id = ?', [$c]);
        $u = Auth::user();
        $errors = validate($_POST, ['location' => 'required|max:190', 'preferred_date' => 'date', 'po_no' => 'max:60', 'description' => 'max:2000']);
        $items = array_values(array_filter($_POST['items'] ?? [], fn ($i) => is_array($i) && trim($i['equipment_description'] ?? '') !== ''));
        if (!$items) $errors['items'] = 'Add at least one equipment item.';
        if ($errors) $this->failValidation($errors);
        RequestService::create([
            'client_id' => $c, 'company_name' => $client['name'], 'contact_name' => $u['name'], 'email' => $u['email'], 'phone' => $u['phone'] ?: $client['phone'],
            'location' => input('location'), 'po_no' => input('po_no'), 'preferred_date' => input('preferred_date'), 'description' => input('description'),
        ], $items, 'portal');
        flash('success', 'Request submitted. We will confirm the schedule shortly.');
        redirect('portal/requests');
    }

    public function jobs(): void
    {
        $c = $this->cid();
        [$jobs, $meta] = paginate("SELECT * FROM jobs WHERE client_id = ? ORDER BY id DESC", [$c], 15, 'SELECT COUNT(*) FROM jobs WHERE client_id = ?');
        foreach ($jobs as &$j) $j['progress'] = JobService::progress((int) $j['id']);
        unset($j);
        $this->view('customer/jobs', compact('jobs', 'meta') + ['title' => 'Jobs', 'crumbs' => ['Jobs & Progress' => null]]);
    }

    public function job(string $id): void
    {
        $c = $this->cid();
        $job = DB::one('SELECT j.*, u.name lead_name FROM jobs j LEFT JOIN users u ON u.id = j.lead_inspector_id WHERE j.id = ? AND j.client_id = ?', [(int) $id, $c]);
        if (!$job) $this->notFound();
        $subs = JobService::subJobsOf((int) $job['id']);
        $released = $job['status'] === 'completed';           // documents appear after the admin's final approval
        foreach ($subs as &$s) {
            if (!$released && $s['status'] === 'approved') $s['status'] = 'submitted';   // shown as "Quality review" until then
            $s['cert'] = $released ? DB::one("SELECT certificate_no, status, next_inspection_date FROM certificates WHERE sub_job_id = ?", [$s['id']]) : null;
            $s['sticker_id'] = $released && $s['status'] === 'approved' ? DB::value("SELECT id FROM stickers WHERE sub_job_id = ? AND status = 'closed'", [$s['id']]) : null;
        }
        unset($s);
        $this->view('customer/job', ['job' => $job, 'subs' => $subs, 'progress' => JobService::progress((int) $job['id']),
            'title' => $job['job_no'], 'crumbs' => ['Jobs & Progress' => 'portal/jobs', $job['job_no'] => null]]);
    }

    public function certificates(): void
    {
        $c = $this->cid();
        $q = trim((string) input('q', ''));
        $rows = $q !== ''
            ? $this->certQuery($c, 'ORDER BY ce.issue_date DESC', ['(ce.certificate_no LIKE ? OR e.equipment_no LIKE ? OR e.description LIKE ?)'], ["%$q%", "%$q%", "%$q%"])
            : $this->certQuery($c, 'ORDER BY ce.issue_date DESC');
        if ($this->wantsCsv()) {
            csv_export('certificates', $rows, ['certificate_no' => 'Certificate No', 'type_name' => 'Inspection', 'eq_description' => 'Equipment', 'equipment_no' => 'Equipment ID',
                'result' => 'Result', 'inspection_date' => 'Inspection Date', 'issue_date' => 'Issued', 'next_inspection_date' => 'Valid Until', 'status' => 'Status']);
        }
        $this->view('customer/certificates', compact('rows', 'q') + ['title' => 'Certificates', 'crumbs' => ['Certificates' => null]]);
    }

    public function equipment(): void
    {
        $c = $this->cid();
        $rows = DB::all("SELECT e.*, t.name type_name,
                           (SELECT ce.sub_job_id FROM certificates ce JOIN sub_jobs s ON s.id = ce.sub_job_id JOIN jobs j ON j.id = s.job_id WHERE s.equipment_id = e.id AND ce.status = 'issued' AND j.status = 'completed' ORDER BY ce.issue_date DESC LIMIT 1) last_cert_sj,
                           (SELECT ce.certificate_no FROM certificates ce JOIN sub_jobs s ON s.id = ce.sub_job_id JOIN jobs j ON j.id = s.job_id WHERE s.equipment_id = e.id AND ce.status = 'issued' AND j.status = 'completed' ORDER BY ce.issue_date DESC LIMIT 1) last_cert
                         FROM equipment e LEFT JOIN inspection_types t ON t.id = e.inspection_type_id WHERE e.client_id = ?
                         ORDER BY e.next_inspection_date IS NULL, e.next_inspection_date, e.description", [$c]);
        if ($this->wantsCsv()) {
            csv_export('equipment-register', $rows, ['description' => 'Description', 'equipment_no' => 'Equipment ID', 'serial_no' => 'Serial', 'manufacturer' => 'Manufacturer', 'model' => 'Model',
                'capacity' => 'Capacity', 'last_inspection_date' => 'Last Inspection', 'next_inspection_date' => 'Next Due', 'last_cert' => 'Certificate', 'status' => 'Status']);
        }
        $this->view('customer/equipment', compact('rows') + ['title' => 'Equipment Register', 'crumbs' => ['Equipment Register' => null]]);
    }
}
