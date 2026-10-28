<?php
/**
 * Motorcycle inspection report (F-7.5.1-14) as a certificate template (layout 'mo').
 * The certificate fields carry the cover data and one Pass / Fail / N/A answer per checklist item (field.section / field.item hold the
 * bilingual form text); this wrapper maps them onto the model of the bilingual report layout in report_mo.php.
 * @var array $m  @var ?array $toolbar
 */
$cm = $m;
$cert = $cm['cert'];
$fv = [];
$items = [];
$codes = ['Pass' => 'pass', 'Fail' => 'fail', 'N/A' => 'na'];
foreach ($cm['fields'] as $f) {
    $fv[$f['key']] = (string) ($f['value'] ?? '');
    if (isset($f['item'], $f['section'])) {
        $items[] = ['section' => $f['section'], 'label' => $f['item'], 'result' => $codes[(string) ($f['value'] ?? '')] ?? null];
    }
}
$signed = in_array($cert['status'] ?? '', ['pending_qc', 'issued'], true);
$m = [
    'template' => $cm['template'],
    'sj' => $cm['sj'],
    'data' => $fv,
    'certificate_no' => $cert['certificate_no'] ?? '',
    'issue_date' => $cert['issue_date'] ?? null,
    'review_date' => !empty($cm['issued']) ? ($cert['issue_date'] ?? null) : null,
    'cert_result' => $cert['result'] ?? null,
    'checklist' => ['inspection_date' => $cert['inspection_date'] ?? null, 'serial_no' => $cm['sj']['eq_serial_no'] ?? '', 'remarks' => $cert['remarks'] ?? '', 'status' => $signed ? 'completed' : 'draft'],
    'items' => $items,
    'photos' => array_map(fn ($p) => $p['src'], $cm['photos'] ?? []),
    'inspector' => $cm['inspector'] ?? null,
    'reviewer' => $cm['reviewer'] ?? null,
];
$L = ['report' => ['ar' => ['style' => 'mo', 'pt' => 8, 'line' => 1.1, 'font' => 10, 'en_line' => 1.23, 'top' => 29.7, 'top_next' => 25.4, 'bottom' => 268.5, 'hdr_gap' => 8, 'sign_gap' => 14]]];
require __DIR__ . '/report_mo.php';
