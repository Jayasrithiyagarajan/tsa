<?php
/**
 * Crane Health Check (F-7.5.1-CL 029) as a certificate template (layout 'ch').
 * The certificate fields carry the cover data and one Yes / No / N/A answer per check row (field.section / field.item hold the form text);
 * this wrapper maps them onto the model of the report layout in report_ch.php.
 * @var array $m  @var ?array $toolbar
 */
$cm = $m;
// report date: the certificate-date field if the inspector set one, else the issue date, else today (drafts)
$issueDate = $cm['cert']['issue_date'] ?? null;
foreach ($cm['fields'] as $f0) {
    if (($f0['source'] ?? '') === 'cert.issue_date' && ($ts0 = strtotime((string) ($f0['value'] ?? ''))) !== false && ($f0['value'] ?? '') !== '') { $issueDate = date('Y-m-d', $ts0); break; }
}
$issueDate = $issueDate ?: date('Y-m-d');
$cert = $cm['cert'];
$fv = [];
$items = [];
$codes = ['Yes' => 'pass', 'No' => 'fail', 'N/A' => 'na'];
foreach ($cm['fields'] as $f) {
    $fv[$f['key']] = (string) ($f['value'] ?? '');
    if (isset($f['item'], $f['section'])) {
        $res = $codes[(string) ($f['value'] ?? '')] ?? null;
        $items[] = ['section' => $f['section'], 'label' => $f['item'], 'result' => $res, 'remarks' => $res === 'na' ? 'N/A' : ''];
    }
}
$signed = in_array($cert['status'] ?? '', ['pending_qc', 'issued'], true);
$m = [
    'cert_mode' => true,                       // cover values come from the (editable) certificate fields
    'template' => $cm['template'],
    'sj' => $cm['sj'],
    'data' => $fv,
    'certificate_no' => $cert['certificate_no'] ?? '',
    'issue_date' => $issueDate,
    'cert_result' => $cert['result'] ?? null,
    'checklist' => ['inspection_date' => $cert['inspection_date'] ?? null, 'serial_no' => $cm['sj']['eq_serial_no'] ?? '', 'remarks' => $cert['remarks'] ?? '', 'status' => $signed ? 'completed' : 'draft'],
    'items' => $items,
    'photos' => array_map(fn ($p) => $p['src'], $cm['photos'] ?? []),
    'inspector' => $cm['inspector'] ?? null,
    'reviewer' => !empty($cm['issued']) ? ($cm['reviewer'] ?? null) : null,
];
$L = ['kind' => 'report', 'left' => 10.8, 'row_h' => 7.76, 'font' => 11, 'line' => 1.22, 'item_bold' => false, 'title_font' => 12,
    'top' => 33.0, 'top_next' => 30.85, 'bottom' => 254.5, 'sig_gap' => 0, 'sig_review' => true];
$R = [
    'style' => 'ch', 'cols' => [104.7, 31.7, 25.9, 28.8], 'answers' => [['pass', 'Yes'], ['fail', 'No']], 'note_label' => 'REMARKS',
    'cover' => [
        'width' => 191.1, 'left' => 10.8, 'top' => 33.0,
        'rows' => [
            ['hdr4', [['Client Name', 'client'], ['Location / Vessel', 'site'], ['Certificate No.', 'report_no'], ['Inspection Date', 'inspection_date']]],
            ['quad', [['Crane Type', 'crane_type'], ['Capacity / SWL', 'capacity']], 8.48],
            ['quad', [['Identification No. S/N', 'serial'], ['Manufacturer / Year', 'manufacturer_year']], 9.82],
            ['quad', [['Equipment Tag No', 'tag_no'], ['Min. / Max. Boom Length', 'boom_length']], 8.48],
            ['quad', [['Crane Model', 'model'], ['Min. / Max. Radius', 'radius']], 8.48],
            ['quad', [['Boom Hoist Wire Rope Specs', 'boom_rope'], ['Main Hoist Wire Rope Specs', 'main_rope']], 8.48],
            ['quad', [['Pendant Wire Rope Specs', 'pendant_rope'], ['Aux Hoist Wire Rope Specs', 'aux_rope']], 8.48],
            ['quad', [['Date of Last Examination (Blue Sticker)', 'last_exam'], ['Date of Next Examination (Blue Sticker)', 'next_exam']], 11.4],
        ],
    ],
    'pictures' => ['title' => 'REFERENCE PHOTOS:', 'cols' => 2, 'rows' => 3, 'h' => 87.13, 'title_h' => 7.76],
];
require __DIR__ . '/report_ch.php';
