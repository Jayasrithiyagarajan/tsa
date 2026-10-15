<?php
/** Certificate / report in the exact F-7.5.1-06 pattern. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

// header grid: label/value pairs, two pairs per row
$grid = '<table class="f grid">' . doc_sizer([37.6, 60.7, 41.3, 49]);
foreach (array_chunk($m['fields'], 2) as $pair) {
    $grid .= '<tr>';
    foreach ($pair as $f) {
        $grid .= '<td class="l">' . e($f['label']) . '</td><td>' . e($f['value']) . '</td>';
    }
    if (count($pair) === 1) $grid .= '<td class="l"></td><td></td>';
    $grid .= '</tr>';
}
$grid .= '</table>';

$img = '<div class="imgarea">';
foreach ($m['photos'] as $p) {
    $img .= '<img src="' . $p['src'] . '" alt="">';
}
$img .= '</div>';
if (!$tpl['show_image_area']) $img = '';

$labels = ['accepted' => 'Accepted', 'rejected' => 'Rejected', 'repair' => 'Repair'];
$opts = '';
foreach ($m['options'] as $o) {
    $o = trim($o);
    $on = $c['result'] === $o;
    $opts .= '<td><span class="o ' . e($o) . '"><span class="radio">' . ($on ? '&#9673;' : '&#9711;') . '</span> ' . e($labels[$o] ?? ucfirst($o)) . '</span></td>';
}
$n = max(1, count($m['options']));
$result = '<table class="f result">' . doc_sizer(array_fill(0, $n, 188.6 / $n)) . '<tr class="h"><td colspan="' . $n . '">Result</td></tr>'
    . '<tr class="opt">' . $opts . '</tr>'
    . '<tr class="stmt"><td colspan="' . $n . '">' . e($c['result_statement']) . '</td></tr></table>';

$notes = '';
foreach ($m['notes'] as $line) $notes .= '<li>' . e($line) . '</li>';
$remarks = '<table class="f remarks">' . doc_sizer([35.5, 153.1]) . '<tr><td class="l">Remarks \ Notes</td><td class="v">' . nl2br(e($c['remarks'])) . '</td></tr>'
    . ($notes ? '<tr class="notes"><td colspan="2"><ul>' . $notes . '</ul></td></tr>' : '') . '</table>';

$insp = $m['inspector'];
$rev = $m['reviewer'];
$who = fn ($p) => $p ? e($p['name'] . ($p['qualification'] ? ' - ' . $p['qualification'] : '')) : '';
$sigImg = fn ($p, $show) => ($show && $p && $p['signature']) ? '<img class="sigimg" src="' . $p['signature'] . '" alt="">' : '';
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sign = '<table class="f signblock">' . doc_sizer([35.5, 59, 36, 58.1])
    . '<tr><td class="l">' . e($tpl['inspector_label']) . '</td><td>' . $who($insp) . '</td><td class="l" rowspan="2">Signature</td><td class="sigcell">' . $sigImg($insp, $signed) . '</td></tr>'
    . '<tr><td class="l">Reviewed By:</td><td>' . ($m['issued'] ? $who($rev) : '<span class="muted">Pending QC review</span>') . '</td><td class="sigcell">' . $sigImg($rev, $m['issued']) . '</td></tr></table>';

$stamp = $m['issued'] ? '<img class="stamp" style="left:175mm; top:246mm" src="' . $brand['stamp'] . '" alt="">' : '';

$body = $grid . $img . $result . $remarks . $sign;
$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $body,
        'top'   => 39.7,
        'left'  => 14,
        'width' => 188.6,
        'extra' => '<div class="cert-title">' . e($tpl['title']) . '</div>' . $stamp,
    ]],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['issued'] ? $m['qr'] : null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
