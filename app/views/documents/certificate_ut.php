<?php
/** Ultrasonic thickness report in the exact F-7.5.1-08 pattern. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');

// ---- details (two label/value pairs per row, as on the form)
$pairs = [
    ['Client Name', 'client', 'Inspection Location', 'location'],
    ['Applicable Procedure', 'procedure', 'Relevant Standard', 'standard'],
    ['Equipment Name', 'eq_name', 'Certificate No.', 'cert_no'],
    ['Equipment S.N', 'eq_sn', 'Calibration Due Date', 'calibration_due'],
    ['Devise S.N', 'device_sn', 'Measuring Unit Size', 'unit_size'],
    ['Probe Frequency', 'probe_freq', 'Certificate Date:', 'cert_date'],
    ['Inspection Date', 'insp_date', 'Next Inspection Date', 'next_date'],
];
$t1 = '<table class="f ut">' . doc_sizer([38, 61.2, 36.6, 54.2]);
foreach ($pairs as $r) {
    $t1 .= '<tr class="h63"><td class="l">' . e($r[0]) . '</td><td>' . $v($r[1]) . '</td><td class="l">' . e($r[2]) . '</td><td>' . $v($r[3]) . '</td></tr>';
}
$t1 .= '</table>';

// ---- photos (inside the box beside the readings)
$photos = array_slice(array_values($m['photos'] ?? []), 0, 4);
$n = count($photos);
$mw = $n <= 1 ? 110 : 55;
$mh = $n <= 2 ? 72 : 35;
$img = '<div class="ut-img">';
foreach ($photos as $p) $img .= '<img style="max-width:' . $mw . 'mm; max-height:' . $mh . 'mm" src="' . $p['src'] . '" alt="">';
$img .= '</div>';

// a reading cell is always one line (long text, e.g. template-preview placeholders, is shrunk and clipped so rows never grow)
$cell = function (string $k) use ($fv) {
    $t = trim((string) ($fv[$k] ?? ''));
    if ($t === '') return '';
    $len = mb_strlen($t);
    if ($len > 8) { $t = mb_substr($t, 0, 12); $st = 'font-size:6.5pt;'; } else { $st = ''; }
    return '<span style="white-space:nowrap;' . $st . '">' . e($t) . '</span>';
};

// ---- readings: points 1-10 left, 11-20 right
$rd = '<table class="f ut" style="margin-top:7.6mm">' . doc_sizer([11.6, 18.4, 13.3, 18.4, 13, 115.3])
    . '<tr class="hh"><td class="l c">Point<br>No.</td><td class="l c">Readings<br>(mm)</td><td class="l c">Point<br>No.</td><td class="l c">Readings<br>(mm)</td><td class="sp" rowspan="11"></td><td class="imgbox" rowspan="11">' . $img . '</td></tr>';
for ($i = 1; $i <= 10; $i++) {
    $a = sprintf('rd_%02d', $i);
    $b = sprintf('rd_%02d', $i + 10);
    $rd .= '<tr class="h61"><td class="c">' . $i . '</td><td class="c">' . $cell($a) . '</td><td class="c">' . ($i + 10) . '</td><td class="c">' . $cell($b) . '</td></tr>';
}
$rd .= '</table>';

// ---- statement
$st = '<table class="f ut" style="margin-top:7.6mm">' . doc_sizer([190]) . '<tr><td class="stmt">' . e($c['result_statement']) . '</td></tr></table>';

// ---- remarks + notes
$notes = '';
$cd = json_decode((string) ($c['data'] ?? ''), true) ?: [];
$lines = $m['notes'];
if (isset($cd['ut_notes']) && trim((string) $cd['ut_notes']) !== '') $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $cd['ut_notes'])), 'strlen'));
$cd0 = json_decode((string) ($c['data'] ?? ''), true) ?: [];
$remarksTxt = (string) (array_key_exists('cert_remarks', $cd0) ? $cd0['cert_remarks'] : ($c['remarks'] ?? ''));   // own Remarks \ Notes field; the Message to QC is not printed
$remarksTxt = trim(preg_replace('/[^\P{C}\n]+/u', '', str_replace("\r", '', $remarksTxt)));   // drop stray control / invisible characters (they print as an empty box in the PDF)
if ($remarksTxt !== '') $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $remarksTxt)), 'strlen'));   // what is typed in Remarks becomes the bullet list (replaces the standard notes)
foreach ($lines as $k => $line) $notes .= '<li' . ($k === count($lines) - 1 && count($lines) > 3 ? ' class="nb"' : '') . '>' . e($line) . '</li>';
$rm = '<table class="f ut" style="margin-top:7.6mm">' . doc_sizer([37, 153])
    . '<tr class="h76"><td class="l rm">Remarks \ Notes:</td><td></td></tr>'
    . ($notes ? '<tr><td colspan="2" class="nt">'
                . ($notes ? '<ul>' . $notes . '</ul>' : '') . '</td></tr>' : '') . '</table>';

// ---- sign-off
$insp = $m['inspector'];
$rev = $m['reviewer'];
$who = fn ($p) => $p ? e($p['name'] . ($p['qualification'] ? ' - ' . $p['qualification'] : '')) : '';
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sg = fn ($p, $show, $w = 36) => ($show && $p && !empty($p['signature'])) ? '<img class="sg" style="max-width:' . $w . 'mm" src="' . $p['signature'] . '" alt="">' : '';
$seal = $m['issued'] ? '<img src="' . $brand['stamp'] . '" style="width:15mm;height:15mm;vertical-align:middle;margin-left:1.5mm" alt="">' : '';   // seal sits inside the reviewer's signature cell, so it follows the table
$sign = '<table class="f ut" style="margin-top:7.6mm">' . doc_sizer([38, 62, 37, 53])
    . '<tr class="h128"><td class="l c">' . e($tpl['inspector_label']) . '</td><td class="c">' . $who($insp) . '</td><td class="l c" rowspan="2">Signature</td><td class="c sgc">' . $sg($insp, $signed) . '</td></tr>'
    . '<tr class="h128"><td class="l c">Reviewed By:</td><td class="c">' . ($m['issued'] ? $who($rev) : '<span class="muted">Pending QC review</span>') . '</td><td class="c sgc" style="height:17mm">' . $sg($rev, $m['issued'], 24) . $seal . '</td></tr></table>';

$stamp = '';
$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $t1 . $rd . $st . $rm . $sign,
        'top'   => 36,
        'left'  => 10.5,
        'width' => 190,
        'extra' => '<div class="cert-title ut-t">' . e($tpl['title']) . '</div>' . $stamp,
    ]],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
