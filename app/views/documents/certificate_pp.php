<?php
/**
 * Petroleum Products Transportation Tanks inspection report (F-7.5.1-CL 11) as a certificate template (layout 'pp').
 * The certificate fields carry the cover data and one Yes / No / N/A answer (plus a remark) per form row (field.item holds the form text);
 * this wrapper maps them onto the model of the report layout in report.php (cover, VIN boxes, check tables, vehicle pictures, sign-off).
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
foreach ($cm['fields'] as $f) {
    $fv[$f['key']] = (string) ($f['value'] ?? '');
    if (isset($f['item'], $f['section'])) {
        $items[] = ['section' => $f['section'], 'label' => $f['item'], 'result' => $codes[(string) ($f['value'] ?? '')] ?? null, 'remarks' => trim((string) ($cdata[$f['key'] . '_r'] ?? ''))];
    }
}
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
// print layout of the form (as in the CL 11 PDF)
$L = [
    'kind' => 'report', 'left' => 15.8, 'row_h' => 7.8, 'font' => 10, 'line' => 1.23, 'item_bold' => true, 'title_font' => 14,
    'top' => 33.3, 'top_next' => 25.4, 'bottom' => 273, 'sig_gap' => 7.4, 'sig_review' => true,
    'report' => [
        'cols' => [94, 13.2, 13.2, 13.3, 52],
        'answers' => [['na', 'N/A'], ['fail', 'No'], ['pass', 'Yes']],
        'note_label' => 'Note:', 'band_h' => 5.2, 'intro_h' => 5.3, 'colhead_h' => 5.3, 'band_align' => 'center',
        'gap_before' => ['Vehicle dimensions and weights'],
        'intros' => ['Supporting Documents' => 'The result (Yes/No/No) should be given only after the inspection of mobile gas station tanks has been completed.'],
        'pictures' => ['title' => 'Vehicle Picture', 'cols' => 2, 'rows' => 4, 'h' => 51, 'title_h' => 10, 'gap' => 0],
        'cover' => [
            'left' => 15.0, 'width' => 185.7, 'label_w' => 50.6, 'title_h' => 12.9, 'top' => 33.3, 'row_h' => 9.07,
            'rows' => [
                ['f', 'Report No.', 'report_no'], ['f', 'Report Date', 'report_date'], ['f', 'Client Name', 'client'],
                ['f', 'Address of the site', 'site'], ['f', 'Date of inspection', 'inspection_date'], ['f', 'Odometer', 'odometer'],
                ['f', 'Specifications', 'specs'], ['band', 'Vin Number', 10.3], ['vin', 'vin', 9.1, 9.1, true],
                ['band', 'Vehicle Data', 10.3], ['f', 'Vehicle Type (Description)', 'eq_desc'], ['f', 'Model', 'model'],
                ['f', 'Color', 'color'], ['f', 'Country Made', 'origin'], ['f', 'Manufacturer Name (Brand)', 'brand'],
                ['f', 'Year of manufacture', 'year'], ['result', 'Inspection result', 15.4, 36.6],
            ],
        ],
    ],
];
require __DIR__ . '/report.php';
