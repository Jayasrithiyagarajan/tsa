<?php
/** New job / convert request. @var ?array $request */
$r = $request;
$old = fn ($k, $d = '') => old($k, $d);
$clientId = $old('client_id', $r['client_id'] ?? '');
?>
<div class="page-head">
  <div class="ph-text">
    <?php if ($r): ?><div class="eyebrow">Convert <?= e($r['request_no']) ?> · <?= e(Workflow::label($r['channel'])) ?></div><?php endif; ?>
    <h1><?= $r ? 'Create job from request' : 'Create a new job' ?></h1>
    <div class="sub">A job is the client order. Once created and assigned, the inspector will add sub-jobs for each piece of equipment.</div>
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
    </div>
    <div class="stack" style="position:sticky;top:76px">
      <div class="card">
        <div class="card-h"><h2><?= icon('users', 'sm') ?> Assignment</h2></div>
        <div class="card-b">
          <div class="field"><label>Assign to inspector</label>
            <select class="select" name="inspector_id"><option value="">Assign later</option>
              <?php foreach ($inspectors as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['name']) ?> — <?= (int) $u['open_load'] ?> open</option><?php endforeach; ?></select>
            <div class="help">The assigned inspector will create sub-jobs for each piece of equipment they need to inspect.</div></div>
        </div>
        <div class="card-f">
          <a class="btn btn-ghost" href="<?= url($r ? 'admin/requests/' . $r['id'] : 'admin/jobs') ?>">Cancel</a>
          <button class="btn btn-primary"><?= icon('check', 'sm') ?> <?= $r ? 'Convert to job' : 'Create job' ?></button>
        </div>
      </div>
      <?php if ($r): ?>
      <div class="card"><div class="card-h"><h2><?= icon('inbox', 'sm') ?> From request</h2></div><div class="card-b small"><dl class="kv">
        <dt>Company</dt><dd><?= e($r['company_name']) ?></dd><dt>Contact</dt><dd><?= e($r['contact_name']) ?></dd>
        <dt>Email</dt><dd><?= e($r['email'] ?: '—') ?></dd><dt>Phone</dt><dd><?= e($r['phone'] ?: '—') ?></dd></dl></div></div>
      <?php endif; ?>
    </div>
  </div>
</form>
