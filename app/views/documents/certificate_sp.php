<?php
/** Certificate of thorough examination / load test in the exact F-7.5.1-04 / -05 pattern. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
$fdef = [];
foreach ($m['fields'] as $f) { $fv[$f['key']] = (string) ($f['value'] ?? ''); $fdef[$f['key']] = $f; }
$v = fn ($k) => e($fv[$k] ?? '');
$has = fn ($k) => isset($fdef[$k]);
$resLabel = ['accepted' => 'Passed', 'rejected' => 'Failed', 'repair' => 'Repair Required'][$c['result'] ?? ''] ?? '';
$W = 182.2;

// ---- certificate block
$t1 = '<table class="f sp">' . doc_sizer([93.4, 44.4, 44.4])
    . '<tr class="h76"><td rowspan="2" class="tl"><b>Tested according to:</b><br><span class="std">' . $v('standard') . '</span></td><td class="l c">Certificate Date</td><td class="l c">Certificate No.</td></tr>'
    . '<tr class="h76"><td class="c">' . $v('cert_date') . '</td><td class="c">' . $v('cert_no') . '</td></tr></table>';

// ---- customer block
$t2 = '<table class="f sp gap">' . doc_sizer([30.7, 62.7, 44.4, 44.4])
    . '<tr class="h76"><td class="l">Customer:</td><td>' . $v('client') . '</td><td class="l c">Inspection Date</td><td class="l c">Next Inspection Date</td></tr>'
    . '<tr class="h76"><td class="l">Location:</td><td>' . $v('location') . '</td><td class="c">' . $v('insp_date') . '</td><td class="c">' . $v('next_date') . '</td></tr></table>';

// ---- examined item block
$rightLbl = $has('po') ? 'Customer Order No.' : 'Checklist No.';
$rightVal = $has('po') ? $v('po') : $v('checklist_no');
$t3 = '<table class="f sp gap">' . doc_sizer([45.5, 45.5, 45.5, 45.7])
    . '<tr class="h51"><td class="l">Examined Item</td><td>' . $v('eq_item') . '</td><td class="l">' . $rightLbl . '</td><td>' . $rightVal . '</td></tr>'
    . '<tr class="h51"><td class="l">Identification / SN</td><td>' . $v('eq_sn') . '</td><td class="l">Manufacturer</td><td>' . $v('manufacturer') . '</td></tr>'
    . '<tr class="h51"><td class="l">Last Examination Date</td><td>' . $v('last_exam') . '</td><td class="l">Type of Examination</td><td>' . $v('exam_type') . '</td></tr>'
    . '<tr class="h51"><td class="l">Sticker No.</td><td>' . $v('sticker_no') . '</td><td class="l">Test Result</td><td>' . e($resLabel) . '</td></tr></table>';

// ---- description table
$withSwl = $has('swl');
$cols = $withSwl ? [39.9, 18.5, 79.8, 22, 22] : [39.9, 18.5, 123.8];
$desc = '<div class="descbox">' . nl2br($v('description')) . '</div>';
$hdr = '<tr class="h76"><td class="l c">ID / SN</td><td class="l c">Qty</td><td class="l c">Description</td>' . ($withSwl ? '<td class="l c">SWL</td><td class="l c">Proof Load Test</td>' : '') . '</tr>';
$row = '<tr><td class="c dr">' . $v('item_id') . '</td><td class="c dr">' . $v('qty') . '</td><td class="dd">' . $desc . '</td>' . ($withSwl ? '<td class="c dr">' . $v('swl') . '</td><td class="c dr">' . $v('proof_load') . '</td>' : '') . '</tr>';
$t4 = '<table class="f sp gap">' . doc_sizer($cols) . $hdr . $row . '</table>';

// ---- NDT + interval
$opts = ($has('interval') && !empty($fdef['interval']['options'])) ? $fdef['interval']['options'] : ['3 Months', '6 Months', '12 Months'];
$cells = '';
foreach ($opts as $o) {
    $on = trim((string) ($fv['interval'] ?? '')) === trim($o);
    $cells .= '<td class="c"><span class="radio">' . ($on ? '&#9673;' : '&#9711;') . '</span> ' . e($o) . '</td>';
}
$t5 = '<table class="f sp gap">' . doc_sizer([45.5, 45.5, 45.5, 45.7])
    . '<tr class="h76"><td colspan="4" class="l">NDT Details if Applicable:</td></tr>'
    . '<tr class="h76"><td class="c b">NDT Certificate No:</td><td class="c">' . $v('ndt_no') . '</td><td class="c b">NDT Certificate Date:</td><td class="c">' . $v('ndt_date') . '</td></tr>'
    . '<tr class="h76"><td colspan="4" class="l">Was the thorough examination carried out?</td></tr>'
    . '<tr class="h76"><td class="c b">Within an Interval:</td>' . $cells . str_repeat('<td></td>', max(0, 3 - count($opts))) . '</tr></table>';

// ---- inspection results (one bullet per sentence)
$sent = preg_split('/(?<=\.)\s+/', trim((string) $c['result_statement'])) ?: [];
$li = '';
foreach ($sent as $s) if (trim($s) !== '') $li .= '<li>' . e($s) . '</li>';
$t6 = '<table class="f sp gap">' . doc_sizer([45.5, 136.7])
    . '<tr><td class="b c ir">Inspection Results:</td><td class="irt"><ul>' . $li . '</ul></td></tr></table>';

// ---- sign-off
$insp = $m['inspector'];
$rev = $m['reviewer'];
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sig = ($signed && $insp && !empty($insp['signature'])) ? '<img class="sg" src="' . $insp['signature'] . '" alt="">' : '';
$sig2 = ($m['issued'] && $rev && !empty($rev['signature'])) ? '<img class="sg" src="' . $rev['signature'] . '" alt="">' : '';
$t7 = '<table class="f sp gap">' . doc_sizer([78.4, 103.8])
    . '<tr class="h76"><td class="b">Inspection carried out by:</td><td>' . e($insp['name'] ?? '') . '</td></tr>'
    . '<tr class="h76"><td class="b">Signature:</td><td rowspan="2" class="sgc">' . $sig . '</td></tr>'
    . '<tr class="h76"><td class="b">Stamp:</td></tr>'
    . '<tr class="h76"><td class="b">Reviewed / Approved by:</td><td>' . ($m['issued'] ? e($rev['name'] ?? '') : '<span class="muted">Pending QC review</span>') . $sig2 . '</td></tr></table>';

$stamp = $m['issued'] ? '<img class="stamp" style="left:159mm; top:206mm; width:28mm; height:28mm" src="' . $brand['stamp'] . '" alt="">' : '';
$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $t1 . $t2 . $t3 . $t4 . $t5 . $t6 . $t7,
        'top'   => 35.8,
        'left'  => 14.2,
        'width' => $W,
        'extra' => '<div class="cert-title sp-title">' . e($tpl['title']) . '</div><div class="sp-sub">' . e($tpl['notes']) . '</div>' . $stamp,
    ]],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
