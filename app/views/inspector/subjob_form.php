<?php
/** Inspector: create a sub-job for an assigned job.
 * @var array $job
 * @var array $types
 * @var array $equipment
 * @var array $certTemplates   all active certificate templates (flat list)
 * @var array $stickerDefaults code => 0 no sticker / 2 red sticker (certificate-only types)
 * @var array $certMap         inspection_type_id => certificate_template_id fixed by the checklist sheet
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
        <div class="card-b" style="padding-bottom:0"><div class="field">
          <label>Inspection category <span class="req">*</span></label>
          <div role="radiogroup" style="display:flex;gap:22px;flex-wrap:wrap">
            <label class="check"><input type="radio" name="ndt_mode" value="with_checklist" required<?= checked($old('ndt_mode') === 'with_checklist') ?>> With checklist, certificate</label>
            <label class="check"><input type="radio" name="ndt_mode" value="cert_only"<?= checked($old('ndt_mode') === 'cert_only') ?>> Without checklist, but having certificate</label>
          </div>
          <div class="help" id="ndt-help">Choose first. With checklist: checklist, certificate and QC review. Without checklist: certificate only.</div>
        </div>
        <div class="field" style="margin-top:14px">
          <label>Sticker <span class="req">*</span></label>
          <div role="radiogroup" style="display:flex;gap:22px;flex-wrap:wrap">
            <label class="check"><input type="radio" name="with_sticker" value="1" required<?= checked($old('with_sticker') === '1') ?>> Yes</label>
            <label class="check"><input type="radio" name="with_sticker" value="0"<?= checked($old('with_sticker') === '0') ?>> No</label>
          </div>
          <div class="help">Yes: a sticker must be linked before submitting. No: this sub-job has no sticker. <b id="sticker-help"></b></div>
        </div></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6">
            <label id="type-label">Checklist / Inspection type <span class="req">*</span></label>
            <select class="select" name="inspection_type_id" id="type-select" required>
              <option value="">Select…</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>" data-group="<?= !empty($t['cert_only']) ? 'cert_only' : 'with_checklist' ?>" data-sticker="<?= (int) (($stickerDefaults ?? [])[$t['code']] ?? -1) ?>"<?= selected($t['id'], $old('inspection_type_id')) ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field col-6">
            <label>Certificate type <span class="req">*</span></label>
            <select class="select" name="cert_template_id" id="cert-select" required>
              <option value="">Select…</option>
              <?php foreach ($certTemplates as $ct): ?>
                <option value="<?= $ct['id'] ?>" data-type="<?= (int) $ct['inspection_type_id'] ?>" data-group="<?= !empty($ct['cert_only']) ? 'cert_only' : 'with_checklist' ?>"<?= selected($ct['id'], $old('cert_template_id')) ?>><?= e($ct['title']) ?><?= $ct['doc_ref'] ? ' · ' . e($ct['doc_ref']) : '' ?></option>
              <?php endforeach; ?>
            </select>
            <div class="help">Set automatically from the checklist type (Special / Lifting / Elevator / Escalator certificate).</div>
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
              <div class="field col-6"><label>Equipment Type</label><input class="input" name="equipment_description" value="<?= e($old('equipment_description')) ?>" placeholder="e.g. BOOM TRUCK"></div>
              <div class="field col-6"><label>Equipment ID / No.</label><input class="input" name="equipment_no" value="<?= e($old('equipment_no')) ?>"></div>
              <div class="field col-6"><label>Equipment Sl No.</label><input class="input" name="serial_no" value="<?= e($old('serial_no')) ?>"></div>
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
          <div class="field col-6"><label>Job Ref <span class="req">*</span></label><input class="input mono" name="job_ref" value="<?= e($old('job_ref')) ?>" required maxlength="60" placeholder="e.g. client job / PO reference"></div>
          <div class="field col-6"><label>Certification <span class="req">*</span></label><input class="input" name="certification" value="<?= e($old('certification')) ?>" required maxlength="120"></div>
          <div class="field col-6"><label>Assessor <span class="req">*</span></label><input class="input" name="assessor" value="<?= e($old('assessor', Auth::user()['name'] ?? '')) ?>" required maxlength="120"></div>
          <div class="field col-12"><label>Certificate of Competence <span class="req">*</span></label><textarea class="textarea" name="competence" rows="5" maxlength="1000" required><?= e($old('competence')) ?></textarea>
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
          <button class="btn btn-primary"><?= icon('check', 'sm') ?> Save sub-job</button>
        </div>
      </div>
    </div>
  </div>
</form>
<script>
// NDT / Non-NDT first: it decides which inspection types and certificates can be chosen
(function () {
  const form = document.getElementById('sj-form'), t = document.getElementById('type-select'), c = document.getElementById('cert-select');
  if (!form || !t || !c) return;
  const snap = (s) => ({ ph: s.options[0].cloneNode(true), all: Array.from(s.options).slice(1).map(o => o.cloneNode(true)) });
  const T = snap(t), C = snap(c);
  const fill = (sel, st, g) => {
    const cur = sel.value; sel.innerHTML = ''; sel.appendChild(st.ph.cloneNode(true));
    st.all.filter(o => o.dataset.group === g).forEach(o => sel.appendChild(o.cloneNode(true)));
    if (Array.from(sel.options).some(o => o.value === cur)) sel.value = cur;
  };
  const sync = () => {
    const r = form.querySelector('input[name=ndt_mode]:checked'), g = r ? r.value : '';
    fill(t, T, g); fill(c, C, g);
    t.disabled = c.disabled = !g;
    document.getElementById('type-label').firstChild.textContent = g === 'cert_only' ? 'Inspection type (certificate only) ' : 'Checklist / Inspection type ';
    document.getElementById('ndt-help').textContent = g === 'cert_only'
      ? 'Without checklist: certificate only - no checklist is filled for this sub-job.'
      : (g === 'with_checklist' ? 'With checklist: checklist, certificate and QC review.' : 'Choose first. With checklist: checklist, certificate and QC review. Without checklist: certificate only.');
  };
  form.addEventListener('change', e => { if (e.target.name === 'ndt_mode') sync(); });
  sync();
})();
// Pre-select the matching certificate when the checklist / inspection type changes
(function () {
  const t = document.getElementById('type-select'), c = document.getElementById('cert-select');
  if (!t || !c) return;
  const MAP = <?= json_encode((object) ($certMap ?? [])) ?>;
  const form = document.getElementById('sj-form');
  t.addEventListener('change', function () {
    const so = t.options[t.selectedIndex], sd = so ? so.dataset.sticker : '-1';
    if (sd === '0' || sd === '1' || sd === '2') {   // sticker suggested by the sheet (still the inspector's choice)
      const r = form.querySelector('input[name=with_sticker][value="' + (sd === '0' ? '0' : '1') + '"]'); if (r) r.checked = true;
      const h = document.getElementById('sticker-help');
      if (h) h.textContent = sd === '2' ? 'This certificate uses a RED sticker.' : (sd === '0' ? 'This certificate normally has no sticker.' : '');
    }
    const fixed = form.querySelector('input[name=ndt_mode]:checked')?.value === 'with_checklist' ? MAP[t.value] : null;
    if (fixed) {   // certificate is fixed by the checklist type
      c.value = String(fixed);
      Array.from(c.options).forEach(o => { o.hidden = o.value !== '' && o.value !== String(fixed); });
      return;
    }
    Array.from(c.options).forEach(o => { o.hidden = false; });
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
    form.querySelector('.card-f .btn-primary').lastChild.textContent = operator ? ' Issue card' : ' Save sub-job';
  };
  form.addEventListener('change', e => { if (e.target.name === 'kind') sync(); });
  sync();
})();
</script>
