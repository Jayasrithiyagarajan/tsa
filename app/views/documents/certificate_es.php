<?php
/** Bilingual (English / Arabic) escalator inspection certificate, F-7.5.1-03 pattern, one A4 page. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$ar = fn (string $t, float $w = 0, float $pt = 10, bool $b = true) => ArabicText::html($t, $w, $pt, $b);
$na = fn (string $k) => ($fv[$k] ?? '') !== '' ? e($fv[$k]) : 'N/A';

// bilingual label cell: Arabic line above the English line
$lab = fn (string $a, string $en, float $w = 44) => '<div class="ara">' . $ar($a, $w, 9.5) . '</div><div class="en">' . e($en) . '</div>';

// ---- certificate number
$t0 = '<table class="f es" style="width:72mm">' . doc_sizer([72])
    . '<tr class="h53"><td class="cn"><b>Certificate No:</b> <span class="cnv">' . $v('cert_no') . '</span></td></tr></table>';

// ---- title band
$t1 = '<table class="f es" style="margin-top:4.6mm">' . doc_sizer([190])
    . '<tr class="h76"><td class="band c">' . $ar('شهادة تفتيش سلم كهربائي', 180, 12) . '</td></tr>'
    . '<tr class="h74"><td class="c eng">' . e($tpl['title']) . '</td></tr></table>';

// ---- inspection body (fixed company details of the form)
$link = fn ($a) => '<span class="lk">' . e($a) . '</span>';
$org = [
    ['APPROVED INSPECTION BODY - ', 'اسم جهة التفتيش', 'Trading Utility Vendor Est for Inspection'],
    ['ADDRESS - ', 'العنوان', 'Al-Khobar - ' . ArabicText::html('الخبر', 0, 10)],
    ['PHONE - ', 'الهاتف', '+966 56648 5177 &nbsp;&nbsp;-&nbsp;&nbsp; +966 53189 3259'],
    ['E-MAIL - ', 'البريد الالكتروني', $link('info@tuv-east.com') . ' &nbsp;&nbsp;-&nbsp;&nbsp; ' . $link('operations@tuv-east.com')],
];
$t2 = '<table class="f es" style="margin-top:5.6mm">' . doc_sizer([97, 93]);
foreach ($org as $r) {
    $t2 .= '<tr class="h48"><td class="c lb">' . e($r[0]) . $ar($r[1], 0, 9) . '</td><td class="c ov">' . $r[2] . '</td></tr>';
}
$t2 .= '</table>';

// ---- owner details
$owner = trim(($fv['owner'] ?? '') . (($fv['tenant'] ?? '') !== '' ? ' - Tenant: ' . $fv['tenant'] : ''));
$t3 = '<table class="f es" style="margin-top:4.2mm">' . doc_sizer([95, 95])
    . '<tr class="h74"><td class="l c sm">OWNER DETAILS</td><td class="l c">' . $ar('بيانات المالك', 90, 10) . '</td></tr>'
    . '<tr class="h100"><td class="bl">Owner: ' . e($owner) . '</td><td class="rt">' . $ar('الإسم: ' . ($fv['owner_ar'] ?? ''), 88, 9) . '</td></tr>'
    . '<tr class="h100"><td class="bl">Address: ' . $v('address') . '</td><td class="rt">' . $ar('العنوان: ' . ($fv['address_ar'] ?? ''), 88, 9) . '</td></tr>'
    . '<tr class="h86"><td class="bl">Location: ' . $v('location') . '</td><td class="rt">' . $ar('مكان التركيب: ' . ($fv['location_ar'] ?? ''), 88, 9) . '</td></tr></table>';

// ---- inspected escalator
$cls = ['Passenger Escalator' => 'سلم ركاب', 'Moving Walkway' => 'ممر متحرك'];
$clsVal = e($fv['classification'] ?? '') . (($cls[$fv['classification'] ?? ''] ?? '') !== '' ? '<br>' . $ar($cls[$fv['classification']], 0, 9, false) : '');
$std = implode('<br>', array_map('e', array_map('trim', explode(' / ', $fv['standard'] ?? ''))));
$t4 = '<table class="f es" style="margin-top:4.2mm">' . doc_sizer([49, 47.8, 43, 50.2])
    . '<tr class="h74"><td class="l c sm" colspan="2">INSPECTED ESCALATOR</td><td class="l c" colspan="2">' . $ar('السلم المفتش عليه', 90, 10) . '</td></tr>'
    . '<tr class="h86"><td class="c">' . $na('year') . '</td><td class="l c">' . $lab('سنة التركيب', 'Installation Year') . '</td><td class="c">' . $na('serial') . '</td><td class="l c">' . $lab('الرقم التسلسلي للسلم', 'Serial No.') . '</td></tr>'
    . '<tr class="h86"><td class="c">' . $clsVal . '</td><td class="l c">' . $lab('التصنيف / المصنع', 'Classification') . '</td><td class="c">' . $na('speed') . '</td><td class="l c">' . $lab('السرعة', 'Speed (M/S)') . '</td></tr>'
    . '<tr class="h130"><td class="c">' . $na('load_test') . '</td><td class="l c">' . $lab('تاريخ اجراء اختبار تباين الحموله', 'Load Test Date') . '</td><td class="c">' . $na('capacity') . '</td><td class="l c">' . $lab('الحمولة القصوى', 'Max Load / Capacity') . '</td></tr>'
    . '<tr class="h130"><td class="c">' . $std . '</td><td class="l c">' . $lab('كود الفحص المتبع / المرجع', 'Relevant Standard') . '</td><td class="c">' . $na('sticker_no') . '</td><td class="l c">' . $lab('ملصق رقم', 'Sticker No.') . '</td></tr></table>';

// ---- dates and result
$res = (string) ($c['result'] ?? '');
$resEn = ['accepted' => 'Passed', 'rejected' => 'Rejected', 'repair' => 'Repair'][$res] ?? '';
$resAr = ['accepted' => 'مقبول', 'rejected' => 'مرفوض', 'repair' => 'يحتاج إلى إصلاح'][$res] ?? '';
$t5 = '<table class="f es" style="margin-top:4.2mm">' . doc_sizer([97, 43, 50])
    . '<tr class="h90"><td class="l c"><span class="sm2">Expiration Date</span> &nbsp;&nbsp;&nbsp; ' . $ar('تاريخ انتهاء صلاحية الشهادة', 0, 9) . '</td>'
    . '<td class="l c">' . $lab('تاريخ الشهادة', 'Certificate Date') . '</td><td class="l c">' . $lab('تاريخ التفتيش', 'Inspection Date') . '</td></tr>'
    . '<tr class="h76"><td class="c">' . $v('next_date') . '</td><td class="c">' . $v('cert_date') . '</td><td class="c">' . $v('insp_date') . '</td></tr>'
    . '<tr class="h76"><td class="c rs">' . ($resEn !== '' ? e($resEn) . ' / ' . $ar($resAr, 0, 9) : '') . '</td><td class="l c rs" colspan="2">/ Result &nbsp;&nbsp; ' . $ar('نتيجة الفحص', 0, 9) . '</td></tr></table>';

// ---- inspector / reviewer
$insp = $m['inspector'];
$rev = $m['reviewer'];
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$sg = fn ($p, $show) => ($show && $p && !empty($p['signature'])) ? '<img class="sg" src="' . $p['signature'] . '" alt="">' : '';
$blk = fn ($label, $name, $img) => '<table class="in"><tr><td class="tt"><b>' . $label . '</b> ' . $name . '<br><br><b>Signature:</b></td><td class="ig">' . $img . '</td></tr></table>';
$t6 = '<table class="f es" style="margin-top:4.2mm">' . doc_sizer([95, 95])
    . '<tr class="h86"><td class="l c">' . $lab('المسؤول عن التفتيش', 'INSPECTOR', 88) . '</td><td class="l c">' . $lab('اسم المسئول عن منح شهادة التفتيش', 'CERTIFICATE REVIEWED BY', 88) . '</td></tr>'
    . '<tr class="h175"><td class="sgn">' . $blk('Name:', e($insp['name'] ?? ''), $sg($insp, $signed)) . '</td>'
    . '<td class="sgn">' . $blk('Checked By:', $m['issued'] ? e($rev['name'] ?? '') : '<span class="muted">Pending QC review</span>', $sg($rev, $m['issued'])) . '</td></tr></table>';

// ---- Arabic notes (template notes, one bullet per line)
$notes = '';
foreach ($m['notes'] as $n) $notes .= '<div class="nr">' . $ar($n, 184, 7.5) . '</div>';
$t7 = $notes ? '<table class="f es" style="margin-top:4.2mm">' . doc_sizer([190]) . '<tr><td class="nt">' . $notes . '</td></tr></table>' : '';

$stamp = $m['issued'] ? '<img class="stamp" style="left:72mm; top:226mm; width:22mm; height:22mm" src="' . $brand['stamp'] . '" alt="">' : '';
$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $t0 . $t1 . $t2 . $t3 . $t4 . $t5 . $t6 . $t7,
        'top'   => 27,
        'left'  => 10.5,
        'width' => 190,
        'extra' => $stamp,
    ]],
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
