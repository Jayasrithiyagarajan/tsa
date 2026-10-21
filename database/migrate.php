<?php
/**
 * Non-destructive upgrade:  php database/migrate.php
 * - adds checklist_templates.layout (print layout per form), inspector_cards (ID cards)
 * - loads/updates every checklist & certificate template from seed_templates.php:
 *     new inspection types are created; changed checklists of existing types are saved as a NEW VERSION
 *     (documents already filled keep the version they were created with).
 * Safe to run repeatedly.
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require __DIR__ . '/../app/bootstrap.php';
$_SESSION = [];

if (!DB::one("SHOW COLUMNS FROM checklist_templates LIKE 'layout'")) {
    DB::pdo()->exec("ALTER TABLE checklist_templates ADD layout TEXT NULL AFTER revision_date");
    echo "✓ added checklist_templates.layout\n";
}

if (!DB::one("SHOW COLUMNS FROM certificate_templates LIKE 'layout'")) {
    DB::pdo()->exec("ALTER TABLE certificate_templates ADD layout VARCHAR(20) NULL AFTER show_image_area");
    echo "✓ added certificate_templates.layout\n";
}

if (DB::value("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'checklist_template_items' AND COLUMN_NAME = 'label'") !== 'text') {
    DB::pdo()->exec('ALTER TABLE checklist_template_items MODIFY label TEXT NOT NULL');
    echo "✓ checklist item labels widened to TEXT\n";
}

if (!DB::one("SHOW COLUMNS FROM checklists LIKE 'remarks'")) {
    DB::pdo()->exec("ALTER TABLE checklists ADD remarks TEXT NULL AFTER inspection_date");
    echo "✓ added checklists.remarks\n";
}

if (!DB::one("SHOW COLUMNS FROM checklists LIKE 'data'")) {
    DB::pdo()->exec("ALTER TABLE checklists ADD data LONGTEXT NULL AFTER remarks");
    echo "✓ added checklists.data\n";
}

if (!DB::one("SHOW COLUMNS FROM stickers LIKE 'linked_at'")) {
    // sticker lifecycle: in_stock → allocated (to inspector) → closed (linked to a job, locked) / void
    DB::pdo()->exec("ALTER TABLE stickers MODIFY status ENUM('in_stock','reserved','issued','allocated','closed','void') NOT NULL DEFAULT 'in_stock'");
    DB::pdo()->exec("ALTER TABLE stickers ADD allocated_at DATETIME NULL AFTER inspector_id, ADD allocated_by INT UNSIGNED NULL AFTER allocated_at,
                     ADD linked_at DATETIME NULL AFTER allocated_by, ADD KEY idx_st_insp (inspector_id, status)");
    DB::pdo()->exec("UPDATE stickers s SET s.linked_at = COALESCE((SELECT MIN(a.created_at) FROM audit_logs a WHERE a.entity_type = 'sticker' AND a.entity_id = s.id AND a.action = 'sticker.reserved'), s.issued_at, NOW()),
                     s.allocated_at = s.linked_at, s.status = 'closed' WHERE s.status IN ('reserved','issued')");
    DB::pdo()->exec("ALTER TABLE stickers MODIFY status ENUM('in_stock','allocated','closed','void') NOT NULL DEFAULT 'in_stock'");
    echo "✓ stickers: allocation to inspectors + closed-on-link lifecycle
";
}

if (!DB::one("SHOW COLUMNS FROM stickers LIKE 'verify_token'")) {
    DB::pdo()->exec("ALTER TABLE stickers ADD verify_token CHAR(32) NULL AFTER sticker_no, ADD UNIQUE KEY uq_st_token (verify_token)");
    echo "✓ added stickers.verify_token (QR code)\n";
}
foreach (DB::all('SELECT id FROM stickers WHERE verify_token IS NULL') as $row) {
    DB::update('stickers', ['verify_token' => bin2hex(random_bytes(16))], 'id = ?', [$row['id']]);
}

if (!DB::one("SHOW TABLES LIKE 'inspector_cards'")) {
    preg_match('/CREATE TABLE inspector_cards \(.*?\) ENGINE=[^;]+;/s', file_get_contents(__DIR__ . '/schema.sql'), $m);
    DB::pdo()->exec($m[0]);
    echo "✓ added inspector_cards (inspector ID cards)\n";
}

if (!DB::one("SHOW COLUMNS FROM inspector_cards LIKE 'handed_over_at'")) {
    DB::pdo()->exec("ALTER TABLE inspector_cards ADD handed_over_at DATETIME NULL AFTER revoked_reason, ADD handed_over_by INT UNSIGNED NULL AFTER handed_over_at,
                     ADD CONSTRAINT fk_ic_hand FOREIGN KEY (handed_over_by) REFERENCES users(id) ON DELETE SET NULL");
    echo "✓ added inspector_cards.handed_over_at\n";
}

if (!DB::one("SHOW COLUMNS FROM inspector_profiles LIKE 'photo_path'")) {
    DB::pdo()->exec("ALTER TABLE inspector_profiles ADD photo_path VARCHAR(255) NULL AFTER base_location");
    echo "✓ added inspector_profiles.photo_path\n";
}

if (!DB::one("SHOW COLUMNS FROM inspector_cards LIKE 'holder_type'")) {
    // cards for client workers (riggers, operators …) who have no login
    DB::pdo()->exec("ALTER TABLE inspector_cards ADD holder_type ENUM('inspector','worker') NOT NULL DEFAULT 'inspector' AFTER id,
                     MODIFY user_id INT UNSIGNED NULL, ADD client_id INT UNSIGNED NULL AFTER user_id, ADD KEY idx_ic_idno (id_number),
                     ADD CONSTRAINT fk_ic_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL");
    echo "✓ inspector_cards: client worker cards (holder_type, client_id)\n";
}

if (!DB::one("SHOW COLUMNS FROM users WHERE Field = 'role' AND Type LIKE '%superadmin%'")) {
    // the former admin becomes Super Admin; "admin" is now the operations role (jobs + final approval, view-only master data)
    DB::pdo()->exec("ALTER TABLE users MODIFY role ENUM('superadmin','admin','inspector','qc','customer') NOT NULL");
    $n = DB::pdo()->exec("UPDATE users SET role = 'superadmin' WHERE role = 'admin'");
    echo "✓ roles: superadmin added, $n existing admin account(s) promoted to Super Admin\n";
}

if (!DB::one("SHOW COLUMNS FROM jobs LIKE 'closed_by'")) {
    // QC-approved jobs wait for an admin's final approval before they close and the certificates are released
    DB::pdo()->exec("ALTER TABLE jobs MODIFY status ENUM('pending_assignment','assigned','in_progress','under_review','awaiting_approval','completed','cancelled') NOT NULL DEFAULT 'pending_assignment',
                     ADD closed_by INT UNSIGNED NULL AFTER completed_at");
    echo "✓ jobs: final approval step (awaiting_approval, closed_by)\n";
}

if (!DB::one("SHOW COLUMNS FROM jobs LIKE 'closed_by_name'")) {
    // who closed the job (name + role) is saved with the job, so it stays correct even if the user is renamed or changes role later
    DB::pdo()->exec("ALTER TABLE jobs ADD closed_by_name VARCHAR(120) NULL AFTER closed_by, ADD closed_by_role VARCHAR(20) NULL AFTER closed_by_name");
    DB::pdo()->exec("UPDATE jobs j JOIN users u ON u.id = j.closed_by SET j.closed_by_name = u.name, j.closed_by_role = u.role WHERE j.closed_by IS NOT NULL");
    echo "+ jobs: closed_by_name / closed_by_role saved\n";
}

$sig = fn (array $sections) => md5(json_encode(array_map(fn ($items) => array_values($items), $sections)));

foreach (require __DIR__ . '/seed_templates.php' as $def) {
    $code = $def['type']['code'];
    $type = DB::one('SELECT * FROM inspection_types WHERE code = ?', [$code]);
    if (!$type) {
        $typeId = DB::insert('inspection_types', $def['type']);
        echo "✓ inspection type $code created\n";
    } else {
        $typeId = (int) $type['id'];
    }

    if ($def['checklist']) {
        $cur = InspectionService::activeChecklistTemplate($typeId);
        $curSections = [];
        if ($cur) {
            foreach (DB::all('SELECT section, label FROM checklist_template_items WHERE template_id = ? ORDER BY sort_order', [$cur['id']]) as $i) {
                $curSections[$i['section']][] = $i['label'];
            }
        }
        $wantLayout = TemplateService::cleanLayout($def['checklist']['layout'] ?? null);
        $same = $cur && $sig($curSections) === $sig($def['checklist']['sections'])
            && $cur['title'] === $def['checklist']['title'] && $cur['revision'] === $def['checklist']['revision']
            && (string) $cur['layout'] === (string) $wantLayout;
        if (!$same) {
            $id = TemplateService::saveChecklist($cur['id'] ?? null, $typeId, $def['checklist'], $def['checklist']['sections']);
            $n = (int) DB::value('SELECT COUNT(*) FROM checklist_template_items WHERE template_id = ?', [$id]);
            $v = DB::value('SELECT version FROM checklist_templates WHERE id = ?', [$id]);
            echo "✓ $code checklist {$def['checklist']['doc_ref']} saved (v$v, $n items)\n";
        } else {
            echo "· $code checklist up to date\n";
        }
    }

    if ($def['certificate'] && !InspectionService::activeCertificateTemplate($typeId)) {
        TemplateService::saveCertificate(null, $typeId, $def['certificate'] + ['show_image_area' => 1, 'result_options' => ['accepted', 'rejected', 'repair']], $def['certificate']['fields']);
        echo "✓ $code certificate template created\n";
    }
    // certificates that declare a print layout: keep the stored template in step with the seed (versioned when already in use)
    elseif ($def['certificate'] && !empty($def['certificate']['layout'])) {
        $cur = InspectionService::activeCertificateTemplate($typeId);
        $c = $def['certificate'];
        $norm = fn (array $fs) => array_map(fn ($f) => [$f['key'], $f['label'], $f['source'] ?? '', $f['type'] ?? '', array_values($f['options'] ?? []), !empty($f['required']), !empty($f['carry'])], $fs);
        $same = (string) $cur['layout'] === (string) $c['layout'] && $cur['title'] === $c['title'] && $cur['revision'] === $c['revision']
            && $cur['inspector_label'] === $c['inspector_label'] && (string) $cur['default_statement'] === (string) $c['default_statement']
            && (string) $cur['notes'] === (string) ($c['notes'] ?? '') && $norm(json_decode($cur['fields'], true) ?: []) === $norm($c['fields']);
        if (!$same) {
            TemplateService::saveCertificate($cur['id'], $typeId, $c + ['show_image_area' => (int) $cur['show_image_area'], 'result_options' => explode(',', $cur['result_options'])], $c['fields']);
            echo "✓ $code certificate template updated ({$c['doc_ref']}, layout {$c['layout']})\n";
        } else {
            echo "· $code certificate up to date\n";
        }
    }
}
echo "Done. Remember to grant inspectors the new qualifications (Users & Inspectors → Qualified to inspect).\n";
