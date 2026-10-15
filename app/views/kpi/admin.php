<?php
/** Admin KPI dashboard. */
$mmax = max(1, ...array_map(fn ($m) => max($m['completed'], $m['certificates']), $monthly));
$chMax = max(1, ...array_map(fn ($c) => (int) $c['n'], $channels ?: [['n' => 1]]));
$meter = function (?float $pct, ?float $target = null) {
    if ($pct === null) return '<span class="muted">—</span>';
    $col = $target === null ? 'var(--primary)' : ($pct >= $target ? 'var(--green)' : ($pct >= $target - 10 ? 'var(--amber)' : 'var(--red)'));
    return '<div class="row" style="gap:8px"><div class="meter" style="flex:1"><span style="width:' . min(100, $pct) . '%;background:' . $col . '"></span></div><span class="nowrap" style="font-variant-numeric:tabular-nums">' . number_format($pct, 0) . '%</span></div>';
};
$tFpy = KpiService::target('first_pass_yield');
$tOn = KpiService::target('on_time_rate');
?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow">Operations</div><h1>KPI dashboard</h1>
    <div class="sub">Request handling, assignment, delivery and quality across the whole inspection workflow.</div></div>
  <?php if (Auth::isSuper()): ?><div class="actions"><button class="btn btn-secondary" data-modal="kpi-targets"><?= icon('sliders', 'sm') ?> Targets</button></div><?php endif; ?>
</div>
<?php View::render('partials/kpi_period', ['P' => $P], null); ?>
<?php View::render('partials/kpi_cards', ['cards' => $cards], null); ?>

<div class="grid grid-2">
  <div class="card">
    <div class="card-h"><h2><?= icon('award', 'sm') ?> Monthly output</h2>
      <span class="legend"><span><i style="background:var(--primary)"></i>Inspections completed</span><span><i style="background:var(--teal)"></i>Certificates issued</span></span></div>
    <div class="card-b">
      <div class="colchart chart-dual">
        <?php foreach ($monthly as $m): ?>
          <div class="col"><div class="pair">
              <div class="b" data-v="<?= $m['completed'] ?> inspections" style="height:<?= max(2, round($m['completed'] / $mmax * 100)) ?>%"></div>
              <div class="b alt" data-v="<?= $m['certificates'] ?> certificates" style="height:<?= max(2, round($m['certificates'] / $mmax * 100)) ?>%"></div></div>
            <small><?= e($m['short']) ?></small></div>
        <?php endforeach; ?>
      </div>
      <div class="row wrap small muted mt-1" style="gap:14px">First-pass yield:
        <?php foreach ($monthly as $m): ?><span><b style="color:var(--text)"><?= e($m['short']) ?></b> <?= $m['fpy'] === null ? '—' : e(number_format($m['fpy'], 0)) . '%' ?></span><?php endforeach; ?></div>
    </div>
  </div>
  <div class="card">
    <div class="card-h"><h2><?= icon('inbox', 'sm') ?> Requests by channel</h2><span class="hint">bar = received · % = converted to jobs</span></div>
    <div class="card-b bars">
      <?php foreach ($channels as $c): ?>
        <div class="bar-row"><span><?= e(Workflow::label($c['channel'])) ?></span><div class="track"><span style="width:<?= round($c['n'] / $chMax * 100) ?>%;background:var(--primary)"></span></div>
          <span class="v"><?= (int) $c['n'] ?></span></div>
        <div class="small muted" style="margin:-6px 0 2px 140px"><?= $c['n'] ? round($c['converted'] / $c['n'] * 100) : 0 ?>% converted</div>
      <?php endforeach; ?>
      <?php if (!$channels): ?><p class="muted small">No requests in this period.</p><?php endif; ?>
    </div>
  </div>
</div>

<div class="card mt-2">
  <div class="card-h"><h2><?= icon('users', 'sm') ?> Inspector productivity &amp; quality</h2><span class="hint">click an inspector for the personal scorecard</span></div>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Inspector</th><th class="num">Completed</th><th class="num">Avg inspection</th><th style="min-width:150px">First-pass yield</th><th class="num">QC returns</th><th style="min-width:150px">On-time submission</th><th class="num">Open now</th></tr></thead>
    <tbody><?php foreach ($inspectors as $r): $fpy = (int) $r['completed'] ? (int) $r['fpy_n'] / (int) $r['completed'] * 100 : null; $on = (int) $r['submitted'] ? (int) $r['ontime_n'] / (int) $r['submitted'] * 100 : null; ?>
      <tr class="row-link" data-href="<?= url('admin/kpi/inspector/' . $r['id'], ['period' => $P['key'], 'from' => $P['from'], 'to' => $P['to']]) ?>">
        <td data-label="Inspector"><span class="person"><span class="avatar sm"><?= e(initials($r['name'])) ?></span><span class="p-n"><?= e($r['name']) ?></span></span></td>
        <td class="num" data-label="Completed"><b><?= (int) $r['completed'] ?></b></td>
        <td class="num" data-label="Avg inspection"><?= $r['insp_hours'] === null ? '—' : e(number_format((float) $r['insp_hours'], 1)) . ' h' ?></td>
        <td data-label="First-pass yield"><?= $meter($fpy, $tFpy) ?></td>
        <td class="num" data-label="QC returns"><?= (int) $r['rework'] ?></td>
        <td data-label="On-time"><?= $meter($on, $tOn) ?></td>
        <td class="num" data-label="Open now"><span class="pill"><?= (int) $r['open_now'] ?></span></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div>

<div class="card mt-2">
  <div class="card-h"><h2><?= icon('shield', 'sm') ?> QC review</h2><span class="hint">click a reviewer for the personal scorecard</span></div>
  <div class="card-b"><div class="grid grid-4" style="gap:12px">
    <div><div class="small muted">Reviews</div><div class="kc-value" style="font-size:22px"><?= (int) $qc_team['qc_reviews'] ?></div></div>
    <div><div class="small muted">Avg review time</div><div class="kc-value" style="font-size:22px"><?= e(KpiService::fmt($qc_team['qc_review_hours'], 'h')) ?></div></div>
    <div><div class="small muted">Reviewed within 24 h</div><div class="kc-value" style="font-size:22px"><?= e(KpiService::fmt($qc_team['qc_sla'], '%')) ?></div></div>
    <div><div class="small muted">Rework rate</div><div class="kc-value" style="font-size:22px"><?= e(KpiService::fmt($qc_team['qc_rework_rate'], '%')) ?></div></div>
  </div></div>
  <div class="table-wrap"><table class="t"><tbody><?php foreach ($qc_people as $q): ?>
    <tr class="row-link" data-href="<?= url('admin/kpi/qc/' . $q['id'], ['period' => $P['key'], 'from' => $P['from'], 'to' => $P['to']]) ?>"><td><span class="person"><span class="avatar sm"><?= e(initials($q['name'])) ?></span><span class="p-n"><?= e($q['name']) ?></span></span></td>
      <td class="num"><b><?= (int) $q['reviews'] ?></b> <span class="sub">reviews</span></td><td class="num"><?= (int) $q['reworks'] ?> <span class="sub">returned</span></td></tr>
  <?php endforeach; ?></tbody></table></div>
</div>

<div class="grid grid-2 mt-2" style="align-items:start">
  <div class="card"><div class="card-h"><h2><?= icon('building', 'sm') ?> Client volume</h2></div>
    <div class="table-wrap"><table class="t">
      <thead><tr><th>Client</th><th class="num">Requests</th><th class="num">Completed</th><th class="num">Certificates</th><th class="num">Open</th></tr></thead>
      <tbody><?php foreach ($clients as $c): ?>
        <tr><td><span class="strong"><?= e($c['client']) ?></span> <span class="pill mono"><?= e($c['code']) ?></span></td><td class="num"><?= (int) $c['requests'] ?></td>
          <td class="num"><?= (int) $c['completed'] ?></td><td class="num"><?= (int) $c['certificates'] ?></td><td class="num"><?= (int) $c['open_now'] ?></td></tr>
      <?php endforeach; ?></tbody></table></div></div>
  <div class="card"><div class="card-h"><h2><?= icon('layers', 'sm') ?> Completed by inspection type</h2></div>
    <div class="table-wrap"><table class="t">
      <thead><tr><th>Inspection type</th><th class="num">Completed</th><th style="min-width:150px">First-pass yield</th></tr></thead>
      <tbody><?php foreach ($types as $t): ?>
        <tr><td><?= e($t['type']) ?></td><td class="num"><b><?= (int) $t['n'] ?></b></td><td><?= $meter($t['n'] ? $t['fpy'] / $t['n'] * 100 : null, $tFpy) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$types): ?><tr><td colspan="3" class="muted">No completed inspections in this period.</td></tr><?php endif; ?></tbody></table></div></div>
</div>

<div class="card mt-2"><div class="card-h"><h2><?= icon('info', 'sm') ?> How the KPIs are measured</h2></div>
  <div class="card-b"><div class="grid grid-2 small" style="gap:6px 24px">
    <?php foreach ($cards as $c): ?><div><b><?= e($c['label']) ?></b> — <span class="muted"><?= e($c['desc']) ?></span></div><?php endforeach; ?>
  </div><p class="small muted mt-2">Status: <b style="color:var(--green)">On target</b> meets the target · <b style="color:var(--amber)">Watch</b> within 10 % of it · <b style="color:var(--red)">Off target</b> worse. Arrows compare with the previous period of the same length.</p></div></div>

<dialog class="modal" id="kpi-targets" style="width:min(720px,calc(100vw - 32px))"><form method="post" action="<?= url('admin/kpi/targets') ?>"><?= Csrf::field() ?>
  <div class="m-h"><h3>KPI targets</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><p class="small muted mb-2">Targets apply to all three portals. Leave empty to show a KPI without a target.</p>
    <div class="form-grid">
      <div class="field col-6"><label>On-time grace (days after scheduled date, used when a job has no due date)</label><input class="input" type="number" min="0" max="60" name="sla_days" value="<?= e(setting('kpi_sla_days') ?: 3) ?>"></div>
      <?php foreach (KpiService::CATALOG as $k => [$label, $unit, $def, $dir]): if ($dir === 'none') continue; ?>
        <div class="field col-6"><label><?= e($label) ?> <span class="muted">(<?= $dir === 'up' ? '≥' : '≤' ?><?= $unit ? ' ' . e($unit) : '' ?>)</span></label>
          <input class="input" name="t[<?= e($k) ?>]" inputmode="decimal" value="<?= e(KpiService::target($k)) ?>"></div>
      <?php endforeach; ?>
    </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Save targets</button></div>
</form></dialog>
