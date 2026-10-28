<?php
/**
 * Bilingual Trailer & Semi-Trailer inspection report (F-7.5.1-12), reproduced from the Word form:
 * data cover with VIN boxes / dimensions / axle weights / result, "General Requirements" header, one table of Yes / No / N/A rows
 * (Arabic paragraph + English paragraph per row, section headings inside the cell), trailer picture grid (2 x 3), notes box and
 * inspector / reviewer block.  Row heights and page breaks are those of the Word form (ROWH / PAGES below); rows that the form
 * splits across two pages are drawn as two clipped fragments of the same cell.
 * Called from documents/certificate_tr.php.   @var array $m  @var ?array $toolbar
 */
$tpl = $m['template'];
$mmPt = 0.3528;
$BL = '#aabee3'; $GR = '#f2f2f2';
$LEFT = 14.6; $TW = 180.9;
$box = fn ($on) => $on ? '&#9746;' : '&#9744;';
$arh = fn (string $t, float $w = 0, float $pt = 9, bool $b = true) => ArabicText::html($t, $w, $pt, $b);
$pdfMode = ($toolbar ?? null) === null;                                  // Dompdf: collapsed 0.5 pt rules add ~0.18 mm to every table row
$h = fn ($mm) => 'height:' . round($mm - ($pdfMode ? 0.18 : 0), 2) . 'mm';

// ---------------------------------------------------------------- cover values
$sj = $m['sj'];
$data = $m['data'] ?? [];
$val = function (string $key, string $fallback = '') use ($data) {
    $v = (string) ($data[$key] ?? '');
    return e($v !== '' ? $v : $fallback);
};
$cv = [
    'cert_no' => e((string) ($m['certificate_no'] ?? '')), 'report_date' => e(fmt_date($m['issue_date'] ?? null)),
    'insp_date' => e(fmt_date($m['checklist']['inspection_date'] ?? null)),
];
$cov = fn (string $k) => $cv[$k] ?? $val($k);
$fails = 0; $answered = 0;
foreach ($m['items'] as $it) { if ($it['result'] === 'fail') $fails++; if ($it['result']) $answered++; }
$compliant = null;
if ($fails > 0 || in_array($m['cert_result'] ?? null, ['rejected', 'repair'], true)) $compliant = false;
elseif ($answered === count($m['items']) && $answered > 0) $compliant = true;

// bilingual label: Arabic line above, English line below
$bi = fn ($ar, $en, $pt = 9, $enPt = 11) => '<div style="line-height:1;font-weight:bold;font-size:' . $pt . 'pt">' . $arh($ar, 0, $pt) . '</div><div style="line-height:0.85;font-size:' . $enPt . 'pt">' . e($en) . '</div>';
$tbl = fn (array $cols, string $rows, bool $first = false, string $fs = '11pt') => '<table class="f mo trl" style="font-size:' . $fs . ';line-height:0.85' . ($first ? '' : ';margin-top:-0.5pt') . '">' . doc_sizer($cols) . $rows . '</table>';
$lab = fn ($ar, $en, $hh, $extra = '') => '<td class="c" style="background:' . $BL . ';' . $h($hh) . $extra . '">' . $bi($ar, $en) . '</td>';

$rowsA = [
    ['report_no', 'Report No.', 'رقم التقرير', 'cert_no', 8.7], ['report_date', 'Report Date', 'تاريخ التقرير', 'report_date', 8.6],
    ['center', 'Inspection Center Name', 'أسم مركز الفحص', 'center_name', 9.6], ['addr', 'Inspection Center Address', 'عنوان مركز الفحص', 'center_address', 8.7],
    ['client', 'Client Name', 'أسم العميل', 'client', 8.6], ['origin', 'Origin Country', 'بلد المنشأ', 'origin', 8.6],
    ['desc', 'Product Description', 'وصف المنتج', 'eq_desc', 8.6], ['model', 'Model', 'الموديل', 'model', 8.7],
    ['type', 'Type', 'فئة المنتج', 'type', 8.6], ['brand', 'Brand/Trademark Name', 'العلامة التجارية', 'brand', 8.6],
    ['mfg', 'Manufacture Date', 'سنة الصنع', 'mfg_date', 8.6],
];
$sjFallback = ['client' => $sj['client_name'] ?? '', 'eq_desc' => $sj['eq_description'] ?? '', 'model' => $sj['eq_model'] ?? '', 'brand' => $sj['manufacturer'] ?? '', 'mfg_date' => $sj['eq_year'] ?? '', 'center_name' => ''];
$rA = '';
foreach ($rowsA as [, $en, $ar, $key, $hh]) {
    $rA .= '<tr><td class="c" style="' . $h($hh) . '">' . (isset($cv[$key]) ? $cv[$key] : $val($key, (string) ($sjFallback[$key] ?? ''))) . '</td>' . $lab($ar, $en, $hh) . '</tr>';
}
$title = '<div style="text-align:center;font-weight:bold;font-size:12pt;line-height:1;height:5.6mm;padding-top:0.2mm">' . e($tpl['title']) . '</div>'
    . '<div style="text-align:center;font-weight:bold;font-size:12pt;line-height:1;height:5.9mm">' . $arh('تقرير التفتيش للمقطورات و نصف المقطورات', 0, 12, true) . '</div>';

$vinChars = preg_split('//u', strtoupper(html_entity_decode($val('vin'), ENT_QUOTES, 'UTF-8')), -1, PREG_SPLIT_NO_EMPTY);
$vn = ''; $vv = '';
for ($i = 0; $i < 17; $i++) {
    $vn .= '<td class="c" style="' . $h(4.5) . ';padding:0;font-size:10pt">' . ($i + 1) . '</td>';
    $vv .= '<td class="c" style="' . $h(9.6) . ';padding:0;font-size:11pt;font-weight:bold">' . e($vinChars[$i] ?? '') . '</td>';
}
$vinTitle = '<tr><td class="c" style="background:' . $BL . ';' . $h(5.1) . '">Chassis No. (VIN) - <span style="line-height:1;font-size:9pt">' . $arh('رقم الهيكل', 0, 9) . '</span></td></tr>';
$dimC = [22.3, 22.4, 22.5, 22.5, 22.4, 22.4, 46.4];
$dim = '<tr><td class="c" style="' . $h(8.7) . '">' . $val('dim_h') . '</td>' . $lab('الأرتفاع', 'Height', 8.7) . '<td class="c">' . $val('dim_w') . '</td>' . $lab('العرض', 'Width', 8.7)
    . '<td class="c">' . $val('dim_l') . '</td>' . $lab('الطول', 'Length', 8.7) . $lab('الأبعاد (متر)', 'Dimension (Meter)', 8.7) . '</tr>'
    . '<tr><td class="c" style="' . $h(11.5) . '">' . $val('axle_3') . '</td>' . '<td class="c" style="background:' . $BL . '"><div style="line-height:1;font-size:11pt">' . $arh('محور 3', 0, 11) . '</div><div style="line-height:0.85">Axle 3</div></td>'
    . '<td class="c">' . $val('axle_2') . '</td><td class="c" style="background:' . $BL . '"><div style="line-height:1;font-size:11pt">' . $arh('محور 2', 0, 11) . '</div><div style="line-height:0.85">Axle 2</div></td>'
    . '<td class="c">' . $val('axle_1') . '</td><td class="c" style="background:' . $BL . '"><div style="line-height:1;font-size:11pt">' . $arh('محور 1', 0, 11) . '</div><div style="line-height:0.85">Axle 1</div></td>'
    . '<td class="c" style="background:' . $BL . '"><div style="line-height:1;font-size:9pt">' . $arh('الوزن الأقصى علي كل محور', 0, 9) . '<span style="font-size:7pt"> ' . $arh('(كغ)', 0, 7) . '</span></div><div style="line-height:0.85">Max. Axle Weight (kg)</div></td></tr>';
$rB = '<tr><td class="c" style="' . $h(8.6) . '">' . $val('gross') . '</td>' . $lab('الوزن الكلي (كغ)', 'Gross Weight (kg)', 8.6) . '</tr>'
    . '<tr><td class="c" style="' . $h(9.6) . '">MA-162-17-04-01<br>SASO-TR-Trailers and Semi-Trailers-Version 1-Amendment 1</td>' . $lab('اللائحة الفنية', 'Technical Regulation', 9.6) . '</tr>'
    . '<tr><td class="c" style="' . $h(8.7) . '">SASO 2910:2022</td>' . $lab('المواصفات الفنية', 'Technical Standards', 8.7) . '</tr>'
    . '<tr><td class="c" style="' . $h(8.6) . '">' . $cov('insp_date') . '</td>' . $lab('تاريخ التفتيش', 'Inspection Date', 8.6) . '</tr>';
$radio = fn ($on) => '<span style="font-size:11pt;color:#555;font-family:\'DejaVu Sans\',sans-serif">' . ($on ? '&#9673;' : '&#9711;') . '</span>';
$rR = '<tr><td class="c" style="' . $h(8.6) . ';font-weight:bold"><span style="color:#00b050">Compliance to Requirements</span><div style="line-height:1;font-size:9pt;color:#00b050">' . $arh('مطابق للمتطلبات', 0, 9) . '</div></td><td class="c">' . $radio($compliant === true) . '</td>'
    . '<td rowspan="2" class="c" style="background:' . $BL . '">' . $bi('نتيجة التفتيش', 'Inspection Result') . '</td></tr>'
    . '<tr><td class="c" style="' . $h(8.6) . ';font-weight:bold"><span style="color:#ff0000">Non-Compliance to Requirements</span><div style="line-height:1;font-size:9pt;color:#ff0000">' . $arh('غير مطابق للمتطلبات', 0, 9) . '</div></td><td class="c">' . $radio($compliant === false) . '</td></tr>';
$cover = $title . $tbl([134.6, 46.3], $rA, true)
    . $tbl([$TW], $vinTitle) . $tbl(array_fill(0, 17, $TW / 17), '<tr>' . $vn . '</tr><tr>' . $vv . '</tr>', false, '10pt')
    . $tbl($dimC, $dim) . $tbl([134.6, 46.3], $rB) . $tbl([108.6, 26.0, 46.3], $rR);

// ---------------------------------------------------------------- general requirements + column header (page 1, split row continues on page 2)
$cols = [45.3, 15.0, 14.9, 15.0, 90.7];
$sizer = doc_sizer($cols);
$descW = $cols[4] - 2.8;                      // text width: 1.9 mm left, 1.1 mm right padding, Arabic lines 0.9 mm further in
$gb = 'font-weight:bold;text-align:center';
$gr = '<table class="f mo trl" style="font-size:11pt;line-height:0.85;margin-top:9.1mm">' . doc_sizer([90.2, 90.7])
    . '<tr><td style="background:' . $GR . ';' . $gb . ';' . $h(10.2) . '">General Requirements:</td><td style="background:' . $GR . ';' . $gb . '">' . $arh('المتطلبات العامة:', 0, 9) . '</td></tr>'
    . '<tr><td style="background:' . $BL . ';' . $gb . ';' . $h(11.0) . '"><div style="line-height:1.0">The result (Yes\No\N/A) should be placed only after completing the inspection</div></td>'
    . '<td style="background:' . $BL . ';' . $gb . '"><div style="line-height:1.1;font-size:9pt">' . $arh('يجب وضع نتيجة (نعم\لا\لاينطبق) فقط بعد الانتهاء من التفتيش', $cols[4] - 4, 9) . '</div></td></tr></table>';
$hdrH = 15.3; $hdrP1 = 9.8;
$hl = fn ($ar, $en, $w) => '<div style="height:4.0mm;line-height:4.0mm;font-size:9pt;font-weight:bold;white-space:nowrap">' . $arh($ar, 0, 9) . '</div><div style="height:5.3mm;line-height:4.0mm;font-weight:bold;font-size:11pt">' . e($en) . '</div>';
$hdrCells = function (int $off, float $hh) use ($hl, $BL, $h, $arh) {
    $wrap = fn ($inner) => '<div style="height:' . $hh . 'mm;overflow:hidden"><div style="position:relative;top:-' . $off . 'mm;height:' . ($off + $hh) . 'mm">' . $inner . '</div></div>';
    $c = fn ($inner) => '<td class="c" style="background:' . $BL . ';padding:0;vertical-align:top;' . $h($hh) . '">' . $wrap($inner) . '</td>';
    $ln = fn ($t) => '<div style="height:4.9mm;line-height:4.9mm;font-size:9pt;font-weight:bold">' . $arh($t, 0, 9) . '</div>';
    return '<tr>' . $c($hl('ملحوظة:', 'Note:', 0))
        . $c($ln('لا') . $ln('ينطبق') . '<div style="height:4.9mm;line-height:4.9mm;font-weight:bold;font-size:11pt">N/A</div>')
        . $c($hl('لا', 'NO', 0)) . $c($hl('نعم', 'Yes', 0)) . $c($hl('الوصف', 'Description', 0)) . '</tr>';
};
$gr .= '<table class="f mo trl cl" style="font-size:10pt;margin-top:-0.5pt">' . $sizer . $hdrCells(0, $hdrP1) . '</table>';

// ---------------------------------------------------------------- item rows (heights and page breaks of the Word form)
$ROWH = [63.5, 45.8, 29.1, 24.8, 29.1, 21.1, 21.1,  45.1, 25.4, 53.1, 16.8, 45.7, 37.1, 21.2,  21.7, 16.8, 29.1, 25.4, 37.1, 37.2, 16.9, 50.0,
    25.4, 25.4, 25.4, 16.8, 27.9, 40.8, 50.8,  38.1, 29.2, 66.0, 29.2, 13.0, 41.5, 24.8,  13.1, 8.7, 35.6, 24.8, 32.8, 61.2, 16.8, 61.9,
    45.7, 57.1, 66.3, 40.9, 53.9,  37.1, 17.4, 16.5, 26.0, 58.0, 96.3];
$PAGES = [[0, 7], [7, 14], [14, 22], [22, 29], [29, 36], [36, 44], [44, 49], [49, 55]];      // [first row, end row) per page
$SPLIT = [13 => 16.8, 43 => 48.8, 48 => 15.6, 54 => 46.9];                                  // row => height drawn on its first page
$enPt = 10.0; $arPt = 9.0;
$enLine = round(1.219 / 1.344, 3);
$arLh = 0.9;                                  // Dompdf draws the shaped Arabic (DejaVu) with a 0.451 x font-size line pitch at line-height 1
$arLine = $arLh;
$enW = $cols[4] - 3.0;
$spaceEn = DocumentService::textWidthMm(' ', $enPt, false) ?? 1.0;
$wrapEn = function (string $text, bool $bold) use ($enW, $enPt, $mmPt, $spaceEn) {
    $lines = 1; $len = 0.0;
    foreach (preg_split('/\s+/u', trim($text)) as $w) {
        if ($w === '') continue;
        $wl = DocumentService::textWidthMm($w, $enPt, $bold) ?? mb_strlen($w) * 0.45 * $enPt * $mmPt;
        if ($len > 0 && $len + $spaceEn + $wl > $enW) { $lines++; $len = 0.0; }
        $len += ($len > 0 ? $spaceEn : 0) + $wl;
    }
    return $lines;
};
// the form's Arabic is set in a narrower face than the DejaVu Sans Dompdf draws it with: the Arabic size is reduced per row
// until the row's text fits the height of the Word row
$buildDesc = function (string $text, float $arPt) use ($descW, $arLine, $enLine, $wrapEn, $enPt, $mmPt) {
    $html = ''; $hmm = 0.0;
    foreach (preg_split('/\R/u', $text) as $ln) {
        if (trim($ln) === '') continue;
        $mode = '';
        if (str_starts_with($ln, '## ')) { $mode = 'h'; $ln = substr($ln, 3); }
        elseif (str_starts_with($ln, '#> ')) { $mode = 'hr'; $ln = substr($ln, 3); }
        elseif (str_starts_with($ln, '>> ')) { $mode = 'r'; $ln = substr($ln, 3); }
        $head = $mode === 'h' || $mode === 'hr';
        $deco = $head ? ';font-weight:bold;text-decoration:underline' : '';
        if (ArabicText::has($ln)) {
            $hmm += count(ArabicText::wrap($ln, $descW * 0.97, $arPt, $head)) * $arPt * 0.451 * $arLine;
            $html .= '<div style="font-size:' . $arPt . 'pt;text-align:right;padding-right:0.9mm;line-height:' . $arLine . $deco . '">' . ArabicText::html($ln, $descW, $arPt, $head) . '</div>';
        } else {
            $hmm += $wrapEn($ln, $head) * $enPt * 1.219 * $mmPt;
            $html .= '<div style="line-height:' . $enLine . ';text-align:' . ($mode === 'hr' || $mode === 'r' ? 'right' : 'justify') . $deco . '">' . e($ln) . '</div>';
        }
    }
    return [$html, $hmm];
};
$descHtml = function (string $text, float $hh) use ($buildDesc, $arPt) {
    for ($pt = $arPt; $pt >= 6.5; $pt -= 0.25) {
        [$html, $hmm] = $buildDesc($text, $pt);
        if ($hmm <= $hh - 0.6) return $html;
    }
    return $html;
};
$items = array_values($m['items']);
$useTable = count($items) === count($ROWH);
$frag = function (int $i, float $off, float $hh, bool $split) use ($items, $descHtml, $box, $h, $ROWH, $useTable, $pdfMode) {
    $it = $items[$i];
    $inner = $descHtml((string) $it['label'], $useTable ? $ROWH[$i] : $hh);
    $inner = '<div style="height:' . round($hh - ($pdfMode ? 0.2 : 0), 2) . 'mm;overflow:hidden"><div style="position:relative;top:-' . $off . 'mm">' . $inner . '</div></div>';   // a row never grows past the form's row height
    $tr = '<tr><td style="' . $h($hh) . ';padding:0"></td>';
    foreach (['na', 'fail', 'pass'] as $code) {
        $tr .= '<td class="box" style="' . $h($hh) . ';padding:0' . ($split ? ';vertical-align:top' : '') . '">' . ($off > 0 ? '' : ($split ? '<div style="padding-top:0.2mm">' . $box($it['result'] === $code) . '</div>' : $box($it['result'] === $code))) . '</td>';
    }
    return $tr . '<td style="' . $h($hh) . ';padding-left:1.9mm;padding-right:0.6mm;vertical-align:top;font-weight:normal">' . $inner . '</td></tr>';
};
$open = '<table class="f mo trl cl" style="font-size:10pt">' . $sizer;
$pages = [];
$pages[] = ['body' => $cover . $gr, 'top' => 28.6, 'left' => $LEFT, 'width' => $TW];
$carry = null;      // [row, offset] of a row continued from the previous page
if ($useTable) {
    foreach ($PAGES as $pi => [$a, $b]) {
        $rows = '';
        if ($pi === 0) $rows .= $hdrCells((int) $hdrP1, $hdrH - $hdrP1);           // sliver of the column header row
        if ($carry) { $rows .= $frag($carry[0], $carry[1], $ROWH[$carry[0]] - $carry[1], true); $carry = null; }
        for ($r = $a; $r < $b; $r++) {
            if (isset($SPLIT[$r])) { $rows .= $frag($r, 0, $SPLIT[$r], true); $carry = [$r, $SPLIT[$r]]; }
            else $rows .= $frag($r, 0, $ROWH[$r], false);
        }
        $pages[] = ['body' => $open . $rows . '</table>', 'top' => 28.7, 'left' => $LEFT, 'width' => $TW];
    }
    if ($carry) $pages[] = ['body' => $open . $frag($carry[0], $carry[1], $ROWH[$carry[0]] - $carry[1], true) . '</table>', 'top' => 28.7, 'left' => $LEFT, 'width' => $TW];
} else {
    // the template no longer has the 55 rows of the form: plain flow with the row heights estimated from the text
    $bottom = 270.0; $rows = ''; $used = 28.7 + 5.1; $body = '';
    foreach ($items as $i => $it) {
        $hh = max(8.7, (substr_count((string) $it['label'], "\n") + 1) * 4.3 + 3);
        if ($used + $hh > $bottom && $rows !== '') { $pages[] = ['body' => $open . $rows . '</table>', 'top' => 28.7, 'left' => $LEFT, 'width' => $TW]; $rows = ''; $used = 28.7; }
        $rows .= $frag($i, 0, $hh, false); $used += $hh;
    }
    $pages[] = ['body' => $open . $rows . '</table>', 'top' => 28.7, 'left' => $LEFT, 'width' => $TW];
}

// ---------------------------------------------------------------- pictures page
$photos = $m['photos'] ?? [];
$img = fn ($s, $mw, $mh) => $s ? '<img src="' . $s . '" style="max-width:' . $mw . 'mm;max-height:' . $mh . 'mm" alt="">' : '';
$pt = '<table class="f mo trl" style="font-size:11pt;line-height:0.85">' . doc_sizer([82.8, 87.0])
    . '<tr><td colspan="2" class="c" style="background:' . $BL . ';' . $h(9.0) . '">' . $bi('صور المقطورة', 'Trailer Pictures', 10) . '</td></tr>';
for ($r = 0; $r < 3; $r++) {
    $pt .= '<tr>';
    foreach ([82.8, 87.0] as $c => $w) $pt .= '<td class="c" style="' . $h(66.05) . ';padding:0">' . $img($photos[$r * 2 + $c] ?? null, $w - 3, 62) . '</td>';
    $pt .= '</tr>';
}
$pages[] = ['body' => $pt . '</table>', 'top' => 28.7, 'left' => 20.0, 'width' => 170.0];

// ---------------------------------------------------------------- inspector notes + signatures
$insp = $m['inspector'] ?? null;
$rev = $m['reviewer'] ?? null;
$remarks = nl2br(e((string) ($m['checklist']['remarks'] ?? '')));
$sg = [28.6, 17.2, 35.9, 20.2, 48.2, 34.6];
$rowH = 12.7;
$sigImg = fn ($p, $ok) => ($ok && !empty($p['signature'])) ? '<img src="' . $p['signature'] . '" style="max-height:' . ($rowH - 2) . 'mm;max-width:33mm" alt="">' : '';
$sgrow = fn ($dt, $im, $name, $en, $ar) => '<tr><td class="c" style="' . $h($rowH) . '">' . $dt . '</td>' . $lab('التاريخ', 'Date', $rowH) . '<td class="c">' . $im . '</td>' . $lab('التوقيع', 'Sign', $rowH)
    . '<td class="c">' . e($name) . '</td>' . $lab($ar, $en, $rowH) . '</tr>';
$notes = '<table class="f mo trl" style="font-size:11pt;line-height:0.85">' . doc_sizer([92.5, 92.3])
    . '<tr><td style="background:' . $BL . ';font-weight:bold;' . $h(12.7) . '">Inspector Notes:</td><td style="background:' . $BL . ';text-align:right;font-weight:bold">' . $arh('ملاحظات المفتش:', 0, 10) . '</td></tr>'
    . '<tr><td colspan="2" style="' . $h(49.2) . ';vertical-align:top;padding-top:0.6mm">' . $remarks . '</td></tr></table>'
    . '<table class="f mo trl" style="font-size:11pt;line-height:0.85;margin-top:-0.5pt">' . doc_sizer(array_map(fn ($x) => $x * 184.8 / array_sum($sg), $sg))
    . $sgrow($m['checklist']['inspection_date'] ? e(fmt_date($m['checklist']['inspection_date'])) : '', $sigImg($insp, ($m['checklist']['status'] ?? '') === 'completed'), $insp['name'] ?? '', 'Inspector Name', 'أسم المفتش')
    . $sgrow(!empty($m['review_date']) ? e(fmt_date($m['review_date'])) : '', $sigImg($rev, true), $rev['name'] ?? '', 'Reviewed By', 'أسم المراجع') . '</table>';
$pages[] = ['body' => $notes, 'top' => 28.7, 'left' => 12.6, 'width' => 184.8];

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . $m['sj']['sub_job_no'],
    'pages'     => $pages,
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => ($m['checklist']['status'] ?? '') === 'draft' ? 'DRAFT' : null,
]);
