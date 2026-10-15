<?php $tab = fn ($s, $label) => '<a class="' . ($status === $s ? 'on' : '') . '" href="' . url('admin/stickers', ['status' => $s]) . '">' . e($label) . '<span class="n">' . (int) ($s ? ($counts[$s] ?? 0) : array_sum($counts)) . '</span></a>'; ?>
<div class="page-head">
  <div class="ph-text"><h1>Stickers</h1><div class="sub">Serialized inspection stickers: stock → reserved by inspector on site → issued with the certificate (or void).</div></div>
  <div class="actions">
    <?php View::render('partials/export', [], null); ?>
    <button class="btn btn-primary" data-modal="batch"><?= icon('plus', 'sm') ?> Add sticker batch</button>
  </div>
</div>
<div class="kpis">
  <div class="kpi"><span class="k-ic t-gray"><?= icon('layers') ?></span><span><div class="k-v"><?= (int) ($counts['in_stock'] ?? 0) ?></div><div class="k-l">In stock</div></span></div>
  <div class="kpi"><span class="k-ic t-amber"><?= icon('clock') ?></span><span><div class="k-v"><?= (int) ($counts['reserved'] ?? 0) ?></div><div class="k-l">Reserved (awaiting QC)</div></span></div>
  <div class="kpi"><span class="k-ic t-green"><?= icon('tag') ?></span><span><div class="k-v"><?= (int) ($counts['issued'] ?? 0) ?></div><div class="k-l">Issued</div></span></div>
  <div class="kpi"><span class="k-ic t-red"><?= icon('x') ?></span><span><div class="k-v"><?= (int) ($counts['void'] ?? 0) ?></div><div class="k-l">Void</div></span></div>
</div>
<div class="card">
  <div class="toolbar">
    <div class="seg-tabs"><?= $tab('', 'All') ?><?= $tab('reserved', 'Reserved') ?><?= $tab('issued', 'Issued') ?><?= $tab('in_stock', 'In stock') ?><?= $tab('void', 'Void') ?></div>
    <span class="grow"></span>
    <form class="search" method="get"><input type="hidden" name="status" value="<?= e($status) ?>"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Sticker, batch, certificate, equipment…"></form>
  </div>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Sticker</th><th>Batch</th><th>Linked to</th><th>Certificate</th><th>Inspector</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $s): ?>
      <tr>
        <td data-label="Sticker"><span class="row" style="gap:8px"><i style="width:10px;height:10px;border-radius:3px;background:<?= $s['color'] === 'red' ? 'var(--red)' : 'var(--green)' ?>"></i><span class="mono strong"><?= e($s['sticker_no']) ?></span></span></td>
        <td data-label="Batch" class="sub"><?= e($s['batch_no']) ?></td>
        <td data-label="Linked"><?php if ($s['sub_job_no']): ?><a href="<?= url('admin/jobs/' . $s['job_id']) ?>" class="mono"><?= e($s['sub_job_no']) ?></a><div class="sub"><?= e($s['client_name']) ?> · <?= e($s['eq_description']) ?> <?= e($s['equipment_no']) ?></div><?php else: ?><span class="muted">—</span><?php endif; ?></td>
        <td data-label="Certificate" class="mono small"><?= e($s['certificate_no'] ?: '—') ?></td>
        <td data-label="Inspector" class="small"><?= e($s['inspector_name'] ?: '—') ?></td>
        <td data-label="Expiry" class="nowrap"><?= e(fmt_date($s['expiry_date']) ?: '—') ?></td>
        <td data-label="Status"><?= badge($s['status']) ?><?= $s['void_reason'] ? '<div class="sub">' . e($s['void_reason']) . '</div>' : '' ?></td>
        <td class="actions">
          <?php if ($s['status'] === 'issued'): ?><a class="btn btn-ghost btn-sm" href="<?= url('documents/sticker/' . $s['id']) ?>" title="Print"><?= icon('printer', 'sm') ?></a><?php endif; ?>
          <?php if (Workflow::can('sticker', $s['status'], 'void')): ?>
            <form method="post" action="<?= url('admin/stickers/' . $s['id'] . '/void') ?>" style="display:inline" onsubmit="const r=prompt('Reason for voiding <?= e($s['sticker_no']) ?>:');if(!r)return false;this.reason.value=r;">
              <?= Csrf::field() ?><input type="hidden" name="reason"><button class="btn btn-ghost btn-sm" style="color:var(--red)" title="Void"><?= icon('x', 'sm') ?></button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
</div>
<dialog class="modal" id="batch"><form method="post" action="<?= url('admin/stickers') ?>"><?= Csrf::field() ?>
  <div class="m-h"><h3>Add sticker batch</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-4"><label>Prefix</label><input class="input mono" name="prefix" value="TE-" required></div>
    <div class="field col-4"><label>From No.</label><input class="input" type="number" min="1" name="from" required></div>
    <div class="field col-4"><label>To No.</label><input class="input" type="number" min="1" name="to" required></div>
    <div class="field col-6"><label>Colour</label><select class="select" name="color"><option value="green">Green — passed</option><option value="red">Red — rejected / do not use</option></select></div>
    <div class="field col-6"><label>Batch No.</label><input class="input" name="batch_no" placeholder="B-2026-02"></div>
    <p class="col-12 small muted">Numbers are zero-padded to 6 digits, e.g. TE-001151.</p>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Add to stock</button></div>
</form></dialog>
