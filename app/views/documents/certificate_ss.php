<?php
/** Steel structure inspection report in the F-7.5.1-16 pattern (8 pages: report, 2 drawing boxes, 4 photo pages of 16 boxes, sign-off). @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$tx = fn ($k) => nl2br(e($fv[$k] ?? ''));
$lines = fn ($k) => array_values(array_filter(array_map('trim', preg_split('/\R/', $fv[$k] ?? '')), fn ($l) => $l !== ''));
$box = fn ($on, $label) => '<span class="radio">' . ($on ? '&#9746;' : '&#9744;') . '</span> ' . e($label);

$result = (string) ($c['result'] ?? '');
$testResult = ['accepted' => 'Passed', 'rejected' => 'Failed', 'repair' => 'Repair required'][$result] ?? '';
$satisfactory = $result === 'accepted';

// ---------------- page 1: details, stage, type, introduction, reference standards
$pairs = [
    ['Client', e($fv['client'] ?? ''), 'Report No.', e($fv['cert_no'] ?? '')],
    ['Address', $v('address'), 'Inspection Date', $v('insp_date')],
    ['Project', $v('project'), 'Inspection Location', $v('location')],
    ['Manufacuture', $v('manufacture'), 'Test Result', e($testResult)],
];
$t1 = '<table class="f sc">' . doc_sizer([30, 65, 34, 61]);
foreach ($pairs as $r) $t1 .= '<tr class="h82"><td class="l">' . $r[0] . '</td><td>' . $r[1] . '</td><td class="l">' . $r[2] . '</td><td>' . $r[3] . '</td></tr>';
$t1 .= '</table>';

$t2 = '<table class="f sc" style="margin-top:5mm">' . doc_sizer([30, 160])
    . '<tr class="h66"><td class="l">Inspected Item</td><td>' . $v('eq_item') . '</td></tr>'
    . '<tr class="h66"><td class="l">Item / Tag No.</td><td>' . $v('eq_tag') . '</td></tr>'
    . '<tr class="h66"><td class="l">QTY / Drawing No.</td><td>' . $v('qty_drawing') . '</td></tr></table>';

$choice = function (string $title, array $opts, array $picked) use ($box) {
    $h = '<table class="f sc" style="margin-top:5mm">' . doc_sizer([63.4, 63.3, 63.3]) . '<tr><td class="band c" colspan="3">' . e($title) . '</td></tr>';
    foreach (array_chunk($opts, 3) as $row) {
        $row = array_pad($row, 3, null);
        $h .= '<tr class="h86">';
        foreach ($row as $o) $h .= '<td>' . ($o === null ? '' : $box(in_array($o, $picked, true), strtoupper($o))) . '</td>';
        $h .= '</tr>';
    }
    return $h . '</table>';
};
$stage = $choice('STAGE OF INSPECTION', ['Before Manufacturing', 'During Manufacturing', 'Final', 'Packing', 'After Installed', 'In-Service Inspection'], [$fv['stage'] ?? '']);
$typeOpts = ['Pre-Inspection', 'Document and QC Record Review', 'Visual Examination/Checks', 'Witnessing Tests', 'Final Inspection'];
$typePicked = array_filter([$fv['type_insp'] ?? '', $fv['type_insp2'] ?? '']);
$types = $choice('TYPE OF INSPECTION', $typeOpts, $typePicked);

$intro = '<table class="f sc" style="margin-top:5mm">' . doc_sizer([190])
    . '<tr><td class="hd">INTRODUCTION:</td></tr><tr><td class="tx intro">' . $tx('intro') . '</td></tr></table>';

$std = '<table class="f sc" style="margin-top:5mm">' . doc_sizer([88, 50, 52])
    . '<tr><td class="band c" colspan="3">Reference Standard</td></tr>'
    . '<tr class="h66"><td class="l c">Title</td><td class="l c">Standard</td><td class="l c">Remarks</td></tr>';
foreach ($m['notes'] as $n) {
    $p = array_map('trim', explode('|', $n)) + ['', '', ''];
    $std .= '<tr class="h66"><td>' . e($p[0]) . '</td><td class="c">' . e($p[1]) . '</td><td class="c">' . e($p[2]) . '</td></tr>';
}
$std .= '</table>';

// ---------------- page 2: result, conclusions, attendees, scope, activities, certification
$res = '<table class="f sc">' . doc_sizer([95, 95])
    . '<tr><td class="band" colspan="2">RESULT OF INSPECTION:</td></tr>'
    . '<tr class="h86"><td class="c">' . $box($satisfactory, 'SATISFACTORY') . '</td><td class="c">' . $box($result !== '' && !$satisfactory, 'UNSATISFACTORY') . '</td></tr>'
    . '<tr><td class="tx" colspan="2"><div class="hd2">NON-CONFORMITIES REPORTS (NCR):</div>'
    . '<div class="ni"><span>1.</span> NCRs issued during reports: ' . $v('ncr_issued') . '</div>'
    . '<div class="ni"><span>2.</span> List of outstanding NCRs: ' . $v('ncr_outstanding') . '</div></td></tr></table>';

$concl = '';
foreach ($lines('conclusions') as $k => $l) $concl .= '<div class="ni"><span>' . ($k + 1) . '.</span> ' . e($l) . '</div>';
$cn = '<table class="f sc" style="margin-top:5mm">' . doc_sizer([190])
    . '<tr><td class="band">CONCLUSIONS AND REMARKS:</td></tr><tr><td class="tx concl">' . $concl . '</td></tr></table>';

$att = '<table class="f sc" style="margin-top:5mm">' . doc_sizer([63.4, 63.3, 63.3])
    . '<tr class="h66"><td class="hd c" colspan="3">ATTENDEES</td></tr>'
    . '<tr class="h66"><td class="l c">NAME</td><td class="l c">DESIGNATION</td><td class="l c">REPRESENTATIVE</td></tr>';
foreach ($lines('attendees') as $l) {
    $p = array_map('trim', explode('|', $l)) + ['', '', ''];
    $att .= '<tr class="h66"><td class="c">' . e($p[0]) . '</td><td class="c">' . e($p[1]) . '</td><td class="c">' . e($p[2]) . '</td></tr>';
}
$att .= '<tr class="h66"><td class="hd c" colspan="3">SCOPE OF INSPECTION</td></tr>'
    . '<tr class="h66"><td class="l c">INSPECTION METHOD</td><td class="l c">SCOPE</td><td class="l c">REMARKS</td></tr>'
    . '<tr class="h66"><td class="c">' . $v('scope_method') . '</td><td class="c">' . $v('scope_scope') . '</td><td class="c">' . $v('scope_remarks') . '</td></tr></table>';

$det = '<table class="f sc" style="margin-top:5mm">' . doc_sizer([190])
    . '<tr><td class="band hd">DETAIL OF INSPECTION ACTIVITIES</td></tr>'
    . '<tr><td class="band">VISUAL INSPECTION</td></tr>'
    . '<tr><td class="tx"><b>General Description:</b> ' . $v('gen_desc') . '</td></tr>'
    . '<tr><td class="tx act">' . $tx('activities') . '</td></tr></table>';

$cert = '<table class="f sc" style="margin-top:5mm">' . doc_sizer([190])
    . '<tr><td class="band c">RESULT OF INSPECTION</td></tr>'
    . '<tr><td class="tx fin"><div class="ni"><span>-</span> ' . e($c['result_statement']) . '.</div>'
    . '<div class="ni"><span>-</span> We hereby certify that the above item described in this certificate was tested and examined according to relevant standards and that the above particulars are correct.</div></td></tr></table>';

// ---------------- sign-off
$insp = $m['inspector'];
$rev = $m['reviewer'];
$who = fn ($p) => $p ? e($p['name'] . ($p['qualification'] ? ' - ' . $p['qualification'] : '')) : '';
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sgImg = ($signed && $insp && !empty($insp['signature'])) ? '<img class="sg" src="' . $insp['signature'] . '" alt="">' : '';
$stImg = $m['issued'] ? '<img class="sp" src="' . $brand['stamp'] . '" alt="">' : '';
$sign = '<table class="f sc" style="margin-top:8mm">' . doc_sizer([62, 128])
    . '<tr class="h110"><td class="l">' . e($tpl['inspector_label']) . '</td><td>' . $who($insp) . '</td></tr>'
    . '<tr class="h130"><td class="l">Signature:</td><td rowspan="2" class="c sgc">' . $sgImg . $stImg . '</td></tr>'
    . '<tr class="h130"><td class="l">Stamp:</td></tr>'
    . '<tr class="h110"><td class="l">Reviewed By:</td><td>' . ($m['issued'] ? $who($rev) : '<span class="muted">Pending QC review</span>') . '</td></tr></table>';

// ---------------- attachments: captions containing "draw" are drawings (one per page, max 2), the rest are photographs (max 64)
$drawings = $pics = [];
foreach ($m['photos'] ?? [] as $p) {
    if (preg_match('/draw/i', (string) ($p['caption'] ?? ''))) $drawings[] = $p; else $pics[] = $p;
}
$pages = [];
$title = '<div class="cert-title" style="top:24.6mm">' . e($tpl['title']) . '</div>';
$pages[] = ['body' => $t1 . $t2 . $stage . $types . $intro . $std, 'top' => 36, 'left' => 10.5, 'width' => 190, 'extra' => $title];
$pages[] = ['body' => $res . $cn . $att . $det . $cert, 'top' => 36, 'left' => 10.5, 'width' => 190];

// Fixed to the form: pages 3-4 = two drawing boxes, pages 5-8 = four photo pages of 4 x 4 boxes (64), sign-off on page 8. Extra images are ignored.
$drawTitle = trim($fv['draw_title'] ?? '') !== '' ? $v('draw_title') : 'Structure Visual inspection Picture As Per Drawing &amp; Site Visit :';
for ($i = 0; $i < 2; $i++) {
    $d = $drawings[$i] ?? null;
    $head = $i === 0 ? '<div class="sc-h">' . $drawTitle . '</div>' : '';
    $pages[] = ['body' => $head . '<div class="sc-draw' . ($i === 0 ? ' first' : '') . '">' . ($d ? '<img src="' . $d['src'] . '" alt="">' : '') . '</div>',
        'top' => 36, 'left' => 10.5, 'width' => 190];
}

$pics = array_slice($pics, 0, 64);
for ($i = 0; $i < 4; $i++) {
    $chunk = array_slice($pics, $i * 16, 16);
    $last = $i === 3;
    $g = '<table class="f sc-ph ss' . ($last ? ' last' : '') . '">' . doc_sizer([47.5, 47.5, 47.5, 47.5]);
    for ($r = 0; $r < 4; $r++) {
        $g .= '<tr>';
        for ($k = 0; $k < 4; $k++) {
            $p = $chunk[$r * 4 + $k] ?? null;
            $g .= '<td class="c">' . ($p ? '<img src="' . $p['src'] . '" alt="">' : '') . '</td>';
        }
        $g .= '</tr>';
    }
    $g .= '</table>';
    $pages[] = ['body' => $g . ($last ? $sign : ''), 'top' => 36, 'left' => 10.5, 'width' => 190];
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
