<?php
/** Destructive (pull) test report - exact layout of the supplied PDF, one page. @var array $m  @var ?array $toolbar */
$tpl = $m['template'];
$c = $m['cert'];
$brand = DocumentService::brand();

$fv = [];
foreach ($m['fields'] as $f) $fv[$f['key']] = (string) ($f['value'] ?? '');
$v = fn ($k) => e($fv[$k] ?? '');
$hh = fn ($mm) => 'height:' . round($mm - 0.18, 2) . 'mm';
$fix = fn (string $t) => preg_replace_callback('~<tr style="(height:[^"]+)">(.*?)</tr>~s', fn ($mm) => '<tr>' . preg_replace('~<td\b~', '<td style="' . $mm[1] . '"', $mm[2]) . '</tr>', $t);

// ---- header data (label / value, two pairs per row)
$pairs = [
    ['Client Name', 'client', 'Address / Location', 'address', 9.7],
    ['Test Location', 'location', 'Report No.', 'cert_no', 9.0],
    ['Description', 'description', 'Test Date', 'test_date', 8.9],
    ['Testing Equipment', 'machine', 'Hydraulic Machine Capacity', 'capacity', 9.2],
    ['Machine Serial No.', 'machine_sn', 'Display Serial No.', 'display_sn', 9.2],
    ['Accuracy', 'accuracy', 'Machine Calibration<br>Due date', 'cal_due', 9.7],
];
$t1 = '<table class="f dt">' . doc_sizer([43.8, 50.3, 45, 48.2]);
foreach ($pairs as $r) $t1 .= '<tr style="' . $hh($r[4]) . '"><td class="l">' . $r[0] . '</td><td>' . $v($r[1]) . '</td><td class="l">' . $r[2] . '</td><td>' . $v($r[3]) . '</td></tr>';
$t1 .= '<tr style="' . $hh(12.7) . '"><td class="l">Material Description</td><td colspan="3">' . nl2br($v('material')) . '</td></tr></table>';

// ---- photo
$photos = array_values($m['photos'] ?? []);
// the whole photo is shown (never cropped), scaled up or down to the largest size that fits the 187.4 x 79 mm image area and centred in it
$img = '<div class="dt-img"></div>';
if (isset($photos[0])) {
    $bw = 187.4; $bh = 79.0;
    $src0 = (string) $photos[0]['src'];
    $raw = str_contains($src0, 'base64,') ? base64_decode(substr($src0, strpos($src0, 'base64,') + 7), true) : false;
    $dim = $raw !== false ? @getimagesizefromstring($raw) : false;
    if ($dim && $dim[0] > 0 && $dim[1] > 0) {
        $k = min($bw / $dim[0], $bh / $dim[1]);
        $iw = round($dim[0] * $k, 2); $ih = round($dim[1] * $k, 2);
        $pad = round(($bh - $ih) / 2, 2);
        $img = '<div class="dt-img" style="padding-top:' . $pad . 'mm;height:' . round($bh - $pad, 2) . 'mm"><img src="' . $src0 . '" style="width:' . $iw . 'mm;height:' . $ih . 'mm" alt=""></div>';
    } else {
        $img = '<div class="dt-img" style="width:187.4mm;background:url(\'' . $src0 . '\') center center no-repeat;background-size:contain"></div>';
    }
}

// ---- sample details
$t2 = '<table class="f dt" style="margin-top:3.1mm">' . doc_sizer([43.8, 95.3, 48.2])
    . '<tr style="' . $hh(8.1) . '"><td colspan="3" class="l c h12">SAMPLE DETAILS</td></tr>'
    . '<tr style="' . $hh(9.4) . '"><td class="l c h11">Sample Tage / ID</td><td class="l c h11">DESCRIPTION</td><td class="l c h11">Applied Pull Load</td></tr>'
    . '<tr style="' . $hh(9.4) . '"><td class="c b">' . $v('sample_id') . '</td><td class="c b">' . $v('sample_desc') . '</td><td class="c b h11">' . $v('pull_load') . '</td></tr></table>';

// ---- sign-off
$insp = $m['inspector'];
$rev = $m['reviewer'];
$signed = in_array($c['status'], ['pending_qc', 'issued'], true);
$im = fn ($p, $ok) => ($ok && $p && !empty($p['signature'])) ? '<img src="' . $p['signature'] . '" style="max-height:13mm;max-width:34mm;vertical-align:middle;margin:0 1.5mm" alt="">' : '';
$seal = $m['issued'] ? '<img src="' . $brand['stamp'] . '" style="width:15mm;height:15mm;vertical-align:middle;margin:0 1.5mm" alt="">' : '';
$sig = '';
$stamp = '';
// each person's signature sits in the same row as their name; the seal is beside the reviewer's signature
$t3 = '<table class="f dt" style="margin-top:4.2mm">' . doc_sizer([43.8, 72.7, 70.9])
    . '<tr style="' . $hh(9.4) . '"><td class="l c b h11">Role</td><td class="l c b h11">Name</td><td class="l c b h11">Signature / Stamp</td></tr>'
    . '<tr style="' . $hh(16) . '"><td class="l b">' . e($tpl['inspector_label']) . '</td><td class="c b">' . e($insp['name'] ?? '') . '</td><td class="c">' . $im($insp, $signed) . '</td></tr>'
    . '<tr style="' . $hh(16) . '"><td class="l b">Reviewed by</td><td class="c b">' . ($m['issued'] ? e($rev['name'] ?? '') : '<span class="muted">Pending QC review</span>') . '</td><td class="c">' . $im($rev, $m['issued']) . $seal . '</td></tr></table>';

$wm = ['draft' => 'DRAFT', 'pending_qc' => 'DRAFT', 'revoked' => 'REVOKED', 'rejected' => 'DRAFT'][$c['status']] ?? null;

echo View::partial('documents/frame', [
    'title'     => $tpl['title'] . ' · ' . ($c['certificate_no'] ?? ''),
    'pages'     => [[
        'body'  => $fix($t1) . $img . $fix($t2 . $t3),
        'top'   => 36.4,
        'left'  => 11.8,
        'width' => 187.4,
        'extra' => '<div class="cert-title dt-title">' . e($tpl['title']) . '</div>' . $stamp . $sig,
    ]],
    'sidetext'  => '',   // this form carries no document reference line
    'qr'        => $m['qr'] ?? null,
    'toolbar'   => $toolbar ?? null,
    'watermark' => $wm,
]);
