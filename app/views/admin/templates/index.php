<div class="page-head">
  <div class="ph-text"><h1>Inspection types & templates</h1><div class="sub">Checklists and certificates are kept in separate folders. Templates are versioned — documents already filled keep the version they were created with.</div></div>
  <div class="actions"><button class="btn btn-primary" data-modal="type-new"><?= icon('plus', 'sm') ?> New inspection type</button></div>
</div>
<style>
.tpl-folder{border:1px solid var(--border);border-radius:12px;background:var(--surface,transparent);margin-bottom:16px;overflow:hidden}
.tpl-folder>summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:12px;padding:14px 16px;user-select:none}
.tpl-folder>summary::-webkit-details-marker{display:none}
.tpl-folder>summary .chev{transition:transform .15s;display:inline-block}
.tpl-folder[open]>summary .chev{transform:rotate(90deg)}
.tpl-folder>summary h2{margin:0;font-size:16px}
.tpl-folder>summary .count{margin-left:auto}
.tpl-cat{padding:8px 16px;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;border-top:1px solid var(--border);opacity:.7}
.tpl-row{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,2fr) auto;gap:12px;align-items:center;padding:10px 16px;border-top:1px solid var(--border)}
.tpl-row .nm{display:flex;gap:8px;align-items:center;min-width:0}
.tpl-row .nm span.t{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tpl-row .acts{display:flex;gap:6px;align-items:center}
@media (max-width:720px){.tpl-row{grid-template-columns:1fr}.tpl-row .acts{justify-content:flex-start}}
</style>
<?php
$folders = [
  'checklist' => ['label' => 'Checklists', 'icon' => 'clipboard', 'sub' => 'Field checklists completed by inspectors', 'req' => 'requires_checklist', 'id' => 'cl_id', 'lbl' => 'cl_label', 'empty' => 'Not configured'],
  'certificate' => ['label' => 'Certificates', 'icon' => 'award', 'sub' => 'Certificate and report layouts for types without a checklist (NDT, lifts, scaffolding, thorough examination)', 'req' => 'requires_certificate', 'id' => 'ct_id', 'lbl' => 'ct_label', 'empty' => 'Not configured — inspectors upload certificates'],
];
foreach ($folders as $kind => $f):
  $list = array_values(array_filter($types, fn($t) => $kind === 'checklist' ? ($t['requires_checklist'] || $t['cl_id']) : (!$t['requires_checklist'] && ($t['requires_certificate'] || $t['ct_id'])))); // equipment types with a checklist live only in Checklists
  $done = count(array_filter($list, fn($t) => $t[$f['id']]));
  $lastCat = null; ?>
<details class="tpl-folder" id="folder-<?= $kind ?>" open>
  <summary><span class="chev"><?= icon('chevron-right', 'sm') ?></span><?= icon($f['icon'], 'sm') ?>
    <div><h2><?= e($f['label']) ?></h2><div class="small muted"><?= e($f['sub']) ?></div></div>
    <span class="pill count"><?= $done ?> of <?= count($list) ?> configured</span></summary>
<?php if (!$list): ?><div class="tpl-row small muted">No inspection types need a <?= strtolower(e($f['label'])) ?> template.</div><?php endif; ?>
<?php foreach ($list as $t): ?>
<?php if ($t['category'] !== $lastCat): $lastCat = $t['category']; ?><div class="tpl-cat"><?= e($lastCat) ?></div><?php endif; ?>
  <div class="tpl-row">
    <div class="nm"><span class="pill"><?= e($t['code']) ?></span><span class="t strong" title="<?= e($t['name']) ?>"><?= e($t['name']) ?></span><?= badge($t['status']) ?></div>
    <div class="small muted"><?php if ($t[$f['id']]): ?><?= e($t[$f['lbl']]) ?><?= $kind === 'checklist' ? ' · ' . (int) $t['cl_items'] . ' items' : '' ?><?php else: ?><?= e($f['empty']) ?><?php endif; ?></div>
    <div class="acts">
      <?php if ($t[$f['id']]): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/templates/preview/' . $kind . '/' . $t[$f['id']]) ?>" target="_blank" rel="noopener" title="Preview"><?= icon('eye', 'sm') ?></a><?php endif; ?>
      <a class="btn btn-secondary btn-sm" href="<?= url('admin/templates/' . $kind . '/' . $t['id']) ?>"><?= $t[$f['id']] ? 'Edit' : 'Create' ?></a>
      <button class="btn btn-ghost btn-sm" data-modal="type-<?= $t['id'] ?>" title="Inspection type settings"><?= icon('settings', 'sm') ?></button>
    </div>
  </div>
<?php endforeach; ?>
</details>
<?php endforeach; ?>
<script>
(function(){ ['checklist','certificate'].forEach(function(k){ var d=document.getElementById('folder-'+k); if(!d) return;
  try{ if(localStorage.getItem('tplfolder-'+k)==='closed') d.open=false; }catch(e){}
  d.addEventListener('toggle',function(){ try{ localStorage.setItem('tplfolder-'+k, d.open?'open':'closed'); }catch(e){} }); }); })();
</script>
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
