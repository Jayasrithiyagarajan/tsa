<?php
/**
 * One face of the inspector ID card (styles: assets/css/idcard.css).
 * @var array $m InspectorCardService::model()  @var string $side front|back  @var bool $public mask the Iqama number
 */
$c = $m['card'];
$fit = fn (string $s, float $pt, int $chars) => mb_strlen($s) > $chars ? round($pt * $chars / mb_strlen($s), 2) : $pt;
$rings = fn (float $x, float $y, array $radii, float $w) => implode('', array_map(
    fn ($r) => '<span class="ring" style="left:' . ($x - $r) . 'mm;top:' . ($y - $r) . 'mm;width:' . (2 * $r - 2 * $w) . 'mm;height:' . (2 * $r - 2 * $w) . 'mm;border-width:' . $w . 'mm"></span>',
    $radii
));
?>
<?php if ($side === 'front'): ?>
<div class="idc idc-front">
  <?= $rings(57, 30, [9, 13.5, 18], 2.4) ?>
  <div class="band band-top"></div>
  <div class="logo-box"><?php if ($m['logo']): ?><img src="<?= $m['logo'] ?>" alt="TUV EAST"><?php endif; ?></div>
  <div class="photo" data-f="photo"><?php if ($m['photo']): ?><img src="<?= $m['photo'] ?>" alt=""><?php else: ?><span class="ph">PHOTO</span><?php endif; ?></div>
  <div class="logos"><?php if ($m['logos']): ?><img src="<?= $m['logos'] ?>" alt=""><?php endif; ?></div>

  <span class="lbl" style="top:13.6mm">NAME</span>
  <span class="val" data-f="holder_name" style="top:15.8mm;font-size:<?= $fit($c['holder_name'], 7, 25) ?>pt"><?= e($c['holder_name']) ?></span>
  <span class="lbl" style="top:19.8mm">IQAMA / ID NO</span>
  <span class="val" data-f="id_number" style="top:22mm"><?= e(!empty($public) ? InspectorCardService::maskId($c['id_number']) : $c['id_number']) ?></span>
  <span class="lbl" style="top:26mm">COMPANY</span>
  <span class="val" data-f="company" style="top:28.2mm;font-size:<?= $fit($c['company'], 7, 25) ?>pt"><?= e($c['company']) ?></span>
  <span class="lbl" style="top:32.6mm">ID CODE</span>
  <span class="val" data-f="card_no" style="top:34.8mm"><?= e($c['card_no']) ?></span>
  <span class="lbl" style="top:39.1mm">ISSUE DATE</span>
  <span class="val" data-f="issue_date" style="top:41.3mm"><?= e(fmt_date($c['issue_date'], 'd-m-Y')) ?></span>
  <span class="lbl exp" style="top:39.1mm">EXP DATE</span>
  <span class="val exp" data-f="expiry_date" style="top:41.3mm"><?= e(fmt_date($c['expiry_date'], 'd-m-Y')) ?></span>

  <div class="band band-bottom"></div>
  <span class="title" data-f="title" style="font-size:<?= $fit($c['title'], 7.6, 32) ?>pt"><?= e($c['title']) ?></span>
</div>
<?php else: ?>
<div class="idc idc-back">
  <?= $rings(75.2, 27.6, [9.5, 14.5, 19.5], 2.6) ?>
  <div class="band band-top"></div>
  <span class="line" style="top:6.2mm">Job Ref: <b data-f="job_ref"><?= e($c['job_ref']) ?></b></span>
  <span class="line" style="top:9.6mm">Certification: <b data-f="certification"><?= e($c['certification']) ?></b></span>
  <span class="line" style="top:13mm">Assessor: <b data-f="assessor"><?= e($c['assessor']) ?></b></span>
  <div class="box" data-f="competence">
    <span class="shade s1"></span><span class="shade s2"></span>
    <span class="bt">Certificate of Competence:</span>
    <?php $fitC = InspectorCardService::fitCompetence($c['competence']);
    foreach ($fitC['cols'] as $col): foreach ($col['lines'] as $i => $line): ?>
      <span class="bc" style="left:<?= $col['left'] ?>mm;top:<?= $col['tops'][$i] ?>mm;width:<?= $col['width'] ?>mm;font-size:<?= $fitC['pt'] ?>pt;line-height:1.3"><?= e($line) ?></span>
    <?php endforeach; endforeach; ?>
  </div>
  <div class="qr"><img src="<?= $m['qr'] ?>" alt="Verify"></div>
  <div class="note">The person is Certified for the job mentioned above, to verify this card please scan the QR code or Through E-Mails:<?php
    foreach (preg_split('/\s+-\s+|[,;]\s*/', (string) $m['emails']) as $i => $mail): ?><?= $i ? ' &nbsp;-&nbsp; ' : '' ?><a href="mailto:<?= e(trim($mail)) ?>"><?= e(trim($mail)) ?></a><?php endforeach; ?></div>
  <div class="band band-bottom"></div>
  <span class="addr"><?= e($m['address']) ?></span>
</div>
<?php endif; ?>
