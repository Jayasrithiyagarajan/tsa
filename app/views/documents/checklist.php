<?php
/**
 * Checklist in the F-7.5.1-CL pattern (CL 01 Boom Truck, CL 02 Forklift, CL 03 Generator …).
 * Column widths, row height, fonts and header wording come from the template's print layout.
 * @var array $m  @var ?array $toolbar
 */
$tpl = $m['template'];
$L = TemplateService::checklistLayout($tpl);
if ($L['kind'] === 'report') {                  // CL 10-11: cover page + Yes/No/N/A tables
    echo View::partial('documents/report', ['m' => $m, 'toolbar' => $toolbar ?? null, 'L' => $L]);
    return;
}
[$cLabel, $cValue, $cPass, $cFail, $cNa, $cRem] = $L['cols'];
$box = fn ($on) => $on ? '&#9746;' : '&#9744;';
// Dompdf sizes tables from the first row, so every table starts with an invisible sizer row
$sizer = doc_sizer($L['cols']);
$pt = 0.3528;                                   // mm per point
$rowCss = 'height:' . round($L['row_h'] - 0.35, 2) . 'mm';
$cellFont = 'font-size:' . $L['font'] . 'pt;line-height:' . round($L['font'] * 1.08 * $pt, 2) . 'mm';

/** Estimated rendered height (mm) of a row whose text wraps inside $widthMm (real font metrics when available). */
$estimate = function (string $text, float $widthMm, float $fontPt, bool $bold = true) use ($L, $pt): float {
    $avail = $widthMm - 6.4;                                // Word cell margins (text wraps ~6.4 mm before the border)
    $space = DocumentService::textWidthMm(' ', $fontPt, $bold);
    $perLine = max(10, (int) floor($avail / (0.405 * $fontPt * $pt)));
    $lines = 0;
    foreach (preg_split('/\R/', $text) ?: [''] as $para) {
        $lines++;
        $len = 0.0;
        foreach (preg_split('/\s+/', trim($para)) as $w) {
            if ($w === '') continue;
            $wl = $space !== null ? DocumentService::textWidthMm($w, $fontPt, $bold) : mb_strlen($w) * $avail / $perLine;
            $sp = $space ?? $avail / $perLine;
            if ($len > 0 && $len + $sp + $wl > $avail) { $lines++; $len = 0.0; }
            $len += ($len > 0 ? $sp : 0) + $wl;
        }
    }
    return max($L['row_h'], $lines * $fontPt * $L['line'] * $pt + 0.4);
};

// Dompdf renders Calibri at ~1.344 × font per line; scale the CSS line-height to the form's line pitch
$labelLh = round($L['line'] / 1.344, 3);

// rows: [html, heightMm]; sections after the first are shaded bands (full / blank / split), the first sits in the header
$sections = [];
foreach ($m['items'] as $it) $sections[$it['section']][] = $it;
$firstSection = array_key_first($sections) ?? 'General Requirements';
$rows = [];
foreach ($sections as $name => $items) {
    if ($name !== $firstSection) {
        $style = TemplateService::sectionStyle($name);
        $text = $style === 'blank' ? '' : e(TemplateService::sectionLabel($name));
        $bandCss = $rowCss . ';' . $cellFont . ($L['band_align'] === 'center' ? ';text-align:center' : '');
        $rows[] = [$style === 'split'
            ? '<tr class="sec"><td colspan="2" style="' . $bandCss . '">' . $text . '</td><td></td><td></td><td></td><td></td></tr>'
            : '<tr class="sec"><td colspan="6" style="' . $bandCss . '">' . $text . '</td></tr>', $L['row_h']];
    }
    foreach ($items as $it) {
        $h = max($estimate($it['label'], $cLabel + $cValue, $L['font'], $L['item_bold']), $estimate((string) $it['remarks'], $cRem, 9, false));
        $rows[] = ['<tr><td colspan="2" style="' . $rowCss . ';' . $cellFont . ';padding-left:1.9mm;padding-right:2.4mm' . ($L['item_bold'] ? '' : ';font-weight:normal') . '"><div style="line-height:' . $labelLh . '">' . nl2br(e($it['label'])) . '</div></td>'
            . '<td class="box">' . $box($it['result'] === 'pass') . '</td>'
            . '<td class="box">' . $box($it['result'] === 'fail') . '</td>'
            . '<td class="box">' . $box($it['result'] === 'na') . '</td>'
            . '<td class="rem">' . e($it['remarks']) . '</td></tr>', $h];
    }
}

$hdrCss = 'height:' . round($L['hdr_row_h'] - 0.35, 2) . 'mm;' . $cellFont;
$val = fn ($v) => '<td style="font-weight:normal">' . e($v) . '</td>';
$hdrRows = 1;
switch ($L['header']) {
    case 'cert_serial_std':          // Certificate No. / Serial No. + two-row Ref. Standard block (CL 07-09)
        $ident = '<tr><td style="' . $hdrCss . '">Certificate No.</td>' . $val($m['certificate_no'])
            . '<td colspan="3" rowspan="2" class="c">' . e($L['std_label']) . '</td>'
            . '<td rowspan="2" class="c" style="font-weight:normal">' . nl2br(e($L['std_text'])) . '</td></tr>'
            . '<tr><td style="' . $hdrCss . '">Serial No.</td>' . $val($m['checklist']['serial_no']) . '</tr>';
        $hdrRows = 2;
        break;
    case 'elevator':                 // S.N. / Job No. - Location / Date - Ref. Standard (CL 06)
        $ident = '<tr><td style="' . $hdrCss . '">S.N.</td>' . $val($m['checklist']['serial_no'])
            . '<td colspan="3" class="c">Job No.</td>' . $val($m['sj']['sub_job_no'] ?? '') . '</tr>'
            . '<tr><td style="' . $hdrCss . '">Location</td>' . $val($m['sj']['location'] ?? '')
            . '<td colspan="3" class="c">Date:</td><td class="c" style="font-weight:normal">' . e(fmt_date($m['checklist']['inspection_date'])) . '</td></tr>'
            . '<tr><td style="' . $hdrCss . '">' . e($L['std_label']) . '</td><td colspan="5" class="c" style="font-weight:normal">' . nl2br(e($L['std_text'])) . '</td></tr>';
        $hdrRows = 3;
        break;
    default:                         // Certificate No. / Serial No. (CL 01-05)
        $ident = '<tr><td class="c" style="' . $rowCss . ';' . $cellFont . '">Certificate No.</td>' . $val($m['certificate_no'])
            . '<td colspan="3" class="c">Serial No.</td>' . $val($m['checklist']['serial_no']) . '</tr>';
}
$headH = $L['title_h'] + ($L['header'] === 'cert_serial' ? $L['row_h'] : $hdrRows * $L['hdr_row_h']) + $L['head_h'];
$headRow = 'height:' . round($L['head_h'] / 2 - 0.3, 2) . 'mm';
$head = '<tr class="title shade"><td colspan="6" style="font-size:' . $L['title_font'] . 'pt;height:' . round($L['title_h'] - 0.35, 2) . 'mm">' . e($tpl['title']) . '</td></tr>'
    . $ident
    . '<tr class="head shade"><td colspan="2" class="c" style="border-bottom:0;' . $headRow . '">' . e($L['desc_label']) . '</td>'
    . '<td rowspan="2" class="c">PASS</td><td rowspan="2" class="c">FAIL</td><td rowspan="2" class="c">N/A</td><td rowspan="2" class="c">' . e($L['rem_label']) . '</td></tr>'
    . '<tr class="head shade"><td colspan="2" style="border-top:0;' . $headRow . '">' . e(TemplateService::sectionLabel($firstSection)) . '</td></tr>';

$insp = $m['inspector'];
$third = $L['width'] / 3;
$sig = '<table class="f sig" style="margin-top:' . $L['sig_gap'] . 'mm">' . doc_sizer([$third, $third, $third])
    . '<tr class="h shade"><td>Inspector Name</td><td>Inspector Signature</td><td>Date of Inspection</td></tr>'
    . '<tr class="v"><td>' . e($insp['name'] ?? '') . '</td><td>'
    . (!empty($insp['signature']) && $m['checklist']['status'] === 'completed' ? '<img src="' . $insp['signature'] . '" alt="">' : '')
    . '</td><td>' . ($m['checklist']['inspection_date'] ? e(fmt_date($m['checklist']['inspection_date'])) : '<span class="muted">—</span>') . '</td></tr>';
$sigH = $L['sig_gap'] + 10.6 + 20.3;
if ($L['sig_review']) {              // QC reviewer block (CL 09)
    $rev = $m['reviewer'] ?? null;
    $sig .= '<tr class="h shade"><td>Reviewed By</td><td>Signature</td><td>Date</td></tr>'
        . '<tr class="v"><td>' . e($rev['name'] ?? '') . '</td><td>' . (!empty($rev['signature']) ? '<img src="' . $rev['signature'] . '" alt="">' : '')
        . '</td><td>' . (!empty($m['review_date']) ? e(fmt_date($m['review_date'])) : '<span class="muted">—</span>') . '</td></tr>';
    $sigH += 10.6 + 20.3;
}
$sig .= '</table>';
$remarksBox = '';
if ($L['remarks_box'] > 0) {         // "Remarks /" box under the items (CL 06)
    $remarksBox = '<tr><td colspan="6" style="height:' . round($L['remarks_box'] - 0.35, 2) . 'mm;vertical-align:top;padding-top:1mm;' . $cellFont . '">Remarks / '
        . '<span style="font-weight:normal">' . nl2br(e($m['checklist']['remarks'] ?? '')) . '</span></td></tr>';
    $sigH += $L['remarks_box'];
}

// Height-based pagination between the header band and the footer band (277.6 mm)
$bottom = $L['bottom'];
$pages = [];
$chunk = [];
$used = $L['top'] + $headH;
foreach ($rows as [$html, $h]) {
    if ($chunk && $used + $h > $bottom) {
        $pages[] = $chunk;
        $chunk = [];
        $used = $L['top_next'];
    }
    $chunk[] = $html;
    $used += $h;
}
if ($chunk && $used + $sigH > $bottom) {      // signature block does not fit → move it to a new page
    $pages[] = $chunk;
    $chunk = [];
}
$pages[] = $chunk;

$out = [];
foreach ($pages as $i => $chunk) {
    $html = '';
    $style = ' style="' . $cellFont . '"';
    $last = $i === count($pages) - 1;
    $tail = $last ? $remarksBox : '';
    if ($i === 0) {
        $html .= '<table class="f cl"' . $style . '>' . $sizer . $head . implode('', $chunk) . $tail . '</table>';
    } elseif ($chunk || $tail) {
        $html .= '<table class="f cl"' . $style . '>' . $sizer . implode('', $chunk) . $tail . '</table>';
    }
    if ($i === count($pages) - 1) $html .= $sig;
    $out[] = ['body' => $html, 'top' => $i === 0 ? $L['top'] : $L['top_next'], 'left' => $L['left'], 'width' => $L['width']];
}

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . $m['sj']['sub_job_no'],
    'pages'     => $out,
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => DocumentService::qr(abs_url('verify') . '?certificate_no=' . rawurlencode((string) ($m['certificate_no'] ?? ''))),
    'qr_pos'    => 'top',
    'toolbar'   => $toolbar ?? null,
    'watermark' => $m['checklist']['status'] === 'draft' ? 'DRAFT' : null,
]);
