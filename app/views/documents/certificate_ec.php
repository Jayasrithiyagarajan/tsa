<?php
/** Eddy Current certificate in the exact F-7.5.1-09 pattern. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');

// ---- block 1: certificate / customer details
$t1 = '<table class="f ec">' . doc_sizer([39.7, 55.6, 44.4, 44.4])
    . '<tr class="h85"><td colspan="2" rowspan="2" class="tl"><b class="lb">Tested according to:</b><div class="val" style="margin-top:2.1mm">' . $v('standard') . '</div></td>'
    . '<td class="l c">Certificate Date</td><td class="l c">Certificate No.</td></tr>'
    . '<tr class="h85"><td class="c val">' . $v('cert_date') . '</td><td class="c val">' . $v('cert_no') . '</td></tr>'
    . '<tr class="h85"><td class="l">Customer</td><td class="val">' . $v('client') . '</td><td class="l c">Inspection Date</td><td class="l c">Next Inspection Date</td></tr>'
    . '<tr class="h85"><td class="l">Location</td><td class="val">' . $v('location') . '</td><td class="c val">' . $v('insp_date') . '</td><td class="c val">' . $v('next_date') . '</td></tr>'
    . '</table>';

// ---- images (first four inside the form, the rest on a continuation sheet)
$photos = array_values($m['photos'] ?? []);
$first = array_slice($photos, 0, 4);
$rest = array_slice($photos, 4);
$n = count($first);
$mw = $n <= 1 ? 172 : 84;
$mh = $n <= 1 ? 66 : ($n === 2 ? 66 : 31);
$img = '<div class="ec-img">';
foreach ($first as $p) $img .= '<img style="max-width:' . $mw . 'mm; max-height:' . $mh . 'mm" src="' . $p['src'] . '" alt="">';
$img .= '</div>';

// ---- notes
$notes = '';
foreach ($m['notes'] as $line) $notes .= '<li>' . e($line) . '</li>';

// ---- sign-off
$insp = $m['inspector'];
$rev = $m['reviewer'];
$who = fn ($p) => $p ? e($p['name'] . ($p['qualification'] ? ' - ' . $p['qualification'] : '')) : '';
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sigs = '';
if ($signed && $insp && !empty($insp['signature'])) $sigs .= '<img class="sg" src="' . $insp['signature'] . '" alt="">';
if ($m['issued'] && $rev && !empty($rev['signature'])) $sigs .= '<img class="sg" src="' . $rev['signature'] . '" alt="">';
if ($m['issued']) $sigs .= '<img class="sp" src="' . $brand['stamp'] . '" alt="">';

$t2 = '<table class="f ec" style="margin-top:4.3mm">' . doc_sizer([39.7, 19.0, 4.2, 32.3, 27.5, 14.3, 47.1])
    . '<tr class="h78"><td class="l">Inspected Item</td><td colspan="6" class="val">' . $v('eq_item') . '</td></tr>'
    . '<tr class="h78"><td class="l">Identification / SN</td><td colspan="6" class="val">' . $v('eq_id') . '</td></tr>'
    . '<tr class="h66"><td class="l">Devise SN</td><td colspan="3" class="val">' . $v('device_sn') . '</td><td colspan="2" class="l c">Calibration Due Date</td><td class="c val">' . $v('calibration_due') . '</td></tr>'
    . '<tr class="gap"><td colspan="7"></td></tr>'
    . '<tr><td colspan="7" class="imgcell">' . $img . '</td></tr>'
    . '<tr class="h102"><td colspan="7" class="l trl">Test Result:</td></tr>'
    . '<tr><td colspan="7" class="stmt">' . e($c['result_statement']) . '</td></tr>'
    . '<tr class="h102"><td colspan="2" class="l rm">Remarks \ Notes:</td><td colspan="5" class="val">' . nl2br(e($c['remarks'])) . '</td></tr>'
    . ($notes ? '<tr><td colspan="7" class="nt"><ul>' . $notes . '</ul></td></tr>' : '')
    . '<tr class="h102"><td colspan="3" class="l ins">' . e(rtrim($tpl['inspector_label'], ': ')) . '</td><td colspan="2" class="val ins">' . $who($insp) . '</td><td colspan="2" rowspan="2" class="sgcell">' . $sigs . '</td></tr>'
    . '<tr class="h102"><td colspan="3" class="l ins">Approved by</td><td colspan="2" class="val ins">' . ($m['issued'] ? $who($rev) : '<span class="muted">Pending QC review</span>') . '</td></tr>'
    . '</table>';

$pages = [[
    'body'  => $t1 . $t2,
    'top'   => 34.3,
    'left'  => 12.8,
    'width' => 184.1,
    'extra' => '<div class="cert-title ec-t">' . e($tpl['title']) . '</div>',
]];

// The Word form (F-7.5.1-09) always has this second bordered sheet, so it is always output.
{
    $box = '<div class="ec-page2">';
    foreach ($rest as $p) $box .= '<img src="' . $p['src'] . '" alt="">';
    $box .= '</div>';
    $pages[] = ['body' => $box, 'top' => 25.4, 'left' => 12.8, 'width' => 182.6, 'extra' => ''];
}

$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => $pages,
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
