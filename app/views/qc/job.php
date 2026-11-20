<div class="page-head">
  <div class="ph-text">
    <div class="eyebrow">Job · <?= e($job['client_name']) ?></div>
    <h1 class="row wrap"><span class="mono" style="font-size:20px"><?= e($job['job_no']) ?></span> <?= badge($job['status']) ?> <?= badge($job['priority']) ?></h1>
    <div class="sub">
      <span><?= icon('briefcase', 'sm') ?> <?= e($job['title']) ?></span>
      <?php if ($job['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($job['location']) ?></span><?php endif; ?>
      <?php if ($job['scheduled_date']): ?><span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($job['scheduled_date'])) ?></span><?php endif; ?>
      <?php if ($job['lead_name']): ?><span><?= icon('user', 'sm') ?> <?= e($job['lead_name']) ?></span><?php endif; ?>
    </div>
  </div>
  <div class="actions"><a class="btn btn-secondary" href="<?= url('qc/jobs') ?>">&larr; Back to jobs</a></div>
</div>
<div class="card card-b mb-2">
  <div class="progress-label"><span class="strong" style="color:var(--text)">Job progress</span><span><?= $progress['approved'] ?> of <?= $progress['total'] ?> sub-jobs approved · <?= $progress['submitted'] ?> at QC · <?= $progress['active'] ?> in inspection · <?= $progress['unassigned'] ?> unassigned</span></div>
  <div class="progress" style="height:10px"><span style="width:<?= $progress['pct'] ?>%"></span></div>
</div>
<div class="card">
  <div class="card-h"><h2><?= icon('layers', 'sm') ?> Sub-jobs</h2><span class="hint"><?= count($subs) ?> total</span></div>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Sub-job</th><th>Inspection / equipment</th><th>Inspector</th><th>Documents</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($subs as $s): $reviewable = !in_array($s['status'], ['pending', 'assigned', 'cancelled'], true); ?>
      <tr<?= $reviewable ? ' class="row-link" data-href="' . url('qc/review/' . $s['id']) . '"' : '' ?>>
        <td data-label="Sub-job" class="nowrap"><span class="mono strong" style="font-size:14px"><?= (int) substr($job['job_no'], -4) ?>.<?= (int) $s['seq'] ?></span><div class="sub mono" style="font-size:11px"><?= e($s['sub_job_no']) ?></div></td>
        <td data-label="Inspection"><span class="strong"><?= e($s['type_name']) ?></span><div class="sub"><?= e($s['eq_description'] ?: 'Equipment not registered') ?><?= $s['equipment_no'] ? ' · ' . e($s['equipment_no']) : '' ?></div></td>
        <td data-label="Inspector"><?= $s['inspector_name'] ? '<span class="person"><span class="avatar sm">' . e(initials($s['inspector_name'])) . '</span>' . e($s['inspector_name']) . '</span>' : '<span class="muted">Unassigned</span>' ?></td>
        <td data-label="Documents" class="nowrap">
          <?php if ($s['checklist']): ?><a class="pill" href="<?= url('documents/checklist/' . $s['id']) ?>" title="Checklist"><?= icon('clipboard', 'sm') ?> <?= $s['checklist']['status'] === 'completed' ? 'Done' : 'Draft' ?></a><?php endif; ?>
          <?php if ($s['cert']): ?><a class="pill" href="<?= url('documents/certificate/' . $s['id']) ?>" title="<?= e($s['cert']['certificate_no']) ?>"><?= icon('award', 'sm') ?> <?= e(Workflow::label($s['cert']['status'])) ?></a><?php endif; ?>
          <?php if (!$s['checklist'] && !$s['cert']): ?><span class="muted small">Not started</span><?php endif; ?>
        </td>
        <td data-label="Status"><?= badge($s['status']) ?></td>
        <td class="actions"><?php if ($reviewable): ?><a class="btn <?= $s['status'] === 'submitted' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" href="<?= url('qc/review/' . $s['id']) ?>"><?= $s['status'] === 'submitted' ? 'Review' : 'Open' ?></a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
