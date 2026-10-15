<?php
$label = ['pending' => 'Scheduling', 'assigned' => 'Inspector assigned', 'in_progress' => 'Inspection in progress', 'rework' => 'Inspection in progress',
          'submitted' => 'Quality review', 'approved' => 'Certified', 'cancelled' => 'Cancelled'];
$tone = ['pending' => 'amber', 'assigned' => 'blue', 'in_progress' => 'indigo', 'rework' => 'indigo', 'submitted' => 'violet', 'approved' => 'green', 'cancelled' => 'gray'];
?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow mono"><?= e($job['job_no']) ?></div><h1 class="row wrap"><?= e($job['title']) ?> <?= badge($job['status']) ?></h1>
    <div class="sub"><?php if ($job['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($job['location']) ?></span><?php endif; ?><span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($job['scheduled_date']) ?: 'Scheduling') ?></span>
      <?php if ($job['lead_name']): ?><span><?= icon('user', 'sm') ?> Lead inspector: <?= e($job['lead_name']) ?></span><?php endif; ?><?php if ($job['po_no']): ?><span>P.O. <?= e($job['po_no']) ?></span><?php endif; ?></div></div>
</div>
<div class="card card-b mb-2"><div class="progress-label"><span class="strong" style="color:var(--text)">Progress</span><span><?= $progress['approved'] ?> of <?= $progress['total'] ?> certified · <?= $progress['submitted'] ?> in quality review</span></div><div class="progress" style="height:10px"><span style="width:<?= $progress['pct'] ?>%"></span></div></div>
<div class="card">
  <div class="card-h"><h2><?= icon('layers', 'sm') ?> Inspections</h2></div>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>No.</th><th>Inspection</th><th>Equipment</th><th>Status</th><th>Certificate</th><th>Valid until</th><th></th></tr></thead>
    <tbody><?php foreach ($subs as $s): $ok = $s['status'] === 'approved' && $s['cert'] && in_array($s['cert']['status'], ['issued', 'revoked'], true);
      $reportOnly = $s['status'] === 'approved' && !$s['requires_certificate'] && $s['requires_checklist']; ?>
      <tr>
        <td data-label="No." class="mono strong"><?= (int) substr($job['job_no'], -4) ?>.<?= (int) $s['seq'] ?></td>
        <td data-label="Inspection"><?= e($s['type_name']) ?></td>
        <td data-label="Equipment"><?= e($s['eq_description'] ?: '—') ?><div class="sub"><?= e($s['equipment_no']) ?></div></td>
        <td data-label="Status"><span class="badge badge-<?= $tone[$s['status']] ?>"><i></i><?= e($label[$s['status']]) ?></span><?= $ok && $s['result'] ? ' ' . badge($s['result']) : '' ?></td>
        <td data-label="Certificate" class="mono small"><?= $ok ? e($s['cert']['certificate_no']) : ($reportOnly ? e(Numbering::certificate($s)) . ' <span class="muted">(report)</span>' : '<span class="muted">After QC approval</span>') ?></td>
        <td data-label="Valid until"><?= $ok ? e(fmt_date($s['cert']['next_inspection_date'])) : '—' ?></td>
        <td class="actions"><?php if ($reportOnly): ?>
          <a class="btn btn-ghost btn-sm" href="<?= url('documents/checklist/' . $s['id']) ?>" title="View"><?= icon('eye', 'sm') ?></a>
          <a class="btn btn-secondary btn-sm" href="<?= url('documents/checklist/' . $s['id'], ['download' => 1]) ?>"><?= icon('download', 'sm') ?> Report PDF</a>
        <?php endif; ?><?php if ($ok): ?>
          <a class="btn btn-ghost btn-sm" href="<?= url('documents/certificate/' . $s['id']) ?>" title="View"><?= icon('eye', 'sm') ?></a>
          <a class="btn btn-secondary btn-sm" href="<?= url('documents/certificate/' . $s['id'], ['download' => 1]) ?>"><?= icon('download', 'sm') ?> PDF</a>
          <?php if ($s['requires_checklist']): ?><a class="btn btn-ghost btn-sm" href="<?= url('documents/checklist/' . $s['id']) ?>" title="Checklist"><?= icon('clipboard', 'sm') ?></a><?php endif; ?>
        <?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div>
