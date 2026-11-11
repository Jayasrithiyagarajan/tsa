<?php
/** Anemometer calibration certificate (F-7.5.1-11, exact layout of the supplied PDF), one page. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$hh = fn ($mm) => 'height:' . round($mm - 0.18, 2) . 'mm';
// Dompdf ignores a height on <tr>: copy it onto every cell of the row
$fix = fn (string $t) => preg_replace_callback('~<tr style="(height:[^"]+)">(.*?)</tr>~s', fn ($mm) => '<tr>' . preg_replace('~<td\b~', '<td style="' . $mm[1] . '"', $mm[2]) . '</tr>', $t);
$W = 186.2;

// ---- block 1: procedure, certificate date / number
$t1 = '<table class="f an">' . doc_sizer([96.6, 44.9, 44.7])
    . '<tr style="' . $hh(5.05) . '"><td class="b">Tested in accordance / Procedure:</td><td class="l c">Certificate Date</td><td class="l c">Certificate No.</td></tr>'
    . '<tr style="' . $hh(5.05) . '"><td>' . $v('standard') . '</td><td class="c">' . $v('cert_date') . '</td><td class="c">' . $v('cert_no') . '</td></tr></table>';

// ---- block 2: customer / calibration dates
$t2 = '<table class="f an" style="margin-top:4.27mm">' . doc_sizer([40, 56.6, 44.9, 44.7])
    . '<tr style="' . $hh(5.27) . '"><td class="l">Customer:</td><td>' . $v('client') . '</td><td class="l c">Calibration Date</td><td class="l c">Calibration Due Date</td></tr>'
    . '<tr style="' . $hh(5.27) . '"><td class="l">Location:</td><td>' . $v('location') . '</td><td class="c">' . $v('cal_date') . '</td><td class="c">' . $v('cal_due') . '</td></tr></table>';

// ---- block 3: examined item
$pairs = [
    ['Examined Item', 'eq_item', 'Customer Order No.', 'po'],
    ['Identification / SN', 'eq_id', 'Manufacturer', 'manufacturer'],
    ['Temperature', 'temp', 'Calibration Result', 'cal_result'],
    ['Relative Humidity', 'humidity', 'Observations', 'observations'],
];
$t3 = '<table class="f an" style="margin-top:4.27mm">' . doc_sizer([40, 56.6, 44.9, 44.7]);
foreach ($pairs as $r) $t3 .= '<tr style="' . $hh(5.17) . '"><td class="l">' . $r[0] . '</td><td>' . $v($r[1]) . '</td><td class="l">' . $r[2] . '</td><td>' . $v($r[3]) . '</td></tr>';
$t3 .= '</table>';

// ---- block 4: ID / SN, qty, calibration data (first line bold)
$data = preg_split('/\R/u', trim($fv['cal_data'] ?? ''));
$first = e(array_shift($data) ?? '');
$rest = implode('<br>', array_map('e', $data));
$t4 = '<table class="f an" style="margin-top:4.27mm">' . doc_sizer([40, 20, 126.2])
    . '<tr style="' . $hh(5.4) . '"><td class="l c">ID / SN</td><td class="l c">Qty</td><td class="l c">Calibration Data</td></tr>'
    . '<tr style="' . $hh(24.7) . '"><td class="c b">' . $v('eq_id') . '</td><td class="c b">' . $v('qty') . '</td><td class="cd"><b>' . $first . '</b>' . ($rest !== '' ? '<br><span class="sm">' . $rest . '</span>' : '') . '</td></tr></table>';

// ---- air velocity readings + traceability (one 7-column grid)
$t5 = '<table class="f an" style="margin-top:-0.2mm">' . doc_sizer([30.5, 24.4, 21.6, 24, 31.2, 27.8, 26.5])
    . '<tr style="' . $hh(5.8) . '"><td colspan="7" class="l c">Air Velocity</td></tr>'
    . '<tr style="' . $hh(5.6) . '"><td class="l c nb">Normal Value</td><td colspan="3" class="l c">Measured Value (m/s)</td><td rowspan="2" class="l c">Means (m/s)</td><td rowspan="2" class="l c">Error</td><td rowspan="2" class="l c">Remarks</td></tr>'
    . '<tr style="' . $hh(5.6) . '"><td class="l c nt">(m/s)</td><td class="l c">Trail. 1</td><td class="l c">Trail. 2</td><td class="l c">Trail. 3</td></tr>';
foreach ([1, 2, 3] as $i) {
    $t5 .= '<tr style="' . $hh(5.17) . '"><td class="c b">' . $v("n$i") . '</td><td class="c b">' . $v("r{$i}_1") . '</td><td class="c b">' . $v("r{$i}_2") . '</td><td class="c b">' . $v("r{$i}_3") . '</td><td class="c b">' . $v("m$i") . '</td><td class="c b">' . $v("e$i") . '</td><td class="c b">' . $v("k$i") . '</td></tr>';
}
$t5 .= '<tr style="' . $hh(5.6) . '"><td colspan="7" class="l c">Traceability Information</td></tr>'
    . '<tr style="' . $hh(4.7) . '"><td class="l c">Ref. Instrument</td><td colspan="2" class="l c">Manufacturer</td><td class="l c">ID No.</td><td colspan="2" class="l c">Calibration Certificate</td><td class="l c">Expiration</td></tr>'
    . '<tr style="' . $hh(5.6) . '"><td class="c b">' . $v('tr_ref') . '</td><td colspan="2" class="c b">' . $v('tr_mfr') . '</td><td class="c b">' . $v('tr_id') . '</td><td colspan="2" class="c b">' . $v('tr_cert') . '</td><td class="c b">' . $v('tr_exp') . '</td></tr></table>';

// ---- notes
$notes = '';
foreach ((array) ($m['notes'] ?? []) as $line) { $line = trim((string) $line); if ($line !== '') $notes .= '<div class="nl">- ' . e($line) . '</div>'; }
$t6 = '<table class="f an" style="margin-top:4.9mm">' . doc_sizer([40, 146.2])
    . '<tr style="' . $hh(21.1) . '"><td class="c b">Notes:</td><td class="nt2">' . $notes . '</td></tr></table>';

// ---- sign-off (signature cell on the right, stamp underneath)
$insp = $m['inspector'];
$rev = $m['reviewer'];
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sig = ($signed && $insp && !empty($insp['signature'])) ? '<img class="lp-sig" style="left:124mm;top:200.4mm" src="' . $insp['signature'] . '" alt="">' : '';
$sig2 = ($m['issued'] && $rev && !empty($rev['signature'])) ? '<img class="lp-sig" style="left:148mm;top:209.4mm" src="' . $rev['signature'] . '" alt="">' : '';
$stamp = $m['issued'] ? '<img class="stamp" style="left:121mm; top:212.5mm; width:28mm; height:28mm" src="' . $brand['stamp'] . '" alt="">' : '';
$t7 = '<table class="f an" style="margin-top:4.5mm">' . doc_sizer([102.8, 83.4])
    . '<tr style="' . $hh(8.4) . '"><td class="b">' . e(rtrim($tpl['inspector_label'], ': ')) . ':</td><td rowspan="3"></td></tr>'
    . '<tr style="' . $hh(8.4) . '"><td class="b">Reviewed / Approved by:</td></tr>'
    . '<tr style="' . $hh(8.4) . '"><td class="b">Stamp:</td></tr></table>';

$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $fix($t1 . $t2 . $t3 . $t4 . $t5 . $t6 . $t7),
        'top'   => 39.7,
        'left'  => 11.9,
        'width' => $W,
        'extra' => '<div class="cert-title an-title">' . e($tpl['title']) . '</div>' . $stamp . $sig . $sig2,
    ]],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
