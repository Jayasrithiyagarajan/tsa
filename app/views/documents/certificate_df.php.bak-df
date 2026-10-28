<?php
/** Defect Report in the F-7.5.1-D 01 pattern (2 pages: report, four photo boxes). @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$lines = fn ($k) => array_values(array_filter(array_map('trim', preg_split('/\R/u', $fv[$k] ?? '')), fn ($l) => $l !== ''));

// ---- header grid (label / value, two pairs per row)
$pairs = [
    ['Client Name', $v('client'), 'Report No.', $v('cert_no')],
    ['Client Address', $v('client_address'), 'Job Order No.', $v('job_order')],
    ['Date Of Certificate', $v('cert_date'), 'Inspection Location', $v('location')],
    ['Inspection Date', $v('insp_date'), 'Manufacturer Name', $v('manufacturer')],
    ['Identification No.', $v('ident_no'), 'Capacity / SWL', $v('capacity')],
    ['Type of Inspection', $v('insp_type'), 'Date of Last Examination / Load Test', $v('last_exam')],
    ['Equipment Type', $v('eq_type'), 'Applicable Standard', $v('standard')],
];
$t1 = '<table class="f df">' . doc_sizer([34.7, 50.8, 58.7, 46]);
foreach ($pairs as $i => $r) {
    $t1 .= '<tr class="' . ($i === 6 ? 'h87' : 'h63') . '"><td class="l">' . $r[0] . '</td><td>' . $r[1] . '</td><td class="l">' . $r[2] . '</td><td>' . $r[3] . '</td></tr>';
}
$t1 .= '</table>';

$intro = '<div class="intro">The equipment mentioned below was inspected according to the requirements of International Standards, an annual examination was carried out and the report is as follows.</div>';

// ---- item table
$desc = $lines('item_desc');
$descHtml = '';
foreach ($desc as $k => $l) $descHtml .= ($k === 0 ? '<b>' . e($l) . '</b><br><br>' : e($l) . '<br>');
$reasons = '';
foreach ($lines('item_reason') as $l) $reasons .= e($l) . '<br>';
$t2 = '<table class="f df" style="margin-top:2.6mm">' . doc_sizer([34.9, 54, 22.9, 78.7])
    . '<tr class="h76"><td class="band c">Serial No.</td><td class="band c">Description</td><td class="band c">SWL</td><td class="band c">Reason of Rejection</td></tr>'
    . '<tr class="itm"><td class="c b">' . $v('item_serial') . '</td><td class="tp">' . $descHtml . '</td><td class="c b">' . $v('item_swl') . '</td><td class="tp">' . $reasons . '</td></tr></table>';

// ---- result sentence ("NOT SAFE FOR USE" is shown in red)
$stmt = e($c['result_statement'] ?? '');
$stmt = str_replace('NOT SAFE FOR USE', '<span class="red">NOT SAFE FOR USE</span>', $stmt);
$res = '<div class="res"><b>Result:</b> ' . $stmt . '</div>';

// ---- notes (template notes, one bullet per line)
$notes = '';
foreach ($m['notes'] as $n) $notes .= '<div class="nl"><span class="bu">&bull;</span>' . e($n) . '</div>';
$t3 = $notes ? '<table class="f df nt">' . doc_sizer([190]) . '<tr><td>' . $notes . '</td></tr></table>' : '';

// ---- sign-off
$insp = $m['inspector'];
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sgImg = ($signed && $insp && !empty($insp['signature'])) ? '<img class="sg" src="' . $insp['signature'] . '" alt="">' : '';
$rej = (($c['result'] ?? '') === 'rejected') ? '<span class="rej">REJECTED</span>' : '';
$t4 = '<table class="f df" style="margin-top:6.4mm">' . doc_sizer([41.1, 47.8, 48.3, 52.9])
    . '<tr class="h76"><td class="band c">Title</td><td class="band c">Name</td><td class="band c">Signature</td><td class="band c">Company Stamp</td></tr>'
    . '<tr class="sgn"><td class="c b">Inspector /<br>Technician</td><td class="c">' . e($insp['name'] ?? '') . '</td><td class="c">' . $sgImg . '</td><td class="c">' . $rej . '</td></tr></table>';

// ---- page 2: four photo boxes (2 x 2), captions are not printed
$pics = array_slice($m['photos'] ?? [], 0, 4);
$g = '<table class="f df ph">' . doc_sizer([95.2, 88.9]);
for ($r = 0; $r < 2; $r++) {
    $g .= '<tr>';
    for ($k = 0; $k < 2; $k++) {
        $p = $pics[$r * 2 + $k] ?? null;
        $g .= '<td class="c">' . ($p ? '<img src="' . $p['src'] . '" alt="">' : '') . '</td>';
    }
    $g .= '</tr>';
}
$g .= '</table>';

$title = '<div class="df-title">Defect Report</div>';
$pages = [
    ['body' => $t1 . $intro . $t2 . $res . $t3 . $t4, 'top' => 37.7, 'left' => 10.5, 'width' => 190, 'extra' => $title],
    ['body' => $g, 'top' => 38, 'left' => 10.5, 'width' => 190, 'extra' => $title],
];
$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' - ' . ($c['certificate_no'] ?? ''),
    'pages'     => $pages,
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
