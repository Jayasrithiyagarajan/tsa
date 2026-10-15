<?php
/**
 * Non-destructive upgrade:  php database/migrate.php
 * - adds checklist_templates.layout (print layout per form)
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
