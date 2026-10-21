<?php
/** Elevator Inspection Defect Report (F-7.5.1-D 02): page 1 bilingual lift certificate, page 2 numbered defect list. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$na = fn ($k) => ($fv[$k] ?? '') !== '' ? e($fv[$k]) : 'N/A';
$ar = fn (string $t, float $w = 0, float $pt = 10, bool $b = true) => ArabicText::html($t, $w, $pt, $b);
$hh = fn ($mm) => 'height:' . round($mm - 0.18, 2) . 'mm';
$fix = fn (string $t) => preg_replace('~style="([^"]*)" data-s="([^"]*)"~', 'style="$1;$2"', preg_replace_callback('~<tr style="(height:[^"]+)">(.*?)</tr>~s', fn ($mm) => '<tr>' . preg_replace('~<td\b~', '<td style="' . $mm[1] . '"', $mm[2]) . '</tr>', $t));
$lab = fn (string $a, string $en) => $ar($a, 44, 10) . '<br>' . e($en);
$gap = fn ($mm) => ' style="margin-top:' . $mm . 'mm"';
// $sh: text shift in pt (+ down via padding, - up via tighter line-height); $mid: vertical-align middle
$row = function ($h, $cells, float $sh = 0, bool $mid = false) use ($hh) {
    $st = '';
    if ($sh > 0) { $st = 'padding-top:' . $sh . 'pt;'; $h -= $sh * 0.3528; }
    elseif ($sh < 0) $st = 'line-height:' . round(0.909 - 2 * abs($sh) / 13.44, 3) . ';';
    if ($mid) $st .= 'vertical-align:middle;';
    $cells = $st !== '' ? preg_replace('~<td\b~', '<td data-s="' . $st . '"', $cells) : $cells;
    return '<tr style="' . $hh($h) . '">' . $cells . '</tr>';
};

// ---- page 1
$t0 = '<table class="f ld" style="width:68.4mm;margin-left:119.6mm">' . doc_sizer([68.4])
    . $row(4.94, '<td class="b">Certificate No: ' . $v('cert_no') . '</td>', -2.2) . '</table>';
$t1 = '<table class="f ld"' . $gap(4.94) . '>' . doc_sizer([187.6])
    . $row(7.62, '<td class="l c b t12">' . $ar('شهادة تفتيش مصعد', 180, 12) . '</td>')
    . $row(7.62, '<td class="c b t12">LIFT INSPECTION CERTIFICATE</td>') . '</table>';
$org = [['APPROVED INSPECTION BODY - ' . $ar('اسم جهة التفتيش', 0, 10), 'Trading Utility Vendor Est for Inspection'],
    ['ADDRESS - ' . $ar('العنوان', 0, 10), 'Al-Khobar - ' . $ar('الخبر', 0, 10)],
    ['PHONE - ' . $ar('الهاتف', 0, 10), '+966 56648 5177 &nbsp;&nbsp;-&nbsp;&nbsp; +966 53189 3259'],
    ['E-MAIL - ' . $ar('البريد الالكتروني', 0, 10), 'info@tuv-east.com &nbsp;&nbsp;-&nbsp;&nbsp; operations@tuv-east.com']];
$t2 = '<table class="f ld"' . $gap(5.47) . '>' . doc_sizer([97.1, 90.5]);
foreach ($org as $r) $t2 .= $row(4.94, '<td class="c b">' . $r[0] . '</td><td class="c b">' . $r[1] . '</td>', -3.0);
$t2 .= '</table>';
$t3 = '<table class="f ld"' . $gap(4.94) . '>' . doc_sizer([97.1, 90.5])
    . $row(7.62, '<td class="l c b">LIFT OWNER</td><td class="l c b">' . $ar('مالك المصعد', 88, 10) . '</td>')
    . $row(10.16, '<td class="b">Owner: ' . $v('owner') . '</td><td class="b rt">' . $ar('الاسم: ' . ($fv['owner_ar'] ?? ''), 86, 10) . '</td>', 5.2)
    . $row(7.62, '<td class="b">Address: ' . $v('address') . '</td><td class="b rt">' . $ar('العنوان: ' . ($fv['address_ar'] ?? ''), 86, 10) . '</td>', 1.3)
    . $row(7.62, '<td class="b">Location: ' . $v('location') . '</td><td class="b rt">' . $ar('مكان التركيب: ' . ($fv['location_ar'] ?? ''), 86, 10) . '</td>', 1.3) . '</table>';
$sl = array_values(array_filter(array_map('trim', preg_split('/\R| \/ /u', $fv['standard'] ?? ''))));
$std = '';
foreach ($sl as $i => $x) $std .= ($i ? '<br>' : '') . '<span style="font-size:9pt' . ($i === count($sl) - 1 && count($sl) > 2 ? ';font-weight:bold' : '') . '">' . e($x) . '</span>';
$cl = ($fv['classification'] ?? '') === '' ? 'N/A' : (preg_match('/^(.*?)(\p{Arabic}.*)$/us', $fv['classification'], $cm) ? e($cm[1]) . $ar(trim($cm[2]), 0, 10, false) : e($fv['classification']));
$t4 = '<table class="f ld"' . $gap(3.7) . '>' . doc_sizer([49.3, 47.8, 42.9, 47.6])
    . $row(7.62, '<td colspan="2" class="l c b">INSPECTED LIFT</td><td colspan="2" class="l c b">' . $ar('المصعد المفتش عليه', 88, 10) . '</td>', 0, true)
    . $row(14.0, '<td class="c">' . $na('year') . '</td><td class="l c b lh">' . $lab('سنة التركيب', 'Installation Year') . '</td><td class="c">' . $na('serial') . '</td><td class="l c b lh">' . $lab('الرقم التسلسلي للمصعد', 'Lift No.') . '</td>', 0, true)
    . $row(9.7, '<td class="c b">' . $cl . '</td><td class="l c b lh">' . $lab('التصنيف / المصنع', 'Classification') . '</td><td class="c">' . $na('speed') . '</td><td class="l c b lh">' . $lab('سرعة المركبة', 'Car Speed (M/S)') . '</td>', 0, true)
    . $row(14.3, '<td class="c">' . $na('load_test') . '</td><td class="l c b lh">' . $lab('تاريخ اجراء اختبار تباين الحموله', 'Load Test Date') . '</td><td class="c">' . $na('capacity') . '</td><td class="l c b lh">' . $lab('الحمولة القصوى', 'Max Load / Capacity') . '</td>', 0, true)
    . $row(14.4, '<td class="c">' . ($std !== '' ? $std : 'N/A') . '</td><td class="l c b lh">' . $lab('كود الفحص المتبع / المرجع', 'Relevant Standard') . '</td><td class="c">' . $na('sticker_no') . '</td><td class="l c b lh">' . $lab('ملصق رقم', 'Sticker No.') . '</td>', 0, true) . '</table>';
$res = (string) ($c['result'] ?? '');
$resTxt = ['accepted' => 'مقبول/ Accepted', 'rejected' => 'غير مقبول/ Rejected', 'repair' => 'يحتاج إلى إصلاح/ Repair'][$res] ?? '';
[$ra, $re] = $resTxt !== '' ? array_map('trim', explode('/', $resTxt, 2)) : ['', ''];
$resCls = $res === 'accepted' ? 'gn' : 'rd';
$t5 = '<table class="f ld"' . $gap(4.2) . '>' . doc_sizer([97.1, 90.5])
    . $row(9.4, '<td class="l c b">' . 'Certificate Date &nbsp;&nbsp; ' . $ar('تاريخ الشهادة', 0, 10) . '</td><td class="l c b">' . $ar('تاريخ التفتيش', 0, 10) . '<br>Inspection Date</td>', 0, true)
    . $row(7.62, '<td class="c">' . $v('cert_date') . '</td><td class="c">' . $v('insp_date') . '</td>', 0, true)
    . $row(7.62, '<td class="c b ' . $resCls . '">' . ($resTxt !== '' ? e($re) . ' / ' . $ar($ra, 0, 10) : '') . '</td><td class="l c b">' . '/ Result &nbsp;&nbsp; ' . $ar('نتيجة الفحص', 0, 10) . '</td>', 0, true) . '</table>';
$insp = $m['inspector']; $rev = $m['reviewer'];
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sg = fn ($p, $show) => ($show && $p && !empty($p['signature'])) ? '<img class="sg" src="' . $p['signature'] . '" alt="">' : '';
$t6 = '<table class="f ld"' . $gap(4.94) . '>' . doc_sizer([97.1, 90.5])
    . $row(9.6, '<td class="l c b">' . $ar('المسؤول عن التفتيش', 88, 10) . '<br>INSPECTOR</td><td class="l c b">' . $ar('اسم المسئول عن منح شهادة التفتيش', 88, 10) . '<br>CERTIFICATE REVIEWED BY</td>', 0, true)
    . $row(17.78, '<td class="b">Name: ' . e($insp['name'] ?? '') . '<br><br>Signature: ' . $sg($insp, $signed) . '</td><td class="b">Checked By: ' . ($m['issued'] ? e($rev['name'] ?? '') : '<span class="muted">Pending QC review</span>') . '<br><br>Signature ' . $sg($rev, $m['issued']) . '</td>') . '</table>';
$notes = '';
foreach ($m['notes'] as $n) $notes .= '<div class="nr" style="font-size:7pt">' . $ar(ltrim($n, '- '), 182, 7, false) . ' -</div>';
$t7 = '<table class="f ld"' . $gap(4.94) . '>' . doc_sizer([187.6]) . $row(10.16, '<td class="nt">' . $notes . '</td>') . '</table>';
$stamp = $m['issued'] ? '<img class="stamp" style="left:13.4mm; top:243mm; width:28mm; height:28mm" src="' . $brand['stamp'] . '" alt="">' : '';

// ---- page 2: defects
$d = preg_split('/\R/u', trim((string) ($fv['defects'] ?? ''))) ?: [];
$p2 = '<table class="f ld" style="width:188.9mm">' . doc_sizer([68.3, 27.9, 55.0, 37.8])
    . $row(7.37, '<td colspan="4" class="l c b t12">' . $ar('الملاحظات ونقاط الخلل في المصعد', 180, 12) . '</td>', 0, true)
    . $row(9.2, '<td class="c b">' . $v('cert_no') . '</td><td class="l rt b lh" style="font-size:9pt">' . $ar('تقرير الفحص', 0, 9) . '<br>' . $ar('رقم:', 0, 9) . '</td><td class="c b">' . $na('serial') . '</td><td class="l b rt lh" style="font-size:9pt">' . $ar('الرقم التسلسلي', 0, 9) . '<br>' . $ar('للمصعد:', 0, 9) . '</td>', 0, true)
    . $row(7.37, '<td colspan="4" class="b">' . $ar('الملاحظات خلال عملية الفحص (يجب اصلاحها او تعديلها):', 185, 10) . '</td>', 0, true) . '</table>'
    . '<table class="f ld" style="width:188.9mm;margin-top:-0.3mm">' . doc_sizer([180.0, 8.9]);
for ($i = 0; $i < 20; $i++) {
    $t = trim($d[$i] ?? '');
    $p2 .= $row(10.16, '<td class="rt">' . ($t !== '' ? $ar($t, 176, 10, false) : '') . '</td><td class="c b">' . ($i + 1) . '</td>', 0, true);
}
$p2 .= '</table>';

$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;
echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [
        ['body' => $fix($t0 . $t1 . $t2 . $t3 . $t4 . $t5 . $t6 . $t7), 'top' => 26.99, 'left' => 9.17, 'width' => 188.0, 'extra' => $stamp],
        ['body' => $fix($p2), 'top' => 26.99, 'left' => 15.9, 'width' => 188.9],
    ],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
