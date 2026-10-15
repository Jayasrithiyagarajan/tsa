<?php

final class JobService
{
    /** Common SELECT used everywhere a sub-job is shown with its full context. */
    public const SUBJOB_SQL = "
        SELECT sj.*, j.job_no, j.client_id, j.location, j.po_no, j.title AS job_title, j.status AS job_status,
               j.scheduled_date AS job_scheduled_date, j.priority,
               c.name AS client_name, c.code AS client_code,
               t.name AS type_name, t.code AS type_code, t.category AS type_category, t.procedure_ref, t.standard_ref,
               t.validity_months, t.requires_checklist, t.requires_certificate, t.requires_sticker,
               e.description AS eq_description, e.equipment_no, e.serial_no AS eq_serial_no, e.manufacturer,
               e.model AS eq_model, e.capacity AS eq_capacity, e.year_built AS eq_year,
               u.name AS inspector_name
        FROM sub_jobs sj
        JOIN jobs j ON j.id = sj.job_id
        JOIN clients c ON c.id = j.client_id
        JOIN inspection_types t ON t.id = sj.inspection_type_id
        LEFT JOIN equipment e ON e.id = sj.equipment_id
        LEFT JOIN users u ON u.id = sj.inspector_id";

    public static function subJob(int $id): ?array
    {
        return DB::one(self::SUBJOB_SQL . ' WHERE sj.id = ?', [$id]);
    }

    public static function subJobsOf(int $jobId): array
    {
        return DB::all(self::SUBJOB_SQL . ' WHERE sj.job_id = ? ORDER BY sj.seq', [$jobId]);
    }

    public static function create(array $d, array $subJobs): int
    {
        return DB::tx(function () use ($d, $subJobs) {
            if (empty($d['client_id'])) throw new DomainException('Client is required.');
            if (trim($d['title'] ?? '') === '') throw new DomainException('Job title is required.');
            $id = DB::insert('jobs', [
                'job_no'         => Numbering::job(),
                'client_id'      => (int) $d['client_id'],
                'request_id'     => $d['request_id'] ?? null,
                'title'          => mb_substr($d['title'], 0, 190),
                'location'       => nullable($d['location'] ?? null),
                'po_no'          => nullable($d['po_no'] ?? null),
                'scheduled_date' => nullable($d['scheduled_date'] ?? null),
                'due_date'       => nullable($d['due_date'] ?? null),
                'priority'       => in_array($d['priority'] ?? '', ['low', 'normal', 'high', 'urgent'], true) ? $d['priority'] : 'normal',
                'notes'          => nullable($d['notes'] ?? null),
                'created_by'     => Auth::id(),
            ]);
            $jobNo = DB::value('SELECT job_no FROM jobs WHERE id = ?', [$id]);
            Audit::log('job.created', 'job', $id, ['job_no' => $jobNo]);
            foreach ($subJobs as $sj) {
                $qty = max(1, min(100, (int) ($sj['quantity'] ?? 1)));
                for ($i = 0; $i < $qty; $i++) {
                    self::addSubJob($id, $sj);
                }
            }
            if (!empty($d['inspector_id'])) {
                self::assignJob($id, (int) $d['inspector_id'], 'Assigned on job creation');
            }
            self::recompute($id);
            return $id;
        });
    }

    public static function addSubJob(int $jobId, array $sj): int
    {
        return DB::tx(function () use ($jobId, $sj) {
            $job = DB::one('SELECT * FROM jobs WHERE id = ? FOR UPDATE', [$jobId]);
            if (!$job) throw new DomainException('Job not found.');
            if (in_array($job['status'], ['completed', 'cancelled'], true)) {
                throw new DomainException('Cannot add sub-jobs to a ' . Workflow::label($job['status']) . ' job.');
            }
            $typeId = (int) ($sj['inspection_type_id'] ?? 0);
            if (!$typeId || !DB::value("SELECT id FROM inspection_types WHERE id = ? AND status = 'active'", [$typeId])) {
                throw new DomainException('Select a valid inspection type for every sub-job.');
            }
            $equipmentId = (int) ($sj['equipment_id'] ?? 0) ?: null;
            if ($equipmentId && (int) DB::value('SELECT client_id FROM equipment WHERE id = ?', [$equipmentId]) !== (int) $job['client_id']) {
                throw new DomainException('Selected equipment does not belong to this client.');
            }
            if (!$equipmentId && trim($sj['equipment_description'] ?? '') !== '') {
                $equipmentId = DB::insert('equipment', [
                    'client_id'          => $job['client_id'],
                    'inspection_type_id' => $typeId,
                    'description'        => mb_substr(trim($sj['equipment_description']), 0, 190),
                    'equipment_no'       => nullable($sj['equipment_no'] ?? null),
                    'serial_no'          => nullable($sj['serial_no'] ?? null),
                    'location'           => $job['location'],
                ]);
            }
            $seq = (int) DB::value('SELECT COALESCE(MAX(seq),0)+1 FROM sub_jobs WHERE job_id = ?', [$jobId]);
            $id = DB::insert('sub_jobs', [
                'job_id'             => $jobId,
                'seq'                => $seq,
                'sub_job_no'         => $job['job_no'] . '.' . $seq,
                'inspection_type_id' => $typeId,
                'equipment_id'       => $equipmentId,
                'scheduled_date'     => nullable($sj['scheduled_date'] ?? null) ?? $job['scheduled_date'],
            ]);
            Audit::log('subjob.created', 'sub_job', $id, ['job_id' => $jobId, 'seq' => $seq]);
            if (!empty($sj['inspector_id'])) {
                self::assign([$id], (int) $sj['inspector_id']);
            }
            self::recompute($jobId);
            return $id;
        });
    }

    /** Inspectors qualified for a given inspection type, with current open workload. */
    public static function inspectorsFor(?int $typeId = null): array
    {
        $sql = "SELECT u.id, u.name, u.email, p.qualification, p.base_location, p.cert_expiry,
                       (SELECT COUNT(*) FROM sub_jobs s WHERE s.inspector_id = u.id AND s.status IN ('assigned','in_progress','rework')) AS open_load,
                       GROUP_CONCAT(t.code ORDER BY t.code SEPARATOR ', ') AS skills
                FROM users u
                LEFT JOIN inspector_profiles p ON p.user_id = u.id
                LEFT JOIN inspector_skills k ON k.user_id = u.id
                LEFT JOIN inspection_types t ON t.id = k.inspection_type_id
                WHERE u.role = 'inspector' AND u.status = 'active'";
        $params = [];
        if ($typeId) {
            $sql .= ' AND EXISTS (SELECT 1 FROM inspector_skills k2 WHERE k2.user_id = u.id AND k2.inspection_type_id = ?)';
            $params[] = $typeId;
        }
        return DB::all($sql . ' GROUP BY u.id ORDER BY open_load, u.name', $params);
    }

    /**
     * Assign (or re-assign) one inspector to a set of sub-jobs.
     * Guards: inspector active + qualified for the sub-job's inspection type; sub-job not yet submitted/approved.
     */
    public static function assign(array $subJobIds, int $inspectorId, ?string $note = null): int
    {
        return DB::tx(function () use ($subJobIds, $inspectorId, $note) {
            $insp = DB::one("SELECT id, name FROM users WHERE id = ? AND role = 'inspector' AND status = 'active'", [$inspectorId]);
            if (!$insp) throw new DomainException('Select an active inspector.');
            $done = 0;
            $jobs = [];
            foreach (array_unique(array_map('intval', $subJobIds)) as $sid) {
                $sj = DB::one('SELECT sj.*, t.code type_code, j.job_no FROM sub_jobs sj JOIN inspection_types t ON t.id = sj.inspection_type_id JOIN jobs j ON j.id = sj.job_id WHERE sj.id = ? FOR UPDATE', [$sid]);
                if (!$sj) continue;
                if ((int) $sj['inspector_id'] === $inspectorId) continue;
                if (!DB::value('SELECT 1 FROM inspector_skills WHERE user_id = ? AND inspection_type_id = ?', [$inspectorId, $sj['inspection_type_id']])) {
                    throw new DomainException("{$insp['name']} is not qualified for {$sj['type_code']} ({$sj['sub_job_no']}). Update the inspector's qualifications first.");
                }
                $to = $sj['status'] === 'rework' ? 'rework' : 'assigned';
                if ($to === 'assigned') {
                    Workflow::assert('sub_job', $sj['status'], 'assigned');
                }
                DB::update('job_assignments', ['released_at' => now()], 'sub_job_id = ? AND released_at IS NULL', [$sid]);
                DB::update('sub_jobs', ['inspector_id' => $inspectorId, 'status' => $to], 'id = ?', [$sid]);
                DB::insert('job_assignments', [
                    'job_id' => $sj['job_id'], 'sub_job_id' => $sid, 'inspector_id' => $inspectorId,
                    'assigned_by' => Auth::id(), 'note' => nullable($note),
                ]);
                Audit::log('subjob.assigned', 'sub_job', $sid, ['inspector_id' => $inspectorId, 'previous' => $sj['inspector_id']]);
                $jobs[$sj['job_id']] = $sj['job_no'];
                $done++;
            }
            foreach ($jobs as $jobId => $jobNo) {
                if (!DB::value('SELECT lead_inspector_id FROM jobs WHERE id = ?', [$jobId])) {
                    DB::update('jobs', ['lead_inspector_id' => $inspectorId], 'id = ?', [$jobId]);
                }
                self::recompute($jobId);
                Notify::user($inspectorId, "New assignment on $jobNo", 'You have been assigned inspection work.', 'inspector/jobs/' . $jobId);
            }
            return $done;
        });
    }

    /** Assign every open sub-job in a job to one inspector (skips ones he is not qualified for). */
    public static function assignJob(int $jobId, int $inspectorId, ?string $note = null): array
    {
        $ok = [];
        $skipped = [];
        foreach (DB::all("SELECT sj.id, sj.sub_job_no, sj.inspection_type_id FROM sub_jobs sj WHERE sj.job_id = ? AND sj.status IN ('pending','assigned')", [$jobId]) as $sj) {
            if (DB::value('SELECT 1 FROM inspector_skills WHERE user_id = ? AND inspection_type_id = ?', [$inspectorId, $sj['inspection_type_id']])) {
                $ok[] = $sj['id'];
            } else {
                $skipped[] = $sj['sub_job_no'];
            }
        }
        if ($ok) self::assign($ok, $inspectorId, $note);
        DB::update('jobs', ['lead_inspector_id' => $inspectorId], 'id = ?', [$jobId]);
        return ['assigned' => count($ok), 'skipped' => $skipped];
    }

    public static function unassign(int $subJobId): void
    {
        $sj = DB::one('SELECT * FROM sub_jobs WHERE id = ?', [$subJobId]);
        if (!$sj) throw new DomainException('Sub-job not found.');
        Workflow::assert('sub_job', $sj['status'], 'pending');
        DB::update('job_assignments', ['released_at' => now()], 'sub_job_id = ? AND released_at IS NULL', [$subJobId]);
        DB::update('sub_jobs', ['inspector_id' => null, 'status' => 'pending'], 'id = ?', [$subJobId]);
        Audit::log('subjob.unassigned', 'sub_job', $subJobId, ['previous' => $sj['inspector_id']]);
        self::recompute((int) $sj['job_id']);
    }

    public static function cancelSubJob(int $subJobId, string $reason): void
    {
        $sj = DB::one('SELECT * FROM sub_jobs WHERE id = ?', [$subJobId]);
        if (!$sj) throw new DomainException('Sub-job not found.');
        Workflow::assert('sub_job', $sj['status'], 'cancelled');
        DB::tx(function () use ($sj, $subJobId, $reason) {
            DB::update('sub_jobs', ['status' => 'cancelled'], 'id = ?', [$subJobId]);
            // a linked sticker is already on the equipment, so it cannot go back to stock
            DB::update('stickers', ['status' => 'void', 'void_reason' => 'Sub-job cancelled: ' . $reason], "sub_job_id = ? AND status = 'closed'", [$subJobId]);
            Audit::log('subjob.cancelled', 'sub_job', $subJobId, ['reason' => $reason]);
            self::recompute((int) $sj['job_id']);
        });
    }

    public static function cancelJob(int $jobId, string $reason): void
    {
        DB::tx(function () use ($jobId, $reason) {
            if (DB::value("SELECT COUNT(*) FROM sub_jobs WHERE job_id = ? AND status IN ('submitted','approved')", [$jobId])) {
                throw new DomainException('Job has submitted or approved inspections and cannot be cancelled. Cancel the remaining sub-jobs individually.');
            }
            foreach (DB::all("SELECT id FROM sub_jobs WHERE job_id = ? AND status <> 'cancelled'", [$jobId]) as $s) {
                self::cancelSubJob((int) $s['id'], $reason);
            }
            DB::update('jobs', ['status' => 'cancelled'], 'id = ?', [$jobId]);
            Audit::log('job.cancelled', 'job', $jobId, ['reason' => $reason]);
        });
    }

    /** Job status is derived from its sub-jobs — never set by hand (except cancel). */
    public static function recompute(int $jobId): string
    {
        $job = DB::one('SELECT status FROM jobs WHERE id = ?', [$jobId]);
        if (!$job || $job['status'] === 'cancelled') return $job['status'] ?? '';
        $c = [];
        foreach (DB::all("SELECT status, COUNT(*) n FROM sub_jobs WHERE job_id = ? AND status <> 'cancelled' GROUP BY status", [$jobId]) as $r) {
            $c[$r['status']] = (int) $r['n'];
        }
        $total = array_sum($c);
        $n = fn ($k) => $c[$k] ?? 0;
        if ($total === 0 || $n('pending') === $total) {
            $s = 'pending_assignment';
        } elseif ($n('approved') === $total) {
            // every sub-job passed QC: the job waits for an admin's final approval (close()); a closed job stays closed
            $s = $job['status'] === 'completed' ? 'completed' : 'awaiting_approval';
        } elseif ($n('submitted') + $n('approved') === $total) {
            $s = 'under_review';
        } elseif ($n('in_progress') + $n('submitted') + $n('approved') + $n('rework') > 0) {
            $s = 'in_progress';
        } elseif ($n('pending') > 0) {
            $s = 'pending_assignment';
        } else {
            $s = 'assigned';
        }
        if ($s !== $job['status']) {
            DB::update('jobs', ['status' => $s] + ($s === 'completed' ? [] : ['completed_at' => null, 'closed_by' => null]), 'id = ?', [$jobId]);
            Audit::log('job.status', 'job', $jobId, ['from' => $job['status'], 'to' => $s]);
            if ($s === 'awaiting_approval') {
                $no = DB::value('SELECT job_no FROM jobs WHERE id = ?', [$jobId]);
                Notify::role('admin', "Job $no ready for final approval", 'All inspections passed QC review.', 'admin/jobs/' . $jobId);
            }
        }
        return $s;
    }

    /**
     * Final approval: an admin closes a job whose sub-jobs all passed QC. Only now are the certificates and
     * reports released to the client (portal, notifications, public verification).
     */
    public static function close(int $jobId, string $note = ''): void
    {
        $job = DB::one('SELECT * FROM jobs WHERE id = ?', [$jobId]);
        if (!$job) throw new DomainException('Job not found.');
        if ($job['status'] !== 'awaiting_approval') {
            throw new DomainException('Only a job whose inspections all passed QC review (Awaiting Final Approval) can be closed.');
        }
        DB::tx(function () use ($job, $note) {
            DB::update('jobs', ['status' => 'completed', 'completed_at' => now(), 'closed_by' => Auth::id()], 'id = ?', [$job['id']]);
            Audit::log('job.closed', 'job', (int) $job['id'], ['note' => trim($note)]);
            $certs = (int) DB::value("SELECT COUNT(*) FROM certificates c JOIN sub_jobs s ON s.id = c.sub_job_id WHERE s.job_id = ? AND c.status = 'issued'", [$job['id']]);
            Notify::client((int) $job['client_id'], "Job {$job['job_no']} completed",
                $certs ? "$certs certificate(s) are available for download." : 'All reports are available for download.', 'portal/jobs/' . $job['id']);
        });
    }

    /** Progress numbers for a job card. */
    public static function progress(int $jobId): array
    {
        $r = DB::one("SELECT COUNT(*) total, SUM(status='approved') approved, SUM(status='submitted') submitted,
                             SUM(status IN ('in_progress','rework')) active, SUM(status='pending') unassigned
                      FROM sub_jobs WHERE job_id = ? AND status <> 'cancelled'", [$jobId]);
        $total = (int) $r['total'];
        return [
            'total' => $total, 'approved' => (int) $r['approved'], 'submitted' => (int) $r['submitted'],
            'active' => (int) $r['active'], 'unassigned' => (int) $r['unassigned'],
            'pct' => $total ? (int) round(((int) $r['approved'] + 0.5 * (int) $r['submitted']) / $total * 100) : 0,
        ];
    }
}
