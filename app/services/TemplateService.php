<?php

/**
 * Dynamic checklist & certificate templates.
 * A template that is already referenced by a filled document is never modified in place:
 * saving creates a new version and deactivates the old one, so issued documents stay reproducible.
 */
final class TemplateService
{
    /** Print layout of the F-7.5.1-CL forms (mm / pt). Defaults = CL 01 Boom Truck. */
    public const CHECKLIST_LAYOUT = [
        'left' => 15, 'width' => 181, 'cols' => [36, 49, 12, 11, 12.7, 60.3],
        'row_h' => 7.85, 'font' => 10.5, 'title_font' => 13.5, 'desc_label' => 'DESCRIPTION',
        'top' => 31.6, 'top_next' => 28.6, 'sig_gap' => 7.4,
        'head_h' => 15.5,   // DESCRIPTION + first-section header rows together
        'title_h' => 10.4,  // title band
        'bottom' => 277.6,  // lowest point a row may reach before breaking to the next page
        'header' => 'cert_serial',  // cert_serial | cert_serial_std (+ Ref. Standard block) | elevator (S.N./Job No./Location/Date/Ref. Standard)
        'hdr_row_h' => 0,           // height of the identification rows under the title (0 = row_h)
        'std_label' => 'Ref. Standard', 'std_text' => '',
        'rem_label' => 'REMARKS',
        'band_align' => 'left',     // section band text alignment
        'remarks_box' => 0,         // mm; > 0 adds a "Remarks /" box under the items
        'sig_review' => false,      // add a "Reviewed By / Signature / Date" block (QC reviewer)
        'item_bold' => true,        // item text weight
        'kind' => 'checklist',      // checklist (F-7.5.1-CL grid) | report (cover page + Yes/No/N/A tables, CL 10-11)
        'report' => [],             // report-kind settings: cover rows, answer columns, sections, pictures, notes box
        'line' => 1.25,     // line pitch of wrapped item text, × font size
    ];

    /**
     * Section name markers (the name without the marker is what inspectors see in the app):
     *   "~Name"  → printed as a blank shaded band (the form has an unlabelled separator)
     *   "^Name"  → printed as a shaded band split into the PASS/FAIL/N/A/REMARKS columns
     *   "Name"   → printed as a full-width shaded band with the name
     */
    public static function sectionLabel(string $name): string
    {
        return ltrim($name, '~^');
    }

    public static function sectionStyle(string $name): string
    {
        return $name !== '' && $name[0] === '~' ? 'blank' : ($name !== '' && $name[0] === '^' ? 'split' : 'band');
    }

    public static function checklistLayout(array $tpl): array
    {
        $l = array_merge(self::CHECKLIST_LAYOUT, json_decode((string) ($tpl['layout'] ?? ''), true) ?: []);
        $cols = array_map('floatval', array_slice(array_pad((array) $l['cols'], 6, 10), 0, 6));
        $l['cols'] = $cols;
        $l['width'] = array_sum($cols);
        foreach (['left', 'row_h', 'font', 'title_font', 'top', 'top_next', 'sig_gap', 'head_h', 'line', 'title_h', 'bottom', 'hdr_row_h', 'remarks_box'] as $k) $l[$k] = (float) $l[$k];
        if ($l['hdr_row_h'] <= 0) $l['hdr_row_h'] = $l['row_h'];
        $l['sig_review'] = !empty($l['sig_review']);
        $l['item_bold'] = !isset($l['item_bold']) || !empty($l['item_bold']);
        $l['kind'] = ($l['kind'] ?? '') === 'report' ? 'report' : 'checklist';
        $l['report'] = is_array($l['report'] ?? null) ? $l['report'] : [];
        return $l;
    }

    /** Sanitize a layout array coming from the template editor or seed data. */
    public static function cleanLayout($in): ?string
    {
        if (!is_array($in) || !$in) return null;
        $d = self::CHECKLIST_LAYOUT;
        $num = fn ($k, $min, $max) => max($min, min($max, (float) ($in[$k] ?? $d[$k])));
        $cols = is_array($in['cols'] ?? null) ? $in['cols'] : explode(',', (string) ($in['cols'] ?? ''));
        $cols = array_map(fn ($c) => max(5, min(150, (float) $c)), array_slice(array_filter($cols, 'is_numeric'), 0, 6));
        if (count($cols) !== 6 || array_sum($cols) > 190) $cols = $d['cols'];
        return json_encode([
            'left' => $num('left', 5, 30), 'cols' => array_values($cols), 'row_h' => $num('row_h', 5, 15), 'font' => $num('font', 7, 14),
            'title_font' => $num('title_font', 9, 18), 'desc_label' => mb_substr(trim((string) ($in['desc_label'] ?? $d['desc_label'])) ?: $d['desc_label'], 0, 40),
            'top' => $num('top', 20, 50), 'top_next' => $num('top_next', 20, 50), 'sig_gap' => $num('sig_gap', 0, 30),
            'head_h' => $num('head_h', 8, 30), 'line' => $num('line', 1, 2), 'title_h' => $num('title_h', 6, 20), 'bottom' => $num('bottom', 240, 278),
            'header' => in_array($in['header'] ?? '', ['cert_serial', 'cert_serial_std', 'elevator'], true) ? $in['header'] : 'cert_serial',
            'hdr_row_h' => max(0, min(20, (float) ($in['hdr_row_h'] ?? 0))),
            'std_label' => mb_substr(trim((string) ($in['std_label'] ?? $d['std_label'])) ?: $d['std_label'], 0, 40),
            'std_text' => mb_substr(trim((string) ($in['std_text'] ?? '')), 0, 300),
            'rem_label' => mb_substr(trim((string) ($in['rem_label'] ?? $d['rem_label'])) ?: $d['rem_label'], 0, 30),
            'band_align' => ($in['band_align'] ?? '') === 'center' ? 'center' : 'left',
            'remarks_box' => max(0, min(80, (float) ($in['remarks_box'] ?? 0))),
            'sig_review' => !empty($in['sig_review']),
            'item_bold' => !isset($in['item_bold']) || !empty($in['item_bold']),
            'kind' => ($in['kind'] ?? '') === 'report' ? 'report' : 'checklist',
            'report' => self::cleanReport($in['report'] ?? null),
        ]);
    }

    /** Report-kind settings are structured (cover rows, answer columns …); accept JSON text or arrays, keep only known keys. */
    public static function cleanReport($r): array
    {
        if (is_string($r)) $r = json_decode($r, true);
        if (!is_array($r)) return [];
        $keep = ['cover', 'cols', 'answers', 'note_label', 'band_h', 'intro_h', 'colhead_h', 'band_align', 'intros', 'gap_before',
                 'pictures', 'notes_box', 'item_font', 'ar', 'sign', 'sign_h', 'style'];
        return array_intersect_key($r, array_flip($keep));
    }

    /** Manual (inspector-entered) cover keys of a report layout. */
    public static function reportManualKeys(array $layout): array
    {
        $keys = [];
        foreach ($layout['report']['cover']['rows'] ?? [] as $row) {
            $cells = in_array($row[0], ['triple', 'quad', 'hdr4'], true) ? $row[1] : [[$row[1] ?? '', $row[2] ?? '']];
            if (in_array($row[0], ['f', 'triple', 'vin', 'quad', 'hdr4'], true)) {
                foreach ($cells as $c) {
                    $k = $row[0] === 'vin' ? ($row[1] ?? 'vin') : ($c[1] ?? '');
                    if ($k !== '' && !array_key_exists($k, self::REPORT_SOURCES)) $keys[$k] = $row[0] === 'vin' ? 'VIN (17 characters)' : ($c[0] ?? $k);
                }
            }
        }
        return $keys;
    }

    /** Cover-page keys filled from the system record (not editable on the report). */
    public const REPORT_SOURCES = [
        'report_no' => 'Report / certificate No.', 'report_date' => 'Report date (on QC approval)', 'client' => 'Client name',
        'inspection_date' => 'Inspection date', 'site' => 'Job location', 'serial' => 'Serial No.', 'center_name' => 'Inspection centre',
    ];

    /** Cover-page keys pre-filled from the equipment record that the inspector may correct. */
    public const REPORT_DEFAULTS = ['eq_desc' => 'description', 'model' => 'model', 'brand' => 'manufacturer', 'year' => 'year_built'];

    /** @param array $sections ['Section name' => ['item label', …] | [['label'=>…, 'mandatory'=>bool], …]] */
    public static function saveChecklist(?int $templateId, int $typeId, array $header, array $sections): int
    {
        return DB::tx(function () use ($templateId, $typeId, $header, $sections) {
            $sections = array_filter($sections, fn ($items) => count($items) > 0);
            if (!$sections) throw new DomainException('Add at least one checklist item.');
            foreach (['title', 'doc_ref', 'revision'] as $k) {
                if (trim($header[$k] ?? '') === '') throw new DomainException('Title, document ref. and revision are required.');
            }
            $old = $templateId ? DB::one('SELECT * FROM checklist_templates WHERE id = ?', [$templateId]) : null;
            $inUse = $old && DB::value('SELECT COUNT(*) FROM checklists WHERE template_id = ?', [$old['id']]);
            $row = [
                'inspection_type_id' => $typeId,
                'code'          => mb_substr(trim($header['code'] ?? $header['doc_ref']), 0, 40),
                'title'         => mb_substr(trim($header['title']), 0, 190),
                'doc_ref'       => mb_substr(trim($header['doc_ref']), 0, 60),
                'revision'      => mb_substr(trim($header['revision']), 0, 10),
                'revision_date' => mb_substr(trim($header['revision_date'] ?? ''), 0, 20),
                // editor posts only the fields it shows: merge with the stored layout so report settings etc. survive
                'layout'        => self::cleanLayout(is_array($header['layout'] ?? null)
                    ? array_merge(json_decode((string) ($old['layout'] ?? ''), true) ?: [], $header['layout'])
                    : null) ?? ($old['layout'] ?? null),
            ];
            if ($old && !$inUse) {
                DB::update('checklist_templates', $row, 'id = ?', [$old['id']]);
                DB::run('DELETE FROM checklist_template_items WHERE template_id = ?', [$old['id']]);
                $id = (int) $old['id'];
            } else {
                if ($old) DB::update('checklist_templates', ['is_active' => 0], 'id = ?', [$old['id']]);
                DB::update('checklist_templates', ['is_active' => 0], 'inspection_type_id = ?', [$typeId]);
                $row['version'] = $old ? (int) $old['version'] + 1 : 1;
                $id = DB::insert('checklist_templates', $row);
            }
            $sort = 0;
            foreach ($sections as $section => $items) {
                foreach ($items as $it) {
                    $label = is_array($it) ? trim($it['label'] ?? '') : trim($it);
                    if ($label === '') continue;
                    DB::insert('checklist_template_items', [
                        'template_id' => $id, 'section' => mb_substr(trim($section), 0, 120), 'label' => mb_substr($label, 0, 2000),
                        'is_mandatory' => is_array($it) ? (int) !empty($it['mandatory']) : 1, 'sort_order' => ++$sort,
                    ]);
                }
            }
            Audit::log($old ? ($inUse ? 'template.checklist.versioned' : 'template.checklist.updated') : 'template.checklist.created', 'checklist_template', $id);
            return $id;
        });
    }

    public static function saveCertificate(?int $templateId, int $typeId, array $h, array $fields): int
    {
        return DB::tx(function () use ($templateId, $typeId, $h, $fields) {
            foreach (['title', 'doc_ref', 'revision'] as $k) {
                if (trim($h[$k] ?? '') === '') throw new DomainException('Title, document ref. and revision are required.');
            }
            $clean = [];
            $keys = [];
            foreach ($fields as $f) {
                $label = trim($f['label'] ?? '');
                if ($label === '') continue;
                $key = trim($f['key'] ?? '') ?: trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');
                $base = $key; $i = 2;
                while (isset($keys[$key])) $key = $base . '_' . $i++;
                $keys[$key] = true;
                $src = array_key_exists($f['source'] ?? '', CertificateService::SOURCES) ? ($f['source'] ?? '') : '';
                $row = ['key' => $key, 'label' => mb_substr($label, 0, 60)];
                if ($src) {
                    $row['source'] = $src;
                } else {
                    $row['type'] = in_array($f['type'] ?? 'text', ['text', 'textarea', 'date', 'select', 'number'], true) ? $f['type'] : 'text';
                    $row['required'] = !empty($f['required']);
                    $row['carry'] = !empty($f['carry']);
                    // print-layout data of report-style templates (e.g. the motorcycle form): section heading + bilingual item text
                    if (!empty($f['section'])) $row['section'] = mb_substr((string) $f['section'], 0, 160);
                    if (!empty($f['item'])) $row['item'] = mb_substr(str_replace("\r\n", "\n", (string) $f['item']), 0, 2000);
                    if ($row['type'] === 'select') {
                        $opts = is_array($f['options'] ?? null) ? $f['options'] : explode(',', (string) ($f['options'] ?? ''));
                        $row['options'] = array_values(array_filter(array_map('trim', $opts), 'strlen'));
                    }
                }
                $clean[] = $row;
            }
            if (!$clean) throw new DomainException('Add at least one certificate field.');
            $opts = array_values(array_intersect(['accepted', 'rejected', 'repair'], (array) ($h['result_options'] ?? ['accepted', 'rejected', 'repair'])));
            $row = [
                'inspection_type_id' => $typeId,
                'code'              => mb_substr(trim($h['code'] ?? $h['doc_ref']), 0, 40),
                'title'             => mb_substr(trim($h['title']), 0, 190),
                'doc_ref'           => mb_substr(trim($h['doc_ref']), 0, 60),
                'revision'          => mb_substr(trim($h['revision']), 0, 10),
                'revision_date'     => mb_substr(trim($h['revision_date'] ?? ''), 0, 20),
                'fields'            => json_encode($clean, JSON_UNESCAPED_UNICODE),
                'result_options'    => implode(',', $opts ?: ['accepted', 'rejected']),
                'default_statement' => nullable($h['default_statement'] ?? null),
                'notes'             => nullable($h['notes'] ?? null),
                'inspector_label'   => mb_substr(trim($h['inspector_label'] ?? 'Inspector:') ?: 'Inspector:', 0, 60),
                'show_image_area'   => !empty($h['show_image_area']) ? 1 : 0,
            ];
            $old = $templateId ? DB::one('SELECT * FROM certificate_templates WHERE id = ?', [$templateId]) : null;
            if (DB::one("SHOW COLUMNS FROM certificate_templates LIKE 'layout'")) {
                $row['layout'] = nullable($h['layout'] ?? ($old['layout'] ?? null));
            }
            $inUse = $old && DB::value('SELECT COUNT(*) FROM certificates WHERE template_id = ?', [$old['id']]);
            if ($old && !$inUse) {
                DB::update('certificate_templates', $row, 'id = ?', [$old['id']]);
                $id = (int) $old['id'];
            } else {
                DB::update('certificate_templates', ['is_active' => 0], 'inspection_type_id = ?', [$typeId]);
                $row['version'] = $old ? (int) $old['version'] + 1 : 1;
                $id = DB::insert('certificate_templates', $row);
            }
            Audit::log($old ? ($inUse ? 'template.certificate.versioned' : 'template.certificate.updated') : 'template.certificate.created', 'certificate_template', $id);
            return $id;
        });
    }
}
