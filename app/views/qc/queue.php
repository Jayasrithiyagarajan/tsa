<div class="page-head">
  <div class="ph-text"><h1>Review queue</h1><div class="sub">Submitted inspections — oldest and most urgent first.</div></div>
  <form method="get" data-autosubmit><select class="select" name="type"><option value="">All inspection types</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"<?= selected($t['id'], $type) ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></form>
</div>
<div class="card">
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Sub-job</th><th>Inspection / equipment</th><th>Client</th><th>Inspector</th><th>Checklist</th><th>Result</th><th>Waiting</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $s): ?>
      <tr class="row-link" data-href="<?= url('qc/review/' . $s['id']) ?>">
        <td data-label="Sub-job"><span class="mono strong"><?= e($s['sub_job_no']) ?></span><div><?= badge($s['priority']) ?><?= $s['override_by'] ? ' <span class="badge badge-amber"><i></i>Override</span>' : '' ?></div></td>
        <td data-label="Inspection"><?= e($s['type_name']) ?><div class="sub"><?= e($s['eq_description']) ?> <?= e($s['equipment_no']) ?></div></td>
        <td data-label="Client"><?= e($s['client_name']) ?></td>
        <td data-label="Inspector"><?= e($s['inspector_name']) ?></td>
        <td data-label="Checklist" class="small"><?php if ($s['stats']): ?><?= (int) $s['stats']['pass'] ?> pass · <span style="color:var(--red)"><?= (int) $s['stats']['fail'] ?> fail</span> · <?= (int) $s['stats']['na'] ?> N/A<?php else: ?><span class="muted">n/a</span><?php endif; ?></td>
        <td data-label="Result"><?= !empty($s['cert']['result']) ? badge($s['cert']['result']) : '<span class="muted">—</span>' ?></td>
        <td data-label="Waiting" class="sub nowrap"><?= e(time_ago($s['submitted_at'])) ?></td>
        <td class="actions"><a class="btn btn-primary btn-sm" href="<?= url('qc/review/' . $s['id']) ?>">Review</a></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'check', 'title' => 'All caught up', 'text' => 'No inspections are waiting for QC.'], null); endif; ?>
</div>
