<?php

final class QcService
{
    /** submitted → approved. Issues the certificate, stamps the linked sticker with it, updates the equipment register. */
    public static function approve(int $subJobId, ?string $comments = null): void
    {
        DB::tx(function () use ($subJobId, $comments) {
            $sj = JobService::subJob($subJobId);
            if (!$sj) throw new DomainException('Sub-job not found.');
            Workflow::assert('sub_job', $sj['status'], 'approved');
            if ((int) $sj['inspector_id'] === Auth::id()) {
                throw new DomainException('Four-eyes rule: you cannot approve an inspection you performed.');
            }
            $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ? ORDER BY seq LIMIT 1', [$subJobId]);
            DB::update('sub_jobs', ['status' => 'approved', 'approved_at' => now(), 'result' => $cert['result'] ?? $sj['result']], 'id = ?', [$subJobId]);
            DB::insert('qc_reviews', ['sub_job_id' => $subJobId, 'reviewer_id' => Auth::id(), 'decision' => 'approved', 'comments' => nullable($comments)]);

            if ($cert) {
                // issue every page of a multi-page certificate, not just page 1
                DB::update('certificates', [
                    'status' => 'issued', 'reviewer_id' => Auth::id(), 'issue_date' => date('Y-m-d'),
                ], 'sub_job_id = ?', [$subJobId]);
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
            foreach (DB::all("SELECT id FROM stickers WHERE sub_job_id = ? AND status = 'closed'", [$subJobId]) as $st) {
                DB::update('stickers', [
                    'issued_at' => now(), 'certificate_id' => $cert['id'] ?? null,
                    'expiry_date' => $cert['next_inspection_date'] ?? null,
                ], 'id = ?', [$st['id']]);
                Audit::log('sticker.issued', 'sticker', (int) $st['id'], ['sub_job_id' => $subJobId]);
            }
            Audit::log('qc.approved', 'sub_job', $subJobId, ['comments' => $comments]);
            if ($sj['inspector_id']) {
                Notify::user((int) $sj['inspector_id'], "{$sj['sub_job_no']} approved by QC", $comments, 'inspector/subjobs/' . $subJobId);
            }
            // the client is notified when an admin gives the job its final approval (JobService::close)
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
            DB::update('stickers', ['status' => 'void', 'void_reason' => 'Certificate revoked: ' . $reason], "certificate_id = ? AND status = 'closed'", [$certId]);
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
    /**
     * Adds a printed batch to stock, optionally handing it straight to an inspector.
     * @return int[] ids of the stickers added (numbers already registered are skipped)
     */
    public static function addBatch(string $prefix, int $from, int $to, string $color, ?string $batch, ?int $inspectorId = null): array
    {
        if ($from < 1 || $to < $from || $to - $from >= 2000) throw new DomainException('Enter a valid range (max 2,000 stickers per batch).');
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $prefix));
        if ($prefix === '') throw new DomainException('Enter a sticker prefix, e.g. TE-.');
        $other = DB::value("SELECT color FROM stickers WHERE sticker_no LIKE ? AND sticker_no REGEXP ? LIMIT 1", [$prefix . '%', '^' . preg_quote($prefix) . '[0-9]+$']);
        if ($other && $other !== $color) throw new DomainException("Series $prefix is already used for " . ($other === 'red' ? 'red' : 'blue') . ' stickers. Use another prefix for this colour.');
        if ($inspectorId) self::inspector($inspectorId);
        $ids = [];
        DB::tx(function () use ($prefix, $from, $to, $color, $batch, &$ids) {
            for ($i = $from; $i <= $to; $i++) {
                $no = $prefix . str_pad((string) $i, 6, '0', STR_PAD_LEFT);
                if (DB::run('INSERT IGNORE INTO stickers (sticker_no, verify_token, batch_no, color) VALUES (?,?,?,?)', [$no, bin2hex(random_bytes(16)), nullable($batch), $color])->rowCount()) {
                    $ids[] = (int) DB::pdo()->lastInsertId();
                }
            }
        });
        $n = count($ids);
        Audit::log('sticker.batch_added', 'sticker', null, compact('prefix', 'from', 'to', 'color', 'batch', 'n'));
        if ($inspectorId && $ids) self::allocate($ids, $inspectorId);
        return $ids;
    }

    /** Ids of every sticker between two numbers of the same series, e.g. TE-001001 … TE-001050. */
    public static function range(string $fromNo, string $toNo): array
    {
        $a = strtoupper(trim($fromNo));
        $b = strtoupper(trim($toNo)) ?: $a;
        $prefix = preg_replace('/\d+$/', '', $a);
        if ($a === '' || $prefix !== preg_replace('/\d+$/', '', $b) || strlen($a) !== strlen($b)) {
            throw new DomainException('Both sticker numbers must be from the same series (same prefix and length).');
        }
        if (strcmp($a, $b) > 0) [$a, $b] = [$b, $a];
        $ids = array_map('intval', array_column(DB::all(
            'SELECT id FROM stickers WHERE sticker_no BETWEEN ? AND ? AND sticker_no LIKE ? AND LENGTH(sticker_no) = ? ORDER BY sticker_no',
            [$a, $b, $prefix . '%', strlen($a)]
        ), 'id'));
        if (!$ids) throw new DomainException("No stickers registered between $a and $b.");
        return $ids;
    }

    /**
     * One row per sticker series (prefix): colour, counts, the next number to generate (last generated + 1)
     * and the next sticker to hand out (lowest number still in office stock).
     */
    public static function series(): array
    {
        return DB::all("SELECT REGEXP_REPLACE(sticker_no, '[0-9]+$', '') prefix, MAX(color) color, COUNT(*) total,
                               SUM(status = 'in_stock') in_stock,
                               MAX(CAST(REGEXP_SUBSTR(sticker_no, '[0-9]+$') AS UNSIGNED)) + 1 next_generate,
                               MIN(CASE WHEN status = 'in_stock' THEN sticker_no END) next_free
                        FROM stickers GROUP BY prefix ORDER BY MAX(color) <> 'red' DESC, prefix");
    }

    /** Ids of the next $qty stickers still in office stock, starting at $fromNo within its series. */
    public static function next(string $fromNo, int $qty): array
    {
        $from = strtoupper(trim($fromNo));
        if ($from === '' || $qty < 1 || $qty > 2000) throw new DomainException('Enter the first sticker number and a quantity between 1 and 2,000.');
        $prefix = preg_replace('/\d+$/', '', $from);
        $ids = array_map('intval', array_column(DB::all(
            "SELECT id FROM stickers WHERE status = 'in_stock' AND sticker_no >= ? AND sticker_no LIKE ? AND LENGTH(sticker_no) = ? ORDER BY sticker_no LIMIT " . (int) $qty,
            [$from, $prefix . '%', strlen($from)]
        ), 'id'));
        if (!$ids) throw new DomainException("No stickers in office stock from $from onwards.");
        if (count($ids) < $qty) throw new DomainException('Only ' . count($ids) . " sticker(s) are in office stock from $from onwards. Generate more or lower the quantity.");
        return $ids;
    }

    /**
     * Hands stickers to an inspector (in stock → allocated, or moves them to another inspector).
     * Closed and void stickers are skipped. @return array{0:int,1:int} [allocated, skipped]
     */
    public static function allocate(array $ids, int $inspectorId): array
    {
        $insp = self::inspector($inspectorId);
        $ids = self::ids($ids);
        $done = [];
        DB::tx(function () use ($ids, $inspectorId, &$done) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            foreach (DB::all("SELECT id, sticker_no FROM stickers WHERE id IN ($in) AND status IN ('in_stock','allocated') ORDER BY sticker_no FOR UPDATE", $ids) as $s) {
                DB::update('stickers', ['status' => 'allocated', 'inspector_id' => $inspectorId, 'allocated_at' => now(), 'allocated_by' => Auth::id()], 'id = ?', [$s['id']]);
                $done[] = $s['sticker_no'];
            }
        });
        if ($done) {
            $span = $done[0] . (count($done) > 1 ? ' … ' . end($done) : '');
            Audit::log('sticker.allocated', 'user', $inspectorId, ['inspector' => $insp['name'], 'count' => count($done), 'stickers' => $span]);
            Notify::user($inspectorId, count($done) . ' sticker(s) allocated to you', $span, 'inspector/stickers');
        }
        return [count($done), count($ids) - count($done)];
    }

    /** Unused stickers taken back from inspectors into office stock. @return int stickers returned */
    public static function returnToStock(array $ids): int
    {
        $ids = self::ids($ids);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $n = DB::run("UPDATE stickers SET status = 'in_stock', inspector_id = NULL, allocated_at = NULL, allocated_by = NULL WHERE id IN ($in) AND status = 'allocated'", $ids)->rowCount();
        if ($n) Audit::log('sticker.returned', 'sticker', null, ['count' => $n]);
        return $n;
    }

    public static function void(int $id, string $reason): void
    {
        $s = DB::one('SELECT * FROM stickers WHERE id = ?', [$id]);
        if (!$s) throw new DomainException('Sticker not found.');
        Workflow::assert('sticker', $s['status'], 'void');
        if (trim($reason) === '') throw new DomainException('A reason is required.');
        DB::update('stickers', ['status' => 'void', 'void_reason' => $reason], 'id = ?', [$id]);
        Audit::log('sticker.voided', 'sticker', $id, ['reason' => $reason, 'sub_job_id' => $s['sub_job_id']]);
    }

    /** Blue stickers (stock colour "green") are for Accepted results, red ones for everything else. */
    public static function colourMismatch(string $colour, ?string $result): ?string
    {
        if (!$result) return null;
        if ($result === 'accepted' && $colour === 'red') return 'the result is Accepted, so use a blue sticker, not a red one.';
        if ($result !== 'accepted' && $colour !== 'red') return 'the result is ' . Workflow::label($result) . ', so use a red (Failed) sticker, not a blue one.';
        return null;
    }

    private static function ids(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) throw new DomainException('Select at least one sticker.');
        if (count($ids) > 2000) throw new DomainException('Work with at most 2,000 stickers at a time.');
        return $ids;
    }

    private static function inspector(int $id): array
    {
        $u = DB::one("SELECT id, name FROM users WHERE id = ? AND role = 'inspector' AND status = 'active'", [$id]);
        if (!$u) throw new DomainException('Choose an active inspector.');
        return $u;
    }
}
