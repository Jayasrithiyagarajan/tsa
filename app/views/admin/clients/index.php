<div class="page-head">
  <div class="ph-text"><h1>Clients</h1><div class="sub">Registered companies, their equipment and portal users.</div></div>
  <div class="actions">
    <?php View::render('partials/export', [], null); ?>
    <a class="btn btn-primary" href="<?= url('admin/clients/new') ?>"><?= icon('plus', 'sm') ?> New client</a>
  </div>
</div>
<div class="card">
  <div class="toolbar"><form class="search" method="get"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Name, code, email, city…"></form></div>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Client</th><th>Code</th><th>Contact</th><th>City</th><th class="num">Jobs</th><th class="num">Equipment</th><th class="num">Portal users</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($rows as $c): ?>
      <tr class="row-link" data-href="<?= url('admin/clients/' . $c['id']) ?>">
        <td data-label="Client" class="primary-cell"><?= e($c['name']) ?></td>
        <td data-label="Code"><span class="pill mono"><?= e($c['code']) ?></span></td>
        <td data-label="Contact"><?= e($c['contact_person']) ?><div class="sub"><?= e($c['email']) ?></div></td>
        <td data-label="City"><?= e($c['city']) ?></td>
        <td class="num" data-label="Jobs"><?= (int) $c['jobs_n'] ?><?= $c['open_n'] ? ' <span class="sub">(' . (int) $c['open_n'] . ' open)</span>' : '' ?></td>
        <td class="num" data-label="Equipment"><?= (int) $c['eq_n'] ?></td>
        <td class="num" data-label="Users"><?= (int) $c['users_n'] ?></td>
        <td data-label="Status"><?= badge($c['status']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'building', 'title' => 'No clients found', 'text' => 'Clients are created here or when converting a request.'], null); endif; ?>
</div>
