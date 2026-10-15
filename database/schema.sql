-- =====================================================================
--  TUV EAST · Inspection Management System (IMS)
--  Relational schema — MariaDB 10.4+ / MySQL 8+
--  Workflow: Client Request → Admin Assignment → Inspector Job/Sub-Job
--            → Checklist → QC Review → Certificate & Sticker
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs, notifications, settings, number_sequences, inspector_cards,
  attachments, stickers, qc_reviews, certificates, checklist_responses, checklists,
  job_assignments, sub_jobs, jobs, service_request_items, service_requests,
  equipment, certificate_templates, checklist_template_items, checklist_templates,
  inspector_skills, inspector_profiles, users, inspection_types, clients;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------- clients
CREATE TABLE clients (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code            VARCHAR(20)  NOT NULL UNIQUE,          -- used in certificate numbers
  name            VARCHAR(190) NOT NULL,
  contact_person  VARCHAR(120) NULL,
  email           VARCHAR(190) NULL,
  phone           VARCHAR(40)  NULL,
  address         VARCHAR(255) NULL,
  city            VARCHAR(80)  NULL,
  vat_no          VARCHAR(40)  NULL,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_clients_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- users (all 4 portals)
CREATE TABLE users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(120) NOT NULL,
  email           VARCHAR(190) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('superadmin','admin','inspector','qc','customer') NOT NULL,   -- superadmin: full control; admin: jobs + final approval, master data view-only
  phone           VARCHAR(40)  NULL,
  client_id       INT UNSIGNED NULL,                      -- customers only
  signature_path  VARCHAR(255) NULL,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login_at   DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_users_role (role),
  CONSTRAINT fk_users_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- inspection types (service catalogue)
CREATE TABLE inspection_types (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code            VARCHAR(20)  NOT NULL UNIQUE,
  name            VARCHAR(120) NOT NULL,
  category        VARCHAR(60)  NOT NULL,                 -- Lifting Equipment, NDT, Heavy Equipment …
  procedure_ref   VARCHAR(80)  NULL,
  standard_ref    VARCHAR(80)  NULL,
  validity_months TINYINT UNSIGNED NOT NULL DEFAULT 12,  -- drives next inspection date
  requires_checklist   TINYINT(1) NOT NULL DEFAULT 1,
  requires_certificate TINYINT(1) NOT NULL DEFAULT 1,
  requires_sticker     TINYINT(1) NOT NULL DEFAULT 1,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inspector_profiles (
  user_id         INT UNSIGNED PRIMARY KEY,
  employee_no     VARCHAR(30) NULL,
  qualification   VARCHAR(120) NULL,                     -- e.g. "NDT Level II", "LEEA Lifting Inspector"
  cert_no         VARCHAR(60) NULL,
  cert_expiry     DATE NULL,
  base_location   VARCHAR(80) NULL,
  photo_path      VARCHAR(255) NULL,                     -- ID photo, uploaded by the inspector or admin
  CONSTRAINT fk_ip_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- which inspection types an inspector is qualified for (assignment guard)
CREATE TABLE inspector_skills (
  user_id            INT UNSIGNED NOT NULL,
  inspection_type_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, inspection_type_id),
  CONSTRAINT fk_is_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_is_type FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- dynamic templates
-- Checklist template = header block (title, doc ref, rev) + ordered sections/items.
-- Templates are versioned: editing a template that is already in use creates a new version,
-- so historic checklists always render against the exact items they were filled with.
CREATE TABLE checklist_templates (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_type_id INT UNSIGNED NOT NULL,
  code               VARCHAR(40)  NOT NULL,                -- F-7.5.1-CL 01
  title              VARCHAR(190) NOT NULL,                -- BOOM TRUCK INSPECITON CHECKLIST
  doc_ref            VARCHAR(60)  NOT NULL,
  revision           VARCHAR(10)  NOT NULL DEFAULT '01',
  revision_date      VARCHAR(20)  NULL,                    -- printed as on the form, e.g. 09/Feb./23
  layout             TEXT NULL,                            -- JSON print layout: column widths, row height, fonts (TemplateService::CHECKLIST_LAYOUT)
  version            SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  is_active          TINYINT(1) NOT NULL DEFAULT 1,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ct_type (inspection_type_id, is_active),
  CONSTRAINT fk_ct_type FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE checklist_template_items (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id    INT UNSIGNED NOT NULL,
  section        VARCHAR(120) NOT NULL,                    -- "General Requirements"
  label          TEXT NOT NULL,                            -- may contain line breaks / bullet lists
  is_mandatory   TINYINT(1) NOT NULL DEFAULT 1,
  sort_order     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_cti_tpl (template_id, sort_order),
  CONSTRAINT fk_cti_tpl FOREIGN KEY (template_id) REFERENCES checklist_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certificate template = header data grid (label/value pairs, MPI layout) + result block
-- + standard notes + signature block. `fields` is a JSON array:
--   [{"key":"magnet_serial","label":"Magnet Serial No.","type":"text","required":true,
--     "source":"manual|client.name|job.location|..."}]
CREATE TABLE certificate_templates (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_type_id INT UNSIGNED NOT NULL,
  code               VARCHAR(40)  NOT NULL,
  title              VARCHAR(190) NOT NULL,
  doc_ref            VARCHAR(60)  NOT NULL,
  revision           VARCHAR(10)  NOT NULL DEFAULT '01',
  revision_date      VARCHAR(20)  NULL,
  fields             LONGTEXT NOT NULL,
  result_options     VARCHAR(190) NOT NULL DEFAULT 'accepted,rejected,repair',
  default_statement  VARCHAR(255) NULL,
  notes              TEXT NULL,                            -- legal / standard notes (one per line)
  inspector_label    VARCHAR(60) NOT NULL DEFAULT 'Inspector:',
  show_image_area    TINYINT(1) NOT NULL DEFAULT 1,
  version            SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  is_active          TINYINT(1) NOT NULL DEFAULT 1,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cert_tpl_type (inspection_type_id, is_active),
  CONSTRAINT fk_certt_type FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- equipment register
CREATE TABLE equipment (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id          INT UNSIGNED NOT NULL,
  inspection_type_id INT UNSIGNED NULL,
  description        VARCHAR(190) NOT NULL,               -- MAN BASKET
  equipment_no       VARCHAR(80)  NULL,                   -- Equipment ID / S No.  (MB-03)
  serial_no          VARCHAR(80)  NULL,
  manufacturer       VARCHAR(120) NULL,
  model              VARCHAR(120) NULL,
  capacity           VARCHAR(60)  NULL,                   -- SWL / WLL
  year_built         SMALLINT UNSIGNED NULL,
  location           VARCHAR(190) NULL,
  last_inspection_date DATE NULL,
  next_inspection_date DATE NULL,
  status             ENUM('active','out_of_service','retired') NOT NULL DEFAULT 'active',
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_eq_client (client_id),
  KEY idx_eq_due (next_inspection_date),
  CONSTRAINT fk_eq_client FOREIGN KEY (client_id) REFERENCES clients(id),
  CONSTRAINT fk_eq_type FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- 1. client requests
CREATE TABLE service_requests (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no      VARCHAR(30) NOT NULL UNIQUE,              -- REQ-2026-0001
  channel         ENUM('website','email','phone','portal') NOT NULL,
  client_id       INT UNSIGNED NULL,                        -- NULL until matched / registered
  company_name    VARCHAR(190) NOT NULL,
  contact_name    VARCHAR(120) NOT NULL,
  email           VARCHAR(190) NULL,
  phone           VARCHAR(40)  NULL,
  location        VARCHAR(190) NULL,
  po_no           VARCHAR(60)  NULL,
  preferred_date  DATE NULL,
  description     TEXT NULL,
  status          ENUM('new','under_review','quoted','converted','rejected','cancelled') NOT NULL DEFAULT 'new',
  status_note     VARCHAR(255) NULL,
  received_by     INT UNSIGNED NULL,                        -- admin who logged an email/phone request
  submitted_by    INT UNSIGNED NULL,                        -- customer user (portal channel)
  converted_job_id INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_sr_status (status),
  CONSTRAINT fk_sr_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
  CONSTRAINT fk_sr_recv FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_sr_sub FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_request_items (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id         INT UNSIGNED NOT NULL,
  inspection_type_id INT UNSIGNED NULL,
  equipment_description VARCHAR(190) NOT NULL,
  equipment_no       VARCHAR(80) NULL,
  quantity           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  notes              VARCHAR(255) NULL,
  CONSTRAINT fk_sri_req FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_sri_type FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- 2/3. jobs & sub-jobs
CREATE TABLE jobs (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_no          VARCHAR(30) NOT NULL UNIQUE,              -- JOB-2026-0001
  client_id       INT UNSIGNED NOT NULL,
  request_id      INT UNSIGNED NULL,
  title           VARCHAR(190) NOT NULL,
  location        VARCHAR(190) NULL,
  po_no           VARCHAR(60)  NULL,
  scheduled_date  DATE NULL,
  due_date        DATE NULL,
  priority        ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  lead_inspector_id INT UNSIGNED NULL,
  status          ENUM('pending_assignment','assigned','in_progress','under_review','awaiting_approval','completed','cancelled') NOT NULL DEFAULT 'pending_assignment',
                  -- awaiting_approval: every sub-job passed QC; an admin's final approval closes the job (completed) and releases the certificates
  notes           TEXT NULL,
  created_by      INT UNSIGNED NULL,
  completed_at    DATETIME NULL,
  closed_by       INT UNSIGNED NULL,                      -- admin who gave the final approval
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_jobs_status (status),
  KEY idx_jobs_client (client_id),
  CONSTRAINT fk_jobs_client FOREIGN KEY (client_id) REFERENCES clients(id),
  CONSTRAINT fk_jobs_req FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE SET NULL,
  CONSTRAINT fk_jobs_lead FOREIGN KEY (lead_inspector_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_jobs_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE service_requests
  ADD CONSTRAINT fk_sr_job FOREIGN KEY (converted_job_id) REFERENCES jobs(id) ON DELETE SET NULL;

-- One sub-job = one inspection of one piece of equipment of one inspection type.
CREATE TABLE sub_jobs (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id             INT UNSIGNED NOT NULL,
  seq                SMALLINT UNSIGNED NOT NULL,            -- 1,2,3 → displayed as 1.1, 1.2 …
  sub_job_no         VARCHAR(40) NOT NULL UNIQUE,           -- JOB-2026-0001-01
  inspection_type_id INT UNSIGNED NOT NULL,
  equipment_id       INT UNSIGNED NULL,
  inspector_id       INT UNSIGNED NULL,
  scheduled_date     DATE NULL,
  status             ENUM('pending','assigned','in_progress','submitted','rework','approved','cancelled') NOT NULL DEFAULT 'pending',
  result             ENUM('accepted','rejected','repair') NULL,
  started_at         DATETIME NULL,
  submitted_at       DATETIME NULL,
  approved_at        DATETIME NULL,
  override_by        INT UNSIGNED NULL,                     -- authorized completion override
  override_reason    VARCHAR(255) NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_subjob_seq (job_id, seq),
  KEY idx_sj_inspector (inspector_id, status),
  CONSTRAINT fk_sj_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_sj_type FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id),
  CONSTRAINT fk_sj_eq FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
  CONSTRAINT fk_sj_insp FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_sj_ovr FOREIGN KEY (override_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- full assignment history (who was assigned when, by whom)
CREATE TABLE job_assignments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id        INT UNSIGNED NOT NULL,
  sub_job_id    INT UNSIGNED NULL,
  inspector_id  INT UNSIGNED NOT NULL,
  assigned_by   INT UNSIGNED NULL,
  note          VARCHAR(255) NULL,
  assigned_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  released_at   DATETIME NULL,
  KEY idx_ja_insp (inspector_id),
  CONSTRAINT fk_ja_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_ja_sj FOREIGN KEY (sub_job_id) REFERENCES sub_jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_ja_insp FOREIGN KEY (inspector_id) REFERENCES users(id),
  CONSTRAINT fk_ja_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- 4/5. documents
CREATE TABLE checklists (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_job_id      INT UNSIGNED NOT NULL UNIQUE,
  template_id     INT UNSIGNED NOT NULL,
  serial_no       VARCHAR(80) NULL,
  inspection_date DATE NULL,
  remarks         TEXT NULL,                                -- "Remarks /" box on forms that have one
  data            LONGTEXT NULL,                            -- JSON: report cover values entered by the inspector (VIN, dimensions …)
  status          ENUM('draft','completed') NOT NULL DEFAULT 'draft',
  inspector_id    INT UNSIGNED NULL,
  completed_at    DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cl_sj FOREIGN KEY (sub_job_id) REFERENCES sub_jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_cl_tpl FOREIGN KEY (template_id) REFERENCES checklist_templates(id),
  CONSTRAINT fk_cl_insp FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE checklist_responses (
  checklist_id  INT UNSIGNED NOT NULL,
  item_id       INT UNSIGNED NOT NULL,
  result        ENUM('pass','fail','na') NULL,
  remarks       VARCHAR(255) NULL,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (checklist_id, item_id),
  CONSTRAINT fk_cr_cl FOREIGN KEY (checklist_id) REFERENCES checklists(id) ON DELETE CASCADE,
  CONSTRAINT fk_cr_item FOREIGN KEY (item_id) REFERENCES checklist_template_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certificates (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_job_id           INT UNSIGNED NOT NULL UNIQUE,
  template_id          INT UNSIGNED NULL,                 -- NULL when the certificate was uploaded
  certificate_no       VARCHAR(60) NULL UNIQUE,           -- reserved on draft, frozen on issue
  source               ENUM('generated','uploaded') NOT NULL DEFAULT 'generated',
  data                 LONGTEXT NULL,                     -- JSON: template field values
  result               ENUM('accepted','rejected','repair') NULL,
  result_statement     VARCHAR(255) NULL,
  remarks              TEXT NULL,
  inspection_date      DATE NULL,
  issue_date           DATE NULL,
  next_inspection_date DATE NULL,
  inspector_id         INT UNSIGNED NULL,
  reviewer_id          INT UNSIGNED NULL,                 -- QC "Reviewed By"
  status               ENUM('draft','pending_qc','issued','rejected','revoked') NOT NULL DEFAULT 'draft',
  upload_path          VARCHAR(255) NULL,
  verify_token         CHAR(32) NOT NULL UNIQUE,          -- public QR verification
  revoked_reason       VARCHAR(255) NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cert_status (status),
  CONSTRAINT fk_cert_sj FOREIGN KEY (sub_job_id) REFERENCES sub_jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_tpl FOREIGN KEY (template_id) REFERENCES certificate_templates(id),
  CONSTRAINT fk_cert_insp FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_cert_rev FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qc_reviews (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_job_id    INT UNSIGNED NOT NULL,
  reviewer_id   INT UNSIGNED NOT NULL,
  decision      ENUM('approved','rework') NOT NULL,
  comments      TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_qc_sj (sub_job_id),
  CONSTRAINT fk_qc_sj FOREIGN KEY (sub_job_id) REFERENCES sub_jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_qc_rev FOREIGN KEY (reviewer_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stickers are pre-printed serialized stock → allocated (handed) to an inspector → closed when linked to
-- one sub-job/equipment (locked from then on; the digital sticker follows the job) → may be voided.
CREATE TABLE stickers (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sticker_no      VARCHAR(40) NOT NULL UNIQUE,
  verify_token    CHAR(32) NULL UNIQUE,                     -- printed as QR on the sticker → public /s/{token}
  batch_no        VARCHAR(40) NULL,
  color           VARCHAR(20) NOT NULL DEFAULT 'green',     -- green (printed blue) = Accepted, red = Failed
  status          ENUM('in_stock','allocated','closed','void') NOT NULL DEFAULT 'in_stock',
  sub_job_id      INT UNSIGNED NULL,
  equipment_id    INT UNSIGNED NULL,
  certificate_id  INT UNSIGNED NULL,
  inspector_id    INT UNSIGNED NULL,                        -- inspector holding / who used the sticker
  allocated_at    DATETIME NULL,
  allocated_by    INT UNSIGNED NULL,
  linked_at       DATETIME NULL,                            -- linked to the sub-job → status closed
  issued_at       DATETIME NULL,                            -- certificate issued by QC
  expiry_date     DATE NULL,
  void_reason     VARCHAR(255) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_st_status (status),
  KEY idx_st_insp (inspector_id, status),
  CONSTRAINT fk_st_sj FOREIGN KEY (sub_job_id) REFERENCES sub_jobs(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_eq FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_cert FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_insp FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ID / competence card (CR80, printed front + back) for a TUV EAST inspector (holder_type inspector, user_id set)
-- or for an assessed client worker such as a rigger or scaffolding erector (holder_type worker, no login).
-- The QR on the back opens /verify/card/{token}, which shows the card online with a valid / expired / revoked message.
-- A card is valid (and, for inspectors, visible in the inspector portal) only once it is physically handed over;
-- handing over a new card revokes the holder's previous active one.
CREATE TABLE inspector_cards (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  holder_type     ENUM('inspector','worker') NOT NULL DEFAULT 'inspector',
  user_id         INT UNSIGNED NULL,                       -- inspector cards
  client_id       INT UNSIGNED NULL,                       -- worker cards: the worker's company, when it is a client
  card_no         VARCHAR(40)  NOT NULL UNIQUE,            -- printed as "ID CODE"
  verify_token    CHAR(32)     NOT NULL UNIQUE,
  holder_name     VARCHAR(120) NOT NULL,
  id_number       VARCHAR(30)  NULL,                       -- Iqama / national ID
  company         VARCHAR(120) NOT NULL,
  title           VARCHAR(80)  NOT NULL,                   -- bottom band of the front, e.g. LIFTING EQUIPMENT INSPECTOR
  photo_path      VARCHAR(255) NULL,
  job_ref         VARCHAR(60)  NULL,
  certification   VARCHAR(120) NULL,
  assessor        VARCHAR(120) NULL,
  competence      TEXT         NULL,                       -- "Certificate of Competence" box, one line per item
  issue_date      DATE         NOT NULL,
  expiry_date     DATE         NOT NULL,
  status          ENUM('active','revoked') NOT NULL DEFAULT 'active',
  revoked_reason  VARCHAR(255) NULL,
  handed_over_at  DATETIME NULL,                           -- NULL = printed, not yet given to the inspector
  handed_over_by  INT UNSIGNED NULL,
  created_by      INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ic_user (user_id, status),
  KEY idx_ic_idno (id_number),
  CONSTRAINT fk_ic_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ic_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
  CONSTRAINT fk_ic_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_ic_hand FOREIGN KEY (handed_over_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attachments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_job_id    INT UNSIGNED NOT NULL,
  kind          ENUM('photo','certificate_upload','document') NOT NULL DEFAULT 'photo',
  file_path     VARCHAR(255) NOT NULL,
  original_name VARCHAR(190) NOT NULL,
  mime          VARCHAR(80) NOT NULL,
  size_bytes    INT UNSIGNED NOT NULL,
  caption       VARCHAR(190) NULL,
  on_certificate TINYINT(1) NOT NULL DEFAULT 0,             -- print inside certificate image area
  uploaded_by   INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_att_sj FOREIGN KEY (sub_job_id) REFERENCES sub_jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- platform
CREATE TABLE notifications (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(190) NOT NULL,
  body        VARCHAR(255) NULL,
  link        VARCHAR(255) NULL,
  read_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_n_user (user_id, read_at),
  CONSTRAINT fk_n_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NULL,
  action       VARCHAR(60) NOT NULL,                    -- job.assigned, certificate.issued …
  entity_type  VARCHAR(40) NOT NULL,
  entity_id    INT UNSIGNED NULL,
  details      TEXT NULL,                               -- JSON
  ip_address   VARCHAR(45) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE number_sequences (
  name        VARCHAR(30) NOT NULL,
  period      VARCHAR(10) NOT NULL,
  last_value  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (name, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  `key`   VARCHAR(60) PRIMARY KEY,
  `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
