<div class="page-head">
  <div class="ph-text"><h1>Certificates</h1><div class="sub">QC-approved certificates for your equipment. Each one carries a QR code for public verification.</div></div>
  <div class="actions"><?php View::render('partials/export', [], null); ?></div>
</div>
<div class="card">
  <form class="toolbar" method="get"><div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Certificate no., equipment…"></div></form>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Certificate</th><th>Inspection</th><th>Equipment</th><th>Result</th><th>Issued</th><th>Valid until</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $c): ?>
      <tr>
        <td data-label="Certificate" class="mono strong"><?= e($c['certificate_no']) ?></td>
        <td data-label="Inspection"><?= e($c['type_name']) ?></td>
        <td data-label="Equipment"><?= e($c['eq_description']) ?><div class="sub"><?= e($c['equipment_no']) ?></div></td>
        <td data-label="Result"><?= $c['result'] ? badge($c['result']) : '—' ?></td>
        <td data-label="Issued" class="nowrap"><?= e(fmt_date($c['issue_date'])) ?></td>
        <td data-label="Valid until" class="nowrap"><?= e(fmt_date($c['next_inspection_date'])) ?></td>
        <td data-label="Status"><?= badge($c['status']) ?></td>
        <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('documents/certificate/' . $c['sub_job_id']) ?>"><?= icon('eye', 'sm') ?></a><a class="btn btn-secondary btn-sm" href="<?= url('documents/certificate/' . $c['sub_job_id'], ['download' => 1]) ?>"><?= icon('download', 'sm') ?> PDF</a></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: View::render('partials/empty', ['icon' => 'award', 'title' => 'No certificates found', 'text' => ''], null); endif; ?>
</div>
