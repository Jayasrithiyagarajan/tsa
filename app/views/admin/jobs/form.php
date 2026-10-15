<?php
/** New job / convert request. @var ?array $request @var array $items */
$r = $request;
$old = fn ($k, $d = '') => old($k, $d);
$subRows = old('subjobs') ?: ($items
    ? array_map(fn ($i) => ['inspection_type_id' => $i['inspection_type_id'], 'equipment_description' => $i['equipment_description'], 'equipment_no' => $i['equipment_no'], 'quantity' => $i['quantity']], $items)
    : [['inspection_type_id' => '', 'quantity' => 1]]);
$clientId = $old('client_id', $r['client_id'] ?? '');
$row = function ($i, $s) use ($types, $inspectors) { ?>
  <div class="card" data-repeat-row style="margin-top:12px;box-shadow:none;background:var(--surface-2)">
    <div class="card-b" style="padding:14px">
      <div class="form-grid">
        <div class="field col-4"><label>Inspection type <span class="req">*</span></label>
          <select class="select" name="subjobs[<?= $i ?>][inspection_type_id]" required><option value="">Select…</option>
            <?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"<?= selected($t['id'], $s['inspection_type_id'] ?? '') ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field col-5"><label>Registered equipment</label>
          <select class="select" name="subjobs[<?= $i ?>][equipment_id]" data-equipment><option value="">— New / not registered —</option></select></div>
        <div class="field col-3"><label>Inspector (optional)</label>
          <select class="select" name="subjobs[<?= $i ?>][inspector_id]"><option value="">Assign later</option>
            <?php foreach ($inspectors as $u): ?><option value="<?= $u['id'] ?>"<?= selected($u['id'], $s['inspector_id'] ?? '') ?>><?= e($u['name']) ?> (<?= e($u['skills']) ?>)</option><?php endforeach; ?></select></div>
        <div class="col-12" data-new-eq><div class="form-grid">
          <div class="field col-5"><label>New equipment description</label><input class="input" name="subjobs[<?= $i ?>][equipment_description]" value="<?= e($s['equipment_description'] ?? '') ?>" placeholder="e.g. BOOM TRUCK"></div>
          <div class="field col-3"><label>Equipment ID / S No.</label><input class="input" name="subjobs[<?= $i ?>][equipment_no]" value="<?= e($s['equipment_no'] ?? '') ?>"></div>
          <div class="field col-2"><label>Serial No.</label><input class="input" name="subjobs[<?= $i ?>][serial_no]" value="<?= e($s['serial_no'] ?? '') ?>"></div>
          <div class="field col-2"><label>Quantity</label><input class="input" type="number" min="1" max="100" name="subjobs[<?= $i ?>][quantity]" value="<?= e($s['quantity'] ?? 1) ?>"></div>
        </div></div>
      </div>
      <div class="row between mt-1"><span class="small muted">Each unit becomes its own sub-job (e.g. 1.1, 1.2 …)</span>
        <button type="button" class="btn btn-danger btn-sm" data-repeat-remove><?= icon('trash', 'sm') ?> Remove</button></div>
    </div>
  </div>
<?php }; ?>
<div class="page-head">
  <div class="ph-text">
    <?php if ($r): ?><div class="eyebrow">Convert <?= e($r['request_no']) ?> · <?= e(Workflow::label($r['channel'])) ?></div><?php endif; ?>
    <h1><?= $r ? 'Create job from request' : 'Create a new job' ?></h1>
    <div class="sub">A job is the client order; each sub-job is one inspection of one piece of equipment.</div>
  </div>
</div>
<form method="post" action="<?= e($action) ?>">
  <?= Csrf::field() ?>
  <div class="grid grid-main">
    <div class="stack">
      <div class="card">
        <div class="card-h"><h2><?= icon('briefcase', 'sm') ?> Job details</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6"><label>Client <span class="req">*</span></label>
            <select class="select" name="client_id" data-client-equipment <?= $r ? '' : 'required' ?>>
              <option value=""><?= $r ? '— Register new client from request —' : 'Select client…' ?></option>
              <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], $clientId) ?>><?= e($c['name']) ?> (<?= e($c['code']) ?>)</option><?php endforeach; ?>
            </select></div>
          <?php if ($r && !$r['client_id']): ?>
            <div class="field col-3"><label>New client code</label><input class="input" name="new_client_code" value="<?= e($old('new_client_code')) ?>" placeholder="e.g. 22190"><div class="help">Used in certificate numbers</div></div>
            <div class="field col-3"><label>City</label><input class="input" name="new_client_city" value="<?= e($old('new_client_city')) ?>"></div>
          <?php endif; ?>
          <div class="field col-12"><label>Job title <span class="req">*</span></label><input class="input" name="title" required value="<?= e($old('title', $r ? 'Inspection — ' . $r['company_name'] : '')) ?>"></div>
          <div class="field col-6"><label>Inspection location</label><input class="input" name="location" value="<?= e($old('location', $r['location'] ?? '')) ?>"></div>
          <div class="field col-6"><label>Request / P.O. No.</label><input class="input" name="po_no" value="<?= e($old('po_no', $r['po_no'] ?? '')) ?>"></div>
          <div class="field col-4"><label>Scheduled date</label><input class="input" type="date" name="scheduled_date" value="<?= e($old('scheduled_date', $r['preferred_date'] ?? '')) ?>"></div>
          <div class="field col-4"><label>Due date</label><input class="input" type="date" name="due_date" value="<?= e($old('due_date')) ?>"></div>
          <div class="field col-4"><label>Priority</label><select class="select" name="priority"><?php foreach (['normal', 'high', 'urgent', 'low'] as $p): ?><option value="<?= $p ?>"<?= selected($p, $old('priority', 'normal')) ?>><?= e(Workflow::label($p)) ?></option><?php endforeach; ?></select></div>
          <div class="field col-12"><label>Internal notes</label><textarea class="textarea" name="notes" style="min-height:70px"><?= e($old('notes', $r['description'] ?? '')) ?></textarea></div>
        </div></div>
      </div>
      <div class="card">
        <div class="card-h"><h2><?= icon('layers', 'sm') ?> Sub-jobs</h2><span class="hint">inspection type × equipment</span></div>
        <div class="card-b" style="padding-top:2px">
          <div id="subjobs"><?php foreach (array_values($subRows) as $i => $s) $row($i, $s); ?></div>
          <template id="subjobs-tpl"><?php $row('__i__', ['quantity' => 1]); ?></template>
          <button type="button" class="btn btn-secondary mt-2" data-repeat-add="subjobs"><?= icon('plus', 'sm') ?> Add sub-job</button>
        </div>
      </div>
    </div>
    <div class="stack" style="position:sticky;top:76px">
      <div class="card">
        <div class="card-h"><h2><?= icon('users', 'sm') ?> Assignment</h2></div>
        <div class="card-b">
          <div class="field"><label>Assign whole job to</label>
            <select class="select" name="inspector_id"><option value="">Assign later / per sub-job</option>
              <?php foreach ($inspectors as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['name']) ?> — <?= (int) $u['open_load'] ?> open</option><?php endforeach; ?></select>
            <div class="help">Only sub-jobs the inspector is qualified for are assigned; others stay in the assignment board.</div></div>
        </div>
        <div class="card-f"><a class="btn btn-ghost" href="<?= url($r ? 'admin/requests/' . $r['id'] : 'admin/jobs') ?>">Cancel</a><button class="btn btn-primary"><?= icon('check', 'sm') ?> <?= $r ? 'Convert to job' : 'Create job' ?></button></div>
      </div>
      <?php if ($r): ?>
      <div class="card"><div class="card-h"><h2><?= icon('inbox', 'sm') ?> From request</h2></div><div class="card-b small"><dl class="kv">
        <dt>Company</dt><dd><?= e($r['company_name']) ?></dd><dt>Contact</dt><dd><?= e($r['contact_name']) ?></dd>
        <dt>Email</dt><dd><?= e($r['email'] ?: '—') ?></dd><dt>Phone</dt><dd><?= e($r['phone'] ?: '—') ?></dd></dl></div></div>
      <?php endif; ?>
    </div>
  </div>
</form>
