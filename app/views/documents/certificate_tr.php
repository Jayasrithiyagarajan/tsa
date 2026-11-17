<?php
/**
 * Trailer & Semi-Trailer inspection report (F-7.5.1-CL 10) as a certificate template (layout 'tr'), printed exactly as the form:
 * data cover (VIN boxes, dimensions, axle weights, technical regulation, result), "1) The General Requirements" table of Yes / No / N/A rows
 * with a Note column, Trailer Pictures grid (2 x 3), Inspector Notes box and the Inspector / Reviewed By block.
 * The certificate fields carry the cover data and one Yes / No / N/A answer (plus a remark) per form row (field.item holds the form text);
 * this wrapper maps them onto the model of the report layout in report.php.
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
$cdata = json_decode((string) ($cert['data'] ?? '{}'), true) ?: [];
$fv = [];
$items = [];
$codes = ['Yes' => 'pass', 'No' => 'fail', 'N/A' => 'na'];
$lastSec = null;
$headings = ['9- BRAKING SYSTEM' => '. 9- BRAKING SYSTEM'];   // heading text exactly as printed on the form
foreach ($cm['fields'] as $f) {
    $fv[$f['key']] = (string) ($f['value'] ?? '');
    if (isset($f['item'], $f['section'])) {
        $label = (string) $f['item'];
        if ($f['section'] !== $lastSec) { $label = "## " . ($headings[$f['section']] ?? $f['section']) . "\n" . $label; $lastSec = $f['section']; }   // sub-heading is the first line of the group's first row
        $items[] = ['section' => '1) The General Requirements:', 'label' => $label, 'result' => $codes[(string) ($f['value'] ?? '')] ?? null, 'remarks' => trim((string) ($cdata[$f['key'] . '_r'] ?? ''))];
    }
}
if (trim($fv['center_address'] ?? '') === '') $fv['center_address'] = 'Al Khobar, 6205 Prince Muteb St, Al-Sharq Tower, 5th Floor';
$signed = in_array($cert['status'] ?? '', ['pending_qc', 'issued'], true);
$m = [
    'template' => $cm['template'],
    'sj' => $cm['sj'],
    'data' => $fv,
    'certificate_no' => $cert['certificate_no'] ?? '',
    'issue_date' => $issueDate,
    'review_date' => !empty($cm['issued']) ? ($cert['issue_date'] ?? null) : null,
    'cert_result' => $cert['result'] ?? null,
    'checklist' => ['inspection_date' => $cert['inspection_date'] ?? null, 'serial_no' => $cm['sj']['eq_serial_no'] ?? '', 'remarks' => $cert['remarks'] ?? '', 'status' => $signed ? 'completed' : 'draft'],
    'items' => $items,
    'photos' => array_map(fn ($p) => $p['src'], $cm['photos'] ?? []),
    'inspector' => $cm['inspector'] ?? null,
    'reviewer' => !empty($cm['issued']) ? ($cm['reviewer'] ?? null) : null,
    'issued' => !empty($cm['issued']),
];
// print layout of the form (as in the CL 10 PDF)
$L = [
    'kind' => 'report', 'left' => 13.9, 'row_h' => 7.8, 'font' => 10, 'line' => 1.22, 'item_bold' => false, 'title_font' => 14,
    'top' => 28.8, 'top_next' => 28.7, 'bottom' => 267, 'sig_gap' => 0, 'sig_review' => true,
    'report' => [
        'cols' => [93.8, 13.7, 13.8, 13.7, 47.3],
        'answers' => [['pass', 'Yes'], ['fail', 'No'], ['na', 'N/A']],
        'note_label' => 'Note', 'band_h' => 7.7, 'intro_h' => 7.7, 'colhead_h' => 10.3,
        'intros' => ['1) The General Requirements:' => 'The result (Yes\\No\\N/A) should be placed only after completing the inspection'],
        'pictures' => ['title' => 'Trailer Pictures', 'cols' => 2, 'rows' => 3, 'h' => 45.85, 'title_h' => 7.7, 'gap' => 0],
        'notes_box' => ['label' => 'Inspector Notes:', 'gap' => 4.3, 'label_h' => 7.7, 'h' => 19.6],
        'sign_class' => 's10', 'sign_hh' => 10.2, 'sign_vh' => 12.9,
        'cover' => [
            'left' => 13.9, 'width' => 182.3, 'label_w' => 42.9, 'title_h' => 10.1, 'top' => 28.8, 'row_h' => 9.07,
            'rows' => [
                ['f', 'Report No.', 'report_no'], ['f', 'Report Date', 'report_date'], ['f', 'Inspection Center Name', 'center_name'],
                ['f', 'Inspection Center Address', 'center_address'], ['f', 'Client Name', 'client'], ['f', 'Origin Country', 'origin'],
                ['f', 'Product Description', 'eq_desc'], ['f', 'Model', 'model'], ['f', 'Type', 'type'],
                ['f', 'Brand/Trademark Name', 'brand'], ['f', 'Manufacture Date', 'mfg_date'],
                ['band', 'Chassis No. (VIN)', 10.3], ['vin', 'vin', 4.3, 9.7, false],
                ['band', 'Dimension (Meter)', 4.4],
                ['triple', [['Height', 'dim_h'], ['Width', 'dim_w'], ['Length', 'dim_l']], 10.2, [30.2, 30.3, 30.5, 30.3, 30.3, 30.7]],
                ['band', 'Max. Axle Weight (kg)', 4.4],
                ['triple', [['Axle 1', 'axle_1'], ['Axle 2', 'axle_2'], ['Axle 3', 'axle_3']], 10.2, [30.2, 30.3, 30.5, 30.3, 30.3, 30.7]],
                ['f', 'Gross Weight (kg)', 'gross', 10.2],
                ['text', 'Technical Regulation', "MA-162-17-04-01\nSASO-TR-Trailers and Semi-Trailers-Version 1-Amendment 1", 10.2],
                ['text', 'Technical Standards', 'SASO 2910:2022', 10.2],
                ['f', 'Inspection Date', 'inspection_date', 10.2],
                ['result', 'Inspection Result', 12.85, 27.5],
            ],
        ],
    ],
];
require __DIR__ . '/report.php';
