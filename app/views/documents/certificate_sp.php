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
$W = 187.9;
$GAP = 4.41;                                    // empty spacer row between the blocks of the Word table
$hh = fn ($mm) => 'height:' . round($mm - 0.18, 2) . 'mm';
// Dompdf ignores a height on <tr>: copy it onto every cell of the row
$fix = fn (string $t) => preg_replace_callback('~<tr style="(height:[^"]+)">(.*?)</tr>~s', fn ($mm) => '<tr>' . preg_replace('~<td\b~', '<td style="' . $mm[1] . '"', $mm[2]) . '</tr>', $t);

// ---- certificate block (the "Tested according to" cell spans both rows: label, blank line, standard)
$t1 = '<table class="f sp">' . doc_sizer([96.3, 45.9, 45.7])
    . '<tr style="' . $hh(7.58) . '"><td rowspan="2" class="tl"><div class="b">Tested according to:</div><div>&nbsp;</div><div>' . $v('standard') . '</div></td><td class="l c b">Certificate Date</td><td class="l c b">Certificate No.</td></tr>'
    . '<tr style="' . $hh(7.58) . '"><td class="c">' . $v('cert_date') . '</td><td class="c">' . $v('cert_no') . '</td></tr></table>';

// ---- customer block
$t2 = '<table class="f sp gap">' . doc_sizer([31.7, 64.7, 45.8, 45.7])
    . '<tr style="' . $hh(7.65) . '"><td class="l b">Customer:</td><td>' . $v('client') . '</td><td class="l c b">Inspection Date</td><td class="l c b">Next Inspection Date</td></tr>'
    . '<tr style="' . $hh(7.65) . '"><td class="l b">Location:</td><td>' . $v('location') . '</td><td class="c">' . $v('insp_date') . '</td><td class="c">' . $v('next_date') . '</td></tr></table>';

// ---- examined item block
$rightLbl = $has('po') ? 'Customer Order No.' : 'Checklist No.';
$rightVal = $has('po') ? $v('po') : $v('checklist_no');
$r3 = fn ($l1, $v1, $l2, $v2) => '<tr style="' . $hh(5.1) . '"><td class="l b">' . $l1 . '</td><td>' . $v1 . '</td><td class="l b">' . $l2 . '</td><td>' . $v2 . '</td></tr>';
$t3 = '<table class="f sp gap">' . doc_sizer([46.9, 46.9, 47.0, 47.1])
    . $r3('Examined Item', $v('eq_item'), $rightLbl, $rightVal)
    . $r3('Identification / SN', $v('eq_sn'), 'Manufacturer', $v('manufacturer'))
    . $r3('Last Examination Date', $v('last_exam'), 'Type of Examination', $v('exam_type'))
    . $r3('Sticker No.', $v('sticker_no'), 'Test Result', e($resLabel)) . '</table>';

// ---- description table (first line bold, the rest as typed)
$withSwl = $has('swl');
$cols = $withSwl ? [31.6, 15.9, 77.8, 30.0, 32.6] : [41.2, 19.0, 127.7];
$dl = preg_split('/\R/u', trim((string) ($fv['description'] ?? '')));
$desc = '';
foreach ($dl as $i => $ln) $desc .= '<div' . ($i === 0 ? ' class="b"' : '') . '>' . ($ln === '' ? '&nbsp;' : e($ln)) . '</div>';
$hdr = '<tr style="' . $hh(7.62) . '"><td class="l c b">ID / SN</td><td class="l c b">Qty</td><td class="l c b">Description</td>' . ($withSwl ? '<td class="l c b">SWL</td><td class="l c b">Proof Load Test</td>' : '') . '</tr>';
$row = '<tr style="' . $hh(43.2) . '"><td class="c b">' . $v('item_id') . '</td><td class="c b">' . $v('qty') . '</td><td class="dd">' . $desc . '</td>' . ($withSwl ? '<td class="c b">' . $v('swl') . '</td><td class="c b">' . $v('proof_load') . '</td>' : '') . '</tr>';
$t4 = '<table class="f sp gap">' . doc_sizer($cols) . $hdr . $row . '</table>';

// ---- NDT + interval
$opts = ($has('interval') && !empty($fdef['interval']['options'])) ? $fdef['interval']['options'] : ['3 Months', '6 Months', '12 Months'];
$cells = '';
foreach ($opts as $o) {
    $on = trim((string) ($fv['interval'] ?? '')) === trim($o);
    $cells .= '<td class="c"><span class="rb"><span class="radio">' . ($on ? '&#9673;' : '&#9711;') . '</span> ' . e($o) . '</span></td>';
}
$cells .= str_repeat('<td></td>', max(0, 3 - count($opts)));
$t5 = '<table class="f sp gap">' . doc_sizer([46.9, 46.9, 47.0, 47.1])
    . '<tr style="' . $hh(7.6) . '"><td colspan="4" class="l b">NDT Details if Applicable:</td></tr>'
    . '<tr style="' . $hh(7.6) . '"><td class="c b">NDT Certificate No:</td><td class="c">' . $v('ndt_no') . '</td><td class="c b">NDT Certificate Date:</td><td class="c">' . $v('ndt_date') . '</td></tr>'
    . '<tr style="' . $hh(7.6) . '"><td colspan="4" class="l b">Was the thorough examination carried out?</td></tr>'
    . '<tr style="' . $hh(7.6) . '"><td class="c b">Within an Interval:</td>' . $cells . '</tr></table>';

// ---- inspection results (one bullet per sentence)
$sent = preg_split('/(?<=\.)\s+/', trim((string) $c['result_statement'])) ?: [];
$li = '';
$n = 0;
foreach ($sent as $s) if (trim($s) !== '') $li .= '<li' . ($n++ === 0 ? ' style="letter-spacing:0"' : '') . '><span class="bu">&bull;</span>' . e($s) . '</li>';
$t6 = '<table class="f sp gap">' . doc_sizer([31.7, 156.2])
    . '<tr style="' . $hh(10.6) . '"><td class="b c">Inspection Results:</td><td class="irt"><ul>' . $li . '</ul></td></tr></table>';

// ---- sign-off
$insp = $m['inspector'];
$rev = $m['reviewer'];
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sig = ($signed && $insp && !empty($insp['signature'])) ? '<img class="sg" src="' . $insp['signature'] . '" alt="">' : '';
$sig2 = ($m['issued'] && $rev && !empty($rev['signature'])) ? '<img class="sg" src="' . $rev['signature'] . '" alt="">' : '';
$t7 = '<table class="f sp gap">' . doc_sizer([80.8, 107.1])
    . '<tr style="' . $hh(7.62) . '"><td class="b">Inspection carried out by:</td><td>' . e($insp['name'] ?? '') . '</td></tr>'
    . '<tr style="' . $hh(7.62) . '"><td class="b">Signature:</td><td rowspan="2" class="sgc">' . $sig . '</td></tr>'
    . '<tr style="' . $hh(7.62) . '"><td class="b">Stamp:</td></tr>'
    . '<tr style="' . $hh(7.62) . '"><td class="b">Reviewed / Approved by:</td><td>' . ($m['issued'] ? e($rev['name'] ?? '') : '<span class="muted">Pending QC review</span>') . $sig2 . '</td></tr></table>';

$stamp = $m['issued'] ? '<img class="stamp" style="left:164.8mm; top:212.7mm; width:29mm; height:29mm" src="' . $brand['stamp'] . '" alt="">' : '';
$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $fix($t1 . $t2 . $t3 . $t4 . $t5 . $t6 . $t7),
        'top'   => 36.05,
        'left'  => 13.8,
        'width' => $W,
        'extra' => '<div class="cert-title sp-title">' . e($tpl['title']) . '</div><div class="sp-sub">' . e($tpl['notes']) . '</div>' . $stamp,
    ]],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
