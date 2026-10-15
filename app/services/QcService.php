<?php

final class QcService
{
    /** submitted → approved. Issues the certificate, issues the reserved sticker, updates the equipment register. */
    public static function approve(int $subJobId, ?string $comments = null): void
    {
        DB::tx(function () use ($subJobId, $comments) {
            $sj = JobService::subJob($subJobId);
            if (!$sj) throw new DomainException('Sub-job not found.');
            Workflow::assert('sub_job', $sj['status'], 'approved');
            if ((int) $sj['inspector_id'] === Auth::id()) {
                throw new DomainException('Four-eyes rule: you cannot approve an inspection you performed.');
            }
            $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ?', [$subJobId]);
            DB::update('sub_jobs', ['status' => 'approved', 'approved_at' => now(), 'result' => $cert['result'] ?? $sj['result']], 'id = ?', [$subJobId]);
            DB::insert('qc_reviews', ['sub_job_id' => $subJobId, 'reviewer_id' => Auth::id(), 'decision' => 'approved', 'comments' => nullable($comments)]);

            if ($cert) {
                DB::update('certificates', [
                    'status' => 'issued', 'reviewer_id' => Auth::id(), 'issue_date' => date('Y-m-d'),
                ], 'id = ?', [$cert['id']]);
                Audit::log('certificate.issued', 'certificate', (int) $cert['id'], ['certificate_no' => $cert['certificate_no']]);
                if ($sj['equipment_id'] && $cert['result'] !== 'rejected') {
                    DB::update('equipment', [
                        'last_inspection_date' => $cert['inspection_date'],
                        'next_inspection_date' => $cert['next_inspection_date'],
                        'status' => 'active',
                    ], 'id = ?', [$sj['equipment_id']]);
                } elseif ($sj['equipment_id']) {
                    DB::update('equipment', ['last_inspection_date' => $cert['inspection_date'], 'status' => 'out_of_service'], 'id = ?', [$sj['equipment_id']]);
                }
            }
            foreach (DB::all("SELECT id FROM stickers WHERE sub_job_id = ? AND status = 'reserved'", [$subJobId]) as $st) {
                DB::update('stickers', [
                    'status' => 'issued', 'issued_at' => now(), 'certificate_id' => $cert['id'] ?? null,
                    'expiry_date' => $cert['next_inspection_date'] ?? null,
                ], 'id = ?', [$st['id']]);
                Audit::log('sticker.issued', 'sticker', (int) $st['id'], ['sub_job_id' => $subJobId]);
            }
            Audit::log('qc.approved', 'sub_job', $subJobId, ['comments' => $comments]);
            if ($sj['inspector_id']) {
                Notify::user((int) $sj['inspector_id'], "{$sj['sub_job_no']} approved by QC", $comments, 'inspector/subjobs/' . $subJobId);
            }
            if ($cert) {
                Notify::client((int) $sj['client_id'], "Certificate {$cert['certificate_no']} issued", "{$sj['type_name']} · {$sj['eq_description']}", 'portal/certificates');
            }
            JobService::recompute((int) $sj['job_id']);
        });
    }

    /** submitted → rework with mandatory comments; documents go back to draft for the inspector. */
    public static function rework(int $subJobId, string $comments): void
    {
        if (trim($comments) === '') throw new DomainException('Explain what must be corrected.');
        DB::tx(function () use ($subJobId, $comments) {
            $sj = JobService::subJob($subJobId);
            if (!$sj) throw new DomainException('Sub-job not found.');
            Workflow::assert('sub_job', $sj['status'], 'rework');
            DB::update('sub_jobs', ['status' => 'rework'], 'id = ?', [$subJobId]);
            DB::update('certificates', ['status' => 'draft'], 'sub_job_id = ?', [$subJobId]);
            DB::update('checklists', ['status' => 'draft', 'completed_at' => null], 'sub_job_id = ?', [$subJobId]);
            DB::insert('qc_reviews', ['sub_job_id' => $subJobId, 'reviewer_id' => Auth::id(), 'decision' => 'rework', 'comments' => $comments]);
            Audit::log('qc.rework', 'sub_job', $subJobId, ['comments' => $comments]);
            if ($sj['inspector_id']) {
                Notify::user((int) $sj['inspector_id'], "{$sj['sub_job_no']} returned for rework", mb_substr($comments, 0, 250), 'inspector/subjobs/' . $subJobId);
            }
            JobService::recompute((int) $sj['job_id']);
        });
    }
}

final class CertificateService
{
    public static function revoke(int $certId, string $reason): void
    {
        if (trim($reason) === '') throw new DomainException('A reason is required to revoke a certificate.');
        DB::tx(function () use ($certId, $reason) {
            $c = DB::one('SELECT * FROM certificates WHERE id = ?', [$certId]);
            if (!$c) throw new DomainException('Certificate not found.');
            Workflow::assert('certificate', $c['status'], 'revoked');
            DB::update('certificates', ['status' => 'revoked', 'revoked_reason' => $reason], 'id = ?', [$certId]);
            DB::update('stickers', ['status' => 'void', 'void_reason' => 'Certificate revoked: ' . $reason], "certificate_id = ? AND status = 'issued'", [$certId]);
            DB::update('sub_jobs', ['status' => 'rework'], 'id = ?', [$c['sub_job_id']]);
            Audit::log('certificate.revoked', 'certificate', $certId, ['reason' => $reason, 'certificate_no' => $c['certificate_no']]);
            $sj = JobService::subJob((int) $c['sub_job_id']);
            if ($sj['inspector_id']) {
                Notify::user((int) $sj['inspector_id'], "Certificate {$c['certificate_no']} revoked", $reason, 'inspector/subjobs/' . $sj['id']);
            }
            JobService::recompute((int) $sj['job_id']);
        });
    }

    /** Resolve a template field's value from bound system data or the stored manual value. */
    public static function fieldValue(array $f, array $sj, array $cert, array $data): string
    {
        $src = $f['source'] ?? '';
        $map = [
            'client.name'               => $sj['client_name'],
            'job.location'              => $sj['location'],
            'job.po_no'                 => $sj['po_no'] ?: 'N/A',
            'type.procedure_ref'        => $sj['procedure_ref'],
            'type.standard_ref'         => $sj['standard_ref'],
            'cert.certificate_no'       => $cert['certificate_no'] ?? '',
            'cert.issue_date'           => fmt_date($cert['issue_date'] ?? null),
            'cert.inspection_date'      => fmt_date($cert['inspection_date'] ?? null),
            'cert.next_inspection_date' => fmt_date($cert['next_inspection_date'] ?? null),
            'equipment.description'     => $sj['eq_description'],
            'equipment.equipment_no'    => $sj['equipment_no'],
            'equipment.serial_no'       => $sj['eq_serial_no'],
            'equipment.manufacturer'    => $sj['manufacturer'],
            'equipment.model'           => $sj['eq_model'],
            'equipment.capacity'        => $sj['eq_capacity'],
            'equipment.year_built'      => $sj['eq_year'],
        ];
        if ($src !== '') return (string) ($map[$src] ?? '');
        $v = (string) ($data[$f['key']] ?? '');
        return ($f['type'] ?? '') === 'date' ? fmt_date($v) : $v;
    }

    public const SOURCES = [
        ''                          => 'Entered by inspector',
        'client.name'               => 'Client name',
        'job.location'              => 'Job location',
        'job.po_no'                 => 'Request / P.O. No.',
        'type.procedure_ref'        => 'Inspection type · procedure',
        'type.standard_ref'         => 'Inspection type · standard',
        'cert.certificate_no'       => 'Certificate No.',
        'cert.issue_date'           => 'Certificate date (on QC issue)',
        'cert.inspection_date'      => 'Inspection date',
        'cert.next_inspection_date' => 'Next inspection date',
        'equipment.description'     => 'Equipment description',
        'equipment.equipment_no'    => 'Equipment ID / S No.',
        'equipment.serial_no'       => 'Equipment serial no.',
        'equipment.manufacturer'    => 'Equipment manufacturer',
        'equipment.model'           => 'Equipment model',
        'equipment.capacity'        => 'Equipment capacity / SWL',
        'equipment.year_built'      => 'Equipment year',
    ];
}

final class StickerService
{
    public static function addBatch(string $prefix, int $from, int $to, string $color, ?string $batch): int
    {
        if ($from < 1 || $to < $from || $to - $from > 2000) throw new DomainException('Enter a valid range (max 2,000 stickers per batch).');
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $prefix));
        $n = 0;
        DB::tx(function () use ($prefix, $from, $to, $color, $batch, &$n) {
            for ($i = $from; $i <= $to; $i++) {
                $no = $prefix . str_pad((string) $i, 6, '0', STR_PAD_LEFT);
                $n += DB::run('INSERT IGNORE INTO stickers (sticker_no, batch_no, color) VALUES (?,?,?)', [$no, nullable($batch), $color])->rowCount();
            }
        });
        Audit::log('sticker.batch_added', 'sticker', null, compact('prefix', 'from', 'to', 'color', 'batch', 'n'));
        return $n;
    }

    public static function void(int $id, string $reason): void
    {
        $s = DB::one('SELECT * FROM stickers WHERE id = ?', [$id]);
        if (!$s) throw new DomainException('Sticker not found.');
        Workflow::assert('sticker', $s['status'], 'void');
        if (trim($reason) === '') throw new DomainException('A reason is required.');
        DB::update('stickers', ['status' => 'void', 'void_reason' => $reason], 'id = ?', [$id]);
        Audit::log('sticker.voided', 'sticker', $id, ['reason' => $reason]);
    }
}
