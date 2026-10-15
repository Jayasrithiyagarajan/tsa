<?php
/**
 * Inspector ID card print sheet. Each face is its own 85.6 × 54 mm page, so the front and the back are
 * printed separately (?side=front | back) or as a 2-page job for duplex card printers (?side=both).
 * @var array $m @var string $side @var ?array $toolbar
 */
$c = $m['card'];
$faces = $side === 'both' ? ['front', 'back'] : [$side];
$sideUrl = fn ($s) => '?side=' . $s;
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ID Card <?= e($c['card_no']) ?> — <?= e(ucfirst($side)) ?></title>
<style>
<?= file_get_contents(ROOT_PATH . '/assets/css/idcard.css') ?>
@page { size: 85.6mm 54mm; margin: 0; }
html, body { margin: 0; padding: 0; }
.idc * { box-sizing: content-box; }
/* break *before* the second face — Dompdf drops absolutely positioned content after a page-break-after */
.idc-page { width: 85.6mm; height: 54mm; position: relative; }
.face + .face .idc-page { page-break-before: always; }
<?php if (!empty($toolbar)): ?>
@media screen {
  body { background: #525a6b; padding: 76px 16px 32px; font-family: Inter, "Segoe UI", sans-serif; }
  .doc-toolbar { position: fixed; top: 0; left: 0; right: 0; min-height: 52px; background: #1b2540; color: #fff; display: flex; align-items: center; flex-wrap: wrap;
    gap: 8px; padding: 6px 16px; font-size: 14px; z-index: 10; box-sizing: border-box; }
  .doc-toolbar .t { flex: 1; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 120px; }
  .doc-toolbar a, .doc-toolbar button { color: #fff; background: rgba(255,255,255,.12); border: 0; border-radius: 8px; padding: 8px 12px; text-decoration: none; font: inherit; cursor: pointer; }
  .doc-toolbar .seg { display: inline-flex; background: rgba(255,255,255,.08); border-radius: 9px; padding: 2px; }
  .doc-toolbar .seg a { background: transparent; padding: 6px 12px; }
  .doc-toolbar .seg a.on { background: #fff; color: #1b2540; font-weight: 600; }
  .doc-toolbar button.primary { background: #3b82f6; }
  .doc-toolbar .st { font-size: 12px; padding: 3px 8px; border-radius: 99px; background: rgba(255,255,255,.15); }
  .doc-toolbar .st.bad { background: #dc2626; }
  .face { margin: 0 auto 28px; width: max-content; }
  .face .cap { color: #cfd6e6; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; margin: 0 0 8px 2px; }
  .idc-page { zoom: 2.2; box-shadow: 0 4px 18px rgba(0,0,0,.35); border-radius: 3mm; }
  .hint { color: #cfd6e6; font-size: 12.5px; text-align: center; max-width: 560px; margin: 0 auto; line-height: 1.5; }
  @media (max-width: 860px) { .idc-page { zoom: 1.05; } .doc-toolbar .hide-sm { display: none; } }
}
@media print {
  .doc-toolbar, .cap, .hint { display: none; }
  body { background: #fff; padding: 0; }
  .face { margin: 0; }
  .idc { border-radius: 0; }
}
<?php else: ?>
.idc { border-radius: 0; }
<?php endif; ?>
</style></head>
<body>
<?php if (!empty($toolbar)): ?>
  <div class="doc-toolbar">
    <a href="<?= e($toolbar['back']) ?>">&larr; Back</a>
    <span class="t">ID Card <?= e($c['card_no']) ?> · <?= e($c['holder_name']) ?></span>
    <?php if ($m['state'] !== 'valid'): ?><span class="st bad"><?= e(ucfirst($m['state'])) ?></span><?php endif; ?>
    <span class="seg">
      <a href="<?= $sideUrl('front') ?>" class="<?= $side === 'front' ? 'on' : '' ?>">Front</a>
      <a href="<?= $sideUrl('back') ?>" class="<?= $side === 'back' ? 'on' : '' ?>">Back</a>
      <a href="<?= $sideUrl('both') ?>" class="<?= $side === 'both' ? 'on' : '' ?>">Both</a>
    </span>
    <a class="hide-sm" href="<?= $sideUrl($side) ?>&amp;download=1">PDF</a>
    <button type="button" class="primary" onclick="window.print()">Print <?= $side === 'both' ? 'front + back' : e($side) ?></button>
  </div>
<?php endif; ?>
<?php foreach ($faces as $f): ?>
  <div class="face">
    <?php if (!empty($toolbar)): ?><div class="cap"><?= $f === 'front' ? 'Front side' : 'Back side' ?></div><?php endif; ?>
    <div class="idc-page"><?php View::render('partials/id_card', ['m' => $m, 'side' => $f], null); ?></div>
  </div>
<?php endforeach; ?>
<?php if (!empty($toolbar)): ?>
  <p class="hint">Page size is CR80 (85.6 × 54 mm), one card face per page. Print the front, turn the card over and print the back —
    or choose <b>Both</b> for a duplex card printer. In the print dialog set margins to <b>None</b> and scale to <b>100%</b>.</p>
<?php endif; ?>
</body></html>
