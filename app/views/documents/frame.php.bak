<?php
/**
 * A4 document frame.
 * @var string $title  @var array $pages [['body' => html, 'top' => mm, 'extra' => html]]  @var string $sidetext
 * @var ?string $qr  @var ?array $toolbar  ['back' => url, 'pdf' => url, 'status' => html]  @var ?string $watermark
 */
$brand = DocumentService::brand();
$css = file_get_contents(ROOT_PATH . '/assets/css/doc.css');
$n = count($pages);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<style><?= $css ?></style></head>
<body class="doc<?= $toolbar ? ' preview' : '' ?>">
<?php if ($toolbar): ?>
  <div class="doc-toolbar">
    <a href="<?= e($toolbar['back']) ?>">&larr; <span class="hide-sm">Back</span></a>
    <span class="t"><?= e($title) ?></span>
    <?php if (!empty($toolbar['status'])): ?><span class="st hide-sm"><?= e($toolbar['status']) ?></span><?php endif; ?>
    <button type="button" onclick="window.print()" class="hide-sm">Print</button>
    <a class="primary" href="<?= e($toolbar['pdf']) ?>">Download PDF</a>
  </div>
<?php endif; ?>
<?php foreach ($pages as $i => $p): ?>
  <div class="sheet">
    <img class="hdr" src="<?= $brand['header'] ?>" alt="">
    <div class="sidetext"><?= e($sidetext) ?></div>
    <?php if (!empty($watermark)): ?><div class="watermark <?= e(strtolower($watermark)) ?>"><?= e($watermark) ?></div><?php endif; ?>
    <?= $p['extra'] ?? '' ?>
    <div class="body" style="top: <?= (float) $p['top'] ?>mm; left: <?= (float) ($p['left'] ?? 15) ?>mm; width: <?= (float) ($p['width'] ?? 181) ?>mm"><?= $p['body'] ?></div>
    <div class="ftr"><img class="band" src="<?= $brand['footer'] ?>" alt=""></div>
    <?php if (!empty($qr)): ?><img class="qr" src="<?= $qr ?>" alt="Verify"><?php endif; ?>
    <div class="pageno<?= !empty($qr) ? ' with-qr' : '' ?>"><span>Page | <?= $i + 1 ?></span></div>
  </div>
<?php endforeach; ?>
</body></html>
