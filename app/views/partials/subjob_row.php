<?php /** @var array $s sub-job row (JobService::SUBJOB_SQL) @var string $href */ ?>
<a class="sub-row" href="<?= e($href) ?>">
  <span class="sr-no"><?= (int) substr($s['job_no'], -4) ?>.<?= (int) $s['seq'] ?></span>
  <span class="sr-t"><b><?= e($s['type_name']) ?> — <?= e($s['eq_description'] ?: 'Equipment') ?></b><span><?= e($s['equipment_no'] ?: 'No ID') ?><?= $s['eq_serial_no'] ? ' · S/N ' . e($s['eq_serial_no']) : '' ?><?= !empty($s['inspector_name']) && empty($hideInspector) ? ' · ' . e($s['inspector_name']) : '' ?></span></span>
  <span><?= badge($s['status']) ?></span>
  <span class="muted"><?= icon('chevron-right', 'sm') ?></span>
</a>
