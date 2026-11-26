-- ============================================================================
-- One-off clean-up: remove audit-log rows whose record no longer exists, or whose
-- record was deleted and its ID reused by a newer record (log row older than the record).
--
-- Why: after jobs / sub-jobs / requests were cleared, their audit rows stayed behind.
-- New records reuse IDs 1, 2, ... and showed the old history; the "Request response
-- time" KPI went negative. The app now ignores such rows, but they are still noise
-- in the Audit Log and exports.
--
-- !! PERMANENT. Back up the database first (phpMyAdmin > Export). !!
-- Step 1 only counts. Run step 2 after checking the counts.
-- ============================================================================

-- 1) PREVIEW: rows that would be deleted, per entity type
SELECT a.entity_type, COUNT(*) AS rows_to_delete
FROM audit_logs a
LEFT JOIN jobs             j  ON a.entity_type = 'job'             AND j.id  = a.entity_id
LEFT JOIN sub_jobs         s  ON a.entity_type = 'sub_job'         AND s.id  = a.entity_id
LEFT JOIN certificates     c  ON a.entity_type = 'certificate'     AND c.id  = a.entity_id
LEFT JOIN service_requests r  ON a.entity_type = 'service_request' AND r.id  = a.entity_id
WHERE a.entity_id IS NOT NULL AND (
       (a.entity_type = 'job'             AND (j.id IS NULL OR a.created_at < j.created_at))
    OR (a.entity_type = 'sub_job'         AND (s.id IS NULL OR a.created_at < s.created_at))
    OR (a.entity_type = 'certificate'     AND (c.id IS NULL OR a.created_at < c.created_at))
    OR (a.entity_type = 'service_request' AND (r.id IS NULL OR a.created_at < r.created_at))
)
GROUP BY a.entity_type;

-- 2) DELETE (uncomment to run)
-- START TRANSACTION;
-- DELETE a FROM audit_logs a
-- LEFT JOIN jobs             j  ON a.entity_type = 'job'             AND j.id  = a.entity_id
-- LEFT JOIN sub_jobs         s  ON a.entity_type = 'sub_job'         AND s.id  = a.entity_id
-- LEFT JOIN certificates     c  ON a.entity_type = 'certificate'     AND c.id  = a.entity_id
-- LEFT JOIN service_requests r  ON a.entity_type = 'service_request' AND r.id  = a.entity_id
-- WHERE a.entity_id IS NOT NULL AND (
--        (a.entity_type = 'job'             AND (j.id IS NULL OR a.created_at < j.created_at))
--     OR (a.entity_type = 'sub_job'         AND (s.id IS NULL OR a.created_at < s.created_at))
--     OR (a.entity_type = 'certificate'     AND (c.id IS NULL OR a.created_at < c.created_at))
--     OR (a.entity_type = 'service_request' AND (r.id IS NULL OR a.created_at < r.created_at))
-- );
-- COMMIT;
