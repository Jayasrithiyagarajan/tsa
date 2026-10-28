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
        $from = "FROM jobs j JOIN clients c ON c.id = j.client_id LEFT JOIN users u ON u.id = j.lead_inspector_id WHERE " . implode(' AND ', $where);
        $sql = "SELECT j.*, c.name client_name, u.name lead_name $from ORDER BY $sort";
        $countSql = "SELECT COUNT(*) FROM jobs j JOIN clients c ON c.id = j.client_id WHERE " . implode(' AND ', $where);
        $csvCols = ['job_no' => 'Job No', 'title' => 'Title', 'client_name' => 'Client', 'location' => 'Location', 'po_no' => 'PO',
            'scheduled_date' => 'Scheduled', 'priority' => 'Priority', 'status' => 'Status', 'lead_name' => 'Lead Inspector', 'n_total' => 'Sub-jobs', 'n_done' => 'Approved'];
        if ($this->wantsCsv()) {
            csv_export('jobs', self::withCounts(DB::all($sql . ' LIMIT 20000', $p)), $csvCols);
        }
        $counts = array_column(DB::all('SELECT status, COUNT(*) n FROM jobs GROUP BY status'), 'n', 'status');
        // no client / inspector / search filter: the total is already known from the status tabs, so skip the COUNT(*) over 50k+ rows
        $known = null;
        if (!$client && !$inspector && $q === '') {
            if ($status === '') $known = (int) array_sum($counts);
            elseif ($status === 'open') $known = (int) array_sum($counts) - (int) ($counts['completed'] ?? 0) - (int) ($counts['cancelled'] ?? 0);
            else $known = (int) ($counts[$status] ?? 0);
        }
        // two steps: sort/limit on the narrow job ids first, then load the 20 full rows
        [$idRows, $meta] = paginate("SELECT j.id $from ORDER BY $sort", $p, 20, $countSql, null, $known);
        $ids = array_map('intval', array_column($idRows, 'id'));
        $rows = $ids ? DB::all("SELECT j.*, c.name client_name, u.name lead_name FROM jobs j JOIN clients c ON c.id = j.client_id LEFT JOIN users u ON u.id = j.lead_inspector_id
                                WHERE j.id IN (" . implode(',', $ids) . ') ORDER BY FIELD(j.id, ' . implode(',', $ids) . ')') : [];
        $rows = self::withCounts($rows);     // sub-job counts for the 20 rows shown only, in one grouped query
        $this->view('admin/jobs/index', compact('rows', 'meta', 'status', 'q', 'counts', 'client', 'inspector') + [
            'title' => 'Jobs', 'crumbs' => ['Jobs & Sub-jobs' => null],
            'clients' => DB::all('SELECT id, name FROM clients ORDER BY name'),
            'inspectors' => DB::all("SELECT id, name FROM users WHERE role='inspector' ORDER BY name"),
        ]);
    }

    /** Adds n_total / n_done / n_pending / n_qc to a page of jobs with ONE grouped query (no per-row sub-selects). */
    private static function withCounts(array $rows): array
    {
        $ids = array_map('intval', array_column($rows, 'id'));
        $c = [];
        if ($ids) {
            foreach (DB::all("SELECT job_id, SUM(status <> 'cancelled') n_total, SUM(status = 'approved') n_done, SUM(status = 'pending') n_pending, SUM(status = 'submitted') n_qc
                              FROM sub_jobs WHERE job_id IN (" . implode(',', $ids) . ') GROUP BY job_id') as $r) $c[(int) $r['job_id']] = $r;
        }
        foreach ($rows as &$r) {
            $x = $c[(int) $r['id']] ?? [];
            foreach (['n_total', 'n_done', 'n_pending', 'n_qc'] as $k) $r[$k] = (int) ($x[$k] ?? 0);
        }
        unset($r);
        return $rows;
    }

    public function create(): void
    {
        $this->view('admin/jobs/form', [
            'title' => 'New Job', 'crumbs' => ['Jobs & Sub-jobs' => 'admin/jobs', 'New job' => null],
            'request' => null,
            'clients' => DB::all("SELECT id, code, name FROM clients WHERE status='active' ORDER BY name"),
            'inspectors' => JobService::inspectorsFor(),
            'action' => url('admin/jobs'),
        ]);
    }

    public function store(): void
    {
        $this->attempt(function () {
            if (empty($_POST['client_id'])) throw new DomainException('Client is required.');
            if (trim($_POST['title'] ?? '') === '') throw new DomainException('Job title is required.');
            $id = JobService::create($_POST, []);
            flash('success', 'Job ' . DB::value('SELECT job_no FROM jobs WHERE id = ?', [$id]) . ' created. The assigned inspector can now add sub-jobs.');
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
            $s['sticker'] = DB::one("SELECT id, sticker_no, status, color FROM stickers WHERE sub_job_id = ? AND status = 'closed' ORDER BY id DESC LIMIT 1", [$s['id']]);
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

    /** Final approval after QC: closes the job and releases certificates to the client. */
    public function close(string $id): void
    {
        // second check: the admin must type the text shown in the verification image (one-time, expires after 10 minutes)
        $this->attempt(function () use ($id) {
            $c = $_SESSION['close_captcha'] ?? null;
            unset($_SESSION['close_captcha']);
            if (!$c || time() - $c['t'] > 600 || strcasecmp(trim((string) input('captcha', '')), $c['code']) !== 0) {
                throw new DomainException('The verification text does not match the image. Please try again.');
            }
            JobService::close((int) $id, (string) input('note', ''));
        }, 'Final approval given. The job is closed and the client can now download the certificates.');
    }

    /** One-time check of the typed verification text; throws when it does not match the image. */
    public static function checkCaptcha(): void
    {
        $c = $_SESSION['close_captcha'] ?? null;
        unset($_SESSION['close_captcha']);
        if (!$c || time() - $c['t'] > 600 || strcasecmp(trim((string) input('captcha', '')), $c['code']) !== 0) {
            throw new DomainException('The verification text does not match the image. Please try again.');
        }
    }

    /** Verification image shown beside the "close job" and "QC approve" buttons. The code lives only in the session. */
    public function captcha(): void
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';        // no 0/O/1/I to avoid misreading
        $code = '';
        for ($i = 0; $i < 5; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
        $_SESSION['close_captcha'] = ['code' => $code, 't' => time()];

        $w = 190; $h = 60;
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 244, 246, 250));
        for ($i = 0; $i < 7; $i++) {                         // noise lines
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), imagecolorallocate($img, random_int(150, 205), random_int(150, 205), random_int(150, 205)));
        }
        for ($i = 0; $i < 120; $i++) imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), imagecolorallocate($img, 170, 170, 190));
        $font = __DIR__ . '/../../vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf';
        foreach (str_split($code) as $i => $ch) {
            $col = imagecolorallocate($img, random_int(10, 90), random_int(10, 90), random_int(60, 140));
            if (is_file($font)) imagettftext($img, 28, random_int(-22, 22), 14 + $i * 33, random_int(40, 50), $col, $font, $ch);
            else imagestring($img, 5, 18 + $i * 33, 22, $ch, $col);
        }
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        imagepng($img);
        exit;
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
        // page through JOBS that still have unassigned sub-jobs (20 per page), then load only those jobs' sub-jobs
        $jobSql = "SELECT j.id FROM jobs j WHERE j.status <> 'cancelled' AND EXISTS (SELECT 1 FROM sub_jobs s WHERE s.job_id = j.id AND s.status = 'pending')
                   ORDER BY FIELD(j.priority,'urgent','high','normal','low'), j.scheduled_date IS NULL, j.scheduled_date, j.id";
        [$jobRows, $meta] = paginate($jobSql, [], 20, "SELECT COUNT(DISTINCT s.job_id) FROM sub_jobs s JOIN jobs j ON j.id = s.job_id WHERE s.status = 'pending' AND j.status <> 'cancelled'");
        $ids = array_map('intval', array_column($jobRows, 'id'));
        $pending = $ids ? DB::all(JobService::SUBJOB_SQL . " WHERE sj.status = 'pending' AND sj.job_id IN (" . implode(',', $ids) . ')
                        ORDER BY FIELD(j.priority,\'urgent\',\'high\',\'normal\',\'low\'), j.scheduled_date IS NULL, j.scheduled_date, sj.job_id, sj.seq') : [];
        $byJob = [];
        foreach ($pending as $s) $byJob[$s['job_id']][] = $s;
        $inspectors = JobService::inspectorsFor();
        $skills = [];
        foreach (DB::all('SELECT k.user_id, t.code FROM inspector_skills k JOIN inspection_types t ON t.id = k.inspection_type_id') as $r) {
            $skills[$r['user_id']][] = $r['code'];
        }
        $this->view('admin/jobs/board', compact('byJob', 'inspectors', 'skills', 'meta') + [
            'title' => 'Assignment Board', 'crumbs' => ['Assignment Board' => null],
        ]);
    }
}
