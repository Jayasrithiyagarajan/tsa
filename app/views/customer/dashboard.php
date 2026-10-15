<?php $u = Auth::user(); $today = date('Y-m-d'); $soon = date('Y-m-d', strtotime('+30 days')); ?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow"><?= e($u['client_name']) ?></div><h1>Welcome, <?= e(explode(' ', $u['name'])[0]) ?></h1><div class="sub">Track your inspections, download certificates and keep your equipment compliant.</div></div>
  <div class="actions"><a class="btn btn-primary" href="<?= url('portal/requests/new') ?>"><?= icon('plus', 'sm') ?> Request inspection</a></div>
</div>
<?php if ((int) $k['overdue'] > 0): ?><div class="alert alert-error"><?= icon('alert') ?><div><b><?= (int) $k['overdue'] ?> item(s) are past their inspection due date.</b> Equipment should not be used until re-inspected. <a href="<?= url('portal/equipment') ?>">View register</a></div></div><?php endif; ?>
<div class="kpis">
  <a class="kpi" href="<?= url('portal/requests') ?>"><span class="k-ic t-blue"><?= icon('inbox') ?></span><span><div class="k-v"><?= (int) $k['open_requests'] ?></div><div class="k-l">Open requests</div></span></a>
  <a class="kpi" href="<?= url('portal/jobs') ?>"><span class="k-ic t-indigo"><?= icon('briefcase') ?></span><span><div class="k-v"><?= (int) $k['active_jobs'] ?></div><div class="k-l">Jobs in progress</div></span></a>
  <a class="kpi" href="<?= url('portal/certificates') ?>"><span class="k-ic t-green"><?= icon('award') ?></span><span><div class="k-v"><?= (int) $k['certs'] ?></div><div class="k-l">Certificates available</div></span></a>
  <a class="kpi" href="<?= url('portal/equipment') ?>"><span class="k-ic t-amber"><?= icon('calendar') ?></span><span><div class="k-v"><?= (int) $k['due'] ?></div><div class="k-l">Due within 30 days</div></span></a>
</div>
<div class="grid grid-main">
  <div class="stack">
    <div class="card"><div class="card-h"><h2><?= icon('briefcase', 'sm') ?> Your jobs</h2><a class="btn btn-ghost btn-sm" href="<?= url('portal/jobs') ?>">All jobs <?= icon('arrow-right', 'sm') ?></a></div>
      <?php if ($jobs): foreach ($jobs as $j): ?>
        <a class="sub-row" href="<?= url('portal/jobs/' . $j['id']) ?>" style="grid-template-columns:minmax(0,1fr) 220px auto">
          <span class="sr-t"><b><?= e($j['title']) ?></b><span class="mono"><?= e($j['job_no']) ?> · <?= e(fmt_date($j['scheduled_date']) ?: 'scheduling') ?></span></span>
          <span><span class="progress-label"><span><?= $j['progress']['approved'] ?>/<?= $j['progress']['total'] ?> certified</span></span><span class="progress" style="display:block"><span style="width:<?= $j['progress']['pct'] ?>%"></span></span></span>
          <span><?= badge($j['status']) ?></span>
        </a>
      <?php endforeach; else: View::render('partials/empty', ['icon' => 'briefcase', 'title' => 'No jobs yet', 'text' => 'Submit a request to get started.'], null); endif; ?>
    </div>
    <div class="card"><div class="card-h"><h2><?= icon('award', 'sm') ?> Latest certificates</h2><a class="btn btn-ghost btn-sm" href="<?= url('portal/certificates') ?>">All <?= icon('arrow-right', 'sm') ?></a></div>
      <?php if ($certs): ?><div class="table-wrap"><table class="t stack-sm"><tbody><?php foreach ($certs as $c): ?>
        <tr><td data-label="Certificate"><span class="mono strong"><?= e($c['certificate_no']) ?></span><div class="sub"><?= e($c['type_name']) ?> · <?= e($c['eq_description']) ?> <?= e($c['equipment_no']) ?></div></td>
          <td data-label="Issued" class="sub nowrap"><?= e(fmt_date($c['issue_date'])) ?></td><td data-label="Status"><?= badge($c['status']) ?></td>
          <td class="actions"><a class="btn btn-secondary btn-sm" href="<?= url('documents/certificate/' . $c['sub_job_id'], ['download' => 1]) ?>"><?= icon('download', 'sm') ?> PDF</a></td></tr>
      <?php endforeach; ?></tbody></table></div><?php else: View::render('partials/empty', ['icon' => 'award', 'title' => 'No certificates yet', 'text' => 'Certificates appear here as soon as QC approves them.'], null); endif; ?>
    </div>
  </div>
  <div class="card"><div class="card-h"><h2><?= icon('calendar', 'sm') ?> Upcoming due dates</h2></div>
    <div class="card-b" style="padding-top:6px;padding-bottom:6px"><?php foreach ($due as $e): $n = $e['next_inspection_date']; ?>
      <div class="row" style="padding:10px 0;border-bottom:1px dashed var(--border)"><div style="flex:1;min-width:0"><div class="strong small"><?= e($e['description']) ?> <?= e($e['equipment_no']) ?></div><div class="small muted"><?= e($e['manufacturer']) ?> <?= e($e['model']) ?></div></div>
        <span class="badge badge-<?= $n < $today ? 'red' : ($n <= $soon ? 'amber' : 'green') ?>"><i></i><?= e(fmt_date($n)) ?></span></div>
    <?php endforeach; ?><?php if (!$due): ?><p class="muted small" style="padding:10px 0">No due dates yet.</p><?php endif; ?></div>
    <div class="card-f"><a class="btn btn-primary btn-sm" href="<?= url('portal/requests/new') ?>">Book re-inspection</a></div>
  </div>
</div>
