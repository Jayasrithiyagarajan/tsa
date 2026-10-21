<?php
/** Service / Delivery Receipt (F-7.5.1-D 03), one page: one table with 15 numbered item rows. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$hh = fn ($mm) => 'height:' . round($mm - 0.18, 2) . 'mm';
// Dompdf ignores a height on <tr>: copy it onto every cell of the row
$fix = fn (string $t) => preg_replace_callback('~<tr style="(height:[^"]+)">(.*?)</tr>~s', fn ($mm) => '<tr>' . preg_replace('~<td\b~', '<td style="' . $mm[1] . '"', $mm[2]) . '</tr>', $t);

// items: one line per row, "description | unit | qty | remarks"
$rows = preg_split('/\R/u', trim((string) ($fv['items'] ?? ''))) ?: [];
$cell = fn ($t) => e(trim((string) $t));

$sp = fn ($mm) => '<tr style="' . $hh($mm) . '"><td colspan="6" class="nb"></td></tr>';
$t = '<table class="f dr">' . doc_sizer([10.0, 18.9, 71.4, 15.9, 15.9, 55.6])
    . '<tr style="' . $hh(7.62) . '"><td colspan="6" class="l c t14">' . 'TRADING UTILITY VENDOR EST' . '</td></tr>'
    . '<tr style="' . $hh(7.62) . '"><td colspan="6" class="c t14">' . e($tpl['title']) . '</td></tr>'
    . $sp(5.2)
    . '<tr style="' . $hh(10.16) . '"><td colspan="5" class="h12">CLIENT NAME: &nbsp;&nbsp;<span class="n10">' . $v('client') . '</span></td><td class="h12">Date: &nbsp;&nbsp;<span class="n10">' . $v('date') . '</span></td></tr>'
    . $sp(5.2)
    . '<tr style="' . $hh(7.62) . '"><td class="h12 b">No.</td><td colspan="2" class="c h12">Item Description</td><td class="c h12">Unit</td><td class="c h12">QTY.</td><td class="c h12">Remarks / Serial number</td></tr>';
for ($i = 0; $i < 15; $i++) {
    $p = array_pad(array_map('trim', explode('|', $rows[$i] ?? '')), 4, '');
    $t .= '<tr style="' . $hh(7.62) . '"><td class="c b">' . sprintf('%02d', $i + 1) . '</td><td colspan="2">' . $cell($p[0]) . '</td><td class="c">' . $cell($p[1]) . '</td><td class="c">' . $cell($p[2]) . '</td><td>' . $cell($p[3]) . '</td></tr>';
}
$t .= $sp(10.16)
    . '<tr style="' . $hh(7.62) . '"><td colspan="2" class="l c b">Received By:</td><td>' . $v('received_by') . '</td><td colspan="2" class="c b">Sign.</td><td></td></tr>'
    . '<tr style="' . $hh(7.62) . '"><td colspan="2" class="l c b">Delivered By:</td><td>' . $v('delivered_by') . '</td><td colspan="2" class="c b">Sign.</td><td></td></tr></table>';

$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $fix($t),
        'top'   => 26.1,
        'left'  => 9.07,
        'width' => 187.7,
    ]],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
