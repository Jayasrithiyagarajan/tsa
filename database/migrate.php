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
}
echo "Done. Remember to grant inspectors the new qualifications (Users & Inspectors → Qualified to inspect).\n";
