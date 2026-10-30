<?php $tab = fn ($s, $label) => '<a class="' . ($status === $s ? 'on' : '') . '" href="' . url('admin/certificates', ['status' => $s]) . '">' . e($label) . '<span class="n">' . (int) ($s ? ($counts[$s] ?? 0) : array_sum($counts)) . '</span></a>'; ?>
<div class="cert-page">
<div class="page-head">
  <div class="ph-text"><h1>Certificates</h1><div class="sub">Generated and uploaded certificates. Numbers are reserved when an inspection starts and frozen when QC issues the certificate.</div></div>
  <div class="actions"><?php View::render('partials/export', [], null); ?></div>
</div>
<div class="card">
  <div class="toolbar">
    <div class="seg-tabs"><?= $tab('', 'All') ?><?= $tab('draft', 'Draft') ?><?= $tab('pending_qc', 'Pending QC') ?><?= $tab('issued', 'Issued') ?><?= $tab('revoked', 'Revoked') ?></div>
    <span class="grow"></span>
    <form class="row wrap cert-filter" method="get" data-autosubmit><input type="hidden" name="status" value="<?= e($status) ?>">
      <div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Certificate no., client, equipment…"></div>
      <select class="select" name="type"><option value="">All types</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"<?= selected($t['id'], $type) ?>><?= e($t['name']) ?></option><?php endforeach; ?></select>
    </form>
  </div>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Certificate</th><th>Client / equipment</th><th>Inspection</th><th>Result</th><th>Issued</th><th>Valid until</th><th>Inspector / QC</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $c): ?>
      <tr>
        <td data-label="Certificate"><span class="mono strong"><?= e($c['certificate_no']) ?></span><div class="sub"><a href="<?= url('admin/jobs/' . $c['job_id']) ?>"><?= e($c['sub_job_no']) ?></a><?= $c['source'] === 'uploaded' ? ' · uploaded' : '' ?></div></td>
        <td data-label="Client"><?= e($c['client_name']) ?><div class="sub"><?= e($c['eq_description']) ?> <?= e($c['equipment_no']) ?></div></td>
        <td data-label="Inspection"><?= e($c['type_name']) ?><div class="sub"><?= e(fmt_date($c['inspection_date'])) ?></div></td>
        <td data-label="Result"><?= $c['result'] ? badge($c['result']) : '<span class="muted">—</span>' ?></td>
        <td data-label="Issued" class="nowrap"><?= e(fmt_date($c['issue_date']) ?: '—') ?></td>
        <td data-label="Valid until" class="nowrap"><?= e(fmt_date($c['next_inspection_date']) ?: '—') ?></td>
        <td data-label="People" class="small"><?= e($c['inspector_name']) ?><div class="sub"><?= e($c['reviewer_name'] ?: '—') ?></div></td>
        <td data-label="Status"><?= badge($c['status']) ?></td>
        <td class="actions cert-actions">
          <a class="btn btn-ghost btn-sm" href="<?= url('documents/certificate/' . $c['sub_job_id']) ?>" title="View"><?= icon('eye', 'sm') ?></a>
          <a class="btn btn-ghost btn-sm" href="<?= url('documents/certificate/' . $c['sub_job_id'], ['download' => 1]) ?>" title="Download PDF"><?= icon('download', 'sm') ?></a>
          <?php if ($c['status'] === 'issued' && Auth::isSuper()): ?><button class="btn btn-ghost btn-sm" data-modal="revoke-<?= $c['id'] ?>" title="Revoke" style="color:var(--red)"><?= icon('x', 'sm') ?></button><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'award', 'title' => 'No certificates', 'text' => 'Certificates are created when inspectors start an inspection.'], null); endif; ?>
</div>
</div>
<?php foreach ($rows as $c): if ($c['status'] !== 'issued') continue; ?>
<dialog class="modal" id="revoke-<?= $c['id'] ?>"><form method="post" action="<?= url('admin/certificates/' . $c['id'] . '/revoke') ?>"><?= Csrf::field() ?>
  <div class="m-h"><h3>Revoke <?= e($c['certificate_no']) ?>?</h3></div>
  <div class="m-b"><div class="alert alert-warning"><?= icon('alert') ?><div>The public verification page will show this certificate as revoked, its sticker is voided and the sub-job returns to the inspector for rework.</div></div>
    <div class="field"><label>Reason</label><textarea class="textarea" name="reason" required></textarea></div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-danger solid">Revoke certificate</button></div>
</form></dialog>
<?php endforeach; ?>
