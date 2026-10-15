<?php

final class AdminJobController extends Controller
{
    public function index(): void
    {
        $status = (string) input('status', '');
        $q = trim((string) input('q', ''));
        $client = (int) input('client', 0);
        $inspector = (int) input('inspector', 0);
        $where = ['1=1'];
        $p = [];
        if ($status === 'open') $where[] = "j.status NOT IN ('completed','cancelled')";
        elseif ($status !== '') { $where[] = 'j.status = ?'; $p[] = $status; }
        if ($client) { $where[] = 'j.client_id = ?'; $p[] = $client; }
        if ($inspector) { $where[] = 'EXISTS (SELECT 1 FROM sub_jobs s WHERE s.job_id = j.id AND s.inspector_id = ?)'; $p[] = $inspector; }
        if ($q !== '') { $where[] = '(j.job_no LIKE ? OR j.title LIKE ? OR j.po_no LIKE ? OR c.name LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
        $sort = [
            'newest' => 'j.id DESC', 'scheduled' => 'j.scheduled_date IS NULL, j.scheduled_date', 'priority' => "FIELD(j.priority,'urgent','high','normal','low'), j.id DESC",
            'client' => 'c.name',
        ][input('sort', 'newest')] ?? 'j.id DESC';
        $sql = "SELECT j.*, c.name client_name, u.name lead_name,
                   (SELECT COUNT(*) FROM sub_jobs s WHERE s.job_id = j.id AND s.status <> 'cancelled') n_total,
                   (SELECT COUNT(*) FROM sub_jobs s WHERE s.job_id = j.id AND s.status = 'approved') n_done,
                   (SELECT COUNT(*) FROM sub_jobs s WHERE s.job_id = j.id AND s.status = 'pending') n_pending,
                   (SELECT COUNT(*) FROM sub_jobs s WHERE s.job_id = j.id AND s.status = 'submitted') n_qc
                FROM jobs j JOIN clients c ON c.id = j.client_id LEFT JOIN users u ON u.id = j.lead_inspector_id
                WHERE " . implode(' AND ', $where) . " ORDER BY $sort";
        if ($this->wantsCsv()) {
            csv_export('jobs', DB::all($sql, $p), ['job_no' => 'Job No', 'title' => 'Title', 'client_name' => 'Client', 'location' => 'Location', 'po_no' => 'PO',
                'scheduled_date' => 'Scheduled', 'priority' => 'Priority', 'status' => 'Status', 'lead_name' => 'Lead Inspector', 'n_total' => 'Sub-jobs', 'n_done' => 'Approved']);
        }
        [$rows, $meta] = paginate($sql, $p);
        $counts = array_column(DB::all('SELECT status, COUNT(*) n FROM jobs GROUP BY status'), 'n', 'status');
        $this->view('admin/jobs/index', compact('rows', 'meta', 'status', 'q', 'counts', 'client', 'inspector') + [
            'title' => 'Jobs', 'crumbs' => ['Jobs & Sub-jobs' => null],
            'clients' => DB::all('SELECT id, name FROM clients ORDER BY name'),
            'inspectors' => DB::all("SELECT id, name FROM users WHERE role='inspector' ORDER BY name"),
        ]);
    }

    public function create(): void
    {
        $this->view('admin/jobs/form', [
            'title' => 'New Job', 'crumbs' => ['Jobs & Sub-jobs' => 'admin/jobs', 'New job' => null],
            'request' => null, 'items' => [],
            'types' => DB::all("SELECT id, code, name FROM inspection_types WHERE status='active' ORDER BY name"),
            'clients' => DB::all("SELECT id, code, name FROM clients WHERE status='active' ORDER BY name"),
            'inspectors' => JobService::inspectorsFor(),
            'action' => url('admin/jobs'),
        ]);
    }

    public function store(): void
    {
        $this->attempt(function () {
            $subs = array_values(array_filter($_POST['subjobs'] ?? [], fn ($s) => is_array($s) && !empty($s['inspection_type_id'])));
            if (!$subs) throw new DomainException('Add at least one sub-job (inspection type + equipment).');
            $id = JobService::create($_POST, $subs);
            flash('success', 'Job ' . DB::value('SELECT job_no FROM jobs WHERE id = ?', [$id]) . ' created.');
            return 'admin/jobs/' . $id;
        });
    }

    public function show(string $id): void
    {
        $job = DB::one('SELECT j.*, c.name client_name, c.code client_code, c.contact_person, c.phone client_phone, c.email client_email,
                               r.request_no, r.channel, u.name lead_name, cb.name created_name
                        FROM jobs j JOIN clients c ON c.id = j.client_id LEFT JOIN service_requests r ON r.id = j.request_id
                        LEFT JOIN users u ON u.id = j.lead_inspector_id LEFT JOIN users cb ON cb.id = j.created_by WHERE j.id = ?', [(int) $id]);
        if (!$job) $this->notFound();
        $subs = JobService::subJobsOf((int) $job['id']);
        foreach ($subs as &$s) {
            $s['cert'] = DB::one('SELECT id, certificate_no, status, template_id FROM certificates WHERE sub_job_id = ?', [$s['id']]);
            $s['checklist'] = DB::one('SELECT id, status FROM checklists WHERE sub_job_id = ?', [$s['id']]);
            $s['sticker'] = DB::one("SELECT id, sticker_no, status FROM stickers WHERE sub_job_id = ? AND status IN ('reserved','issued') ORDER BY id DESC LIMIT 1", [$s['id']]);
        }
        unset($s);
        $history = DB::all("SELECT a.*, u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                            WHERE (a.entity_type = 'job' AND a.entity_id = ?) OR (a.entity_type = 'sub_job' AND a.entity_id IN (SELECT id FROM sub_jobs WHERE job_id = ?))
                            ORDER BY a.id DESC LIMIT 30", [$job['id'], $job['id']]);
        $this->view('admin/jobs/show', [
            'job' => $job, 'subs' => $subs, 'history' => $history, 'progress' => JobService::progress((int) $job['id']),
            'inspectors' => JobService::inspectorsFor(),
            'types' => DB::all("SELECT id, code, name FROM inspection_types WHERE status='active' ORDER BY name"),
            'equipment' => DB::all('SELECT id, description, equipment_no FROM equipment WHERE client_id = ? ORDER BY description', [$job['client_id']]),
            'title' => $job['job_no'], 'crumbs' => ['Jobs & Sub-jobs' => 'admin/jobs', $job['job_no'] => null],
        ]);
    }

    public function update(string $id): void
    {
        $this->attempt(function () use ($id) {
            $errors = validate($_POST, ['title' => 'required|max:190', 'scheduled_date' => 'date', 'due_date' => 'date', 'priority' => 'in:low,normal,high,urgent']);
            if ($errors) throw new DomainException(implode(' ', $errors));
            DB::update('jobs', [
                'title' => input('title'), 'location' => nullable(input('location')), 'po_no' => nullable(input('po_no')),
                'scheduled_date' => nullable(input('scheduled_date')), 'due_date' => nullable(input('due_date')),
                'priority' => input('priority', 'normal'), 'notes' => nullable(input('notes')),
            ], 'id = ?', [(int) $id]);
            Audit::log('job.updated', 'job', (int) $id);
        }, 'Job details saved.');
    }

    public function assign(string $id): void
    {
        $this->attempt(function () use ($id) {
            $inspector = (int) input('inspector_id');
            $ids = array_map('intval', (array) ($_POST['sub_job_ids'] ?? []));
            if (!$inspector) throw new DomainException('Choose an inspector.');
            if (!$ids) throw new DomainException('Select at least one sub-job.');
            $own = array_column(DB::all('SELECT id FROM sub_jobs WHERE job_id = ?', [(int) $id]), 'id');
            $n = JobService::assign(array_values(array_intersect($ids, array_map('intval', $own))), $inspector, input('note'));
            flash('success', $n . ' sub-job(s) assigned to ' . DB::value('SELECT name FROM users WHERE id = ?', [$inspector]) . '.');
        });
    }

    public function addSubJob(string $id): void
    {
        $this->attempt(fn () => JobService::addSubJob((int) $id, $_POST), 'Sub-job added.');
    }

    public function unassign(string $id): void
    {
        $this->attempt(fn () => JobService::unassign((int) $id), 'Inspector removed from sub-job.');
    }

    public function cancelSubJob(string $id): void
    {
        $this->attempt(fn () => JobService::cancelSubJob((int) $id, (string) input('reason', '')), 'Sub-job cancelled.');
    }

    public function cancel(string $id): void
    {
        $this->attempt(function () use ($id) {
            if (trim((string) input('reason')) === '') throw new DomainException('Enter a cancellation reason.');
            JobService::cancelJob((int) $id, input('reason'));
        }, 'Job cancelled.');
    }

    /** Authorized completion override: submit an incomplete inspection to QC with a recorded reason. */
    public function override(string $id): void
    {
        $this->attempt(function () use ($id) {
            $sj = JobService::subJob((int) $id);
            if (!$sj) throw new DomainException('Sub-job not found.');
            if ($sj['status'] !== 'in_progress') throw new DomainException('Only an in-progress inspection can be force-submitted.');
            // admin acts with inspector authority for this action only
            InspectionService::submit((int) $id, (string) input('reason', ''));
        }, 'Inspection submitted to QC with override. The reason has been recorded in the audit log.');
    }

    /** Assignment board: all unassigned sub-jobs with qualified inspectors and their workload. */
    public function board(): void
    {
        $pending = DB::all(JobService::SUBJOB_SQL . " WHERE sj.status = 'pending' AND j.status <> 'cancelled' ORDER BY FIELD(j.priority,'urgent','high','normal','low'), j.scheduled_date IS NULL, j.scheduled_date, sj.job_id, sj.seq");
        $byJob = [];
        foreach ($pending as $s) $byJob[$s['job_id']][] = $s;
        $inspectors = JobService::inspectorsFor();
        $skills = [];
        foreach (DB::all('SELECT k.user_id, t.code FROM inspector_skills k JOIN inspection_types t ON t.id = k.inspection_type_id') as $r) {
            $skills[$r['user_id']][] = $r['code'];
        }
        $this->view('admin/jobs/board', compact('byJob', 'inspectors', 'skills') + [
            'title' => 'Assignment Board', 'crumbs' => ['Assignment Board' => null],
        ]);
    }
}
