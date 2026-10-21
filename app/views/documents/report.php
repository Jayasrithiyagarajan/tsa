<?php
/**
 * Report-style checklist (F-7.5.1-CL 10 Trailer & Semi-Trailer, CL 11 Petroleum Products Transportation Tanks):
 * page 1 = data cover (fields, VIN boxes, dimensions, regulation, compliance result), then Yes/No/N/A/Note tables
 * per section, a pictures grid (inspector photos), optional Inspector Notes box and Inspector / Reviewed By block.
 * @var array $m  @var ?array $toolbar  @var array $L (checklist layout)
 */
$tpl = $m['template'];
$R = $L['report'];
if (($R['style'] ?? '') === 'ch') { require __DIR__ . '/report_ch.php'; return; }
if (($R['ar']['style'] ?? '') === 'mo') { require __DIR__ . '/report_mo.php'; return; }
$pt = 0.3528;
$font = (float) ($R['item_font'] ?? $L['font']);
$cols = array_map('floatval', $R['cols'] ?? [94, 13.6, 13.8, 13.7, 47.4]);
$width = array_sum($cols);
$answers = $R['answers'] ?? [['pass', 'Yes'], ['fail', 'No'], ['na', 'N/A']];
$box = fn ($on) => $on ? '&#9746;' : '&#9744;';
$sizer = doc_sizer($cols);
$AR = $R['ar'] ?? null;                         // bilingual (Arabic / English) form: mirrored columns, Arabic labels
$arl = fn ($en) => $AR['labels'][$en] ?? '';
$arPt = (float) ($AR['pt'] ?? 8.5);
$bil = fn ($en, $w = 40, $pt = 8) => ($arl($en) !== '' ? '<div style="line-height:1;font-size:' . $pt . 'pt">' . ArabicText::html($arl($en), $w, $pt, true) . '</div>' : '') . '<div style="line-height:0.85">' . e($en) . '</div>';
$lineMm = $font * $L['line'] * $pt;

// ---------------------------------------------------------------- cover values
$sj = $m['sj'];
$data = $m['data'] ?? [];
$src = [
    'report_no' => $m['certificate_no'], 'report_date' => fmt_date($m['issue_date'] ?? null), 'client' => $sj['client_name'] ?? '',
    'inspection_date' => fmt_date($m['checklist']['inspection_date']), 'site' => $sj['location'] ?? '', 'eq_desc' => $sj['eq_description'] ?? '',
    'model' => $sj['eq_model'] ?? '', 'brand' => $sj['manufacturer'] ?? '', 'year' => $sj['eq_year'] ?? '',
    'serial' => $m['checklist']['serial_no'] ?? '', 'center_name' => 'TUV EAST',
];
$bound = array_diff_key($src, TemplateService::REPORT_DEFAULTS);       // report no., dates, client … always from the record
$value = fn ($key) => (string) (array_key_exists($key, $bound) ? $bound[$key] : (($data[$key] ?? '') !== '' ? $data[$key] : ($src[$key] ?? '')));

// compliance: any "No" (fail) or a rejected / repair certificate result → non-compliance
$fails = 0; $answered = 0;
foreach ($m['items'] as $it) { if ($it['result'] === 'fail') $fails++; if ($it['result']) $answered++; }
$compliant = null;
if ($fails > 0 || in_array($m['cert_result'] ?? null, ['rejected', 'repair'], true)) $compliant = false;
elseif ($answered === count($m['items']) && $answered > 0) $compliant = true;

$C = $R['cover'] ?? [];
$cw = (float) ($C['width'] ?? $width);
$lw = (float) ($C['label_w'] ?? 43.3);
$rowH = (float) ($C['row_h'] ?? 9.07);
$h = fn ($mm) => 'height:' . round($mm - 0.35, 2) . 'mm';
if ($AR) {
    $cvw = $cw - $lw;
    $cover = '<table style="width:' . $cw . 'mm;border-collapse:collapse">' . doc_sizer([$cw]) . '<tr><td class="c" style="border:0;padding:0 0 0.4mm"><div class="b" style="font-size:' . ($L['title_font'] - 3) . 'pt;line-height:1.1;font-weight:bold">' . e($tpl['title']) . '</div><div style="line-height:1;margin-top:0.8mm">' . ArabicText::html($AR['title_ar'] ?? '', 0, $L['title_font'], true) . '</div></td></tr></table>'
          . '<table class="f rp-cover" style="font-size:10pt;line-height:0.85">' . doc_sizer([$cw - $lw, $lw]);
    $pend = null;
    foreach ($C['rows'] ?? [] as $row) {
        switch ($row[0]) {
            case 'f': case 'text':
                $val = $row[0] === 'f' ? e($value($row[2])) : nl2br(e($row[2]));
                $cover .= '<tr><td class="c" style="' . $h($row[3] ?? $rowH) . '">' . $val . '</td><td class="shade b c">' . $bil($row[1], $lw - 4) . '</td></tr>';
                break;
            case 'band':
                if (str_contains($row[1], 'Chassis')) $cover .= '<tr class="shade"><td colspan="2" class="c b" style="' . $h($row[2] ?? 7) . '">' . e($row[1]) . ' - <span style="line-height:1;font-size:8.5pt">' . ArabicText::html($arl($row[1]), 0, 8.5, true) . '</span></td></tr>';
                else $pend = $row[1];
                break;
            case 'vin':
                $chars = preg_split('//u', strtoupper($value($row[1])), -1, PREG_SPLIT_NO_EMPTY);
                $n = ''; $v = '';
                for ($i = 0; $i < 17; $i++) {
                    $n .= '<td class="c" style="' . $h($row[2] ?? 6) . ';padding:0">' . ($i + 1) . '</td>';
                    $v .= '<td class="c" style="' . $h($row[3] ?? 9.7) . ';padding:0;font-size:12pt">' . e($chars[$i] ?? '') . '</td>';
                }
                $cover .= '<tr><td colspan="2" style="padding:0;border:0"><table class="f" style="font-size:9pt;line-height:0.75">' . doc_sizer(array_fill(0, 17, $cw / 17)) . '<tr>' . $n . '</tr><tr>' . $v . '</tr></table></td></tr>';
                break;
            case 'triple':
                $gl = (float) ($row[3] ?? $lw);
                $cwid = ($cw - $gl) / 6;
                $cells = '';
                foreach ($row[1] as [$label, $key]) $cells .= '<td class="c" style="' . $h($row[2] ?? 10.3) . '">' . e($value($key)) . '</td><td class="shade b c">' . $bil($label, $cwid - 3) . '</td>';
                $cells .= '<td class="shade b c">' . $bil((string) $pend, $gl - 3) . '</td>';
                $cover .= '<tr><td colspan="2" style="padding:0;border:0"><table class="f" style="font-size:10pt">' . doc_sizer(array_merge(array_fill(0, 6, $cwid), [$gl])) . '<tr>' . $cells . '</tr></table></td></tr>';
                break;
            case 'result':
                $rh = $row[2] ?? 12.85;
                $radioW = (float) ($row[3] ?? 12.7);
                $radio = fn ($on) => '<span class="radio" style="font-size:11pt;color:#555">' . ($on ? '&#9673;' : '&#9711;') . '</span>';
                $tw = $cw - $lw - $radioW;
                $cover .= '<tr><td colspan="2" style="padding:0;border:0"><table class="f" style="font-size:10pt">' . doc_sizer([$tw, $radioW, $lw])
                    . '<tr><td class="c b" style="' . $h($rh) . ';color:#00a651">Compliance to Requirements<div style="line-height:1;font-size:8pt">' . ArabicText::html('مطابق للمتطلبات', 0, 8, true) . '</div></td><td class="c">' . $radio($compliant === true) . '</td><td rowspan="2" class="shade b c">' . $bil($row[1] ?? 'Inspection Result', $lw - 4) . '</td></tr>'
                    . '<tr><td class="c b" style="' . $h($rh) . ';color:#e00000">Non-Compliance to Requirements<div style="line-height:1;font-size:8pt">' . ArabicText::html('غير مطابق للمتطلبات', 0, 8, true) . '</div></td><td class="c">' . $radio($compliant === false) . '</td></tr></table></td></tr>';
                break;
        }
    }
    $cover .= '</table>';
} else {
$cover = '<table class="f rp-cover" style="font-size:10pt">' . doc_sizer([$lw, $cw - $lw])
    . '<tr class="shade"><td colspan="2" class="c b" style="' . $h($C['title_h'] ?? 10.3) . ';font-size:' . $L['title_font'] . 'pt">' . e($tpl['title']) . '</td></tr>';
$sub = fn ($inner, $widths) => '<tr><td colspan="2" style="padding:0;border:0"><table class="f" style="font-size:10pt">' . doc_sizer($widths) . $inner . '</table></td></tr>';
foreach ($C['rows'] ?? [] as $row) {
    switch ($row[0]) {
        case 'f':       // label | value
            $cover .= '<tr><td class="shade b c" style="' . $h($row[3] ?? $rowH) . '">' . e($row[1]) . '</td><td class="c">' . e($value($row[2])) . '</td></tr>';
            break;
        case 'text':    // label | fixed text (regulation / standard)
            $cover .= '<tr><td class="shade b c" style="' . $h($row[3] ?? $rowH) . '">' . e($row[1]) . '</td><td class="c b">' . nl2br(e($row[2])) . '</td></tr>';
            break;
        case 'band':
            $cover .= '<tr class="shade"><td colspan="2" class="c b" style="' . $h($row[2] ?? 7) . '">' . e($row[1]) . '</td></tr>';
            break;
        case 'vin':     // 17 numbered boxes
            $chars = preg_split('//u', strtoupper($value($row[1])), -1, PREG_SPLIT_NO_EMPTY);
            $n = '';
            $v = '';
            for ($i = 0; $i < 17; $i++) {
                $n .= '<td class="c' . (!empty($row[4]) ? ' b' : '') . '" style="' . $h($row[2] ?? 6) . ';padding:0">' . ($i + 1) . '</td>';
                $v .= '<td class="c" style="' . $h($row[3] ?? 9.7) . ';padding:0;font-size:12pt">' . e($chars[$i] ?? '') . '</td>';
            }
            $cover .= $sub('<tr>' . $n . '</tr><tr>' . $v . '</tr>', array_fill(0, 17, $cw / 17));
            break;
        case 'triple':  // label | value × 3 (dimensions / axle weights)
            $w = $row[3] ?? [32.2, 28.5, 30.4, 30.3, 30.4, 30.7];
            $scale = $cw / array_sum($w);
            $cells = '';
            foreach ($row[1] as [$label, $key]) $cells .= '<td class="shade b' . (empty($row[4]) ? '' : ' c') . '" style="' . $h($row[2] ?? 10.3) . '">' . e($label) . '</td><td class="c">' . e($value($key)) . '</td>';
            $cover .= $sub('<tr>' . $cells . '</tr>', array_map(fn ($x) => $x * $scale, $w));
            break;
        case 'result':  // Inspection Result: ◉ Compliance / ◉ Non-Compliance
            $rh = $row[2] ?? 12.85;
            $radioW = (float) ($row[3] ?? 42.6);
            $radio = fn ($on) => '<span class="radio" style="font-size:11pt;color:#555">' . ($on ? '&#9673;' : '&#9711;') . '</span>';
            $cover .= $sub('<tr><td rowspan="2" class="shade b c" style="' . $h($rh) . '">' . e($row[1] ?? 'Inspection Result') . '</td>'
                . '<td class="c">' . $radio($compliant === true) . '</td><td class="c b" style="color:#00a651">Compliance to Requirements</td></tr>'
                . '<tr><td class="c" style="' . $h($rh) . '">' . $radio($compliant === false) . '</td><td class="c b" style="color:#e00000">Non-Compliance to Requirements</td></tr>',
                [$lw, $radioW, $cw - $lw - $radioW]);
            break;
    }
}
$cover .= '</table>';
}

// ---------------------------------------------------------------- item rows
$avail = ($AR ? $cols[4] : $cols[0]) - 4.3;                       // matches the cell padding below (1.9 + 2.4 mm)
$wrapLines = function (string $text, bool $bold) use ($avail, $font) {
    $space = DocumentService::textWidthMm(' ', $font, $bold) ?? 1.0;
    $lines = 1; $len = 0.0;
    foreach (preg_split('/\s+/', trim($text)) as $w) {
        if ($w === '') continue;
        $wl = DocumentService::textWidthMm($w, $font, $bold) ?? mb_strlen($w) * 0.45 * $font * 0.3528;
        if ($len > 0 && $len + $space + $wl > $avail) { $lines++; $len = 0.0; }
        $len += ($len > 0 ? $space : 0) + $wl;
    }
    return $lines;
};
$renderItem = function (array $it) use ($wrapLines, $L, $lineMm, $answers, $box, $font, $AR, $arPt, $avail) {
    $html = '';
    $lines = 0;
    $arMm = $arPt * (float) ($AR['line'] ?? 1.1) * 0.3528 * 1.34;
    $arExtra = 0.0;
    foreach (preg_split('/\R/u', (string) $it['label']) as $ln) {
        if ($AR && ArabicText::has($ln)) {
            $head = str_starts_with($ln, '## ');
            $t = $head ? substr($ln, 3) : $ln;
            $n = count(ArabicText::wrap($t, $avail * 0.97, $arPt, $head));
            $arExtra += $n * $arMm;
            $html .= '<div style="font-size:' . $arPt . 'pt;text-align:right;line-height:' . ($AR['line'] ?? 1.1) . ';' . ($head ? 'font-weight:bold;text-decoration:underline' : '') . '">' . ArabicText::html($t, $avail, $arPt, $head) . '</div>';
            continue;
        }
        if (str_starts_with($ln, '#> ')) {
            $t = substr($ln, 3); $lines += $wrapLines($t, true);
            $html .= '<div style="font-weight:bold;text-decoration:underline;text-align:right">' . e($t) . '</div>';
        } elseif (str_starts_with($ln, '## ')) {
            $t = substr($ln, 3); $lines += $wrapLines($t, true);
            $html .= '<div style="font-weight:bold;text-decoration:underline">' . e($t) . '</div>';
        } elseif (str_starts_with($ln, '>> ')) {
            $t = substr($ln, 3); $lines += $wrapLines($t, $L['item_bold']);
            $html .= '<div style="text-align:right">' . e($t) . '</div>';
        } else {
            $lines += $wrapLines($ln, $L['item_bold']);
            $html .= '<div>' . e($ln) . '</div>';
        }
    }
    $hgt = max($L['row_h'], $lines * $lineMm + $arExtra + 1.2);
    if ($AR) {
        $tr = '<tr><td class="rem" style="font-weight:normal">' . e($it['remarks']) . '</td>';
        foreach (array_reverse($answers) as $a) $tr .= '<td class="box">' . $box($it['result'] === $a[0]) . '</td>';
        return [$tr . '<td style="height:' . round($L['row_h'] - 0.35, 2) . 'mm;font-weight:' . ($L['item_bold'] ? 'bold' : 'normal') . ';padding-left:1.9mm;padding-right:2.4mm;text-align:justify"><div style="line-height:' . round($L['line'] / 1.344, 3) . '">' . $html . '</div></td></tr>', $hgt];
    }
    $tr = '<tr><td style="height:' . round($L['row_h'] - 0.35, 2) . 'mm;font-weight:' . ($L['item_bold'] ? 'bold' : 'normal')
        . ';padding-left:1.9mm;padding-right:2.4mm"><div style="line-height:' . round($L['line'] / 1.344, 3) . '">' . $html . '</div></td>';
    foreach ($answers as [$code]) $tr .= '<td class="box">' . $box($it['result'] === $code) . '</td>';
    return [$tr . '<td class="rem" style="font-weight:normal">' . e($it['remarks']) . '</td></tr>', $hgt];
};

// blocks: each is [html rows, height, keepWithNext]; a section header is kept with its first item
$bandH = (float) ($R['band_h'] ?? 7.8);
$introH = (float) ($R['intro_h'] ?? 7.8);
$colH = (float) ($R['colhead_h'] ?? 10.3);
$align = ($R['band_align'] ?? 'left') === 'center' ? 'c' : '';
$colHead = '<tr class="shade b"><td class="c" style="' . $h($colH) . '">Description</td>';
foreach ($answers as [, $label]) $colHead .= '<td class="c">' . e($label) . '</td>';
$colHead .= '<td class="c">' . e($R['note_label'] ?? 'Note') . '</td></tr>';

// bilingual header (repeats on every page): band | intro | column heads, mirrored (Note, N/A, No, Yes, Description)
$arHdr = '';
$arHdrH = 0.0;
if ($AR) {
    $sp = $cols[1] + $cols[2] + $cols[3] + $cols[0];
    $g = 'background:#f2f2f2;font-weight:bold;text-align:center';
    $bandTitle = 'General Requirements:';
    $introTxt = $R['intros']['1) The General Requirements:'] ?? '';
    $arHdr = '<tr><td colspan="4" style="' . $g . ';' . $h($bandH) . '">' . e($bandTitle) . '</td><td style="' . $g . '">' . ArabicText::html($arl($bandTitle), 0, 9, true) . '</td></tr>'
        . '<tr class="shade"><td colspan="4" style="font-weight:bold;text-align:center;' . $h($introH) . '">' . e($introTxt) . '</td><td style="font-weight:bold;text-align:center;font-size:' . $arPt . 'pt">' . ArabicText::html($arl($introTxt), $cols[4] - 4, $arPt, true) . '</td></tr>'
        . '<tr class="shade b"><td class="c" style="' . $h($colH) . '">' . $bil($R['note_label'] . ':', $cols[0] - 3) . '</td>';
    foreach (array_reverse($answers) as [, $label]) $arHdr .= '<td class="c">' . $bil($label, $cols[1] - 2, 8.5) . '</td>';
    $arHdr .= '<td class="c">' . $bil('Description', $cols[4] - 3) . '</td></tr>';
    $arHdrH = $bandH + $introH + $colH;
}

$sections = [];
foreach ($m['items'] as $it) $sections[$it['section']][] = $it;
$flow = [];   // list of ['html' => tr, 'h' => mm, 'gap' => mm before (new table)]
$first = true;
foreach ($sections as $name => $items) {
    $title = TemplateService::sectionLabel($name);
    $gap = !$first && in_array($title, $R['gap_before'] ?? [], true) ? 7.2 : 0;
    $hdr = '<tr class="shade b"><td colspan="5" class="' . $align . '" style="' . $h($bandH) . '">' . e($title) . '</td></tr>';
    $hh = $bandH;
    if (!empty($R['intros'][$title])) { $hdr .= '<tr class="shade b"><td colspan="5" class="c" style="' . $h($introH) . '">' . e($R['intros'][$title]) . '</td></tr>'; $hh += $introH; }
    $hdr .= $colHead;
    $hh += $colH;
    foreach ($items as $i => $it) {
        [$tr, $ih] = $renderItem($it);
        $flow[] = ['html' => ($i === 0 && !$AR ? $hdr : '') . $tr, 'h' => ($i === 0 && !$AR ? $hh : 0) + $ih, 'gap' => $i === 0 ? $gap : 0];
    }
    $first = false;
}

// ---------------------------------------------------------------- tail blocks: pictures, notes, signatures
$tail = [];
if (!empty($R['pictures'])) {
    $P = $R['pictures'];
    $pc = (int) ($P['cols'] ?? 2); $pr = (int) ($P['rows'] ?? 3); $ph = (float) ($P['h'] ?? 45.8);
    $photos = $m['photos'] ?? [];
    $rowsHtml = '';
    for ($r = 0; $r < $pr; $r++) {
        $rowsHtml .= '<tr>';
        for ($c = 0; $c < $pc; $c++) {
            $src = $photos[$r * $pc + $c] ?? null;
            $rowsHtml .= '<td class="c" style="' . $h($ph) . ';padding:1mm">' . ($src ? '<img src="' . $src . '" style="max-height:' . ($ph - 3) . 'mm;max-width:' . (($P['width'] ?? $width) / $pc - 3) . 'mm" alt="">' : '') . '</td>';
        }
        $rowsHtml .= '</tr>';
    }
    $pw = (float) ($P['width'] ?? $width);
    $tail[] = ['<table class="f" style="font-size:10.5pt' . (!empty($P['left']) ? ';margin-left:' . $P['left'] . 'mm' : '') . '">' . doc_sizer(array_fill(0, $pc, $pw / $pc))
        . '<tr class="shade"><td colspan="' . $pc . '" class="c b" style="' . $h($P['title_h'] ?? 7) . '">' . ($AR ? $bil($P['title'] ?? 'Pictures', 60) : e($P['title'] ?? 'Pictures')) . '</td></tr>' . $rowsHtml . '</table>',
        ($P['title_h'] ?? 7) + $pr * $ph + ($P['gap'] ?? 0), $P['gap'] ?? 0];
}
$insp = $m['inspector'];
$third = $width / 3;
$sigHtml = '';
$sigH = 0;
if (!empty($R['notes_box'])) {
    $N = $R['notes_box'];
    $sigHtml .= '<table class="f" style="font-size:10.5pt;margin-top:' . ($N['gap'] ?? 5) . 'mm">' . doc_sizer([$width])
        . '<tr class="shade"><td class="b" style="' . $h($N['label_h'] ?? 7.8) . '">' . e($N['label'] ?? 'Inspector Notes:') . '</td></tr>'
        . '<tr><td style="' . $h($N['h'] ?? 19) . ';vertical-align:top;padding-top:1mm">' . nl2br(e($m['checklist']['remarks'] ?? '')) . '</td></tr></table>';
    $sigH += ($N['gap'] ?? 5) + ($N['label_h'] ?? 7.8) + ($N['h'] ?? 19);
}
$rev = $m['reviewer'] ?? null;
if ($AR) {
    $N = $R['notes_box'] ?? [];
    $sg = $R['sign'] ?? [28.0, 16.8, 35.3, 19.8, 47.3, 34.0];
    $sc = $width / array_sum($sg);
    $sg = array_map(fn ($x) => $x * $sc, $sg);
    $rowH = (float) ($R['sign_h'] ?? 12.7);
    $sigHtml = '<table class="f" style="font-size:10.5pt;margin-top:' . ($N['gap'] ?? 5) . 'mm">' . doc_sizer([$width / 2, $width / 2])
        . '<tr class="shade"><td class="b" style="' . $h($N['label_h'] ?? 12.7) . '">' . e($N['label'] ?? 'Inspector Notes:') . '</td><td class="b c" style="text-align:right">' . ArabicText::html($arl($N['label'] ?? 'Inspector Notes:'), 0, 9.5, true) . '</td></tr>'
        . '<tr><td colspan="2" style="' . $h($N['h'] ?? 19) . ';vertical-align:top;padding-top:1mm">' . nl2br(e($m['checklist']['remarks'] ?? '')) . '</td></tr></table>';
    $row = fn ($dt, $img, $name, $lab) => '<tr><td class="c" style="' . $h($rowH) . '">' . $dt . '</td><td class="shade b c">' . $bil('Date', $sg[1] - 2) . '</td><td class="c">' . $img . '</td><td class="shade b c">' . $bil('Sign', $sg[3] - 2) . '</td><td class="c">' . e($name) . '</td><td class="shade b c">' . $bil($lab, $sg[5] - 2) . '</td></tr>';
    $sigHtml .= '<table class="f" style="font-size:10.5pt">' . doc_sizer($sg)
        . $row($m['checklist']['inspection_date'] ? e(fmt_date($m['checklist']['inspection_date'])) : '', !empty($insp['signature']) && $m['checklist']['status'] === 'completed' ? '<img src="' . $insp['signature'] . '" style="max-height:' . ($rowH - 2) . 'mm" alt="">' : '', $insp['name'] ?? '', 'Inspector Name')
        . $row(!empty($m['review_date']) ? e(fmt_date($m['review_date'])) : '', !empty($rev['signature']) ? '<img src="' . $rev['signature'] . '" style="max-height:' . ($rowH - 2) . 'mm" alt="">' : '', $rev['name'] ?? '', 'Reviewed By')
        . '</table>';
    $tail[] = [$sigHtml, ($N['gap'] ?? 5) + ($N['label_h'] ?? 12.7) + ($N['h'] ?? 19) + 2 * $rowH, 0];
} else {
$sigHtml .= '<table class="f sig" style="margin-top:' . (empty($R['notes_box']) ? $L['sig_gap'] : 0) . 'mm">' . doc_sizer([$third, $third, $third])
    . '<tr class="h shade"><td>Inspector Name</td><td>Inspector Signature</td><td>Date of Inspection</td></tr>'
    . '<tr class="v"><td>' . e($insp['name'] ?? '') . '</td><td>' . (!empty($insp['signature']) && $m['checklist']['status'] === 'completed' ? '<img src="' . $insp['signature'] . '" alt="">' : '')
    . '</td><td>' . ($m['checklist']['inspection_date'] ? e(fmt_date($m['checklist']['inspection_date'])) : '<span class="muted">—</span>') . '</td></tr>';
$sigH += (empty($R['notes_box']) ? $L['sig_gap'] : 0) + 10.6 + 20.3;
if ($L['sig_review']) {
    $sigHtml .= '<tr class="h shade"><td>Reviewed By</td><td>Signature</td><td>Date</td></tr>'
        . '<tr class="v"><td>' . e($rev['name'] ?? '') . '</td><td>' . (!empty($rev['signature']) ? '<img src="' . $rev['signature'] . '" alt="">' : '')
        . '</td><td>' . (!empty($m['review_date']) ? e(fmt_date($m['review_date'])) : '<span class="muted">—</span>') . '</td></tr>';
    $sigH += 10.6 + 20.3;
}
$sigHtml .= '</table>';
$tail[] = [$sigHtml, $sigH, 0];
}

// ---------------------------------------------------------------- paginate
$firstBody = $cover;
if ($AR) $firstBody .= '<div style="height:' . ($AR['hdr_gap'] ?? 7) . 'mm"></div><table class="f cl" style="font-size:' . $font . 'pt">' . $sizer . $arHdr . '</table>';
$pages = [['body' => $firstBody, 'top' => $C['top'] ?? 28.6, 'left' => $C['left'] ?? $L['left'], 'width' => $cw]];
$bottom = $L['bottom'];
$open = '<table class="f cl" style="font-size:' . $font . 'pt">' . $sizer . $arHdr;
$body = '';
$table = '';
$used = $L['top_next'] + $arHdrH;
$flush = function () use (&$body, &$table) { if ($table !== '') { $body .= $table . '</table>'; $table = ''; } };
$newPage = function () use (&$pages, &$body, &$used, $flush, $L, $arHdrH) {
    $flush();
    $pages[] = ['body' => $body, 'top' => $L['top_next'], 'left' => $L['left'], 'width' => 0];
    $body = '';
    $used = $L['top_next'] + $arHdrH;
};
foreach ($flow as $b) {
    if ($body . $table !== '' && $used + $b['gap'] + $b['h'] > $bottom) $newPage();
    if ($b['gap'] > 0 && $table !== '') { $flush(); $body .= '<div style="height:' . $b['gap'] . 'mm"></div>'; $used += $b['gap']; }
    if ($table === '') { $table = $open; if ($body === '' && !$AR) {} }
    $table .= $b['html'];
    $used += $b['h'];
}
// pictures + notes + signatures stay together when they fit on one page (as on the Word forms)
$tailTotal = array_sum(array_column($tail, 1));
if (count($tail) > 1 && $tailTotal <= $bottom - $L['top_next']) {
    $tail = [[implode('', array_column($tail, 0)), $tailTotal, 0]];
}
foreach ($tail as [$html, $th, $gap]) {
    if ($body . $table !== '' && $used + $th > $bottom) { $newPage(); $used -= $arHdrH; }
    $flush();
    if ($gap > 0 && $body !== '') $body .= '<div style="height:' . $gap . 'mm"></div>';
    $body .= $html;
    $used += $th;
}
$newPage();
foreach ($pages as &$pg) { if (!$pg['width']) $pg['width'] = $width; }
unset($pg);

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . $m['sj']['sub_job_no'],
    'pages'     => $pages,
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $m['checklist']['status'] === 'draft' ? 'DRAFT' : null,
]);
