<div class="page-head"><div class="ph-text"><h1>Jobs & progress</h1><div class="sub">Live status of every inspection job for your company.</div></div></div>
<?php if (!$jobs): ?><div class="card"><?php View::render('partials/empty', ['icon' => 'briefcase', 'title' => 'No jobs yet', 'text' => 'Jobs are opened when we accept your request.'], null); ?></div><?php endif; ?>
<?php foreach ($jobs as $j): $p = $j['progress']; ?>
  <a class="job-card" href="<?= url('portal/jobs/' . $j['id']) ?>" style="display:block;color:inherit;text-decoration:none">
    <div class="jc-h">
      <div class="jc-t"><h3><?= e($j['title']) ?></h3>
        <div class="jc-meta"><span class="mono"><?= e($j['job_no']) ?></span><?php if ($j['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($j['location']) ?></span><?php endif; ?><span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($j['scheduled_date']) ?: 'Scheduling') ?></span></div></div>
      <?= badge($j['status']) ?>
    </div>
    <div style="padding:0 18px 16px"><div class="progress-label"><span><?= $p['approved'] ?> of <?= $p['total'] ?> inspections certified</span><span><?= $p['pct'] ?>%</span></div><div class="progress"><span style="width:<?= $p['pct'] ?>%"></span></div></div>
  </a>
<?php endforeach; ?>
<?php View::render('partials/pager', ['meta' => $meta], null); ?>
