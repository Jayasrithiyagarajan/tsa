<?php /** One 100 x 100 mm sticker (print-shop layout). Red stickers drop Capacity / SWL and Next Inspection Date.
 * @var string $no @var bool $passed @var array $values label => value (empty = blank to fill by hand) @var array $art @var ?string $qr */
$pt = min(11.3, round(16.5 / (max(1, strlen($no)) * 0.215), 1));   // shrink long numbers to fit the No: box
if (!$passed) unset($values['Capacity / SWL'], $values['Next Inspection Date']);
?>
<div class="stk<?= $passed ? '' : ' fail' ?>">
  <img class="logo" src="<?= $art['logo'] ?>" alt="TUV EAST · Trading Utility Vendor for Inspection">
  <?php if (!empty($qr)): ?><div class="qr"><img src="<?= e($qr) ?>" alt="Scan to verify"></div><?php endif; ?>
  <div class="res"><?= $passed ? 'Accepted' : 'Rejected' ?></div>
  <div class="uaf"><img src="<?= $art['uaf'] ?>" alt="United Accreditation Foundation"></div>
  <div class="no"><b>No:</b><span style="font-size:<?= $pt ?>pt"><?= e($no) ?></span></div>
  <div class="rows">
    <?php foreach ($values as $k => $v): ?>
      <div class="r"><span class="k"><?= e($k) ?> :</span><span class="v"><?= e((string) $v) ?></span></div>
    <?php endforeach; ?>
  </div>
  <div class="addr">Kingdom of Saudi Arabia - Main Office - Eastern Province<br>Al Khobar, 3205 Prince Muteb St., Al sharq Tower, 5th Floor<br>Phone : 0138185501 - Mob.: +966566485177<br>E-mail : info@tuv-east.com - operations@tuv-east.com</div>
  <img class="leea" src="<?= $art['leea'] ?>" alt="LEEA Full Member">
</div>
