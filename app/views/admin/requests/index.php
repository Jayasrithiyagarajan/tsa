<?php $tab = fn ($s, $label, $n = null) => '<a class="' . ($status === $s ? 'on' : '') . '" href="' . url('admin/requests', ['status' => $s]) . '">' . e($label) . ($n !== null ? '<span class="n">' . (int) $n . '</span>' : '') . '</a>'; ?>
<div class="page-head">
  <div class="ph-text"><h1>Client requests</h1><div class="sub">Every request from the website, email, phone and the customer portal.</div></div>
  <div class="actions">
    <?php View::render('partials/export', [], null); ?>
    <a class="btn btn-primary" href="<?= url('admin/requests/new') ?>"><?= icon('plus', 'sm') ?> Log email / phone request</a>
  </div>
</div>
<div class="card">
  <div class="toolbar">
    <div class="seg-tabs">
      <?= $tab('open', 'Open', ($counts['new'] ?? 0) + ($counts['under_review'] ?? 0) + ($counts['quoted'] ?? 0)) ?>
      <?= $tab('new', 'New', $counts['new'] ?? 0) ?>
      <?= $tab('converted', 'Converted', $counts['converted'] ?? 0) ?>
      <?= $tab('rejected', 'Rejected', $counts['rejected'] ?? 0) ?>
      <?= $tab('all', 'All') ?>
    </div>
    <span class="grow"></span>
    <form class="row wrap" method="get" data-autosubmit>
      <input type="hidden" name="status" value="<?= e($status) ?>">
      <div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Request no., company, contact…"></div>
      <select class="select" name="channel"><option value="">All channels</option>
        <?php foreach (['website', 'email', 'phone', 'portal'] as $c): ?><option value="<?= $c ?>"<?= selected($c, $channel) ?>><?= e(Workflow::label($c)) ?></option><?php endforeach; ?></select>
      <select class="select" name="sort"><?php foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'company' => 'Company A–Z', 'date' => 'Preferred date'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, input('sort', 'newest')) ?>><?= $l ?></option><?php endforeach; ?></select>
    </form>
  </div>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Request</th><th>Company / contact</th><th>Channel</th><th>Location</th><th>Preferred</th><th class="num">Items</th><th>Status</th><th>Received</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="row-link" data-href="<?= url('admin/requests/' . $r['id']) ?>">
        <td data-label="Request"><span class="mono primary-cell"><?= e($r['request_no']) ?></span><?php if ($r['job_no']): ?><div class="sub">→ <?= e($r['job_no']) ?></div><?php endif; ?></td>
        <td data-label="Company"><span class="strong"><?= e($r['company_name']) ?></span><div class="sub"><?= e($r['contact_name']) ?> · <?= e($r['email'] ?: $r['phone']) ?></div></td>
        <td data-label="Channel"><?= badge($r['channel']) ?></td>
        <td data-label="Location"><?= e($r['location']) ?></td>
        <td data-label="Preferred"><?= e(fmt_date($r['preferred_date'])) ?: '<span class="muted">—</span>' ?></td>
        <td class="num" data-label="Items"><?= (int) $r['items'] ?></td>
        <td data-label="Status"><?= badge($r['status']) ?></td>
        <td data-label="Received" class="sub nowrap"><?= e(fmt_datetime($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'inbox', 'title' => 'No requests here', 'text' => 'Requests from the website and customer portal appear automatically. Log email and phone requests manually.'], null); endif; ?>
</div>
