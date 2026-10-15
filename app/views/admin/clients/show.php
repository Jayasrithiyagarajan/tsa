<div class="page-head">
  <div class="ph-text"><div class="eyebrow">Client · <?= e($client['code']) ?></div><h1 class="row wrap"><?= e($client['name']) ?> <?= badge($client['status']) ?></h1>
    <div class="sub"><span><?= icon('user', 'sm') ?> <?= e($client['contact_person'] ?: '—') ?></span><span><?= icon('mail', 'sm') ?> <?= e($client['email'] ?: '—') ?></span><span><?= icon('phone', 'sm') ?> <?= e($client['phone'] ?: '—') ?></span></div></div>
  <div class="actions">
    <button class="btn btn-secondary" data-modal="edit-client"><?= icon('edit', 'sm') ?> Edit</button>
    <a class="btn btn-secondary" href="<?= url('admin/users/new', ['role' => 'customer', 'client' => $client['id']]) ?>"><?= icon('user', 'sm') ?> Add portal user</a>
    <a class="btn btn-primary" href="<?= url('admin/jobs/new') ?>"><?= icon('plus', 'sm') ?> New job</a>
  </div>
</div>
<div class="kpis">
  <div class="kpi"><span class="k-ic t-blue"><?= icon('briefcase') ?></span><span><div class="k-v"><?= count($jobs) ?></div><div class="k-l">Jobs</div></span></div>
  <div class="kpi"><span class="k-ic t-teal"><?= icon('truck') ?></span><span><div class="k-v"><?= count($equipment) ?></div><div class="k-l">Equipment registered</div></span></div>
  <div class="kpi"><span class="k-ic t-green"><?= icon('award') ?></span><span><div class="k-v"><?= $certs ?></div><div class="k-l">Certificates issued</div></span></div>
  <div class="kpi"><span class="k-ic t-violet"><?= icon('users') ?></span><span><div class="k-v"><?= count($users) ?></div><div class="k-l">Portal users</div></span></div>
</div>
<div class="grid grid-2">
  <div class="card"><div class="card-h"><h2><?= icon('briefcase', 'sm') ?> Jobs</h2></div>
    <?php if ($jobs): ?><div class="table-wrap"><table class="t"><tbody><?php foreach ($jobs as $j): ?>
      <tr class="row-link" data-href="<?= url('admin/jobs/' . $j['id']) ?>"><td><span class="mono strong"><?= e($j['job_no']) ?></span><div class="sub"><?= e($j['title']) ?></div></td><td class="sub"><?= e(fmt_date($j['scheduled_date'])) ?></td><td class="right"><?= badge($j['status']) ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php else: View::render('partials/empty', ['icon' => 'briefcase', 'title' => 'No jobs yet', 'text' => ''], null); endif; ?></div>
  <div class="card"><div class="card-h"><h2><?= icon('truck', 'sm') ?> Equipment</h2><a class="btn btn-ghost btn-sm" href="<?= url('admin/equipment', ['client' => $client['id']]) ?>">Manage</a></div>
    <?php if ($equipment): ?><div class="table-wrap"><table class="t"><tbody><?php foreach ($equipment as $e): ?>
      <tr><td><span class="strong"><?= e($e['description']) ?></span><div class="sub"><?= e($e['equipment_no']) ?> · <?= e($e['manufacturer']) ?> <?= e($e['model']) ?></div></td>
        <td class="sub nowrap">Next: <?= e(fmt_date($e['next_inspection_date']) ?: '—') ?></td><td class="right"><?= badge($e['status']) ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php else: View::render('partials/empty', ['icon' => 'truck', 'title' => 'No equipment', 'text' => 'Equipment is added automatically from sub-jobs.'], null); endif; ?></div>
</div>
<?php if ($users): ?>
<div class="card mt-2"><div class="card-h"><h2><?= icon('users', 'sm') ?> Customer portal users</h2></div><div class="table-wrap"><table class="t"><tbody>
  <?php foreach ($users as $u): ?><tr class="row-link" data-href="<?= url('admin/users/' . $u['id']) ?>"><td class="strong"><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td class="sub">Last login <?= e($u['last_login_at'] ? time_ago($u['last_login_at']) : 'never') ?></td><td class="right"><?= badge($u['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php endif; ?>
<dialog class="modal" id="edit-client" style="width:min(760px,calc(100vw - 32px))"><div class="m-h"><h3>Edit client</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><?php View::render('admin/clients/form', ['client' => $client], null); ?></div></dialog>
