<?php /** Period selector + Excel export. @var array $P */ ?>
<form class="kpi-period" method="get" data-autosubmit>
  <div class="seg-tabs">
    <?php foreach (['7d' => '7 days', '30d' => '30 days', 'month' => 'This month', 'lmonth' => 'Last month', 'quarter' => 'Quarter', '90d' => '90 days', 'ytd' => 'YTD'] as $k => $l): ?>
      <a class="<?= $P['key'] === $k ? 'on' : '' ?>" href="?<?= e(http_build_query(['period' => $k])) ?>"><?= e($l) ?></a>
    <?php endforeach; ?>
  </div>
  <span class="row" style="gap:6px">
    <input type="hidden" name="period" value="custom">
    <input class="input" type="date" name="from" value="<?= e($P['from']) ?>" aria-label="From" style="width:auto;height:36px">
    <span class="muted">–</span>
    <input class="input" type="date" name="to" value="<?= e($P['to']) ?>" aria-label="To" style="width:auto;height:36px">
    <button class="btn btn-secondary btn-sm" style="height:36px">Apply</button>
  </span>
  <span class="grow"></span>
  <a class="btn btn-primary" href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'xlsx']))) ?>"><?= icon('download', 'sm') ?> Export to Excel</a>
</form>
<p class="small muted mb-2"><?= e($P['label']) ?> · <?= e(fmt_date($P['from'])) ?> – <?= e(fmt_date($P['to'])) ?> · compared with <?= e(fmt_date($P['prev_from'])) ?> – <?= e(fmt_date($P['prev_to'])) ?></p>
