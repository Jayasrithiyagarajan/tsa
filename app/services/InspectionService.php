<?php

final class ValidationFailed extends DomainException
{
    public array $issues;

    public function __construct(array $issues)
    {
        $this->issues = $issues;
        parent::__construct('The inspection is incomplete: ' . implode(' ', $issues));
    }
}

final class InspectionService
{
    public static function activeChecklistTemplate(int $typeId): ?array
    {
        return DB::one('SELECT * FROM checklist_templates WHERE inspection_type_id = ? AND is_active = 1 ORDER BY version DESC LIMIT 1', [$typeId]);
    }

    public static function activeCertificateTemplate(int $typeId): ?array
    {
        return DB::one('SELECT * FROM certificate_templates WHERE inspection_type_id = ? AND is_active = 1 ORDER BY version DESC LIMIT 1', [$typeId]);
    }

    /** Throws unless the current user may work on this sub-job. */
    public static function authorizeWork(array $sj): void
    {
        if (Auth::is('admin')) return;
        if (Auth::is('inspector') && (int) $sj['inspector_id'] === Auth::id()) return;
        throw new DomainException('This sub-job is not assigned to you.');
    }

    private static function assertEditable(array $sj): void
    {
        if ($sj['status'] !== 'in_progress') {
            throw new DomainException('Start the inspection before recording results (current status: ' . Workflow::label($sj['status']) . ').');
        }
    }

    /** assigned|rework → in_progress. Creates checklist + certificate drafts with the active template versions. */
    public static function start(int $subJobId): void
    {
        DB::tx(function () use ($subJobId) {
            $sj = JobService::subJob($subJobId);
            if (!$sj) throw new DomainException('Sub-job not found.');
            self::authorizeWork($sj);
            Workflow::assert('sub_job', $sj['status'], 'in_progress');
            DB::update('sub_jobs', ['status' => 'in_progress', 'started_at' => $sj['started_at'] ?: now()], 'id = ?', [$subJobId]);

            if ($sj['requires_checklist'] && !DB::value('SELECT id FROM checklists WHERE sub_job_id = ?', [$subJobId])) {
                $tpl = self::activeChecklistTemplate((int) $sj['inspection_type_id']);
                if ($tpl) {
                    DB::insert('checklists', [
                        'sub_job_id' => $subJobId, 'template_id' => $tpl['id'], 'serial_no' => $sj['eq_serial_no'] ?: $sj['equipment_no'],
                        'inspection_date' => date('Y-m-d'), 'inspector_id' => $sj['inspector_id'],
                    ]);
                }
            } else {
                DB::update('checklists', ['status' => 'draft'], 'sub_job_id = ?', [$subJobId]);
            }

            $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ?', [$subJobId]);
            if ($sj['requires_certificate'] && !$cert) {
                $tpl = !empty($sj['cert_template_id'])
                    ? DB::one('SELECT * FROM certificate_templates WHERE id = ? AND is_active = 1', [$sj['cert_template_id']])
                    : null;
                $tpl = $tpl ?? self::activeCertificateTemplate((int) $sj['inspection_type_id']);
                $today = date('Y-m-d');
                DB::insert('certificates', [
                    'sub_job_id'           => $subJobId,
                    'template_id'          => $tpl['id'] ?? null,
                    'source'               => $tpl ? 'generated' : 'uploaded',
                    'certificate_no'       => Numbering::certificate($sj),
                    'data'                 => json_encode(self::carryOverData($sj, $tpl)),
                    'result_statement'     => $tpl['default_statement'] ?? null,
                    'inspection_date'      => $today,
                    'next_inspection_date' => self::nextDate($today, (int) $sj['validity_months']),
                    'inspector_id'         => $sj['inspector_id'],
                    'verify_token'         => bin2hex(random_bytes(16)),
                ]);
            } elseif ($cert && in_array($cert['status'], ['revoked', 'rejected', 'pending_qc'], true)) {
                DB::update('certificates', ['status' => 'draft', 'inspector_id' => $sj['inspector_id']], 'id = ?', [$cert['id']]);
            }
            Audit::log('inspection.started', 'sub_job', $subJobId);
            JobService::recompute((int) $sj['job_id']);
        });
    }

    /** Re-use manual values (e.g. magnet serial, particle type) from this inspector's last certificate of the same template. */
    private static function carryOverData(array $sj, ?array $tpl): array
    {
        if (!$tpl) return [];
        $prev = DB::value(
            "SELECT c.data FROM certificates c JOIN certificate_templates t ON t.id = c.template_id
             WHERE t.inspection_type_id = ? AND c.inspector_id = ? ORDER BY c.id DESC LIMIT 1",
            [$sj['inspection_type_id'], $sj['inspector_id']]
        );
        if (!$prev) return [];
        $prev = json_decode($prev, true) ?: [];
        $keep = [];
        foreach (json_decode($tpl['fields'], true) ?: [] as $f) {
            if (empty($f['source']) && !empty($f['carry']) && isset($prev[$f['key']])) {
                $keep[$f['key']] = $prev[$f['key']];
            }
        }
        return $keep;
    }

    public static function nextDate(string $from, int $months): string
    {
        $d = new DateTime($from);
        $d->modify("+$months months")->modify('-1 day');
        return $d->format('Y-m-d');
    }

    /** @param array $responses [item_id => ['result' => pass|fail|na|'', 'remarks' => '']] */
    public static function saveChecklist(int $subJobId, array $responses, array $header = []): array
    {
        return DB::tx(function () use ($subJobId, $responses, $header) {
            $sj = JobService::subJob($subJobId);
            if (!$sj) throw new DomainException('Sub-job not found.');
            self::authorizeWork($sj);
            self::assertEditable($sj);
            $cl = DB::one('SELECT * FROM checklists WHERE sub_job_id = ?', [$subJobId]);
            if (!$cl) throw new DomainException('No checklist template is configured for this inspection type.');

            $valid = array_column(DB::all('SELECT id FROM checklist_template_items WHERE template_id = ?', [$cl['template_id']]), 'id', 'id');
            $saved = 0;
            foreach ($responses as $itemId => $r) {
                $itemId = (int) $itemId;
                if (!isset($valid[$itemId])) continue;
                $result = in_array($r['result'] ?? '', ['pass', 'fail', 'na'], true) ? $r['result'] : null;
                $remarks = nullable(mb_substr((string) ($r['remarks'] ?? ''), 0, 255));
                DB::run(
                    'INSERT INTO checklist_responses (checklist_id, item_id, result, remarks) VALUES (?,?,?,?)
                     ON DUPLICATE KEY UPDATE result = VALUES(result), remarks = VALUES(remarks)',
                    [$cl['id'], $itemId, $result, $remarks]
                );
                $saved++;
            }
            $upd = [];
            if (array_key_exists('serial_no', $header)) $upd['serial_no'] = nullable(mb_substr($header['serial_no'], 0, 80));
            if (!empty($header['inspection_date']) && strtotime($header['inspection_date'])) $upd['inspection_date'] = $header['inspection_date'];
            if (array_key_exists('remarks', $header) && $header['remarks'] !== null) $upd['remarks'] = nullable(mb_substr((string) $header['remarks'], 0, 2000));
            if (!empty($header['data']) && is_array($header['data'])) {      // report cover values — only keys the template defines
                $tpl = DB::one('SELECT layout FROM checklist_templates WHERE id = ?', [$cl['template_id']]);
                $allowed = TemplateService::reportManualKeys(TemplateService::checklistLayout($tpl ?: []));
                $data = json_decode((string) ($cl['data'] ?? ''), true) ?: [];
                foreach ($header['data'] as $k => $v) {
                    if (isset($allowed[$k]) && is_scalar($v)) $data[$k] = mb_substr(trim((string) $v), 0, $k === 'vin' ? 17 : 300);
                }
                $upd['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            if ($upd) DB::update('checklists', $upd, 'id = ?', [$cl['id']]);
            return self::checklistStats((int) $cl['id']);
        });
    }

    public static function checklistStats(int $checklistId): array
    {
        $r = DB::one(
            "SELECT COUNT(i.id) total, SUM(r.result IS NOT NULL) answered, SUM(r.result='pass') pass, SUM(r.result='fail') fail,
                    SUM(r.result='na') na, SUM(r.result='fail' AND (r.remarks IS NULL OR r.remarks='')) fail_no_remark,
                    SUM(i.is_mandatory = 1 AND r.result IS NULL) missing
             FROM checklists c JOIN checklist_template_items i ON i.template_id = c.template_id
             LEFT JOIN checklist_responses r ON r.checklist_id = c.id AND r.item_id = i.id
             WHERE c.id = ?",
            [$checklistId]
        );
        return array_map('intval', $r);
    }

    public static function saveCertificate(int $subJobId, array $in): void
    {
        DB::tx(function () use ($subJobId, $in) {
            $sj = JobService::subJob($subJobId);
            if (!$sj) throw new DomainException('Sub-job not found.');
            self::authorizeWork($sj);
            self::assertEditable($sj);
            $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ?', [$subJobId]);
            if (!$cert) throw new DomainException('Certificate draft not found. Start the inspection first.');
            $tpl = $cert['template_id'] ? DB::one('SELECT * FROM certificate_templates WHERE id = ?', [$cert['template_id']]) : null;

            $data = json_decode($cert['data'] ?? '{}', true) ?: [];
            foreach ($tpl ? (json_decode($tpl['fields'], true) ?: []) : [] as $f) {
                if (!empty($f['source'])) continue;                 // bound to system data, not editable
                if (array_key_exists($f['key'], $in['fields'] ?? [])) {
                    $v = mb_substr(trim((string) $in['fields'][$f['key']]), 0, ($f['type'] ?? '') === 'textarea' ? 1500 : 190);
                    if (($f['type'] ?? '') === 'select' && $v !== '' && !in_array($v, $f['options'] ?? [], true)) continue;
                    $data[$f['key']] = $v;
                }
            }
            $upd = ['data' => json_encode($data, JSON_UNESCAPED_UNICODE)];
            $options = $tpl ? explode(',', $tpl['result_options']) : ['accepted', 'rejected', 'repair'];
            if (isset($in['result'])) $upd['result'] = in_array($in['result'], $options, true) ? $in['result'] : null;
            if (isset($in['result_statement'])) $upd['result_statement'] = nullable(mb_substr($in['result_statement'], 0, 255));
            if (isset($in['remarks'])) $upd['remarks'] = nullable(mb_substr($in['remarks'], 0, 2000));
            if (!empty($in['inspection_date']) && strtotime($in['inspection_date'])) {
                $upd['inspection_date'] = $in['inspection_date'];
                $upd['next_inspection_date'] = !empty($in['next_inspection_date']) && strtotime($in['next_inspection_date'])
                    ? $in['next_inspection_date']
                    : self::nextDate($in['inspection_date'], (int) $sj['validity_months']);
            }
            DB::update('certificates', $upd, 'id = ?', [$cert['id']]);
            if (isset($upd['result'])) DB::update('sub_jobs', ['result' => $upd['result']], 'id = ?', [$subJobId]);
        });
    }

    /**
     * Links a physical sticker to the sub-job. The sticker must be allocated to the inspector doing the job
     * (an admin may use any sticker from stock). Linking closes the sticker: it can no longer be changed,
     * released or moved to another job — only an administrator can void it.
     */
    public static function linkSticker(int $subJobId, string $stickerNo): void
    {
        DB::tx(function () use ($subJobId, $stickerNo) {
            $sj = JobService::subJob($subJobId);
            if (!$sj) throw new DomainException('Sub-job not found.');
            self::authorizeWork($sj);
            self::assertEditable($sj);
            if (!$sj['requires_sticker']) throw new DomainException('This inspection type does not use stickers.');
            if ($cur = DB::value("SELECT sticker_no FROM stickers WHERE sub_job_id = ? AND status = 'closed'", [$subJobId])) {
                throw new DomainException("Sticker $cur is already linked to this job and is locked.");
            }
            $no = strtoupper(trim($stickerNo));
            $st = DB::one('SELECT * FROM stickers WHERE sticker_no = ? FOR UPDATE', [$no]);
            if (!$st) throw new DomainException("Sticker $no is not registered.");
            if (!Auth::is('admin') && ($st['status'] !== 'allocated' || (int) $st['inspector_id'] !== Auth::id())) {
                throw new DomainException("Sticker {$st['sticker_no']} is not allocated to you" . ($st['status'] === 'allocated' ? '' : ' (' . Workflow::label($st['status']) . ')') . '.');
            }
            Workflow::assert('sticker', $st['status'], 'closed');
            $result = DB::value('SELECT result FROM certificates WHERE sub_job_id = ?', [$subJobId]);
            if ($msg = StickerService::colourMismatch($st['color'], $result)) throw new DomainException(ucfirst($msg));
            DB::update('stickers', [
                'status' => 'closed', 'linked_at' => now(), 'sub_job_id' => $subJobId, 'equipment_id' => $sj['equipment_id'],
                'inspector_id' => $sj['inspector_id'] ?: Auth::id(),
            ], 'id = ?', [$st['id']]);
            Audit::log('sticker.linked', 'sticker', (int) $st['id'], ['sub_job_id' => $subJobId, 'sub_job_no' => $sj['sub_job_no']]);
        });
    }

    /** Completeness rules applied on submit. Returns human-readable issues; empty = ready. */
    public static function issues(int $subJobId): array
    {
        $sj = JobService::subJob($subJobId);
        $issues = [];
        $stats = null;
        if ($sj['requires_checklist']) {
            $cl = DB::one('SELECT id FROM checklists WHERE sub_job_id = ?', [$subJobId]);
            if (!$cl) {
                $issues[] = 'Checklist has not been started.';
            } else {
                $stats = self::checklistStats((int) $cl['id']);
                if ($stats['missing'] > 0) $issues[] = "Checklist: {$stats['missing']} mandatory item(s) have no result.";
                if ($stats['fail_no_remark'] > 0) $issues[] = "Checklist: {$stats['fail_no_remark']} failed item(s) need a remark.";
            }
        }
        $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ?', [$subJobId]);
        if ($sj['requires_certificate']) {
            if (!$cert) {
                $issues[] = 'Certificate has not been prepared.';
            } elseif ($cert['template_id']) {
                $tpl = DB::one('SELECT * FROM certificate_templates WHERE id = ?', [$cert['template_id']]);
                $data = json_decode($cert['data'] ?? '{}', true) ?: [];
                $missing = [];
                foreach (json_decode($tpl['fields'], true) ?: [] as $f) {
                    if (empty($f['source']) && !empty($f['required']) && trim((string) ($data[$f['key']] ?? '')) === '') {
                        $missing[] = $f['label'];
                    }
                }
                if ($missing) $issues[] = 'Certificate: fill in ' . implode(', ', $missing) . '.';
                if (!$cert['result']) $issues[] = 'Certificate: select the inspection result.';
                if ($cert['result'] === 'accepted' && $stats && $stats['fail'] > 0) {
                    $issues[] = 'Certificate result is "Accepted" but the checklist has failed items.';
                }
            } elseif (!DB::value("SELECT id FROM attachments WHERE sub_job_id = ? AND kind = 'certificate_upload'", [$subJobId])) {
                $issues[] = 'Upload the signed certificate (no template is configured for this inspection type).';
            }
        }
        if ($sj['requires_sticker']) {
            $colour = DB::value("SELECT color FROM stickers WHERE sub_job_id = ? AND status = 'closed'", [$subJobId]);
            if (!$colour) $issues[] = 'Sticker: link the sticker number affixed to the equipment.';
            elseif ($msg = StickerService::colourMismatch($colour, $cert['result'] ?? null)) $issues[] = 'Sticker: ' . $msg;
        }
        return $issues;
    }

    /**
     * in_progress → submitted (QC queue). Incomplete inspections are blocked unless an
     * authorized user (admin / QC) provides an override reason, which is stored and audited.
     */
    public static function submit(int $subJobId, ?string $overrideReason = null): void
    {
        DB::tx(function () use ($subJobId, $overrideReason) {
            $sj = JobService::subJob($subJobId);
            if (!$sj) throw new DomainException('Sub-job not found.');
            self::authorizeWork($sj);
            Workflow::assert('sub_job', $sj['status'], 'submitted');
            $issues = self::issues($subJobId);
            $override = [];
            if ($issues) {
                $overrideReason = trim((string) $overrideReason);
                if (!Auth::is('admin', 'qc') || $overrideReason === '') {
                    throw new ValidationFailed($issues);
                }
                $override = ['override_by' => Auth::id(), 'override_reason' => mb_substr($overrideReason, 0, 255)];
                Audit::log('inspection.override', 'sub_job', $subJobId, ['issues' => $issues, 'reason' => $overrideReason]);
            }
            DB::update('sub_jobs', array_merge(['status' => 'submitted', 'submitted_at' => now()], $override), 'id = ?', [$subJobId]);
            DB::update('checklists', ['status' => 'completed', 'completed_at' => now()], 'sub_job_id = ?', [$subJobId]);
            DB::update('certificates', ['status' => 'pending_qc'], 'sub_job_id = ?', [$subJobId]);
            Audit::log('inspection.submitted', 'sub_job', $subJobId);
            Notify::role('qc', "{$sj['sub_job_no']} ready for QC review", "{$sj['type_name']} · {$sj['client_name']}", 'qc/review/' . $subJobId);
            JobService::recompute((int) $sj['job_id']);
        });
    }

    public static function addAttachment(int $subJobId, array $file, string $kind, ?string $caption, bool $onCertificate): int
    {
        $sj = JobService::subJob($subJobId);
        if (!$sj) throw new DomainException('Sub-job not found.');
        self::authorizeWork($sj);
        if (in_array($sj['status'], ['submitted', 'approved', 'cancelled'], true)) {
            throw new DomainException('Attachments are locked once the inspection is submitted.');
        }
        $kind = in_array($kind, ['photo', 'certificate_upload', 'document'], true) ? $kind : 'photo';
        $mimes = $kind === 'photo' ? ['image/jpeg', 'image/png', 'image/webp'] : null;
        $f = store_upload($file, 'subjobs/' . $subJobId, $mimes);
        $id = DB::insert('attachments', [
            'sub_job_id' => $subJobId, 'kind' => $kind, 'file_path' => $f['path'], 'original_name' => $f['name'],
            'mime' => $f['mime'], 'size_bytes' => $f['size'], 'caption' => nullable($caption),
            'on_certificate' => $onCertificate && str_starts_with($f['mime'], 'image/') ? 1 : 0, 'uploaded_by' => Auth::id(),
        ]);
        Audit::log('attachment.uploaded', 'sub_job', $subJobId, ['attachment_id' => $id, 'kind' => $kind]);
        return $id;
    }

    public static function deleteAttachment(int $attachmentId): void
    {
        $a = DB::one('SELECT * FROM attachments WHERE id = ?', [$attachmentId]);
        if (!$a) throw new DomainException('Attachment not found.');
        $sj = JobService::subJob((int) $a['sub_job_id']);
        self::authorizeWork($sj);
        if (in_array($sj['status'], ['submitted', 'approved'], true)) throw new DomainException('Attachments are locked.');
        DB::run('DELETE FROM attachments WHERE id = ?', [$attachmentId]);
        @unlink(upload_abs($a['file_path']));
        Audit::log('attachment.deleted', 'sub_job', (int) $a['sub_job_id'], ['name' => $a['original_name']]);
    }
}
