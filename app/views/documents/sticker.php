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
<style><?= file_get_contents(ROOT_PATH . '/assets/css/doc.css') ?><?= empty($toolbar) ? "\n@page { size: 100mm 100mm; margin: 0; }\n.stk.pdf .no b { position: absolute; left: 0; top: 0; width: 7.9mm; height: 6mm; }\n.stk.pdf .no span { position: absolute; left: 7.9mm; top: 0; width: 17.1mm; height: 6mm; }\n.stk.pdf .rt { width: 92.7mm; border-collapse: separate; border-spacing: 0; }\n.stk.pdf .rt td { padding: 0; vertical-align: top; }\n.stk.pdf .rt td.k { width: 29.75mm; height: 7.7mm; padding-right: .8mm; white-space: nowrap; font-weight: bold; font-size: 9.45pt; line-height: 5.1mm; }\n.stk.pdf .rt td.v { padding-bottom: 1.05mm; }\n.stk.pdf .pv { height: 6.65mm; background: #e6e7e8; border-radius: 1.5mm; font-weight: bold; font-size: 9pt; line-height: 5.1mm; padding: 0 2mm; white-space: nowrap; overflow: hidden; color: #111; }\n.stk.pdf.fail .rt td.k { height: 9.6mm; color: #fff; }\n" : '' ?></style></head>
<body class="doc<?= !empty($toolbar) ? ' preview' : '' ?>">
<?php if (!empty($toolbar)): ?>
  <div class="doc-toolbar">
    <a href="<?= e($toolbar['back']) ?>">&larr; Back</a>
    <span class="t">Sticker <?= e($st['sticker_no']) ?> · <?= e(Workflow::label($st['status'])) ?><?= $st['sub_job_no'] ? ' · ' . e($st['sub_job_no']) : '' ?></span>
    <button type="button" onclick="window.print()">Print</button>
  </div>
<?php endif; ?>
<?php if ($st['status'] === 'void' && !empty($toolbar)): ?><div class="stk-void">VOID<?= $st['void_reason'] ? ' — ' . e($st['void_reason']) : '' ?></div><?php endif; ?>
<?php if (empty($toolbar)): ?><div class="stk-page"><?php endif; ?>
<?php View::render('documents/sticker_card', ['no' => $st['sticker_no'], 'passed' => $st['passed'], 'values' => $values, 'art' => DocumentService::stickerArt(), 'qr' => DocumentService::stickerQr($st['verify_token']), 'pdf' => empty($toolbar)], null); ?>
<?php if (empty($toolbar)): ?></div><?php endif; ?>
</body></html>
