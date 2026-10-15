<div class="page-head">
  <div class="ph-text"><h1>Assigned jobs</h1><div class="sub">Main jobs with the sub-jobs assigned to you.</div></div>
  <div class="seg-tabs"><a class="<?= $filter === 'open' ? 'on' : '' ?>" href="<?= url('inspector/jobs') ?>">Open</a><a class="<?= $filter === 'all' ? 'on' : '' ?>" href="<?= url('inspector/jobs', ['f' => 'all']) ?>">All</a></div>
</div>
<?php if (!$jobs): ?>
  <div class="card"><?php View::render('partials/empty', ['icon' => 'briefcase', 'title' => 'No assigned jobs', 'text' => 'When the administrator assigns work to you it appears here.'], null); ?></div>
<?php endif; ?>
<?php foreach ($jobs as $jid => $g): $j = $g['job']; ?>
  <div class="job-card">
    <div class="jc-h">
      <div class="jc-t">
        <h3><a href="<?= url('inspector/jobs/' . $jid) ?>">Job <?= (int) substr($j['job_no'], -4) ?> · <?= e($j['job_title']) ?></a></h3>
        <div class="jc-meta"><span class="mono"><?= e($j['job_no']) ?></span><span><?= icon('building', 'sm') ?> <?= e($j['client_name']) ?></span>
          <?php if ($j['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($j['location']) ?></span><?php endif; ?>
          <span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($j['job_scheduled_date']) ?: 'Not scheduled') ?></span></div>
      </div>
      <?= badge($j['priority']) ?>
    </div>
    <?php foreach ($g['subs'] as $s) View::render('partials/subjob_row', ['s' => $s, 'href' => url('inspector/subjobs/' . $s['id']), 'hideInspector' => true], null); ?>
  </div>
<?php endforeach; ?>
