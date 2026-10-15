<div class="page-head"><div class="ph-text"><h1>My decisions</h1><div class="sub">Approvals and rework requests you made.</div></div></div>
<div class="card">
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Date</th><th>Sub-job</th><th>Inspection</th><th>Client</th><th>Inspector</th><th>Decision</th><th>Comments</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr class="row-link" data-href="<?= url('qc/review/' . $r['sub_job_id']) ?>">
        <td data-label="Date" class="nowrap small"><?= e(fmt_datetime($r['created_at'])) ?></td>
        <td data-label="Sub-job" class="mono strong"><?= e($r['sub_job_no']) ?><div class="sub"><?= e($r['certificate_no']) ?></div></td>
        <td data-label="Inspection"><?= e($r['type_name']) ?></td>
        <td data-label="Client"><?= e($r['client_name']) ?></td>
        <td data-label="Inspector"><?= e($r['inspector_name']) ?></td>
        <td data-label="Decision"><?= badge($r['decision'] === 'approved' ? 'approved' : 'rework') ?></td>
        <td data-label="Comments" class="small"><?= e(mb_strimwidth((string) $r['comments'], 0, 120, '…')) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'history', 'title' => 'No decisions yet', 'text' => ''], null); endif; ?>
</div>
