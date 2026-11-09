-- TUV East 3rd-party app: database changes for the live site (MySQL 5.7+ / MariaDB)
-- Database: tuvsa1bx_party.  TAKE A BACKUP (phpMyAdmin > Export) BEFORE RUNNING.
-- Safe to run more than once: every step checks first and skips what already exists.

-- ============ 1. Structure ============
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sub_jobs' AND COLUMN_NAME = 'kind') = 0, 'ALTER TABLE sub_jobs ADD kind ENUM(''inspection'',''operator'') NOT NULL DEFAULT ''inspection'' AFTER seq', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sub_jobs' AND COLUMN_NAME = 'operator_data') = 0, 'ALTER TABLE sub_jobs ADD operator_data TEXT NULL AFTER kind', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inspector_cards' AND COLUMN_NAME = 'sub_job_id') = 0, 'ALTER TABLE inspector_cards ADD sub_job_id INT UNSIGNED NULL AFTER client_id', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ============ 2. Speed-up indexes (50,000+ jobs) ============
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_status_id') = 0, 'ALTER TABLE `jobs` ADD INDEX `idx_jobs_status_id` (status, id)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_client_id') = 0, 'ALTER TABLE `jobs` ADD INDEX `idx_jobs_client_id` (client_id, id)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_lead_status') = 0, 'ALTER TABLE `jobs` ADD INDEX `idx_jobs_lead_status` (lead_inspector_id, status)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_sched') = 0, 'ALTER TABLE `jobs` ADD INDEX `idx_jobs_sched` (scheduled_date)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_job_no') = 0, 'ALTER TABLE `jobs` ADD INDEX `idx_jobs_job_no` (job_no)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sub_jobs' AND INDEX_NAME = 'idx_sj_job_status') = 0, 'ALTER TABLE `sub_jobs` ADD INDEX `idx_sj_job_status` (job_id, status)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sub_jobs' AND INDEX_NAME = 'idx_sj_status_job') = 0, 'ALTER TABLE `sub_jobs` ADD INDEX `idx_sj_status_job` (status, job_id)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sub_jobs' AND INDEX_NAME = 'idx_sj_status_submitted') = 0, 'ALTER TABLE `sub_jobs` ADD INDEX `idx_sj_status_submitted` (status, submitted_at)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sub_jobs' AND INDEX_NAME = 'idx_sj_status_approved') = 0, 'ALTER TABLE `sub_jobs` ADD INDEX `idx_sj_status_approved` (status, approved_at)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sub_jobs' AND INDEX_NAME = 'idx_sj_type_status') = 0, 'ALTER TABLE `sub_jobs` ADD INDEX `idx_sj_type_status` (inspection_type_id, status)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'certificates' AND INDEX_NAME = 'idx_cert_no') = 0, 'ALTER TABLE `certificates` ADD INDEX `idx_cert_no` (certificate_no)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'certificates' AND INDEX_NAME = 'idx_cert_token') = 0, 'ALTER TABLE `certificates` ADD INDEX `idx_cert_token` (verify_token)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'certificates' AND INDEX_NAME = 'idx_cert_sj') = 0, 'ALTER TABLE `certificates` ADD INDEX `idx_cert_sj` (sub_job_id)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stickers' AND INDEX_NAME = 'idx_st_sj_status') = 0, 'ALTER TABLE `stickers` ADD INDEX `idx_st_sj_status` (sub_job_id, status)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attachments' AND INDEX_NAME = 'idx_att_sj_kind') = 0, 'ALTER TABLE `attachments` ADD INDEX `idx_att_sj_kind` (sub_job_id, kind)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_entity') = 0, 'ALTER TABLE `audit_logs` ADD INDEX `idx_audit_entity` (entity_type, entity_id)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notif_user') = 0, 'ALTER TABLE `notifications` ADD INDEX `idx_notif_user` (user_id, id)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ============ 3. Data ============
START TRANSACTION;

-- 3a. Operator Assessment type
INSERT INTO inspection_types (`code`, `name`, `category`, `procedure_ref`, `standard_ref`, `validity_months`, `requires_checklist`, `requires_certificate`, `requires_sticker`, `status`)
SELECT 'OPA', 'Operator Assessment', 'Operator Assessment', NULL, NULL, '12', '0', '0', '0', 'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM inspection_types WHERE code = 'OPA');

-- 3b. Exact checklist names
UPDATE inspection_types SET name = 'Boom Truck' WHERE code = 'BOOM' AND name = 'Boom Truck Inspection';
UPDATE inspection_types SET name = 'Forklift' WHERE code = 'FORK' AND name = 'Forklift Inspection';
UPDATE inspection_types SET name = 'Generator' WHERE code = 'GEN' AND name = 'Generator Inspection';
UPDATE inspection_types SET name = 'Mobile Crane' WHERE code = 'MCR' AND name = 'Mobile Crane Inspection';
UPDATE inspection_types SET name = 'Tower Crane' WHERE code = 'TCR' AND name = 'Tower Crane Inspection';
UPDATE inspection_types SET name = 'Elevator' WHERE code = 'ELEV' AND name = 'Elevator Examination';
UPDATE inspection_types SET name = 'Drilling Machine Rig' WHERE code = 'DRIL' AND name = 'Drilling Rig Machine Inspection';
UPDATE inspection_types SET name = 'Telehandler' WHERE code = 'TELE' AND name = 'Telehandler Inspection';
UPDATE inspection_types SET name = 'Motorcycle' WHERE code = 'MOTO' AND name = 'Motorcycle Inspection';
UPDATE inspection_types SET name = 'Trailer & Semi-Trailer' WHERE code = 'TRLR' AND name = 'Trailer & Semi-Trailer Inspection';
UPDATE inspection_types SET name = 'Petroleum Products Transportation Tanks' WHERE code = 'PTNK' AND name = 'Petroleum Products Transportation Tank Inspection';
UPDATE inspection_types SET name = 'Air Compressor' WHERE code = 'AIRC' AND name = 'Air Compressor Inspection';
UPDATE inspection_types SET name = 'Crawler Crane' WHERE code = 'CRWL' AND name = 'Crawler Crane Inspection';
UPDATE inspection_types SET name = 'Sulphur Trailers Equipment' WHERE code = 'SLTR' AND name = 'Sulphur Trailer Inspection';
UPDATE inspection_types SET name = 'Roller Compactor' WHERE code = 'ROLL' AND name = 'Roller Compactor Inspection';
UPDATE inspection_types SET name = 'Wheel Loader' WHERE code = 'WLDR' AND name = 'Wheel Loader Inspection';
UPDATE inspection_types SET name = 'Skid Steer Loader' WHERE code = 'SKID' AND name = 'Skid Steer Loader Inspection';
UPDATE inspection_types SET name = 'Vacuum Lifting Devices' WHERE code = 'VLD' AND name = 'Vacuum Lifting Devices Inspection';
UPDATE inspection_types SET name = 'Excavator' WHERE code = 'EXCV' AND name = 'Excavator Inspection';
UPDATE inspection_types SET name = 'Jib Crane' WHERE code = 'JIBC' AND name = 'Jib Crane Inspection';
UPDATE inspection_types SET name = 'Backhoe Loader' WHERE code = 'BKHO' AND name = 'Backhoe Loader Inspection';
UPDATE inspection_types SET name = 'Bulldozer' WHERE code = 'BLDZ' AND name = 'Bulldozer Inspection';

-- 3c. Petroleum Tanks (Tanker checklist): type, template, 20 items
INSERT INTO inspection_types (`code`, `name`, `category`, `procedure_ref`, `standard_ref`, `validity_months`, `requires_checklist`, `requires_certificate`, `requires_sticker`, `status`)
SELECT 'TNKR', 'Petroleum Tanks', 'Vehicles', 'N/A', 'N/A', '12', '1', '0', '0', 'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM inspection_types WHERE code = 'TNKR');
INSERT INTO checklist_templates (`inspection_type_id`, `code`, `title`, `doc_ref`, `revision`, `revision_date`, `layout`, `version`, `is_active`)
SELECT t.id, 'F-7.5.1-CL 23', 'TANKER CHECKLIST', 'F-7.5.1-CL 23', '00', '', '{\"left\":15,\"cols\":[47.5,50.3,31,26,26.2,5],\"row_h\":6.3,\"font\":9.5,\"title_font\":11,\"desc_label\":\"DESCRIPTION\",\"top\":27,\"top_next\":27,\"sig_gap\":5,\"head_h\":14,\"line\":1.3,\"title_h\":7.5,\"bottom\":273,\"header\":\"tank\",\"hdr_row_h\":7,\"std_label\":\"Ref. Standard\",\"std_text\":\"\",\"rem_label\":\"REMARKS\",\"band_align\":\"left\",\"remarks_box\":0,\"sig_review\":false,\"item_bold\":true,\"kind\":\"checklist\",\"report\":[]}', '1', '1'
FROM inspection_types t WHERE t.code = 'TNKR' AND NOT EXISTS (SELECT 1 FROM checklist_templates c WHERE c.inspection_type_id = t.id);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Previous Inspection Certificate.', '1', '1'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 1);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Valid Vehicle Registration Plate.', '1', '2'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 2);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Chassis Frame Visual Defects.', '1', '3'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 3);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Check that tread is evenly worn and look for cuts or other damage to tread or sidewalls. Check for minimum tread depth (minimum tread depth across the full width is 2/32 inch (2 mm).', '1', '4'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 4);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Tire nuts are present and tighten.', '1', '5'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 5);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Valve caps and stems are not missing, broken, or damaged.', '1', '6'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 6);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Tire rims are in good condition and without dents or warping.', '1', '7'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 7);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Axles are in good condition, and no leaks are present & the axel bearing must be greased.', '1', '8'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 8);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Check rubber dust covers for cracks and damages.', '1', '9'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 9);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'King ping is free from repair welds.', '1', '10'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 10);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Pressure vessels shall be equipped with a suitable contents gauge. All materials in direct contact with LPG, except for seals, shall be metallic.', '1', '11'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 11);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'The road tanker shall have three mutually independent shut-off devices for each bottom filling or discharge opening.', '1', '12'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 12);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Tanker is in good condition, free of damage and secured adequately.', '1', '13'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 13);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Fill cap is free of damage and closes the fuel tank properly.', '1', '14'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 14);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'No fabricated auxiliary fuel tanker. It is in good condition and equipped with a pressure relief device. The valve in fuel supply line from the auxiliary tank is in closed position.', '1', '15'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 15);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Hazard-Identification Number (Primary Hazard) and the Dangerous Substances.', '1', '16'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 16);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Hazard-Identification Number (Secondary Hazard) and the Hazard.', '1', '17'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 17);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Tank Interior (corrosion, pitting, wear etc).', '1', '18'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 18);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Safety instructions, Marking & labelling.', '1', '19'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 19);
INSERT INTO checklist_template_items (`template_id`, `section`, `label`, `is_mandatory`, `sort_order`)
SELECT c.id, 'Checks / Requirements', 'Fill Opening & Manholes.', '1', '20'
FROM checklist_templates c JOIN inspection_types t ON t.id = c.inspection_type_id WHERE t.code = 'TNKR' AND c.is_active = 1 AND NOT EXISTS (SELECT 1 FROM checklist_template_items i WHERE i.template_id = c.id AND i.sort_order = 20);
UPDATE inspection_types SET name = 'Petroleum Tanks' WHERE code = 'TNKR' AND name = 'Tanker Inspection';

-- 3d. Remove the Crane Health Check checklist: deleted when no job ever used it, otherwise just hidden
DELETE i FROM checklist_template_items i JOIN checklist_templates t ON t.id = i.template_id JOIN inspection_types y ON y.id = t.inspection_type_id WHERE y.code = 'CRHC' AND NOT EXISTS (SELECT 1 FROM sub_jobs s WHERE s.inspection_type_id = y.id);
DELETE t FROM checklist_templates t JOIN inspection_types y ON y.id = t.inspection_type_id WHERE y.code = 'CRHC' AND NOT EXISTS (SELECT 1 FROM sub_jobs s WHERE s.inspection_type_id = y.id);
DELETE t FROM certificate_templates t JOIN inspection_types y ON y.id = t.inspection_type_id WHERE y.code = 'CRHC' AND NOT EXISTS (SELECT 1 FROM sub_jobs s WHERE s.inspection_type_id = y.id);
DELETE y FROM inspection_types y WHERE y.code = 'CRHC' AND NOT EXISTS (SELECT 1 FROM sub_jobs s WHERE s.inspection_type_id = y.id);
UPDATE checklist_templates t JOIN inspection_types y ON y.id = t.inspection_type_id SET t.is_active = 0 WHERE y.code = 'CRHC';
UPDATE inspection_types SET status = 'inactive' WHERE code = 'CRHC';

COMMIT;

-- Afterwards: Users & Inspectors > Qualified to inspect > grant "Petroleum Tanks" to the inspectors who need it.
