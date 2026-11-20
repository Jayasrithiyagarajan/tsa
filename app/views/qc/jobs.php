<?php $tab = fn ($s, $label, $n = null) => '<a class="' . ($status === $s ? 'on' : '') . '" href="' . url('qc/jobs', ['status' => $s]) . '">' . e($label) . ($n !== null ? '<span class="n">' . (int) $n . '</span>' : '') . '</a>'; ?>
<div class="page-head">
  <div class="ph-text"><h1>Jobs</h1><div class="sub">All jobs and the progress of their sub-jobs. Open a job to see which inspections are waiting for your review.</div></div>
</div>
<div class="card">
  <div class="toolbar">
    <div class="seg-tabs">
      <?= $tab('', 'All', array_sum(array_diff_key($counts, ['at_qc' => 0]))) ?>
      <?= $tab('at_qc', 'Waiting for QC', $counts['at_qc'] ?? 0) ?>
      <?= $tab('in_progress', 'In progress', $counts['in_progress'] ?? 0) ?>
      <?= $tab('awaiting_approval', 'Awaiting final approval', $counts['awaiting_approval'] ?? 0) ?>
      <?= $tab('completed', 'Completed', $counts['completed'] ?? 0) ?>
    </div>
    <span class="grow"></span>
    <form class="row wrap" method="get" data-autosubmit>
      <input type="hidden" name="status" value="<?= e($status) ?>">
      <div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Job no., title, P.O., client…"></div>
      <select class="select" name="client"><option value="">All clients</option><?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], $client) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
      <select class="select" name="sort"><?php foreach (['newest' => 'Newest', 'scheduled' => 'Scheduled date', 'priority' => 'Priority', 'client' => 'Client'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, input('sort', 'newest')) ?>><?= $l ?></option><?php endforeach; ?></select>
    </form>
  </div>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Job</th><th>Client</th><th>Scheduled</th><th>Priority</th><th>Lead inspector</th><th style="min-width:160px">Progress</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $j): $pct = $j['n_total'] ? round($j['n_done'] / $j['n_total'] * 100) : 0; ?>
      <tr class="row-link" data-href="<?= url('qc/jobs/' . $j['id']) ?>">
        <td data-label="Job"><span class="mono primary-cell"><?= e($j['job_no']) ?></span><div class="sub"><?= e($j['title']) ?></div></td>
        <td data-label="Client"><?= e($j['client_name']) ?><div class="sub"><?= e($j['location']) ?></div></td>
        <td data-label="Scheduled" class="nowrap"><?= e(fmt_date($j['scheduled_date'])) ?: '<span class="muted">—</span>' ?></td>
        <td data-label="Priority"><?= badge($j['priority']) ?></td>
        <td data-label="Lead"><?= $j['lead_name'] ? '<span class="person"><span class="avatar sm">' . e(initials($j['lead_name'])) . '</span>' . e($j['lead_name']) . '</span>' : '<span class="muted">Unassigned</span>' ?></td>
        <td data-label="Progress">
          <div class="progress-label"><span><?= (int) $j['n_done'] ?>/<?= (int) $j['n_total'] ?> approved</span><span><?= $j['n_qc'] ? '<b>' . (int) $j['n_qc'] . ' at QC</b>' : '' ?></span></div>
          <div class="progress"><span style="width:<?= $pct ?>%"></span></div></td>
        <td data-label="Status"><?= badge($j['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'briefcase', 'title' => 'No jobs match', 'text' => 'Try another filter or search.'], null); endif; ?>
</div>
