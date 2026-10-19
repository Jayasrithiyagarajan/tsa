<?php
/**
 * A4 document frame.
 * @var string $title  @var array $pages [['body' => html, 'top' => mm, 'extra' => html]]  @var string $sidetext
 * @var ?string $qr  @var ?array $toolbar  ['back' => url, 'pdf' => url, 'status' => html]  @var ?string $watermark
 */
// Multi-certificate mode: collect this document's pages (with their own QR, watermark and page numbers) instead of printing them.
if (isset($GLOBALS['__doc_collect']) && is_array($GLOBALS['__doc_collect'])) {
    foreach ($pages as $i => $p) {
        $GLOBALS['__doc_collect'][] = $p + ['qr' => $qr ?? null, 'watermark' => $watermark ?? null, 'sidetext' => $sidetext, 'pageno' => $i + 1];
    }
    return;
}
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
<?php foreach ($pages as $i => $p):
    $pQr = array_key_exists('qr', $p) ? $p['qr'] : ($qr ?? null);
    $pWm = array_key_exists('watermark', $p) ? $p['watermark'] : ($watermark ?? null);
    $pSide = $p['sidetext'] ?? $sidetext;
    $pNo = $p['pageno'] ?? $i + 1; ?>
  <div class="sheet">
    <img class="hdr" src="<?= $brand['header'] ?>" alt="">
    <div class="sidetext"><?= e($pSide) ?></div>
    <?php if (!empty($pWm)): ?><div class="watermark <?= e(strtolower($pWm)) ?>"><?= e($pWm) ?></div><?php endif; ?>
    <?= $p['extra'] ?? '' ?>
    <div class="body" style="top: <?= (float) $p['top'] ?>mm; left: <?= (float) ($p['left'] ?? 15) ?>mm; width: <?= (float) ($p['width'] ?? 181) ?>mm"><?= $p['body'] ?></div>
    <div class="ftr"><img class="band" src="<?= $brand['footer'] ?>" alt=""></div>
    <?php if (!empty($pQr)): ?><img class="qr" src="<?= $pQr ?>" alt="Verify"><?php endif; ?>
    <div class="pageno<?= !empty($pQr) ? ' with-qr' : '' ?>"><span>Page | <?= $pNo ?></span></div>
  </div>
<?php endforeach; ?>
</body></html>
