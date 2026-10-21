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
$sig = ($signed && $insp && !empty($insp['signature'])) ? '<img class="lp-sig" style="left:119.7mm;top:215.7mm" src="' . $insp['signature'] . '" alt="">' : '';
$sig2 = ($m['issued'] && $rev && !empty($rev['signature'])) ? '<img class="lp-sig" style="left:119.7mm;top:228.4mm" src="' . $rev['signature'] . '" alt="">' : '';
$stamp = $m['issued'] ? '<img class="stamp" style="left:170.75mm; top:216.65mm; width:28mm; height:28mm" src="' . $brand['stamp'] . '" alt="">' : '';
$t4 = '<table class="f lp gap" style="margin-top:5.18mm">' . doc_sizer([47.6, 47.6, 95.3])
    . '<tr style="' . $hh(12.7) . '"><td class="l c h12 b">Test Witnessed by</td><td class="c">' . e($insp['name'] ?? '') . '</td><td class="l c h12 b">Signature / Stamp</td></tr>'
    . '<tr style="' . $hh(12.7) . '"><td class="l c h12 b">Reviewed by</td><td class="c">' . ($m['issued'] ? e($rev['name'] ?? '') : '<span class="muted">Pending QC review</span>') . '</td><td></td></tr></table>';

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
