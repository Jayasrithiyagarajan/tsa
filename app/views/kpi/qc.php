<?php
/** QC personal + team KPI scorecard. */
$wmax = max(1, ...array_column($weekly, 'n'));
$bmax = max(1, ...array_values($buckets));
$bcol = ['< 4 h' => 'var(--green)', '4–24 h' => '#16a34a', '1–3 days' => 'var(--amber)', '> 3 days' => 'var(--red)'];
?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow"><?= $self ? 'My performance' : 'QC reviewer scorecard' ?></div><h1><?= $self ? 'QC KPIs' : e($who) ?></h1>
    <div class="sub">Review throughput, turnaround against the 24-hour SLA, decisions and the live queue.</div></div>
</div>
<?php View::render('partials/kpi_period', ['P' => $P], null); ?>
<?php View::render('partials/kpi_cards', ['cards' => $cards], null); ?>

<div class="grid grid-2">
  <div class="card">
    <div class="card-h"><h2><?= icon('clock', 'sm') ?> Review turnaround</h2><span class="hint">submission → decision</span></div>
    <div class="card-b bars">
      <?php foreach ($buckets as $k => $n): ?>
        <div class="bar-row"><span><?= e($k) ?></span><div class="track"><span style="width:<?= round($n / $bmax * 100) ?>%;background:<?= $bcol[$k] ?>"></span></div><span class="v"><?= (int) $n ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-h"><h2><?= icon('shield', 'sm') ?> Decisions per week</h2><span class="hint">last 12 weeks</span></div>
    <div class="card-b"><div class="colchart">
      <?php foreach ($weekly as $wi => $w): ?><div class="col"><div class="b" data-v="<?= $w['n'] ?> · week of <?= e($w['label']) ?>" style="height:<?= max(2, round($w['n'] / $wmax * 100)) ?>%"></div><small style="font-size:10px;white-space:nowrap"><?= $wi % 2 ? '&nbsp;' : e($w['label']) ?></small></div><?php endforeach; ?>
    </div></div>
  </div>
</div>

<div class="grid grid-main mt-2">
  <div class="card">
    <div class="card-h"><h2><?= icon('history', 'sm') ?> <?= $self ? 'My' : 'Reviewer' ?> decisions</h2><span class="hint"><?= count($reviews) ?></span></div>
    <?php if ($reviews): ?>
    <div class="table-wrap"><table class="t stack-sm">
      <thead><tr><th>Decided</th><th>Sub-job</th><th>Inspector</th><th class="num">Review time</th><th>Decision</th><th>Comments</th></tr></thead>
      <tbody><?php foreach ($reviews as $r): ?>
        <tr class="row-link" data-href="<?= url('qc/review/' . $r['sub_job_id']) ?>">
          <td data-label="Decided" class="nowrap small"><?= e(fmt_datetime($r['created_at'])) ?></td>
          <td data-label="Sub-job"><span class="mono strong"><?= e($r['sub_job_no']) ?></span><div class="sub"><?= e($r['type_name']) ?><?= $r['override_by'] ? ' · override' : '' ?></div></td>
          <td data-label="Inspector"><?= e($r['inspector_name']) ?></td>
          <td class="num" data-label="Review time"><?php if ($r['review_hours'] === null): ?>—<?php else: ?><span class="badge badge-<?= $r['review_hours'] <= 24 ? 'green' : ($r['review_hours'] <= 72 ? 'amber' : 'red') ?>"><i></i><?= e(number_format($r['review_hours'], 1)) ?> h</span><?php endif; ?></td>
          <td data-label="Decision"><?= badge($r['decision'] === 'approved' ? 'approved' : 'rework') ?></td>
          <td data-label="Comments" class="small"><?= e(mb_strimwidth((string) $r['comments'], 0, 90, '…')) ?></td>
        </tr>
      <?php endforeach; ?></tbody></table></div>
    <?php else: View::render('partials/empty', ['icon' => 'shield', 'title' => 'No decisions in this period', 'text' => 'Choose a longer period above.'], null); endif; ?>
  </div>
  <div class="stack">
    <div class="card"><div class="card-h"><h2><?= icon('users', 'sm') ?> QC team (all reviewers)</h2></div>
      <div class="card-b"><dl class="kv small" style="grid-template-columns:150px 1fr">
        <dt>Reviews</dt><dd><?= (int) $team['qc_reviews'] ?></dd>
        <dt>Avg review time</dt><dd><?= e(KpiService::fmt($team['qc_review_hours'], 'h')) ?></dd>
        <dt>Within 24 h</dt><dd><?= e(KpiService::fmt($team['qc_sla'], '%')) ?></dd>
        <dt>Rework rate</dt><dd><?= e(KpiService::fmt($team['qc_rework_rate'], '%')) ?></dd>
      </dl>
      <table class="t mt-2"><tbody><?php foreach ($reviewers as $r): ?><tr><td><?= e($r['reviewer']) ?></td><td class="num"><b><?= (int) $r['reviews'] ?></b> <span class="sub">reviews</span></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="card"><div class="card-h"><h2><?= icon('alert', 'sm') ?> Rework by inspector</h2><span class="hint">helps target coaching</span></div>
      <div class="card-b flush"><table class="t"><thead><tr><th>Inspector</th><th class="num">Reviews</th><th class="num">Returned</th><th class="num">Rate</th></tr></thead><tbody>
        <?php foreach ($by_inspector as $r): $rate = $r['reviews'] ? $r['reworks'] / $r['reviews'] * 100 : 0; ?>
          <tr><td><?= e($r['inspector']) ?></td><td class="num"><?= (int) $r['reviews'] ?></td><td class="num"><?= (int) $r['reworks'] ?></td>
            <td class="num"><span class="badge badge-<?= $rate <= 10 ? 'green' : ($rate <= 25 ? 'amber' : 'red') ?>"><i></i><?= e(number_format($rate, 0)) ?>%</span></td></tr>
        <?php endforeach; ?>
        <?php if (!$by_inspector): ?><tr><td class="muted" colspan="4">No reviews in this period.</td></tr><?php endif; ?>
      </tbody></table></div></div>
  </div>
</div>
