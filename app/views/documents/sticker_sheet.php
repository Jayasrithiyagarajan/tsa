<?php /** Blank numbered stickers for bulk printing: one 100 x 100 mm label per page (default) or 4 per A4 sheet. @var array $rows @var string $back */
$art = DocumentService::stickerArt();
$blank = array_fill_keys(['Equipment Type', 'Identification No', 'Equip S/N', 'Capacity / SWL', 'Inspection Date', 'Next Inspection Date', 'Inspected By'], '');
$a4 = input('layout') === 'a4';
$card = fn ($s) => View::render('documents/sticker_card', ['no' => $s['sticker_no'], 'passed' => $s['color'] !== 'red', 'values' => $blank, 'art' => $art, 'qr' => DocumentService::stickerQr($s['verify_token'])], null);
// switch layout keeping the same selection (range / batch by link, ticked stickers by re-posting)
$switch = function (string $layout, string $label) use ($rows) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $h = '<form method="post" style="display:inline">' . Csrf::field() . '<input type="hidden" name="layout" value="' . $layout . '">';
        foreach ($rows as $r) $h .= '<input type="hidden" name="ids[]" value="' . (int) $r['id'] . '">';
        return $h . '<button type="submit" style="background:none;border:0;color:inherit;text-decoration:underline;cursor:pointer;font:inherit">' . e($label) . '</button></form>';
    }
    return '<a href="?' . e(http_build_query(['layout' => $layout] + $_GET)) . '">' . e($label) . '</a>';
};
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stickers <?= e($rows[0]['sticker_no']) ?> – <?= e(end($rows)['sticker_no']) ?></title>
<style><?= file_get_contents(ROOT_PATH . '/assets/css/doc.css') ?>
@page { size: <?= $a4 ? 'A4 portrait' : '100mm 100mm' ?>; margin: 0; }</style></head>
<body class="doc preview">
  <div class="doc-toolbar">
    <a href="<?= e($back) ?>">&larr; Back</a>
    <span class="t"><?= count($rows) ?> stickers · <?= e($rows[0]['sticker_no']) ?> – <?= e(end($rows)['sticker_no']) ?> ·
      <?= $a4 ? (int) ceil(count($rows) / 4) . ' A4 sheet(s) · ' . $switch('label', '100 × 100 mm labels') : '100 × 100 mm, one per page · ' . $switch('a4', '4 per A4 sheet') ?></span>
    <button type="button" onclick="window.print()">Print</button>
  </div>
  <?php if ($a4): foreach (array_chunk($rows, 4) as $page): ?>
    <div class="stk-sheet"><?php foreach ($page as $s): ?><div class="cell"><?php $card($s); ?></div><?php endforeach; ?></div>
  <?php endforeach; else: foreach ($rows as $s): ?>
    <div class="stk-page"><?php $card($s); ?></div>
  <?php endforeach; endif; ?>
</body></html>
