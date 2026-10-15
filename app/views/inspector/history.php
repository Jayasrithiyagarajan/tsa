<div class="page-head"><div class="ph-text"><h1>Submitted & approved</h1><div class="sub">Your inspections waiting for QC or already approved.</div></div></div>
<div class="card">
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Sub-job</th><th>Inspection</th><th>Client</th><th>Certificate</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $s): ?>
      <tr class="row-link" data-href="<?= url('inspector/subjobs/' . $s['id']) ?>">
        <td data-label="Sub-job" class="mono strong"><?= e($s['sub_job_no']) ?></td>
        <td data-label="Inspection"><?= e($s['type_name']) ?><div class="sub"><?= e($s['eq_description']) ?> <?= e($s['equipment_no']) ?></div></td>
        <td data-label="Client"><?= e($s['client_name']) ?></td>
        <td data-label="Certificate" class="mono small"><?= e($s['cert']['certificate_no'] ?? '—') ?></td>
        <td data-label="Submitted" class="nowrap small"><?= e(fmt_datetime($s['submitted_at'])) ?></td>
        <td data-label="Status"><?= badge($s['status']) ?><?= $s['result'] ? ' ' . badge($s['result']) : '' ?></td>
        <td class="actions"><?php if ($s['cert']): ?><a class="btn btn-ghost btn-sm" href="<?= url('documents/certificate/' . $s['id']) ?>"><?= icon('eye', 'sm') ?></a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'history', 'title' => 'Nothing submitted yet', 'text' => ''], null); endif; ?>
</div>
