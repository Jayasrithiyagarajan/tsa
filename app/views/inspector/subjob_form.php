<?php
/** Inspector: create a sub-job for an assigned job.
 * @var array $job
 * @var array $types
 * @var array $equipment
 * @var array $certTemplates   all active certificate templates (flat list)
 */
$old = fn ($k, $d = '') => old($k, $d);
?>
<div class="page-head">
  <div class="ph-text">
    <div class="eyebrow"><?= e($job['client_name']) ?> · <?= e($job['job_no']) ?></div>
    <h1>Add sub-job</h1>
    <div class="sub">Each sub-job covers one inspection of one piece of equipment.</div>
  </div>
</div>
<form method="post" action="<?= url('inspector/jobs/' . $job['id'] . '/subjobs') ?>">
  <?= Csrf::field() ?>
  <div class="grid grid-main">
    <div class="stack">
      <div class="card">
        <div class="card-h"><h2><?= icon('layers', 'sm') ?> Inspection details</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6">
            <label>Inspection type <span class="req">*</span></label>
            <select class="select" name="inspection_type_id" id="type-select" required>
              <option value="">Select…</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>"<?= selected($t['id'], $old('inspection_type_id')) ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field col-6">
            <label>Certificate type</label>
            <select class="select" name="cert_template_id">
              <option value="">— Auto (latest active) —</option>
              <?php foreach ($certTemplates as $ct): ?>
                <option value="<?= $ct['id'] ?>"<?= selected($ct['id'], $old('cert_template_id')) ?>><?= e($ct['title']) ?><?= $ct['doc_ref'] ? ' · ' . e($ct['doc_ref']) : '' ?></option>
              <?php endforeach; ?>
            </select>
            <div class="help">Leave as Auto unless you need a specific template version.</div>
          </div>
          <div class="field col-6">
            <label>Registered equipment</label>
            <select class="select" name="equipment_id" id="eq-select">
              <option value="">— New / not registered —</option>
              <?php foreach ($equipment as $eq): ?>
                <option value="<?= $eq['id'] ?>"<?= selected($eq['id'], $old('equipment_id')) ?>><?= e($eq['description']) ?><?= $eq['equipment_no'] ? ' · ' . e($eq['equipment_no']) : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12" id="new-eq-fields" style="<?= $old('equipment_id') ? 'display:none' : '' ?>">
            <div class="form-grid">
              <div class="field col-5"><label>Equipment description</label><input class="input" name="equipment_description" value="<?= e($old('equipment_description')) ?>" placeholder="e.g. BOOM TRUCK"></div>
              <div class="field col-3"><label>Equipment ID / S No.</label><input class="input" name="equipment_no" value="<?= e($old('equipment_no')) ?>"></div>
              <div class="field col-2"><label>Serial No.</label><input class="input" name="serial_no" value="<?= e($old('serial_no')) ?>"></div>
              <div class="field col-2"><label>Quantity</label><input class="input" type="number" min="1" max="50" name="quantity" value="<?= e($old('quantity', '1')) ?>"><div class="help">Each unit becomes a separate sub-job (1.1, 1.2 …)</div></div>
            </div>
          </div>
          <div class="field col-6">
            <label>Scheduled date</label>
            <input class="input" type="date" name="scheduled_date" value="<?= e($old('scheduled_date', $job['scheduled_date'] ?? '')) ?>">
          </div>
          <div class="field col-6">
            <label>Notes (optional)</label>
            <input class="input" name="notes" value="<?= e($old('notes')) ?>" placeholder="Any special remarks for this inspection">
          </div>
        </div></div>
      </div>
    </div>
    <div class="stack" style="position:sticky;top:76px">
      <div class="card">
        <div class="card-h"><h2><?= icon('briefcase', 'sm') ?> Job summary</h2></div>
        <div class="card-b small"><dl class="kv">
          <dt>Client</dt><dd><?= e($job['client_name']) ?></dd>
          <dt>Title</dt><dd><?= e($job['title']) ?></dd>
          <?php if ($job['location']): ?><dt>Location</dt><dd><?= e($job['location']) ?></dd><?php endif; ?>
          <?php if ($job['po_no']): ?><dt>PO No.</dt><dd><?= e($job['po_no']) ?></dd><?php endif; ?>
          <?php if ($job['scheduled_date']): ?><dt>Scheduled</dt><dd><?= e(fmt_date($job['scheduled_date'])) ?></dd><?php endif; ?>
        </dl></div>
        <div class="card-f">
          <a class="btn btn-ghost" href="<?= url('inspector/jobs/' . $job['id']) ?>">Cancel</a>
          <button class="btn btn-primary"><?= icon('plus', 'sm') ?> Add sub-job</button>
        </div>
      </div>
    </div>
  </div>
</form>
<script>
// Equipment picker: show/hide new-equipment fields
document.getElementById('eq-select').addEventListener('change', function () {
  document.getElementById('new-eq-fields').style.display = this.value ? 'none' : '';
});
</script>
