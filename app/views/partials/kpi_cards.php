<?php /** KPI scorecard tiles. @var array $cards */ ?>
<div class="kpi-grid">
<?php foreach ($cards as $c):
    $now = in_array($c['key'], ['backlog_unassigned', 'overdue_open', 'equipment_due_30', 'my_open', 'my_overdue', 'qc_queue', 'qc_oldest_hours'], true); ?>
  <div class="kpi-card st-<?= e($c['status']) ?>" title="<?= e($c['desc']) ?>">
    <div class="kc-top"><span class="kc-label"><?= e($c['label']) ?></span>
      <?php if ($c['status'] !== 'none'): ?><span class="kc-rag"><i></i><?= e(KpiService::statusLabel($c['status'])) ?></span><?php elseif ($now): ?><span class="kc-now">Now</span><?php endif; ?></div>
    <div class="kc-value"><?= e(KpiService::fmt($c['value'], $c['unit'])) ?></div>
    <div class="kc-foot">
      <?php if ($c['target'] !== null): ?><span>Target <?= $c['dir'] === 'down' ? '≤' : '≥' ?> <?= e(KpiService::fmt($c['target'], $c['unit'])) ?></span><?php endif; ?>
      <?php if ($c['delta'] !== null && !$now): ?>
        <span class="kc-delta <?= $c['better'] === null ? '' : ($c['better'] ? 'up' : 'down') ?>"><?= $c['delta'] > 0 ? '▲' : ($c['delta'] < 0 ? '▼' : '■') ?> <?= e(KpiService::fmt(abs($c['delta']), $c['unit'] === '%' ? ' pts' : $c['unit'])) ?> vs prev.</span>
      <?php elseif (!$now && $c['prev'] === null && $c['value'] !== null): ?><span class="muted">no previous data</span><?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
