<?php /** Digital sticker of a job — TUV EAST label, 100 x 100 mm. Blue = Accepted, red = Rejected. @var array $st @var ?array $toolbar */
$values = [
    'Equipment Type'       => $st['eq_description'] ?: $st['type_name'],
    'Identification No'    => $st['equipment_no'],
    'Equip S/N'            => $st['serial_no'],
    'Capacity / SWL'       => $st['capacity'],
    'Inspection Date'      => fmt_date($st['inspection_date'] ?: $st['linked_at']),
    'Next Inspection Date' => $st['passed'] ? fmt_date($st['next_date']) : '',
    'Inspected By'         => $st['inspector_name'],
];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sticker <?= e($st['sticker_no']) ?></title>
<style><?= file_get_contents(ROOT_PATH . '/assets/css/doc.css') ?></style></head>
<body class="doc<?= !empty($toolbar) ? ' preview' : '' ?>">
<?php if (!empty($toolbar)): ?>
  <div class="doc-toolbar">
    <a href="<?= e($toolbar['back']) ?>">&larr; Back</a>
    <span class="t">Sticker <?= e($st['sticker_no']) ?> · <?= e(Workflow::label($st['status'])) ?><?= $st['sub_job_no'] ? ' · ' . e($st['sub_job_no']) : '' ?></span>
    <button type="button" onclick="window.print()">Print</button>
  </div>
<?php endif; ?>
<?php if ($st['status'] === 'void'): ?><div class="stk-void">VOID<?= $st['void_reason'] ? ' — ' . e($st['void_reason']) : '' ?></div><?php endif; ?>
<?php View::render('documents/sticker_card', ['no' => $st['sticker_no'], 'passed' => $st['passed'], 'values' => $values, 'art' => DocumentService::stickerArt(), 'qr' => DocumentService::stickerQr($st['verify_token'])], null); ?>
</body></html>
