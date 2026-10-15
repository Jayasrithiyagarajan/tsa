<?php

/**
 * Single source of truth for statuses, labels and allowed transitions.
 * Every status change in the services goes through Workflow::assert().
 */
final class Workflow
{
    public const TRANSITIONS = [
        'request' => [
            'new'          => ['under_review', 'quoted', 'converted', 'rejected', 'cancelled'],
            'under_review' => ['quoted', 'converted', 'rejected', 'cancelled'],
            'quoted'       => ['converted', 'rejected', 'cancelled'],
            'converted'    => [],
            'rejected'     => ['under_review'],
            'cancelled'    => [],
        ],
        'sub_job' => [
            'pending'     => ['assigned', 'cancelled'],
            'assigned'    => ['pending', 'assigned', 'in_progress', 'cancelled'],
            'in_progress' => ['submitted', 'assigned', 'cancelled'],
            'submitted'   => ['approved', 'rework'],
            'rework'      => ['in_progress', 'submitted', 'assigned', 'cancelled'],
            'approved'    => ['rework'],           // only via certificate revocation
            'cancelled'   => [],
        ],
        'certificate' => [
            'draft'      => ['pending_qc'],
            'pending_qc' => ['issued', 'draft'],
            'issued'     => ['revoked'],
            'revoked'    => ['draft'],
            'rejected'   => ['draft'],
        ],
        'sticker' => [
            'in_stock'  => ['allocated', 'closed', 'void'],
            'allocated' => ['allocated', 'in_stock', 'closed', 'void'],   // re-allocate / return to office / link to a job
            'closed'    => ['void'],                                     // linked to a job: locked, only an admin can void it
            'void'      => [],
        ],
    ];

    private const LABELS = [
        'new' => 'New', 'under_review' => 'Under Review', 'quoted' => 'Quoted', 'converted' => 'Converted to Job',
        'rejected' => 'Rejected', 'cancelled' => 'Cancelled',
        'pending_assignment' => 'Awaiting Assignment', 'assigned' => 'Assigned', 'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'pending' => 'Unassigned', 'submitted' => 'Awaiting QC', 'rework' => 'Rework Required', 'approved' => 'Approved',
        'draft' => 'Draft', 'pending_qc' => 'Pending QC', 'issued' => 'Issued', 'revoked' => 'Revoked',
        'in_stock' => 'In Stock', 'allocated' => 'Allocated', 'closed' => 'Closed', 'reserved' => 'Reserved', 'void' => 'Void',
        'accepted' => 'Accepted', 'repair' => 'Repair',
        'pass' => 'Pass', 'fail' => 'Fail', 'na' => 'N/A',
        'active' => 'Active', 'inactive' => 'Inactive', 'out_of_service' => 'Out of Service', 'retired' => 'Retired',
        'low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent',
        'website' => 'Website', 'email' => 'Email', 'phone' => 'Phone', 'portal' => 'Customer Portal',
        'superadmin' => 'Super Admin', 'admin' => 'Administrator', 'inspector' => 'Inspector', 'qc' => 'QC Reviewer', 'customer' => 'Customer',
        'awaiting_approval' => 'Awaiting Final Approval',
    ];

    private const TONES = [
        'new' => 'blue', 'under_review' => 'amber', 'quoted' => 'violet', 'converted' => 'green',
        'rejected' => 'red', 'cancelled' => 'gray',
        'pending_assignment' => 'amber', 'assigned' => 'blue', 'in_progress' => 'indigo', 'under_review_job' => 'violet',
        'completed' => 'green', 'pending' => 'amber', 'submitted' => 'violet', 'rework' => 'red', 'approved' => 'green',
        'draft' => 'gray', 'pending_qc' => 'violet', 'issued' => 'green', 'revoked' => 'red',
        'in_stock' => 'gray', 'allocated' => 'amber', 'closed' => 'blue', 'reserved' => 'amber', 'void' => 'red',
        'accepted' => 'green', 'repair' => 'amber', 'pass' => 'green', 'fail' => 'red', 'na' => 'gray',
        'active' => 'green', 'inactive' => 'gray', 'out_of_service' => 'red', 'retired' => 'gray',
        'low' => 'gray', 'normal' => 'blue', 'high' => 'amber', 'urgent' => 'red',
        'website' => 'blue', 'email' => 'violet', 'phone' => 'teal', 'portal' => 'indigo',
        'superadmin' => 'red', 'admin' => 'indigo', 'inspector' => 'teal', 'qc' => 'violet', 'customer' => 'blue',
        'awaiting_approval' => 'amber',
    ];

    public static function label(string $s): string
    {
        return self::LABELS[$s] ?? ucwords(str_replace('_', ' ', $s));
    }

    public static function tone(string $s): string
    {
        return self::TONES[$s] ?? 'gray';
    }

    public static function can(string $entity, string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$entity][$from] ?? [], true);
    }

    public static function assert(string $entity, string $from, string $to): void
    {
        if (!self::can($entity, $from, $to)) {
            throw new DomainException(sprintf(
                'A %s cannot move from "%s" to "%s".',
                str_replace('_', '-', $entity), self::label($from), self::label($to)
            ));
        }
    }

    public static function options(array $keys): array
    {
        $o = [];
        foreach ($keys as $k) $o[$k] = self::label($k);
        return $o;
    }
}

final class Numbering
{
    /** Atomic per-period counter. */
    public static function next(string $name, string $period): int
    {
        DB::run(
            'INSERT INTO number_sequences (name, period, last_value) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE last_value = LAST_INSERT_ID(last_value + 1)',
            [$name, $period]
        );
        $id = (int) DB::pdo()->lastInsertId();
        return $id > 0 ? $id : 1;
    }

    public static function request(): string
    {
        $y = date('Y');
        return sprintf('REQ-%s-%04d', $y, self::next('request', $y));
    }

    public static function job(): string
    {
        $y = date('Y');
        return sprintf('JOB-%s-%04d', $y, self::next('job', $y));
    }

    /**
     * Certificate number pattern from settings, e.g. "{YYYY}-{CLIENT}-{JOB}-{SEQ}" → 2026-22108-0012-001
     * Deterministic per sub-job so the number shown on the checklist never changes.
     */
    public static function certificate(array $subJob): string
    {
        $pattern = setting('certificate_pattern', '{YYYY}-{CLIENT}-{JOB}-{SEQ}');
        preg_match('/(\d+)$/', $subJob['job_no'], $m);
        return strtr($pattern, [
            '{YYYY}'   => substr($subJob['job_no'], 4, 4) ?: date('Y'),
            '{CLIENT}' => $subJob['client_code'],
            '{JOB}'    => $m[1] ?? $subJob['job_id'],
            '{SEQ}'    => str_pad((string) $subJob['seq'], 3, '0', STR_PAD_LEFT),
        ]);
    }
}
