<div class="page-head">
  <div class="ph-text"><h1>Assigned jobs</h1><div class="sub">Jobs and sub-jobs assigned to you.</div></div>
  <div class="seg-tabs"><a class="<?= $filter === 'open' ? 'on' : '' ?>" href="<?= url('inspector/jobs') ?>">Open</a><a class="<?= $filter === 'all' ? 'on' : '' ?>" href="<?= url('inspector/jobs', ['f' => 'all']) ?>">All</a></div>
</div>

<?php if ($myJobs): ?>
  <div class="section-label" style="margin:0 0 8px;font-size:.75rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">Awaiting your sub-jobs</div>
  <?php foreach ($myJobs as $j): ?>
    <div class="job-card" style="border-left:3px solid var(--warning,#f59e0b)">
      <div class="jc-h">
        <div class="jc-t">
          <h3><a href="<?= url('inspector/jobs/' . $j['id']) ?>">Job <?= (int) substr($j['job_no'], -4) ?> · <?= e($j['title']) ?></a></h3>
          <div class="jc-meta">
            <span class="mono"><?= e($j['job_no']) ?></span>
            <span><?= icon('building', 'sm') ?> <?= e($j['client_name']) ?></span>
            <?php if ($j['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($j['location']) ?></span><?php endif; ?>
            <span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($j['scheduled_date']) ?: 'Not scheduled') ?></span>
          </div>
        </div>
        <a class="btn btn-primary btn-sm" href="<?= url('inspector/jobs/' . $j['id'] . '/subjobs/new') ?>"><?= icon('plus', 'sm') ?> Add sub-job</a>
      </div>
      <div class="sj-row" style="padding:10px 16px;color:var(--muted);font-size:.85rem"><?= icon('info', 'sm') ?> No sub-jobs yet — add the first one to begin inspecting.</div>
    </div>
  <?php endforeach; ?>
  <?php if ($jobs): ?><div class="section-label" style="margin:16px 0 8px;font-size:.75rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">In progress</div><?php endif; ?>
<?php endif; ?>

<?php if (!$jobs && !$myJobs): ?>
  <div class="card"><?php View::render('partials/empty', ['icon' => 'briefcase', 'title' => 'No assigned jobs', 'text' => 'When the administrator assigns work to you it appears here.'], null); ?></div>
<?php endif; ?>

<?php foreach ($jobs as $jid => $g): $j = $g['job']; ?>
  <div class="job-card">
    <div class="jc-h">
      <div class="jc-t">
        <h3><a href="<?= url('inspector/jobs/' . $jid) ?>"><?= e($j['job_no']) ?> · <?= e($j['title'] ?? $j['job_title']) ?></a></h3>
        <div class="jc-meta"><span class="mono"><?= e($j['job_no']) ?></span><span><?= icon('building', 'sm') ?> <?= e($j['client_name']) ?></span>
          <?php if ($j['location'] ?? ''): ?><span><?= icon('pin', 'sm') ?> <?= e($j['location']) ?></span><?php endif; ?>
          <span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($j['scheduled_date'] ?? $j['job_scheduled_date'] ?? null) ?: 'Not scheduled') ?></span></div>
      </div>
      <?= badge($j['priority']) ?>
    </div>
    <?php foreach ($g['subs'] as $s) View::render('partials/subjob_row', ['s' => $s, 'href' => url('inspector/subjobs/' . $s['id']), 'hideInspector' => true], null); ?>
  </div>
<?php endforeach; ?>
