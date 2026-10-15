<?php $u = Auth::user(); ?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow"><?= e(date('l, d F')) ?></div><h1>Hello, <?= e(explode(' ', $u['name'])[0]) ?></h1>
    <div class="sub"><?= e($profile['qualification'] ?? 'Inspector') ?><?= !empty($profile['base_location']) ? ' · ' . e($profile['base_location']) : '' ?></div></div>
  <div class="actions"><a class="btn btn-primary" href="<?= url('inspector/jobs') ?>"><?= icon('briefcase', 'sm') ?> All assigned jobs</a></div>
</div>
<?php if (!empty($profile['cert_expiry']) && $profile['cert_expiry'] < date('Y-m-d', strtotime('+60 days'))): ?>
  <div class="alert alert-warning"><?= icon('alert') ?><div>Your qualification expires on <b><?= e(fmt_date($profile['cert_expiry'])) ?></b>. Contact the administrator to renew it.</div></div>
<?php endif; ?>
<div class="kpis">
  <div class="kpi"><span class="k-ic t-red"><?= icon('rotate') ?></span><span><div class="k-v"><?= (int) $k['rework'] ?></div><div class="k-l">Returned for rework</div></span></div>
  <div class="kpi"><span class="k-ic t-indigo"><?= icon('clipboard') ?></span><span><div class="k-v"><?= (int) $k['in_progress'] ?></div><div class="k-l">In progress</div></span></div>
  <div class="kpi"><span class="k-ic t-blue"><?= icon('briefcase') ?></span><span><div class="k-v"><?= (int) $k['assigned'] ?></div><div class="k-l">Assigned, not started</div></span></div>
  <div class="kpi"><span class="k-ic t-violet"><?= icon('shield') ?></span><span><div class="k-v"><?= (int) $k['submitted'] ?></div><div class="k-l">Waiting for QC</div></span></div>
  <div class="kpi"><span class="k-ic t-green"><?= icon('award') ?></span><span><div class="k-v"><?= (int) $k['approved_month'] ?></div><div class="k-l">Approved this month</div></span></div>
</div>
<div class="grid grid-main">
  <div class="card">
    <div class="card-h"><h2><?= icon('clipboard', 'sm') ?> Up next</h2><span class="hint">rework first, then in-progress, then scheduled</span></div>
    <?php if ($queue): foreach ($queue as $s): ?>
      <?php View::render('partials/subjob_row', ['s' => $s, 'href' => url('inspector/subjobs/' . $s['id']), 'hideInspector' => true], null); ?>
      <?php if (!empty($reworkNotes[$s['id']])): ?><div style="padding:0 18px 12px 100px" class="small"><span class="badge badge-red"><i></i>QC</span> <?= e(mb_strimwidth($reworkNotes[$s['id']]['comments'], 0, 160, '…')) ?></div><?php endif; ?>
    <?php endforeach; else: View::render('partials/empty', ['icon' => 'check', 'title' => 'No open inspections', 'text' => 'New assignments will appear here and in your notifications.'], null); endif; ?>
  </div>
  <div class="stack">
    <div class="card"><div class="card-h"><h2><?= icon('history', 'sm') ?> Recently submitted</h2></div>
      <?php foreach ($recent as $s): ?>
        <?php View::render('partials/subjob_row', ['s' => $s, 'href' => url('inspector/subjobs/' . $s['id']), 'hideInspector' => true], null); ?>
      <?php endforeach; ?>
      <?php if (!$recent): ?><div class="card-b muted small">Nothing yet.</div><?php endif; ?>
    </div>
    <div class="card"><div class="card-h"><h2><?= icon('info', 'sm') ?> Workflow</h2></div><div class="card-b small muted">
      Start the inspection on site → complete every checklist item (FAIL needs a remark) → fill the certificate data and result → add photos → record the sticker → submit to QC. You cannot edit while QC is reviewing; returned items appear at the top.</div></div>
  </div>
</div>
