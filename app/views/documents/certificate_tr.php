<?php
/**
 * Trailer & Semi-Trailer inspection report (F-7.5.1-12) as a certificate template (layout 'tr').
 * The certificate fields carry the cover data and one Yes / No / N/A answer per form row (field.item holds the bilingual form text);
 * this wrapper maps them onto the model of the report layout in report_tr.php.
 * @var array $m  @var ?array $toolbar
 */
$cm = $m;
$cert = $cm['cert'];
$fv = [];
$items = [];
$codes = ['Yes' => 'pass', 'No' => 'fail', 'N/A' => 'na'];
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
require __DIR__ . '/report_tr.php';
