<?php $today = date('Y-m-d'); $soon = date('Y-m-d', strtotime('+30 days')); ?>
<div class="page-head">
  <div class="ph-text"><h1>Equipment register</h1><div class="sub">All client equipment with last and next inspection dates (updated automatically when QC issues a certificate).</div></div>
  <div class="actions">
    <?php View::render('partials/export', [], null); ?>
    <button class="btn btn-primary" data-modal="eq-new"><?= icon('plus', 'sm') ?> Add equipment</button>
  </div>
</div>
<div class="card">
  <form class="toolbar" method="get" data-autosubmit>
    <div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Description, ID, serial, manufacturer…"></div>
    <select class="select" name="client"><option value="">All clients</option><?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], $client) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
    <select class="select" name="due"><option value="">Any due date</option><?php foreach ([30 => 'Due within 30 days', 90 => 'Due within 90 days'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $due) ?>><?= $l ?></option><?php endforeach; ?></select>
  </form>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Equipment</th><th>Client</th><th>Make / model</th><th>Capacity</th><th>Last inspection</th><th>Next due</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($rows as $e): $n = $e['next_inspection_date']; ?>
      <tr>
        <td data-label="Equipment"><span class="strong"><?= e($e['description']) ?></span><div class="sub mono"><?= e($e['equipment_no']) ?><?= $e['serial_no'] ? ' · S/N ' . e($e['serial_no']) : '' ?></div></td>
        <td data-label="Client"><?= e($e['client_name']) ?></td>
        <td data-label="Make"><?= e(trim($e['manufacturer'] . ' ' . $e['model'])) ?: '—' ?></td>
        <td data-label="Capacity"><?= e($e['capacity'] ?: '—') ?></td>
        <td data-label="Last"><?= e(fmt_date($e['last_inspection_date']) ?: '—') ?></td>
        <td data-label="Next due"><?php if ($n): ?><span class="badge badge-<?= $n < $today ? 'red' : ($n <= $soon ? 'amber' : 'green') ?>"><i></i><?= e(fmt_date($n)) ?></span><?php else: ?>—<?php endif; ?></td>
        <td data-label="Status"><?= badge($e['status']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
  <?php else: View::render('partials/empty', ['icon' => 'truck', 'title' => 'No equipment found', 'text' => ''], null); endif; ?>
</div>
<dialog class="modal" id="eq-new" style="width:min(720px,calc(100vw - 32px))"><form method="post" action="<?= url('admin/equipment') ?>"><?= Csrf::field() ?>
  <div class="m-h"><h3>Add equipment</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-6"><label>Client</label><select class="select" name="client_id" required><option value="">Select…</option><?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], $client) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field col-6"><label>Default inspection type</label><select class="select" name="inspection_type_id"><option value="">—</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field col-6"><label>Description</label><input class="input" name="description" required placeholder="BOOM TRUCK"></div>
    <div class="field col-3"><label>Equipment ID</label><input class="input" name="equipment_no"></div>
    <div class="field col-3"><label>Serial No.</label><input class="input" name="serial_no"></div>
    <div class="field col-4"><label>Manufacturer</label><input class="input" name="manufacturer"></div>
    <div class="field col-4"><label>Model</label><input class="input" name="model"></div>
    <div class="field col-2"><label>Capacity</label><input class="input" name="capacity"></div>
    <div class="field col-2"><label>Year</label><input class="input" name="year_built" inputmode="numeric"></div>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Save</button></div>
</form></dialog>
