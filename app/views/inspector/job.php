<div class="page-head">
  <div class="ph-text"><div class="eyebrow"><?= e($job['client_name']) ?></div><h1 class="row wrap">Job <?= (int) substr($job['job_no'], -4) ?> · <?= e($job['title']) ?> <?= badge($job['status']) ?></h1>
    <div class="sub"><span class="mono"><?= e($job['job_no']) ?></span>
      <?php if ($job['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($job['location']) ?></span><?php endif; ?>
      <span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($job['scheduled_date']) ?: 'Not scheduled') ?></span>
      <?php if ($job['contact_person']): ?><span><?= icon('phone', 'sm') ?> <?= e($job['contact_person']) ?> <?= e($job['client_phone']) ?></span><?php endif; ?></div></div>
</div>
<?php if ($job['notes']): ?><div class="alert alert-info"><?= icon('info') ?><div style="white-space:pre-line"><?= e($job['notes']) ?></div></div><?php endif; ?>
<div class="job-card">
  <div class="jc-h"><div class="jc-t"><h3>Your sub-jobs (<?= count($subs) ?>)</h3></div></div>
  <?php foreach ($subs as $s) View::render('partials/subjob_row', ['s' => $s, 'href' => url('inspector/subjobs/' . $s['id']), 'hideInspector' => true], null); ?>
</div>
