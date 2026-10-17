<div class="page-head">
  <div class="ph-text"><div class="eyebrow"><?= e($job['client_name']) ?></div><h1 class="row wrap">Job <?= (int) substr($job['job_no'], -4) ?> · <?= e($job['title']) ?> <?= badge($job['status']) ?></h1>
    <div class="sub"><span class="mono"><?= e($job['job_no']) ?></span>
      <?php if ($job['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($job['location']) ?></span><?php endif; ?>
      <span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($job['scheduled_date']) ?: 'Not scheduled') ?></span>
      <?php if ($job['contact_person']): ?><span><?= icon('phone', 'sm') ?> <?= e($job['contact_person']) ?> <?= e($job['client_phone']) ?></span><?php endif; ?></div></div>
  <?php if (!in_array($job['status'], ['completed', 'cancelled'])): ?>
  <div class="ph-actions">
    <a class="btn btn-primary btn-sm" href="<?= url('inspector/jobs/' . $job['id'] . '/subjobs/new') ?>"><?= icon('plus', 'sm') ?> Add sub-job</a>
  </div>
  <?php endif; ?>
</div>
<?php if ($job['notes']): ?><div class="alert alert-info"><?= icon('info') ?><div style="white-space:pre-line"><?= e($job['notes']) ?></div></div><?php endif; ?>
<div class="job-card">
  <div class="jc-h"><div class="jc-t"><h3>Your sub-jobs (<?= count($subs) ?>)</h3></div></div>
  <?php if ($subs): ?>
    <?php foreach ($subs as $s) View::render('partials/subjob_row', ['s' => $s, 'href' => url('inspector/subjobs/' . $s['id']), 'hideInspector' => true], null); ?>
  <?php else: ?>
    <div class="empty-state" style="padding:32px 24px;text-align:center">
      <?= icon('layers', 'lg') ?>
      <p class="muted mt-1">No sub-jobs yet. Add one to begin the inspection.</p>
      <?php if (!in_array($job['status'], ['completed', 'cancelled'])): ?>
        <a class="btn btn-primary mt-1" href="<?= url('inspector/jobs/' . $job['id'] . '/subjobs/new') ?>"><?= icon('plus', 'sm') ?> Add first sub-job</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
