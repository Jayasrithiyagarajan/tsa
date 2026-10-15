<?php $tab = fn ($r, $label) => '<a class="' . ($role === $r ? 'on' : '') . '" href="' . url('admin/users', ['role' => $r]) . '">' . e($label) . '<span class="n">' . (int) ($r ? ($counts[$r] ?? 0) : array_sum($counts)) . '</span></a>'; ?>
<div class="page-head">
  <div class="ph-text"><h1>Users & inspectors</h1><div class="sub">Accounts for all four portals. Inspector qualifications control who can be assigned to which inspection type.</div></div>
  <div class="actions">
    <?php View::render('partials/export', [], null); ?>
    <?php if (Auth::isSuper()): ?><a class="btn btn-primary" href="<?= url('admin/users/new', ['role' => $role ?: 'inspector']) ?>"><?= icon('plus', 'sm') ?> New <?= e($role ? strtolower(Workflow::label($role)) : 'user') ?></a><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="toolbar">
    <div class="seg-tabs"><?= $tab('', 'All') ?><?= $tab('inspector', 'Inspectors') ?><?= $tab('qc', 'QC') ?><?= $tab('admin', 'Admins') ?><?= $tab('customer', 'Customers') ?></div>
    <span class="grow"></span>
    <form class="search" method="get"><input type="hidden" name="role" value="<?= e($role) ?>"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Name or email…"></form>
  </div>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>User</th><th>Role</th><th>Qualification / client</th><th>Qualified for</th><th class="num">Open work</th><th>Last login</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($rows as $u): $exp = $u['cert_expiry'] && $u['cert_expiry'] < date('Y-m-d', strtotime('+60 days')); ?>
      <tr class="row-link" data-href="<?= url('admin/users/' . $u['id']) ?>">
        <td data-label="User"><span class="person"><span class="avatar sm"><?= e(initials($u['name'])) ?></span><span><span class="p-n"><?= e($u['name']) ?></span><br><span class="p-s"><?= e($u['email']) ?></span></span></span></td>
        <td data-label="Role"><?= badge($u['role']) ?></td>
        <td data-label="Qualification"><?= e($u['role'] === 'customer' ? $u['client_name'] : ($u['qualification'] ?: '—')) ?>
          <?php if ($exp): ?><div><span class="badge badge-red"><i></i>Expires <?= e(fmt_date($u['cert_expiry'])) ?></span></div><?php endif; ?></td>
        <td data-label="Skills" class="small"><?= e($u['skills'] ?: ($u['role'] === 'inspector' ? 'None — cannot be assigned' : '—')) ?></td>
        <td class="num" data-label="Open"><?= $u['role'] === 'inspector' ? (int) $u['open_load'] : '—' ?></td>
        <td data-label="Last login" class="sub"><?= e($u['last_login_at'] ? time_ago($u['last_login_at']) : 'Never') ?></td>
        <td data-label="Status"><?= badge($u['status']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
</div>
