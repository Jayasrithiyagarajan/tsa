<?php
/** Stickers linked to projects (jobs). Closed = locked to the job; void = removed by an admin after linking. */
$f = array_filter(['inspector' => $insp ?: null, 'client' => $client ?: null, 'q' => $q !== '' ? $q : null]);
$tab = fn ($key, $val, $label, $n) => '<a class="' . (($key === 'status' ? $status : $result) === $val && ($key === 'status' ? $result === '' : $status === '') ? 'on' : '') . '" href="'
    . url('admin/project-stickers', $f + array_filter([$key => $val])) . '">' . e($label) . '<span class="n">' . (int) $n . '</span></a>';
$swatch = fn ($c) => '<i style="display:inline-block;width:10px;height:10px;border-radius:3px;flex:none;background:' . ($c === 'red' ? 'var(--red)' : '#1d4fb8') . '"></i>';
?>
<div class="page-head">
  <div class="ph-text"><h1>Project stickers</h1><div class="sub">Stickers linked to jobs. Linking closes a sticker — it stays locked to that job and equipment. Each one has a digital sticker. Bulk stock is on <a href="<?= url('admin/stickers') ?>">Sticker Stock</a>.</div></div>
  <div class="actions"><?php View::render('partials/export', [], null); ?></div>
</div>

<div class="kpis">
  <a class="kpi" href="<?= url('admin/project-stickers', ['status' => 'closed']) ?>"><span class="k-ic t-blue"><?= icon('lock') ?></span><span><div class="k-v"><?= (int) $counts['closed'] ?></div><div class="k-l">Linked & closed</div></span></a>
  <a class="kpi" href="<?= url('admin/project-stickers', ['result' => 'accepted']) ?>"><span class="k-ic t-blue"><?= icon('check') ?></span><span><div class="k-v"><?= (int) $counts['accepted'] ?></div><div class="k-l">Blue · Accepted</div></span></a>
  <a class="kpi" href="<?= url('admin/project-stickers', ['result' => 'failed']) ?>"><span class="k-ic t-red"><?= icon('alert') ?></span><span><div class="k-v"><?= (int) $counts['failed'] ?></div><div class="k-l">Red · Failed</div></span></a>
  <a class="kpi" href="<?= url('admin/project-stickers', ['result' => 'pending']) ?>"><span class="k-ic t-amber"><?= icon('clock') ?></span><span><div class="k-v"><?= (int) $counts['pending'] ?></div><div class="k-l">Job not yet approved</div></span></a>
</div>

<div class="card">
  <div class="toolbar">
    <div class="seg-tabs">
      <a class="<?= $status === '' && $result === '' ? 'on' : '' ?>" href="<?= url('admin/project-stickers', $f) ?>">All<span class="n"><?= (int) $counts['closed'] + (int) $counts['void'] ?></span></a>
      <?= $tab('status', 'closed', 'Closed', $counts['closed']) ?>
      <?= $tab('result', 'pending', 'Awaiting QC', $counts['pending']) ?>
      <?= $tab('status', 'void', 'Void', $counts['void']) ?>
    </div>
    <span class="grow"></span>
    <form class="row wrap" method="get" style="gap:8px" data-autosubmit>
      <input type="hidden" name="status" value="<?= e($status) ?>"><input type="hidden" name="result" value="<?= e($result) ?>">
      <select class="select" name="client" style="width:auto"><option value="">All clients</option>
        <?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $client === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
      <select class="select" name="inspector" style="width:auto"><option value="">All inspectors</option>
        <?php foreach ($inspectors as $i): ?><option value="<?= (int) $i['id'] ?>" <?= $insp === (int) $i['id'] ? 'selected' : '' ?>><?= e($i['name']) ?></option><?php endforeach; ?></select>
      <div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Sticker, job, certificate, equipment…"></div>
    </form>
  </div>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Sticker</th><th>Project / job</th><th>Client</th><th>Equipment</th><th>Inspector</th><th>Linked</th><th>Certificate</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $s): ?>
      <tr>
        <td data-label="Sticker"><span class="row" style="gap:8px"><?= $swatch($s['color']) ?><span class="mono strong nowrap"><?= e($s['sticker_no']) ?></span></span><div class="sub"><?= $s['color'] === 'red' ? 'Failed' : 'Accepted' ?></div></td>
        <td data-label="Project"><a class="mono" href="<?= url('admin/jobs/' . $s['job_id']) ?>"><?= e($s['sub_job_no']) ?></a><div class="sub"><?= e($s['job_title'] ?: $s['type_name']) ?></div></td>
        <td data-label="Client"><?= e($s['client_name']) ?></td>
        <td data-label="Equipment"><?= e($s['eq_description'] ?: $s['type_name']) ?><div class="sub"><?= e($s['equipment_no']) ?></div></td>
        <td data-label="Inspector" class="small"><?= e($s['inspector_name'] ?: '—') ?></td>
        <td data-label="Linked" class="small nowrap"><?= e(fmt_datetime($s['linked_at'])) ?></td>
        <td data-label="Certificate" class="mono small"><?= e($s['certificate_no'] ?: '—') ?><?= $s['expiry_date'] ? '<div class="sub">until ' . e(fmt_date($s['expiry_date'])) . '</div>' : '' ?></td>
        <td data-label="Status"><?= badge($s['status']) ?> <?= badge($s['sj_status']) ?><?= $s['void_reason'] ? '<div class="sub">' . e($s['void_reason']) . '</div>' : '' ?></td>
        <td class="actions">
          <?php if ($s['status'] === 'closed'): ?>
            <a class="btn btn-ghost btn-sm" href="<?= url('documents/sticker/' . $s['id']) ?>" title="Digital sticker"><?= icon('eye', 'sm') ?></a>
<?php if (Auth::isSuper()): ?><button type="button" class="btn btn-ghost btn-sm" style="color:var(--red)" title="Void" data-void="<?= url('admin/stickers/' . $s['id'] . '/void') ?>" data-no="<?= e($s['sticker_no']) ?>"><?= icon('x', 'sm') ?></button><?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'tag', 'title' => 'No stickers linked to projects', 'text' => 'Inspectors link stickers from the job\'s Sticker tab.'], null); endif; ?>
</div>
<form method="post" id="void-form"><?= Csrf::field() ?><input type="hidden" name="reason"></form>
<script>
document.addEventListener('click', function (e) {
  var v = e.target.closest('[data-void]'); if (!v) return;
  var r = prompt('Reason for voiding ' + v.dataset.no + '? It is linked to a job; once void, the inspector can link a new sticker if the job is still open.'); if (!r) return;
  var f = document.getElementById('void-form'); f.action = v.dataset.void; f.reason.value = r; f.submit();
});
</script>
