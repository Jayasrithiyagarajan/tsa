<?php /** Inspection sticker (90mm label). @var array $st @var ?array $toolbar */ ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sticker <?= e($st['sticker_no']) ?></title>
<style><?= file_get_contents(ROOT_PATH . '/assets/css/doc.css') ?></style></head>
<body class="doc<?= !empty($toolbar) ? ' preview' : '' ?>" style="<?= !empty($toolbar) ? '' : 'padding-top:5mm' ?>">
<?php if (!empty($toolbar)): ?>
  <div class="doc-toolbar">
    <a href="<?= e($toolbar['back']) ?>">&larr; Back</a>
    <span class="t">Sticker <?= e($st['sticker_no']) ?></span>
    <button type="button" onclick="window.print()">Print</button>
  </div>
<?php endif; ?>
<div class="sticker <?= $st['color'] === 'red' ? 'red' : '' ?>" style="background:#fff">
  <div class="top"><?= $st['color'] === 'red' ? 'REJECTED · DO NOT USE' : 'INSPECTED' ?></div>
  <table>
    <tr><td class="k">Sticker No.</td><td class="v"><?= e($st['sticker_no']) ?></td>
      <td rowspan="6" style="width:24mm;text-align:center;vertical-align:middle">
        <?php if ($st['qr']): ?><img src="<?= $st['qr'] ?>" style="width:22mm" alt=""><?php endif; ?></td></tr>
    <tr><td class="k">Certificate No.</td><td class="v"><?= e($st['certificate_no']) ?></td></tr>
    <tr><td class="k">Equipment</td><td class="v"><?= e(trim($st['eq_description'] . ' ' . ($st['equipment_no'] ? '· ' . $st['equipment_no'] : ''))) ?></td></tr>
    <tr><td class="k">Inspected</td><td class="v"><?= e(fmt_date($st['inspection_date'])) ?></td></tr>
    <tr><td class="k">Next Due</td><td class="v"><?= e(fmt_date($st['expiry_date'])) ?></td></tr>
    <tr><td class="k">Inspector</td><td class="v"><?= e($st['inspector_name']) ?></td></tr>
  </table>
  <div style="text-align:center;font-size:7.5pt;padding:1.5mm;background:#eef2fa;color:#24418f">TUV EAST · Trading Utility Vendor for Inspection · www.tuv-east.com</div>
</div>
</body></html>
