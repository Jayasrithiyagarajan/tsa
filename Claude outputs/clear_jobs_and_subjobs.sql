-- ============================================================================
-- Clear ALL jobs and sub-jobs (and everything that hangs off them).
-- KEEPS: clients, users, inspectors, inspection types, checklist/certificate
--        templates, equipment register, service requests, settings, stickers.
--
-- !! PERMANENT. Export the `tsa` database first (phpMyAdmin > Export) !!
-- Run in phpMyAdmin (SQL tab) on the tsa database.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- 1) Stickers: give every sticker that was linked/issued on a job back to the
--    inspector's hand (status allocated). Void stickers stay void.
UPDATE stickers
   SET status = 'allocated', sub_job_id = NULL, equipment_id = NULL, certificate_id = NULL,
       linked_at = NULL, issued_at = NULL, expiry_date = NULL
 WHERE status = 'closed';
UPDATE stickers SET sub_job_id = NULL, certificate_id = NULL WHERE status <> 'closed';

-- 2) Operator / inspector cards that were issued for a sub-job
DELETE FROM inspector_cards WHERE sub_job_id IS NOT NULL;

-- 3) Children of sub-jobs
DELETE FROM checklist_responses;
DELETE FROM checklists;
DELETE FROM certificates;
DELETE FROM qc_reviews;
DELETE FROM attachments;
DELETE FROM job_assignments;

-- 4) Sub-jobs and jobs
DELETE FROM sub_jobs;
DELETE FROM jobs;

-- 5) Job-related notifications (optional – comment out to keep)
DELETE FROM notifications;

-- 6) Restart numbering (optional – uncomment to start JOB-/certificate numbers from 1 again)
-- DELETE FROM number_sequences;

-- 7) Service requests stay, but they no longer point at a deleted job
UPDATE service_requests SET converted_job_id = NULL WHERE converted_job_id IS NOT NULL;

-- 8) Audit history of the deleted records. Without this, a new job / sub-job / certificate that
--    gets the same ID later shows the old record's history, and KPI durations go negative.
DELETE FROM audit_logs WHERE entity_type IN ('job', 'sub_job', 'certificate', 'checklist', 'attachment', 'qc_review');

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- Check: all of these should now be 0
SELECT (SELECT COUNT(*) FROM jobs) jobs, (SELECT COUNT(*) FROM sub_jobs) sub_jobs,
       (SELECT COUNT(*) FROM certificates) certificates, (SELECT COUNT(*) FROM checklists) checklists,
       (SELECT COUNT(*) FROM attachments) attachments;

-- Uploaded photos/files stay on disk (storage/uploads). After the script you can delete that folder's
-- contents by hand if you do not need them.
