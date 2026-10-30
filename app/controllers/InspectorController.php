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
        // page through the inspector's JOBS (15 per page), then load only those jobs' sub-jobs
        $jobSql = "SELECT j.id FROM jobs j WHERE EXISTS (SELECT 1 FROM sub_jobs s WHERE s.job_id = j.id AND s.inspector_id = ? AND s.status IN ($statuses))
                   ORDER BY j.scheduled_date IS NULL, j.scheduled_date, j.id";
        [$jobRows, $meta] = paginate($jobSql, [Auth::id()], 15,
            "SELECT COUNT(DISTINCT s.job_id) FROM sub_jobs s WHERE s.inspector_id = ? AND s.status IN ($statuses)");
        $ids = array_map('intval', array_column($jobRows, 'id'));
        $subs = $ids ? DB::all(JobService::SUBJOB_SQL . " WHERE sj.inspector_id = ? AND sj.status IN ($statuses) AND sj.job_id IN (" . implode(',', $ids) . ')
                       ORDER BY j.scheduled_date IS NULL, j.scheduled_date, j.id, sj.seq', [Auth::id()]) : [];
        $jobs = [];
        foreach ($subs as $s) {
            $jobs[$s['job_id']]['job'] = $s;
            $jobs[$s['job_id']]['subs'][] = $s;
        }
        // also include jobs assigned to me but with no sub-jobs yet
        $myJobs = DB::all("SELECT j.*, c.name client_name FROM jobs j JOIN clients c ON c.id = j.client_id
                           WHERE j.lead_inspector_id = ? AND j.status NOT IN ('completed','cancelled')
                           AND NOT EXISTS (SELECT 1 FROM sub_jobs s WHERE s.job_id = j.id AND s.inspector_id = ?)
                           ORDER BY j.scheduled_date IS NULL, j.scheduled_date, j.id LIMIT 100", [Auth::id(), Auth::id()]);
        $this->view('inspector/jobs', compact('jobs', 'filter', 'myJobs', 'meta') + ['title' => 'Assigned Jobs', 'crumbs' => ['Assigned Jobs' => null]]);
    }

    public function job(string $id): void
    {
        $job = DB::one('SELECT j.*, c.name client_name, c.contact_person, c.phone client_phone FROM jobs j JOIN clients c ON c.id = j.client_id WHERE j.id = ?', [(int) $id]);
        if (!$job) $this->notFound();
        // inspector must be the lead inspector or have at least one sub-job on this job
        $hasAccess = (int) $job['lead_inspector_id'] === Auth::id()
            || DB::value('SELECT id FROM sub_jobs WHERE job_id = ? AND inspector_id = ? LIMIT 1', [(int) $id, Auth::id()]);
        if (!$hasAccess) $this->forbid();
        $subs = DB::all(JobService::SUBJOB_SQL . ' WHERE sj.job_id = ? AND sj.inspector_id = ? ORDER BY sj.seq', [(int) $id, Auth::id()]);
        $this->view('inspector/job', compact('job', 'subs') + ['title' => $job['job_no'], 'crumbs' => ['Assigned Jobs' => 'inspector/jobs', $job['job_no'] => null]]);
    }

    /** Show form to add a new sub-job to an assigned job. */
    public function createSubJob(string $id): void
    {
        $job = DB::one('SELECT j.*, c.name client_name FROM jobs j JOIN clients c ON c.id = j.client_id WHERE j.id = ?', [(int) $id]);
        if (!$job) $this->notFound();
        if ((int) $job['lead_inspector_id'] !== Auth::id()) $this->forbid();
        if (in_array($job['status'], ['completed', 'cancelled'], true)) {
            flash('error', 'Cannot add sub-jobs to a ' . Workflow::label($job['status']) . ' job.');
            redirect('inspector/jobs/' . $id);
        }
        $this->view('inspector/subjob_form', [
            'job'           => $job,
            'types'         => DB::all("SELECT t.id, t.code, t.name FROM inspection_types t WHERE t.status = 'active'
                    AND EXISTS (SELECT 1 FROM checklist_templates c WHERE c.inspection_type_id = t.id AND c.is_active = 1)
                    ORDER BY t.name"),
            'equipment'     => DB::all('SELECT id, description, equipment_no FROM equipment WHERE client_id = ? ORDER BY description', [$job['client_id']]),
            'certTemplates' => DB::all("SELECT c.id, c.title, c.doc_ref, c.inspection_type_id FROM certificate_templates c JOIN inspection_types t ON t.id = c.inspection_type_id
                    WHERE c.is_active = 1
                      AND c.version = (SELECT MAX(v.version) FROM certificate_templates v WHERE v.inspection_type_id = c.inspection_type_id AND v.is_active = 1)
                    ORDER BY t.category, t.name"),
            'title'         => 'Add Sub-job',
            'crumbs'        => ['Assigned Jobs' => 'inspector/jobs', $job['job_no'] => 'inspector/jobs/' . $id, 'Add sub-job' => null],
        ]);
    }

    /** Store one or more sub-jobs created by the inspector. */
    public function storeSubJob(string $id): void
    {
        $this->attempt(function () use ($id) {
            $job = DB::one('SELECT * FROM jobs WHERE id = ?', [(int) $id]);
            if (!$job) throw new DomainException('Job not found.');
            if ((int) $job['lead_inspector_id'] !== Auth::id()) throw new DomainException('You are not assigned to this job.');
            if (in_array($job['status'], ['completed', 'cancelled'], true)) {
                throw new DomainException('Cannot add sub-jobs to a ' . Workflow::label($job['status']) . ' job.');
            }
            if (input('kind') === 'operator') {
                return $this->storeOperatorAssessment($job);
            }
            if (!(int) input('cert_template_id', 0)) throw new DomainException('Select the certificate type.');
            $qty = max(1, min(50, (int) input('quantity', 1)));
            for ($i = 0; $i < $qty; $i++) {
                $sjId = JobService::addSubJob((int) $id, $_POST);
                // auto-assign to self
                JobService::assign([$sjId], Auth::id(), 'Created by inspector');
            }
            flash('success', $qty . ' sub-job(s) added and assigned to you.');
            return 'inspector/jobs/' . $id;
        });
    }

    /** Operator assessment: create the sub-job, issue the worker ID card (valid at once), then wait for the document. */
    private function storeOperatorAssessment(array $job): string
    {
        $in = $_POST;
        $in['holder_type'] = 'worker';
        $in['client_id'] = $job['client_id'];
        foreach (['holder_name', 'company', 'title', 'issue_date', 'expiry_date'] as $k) {
            if (trim((string) ($in[$k] ?? '')) === '') throw new DomainException('Fill in all required card details.');
        }
        $photo = !empty($_FILES['photo']['name']) ? store_upload($_FILES['photo'], 'id-photos', ['image/jpeg', 'image/png', 'image/webp']) : null;
        return DB::tx(function () use ($job, $in, $photo) {
            $sjId = JobService::addOperatorSubJob((int) $job['id'], Auth::id());
            $in['job_ref'] = trim((string) ($in['job_ref'] ?? '')) ?: DB::value('SELECT sub_job_no FROM sub_jobs WHERE id = ?', [$sjId]);
            $cardId = InspectorCardService::save(null, $in, $photo);
            DB::update('inspector_cards', ['sub_job_id' => $sjId], 'id = ?', [$cardId]);
            InspectorCardService::handOver($cardId);
            flash('success', 'Operator assessment created and the ID card issued. Upload the assessment document to close it.');
            return 'inspector/subjobs/' . $sjId;
        });
    }

    /** Operator assessment: enter or correct the Certificate of Competence details (also after closing). */
    public function operatorCertificateDetails(string $id): void
    {
        $this->attempt(function () use ($id) {
            $sj = JobService::subJob((int) $id);
            if (!$sj || $sj['kind'] !== 'operator') throw new DomainException('Operator assessment not found.');
            if ((int) $sj['inspector_id'] !== Auth::id()) throw new DomainException('This sub-job is not assigned to you.');
            $training = [];
            foreach (['program', 'completion_date', 'renewal_date', 'trainer'] as $k) $training[$k] = trim((string) input($k, ''));
            if ($training['program'] === '' || $training['completion_date'] === '') throw new InvalidArgumentException('Enter the training program and the completion date.');
            JobService::saveOperatorCertificate((int) $sj['id'], $training);
            flash('success', 'Certificate details saved.');
            return 'inspector/subjobs/' . (int) $sj['id'];
        });
    }

    /** Operator assessment: the uploaded document closes the sub-job. */
    public function operatorDocument(string $id): void
    {
        $this->attempt(function () use ($id) {
            $sj = JobService::subJob((int) $id);
            if (!$sj || $sj['kind'] !== 'operator') throw new DomainException('Operator assessment not found.');
            if ((int) $sj['inspector_id'] !== Auth::id()) throw new DomainException('This sub-job is not assigned to you.');
            if ($sj['status'] !== 'in_progress') throw new DomainException('This operator assessment is already closed.');
            if (empty($_FILES['document']['name'])) throw new InvalidArgumentException('Choose the assessment document to upload.');
            $training = [];
            foreach (['program', 'completion_date', 'renewal_date', 'trainer'] as $k) $training[$k] = trim((string) input($k, ''));
            if ($training['program'] === '' || $training['completion_date'] === '') throw new InvalidArgumentException('Enter the training program and the completion date for the certificate.');
            DB::tx(function () use ($sj, $training) {
                InspectionService::addAttachment((int) $sj['id'], $_FILES['document'], 'document', input('caption'), false);
                JobService::closeOperatorSubJob((int) $sj['id'], $training);
            });
            flash('success', 'Document uploaded. Operator assessment ' . $sj['sub_job_no'] . ' is closed and the Certificate of Competence is ready.');
            return 'inspector/jobs/' . (int) $sj['job_id'];
        });
    }

    public function subJob(string $id): void
    {
        $sj = JobService::subJob((int) $id);
        if (!$sj) $this->notFound();
        if (Auth::is('inspector') && (int) $sj['inspector_id'] !== Auth::id()) $this->forbid();

        if ($sj['kind'] === 'operator') {
            $card = DB::one('SELECT c.*, NULL AS user_status FROM inspector_cards c WHERE c.sub_job_id = ? ORDER BY c.id DESC', [$sj['id']]);
            $doc = DB::one("SELECT * FROM attachments WHERE sub_job_id = ? AND kind = 'document' ORDER BY id DESC", [$sj['id']]);
            $this->view('inspector/operator', ['sj' => $sj, 'card' => $card, 'doc' => $doc, 'title' => 'Operator assessment ' . $sj['sub_job_no'],
                'crumbs' => ['Assigned Jobs' => 'inspector/jobs', $sj['job_no'] => 'inspector/jobs/' . $sj['job_id'], $sj['sub_job_no'] => null]]);
            return;
        }
        if (Auth::is('qc') && $sj['status'] !== 'submitted') redirect('qc/review/' . (int) $sj['id']);   // QC works on this page only while a sub-job awaits review
        $checklist = DB::one('SELECT * FROM checklists WHERE sub_job_id = ?', [$sj['id']]);
        $items = $checklist ? DB::all(
            'SELECT i.*, r.result, r.remarks FROM checklist_template_items i LEFT JOIN checklist_responses r ON r.item_id = i.id AND r.checklist_id = ?
             WHERE i.template_id = ? ORDER BY i.sort_order', [$checklist['id'], $checklist['template_id']]) : [];
        $clStats = $checklist ? InspectionService::checklistStats((int) $checklist['id']) : null;

        // Load ALL certificate pages; $cert = page being viewed (default: seq=1)
        $certSeq   = max(1, (int) input('cert_page', 1));
        $allCerts  = DB::all('SELECT * FROM certificates WHERE sub_job_id = ? ORDER BY seq', [$sj['id']]);
        $cert      = null;
        foreach ($allCerts as $c) { if ((int)$c['seq'] === $certSeq) { $cert = $c; break; } }
        if (!$cert) $cert = $allCerts[0] ?? null;  // fallback to first
        $certSeq   = $cert ? (int)$cert['seq'] : 1;

        $certTpl = $cert && $cert['template_id'] ? DB::one('SELECT * FROM certificate_templates WHERE id = ?', [$cert['template_id']]) : InspectionService::activeCertificateTemplate((int) $sj['inspection_type_id']);
        $certData = $cert ? (json_decode($cert['data'] ?? '{}', true) ?: []) : [];
        $fields = [];
        foreach ($certTpl ? (json_decode($certTpl['fields'], true) ?: []) : [] as $f) {
            $f['bound'] = !empty($f['source']);
            $f['value'] = $f['bound'] ? CertificateService::fieldValue($f, $sj, $cert ?? [], $certData) : ($certData[$f['key']] ?? '');
            $fields[] = $f;
        }
        $attachments = DB::all('SELECT a.*, u.name uploader FROM attachments a LEFT JOIN users u ON u.id = a.uploaded_by WHERE a.sub_job_id = ? ORDER BY a.id DESC', [$sj['id']]);
        $sticker = DB::one("SELECT * FROM stickers WHERE sub_job_id = ? AND status = 'closed' ORDER BY id DESC LIMIT 1", [$sj['id']]);
        $myStickers = $sticker ? [] : DB::all("SELECT sticker_no, color FROM stickers WHERE status = 'allocated' AND inspector_id = ? ORDER BY color, sticker_no LIMIT 300", [(int) ($sj['inspector_id'] ?: Auth::id())]);
        $reviews = DB::all('SELECT q.*, u.name reviewer FROM qc_reviews q JOIN users u ON u.id = q.reviewer_id WHERE q.sub_job_id = ? ORDER BY q.id DESC', [$sj['id']]);
        $issues = in_array($sj['status'], ['in_progress'], true) ? InspectionService::issues((int) $sj['id']) : [];
        $qcEdit = Auth::is('qc') && $sj['status'] === 'submitted' && (int) $sj['inspector_id'] !== Auth::id();
        $editable = $sj['status'] === 'in_progress' || $qcEdit;
        $this->view('inspector/workspace', compact('sj', 'checklist', 'items', 'clStats', 'cert', 'certSeq', 'allCerts', 'certTpl', 'fields', 'attachments', 'sticker', 'myStickers', 'reviews', 'issues', 'editable', 'qcEdit') + [
            'title' => $sj['sub_job_no'],
            'crumbs' => $qcEdit ? ['Review Queue' => 'qc/queue', $sj['sub_job_no'] => 'qc/review/' . $sj['id'], 'Edit documents' => null] : (Auth::is('admin')
                ? ['Jobs & Sub-jobs' => 'admin/jobs', $sj['job_no'] => 'admin/jobs/' . $sj['job_id'], 'Sub-job ' . $sj['seq'] => null]
                : ['Assigned Jobs' => 'inspector/jobs', $sj['job_no'] => 'inspector/jobs/' . $sj['job_id'], 'Sub-job ' . $sj['seq'] => null]),
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
        $seq = max(1, (int) ($_POST['seq'] ?? 1));
        $this->attempt(fn () => InspectionService::saveCertificate((int) $id, $_POST, $seq), 'Certificate details saved.');
    }

    /** Clone page 1 → pages 2..N based on posted qty. */
    public function cloneCertificate(string $id): void
    {
        $qty = max(1, min(100, (int) ($_POST['qty'] ?? 1)));
        $this->attempt(
            fn () => InspectionService::cloneCertificate((int) $id, $qty),
            $qty > 1 ? "Generated $qty certificate pages." : 'Kept 1 certificate page.',
            'inspector/subjobs/' . (int) $id . '#tab-certificate'
        );
    }

    /** Links an allocated sticker to the sub-job. Once linked the sticker is closed and cannot be changed. */
    public function sticker(string $id): void
    {
        $this->attempt(function () use ($id) {
            InspectionService::linkSticker((int) $id, (string) input('sticker_no', ''));
            flash('success', 'Sticker linked to this job and closed.');
            return 'inspector/subjobs/' . (int) $id . '#tab-sticker';
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
                ], (string) input('kind', 'photo'), input('caption'), !empty($_POST['on_certificate']), ((int) input('cert_seq', 0)) ?: null);
                $n++;
            }
            if (!$n) throw new InvalidArgumentException('Choose at least one file.');
            flash('success', "$n file(s) uploaded.");
            return 'inspector/subjobs/' . (int) $id . '#tab-files';
        });
    }

    public function attachmentPage(string $id): void
    {
        $seq = ((int) ($_POST['cert_seq'] ?? 0)) ?: null;
        try {
            $sjId = InspectionService::setAttachmentPage((int) $id, $seq);
            flash('success', $seq ? "Photo will print on certificate page $seq only." : 'Photo will print on every certificate page.');
            redirect('inspector/subjobs/' . $sjId . '#tab-files');
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            redirect($_SERVER['HTTP_REFERER'] ?? 'inspector');
        }
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

    /** Stickers handed to me (unused) and the ones I have linked to jobs. */
    public function stickers(): void
    {
        $tab = input('tab') === 'used' ? 'used' : 'hand';
        $hand = DB::all("SELECT * FROM stickers WHERE inspector_id = ? AND status = 'allocated' ORDER BY color, sticker_no", [Auth::id()]);
        [$used, $meta] = paginate("SELECT s.*, sj.sub_job_no, sj.status sj_status, e.description eq_description, e.equipment_no, cl.name client_name, c.result
            FROM stickers s JOIN sub_jobs sj ON sj.id = s.sub_job_id LEFT JOIN equipment e ON e.id = s.equipment_id LEFT JOIN clients cl ON cl.id = e.client_id
            LEFT JOIN certificates c ON c.sub_job_id = sj.id
            WHERE s.inspector_id = ? AND s.status IN ('closed','void') ORDER BY s.linked_at DESC", [Auth::id()], 30);
        $this->view('inspector/stickers', compact('tab', 'hand', 'used', 'meta') + ['title' => 'My Stickers', 'crumbs' => ['My Stickers' => null]]);
    }

    /**
     * The inspector's ID card — only once the admin has marked the printed card as handed over —
     * plus the ID photo the inspector uploads for the next card.
     */
    public function card(): void
    {
        $card = InspectorCardService::find("c.user_id = ? AND c.handed_over_at IS NOT NULL ORDER BY c.status = 'active' DESC, c.handed_over_at DESC", [Auth::id()]);
        $this->view('inspector/card', [
            'm' => $card ? InspectorCardService::model($card) : null,
            'photo' => InspectorCardService::profilePhoto(Auth::id()),
            'pending' => (bool) DB::value("SELECT id FROM inspector_cards WHERE user_id = ? AND status = 'active' AND handed_over_at IS NULL LIMIT 1", [Auth::id()]),
            'title' => 'My ID Card', 'crumbs' => ['My ID Card' => null],
        ]);
    }

    public function cardPhoto(): void
    {
        $this->attempt(function () {
            $f = store_upload($_FILES['photo'] ?? [], 'id-photos', ['image/jpeg', 'image/png', 'image/webp']);
            InspectorCardService::setProfilePhoto(Auth::id(), $f['path']);
            Audit::log('id_photo.uploaded', 'user', Auth::id());
            Notify::role('admin', 'ID photo uploaded', Auth::user()['name'] . ' uploaded a new ID card photo.', 'admin/users/' . Auth::id());
        }, 'Photo uploaded. The administrator will use it for your ID card.', 'inspector/card');
    }
}
