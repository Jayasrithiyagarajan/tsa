<?php
/** Leak / Hydro and Pressure / Hydro test witness certificates (F-7.5.1-10 / -11), one page. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$hh = fn ($mm) => 'height:' . round($mm - 0.18, 2) . 'mm';
// Dompdf ignores a height on <tr>: copy it onto every cell of the row
$fix = fn (string $t) => preg_replace_callback('~<tr style="(height:[^"]+)">(.*?)</tr>~s', fn ($mm) => '<tr>' . preg_replace('~<td\b~', '<td style="' . $mm[1] . '"', $mm[2]) . '</tr>', $t);

// ---- header data (label / value, two pairs per row)
$pairs = [
    ['Client Name', 'client', 'Address / Location', 'address'],
    ['Test Location', 'location', 'Report No.', 'cert_no'],
    ['Relevant Standard', 'standard', 'Holding Time', 'holding_time'],
    ['Equipment Name', 'eq_name', 'Normal / Actual Pressure', 'pressure'],
    ['Sticker No', 'sticker_no', 'Test Method', 'test_method'],
    ['Inspection Date', 'insp_date', 'Next Inspection Date', 'next_date'],
];
$t1 = '<table class="f lp">' . doc_sizer([47.6, 47.6, 47.6, 47.7]);
foreach ($pairs as $r) $t1 .= '<tr style="' . $hh(7.62) . '"><td class="l">' . $r[0] . '</td><td>' . $v($r[1]) . '</td><td class="l">' . $r[2] . '</td><td>' . $v($r[3]) . '</td></tr>';
$t1 .= '</table>';

// ---- equipment details
$t2 = '<table class="f lp gap" style="margin-top:4.75mm">' . doc_sizer([63.5, 63.5, 63.5])
    . '<tr style="' . $hh(10.16) . '"><td colspan="3" class="l c h12">EQUIPMENT DETAILS</td></tr>'
    . '<tr style="' . $hh(10.16) . '"><td class="l c h12">S/N</td><td class="l c h12">DESCRIPTION</td><td class="l c h12">CAPACITY</td></tr>'
    . '<tr style="' . $hh(10.16) . '"><td class="c">' . $v('sn') . '</td><td class="c">' . $v('description') . '</td><td class="c">' . $v('capacity') . '</td></tr></table>';

// ---- result + remarks (default remarks = the template notes)
$remarks = trim($fv['remarks'] ?? '') !== '' ? preg_split('/\R/u', trim($fv['remarks'])) : array_values(array_filter(array_map('trim', (array) ($m['notes'] ?? [])), fn ($l) => $l !== ''));
$rem = '<div class="rmh">Remarks:</div>';
foreach ($remarks as $i => $l) $rem .= '<div class="rml' . ($i === count($remarks) - 1 ? ' last' : '') . '">' . e($l) . '</div>';
$t3 = '<table class="f lp gap" style="margin-top:4.93mm">' . doc_sizer([190.5])
    . '<tr style="' . $hh(10.16) . '"><td class="l c h12">RESULT</td></tr>'
    . '<tr style="' . $hh(10.16) . '"><td class="c b h12">' . e($c['result_statement'] ?? '') . '</td></tr>'
    . '<tr style="' . $hh(50.53) . '"><td class="rm">' . $rem . '</td></tr></table>';

// ---- sign-off
$insp = $m['inspector'];
$rev = $m['reviewer'];
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$im = fn ($p, $ok) => ($ok && $p && !empty($p['signature'])) ? '<img src="' . $p['signature'] . '" style="max-height:13mm;max-width:27mm;vertical-align:middle;margin:0 1.5mm" alt="">' : '';
$sigI = $im($insp, $signed);
$sigR = $im($rev, $m['issued']) . ($m['issued'] ? '<img src="' . $brand['stamp'] . '" style="width:19mm;height:19mm;vertical-align:middle;margin:0 1.5mm" alt="">' : '');   // inside the table cells, so they follow them
$sig = '';
$sig2 = '';
$stamp = '';
// names in the first rows, signatures (and the seal) in the row below, each under its own person
$t4 = '<table class="f lp gap" style="margin-top:5.18mm">' . doc_sizer([95.25, 95.25])
    . '<tr style="' . $hh(10.5) . '"><td class="l c h12 b">Test Witnessed by</td><td class="l c h12 b">Reviewed by</td></tr>'
    . '<tr style="' . $hh(10.5) . '"><td class="c">' . e($insp['name'] ?? '') . '</td><td class="c">' . ($m['issued'] ? e($rev['name'] ?? '') : '<span class="muted">Pending QC review</span>') . '</td></tr>'
    . '<tr style="' . $hh(22) . '"><td class="c">' . $sigI . '</td><td class="c">' . $sigR . '</td></tr></table>';

$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $fix($t1 . $t2 . $t3 . $t4),
        'top'   => 39.35,
        'left'  => 12.6,
        'width' => 190.5,
        'extra' => '<div class="cert-title lp-title">' . e($tpl['title']) . '</div>' . $stamp . $sig . $sig2,
    ]],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
