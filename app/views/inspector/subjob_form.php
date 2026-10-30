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
<form method="post" action="<?= url('inspector/jobs/' . $job['id'] . '/subjobs') ?>" enctype="multipart/form-data" id="sj-form">
  <?= Csrf::field() ?>
  <div class="grid grid-main">
    <div class="stack">
      <div class="card">
        <div class="card-h"><h2><?= icon('layers', 'sm') ?> Sub-job type</h2></div>
        <div class="card-b"><div class="seg-tabs" role="radiogroup" style="display:inline-flex;gap:4px">
          <?php foreach (['inspection' => 'Inspection', 'operator' => 'Operator assessment'] as $k => $label): ?>
            <label style="cursor:pointer;padding:6px 14px;border-radius:7px;font-weight:600;font-size:13px"><input type="radio" name="kind" value="<?= $k ?>"<?= checked($old('kind', 'inspection') === $k) ?> style="margin-right:6px"><?= e($label) ?></label>
          <?php endforeach; ?>
        </div>
        <div class="help mt-1">Inspection: checklist, certificate and QC review. Operator assessment: issue the operator's ID card, upload the assessment document, then the sub-job is closed.</div></div>
      </div>
      <fieldset id="kind-inspection" style="border:0;padding:0;margin:0;min-width:0">
      <div class="card">
        <div class="card-h"><h2><?= icon('layers', 'sm') ?> Inspection details</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6">
            <label>Checklist / Inspection type <span class="req">*</span></label>
            <select class="select" name="inspection_type_id" id="type-select" required>
              <option value="">Select…</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>"<?= selected($t['id'], $old('inspection_type_id')) ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field col-6">
            <label>Certificate type <span class="req">*</span></label>
            <select class="select" name="cert_template_id" id="cert-select" required>
              <option value="">Select…</option>
              <?php foreach ($certTemplates as $ct): ?>
                <option value="<?= $ct['id'] ?>" data-type="<?= (int) $ct['inspection_type_id'] ?>"<?= selected($ct['id'], $old('cert_template_id')) ?>><?= e($ct['title']) ?><?= $ct['doc_ref'] ? ' · ' . e($ct['doc_ref']) : '' ?></option>
              <?php endforeach; ?>
            </select>
            <div class="help">Pre-selected from the checklist / inspection type; change it if a different certificate is needed.</div>
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
              <div class="field col-6"><label>Equipment description</label><input class="input" name="equipment_description" value="<?= e($old('equipment_description')) ?>" placeholder="e.g. BOOM TRUCK"></div>
              <div class="field col-6"><label>Equipment ID / S No.</label><input class="input" name="equipment_no" value="<?= e($old('equipment_no')) ?>"></div>
              <div class="field col-6"><label>Serial No.</label><input class="input" name="serial_no" value="<?= e($old('serial_no')) ?>"></div>
              <div class="field col-6"><label>Quantity</label><input class="input" type="number" min="1" max="50" name="quantity" value="<?= e($old('quantity', '1')) ?>"><div class="help">Each unit becomes a separate sub-job (1.1, 1.2 …)</div></div>
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
      </fieldset>
      <fieldset id="kind-operator" style="border:0;padding:0;margin:0;min-width:0;display:none" disabled>
      <div class="card">
        <div class="card-h"><h2><?= icon('user', 'sm') ?> Front side — operator / worker ID card</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6"><label>Client company</label><input class="input" value="<?= e($job['client_name']) ?>" disabled><div class="help">Taken from this job.</div></div>
          <div class="field col-6"><label>Name on card <span class="req">*</span></label><input class="input" name="holder_name" value="<?= e($old('holder_name')) ?>" required maxlength="120"></div>
          <div class="field col-6"><label>Iqama / ID No. <span class="req">*</span></label><input class="input mono" name="id_number" value="<?= e($old('id_number')) ?>" required maxlength="30"></div>
          <div class="field col-6"><label>Company <span class="req">*</span></label><input class="input" name="company" value="<?= e($old('company', mb_strtoupper($job['client_name']))) ?>" required maxlength="120"></div>
          <div class="field col-6"><label>ID code</label><input class="input mono" name="card_no" value="<?= e($old('card_no')) ?>" maxlength="40" placeholder="Auto, e.g. TE-IC-<?= date('Y') ?>-0001"></div>
          <div class="field col-6"><label>Title (blue band) <span class="req">*</span></label><input class="input" name="title" value="<?= e($old('title')) ?>" required maxlength="80" placeholder="e.g. CRANE OPERATOR LEVEL - III"></div>
          <div class="field col-6"><label>Issue date <span class="req">*</span></label><input class="input" type="date" name="issue_date" value="<?= e($old('issue_date', date('Y-m-d'))) ?>" required></div>
          <div class="field col-6"><label>Expiry date <span class="req">*</span></label><input class="input" type="date" name="expiry_date" value="<?= e($old('expiry_date', date('Y-m-d', strtotime('+1 year -1 day')))) ?>" required></div>
          <div class="field col-12"><label>Photo (passport style, JPG / PNG)</label><input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" style="padding-top:7px"><div class="help">Portrait 3 : 4, plain background.</div></div>
        </div></div>
      </div>
      <div class="card" style="margin-top:16px">
        <div class="card-h"><h2><?= icon('qr', 'sm') ?> Back side</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6"><label>Job Ref</label><input class="input mono" name="job_ref" value="<?= e($old('job_ref')) ?>" maxlength="60" placeholder="Default: this sub-job number"></div>
          <div class="field col-6"><label>Certification</label><input class="input" name="certification" value="<?= e($old('certification')) ?>" maxlength="120"></div>
          <div class="field col-6"><label>Assessor</label><input class="input" name="assessor" value="<?= e($old('assessor', Auth::user()['name'] ?? '')) ?>" maxlength="120"></div>
          <div class="field col-12"><label>Certificate of Competence</label><textarea class="textarea" name="competence" rows="5" maxlength="1000"><?= e($old('competence')) ?></textarea>
            <div class="help">One line per competence.</div></div>
        </div></div>
      </div>
      </fieldset>
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
// Pre-select the matching certificate when the checklist / inspection type changes
(function () {
  const t = document.getElementById('type-select'), c = document.getElementById('cert-select');
  if (!t || !c) return;
  t.addEventListener('change', function () {
    const m = Array.from(c.options).find(o => o.dataset.type === t.value);
    if (m) c.value = m.value;
  });
})();
// Equipment picker: show/hide new-equipment fields
document.getElementById('eq-select').addEventListener('change', function () {
  document.getElementById('new-eq-fields').style.display = this.value ? 'none' : '';
});
// Inspection / Operator assessment switch
(function () {
  const form = document.getElementById('sj-form'), insp = document.getElementById('kind-inspection'), op = document.getElementById('kind-operator');
  const sync = () => {
    const operator = form.querySelector('input[name=kind]:checked').value === 'operator';
    insp.disabled = operator; insp.style.display = operator ? 'none' : '';
    op.disabled = !operator; op.style.display = operator ? '' : 'none';
    form.querySelectorAll('input[name=kind]').forEach(r => { const l = r.parentNode; l.style.background = r.checked ? '#fff' : ''; l.style.boxShadow = r.checked ? 'var(--shadow-sm)' : ''; });
    form.querySelector('.card-f .btn-primary').lastChild.textContent = operator ? ' Issue card' : ' Add sub-job';
  };
  form.addEventListener('change', e => { if (e.target.name === 'kind') sync(); });
  sync();
})();
</script>
