<?php
/**
 * Bilingual motorcycle report (F-7.5.1-14): data cover, one table with a Pass / Fail / N/A header row per section,
 * VIN picture, six motorcycle pictures, remarks box and inspector / reviewer block.
 * Called from documents/report.php when the report layout has ar.style = 'mo'.
 * @var array $m  @var ?array $toolbar  @var array $L
 */
$tpl = $m['template'];
$R = $L['report'];
$AR = $R['ar'] ?? [];
$mmPt = 0.3528;
$arPt = (float) ($AR['pt'] ?? 8);
$font = (float) ($AR['font'] ?? 10);
$enLine = (float) ($AR['en_line'] ?? 1.23);
$arLine = (float) ($AR['line'] ?? 1.1);
$box = fn ($on) => $on ? '&#9746;' : '&#9744;';
$arh = fn (string $t, float $w = 0, float $pt = 8.5, bool $b = true) => ArabicText::html($t, $w, $pt, $b);
$h = fn ($mm) => 'height:' . round($mm - 0.35, 2) . 'mm';
$BL = '#aabee3'; $GR = '#f2f2f2';

// ---------------------------------------------------------------- cover values (same sources as the other report layouts)
$sj = $m['sj'];
$data = $m['data'] ?? [];
$src = [
    'report_no' => $m['certificate_no'], 'report_date' => fmt_date($m['issue_date'] ?? null), 'client' => $sj['client_name'] ?? '',
    'inspection_date' => fmt_date($m['checklist']['inspection_date']), 'site' => $sj['location'] ?? '', 'eq_desc' => $sj['eq_description'] ?? '',
    'model' => $sj['eq_model'] ?? '', 'brand' => $sj['manufacturer'] ?? '', 'year' => $sj['eq_year'] ?? '',
    'serial' => $m['checklist']['serial_no'] ?? '', 'center_name' => 'TUV EAST',
];
$bound = array_diff_key($src, TemplateService::REPORT_DEFAULTS);
$value = fn ($key) => e((string) (array_key_exists($key, $bound) ? $bound[$key] : (($data[$key] ?? '') !== '' ? $data[$key] : ($src[$key] ?? ''))));
$fails = 0; $answered = 0;
foreach ($m['items'] as $it) { if ($it['result'] === 'fail') $fails++; if ($it['result']) $answered++; }
$compliant = null;
if ($fails > 0 || in_array($m['cert_result'] ?? null, ['rejected', 'repair'], true)) $compliant = false;
elseif ($answered === count($m['items']) && $answered > 0) $compliant = true;

// ---------------------------------------------------------------- cover
$cw = 185.7;
$bi = fn ($ar, $en, $pt = 9) => '<div style="line-height:1;font-size:' . $pt . 'pt">' . $arh($ar, 0, $pt) . '</div><div style="line-height:0.85">' . e($en) . '</div>';
$mix = fn ($en, $ar) => e($en) . ' <span style="line-height:1">' . $arh($ar, 0, 8.5) . '</span>';
$band = fn ($inner, $hh, $bg = null) => '<tr><td colspan="4" class="c b" style="background:' . ($bg ?? $BL) . ';' . $h($hh) . '">' . $inner . '</td></tr>';
$cover = '<table class="f mo" style="font-size:10pt;line-height:0.85">' . doc_sizer([40, 50.2, 70, 25.5])
    . $band($bi('بيانات التقرير', 'Report Details', 9), 9.1)
    . '<tr><td class="c" style="' . $h(7.6) . '">' . $value('report_date') . '</td><td class="b" style="background:' . $GR . '">' . $mix('Report Date –', 'تاريخ التقرير') . '</td><td rowspan="2" class="c">' . $value('report_no') . '</td><td rowspan="2" class="c b" style="background:' . $BL . '">' . $bi('رقم التقرير', 'Report No.') . '</td></tr>'
    . '<tr><td class="c" style="' . $h(7.6) . '">' . $value('inspection_date') . '</td><td class="b" style="background:' . $GR . '">' . $mix('Inspection Date -', 'تاريخ التفتيش') . '</td></tr>'
    . '<tr><td colspan="3" class="c" style="' . $h(12.7) . '">' . $value('client') . '</td><td class="c b" style="background:' . $BL . '">' . $bi('أسم العميل', 'Client Name') . '</td></tr>'
    . '</table>';
$chars = preg_split('//u', strtoupper(html_entity_decode($value('vin'))), -1, PREG_SPLIT_NO_EMPTY);
$vn = ''; $vv = '';
for ($i = 0; $i < 17; $i++) {
    $vn .= '<td class="c" style="' . $h(6.3) . ';padding:0">' . ($i + 1) . '</td>';
    $vv .= '<td class="c" style="' . $h(10.2) . ';padding:0;font-size:12pt">' . e($chars[$i] ?? '') . '</td>';
}
$pairs = [
    [['Manufacture (Trademark) -', 'اسم الصانع (العلامة التجارية)', 'brand'], ['Motorcycle Type (Description) -', 'نوع الدراجة (الوصف)', 'eq_desc']],
    [['Color -', 'اللون', 'color'], ['Model –', 'الموديل', 'model']],
    [['Length -', 'الطول', 'length'], ['Total weight -', 'الوزن الإجمالي للدراجة', 'weight']],
    [['Odometer -', 'عداد المسافات', 'odometer'], ['Motor capacity -', 'سعة المحرك', 'capacity']],
    [['Origin country -', 'بلد المنشأ', 'origin'], ['Date of Production -', 'سنة الصنع', 'year']],
];
$grid = '';
foreach ($pairs as [$a, $b]) {
    $grid .= '<tr><td class="c b" style="background:' . $GR . ';' . $h(7.6) . '">' . $mix($a[0], $a[1]) . '</td><td class="c b" style="background:' . $GR . '">' . $mix($b[0], $b[1]) . '</td></tr>'
        . '<tr><td class="c" style="' . $h(12.7) . '">' . $value($a[2]) . '</td><td class="c">' . $value($b[2]) . '</td></tr>';
}
$radio = fn ($on) => '<span style="font-size:11pt;color:#555">' . ($on ? '&#9673;' : '&#9711;') . '</span>';
$cover .= '<table class="f mo" style="font-size:10pt;line-height:0.85;margin-top:-0.5pt">' . doc_sizer([$cw])
    . '<tr><td class="c b" style="background:' . $BL . ';' . $h(6.3) . '">' . $mix('Vin Number -', 'الرقم المميز') . '</td></tr></table>'
    . '<table class="f mo" style="font-size:9pt;line-height:0.75;margin-top:-0.5pt">' . doc_sizer(array_fill(0, 17, $cw / 17)) . '<tr>' . $vn . '</tr><tr>' . $vv . '</tr></table>'
    . '<table class="f mo" style="font-size:10pt;line-height:0.85;margin-top:-0.5pt">' . doc_sizer([$cw])
    . '<tr><td class="c b" style="background:' . $BL . ';' . $h(7.6) . '">' . $mix('Technical Regulation & Standards –', 'المواصفات الفنية') . '</td></tr>'
    . '<tr><td class="c b" style="' . $h(10.2) . '">MA-173-19-04-02-SASO Technical Regulation of Motorcycle-Version 1 – (GSO 1798:2007, SASO 1284:2017)</td></tr>'
    . '<tr><td class="c b" style="background:' . $BL . ';' . $h(7.6) . '">' . $mix('Motorcycle information –', 'بيانات الدراجه') . '</td></tr></table>'
    . '<table class="f mo" style="font-size:10pt;line-height:0.85;margin-top:-0.5pt">' . doc_sizer([$cw / 2, $cw / 2]) . $grid
    . '<tr><td colspan="2" class="c b" style="background:' . $BL . ';' . $h(10.2) . '"><div style="line-height:1;font-size:9pt">' . $arh('ضمن الدراجات الآلية الممنوع استيرادها حسب المادة 2.2 من اللائحة الفنية', 0, 9) . '</div>Motorcycle are from prevented imported according to item 2.2 Technical regulation</td></tr></table>'
    . '<table class="f mo" style="font-size:10pt;line-height:0.85;margin-top:-0.5pt">' . doc_sizer([90, 48, $cw - 138])
    . '<tr><td class="c b" style="' . $h(7.6) . '">' . '<div style="line-height:1;font-size:9pt">' . $arh('مطابق', 0, 9) . '</div><span style="color:#00b050">Compliance</span></td><td class="c">' . $radio($compliant === true) . '</td><td rowspan="2" class="c b" style="background:' . $BL . '">' . $bi('نتيجة التفتيش', 'Inspection Result') . '</td></tr>'
    . '<tr><td class="c b" style="' . $h(7.6) . '"><div style="line-height:1;font-size:9pt;color:#ff0000">' . $arh('غير مطابق', 0, 9) . '</div><span style="color:#ff0000">Non-Compliance</span></td><td class="c">' . $radio($compliant === false) . '</td></tr></table>';
$coverH = 9.1 + 15.2 + 12.7 + 6.3 + 6.3 + 10.2 + 7.6 + 10.2 + 7.6 + 5 * 20.3 + 10.2 + 15.2;

// ---------------------------------------------------------------- items table
$cols = [21.9, 21.9, 21.9, 110.3, 8.1];
$tw = array_sum($cols);
$sizer = doc_sizer($cols);
$descW = $cols[3] - 4.3;
$enMm = $font * $enLine * $mmPt;
$arMm = $arPt * $arLine * $mmPt * 1.34;
$wrapEn = function (string $text) use ($descW, $font) {
    $space = DocumentService::textWidthMm(' ', $font, false) ?? 1.0;
    $lines = 1; $len = 0.0;
    foreach (preg_split('/\s+/u', trim($text)) as $w) {
        if ($w === '') continue;
        $wl = DocumentService::textWidthMm($w, $font, false) ?? mb_strlen($w) * 0.45 * $font * $mmPt;
        if ($len > 0 && $len + $space + $wl > $descW) { $lines++; $len = 0.0; }
        $len += ($len > 0 ? $space : 0) + $wl;
    }
    return $lines;
};
$hdr0 = '<tr style="background:' . $BL . '" class="b"><td colspan="3" class="c" style="' . $h(10.2) . '">' . $mix('Acceptance or Rejected -', 'القبول أو الرفض') . '</td><td class="c">' . $mix('Technical Requirement -', 'المتطلبات الفنية') . '</td><td class="c" style="padding:0"><div style="line-height:1;font-size:6.5pt">' . $arh('الرقم', 0, 6.5) . '</div><div style="line-height:0.85;font-size:7pt">No</div></td></tr>';
$hdr0H = 10.2;
$answers = [['na', 'لا ينطبق', 'N/A'], ['fail', 'غير مجتاز', 'Fail'], ['pass', 'مجتاز', 'Pass']];

$sections = [];
foreach ($m['items'] as $it) $sections[$it['section']][] = $it;
$flow = [];
foreach ($sections as $name => $items) {
    [$no, $en, $arT] = array_pad(explode('|', TemplateService::sectionLabel($name), 3), 3, '');
    $sh = '<tr class="b" style="background:' . $BL . '">';
    foreach ($answers as [, $a, $e]) $sh .= '<td class="c" style="' . $h(9.6) . '">' . $bi($a, $e) . '</td>';
    $sh .= '<td class="c">' . ($arT !== '' ? '<div style="line-height:1;font-size:9pt">' . $arh($arT, $descW, 9) . '</div>' : '') . '<div style="line-height:0.85">' . e($en) . '</div></td><td class="c" style="padding:0">' . e($no) . '</td></tr>';
    foreach ($items as $i => $it) {
        $lines = preg_split('/\R/u', (string) $it['label']);
        $num = '';
        if ($lines && str_starts_with($lines[0], '@')) $num = substr(array_shift($lines), 1);
        $html = ''; $hh = 0.0;
        foreach ($lines as $ln) {
            if (trim($ln) === '') continue;
            if (ArabicText::has($ln)) {
                $hh += count(ArabicText::wrap($ln, $descW * 0.97, $arPt, true)) * $arMm;
                $html .= '<div style="font-size:' . $arPt . 'pt;text-align:right;line-height:' . $arLine . ';font-weight:bold">' . $arh($ln, $descW, $arPt, true) . '</div>';
            } else {
                $hh += $wrapEn($ln) * $enMm;
                $html .= '<div style="text-align:justify">' . e($ln) . '</div>';
            }
        }
        $hh = max(7.6, $hh * (float) ($AR['k'] ?? 0.92) + (float) ($AR['pad'] ?? 0.2));
        $tr = '<tr>';
        foreach ($answers as [$code]) $tr .= '<td class="box">' . $box($it['result'] === $code) . '</td>';
        $tr .= '<td style="font-weight:normal;padding-left:1.9mm;padding-right:2mm"><div style="line-height:' . round($enLine / 1.344, 3) . '">' . $html . '</div></td><td class="c" style="padding:0;font-weight:normal">' . e($num) . '</td></tr>';
        $flow[] = ['html' => $tr, 'h' => $hh, 'pre' => $i === 0 ? $sh : '', 'preh' => $i === 0 ? 9.6 : 0];
    }
}

// ---------------------------------------------------------------- picture blocks, remarks, signatures (own tables, one block per row)
$photos = $m['photos'] ?? [];
$vinPic = $photos[0] ?? null;
$pics = array_slice($photos, 1, 6);
$img = fn ($s, $mw, $mh) => $s ? '<img src="' . $s . '" style="max-width:' . $mw . 'mm;max-height:' . $mh . 'mm" alt="">' : '';
$tail = [];
$tail[] = ['<table class="f mo" style="font-size:10pt">' . doc_sizer([185.7]) . '<tr><td class="c b" style="background:' . $BL . ';' . $h(12.7) . '">' . $mix('Vin Number Picture -', 'صورة الرقم الهيكل') . '</td></tr></table>', 12.7, 0];
$tail[] = ['<table class="f mo" style="margin-top:-0.5pt">' . doc_sizer([185.7]) . '<tr><td class="c" style="' . $h(76.2) . ';padding:1mm">' . $img($vinPic, 180, 72) . '</td></tr></table>', 76.2, 0];
$tail[] = ['<table class="f mo" style="font-size:10pt">' . doc_sizer([185.7]) . '<tr><td class="c b" style="background:' . $BL . ';' . $h(10.2) . '">' . $mix('Motorcycle pic.', 'صور الدراجه –') . '</td></tr></table>', 10.2, 8];
for ($r = 0; $r < 3; $r++) {
    $row = '<table class="f mo" style="margin-top:-0.5pt">' . doc_sizer([92.9, 92.9]) . '<tr>';
    for ($c = 0; $c < 2; $c++) $row .= '<td class="c" style="' . $h(73.7) . ';padding:1mm">' . $img($pics[$r * 2 + $c] ?? null, 88, 70) . '</td>';
    $tail[] = [$row . '</tr></table>', 73.7, 0];
}
$remarks = nl2br(e($m['checklist']['remarks'] ?? ''));
$tail[] = ['<table class="f mo" style="margin-left:2.4mm;font-size:10.5pt">' . doc_sizer([181]) . '<tr><td style="' . $h(56) . ';vertical-align:top;padding-top:1mm"><b>REMARKS:</b><div style="margin-top:1mm">' . $remarks . '</div></td></tr></table>', 56, 0];
$insp = $m['inspector'];
$rev = $m['reviewer'] ?? null;
$sg = array_fill(0, 6, 179.4 / 6);
$rowH = 10.2;
$sgrow = fn ($dt, $im, $name, $en, $ar) => '<tr><td class="c" style="' . $h($rowH) . '">' . $dt . '</td><td class="c b" style="background:' . $BL . '">' . $bi('التاريخ', 'Date') . '</td><td class="c">' . $im . '</td><td class="c b" style="background:' . $BL . '">' . $bi('التوقيع', 'Sign') . '</td><td class="c">' . e($name) . '</td><td class="c b" style="background:' . $BL . '">' . $bi($ar, $en) . '</td></tr>';
$sigImg = fn ($p, $ok) => ($ok && !empty($p['signature'])) ? '<img src="' . $p['signature'] . '" style="max-height:' . ($rowH - 2) . 'mm;max-width:27mm" alt="">' : '';
$tail[] = ['<table class="f mo" style="margin-left:3.1mm;margin-top:' . ($AR['sign_gap'] ?? 14) . 'mm;font-size:10pt;line-height:0.85">' . doc_sizer($sg)
    . $sgrow($m['checklist']['inspection_date'] ? e(fmt_date($m['checklist']['inspection_date'])) : '', $sigImg($insp, $m['checklist']['status'] === 'completed'), $insp['name'] ?? '', 'Inspector Name', 'أسم المفتش')
    . $sgrow(!empty($m['review_date']) ? e(fmt_date($m['review_date'])) : '', $sigImg($rev, true), $rev['name'] ?? '', 'Reviewed By', 'أسم المراجع') . '</table>', 2 * $rowH + ($AR['sign_gap'] ?? 14), 0];

// ---------------------------------------------------------------- paginate
$bottom = (float) ($AR['bottom'] ?? 268.5);
$top1 = (float) ($AR['top'] ?? 29.7);
$topN = (float) ($AR['top_next'] ?? 25.4);
$open = '<table class="f mo cl" style="font-size:' . $font . 'pt">' . $sizer . $hdr0;
$pages = [];
$body = $cover . '<div style="height:' . ($AR['hdr_gap'] ?? 8) . 'mm"></div>' . '<table class="f mo" style="font-size:' . $font . 'pt">' . $sizer . $hdr0 . '</table>';
$used = $top1 + $coverH + ($AR['hdr_gap'] ?? 8) + $hdr0H;
$table = '';
$newPage = function () use (&$pages, &$body, &$table, &$used, $topN) {
    if ($table !== '') { $body .= $table . '</table>'; $table = ''; }
    $pages[] = $body; $body = ''; $used = $topN;
};
$first = true;
foreach ($flow as $b) {
    $need = $b['h'] + $b['preh'] + ($table === '' ? $hdr0H : 0);
    if ($used + $need > $bottom) $newPage();
    if ($table === '') { $table = $open; $used += $hdr0H; }
    $table .= $b['pre'] . $b['html'];
    $used += $b['h'] + $b['preh'];
}
if ($table !== '') { $body .= $table . '</table>'; $table = ''; }
foreach ($tail as [$html, $th, $gap]) {
    if ($used + $gap + $th > $bottom) { $pages[] = $body; $body = ''; $used = $topN; $gap = 0; }
    if ($gap > 0 && $body !== '') { $body .= '<div style="height:' . $gap . 'mm"></div>'; $used += $gap; }
    $body .= $html;
    $used += $th;
}
$pages[] = $body;
$out = [];
foreach ($pages as $k => $b) {
    $isFirst = $k === 0;
    $out[] = ['body' => $b, 'top' => $isFirst ? $top1 : $topN, 'left' => $isFirst ? 12.1 : 12.95, 'width' => 185.7];
}

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . $m['sj']['sub_job_no'],
    'pages'     => $out,
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $m['checklist']['status'] === 'draft' ? 'DRAFT' : null,
]);
