<?php
/**
 * Crane Health Check (F-7.5.1-CL 029): one 6-column table (details grid + Yes / No checks), reference photos grid, inspector / reviewer block.
 * Included from report.php when the layout's report style is 'ch'.  @var array $m  @var ?array $toolbar  @var array $L  @var array $R
 */
$tpl = $m['template'];
$C = $R['cover'] ?? [];
$data = $m['data'] ?? [];
$sj = $m['sj'];
$src = [
    'report_no' => $m['certificate_no'], 'client' => $sj['client_name'] ?? '', 'inspection_date' => fmt_date($m['checklist']['inspection_date']),
    'site' => $sj['location'] ?? '', 'model' => $sj['eq_model'] ?? '', 'serial' => $m['checklist']['serial_no'] ?? '',
];
$bound = array_diff_key($src, TemplateService::REPORT_DEFAULTS);
$value = fn ($key) => (string) (array_key_exists($key, $bound) ? $bound[$key] : (($data[$key] ?? '') !== '' ? $data[$key] : ($src[$key] ?? '')));
$hh = fn ($mm) => 'height:' . round($mm - 0.18, 2) . 'mm';
$box = fn ($on) => $on ? '&#9746;' : '&#9744;';
$W = [50.7, 54.0, 31.7, 15.9, 10.0, 28.8];            // union of the details grid (50.7 | 54 | 47.6 | 38.8) and the check grid (104.7 | 31.7 | 25.9 | 28.8)
$sizer = doc_sizer($W);
$open = '<table class="f ch">' . $sizer;
$tr = fn (float $mm, string $cells) => '<tr style="' . $hh($mm) . '">' . $cells . '</tr>';

// ---- first page: title, identification grid, crane details, column heads
$cover = $tr(8.48, '<td colspan="6" class="fl c b t12">' . e($tpl['title']) . '</td>');
foreach ($C['rows'] ?? [] as $row) {
    if ($row[0] === 'hdr4') {
        [$a, $b, $c, $d] = $row[1];
        $cover .= $tr(8.92, '<td class="b">' . e($a[0]) . '</td><td class="b c">' . e($b[0]) . '</td><td colspan="2" class="b c">' . e($c[0]) . '</td><td colspan="2" class="b c">' . e($d[0]) . '</td>')
            . $tr(8.48, '<td>' . e($value($a[1])) . '</td><td class="c">' . e($value($b[1])) . '</td><td colspan="2" class="c">' . e($value($c[1])) . '</td><td colspan="2" class="c">' . e($value($d[1])) . '</td>');
        if (!empty($C['details_band'] ?? 'CRANE DETAILS')) $cover .= $tr(8.48, '<td colspan="6" class="fl c b t12">CRANE DETAILS</td>');
    } elseif ($row[0] === 'quad') {
        [$a, $b] = $row[1];
        $cover .= $tr((float) ($row[2] ?? 8.48), '<td class="b">' . e($a[0]) . '</td><td class="c">' . e($value($a[1])) . '</td><td colspan="2" class="b t10">' . e($b[0]) . '</td><td colspan="2" class="c">' . e($value($b[1])) . '</td>');
    }
}
$cover .= $tr(6.17, '<td colspan="2" rowspan="2" class="c b t10">CHECKS / REQUIREMENTS</td><td colspan="4" class="c b t10">COMPLIANCE</td>')
    . $tr(6.17, '<td class="c b t10 yes">Yes</td><td colspan="2" class="c b t10">No</td><td class="c b t10">REMARKS</td>');
$coverH = 8.48 + 8.92 + 8.48 + 8.48;
foreach ($C['rows'] ?? [] as $row) if ($row[0] === 'quad') $coverH += (float) ($row[2] ?? 8.48);
$coverH += 12.34;

// ---- check rows
$avail = $W[0] + $W[1] - 3.8;
$font = 11.0;
$lines = function (string $text) use ($avail, $font) {
    $n = 0;
    $space = DocumentService::textWidthMm(' ', $font, false) ?? 1.0;
    foreach (preg_split('/\R/u', $text) as $para) {
        $bul = str_starts_with($para, '* ');
        if ($bul) $para = substr($para, 2);
        $av = $avail - ($bul ? 10.8 : 0);
        $n++; $len = 0.0;
        foreach (preg_split('/\s+/', trim($para)) as $w) {
            if ($w === '') continue;
            $wl = DocumentService::textWidthMm($w, $font, false) ?? mb_strlen($w) * 0.45 * $font * 0.3528;
            if ($len > 0 && $len + $space + $wl > $av) { $n++; $len = 0.0; }
            $len += ($len > 0 ? $space : 0) + $wl;
        }
    }
    return $n;
};
$labelHtml = function (string $text) {
    $o = '';
    foreach (preg_split('/\R/u', $text) as $ln) {
        if (str_starts_with($ln, '* ')) $o .= '<div style="padding-left:10.8mm"><span style="float:left;margin-left:-6.35mm;width:6.35mm;font-family:\'DejaVu Sans\';font-size:9pt">&#10003;</span>' . e(substr($ln, 2)) . '</div>';
        else $o .= '<div>' . e($ln) . '</div>';
    }
    return $o;
};
$sections = [];
foreach ($m['items'] as $it) $sections[$it['section']][] = $it;
$flow = [];
foreach ($sections as $name => $items) {
    $flow[] = ['html' => $tr(7.76, '<td colspan="6" class="b t10">' . e(TemplateService::sectionLabel($name)) . '</td>'), 'h' => 7.76, 'raw' => false];
    foreach ($items as $it) {
        $n = $lines((string) $it['label']);
        $h = $n <= 1 ? 7.76 : max(7.76, $n * 4.85);
        $flow[] = ['html' => $tr($h, '<td colspan="2" class="lb">' . $labelHtml((string) $it['label']) . '</td>'
            . '<td class="c bx">' . $box($it['result'] === 'pass') . '</td><td colspan="2" class="c bx">' . $box($it['result'] === 'fail') . '</td><td>' . e((string) $it['remarks']) . '</td>'), 'h' => $h, 'raw' => false];
    }
}
// reference photos band is the last row of the check table
$P = $R['pictures'] ?? [];
$flow[] = ['html' => $tr(7.76, '<td colspan="6" class="b">' . e($P['title'] ?? 'REFERENCE PHOTOS:') . '</td>'), 'h' => 7.76, 'raw' => false];
$pc = (int) ($P['cols'] ?? 2); $pr = (int) ($P['rows'] ?? 3); $ph = (float) ($P['h'] ?? 87.13);
$photos = $m['photos'] ?? [];
$pw = 191.1 / $pc;
for ($r = 0; $r < $pr; $r++) {
    $cells = '';
    for ($c = 0; $c < $pc; $c++) {
        $p = $photos[$r * $pc + $c] ?? null;
        $cells .= '<td class="c ph" style="' . $hh($ph) . '">' . ($p ? '<img src="' . $p . '" style="max-height:' . ($ph - 2) . 'mm;max-width:' . ($pw - 2) . 'mm" alt="">' : '') . '</td>';
    }
    $flow[] = ['html' => '<table class="f ch" style="margin-top:-0.35mm">' . doc_sizer(array_fill(0, $pc, $pw)) . '<tr style="' . $hh($ph) . '">' . $cells . '</tr></table>', 'h' => $ph, 'raw' => true];
}
// inspector / reviewer block
$insp = $m['inspector']; $rev = $m['reviewer'] ?? null;
$img = fn ($p, $ok) => ($ok && !empty($p['signature'])) ? '<img src="' . $p['signature'] . '" style="max-height:11mm;max-width:46mm" alt="">' : '';
$sg = '<table class="f ch" style="margin-top:-0.35mm">' . doc_sizer([47.8, 47.8, 45.6, 49.9])
    . '<tr style="' . $hh(14.11) . '"><td class="fl c b t10">INSPECTOR NAME</td><td class="c b t10">' . e($insp['name'] ?? '') . '</td><td rowspan="2" class="fl c b t10">SIGNATURE</td><td class="c">' . $img($insp, $m['checklist']['status'] === 'completed') . '</td></tr>'
    . '<tr style="' . $hh(13.95) . '"><td class="fl c b t10">REVIEWED BY</td><td class="c b t10">' . e($rev['name'] ?? '') . '</td><td class="c">' . $img($rev, true) . '</td></tr></table>';
$flow[] = ['html' => $sg, 'h' => 28.06, 'raw' => true];

// Dompdf ignores a height on <tr>: copy it onto every cell of the row
$fix = fn (string $t) => preg_replace_callback('~<tr style="(height:[^"]+)">(.*?)</tr>~s', fn ($mm) => '<tr>' . preg_replace('~<td\b(?![^>]*\bstyle=)~', '<td style="' . $mm[1] . '"', $mm[2]) . '</tr>', $t);

// ---- paginate
$bottom = (float) $L['bottom'];
$pages = [];
$body = $open . $cover;
$used = (float) ($C['top'] ?? $L['top']) + $coverH;
$inTable = true;
foreach ($flow as $b) {
    if ($used + $b['h'] > $bottom && $used > $L['top_next'] + 1) {
        $pages[] = ['body' => $fix($body . ($inTable ? '</table>' : '')), 'top' => $pages ? $L['top_next'] : ($C['top'] ?? $L['top']), 'left' => $L['left'], 'width' => 191.1];
        $body = $b['raw'] ? '' : $open;
        $inTable = !$b['raw'];
        $used = $L['top_next'];
    }
    if ($b['raw']) { if ($inTable) { $body .= '</table>'; $inTable = false; } $body .= $b['html']; }
    else { if (!$inTable) { $body .= $open; $inTable = true; } $body .= $b['html']; }
    $used += $b['h'];
}
$pages[] = ['body' => $fix($body . ($inTable ? '</table>' : '')), 'top' => $pages ? $L['top_next'] : ($C['top'] ?? $L['top']), 'left' => $L['left'], 'width' => 191.1];

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . $m['sj']['sub_job_no'],
    'pages'     => $pages,
    'sidetext'  => 'Doc Ref.  ' . $tpl['doc_ref'] . '   l   Rev. ' . $tpl['revision'] . '   l   Date: ' . $tpl['revision_date'],
    'qr'        => null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $m['checklist']['status'] === 'draft' ? 'DRAFT' : null,
]);
