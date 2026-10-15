<div class="page-head">
  <div class="ph-text"><h1>Audit log</h1><div class="sub">Immutable record of logins, status changes, assignments, overrides, approvals, document downloads and exports.</div></div>
  <div class="actions"><?php View::render('partials/export', [], null); ?></div>
</div>
<div class="card">
  <form class="toolbar" method="get" data-autosubmit>
    <div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Action or detail…"></div>
    <select class="select" name="entity"><option value="">All entities</option><?php foreach ($entities as $en): ?><option value="<?= e($en) ?>"<?= selected($en, $entity) ?>><?= e($en) ?></option><?php endforeach; ?></select>
    <select class="select" name="user"><option value="">All users</option><?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"<?= selected($u['id'], $user) ?>><?= e($u['name']) ?></option><?php endforeach; ?></select>
  </form>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr></thead>
    <tbody><?php foreach ($rows as $a): ?>
      <tr>
        <td data-label="Time" class="nowrap small"><?= e(fmt_datetime($a['created_at'])) ?></td>
        <td data-label="User"><?= e($a['user_name'] ?? 'System / public') ?><?= $a['user_role'] ? ' ' . badge($a['user_role']) : '' ?></td>
        <td data-label="Action"><span class="pill mono"><?= e($a['action']) ?></span></td>
        <td data-label="Entity" class="small"><?= e($a['entity_type']) ?><?= $a['entity_id'] ? ' #' . (int) $a['entity_id'] : '' ?></td>
        <td data-label="Details" class="small mono" style="max-width:380px;word-break:break-word"><?= e(mb_strimwidth((string) $a['details'], 0, 220, '…')) ?></td>
        <td data-label="IP" class="small muted"><?= e($a['ip_address']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
</div>
