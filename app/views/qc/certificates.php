<div class="page-head"><div class="ph-text"><h1>Issued certificates</h1><div class="sub">All certificates released after QC review.</div></div>
  <form class="search" method="get" style="position:relative;min-width:260px"><span style="position:absolute;left:10px;top:10px;color:var(--faint)"><?= icon('search', 'sm') ?></span><input class="input" style="padding-left:34px" name="q" value="<?= e($q) ?>" placeholder="Certificate, client, equipment…"></form></div>
<div class="card">
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Certificate</th><th>Client</th><th>Inspection / equipment</th><th>Result</th><th>Issued</th><th>Valid until</th><th>Reviewed by</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $c): ?>
      <tr>
        <td data-label="Certificate" class="mono strong"><?= e($c['certificate_no']) ?></td>
        <td data-label="Client"><?= e($c['client_name']) ?></td>
        <td data-label="Inspection"><?= e($c['type_name']) ?><div class="sub"><?= e($c['eq_description']) ?> <?= e($c['equipment_no']) ?></div></td>
        <td data-label="Result"><?= $c['result'] ? badge($c['result']) : '—' ?></td>
        <td data-label="Issued" class="nowrap"><?= e(fmt_date($c['issue_date'])) ?></td>
        <td data-label="Valid until" class="nowrap"><?= e(fmt_date($c['next_inspection_date'])) ?></td>
        <td data-label="Reviewed by"><?= e($c['reviewer_name']) ?></td>
        <td data-label="Status"><?= badge($c['status']) ?></td>
        <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('documents/certificate/' . $c['sub_job_id']) ?>"><?= icon('eye', 'sm') ?></a><a class="btn btn-ghost btn-sm" href="<?= url('documents/certificate/' . $c['sub_job_id'], ['download' => 1]) ?>"><?= icon('download', 'sm') ?></a></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
</div>
