<div class="page-head">
  <div class="ph-text"><h1>Assignment board</h1><div class="sub">Unassigned sub-jobs by priority and date, with qualified inspectors and their current workload.</div></div>
</div>
<div class="grid grid-main">
  <div class="stack">
    <?php if (!$byJob): ?>
      <div class="card"><?php View::render('partials/empty', ['icon' => 'check', 'title' => 'Nothing to assign', 'text' => 'Every open sub-job has an inspector.'], null); ?></div>
    <?php endif; ?>
    <?php foreach ($byJob as $jobId => $list): $j = $list[0]; ?>
      <form class="job-card" method="post" action="<?= url('admin/jobs/' . $jobId . '/assign') ?>" data-qualified-filter>
        <?= Csrf::field() ?>
        <div class="jc-h">
          <div class="jc-t">
            <h3><a href="<?= url('admin/jobs/' . $jobId) ?>" class="mono"><?= e($j['job_no']) ?></a> · <?= e($j['job_title']) ?></h3>
            <div class="jc-meta"><span><?= icon('building', 'sm') ?> <?= e($j['client_name']) ?></span>
              <?php if ($j['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($j['location']) ?></span><?php endif; ?>
              <span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($j['job_scheduled_date']) ?: 'Not scheduled') ?></span></div>
          </div>
          <?= badge($j['priority']) ?>
        </div>
        <?php foreach ($list as $s): ?>
          <label class="sub-row" style="cursor:pointer">
            <span class="row"><input type="checkbox" name="sub_job_ids[]" value="<?= $s['id'] ?>" data-type="<?= e($s['type_code']) ?>" checked> <span class="sr-no"><?= (int) substr($j['job_no'], -4) ?>.<?= (int) $s['seq'] ?></span></span>
            <span class="sr-t"><b><?= e($s['type_name']) ?></b><span><?= e($s['eq_description'] ?: 'Equipment TBC') ?><?= $s['equipment_no'] ? ' · ' . e($s['equipment_no']) : '' ?></span></span>
            <span class="pill"><?= e($s['type_code']) ?></span><span></span>
          </label>
        <?php endforeach; ?>
        <div class="card-f" style="justify-content:flex-start">
          <select class="select" name="inspector_id" required style="width:auto;min-width:260px;flex:1">
            <option value="">Choose inspector…</option>
            <?php foreach ($inspectors as $u): ?>
              <option value="<?= $u['id'] ?>" data-skills="<?= e(implode(',', $skills[$u['id']] ?? [])) ?>" data-label="<?= e($u['name'] . ' · ' . $u['open_load'] . ' open · ' . ($u['base_location'] ?: '')) ?>"><?= e($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary"><?= icon('users', 'sm') ?> Assign <span data-selected-count></span></button>
        </div>
      </form>
    <?php endforeach; ?>
    <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  </div>
  <div class="stack" style="position:sticky;top:76px">
    <div class="card"><div class="card-h"><h2><?= icon('users', 'sm') ?> Inspectors</h2></div>
      <div class="card-b flush"><table class="t"><thead><tr><th>Inspector</th><th>Qualified</th><th class="num">Open</th></tr></thead><tbody>
      <?php foreach ($inspectors as $u): $expiring = $u['cert_expiry'] && $u['cert_expiry'] < date('Y-m-d', strtotime('+60 days')); ?>
        <tr><td><span class="strong"><?= e($u['name']) ?></span><div class="sub"><?= e($u['qualification']) ?><?= $u['base_location'] ? ' · ' . e($u['base_location']) : '' ?></div>
          <?php if ($expiring): ?><span class="badge badge-red"><i></i>Qualification expires <?= e(fmt_date($u['cert_expiry'])) ?></span><?php endif; ?></td>
          <td class="small"><?= e($u['skills'] ?: '—') ?></td><td class="num"><span class="pill"><?= (int) $u['open_load'] ?></span></td></tr>
      <?php endforeach; ?>
      </tbody></table></div></div>
  </div>
</div>
