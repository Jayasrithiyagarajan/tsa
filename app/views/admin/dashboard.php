<?php
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$f = array_column($funnel, 'n', 'status');
$stages = [
    ['pending', 'Unassigned', '#d97706'], ['assigned', 'Assigned', '#2563eb'], ['in_progress', 'In progress', '#4f46e5'],
    ['rework', 'Rework', '#dc2626'], ['submitted', 'Awaiting QC', '#7c3aed'], ['approved', 'Approved', '#059669'],
];
$max = max(1, ...array_map(fn ($s) => (int) ($f[$s[0]] ?? 0), $stages));
$m = array_column($months, 'n', 'm');
$monthsAxis = [];
for ($i = 5; $i >= 0; $i--) { $key = date('Y-m', strtotime("first day of -$i month")); $monthsAxis[$key] = (int) ($m[$key] ?? 0); }
$mmax = max(1, ...array_values($monthsAxis));
?>
<div class="page-head">
  <div class="ph-text">
    <div class="eyebrow"><?= e(date('l, d F Y')) ?></div>
    <h1><?= e($greet) ?>, <?= e(explode(' ', Auth::user()['name'])[0]) ?></h1>
    <div class="sub">Operations overview — requests, assignments, inspections, QC and certificates.</div>
  </div>
  <div class="actions">
    <a class="btn btn-secondary" href="<?= url('admin/requests/new') ?>"><?= icon('phone', 'sm') ?> Log email / phone request</a>
    <a class="btn btn-primary" href="<?= url('admin/jobs/new') ?>"><?= icon('plus', 'sm') ?> New job</a>
  </div>
</div>

<div class="kpis">
  <a class="kpi" href="<?= url('admin/requests', ['status' => 'open']) ?>"><span class="k-ic t-blue"><?= icon('inbox') ?></span><span><div class="k-v"><?= (int) $k['new_requests'] ?></div><div class="k-l">New client requests</div><div class="k-t"><?= (int) $k['untouched_requests'] ?> not yet reviewed</div></span></a>
  <a class="kpi" href="<?= url('admin/assign') ?>"><span class="k-ic t-amber"><?= icon('users') ?></span><span><div class="k-v"><?= (int) $k['subjobs_unassigned'] ?></div><div class="k-l">Sub-jobs awaiting inspector</div><div class="k-t"><?= (int) $k['jobs_unassigned'] ?> jobs fully unassigned</div></span></a>
  <a class="kpi" href="<?= url('admin/jobs', ['status' => 'in_progress']) ?>"><span class="k-ic t-indigo"><?= icon('clipboard') ?></span><span><div class="k-v"><?= (int) $k['subjobs_active'] ?></div><div class="k-l">Assigned / in progress</div><div class="k-t"><?= (int) $k['in_progress'] ?> inspections running now</div></span></a>
  <a class="kpi" href="<?= url('admin/jobs', ['status' => 'under_review']) ?>"><span class="k-ic t-violet"><?= icon('shield') ?></span><span><div class="k-v"><?= (int) $k['awaiting_qc'] ?></div><div class="k-l">Awaiting QC review</div><div class="k-t"><?= (int) $k['checklists_done'] ?> checklists completed</div></span></a>
  <a class="kpi" href="<?= url('admin/certificates') ?>"><span class="k-ic t-green"><?= icon('award') ?></span><span><div class="k-v"><?= (int) $k['certs_issued'] ?></div><div class="k-l">Certificates issued</div><div class="k-t"><?= (int) $k['certs_pending'] ?> pending (draft / QC)</div></span></a>
  <a class="kpi" href="<?= url('admin/stickers') ?>"><span class="k-ic t-teal"><?= icon('tag') ?></span><span><div class="k-v"><?= (int) $k['stickers_stock'] ?></div><div class="k-l">Stickers in stock</div><div class="k-t"><?= (int) $k['stickers_reserved'] ?> with inspectors · <?= (int) $k['stickers_issued'] ?> used on jobs</div></span></a>
</div>

<div class="grid grid-main">
  <div class="stack">
    <div class="grid grid-2">
      <div class="card">
        <div class="card-h"><h2><?= icon('layers', 'sm') ?> Sub-job pipeline</h2><span class="hint"><?= (int) $k['jobs_open'] ?> open · <?= (int) $k['jobs_completed'] ?> completed jobs</span></div>
        <div class="card-b bars">
          <?php foreach ($stages as [$key, $label, $color]): $n = (int) ($f[$key] ?? 0); ?>
            <div class="bar-row"><span><?= e($label) ?></span><div class="track"><span style="width:<?= round($n / $max * 100) ?>%;background:<?= $color ?>"></span></div><span class="v"><?= $n ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-h"><h2><?= icon('award', 'sm') ?> Certificates issued</h2><span class="hint">last 6 months</span></div>
        <div class="card-b">
          <div class="colchart">
            <?php foreach ($monthsAxis as $key => $n): ?>
              <div class="col"><div class="b" data-v="<?= $n ?>" style="height:<?= max(2, round($n / $mmax * 100)) ?>%"></div><small><?= e(date('M', strtotime($key . '-01'))) ?></small></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><h2><?= icon('alert', 'sm') ?> Needs assignment</h2><a class="btn btn-ghost btn-sm" href="<?= url('admin/assign') ?>">Assignment board <?= icon('arrow-right', 'sm') ?></a></div>
      <?php if ($attention): ?>
      <div class="table-wrap"><table class="t stack-sm">
        <thead><tr><th>Job</th><th>Client</th><th>Scheduled</th><th>Priority</th><th class="num">Unassigned</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($attention as $j): ?>
          <tr data-href="<?= url('admin/jobs/' . $j['id']) ?>" class="row-link">
            <td data-label="Job"><span class="primary-cell mono"><?= e($j['job_no']) ?></span><div class="sub"><?= e($j['title']) ?></div></td>
            <td data-label="Client"><?= e($j['client_name']) ?></td>
            <td data-label="Scheduled"><?= e(fmt_date($j['scheduled_date'])) ?: '<span class="muted">—</span>' ?></td>
            <td data-label="Priority"><?= badge($j['priority']) ?></td>
            <td class="num" data-label="Unassigned"><?= (int) $j['pending_n'] ?> / <?= (int) $j['total_n'] ?></td>
            <td class="actions"><a class="btn btn-secondary btn-sm" href="<?= url('admin/jobs/' . $j['id']) ?>#assign">Assign</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php else: View::render('partials/empty', ['icon' => 'check', 'title' => 'Everything is assigned', 'text' => 'No sub-jobs are waiting for an inspector.'], null); endif; ?>
    </div>

    <div class="card">
      <div class="card-h"><h2><?= icon('inbox', 'sm') ?> Latest client requests</h2><a class="btn btn-ghost btn-sm" href="<?= url('admin/requests') ?>">All requests <?= icon('arrow-right', 'sm') ?></a></div>
      <div class="table-wrap"><table class="t stack-sm">
        <thead><tr><th>Request</th><th>Company</th><th>Channel</th><th>Received</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
          <tr class="row-link" data-href="<?= url('admin/requests/' . $r['id']) ?>">
            <td data-label="Request" class="mono primary-cell"><?= e($r['request_no']) ?></td>
            <td data-label="Company"><?= e($r['company_name']) ?><div class="sub"><?= e($r['contact_name']) ?></div></td>
            <td data-label="Channel"><?= badge($r['channel']) ?></td>
            <td data-label="Received" class="sub"><?= e(time_ago($r['created_at'])) ?></td>
            <td data-label="Status"><?= badge($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
  </div>

  <div class="stack">
    <div class="card">
      <div class="card-h"><h2><?= icon('users', 'sm') ?> Inspector workload</h2></div>
      <div class="card-b" style="padding-top:8px;padding-bottom:8px">
        <?php foreach ($workload as $w): ?>
          <div class="row" style="padding:9px 0;border-bottom:1px dashed var(--border)">
            <span class="avatar sm"><?= e(initials($w['name'])) ?></span>
            <div style="flex:1;min-width:0"><div class="strong small"><?= e($w['name']) ?></div><div class="small muted"><?= e($w['skills'] ?: 'No qualifications') ?></div></div>
            <span class="pill"><?= (int) $w['open_load'] ?> open</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-h"><h2><?= icon('calendar', 'sm') ?> Equipment due</h2></div>
      <div class="card-b"><div class="k-v" style="font-size:28px;font-weight:750"><?= (int) $k['due_soon'] ?></div><div class="muted small">items reach their next inspection date within 30 days</div>
        <a class="btn btn-secondary btn-sm mt-2" href="<?= url('admin/equipment', ['due' => 30]) ?>">View equipment</a></div>
    </div>
    <div class="card">
      <div class="card-h"><h2><?= icon('history', 'sm') ?> Recent activity</h2><?php if (Auth::isSuper()): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/audit') ?>">Audit log</a><?php endif; ?></div>
      <div class="card-b"><ul class="timeline">
        <?php foreach ($activity as $a): ?>
          <li><b><?= e(Workflow::label(str_replace('.', '_', $a['action']))) ?></b> <span class="muted">· <?= e($a['entity_type']) ?> #<?= (int) $a['entity_id'] ?></span><div class="tl-t"><?= e($a['user_name'] ?? 'System / website') ?> · <?= e(time_ago($a['created_at'])) ?></div></li>
        <?php endforeach; ?>
      </ul></div>
    </div>
  </div>
</div>
