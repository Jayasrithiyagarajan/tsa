<?php
/** Scaffolding inspection report in the F-7.5.1-15 pattern (multi-page: report, drawings, photos, sign-off). @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$tx = fn ($k) => nl2br(e($fv[$k] ?? ''));
$lines = fn ($k) => array_values(array_filter(array_map('trim', preg_split('/\R/u', $fv[$k] ?? '')), fn ($l) => $l !== ''));
$box = fn ($on, $label) => '<span class="radio">' . ($on ? '&#9746;' : '&#9744;') . '</span> ' . e($label);

$result = (string) ($c['result'] ?? '');
$testResult = ['accepted' => 'Passed', 'rejected' => 'Failed', 'repair' => 'Repair required'][$result] ?? '';
$satisfactory = $result === 'accepted';

// ---------------- page 1: details, stage, type, introduction, reference standards
// Dimensions are measured from the Word form F-7.5.1-15 (tables 183.1 mm wide, centred). Scoped by the "scf" class.
$pairs = [
    ['Client', e($fv['client'] ?? ''), 'Report No.', e($fv['cert_no'] ?? '')],
    ['Address', $v('address'), 'Inspection Date', $v('insp_date')],
    ['Project', $v('project'), 'Inspection Location', $v('location')],
    ['Manufacuture', $v('manufacture'), 'Test Result', e($testResult)],
];
$t1 = '<table class="f scf">' . doc_sizer([38.6, 54.9, 36.5, 53.1]);
foreach ($pairs as $r) $t1 .= '<tr class="h76"><td class="l">' . $r[0] . '</td><td>' . $r[1] . '</td><td class="l">' . $r[2] . '</td><td>' . $r[3] . '</td></tr>';
$t1 .= '</table>';

$t2 = '<table class="f scf g54">' . doc_sizer([39.5, 143.6])
    . '<tr class="h76"><td class="l">Inspected Item</td><td>' . $v('eq_item') . '</td></tr>'
    . '<tr class="h76"><td class="l">Item / Tag No.</td><td>' . $v('eq_tag') . '</td></tr>'
    . '<tr class="h76"><td class="l">QTY / Drawing No.</td><td>' . $v('qty_drawing') . '</td></tr></table>';

// one option per cell, no inner vertical rules (the form lays these out with tab stops in a single box)
$choice = function (string $title, array $opts, array $picked, array $cols, string $r1, string $r2) use ($box) {
    $h = '<table class="f scf g53 chs">' . doc_sizer($cols) . '<tr><td class="band c" colspan="3">' . e($title) . '</td></tr>';
    foreach (array_chunk($opts, 3) as $i => $row) {
        $row = array_pad($row, 3, null);
        $h .= '<tr class="' . ($i === 0 ? $r1 : $r2) . '">';
        foreach ($row as $o) $h .= '<td class="ck">' . ($o === null ? '' : $box(in_array($o, $picked, true), strtoupper($o))) . '</td>';
        $h .= '</tr>';
    }
    return $h . '</table>';
};
$stage = $choice('STAGE OF INSPECTION', ['Before Manufacturing', 'During Manufacturing', 'Final', 'Packing', 'After Installed', 'In-Service Inspection'], [$fv['stage'] ?? ''], [59.0, 66.5, 57.6], 'rs1', 'rs2');
// the form has separate VISUAL and EXAMINATION/CHECKS boxes; the stored option "Visual Examination/Checks" ticks VISUAL
$typePicked = array_map(fn ($x) => $x === 'Visual Examination/Checks' ? 'Visual' : $x, array_filter([$fv['type_insp'] ?? '', $fv['type_insp2'] ?? '']));
$types = $choice('TYPE OF INSPECTION', ['Pre-Inspection', 'Document and QC Record Review', 'Visual', 'Examination/Checks', 'Witnessing Tests', 'Final Inspection'], $typePicked, [57.1, 68.8, 57.2], 'rt1', 'rt2');

$intro = '<table class="f scf g53">' . doc_sizer([183.1])
    . '<tr><td class="hd ih">INTRODUCTION:</td></tr><tr><td class="tx ib">' . $tx('intro') . '</td></tr></table>';

$std = '<table class="f scf g40">' . doc_sizer([68.2, 44.3, 70.6])
    . '<tr><td class="band c" colspan="3">Reference Standard</td></tr>'
    . '<tr class="h77"><td class="hb c">Title</td><td class="hb c">Standard</td><td class="hb c">Remarks</td></tr>';
foreach ($m['notes'] as $n) {
    $p = array_map('trim', explode('|', $n)) + ['', '', ''];
    $std .= '<tr class="h76"><td>' . e($p[0]) . '</td><td class="c">' . e($p[1]) . '</td><td class="c">' . e($p[2]) . '</td></tr>';
}
$std .= '</table>';

// ---------------- page 2: result, conclusions, attendees, scope, activities, certification
$res = '<table class="f scf">' . doc_sizer([91.55, 91.55])
    . '<tr><td class="band" colspan="2">RESULT OF INSPECTION:</td></tr>'
    . '<tr class="rk"><td class="ck2" colspan="2"><span class="k1">' . $box($satisfactory, 'SATISFACTORY') . '</span>' . $box($result !== '' && !$satisfactory, 'UNSATISFACTORY') . '</td></tr>'
    . '<tr class="rn"><td class="tx" colspan="2"><div class="hd2">NON-CONFORMITIES REPORTS (NCR):</div>'
    . '<div class="ni"><span>1.</span> NCRs issued during reports: ' . $v('ncr_issued') . '</div>'
    . '<div class="ni"><span>2.</span> List of outstanding NCRs: ' . $v('ncr_outstanding') . '</div></td></tr>';

$concl = '';
foreach ($lines('conclusions') as $k => $l) $concl .= '<div class="ni"><span>' . ($k + 1) . '.</span> ' . e($l) . '</div>';
// conclusions continue the result box (shared border, as in the form)
$res .= '<tr><td class="band" colspan="2">CONCLUSIONS AND REMARKS:</td></tr><tr><td class="tx cb" colspan="2">' . $concl . '</td></tr></table>';

$att = '<table class="f scf g70">' . doc_sizer([66.8, 38.2, 78.1])
    . '<tr class="h76"><td class="hd0 c" colspan="3">ATTENDEES</td></tr>'
    . '<tr class="h79"><td class="l c">NAME</td><td class="l c">DESIGNATION</td><td class="l c">REPRESENTATIVE</td></tr>';
foreach ($lines('attendees') as $l) {
    $p = array_map('trim', explode('|', $l)) + ['', '', ''];
    $att .= '<tr class="h75"><td class="c">' . e($p[0]) . '</td><td class="c">' . e($p[1]) . '</td><td class="c">' . e($p[2]) . '</td></tr>';
}
$att .= '<tr class="h75"><td class="hd0 c" colspan="3">SCOPE OF INSPECTION</td></tr>'
    . '<tr class="h85"><td class="l c">INSPECTION METHOD</td><td class="l c">SCOPE</td><td class="l c">REMARKS</td></tr>'
    . '<tr class="h78"><td class="c">' . $v('scope_method') . '</td><td class="c">' . $v('scope_scope') . '</td><td class="c">' . $v('scope_remarks') . '</td></tr></table>';

$det = '<table class="f scf g49">' . doc_sizer([183.1])
    . '<tr><td class="hd dh">DETAIL OF INSPECTION ACTIVITIES</td></tr>'
    . '<tr><td class="band bh">VISUAL INSPECTION</td></tr>'
    . '<tr><td class="tx dg"><b>General Description:</b> ' . $v('gen_desc') . '</td></tr>'
    . '<tr><td class="tx da">' . $tx('activities') . '</td></tr></table>';

$cert = '<table class="f scf g49">' . doc_sizer([183.1])
    . '<tr><td class="band c bh2">RESULT OF INSPECTION</td></tr>'
    . '<tr><td class="tx fn"><div class="ni dash"><span>-</span> ' . e($c['result_statement']) . '.</div>'
    . '<div class="ni dash"><span>-</span> We hereby certify that the above item described in this certificate was tested and examined according to relevant standards and that the above particulars are correct.</div></td></tr></table>';

// ---------------- sign-off
$insp = $m['inspector'];
$rev = $m['reviewer'];
$who = fn ($p) => $p ? e($p['name'] . ($p['qualification'] ? ' - ' . $p['qualification'] : '')) : '';
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sgImg = ($signed && $insp && !empty($insp['signature'])) ? '<img class="sg" src="' . $insp['signature'] . '" alt="">' : '';
$stImg = $m['issued'] ? '<img class="sp" src="' . $brand['stamp'] . '" alt="">' : '';
$sign = '<table class="f scf sgn" style="width:174.7mm; margin-left:-1.6mm; margin-top:17.8mm">' . doc_sizer([87.4, 87.3])
    . '<tr class="h75"><td class="sl">' . e($tpl['inspector_label']) . '</td><td>' . $who($insp) . '</td></tr>'
    . '<tr class="h75"><td class="sl">Signature:</td><td rowspan="2" class="c sgc">' . $sgImg . $stImg . '</td></tr>'
    . '<tr class="h75"><td class="sl">Stamp:</td></tr>'
    . '<tr class="h76"><td class="sl">Reviewed By:</td><td>' . ($m['issued'] ? $who($rev) : '<span class="muted">Pending QC review</span>') . '</td></tr></table>';

// ---------------- attachments: captions containing "draw" are drawings (one per page, max 3), the rest are photographs (max 16)
$drawings = $pics = [];
foreach ($m['photos'] ?? [] as $p) {
    if (preg_match('/draw/i', (string) ($p['caption'] ?? ''))) $drawings[] = $p; else $pics[] = $p;
}
$pages = [];
$title = '<div class="cert-title scf-t">' . e($tpl['title']) . '</div>';
$pages[] = ['body' => $t1 . $t2 . $stage . $types . $intro . $std, 'top' => 37.6, 'left' => 13.45, 'width' => 183.1, 'extra' => $title];
$pages[] = ['body' => $res . $att . $det . $cert, 'top' => 25.5, 'left' => 13.45, 'width' => 183.1];

// Fixed to the form: pages 3-5 = three drawing boxes, pages 6-8 = 6 + 6 + 4 photo boxes (16), sign-off on page 8. Extra images are ignored.
// [body top, box left, box width, heading indent] - measured per page from the form (A4 centring applied)
$dg = [[30.2, 17.65, 169.8, 4.8], [41.8, 22.55, 164.9, 0], [41.8, 20.85, 166.6, 0]];
for ($i = 0; $i < 3; $i++) {
    $d = $drawings[$i] ?? null;
    [$top, $left, $w, $ind] = $dg[$i];
    $head = $i === 0 ? '<div class="scf-h" style="padding-left:' . $ind . 'mm">Scaffolding Structure Darwing:</div>' : '';
    $pages[] = ['body' => $head . '<div class="scf-draw">' . ($d ? '<img src="' . $d['src'] . '" alt="">' : '') . '</div>',
        'top' => $top, 'left' => $left, 'width' => $w];
}

$pics = array_slice($pics, 0, 16);
foreach ([6, 6, 4] as $i => $cells) {
    $chunk = array_slice($pics, array_sum(array_slice([6, 6, 4], 0, $i)), $cells);
    $rows = $cells / 2;
    $head = $i === 0 ? '<div class="scf-h" style="padding-left:3.2mm">Scaffolding Visual inspection Picture as Per Darwing:</div>' : '';
    $g = '<table class="f scf scf-ph">' . doc_sizer([84.1, 84.1]);
    for ($r = 0; $r < $rows; $r++) {
        $g .= '<tr>';
        for ($k = 0; $k < 2; $k++) {
            $p = $chunk[$r * 2 + $k] ?? null;
            $g .= '<td class="c">' . ($p ? '<img src="' . $p['src'] . '" alt="">' : '') . '</td>';
        }
        $g .= '</tr>';
    }
    $g .= '</table>';
    $pages[] = ['body' => $head . $g . ($i === 2 ? $sign : ''), 'top' => [33.6, 44.5, 49.2][$i], 'left' => 19.25, 'width' => 168.2];
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
