<?php
/** Inspector personal KPI scorecard. @var bool $self */
$wmax = max(1, ...array_column($weekly, 'n'));
$byKey = array_column($cards, null, 'key');
$mine = [
    'completed' => $byKey['my_completed']['value'] ?? null,
    'hours' => $byKey['my_inspection_hours']['value'] ?? null,
    'fpy' => $byKey['my_fpy']['value'] ?? null,
];
$cmp = function ($me, $team, bool $higherBetter, string $unit) {
    if ($me === null || $team === null) return '<span class="muted">—</span>';
    $better = $higherBetter ? $me >= $team : $me <= $team;
    return '<span class="badge badge-' . ($better ? 'green' : 'amber') . '"><i></i>' . ($better ? 'Better than' : 'Below') . ' team avg</span>';
};
?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow"><?= $self ? 'My performance' : 'Inspector scorecard' ?></div><h1><?= $self ? 'My KPIs' : e($who) ?></h1>
    <div class="sub">Productivity, timeliness and first-time quality of <?= $self ? 'your' : 'this inspector\'s' ?> inspections.</div></div>
</div>
<?php View::render('partials/kpi_period', ['P' => $P], null); ?>
<?php View::render('partials/kpi_cards', ['cards' => $cards], null); ?>

<div class="grid grid-main">
  <div class="stack">
    <div class="card">
      <div class="card-h"><h2><?= icon('clipboard', 'sm') ?> Approved inspections per week</h2><span class="hint">last 12 weeks</span></div>
      <div class="card-b"><div class="colchart">
        <?php foreach ($weekly as $wi => $w): ?><div class="col"><div class="b" data-v="<?= $w['n'] ?> · week of <?= e($w['label']) ?>" style="height:<?= max(2, round($w['n'] / $wmax * 100)) ?>%"></div><small style="font-size:10px;white-space:nowrap"><?= $wi % 2 ? '&nbsp;' : e($w['label']) ?></small></div><?php endforeach; ?>
      </div></div>
    </div>
    <div class="card">
      <div class="card-h"><h2><?= icon('history', 'sm') ?> Inspections in this period</h2><span class="hint"><?= count($recent) ?></span></div>
      <?php if ($recent): ?>
      <div class="table-wrap"><table class="t stack-sm">
        <thead><tr><th>Sub-job</th><th>Inspection / client</th><th>Scheduled</th><th>Submitted</th><th class="num">Inspection time</th><th class="num">QC returns</th><th>Status</th></tr></thead>
        <tbody><?php foreach ($recent as $r): $late = $r['submitted_at'] && $r['scheduled_date'] && substr($r['submitted_at'], 0, 10) > date('Y-m-d', strtotime($r['scheduled_date'] . ' +1 day')); ?>
          <tr<?= $self ? ' class="row-link" data-href="' . url('inspector/subjobs/' . $r['id']) . '"' : '' ?>>
            <td data-label="Sub-job" class="mono strong"><?= e($r['sub_job_no']) ?></td>
            <td data-label="Inspection"><?= e($r['type_name']) ?><div class="sub"><?= e($r['client_name']) ?></div></td>
            <td data-label="Scheduled" class="nowrap"><?= e(fmt_date($r['scheduled_date']) ?: '—') ?></td>
            <td data-label="Submitted" class="nowrap"><?= e(fmt_datetime($r['submitted_at'])) ?><?= $late ? ' <span class="badge badge-amber"><i></i>Late</span>' : '' ?></td>
            <td class="num" data-label="Time"><?= $r['insp_hours'] === null ? '—' : e(number_format((float) $r['insp_hours'], 1)) . ' h' ?></td>
            <td class="num" data-label="QC returns"><?= (int) $r['reworks'] ? '<span class="badge badge-red"><i></i>' . (int) $r['reworks'] . '</span>' : '0' ?></td>
            <td data-label="Status"><?= badge($r['status']) ?></td>
          </tr>
        <?php endforeach; ?></tbody></table></div>
      <?php else: View::render('partials/empty', ['icon' => 'clipboard', 'title' => 'No inspections in this period', 'text' => 'Choose a longer period above.'], null); endif; ?>
    </div>
  </div>
  <div class="stack">
    <div class="card">
      <div class="card-h"><h2><?= icon('users', 'sm') ?> Team benchmark</h2><span class="hint">anonymous averages</span></div>
      <div class="card-b"><div class="bench">
        <span class="h">Measure</span><span class="h"><?= $self ? 'Me' : 'Inspector' ?></span><span class="h">Team</span>
        <span>Approved inspections</span><b><?= e(KpiService::fmt($mine['completed'], '')) ?></b><span><?= e(KpiService::fmt($bench['completed'], '')) ?></span>
        <span>Avg inspection time</span><b><?= e(KpiService::fmt($mine['hours'], 'h')) ?></b><span><?= e(KpiService::fmt($bench['insp_hours'], 'h')) ?></span>
        <span>First-pass yield</span><b><?= e(KpiService::fmt($mine['fpy'], '%')) ?></b><span><?= e(KpiService::fmt($bench['fpy'], '%')) ?></span>
      </div>
      <div class="row wrap mt-2" style="gap:6px"><?= $cmp($mine['completed'], $bench['completed'], true, '') ?><?= $cmp($mine['fpy'], $bench['fpy'], true, '%') ?></div>
      <?php if ($bench['rank']): ?><p class="small muted mt-2">Rank <b style="color:var(--text)"><?= (int) $bench['rank'] ?></b> of <?= (int) $bench['of'] ?> by approved inspections.</p><?php endif; ?>
      </div>
    </div>
    <div class="card"><div class="card-h"><h2><?= icon('layers', 'sm') ?> By inspection type</h2></div>
      <div class="card-b flush"><table class="t"><tbody>
        <?php foreach ($by_type as $t): ?><tr><td><?= e($t['type']) ?></td><td class="num"><b><?= (int) $t['n'] ?></b></td><td class="num sub"><?= $t['hours'] === null ? '—' : e(number_format((float) $t['hours'], 1)) . ' h' ?></td></tr><?php endforeach; ?>
        <?php if (!$by_type): ?><tr><td class="muted">No approved inspections.</td></tr><?php endif; ?>
      </tbody></table></div></div>
    <div class="card"><div class="card-h"><h2><?= icon('rotate', 'sm') ?> QC returns</h2></div>
      <div class="card-b"><?php if ($reworks): ?><ul class="timeline">
        <?php foreach ($reworks as $r): ?><li><b class="mono"><?= e($r['sub_job_no']) ?></b><div class="small"><?= e(mb_strimwidth((string) $r['comments'], 0, 160, '…')) ?></div><div class="tl-t"><?= e($r['reviewer']) ?> · <?= e(fmt_date($r['created_at'])) ?></div></li><?php endforeach; ?>
      </ul><?php else: ?><div class="ok-line"><?= icon('check') ?> No rework requests in this period</div><?php endif; ?></div></div>
    <div class="card"><div class="card-h"><h2><?= icon('award', 'sm') ?> Last 6 months</h2></div>
      <div class="card-b flush"><table class="t"><thead><tr><th>Month</th><th class="num">Approved</th><th class="num">First-pass</th></tr></thead><tbody>
        <?php foreach ($monthly as $m): ?><tr><td><?= e($m['month']) ?></td><td class="num"><?= (int) $m['completed'] ?></td><td class="num"><?= $m['fpy'] === null ? '—' : e(number_format($m['fpy'], 0)) . '%' ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
  </div>
</div>
