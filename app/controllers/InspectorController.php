<?php

final class InspectorController extends Controller
{
    public function dashboard(): void
    {
        $me = Auth::id();
        $k = DB::one("SELECT SUM(status='assigned') assigned, SUM(status='in_progress') in_progress, SUM(status='rework') rework,
                             SUM(status='submitted') submitted, SUM(status='approved' AND approved_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) approved_month
                      FROM sub_jobs WHERE inspector_id = ?", [$me]);
        $queue = DB::all(JobService::SUBJOB_SQL . " WHERE sj.inspector_id = ? AND sj.status IN ('rework','in_progress','assigned')
                         ORDER BY FIELD(sj.status,'rework','in_progress','assigned'), sj.scheduled_date IS NULL, sj.scheduled_date, sj.id LIMIT 12", [$me]);
        $reworkNotes = [];
        foreach ($queue as $s) {
            if ($s['status'] === 'rework') {
                $reworkNotes[$s['id']] = DB::one("SELECT q.comments, q.created_at, u.name FROM qc_reviews q JOIN users u ON u.id = q.reviewer_id WHERE q.sub_job_id = ? AND q.decision = 'rework' ORDER BY q.id DESC LIMIT 1", [$s['id']]);
            }
        }
        $recent = DB::all(JobService::SUBJOB_SQL . " WHERE sj.inspector_id = ? AND sj.status IN ('submitted','approved') ORDER BY COALESCE(sj.approved_at, sj.submitted_at) DESC LIMIT 5", [$me]);
        $profile = DB::one('SELECT * FROM inspector_profiles WHERE user_id = ?', [$me]);
        $this->view('inspector/dashboard', compact('k', 'queue', 'reworkNotes', 'recent', 'profile') + ['title' => 'My Day', 'crumbs' => ['My Day' => null]]);
    }

    public function jobs(): void
    {
        $filter = (string) input('f', 'open');
        $statuses = $filter === 'all' ? "'assigned','in_progress','rework','submitted','approved'" : "'assigned','in_progress','rework'";
        $subs = DB::all(JobService::SUBJOB_SQL . " WHERE sj.inspector_id = ? AND sj.status IN ($statuses) ORDER BY j.scheduled_date IS NULL, j.scheduled_date, j.id, sj.seq", [Auth::id()]);
        $jobs = [];
        foreach ($subs as $s) {
            $jobs[$s['job_id']]['job'] = $s;
            $jobs[$s['job_id']]['subs'][] = $s;
        }
        $this->view('inspector/jobs', compact('jobs', 'filter') + ['title' => 'Assigned Jobs', 'crumbs' => ['Assigned Jobs' => null]]);
    }

    public function job(string $id): void
    {
        $subs = DB::all(JobService::SUBJOB_SQL . ' WHERE sj.job_id = ? AND sj.inspector_id = ? ORDER BY sj.seq', [(int) $id, Auth::id()]);
        if (!$subs) $this->forbid();
        $job = DB::one('SELECT j.*, c.name client_name, c.contact_person, c.phone client_phone FROM jobs j JOIN clients c ON c.id = j.client_id WHERE j.id = ?', [(int) $id]);
        $this->view('inspector/job', compact('job', 'subs') + ['title' => $job['job_no'], 'crumbs' => ['Assigned Jobs' => 'inspector/jobs', $job['job_no'] => null]]);
    }

    public function subJob(string $id): void
    {
        $sj = JobService::subJob((int) $id);
        if (!$sj) $this->notFound();
        if (Auth::is('inspector') && (int) $sj['inspector_id'] !== Auth::id()) $this->forbid();

        $checklist = DB::one('SELECT * FROM checklists WHERE sub_job_id = ?', [$sj['id']]);
        $items = $checklist ? DB::all(
            'SELECT i.*, r.result, r.remarks FROM checklist_template_items i LEFT JOIN checklist_responses r ON r.item_id = i.id AND r.checklist_id = ?
             WHERE i.template_id = ? ORDER BY i.sort_order', [$checklist['id'], $checklist['template_id']]) : [];
        $clStats = $checklist ? InspectionService::checklistStats((int) $checklist['id']) : null;
        $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ?', [$sj['id']]);
        $certTpl = $cert && $cert['template_id'] ? DB::one('SELECT * FROM certificate_templates WHERE id = ?', [$cert['template_id']]) : InspectionService::activeCertificateTemplate((int) $sj['inspection_type_id']);
        $certData = $cert ? (json_decode($cert['data'] ?? '{}', true) ?: []) : [];
        $fields = [];
        foreach ($certTpl ? (json_decode($certTpl['fields'], true) ?: []) : [] as $f) {
            $f['bound'] = !empty($f['source']);
            $f['value'] = $f['bound'] ? CertificateService::fieldValue($f, $sj, $cert ?? [], $certData) : ($certData[$f['key']] ?? '');
            $fields[] = $f;
        }
        $attachments = DB::all('SELECT a.*, u.name uploader FROM attachments a LEFT JOIN users u ON u.id = a.uploaded_by WHERE a.sub_job_id = ? ORDER BY a.id DESC', [$sj['id']]);
        $sticker = DB::one("SELECT * FROM stickers WHERE sub_job_id = ? AND status IN ('reserved','issued') ORDER BY id DESC LIMIT 1", [$sj['id']]);
        $reviews = DB::all('SELECT q.*, u.name reviewer FROM qc_reviews q JOIN users u ON u.id = q.reviewer_id WHERE q.sub_job_id = ? ORDER BY q.id DESC', [$sj['id']]);
        $issues = in_array($sj['status'], ['in_progress'], true) ? InspectionService::issues((int) $sj['id']) : [];
        $editable = $sj['status'] === 'in_progress';
        $this->view('inspector/workspace', compact('sj', 'checklist', 'items', 'clStats', 'cert', 'certTpl', 'fields', 'attachments', 'sticker', 'reviews', 'issues', 'editable') + [
            'title' => $sj['sub_job_no'],
            'crumbs' => Auth::is('admin')
                ? ['Jobs & Sub-jobs' => 'admin/jobs', $sj['job_no'] => 'admin/jobs/' . $sj['job_id'], 'Sub-job ' . $sj['seq'] => null]
                : ['Assigned Jobs' => 'inspector/jobs', $sj['job_no'] => 'inspector/jobs/' . $sj['job_id'], 'Sub-job ' . $sj['seq'] => null],
        ]);
    }

    public function start(string $id): void
    {
        $this->attempt(fn () => InspectionService::start((int) $id), 'Inspection started. Checklist and certificate drafts are ready.', 'inspector/subjobs/' . (int) $id . '#tab-checklist');
    }

    /** Non-JS fallback; the workspace autosaves through the API. */
    public function saveChecklist(string $id): void
    {
        $this->attempt(fn () => InspectionService::saveChecklist((int) $id, (array) ($_POST['r'] ?? []), ['serial_no' => input('serial_no'), 'inspection_date' => input('inspection_date'), 'remarks' => $_POST['cl_remarks'] ?? null, 'data' => $_POST['cl_data'] ?? null]), 'Checklist saved.');
    }

    public function saveCertificate(string $id): void
    {
        $this->attempt(fn () => InspectionService::saveCertificate((int) $id, $_POST), 'Certificate details saved.');
    }

    public function sticker(string $id): void
    {
        $this->attempt(function () use ($id) {
            if (input('action') === 'release') {
                InspectionService::releaseSticker((int) $id);
                flash('success', 'Sticker released back to stock.');
            } else {
                InspectionService::reserveSticker((int) $id, (string) input('sticker_no', ''));
                flash('success', 'Sticker recorded against this inspection.');
            }
        });
    }

    public function upload(string $id): void
    {
        $this->attempt(function () use ($id) {
            $files = $_FILES['files'] ?? null;
            if (!$files || !is_array($files['name'])) throw new InvalidArgumentException('Choose at least one file.');
            $n = 0;
            foreach ($files['name'] as $i => $name) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                InspectionService::addAttachment((int) $id, [
                    'name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i],
                ], (string) input('kind', 'photo'), input('caption'), !empty($_POST['on_certificate']));
                $n++;
            }
            if (!$n) throw new InvalidArgumentException('Choose at least one file.');
            flash('success', "$n file(s) uploaded.");
            return 'inspector/subjobs/' . (int) $id . '#tab-files';
        });
    }

    public function deleteAttachment(string $id): void
    {
        $this->attempt(fn () => InspectionService::deleteAttachment((int) $id), 'Attachment removed.');
    }

    public function submit(string $id): void
    {
        try {
            InspectionService::submit((int) $id, input('override_reason'));
            flash('success', 'Submitted to QC. You will be notified when it is reviewed.');
            redirect(Auth::is('admin') ? 'admin/jobs/' . DB::value('SELECT job_id FROM sub_jobs WHERE id = ?', [(int) $id]) : 'inspector');
        } catch (ValidationFailed $e) {
            flash('error', "Cannot submit yet:\n• " . implode("\n• ", $e->issues));
            redirect('inspector/subjobs/' . (int) $id . '#tab-submit');
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            redirect('inspector/subjobs/' . (int) $id);
        }
    }

    public function history(): void
    {
        [$rows, $meta] = paginate(JobService::SUBJOB_SQL . " WHERE sj.inspector_id = ? AND sj.status IN ('submitted','approved') ORDER BY COALESCE(sj.approved_at, sj.submitted_at) DESC", [Auth::id()]);
        foreach ($rows as &$r) $r['cert'] = DB::one('SELECT certificate_no, status FROM certificates WHERE sub_job_id = ?', [$r['id']]);
        unset($r);
        $this->view('inspector/history', compact('rows', 'meta') + ['title' => 'Submitted & Approved', 'crumbs' => ['Submitted & Approved' => null]]);
    }
}
