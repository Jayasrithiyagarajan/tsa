<div class="page-head">
  <div class="ph-text"><h1>Inspection types & templates</h1><div class="sub">Each inspection type has its own controlled checklist and certificate template. Templates are versioned — documents already filled keep the version they were created with.</div></div>
  <div class="actions"><button class="btn btn-primary" data-modal="type-new"><?= icon('plus', 'sm') ?> New inspection type</button></div>
</div>
<div class="grid grid-3">
<?php foreach ($types as $t): ?>
  <div class="card">
    <div class="card-h"><h2><span class="pill"><?= e($t['code']) ?></span> <?= e($t['name']) ?></h2><?= badge($t['status']) ?></div>
    <div class="card-b stack">
      <dl class="kv small" style="grid-template-columns:110px 1fr">
        <dt>Category</dt><dd><?= e($t['category']) ?></dd>
        <dt>Procedure</dt><dd><?= e($t['procedure_ref'] ?: '—') ?></dd>
        <dt>Standard</dt><dd><?= e($t['standard_ref'] ?: '—') ?></dd>
        <dt>Validity</dt><dd><?= (int) $t['validity_months'] ?> months</dd>
        <dt>Requires</dt><dd><?= implode(' · ', array_filter([$t['requires_checklist'] ? 'Checklist' : null, $t['requires_certificate'] ? 'Certificate' : null, $t['requires_sticker'] ? 'Sticker' : null])) ?: '—' ?></dd>
      </dl>
      <div class="row between" style="padding:10px 12px;border:1px solid var(--border);border-radius:10px">
        <div><div class="small strong"><?= icon('clipboard', 'sm') ?> Checklist</div><div class="small muted"><?= $t['cl_id'] ? e($t['cl_label']) . ' · ' . (int) $t['cl_items'] . ' items' : ($t['requires_checklist'] ? 'Not configured' : 'Not required') ?></div></div>
        <div class="row"><?php if ($t['cl_id']): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/templates/preview/checklist/' . $t['cl_id']) ?>" title="Preview"><?= icon('eye', 'sm') ?></a><?php endif; ?>
          <a class="btn btn-secondary btn-sm" href="<?= url('admin/templates/checklist/' . $t['id']) ?>"><?= $t['cl_id'] ? 'Edit' : 'Create' ?></a></div>
      </div>
      <div class="row between" style="padding:10px 12px;border:1px solid var(--border);border-radius:10px">
        <div><div class="small strong"><?= icon('award', 'sm') ?> Certificate</div><div class="small muted"><?= $t['ct_id'] ? e($t['ct_label']) : 'Not configured — inspectors upload certificates' ?></div></div>
        <div class="row"><?php if ($t['ct_id']): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/templates/preview/certificate/' . $t['ct_id']) ?>" title="Preview"><?= icon('eye', 'sm') ?></a><?php endif; ?>
          <a class="btn btn-secondary btn-sm" href="<?= url('admin/templates/certificate/' . $t['id']) ?>"><?= $t['ct_id'] ? 'Edit' : 'Create' ?></a></div>
      </div>
    </div>
    <div class="card-f"><span class="small muted" style="flex:1">Used in <?= (int) $t['used'] ?> sub-jobs</span><button class="btn btn-ghost btn-sm" data-modal="type-<?= $t['id'] ?>"><?= icon('settings', 'sm') ?> Settings</button></div>
  </div>
<?php endforeach; ?>
</div>
<?php foreach (array_merge([null], $types) as $t): $mid = $t ? 'type-' . $t['id'] : 'type-new'; ?>
<dialog class="modal" id="<?= $mid ?>" style="width:min(640px,calc(100vw - 32px))"><form method="post" action="<?= url('admin/types') ?>"><?= Csrf::field() ?>
  <input type="hidden" name="id" value="<?= (int) ($t['id'] ?? 0) ?>">
  <div class="m-h"><h3><?= $t ? 'Edit ' . e($t['name']) : 'New inspection type' ?></h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-4"><label>Code</label><input class="input mono" name="code" value="<?= e($t['code'] ?? '') ?>" required></div>
    <div class="field col-8"><label>Name</label><input class="input" name="name" value="<?= e($t['name'] ?? '') ?>" required></div>
    <div class="field col-6"><label>Category</label><input class="input" name="category" value="<?= e($t['category'] ?? '') ?>" list="cats" required><datalist id="cats"><option>Lifting Equipment</option><option>NDT</option><option>Pressure Equipment</option><option>Heavy Equipment</option></datalist></div>
    <div class="field col-6"><label>Certificate validity (months)</label><input class="input" type="number" min="1" max="60" name="validity_months" value="<?= (int) ($t['validity_months'] ?? 12) ?>" required></div>
    <div class="field col-6"><label>Applicable procedure</label><input class="input" name="procedure_ref" value="<?= e($t['procedure_ref'] ?? '') ?>"></div>
    <div class="field col-6"><label>Relevant standard</label><input class="input" name="standard_ref" value="<?= e($t['standard_ref'] ?? '') ?>"></div>
    <div class="col-12 row wrap" style="gap:8px 20px">
      <label class="check"><input type="checkbox" name="requires_checklist" value="1"<?= checked((bool) ($t['requires_checklist'] ?? 1)) ?>> Checklist required</label>
      <label class="check"><input type="checkbox" name="requires_certificate" value="1"<?= checked((bool) ($t['requires_certificate'] ?? 1)) ?>> Certificate required</label>
      <label class="check"><input type="checkbox" name="requires_sticker" value="1"<?= checked((bool) ($t['requires_sticker'] ?? 1)) ?>> Sticker required</label>
    </div>
    <div class="field col-4"><label>Status</label><select class="select" name="status"><?php foreach (['active', 'inactive'] as $s): ?><option value="<?= $s ?>"<?= selected($s, $t['status'] ?? 'active') ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Save</button></div>
</form></dialog>
<?php endforeach; ?>
