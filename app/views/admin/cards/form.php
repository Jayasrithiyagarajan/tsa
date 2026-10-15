<?php
/** Issue / edit an inspector ID card. @var ?array $card @var ?array $renew @var array $people @var int $userId */
$src = $card ?? $renew ?? [];
$person = null;
foreach ($people as $p) if ((int) $p['id'] === $userId) $person = $p;
$defaults = [
    'user_id' => $userId ?: '',
    'holder_name' => $person ? mb_strtoupper($person['name']) : '',
    'company' => 'TUV EAST',
    'title' => $person && $person['qualification'] ? mb_strtoupper($person['qualification']) : '',
    'certification' => $person['qualification'] ?? '',
    'job_ref' => $person['employee_no'] ?? '',
    'competence' => $person ? mb_strtoupper((string) $person['skills']) : '',
    'issue_date' => date('Y-m-d'),
    'expiry_date' => date('Y-m-d', strtotime('+1 year -1 day')),
];
$v = function (string $k) use ($card, $src, $defaults) {
    if (!$card && in_array($k, ['issue_date', 'expiry_date', 'card_no'], true)) return old($k, $defaults[$k] ?? '');
    return old($k, $src[$k] ?? $defaults[$k] ?? '');
};
$profilePhoto = $person['photo_path'] ?? null;
$preview = $model ?? InspectorCardService::model([
    'card_no' => 'TE-IC-' . date('Y') . '-XXXX', 'verify_token' => str_repeat('0', 32), 'photo_path' => $profilePhoto ?: ($src['photo_path'] ?? null),
    'holder_name' => $v('holder_name'), 'id_number' => $v('id_number'), 'company' => $v('company'), 'title' => $v('title'),
    'job_ref' => $v('job_ref'), 'certification' => $v('certification'), 'assessor' => $v('assessor'), 'competence' => $v('competence'),
    'issue_date' => $v('issue_date'), 'expiry_date' => $v('expiry_date'), 'status' => 'active',
]);
$state = $card ? InspectorCardService::state($card) : null;
?>
<style><?= file_get_contents(ROOT_PATH . '/assets/css/idcard.css') ?>
.idc * { box-sizing: content-box; }
.idc-faces { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 18px; background: #eef1f6; border-radius: 12px; }
.idc-faces .cap { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
.idc-faces .idc { zoom: 1.35; box-shadow: 0 2px 10px rgba(15,23,42,.18); }
@media (max-width: 760px) { .idc-faces { padding: 10px; } .idc-faces .idc { zoom: .95; } }
</style>
<div class="page-head">
  <div class="ph-text"><h1><?= $card ? e($card['holder_name']) : 'Issue inspector ID card' ?></h1>
    <div class="sub"><?php if ($card): ?><span class="mono"><?= e($card['card_no']) ?></span>
      <?= InspectorCardService::badge($state) ?>
      <?= $card['handed_over_at'] ? '<span>Handed over ' . e(fmt_datetime($card['handed_over_at'])) . '</span>' : '' ?>
      <?= $card['revoked_reason'] ? '<span>' . e($card['revoked_reason']) . '</span>' : '' ?>
    <?php else: ?>Fill in the card details. The ID code and QR code are generated when you save; the card becomes valid once you mark it as handed over.<?php endif; ?></div></div>
  <?php if ($card): ?><div class="actions">
    <a class="btn btn-secondary" href="<?= url('verify/card/' . $card['verify_token']) ?>" target="_blank" rel="noopener"><?= icon('qr', 'sm') ?> Verification page</a>
    <a class="btn btn-secondary" href="<?= url('admin/cards/' . $card['id'] . '/print', ['side' => 'front']) ?>"><?= icon('printer', 'sm') ?> Print front</a>
    <a class="btn btn-secondary" href="<?= url('admin/cards/' . $card['id'] . '/print', ['side' => 'back']) ?>"><?= icon('printer', 'sm') ?> Print back</a>
  </div><?php endif; ?>
</div>

<?php if ($card && $state === 'pending'): ?>
  <div class="alert alert-warning"><?= icon('clock') ?><div style="flex:1"><b>Awaiting hand-over.</b> Print the front and back, give the card to the inspector, then mark it as handed over.
    Until then the QR shows "not yet issued" and the inspector cannot see the card in their portal.</div>
    <form method="post" action="<?= url('admin/cards/' . $card['id'] . '/handover') ?>" onsubmit="return confirm('Confirm card <?= e($card['card_no']) ?> was handed to <?= e($card['holder_name']) ?>?')">
      <?= Csrf::field() ?><button class="btn btn-primary btn-sm"><?= icon('check', 'sm') ?> Mark as handed over</button></form></div>
<?php endif; ?>
<div class="card mb-2"><div class="card-b">
  <div class="idc-faces" id="idc-preview">
    <div><div class="cap">Front</div><?php View::render('partials/id_card', ['m' => $preview, 'side' => 'front'], null); ?></div>
    <div><div class="cap">Back</div><?php View::render('partials/id_card', ['m' => $preview, 'side' => 'back'], null); ?></div>
  </div>
  <?php if ($card): ?><p class="small muted mt-1" style="text-align:center">QR opens <span class="mono"><?= e(abs_url('verify/card/' . $card['verify_token'])) ?></span></p><?php endif; ?>
</div></div>

<form method="post" action="<?= url($card ? 'admin/cards/' . $card['id'] : 'admin/cards') ?>" enctype="multipart/form-data" id="card-form">
  <?= Csrf::field() ?>
  <?php if (!$card && $renew): ?><input type="hidden" name="renew_from" value="<?= (int) $renew['id'] ?>"><?php endif; ?>
  <div class="grid grid-main">
    <div class="stack">
      <div class="card"><div class="card-h"><h2><?= icon('user', 'sm') ?> Front side</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6"><label>Inspector <span class="req">*</span></label>
            <select class="select" name="user_id" id="card-user" required<?= $card ? ' disabled' : '' ?>><option value="">Select…</option>
              <?php foreach ($people as $p): ?><option value="<?= $p['id'] ?>"<?= selected($p['id'], $v('user_id')) ?>
                data-name="<?= e(mb_strtoupper($p['name'])) ?>" data-qual="<?= e($p['qualification']) ?>" data-emp="<?= e($p['employee_no']) ?>" data-skills="<?= e(mb_strtoupper((string) $p['skills'])) ?>" data-photo="<?= $p['photo_path'] ? e(url('id-photo/' . $p['id'])) : '' ?>"><?= e($p['name']) ?><?= $p['role'] === 'qc' ? ' (QC)' : '' ?></option><?php endforeach; ?>
            </select><?php if ($card): ?><input type="hidden" name="user_id" value="<?= (int) $card['user_id'] ?>"><?php endif; ?><?= field_error('user_id') ?></div>
          <div class="field col-6"><label>Name on card <span class="req">*</span></label><input class="input" name="holder_name" value="<?= e($v('holder_name')) ?>" required maxlength="120"><?= field_error('holder_name') ?></div>
          <div class="field col-6"><label>Iqama / ID No.</label><input class="input mono" name="id_number" value="<?= e($v('id_number')) ?>" maxlength="30"><?= field_error('id_number') ?></div>
          <div class="field col-6"><label>Company <span class="req">*</span></label><input class="input" name="company" value="<?= e($v('company')) ?>" required maxlength="120"><?= field_error('company') ?></div>
          <div class="field col-6"><label>ID code</label><input class="input mono" name="card_no" value="<?= e($card ? $card['card_no'] : old('card_no')) ?>" maxlength="40" placeholder="Auto, e.g. TE-IC-<?= date('Y') ?>-0001"><?= field_error('card_no') ?></div>
          <div class="field col-6"><label>Title (blue band) <span class="req">*</span></label><input class="input" name="title" value="<?= e($v('title')) ?>" required maxlength="80" placeholder="e.g. LIFTING EQUIPMENT INSPECTOR"><?= field_error('title') ?></div>
          <div class="field col-6"><label>Issue date <span class="req">*</span></label><input class="input" type="date" name="issue_date" value="<?= e($v('issue_date')) ?>" required><?= field_error('issue_date') ?></div>
          <div class="field col-6"><label>Expiry date <span class="req">*</span></label><input class="input" type="date" name="expiry_date" value="<?= e($v('expiry_date')) ?>" required><?= field_error('expiry_date') ?></div>
          <div class="field col-12"><label>Photo (passport style, JPG / PNG)</label><input class="input" type="file" name="photo" id="card-photo" accept="image/jpeg,image/png,image/webp" style="padding-top:7px">
            <div class="help"><?= $card ? 'Leave empty to keep the current photo.' : ($profilePhoto ? 'Leave empty to use the photo from the inspector profile.' : 'Portrait 3 : 4, plain background. The inspector can also upload it from their portal.') ?></div>
            <?php if ($card && $profilePhoto && $profilePhoto !== $card['photo_path']): ?>
              <div class="row mt-1" style="gap:10px;align-items:center;padding:8px;border:1px dashed var(--border-strong);border-radius:10px">
                <img src="<?= url('id-photo/' . $card['user_id']) ?>" alt="" style="width:42px;height:56px;object-fit:cover;border-radius:4px">
                <label class="check"><input type="checkbox" name="use_profile_photo" value="1"> Use the newer photo from the inspector profile</label></div>
            <?php endif; ?></div>
        </div></div></div>

      <div class="card"><div class="card-h"><h2><?= icon('qr', 'sm') ?> Back side</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6"><label>Job Ref</label><input class="input mono" name="job_ref" value="<?= e($v('job_ref')) ?>" maxlength="60"></div>
          <div class="field col-6"><label>Certification</label><input class="input" name="certification" value="<?= e($v('certification')) ?>" maxlength="120"></div>
          <div class="field col-6"><label>Assessor</label><input class="input" name="assessor" value="<?= e($v('assessor')) ?>" maxlength="120"></div>
          <div class="field col-12"><label>Certificate of Competence</label><textarea class="textarea" name="competence" rows="5" maxlength="1000"><?= e($v('competence')) ?></textarea>
            <div class="help">One line per competence. The text shrinks (or splits into two columns) so every line fits in the box.</div></div>
        </div></div></div>
    </div>
    <div class="stack">
      <div class="card"><div class="card-b">
        <button class="btn btn-primary btn-block"><?= icon('check', 'sm') ?> <?= $card ? 'Save changes' : 'Issue card' ?></button>
        <a class="btn btn-ghost btn-block mt-1" href="<?= url('admin/cards') ?>">Cancel</a>
        <?php if (!$card): ?><p class="small muted mt-1">Issuing a new card revokes the inspector's previous active card, so a lost or old card no longer verifies.</p><?php endif; ?>
      </div></div>
      <?php if ($card): ?>
      <div class="card"><div class="card-h"><h2><?= icon('rotate', 'sm') ?> Renew / replace</h2></div><div class="card-b">
        <p class="small muted">Issue a new card with the same details and new dates. This card is revoked automatically.</p>
        <a class="btn btn-secondary btn-block mt-1" href="<?= url('admin/cards/new', ['user' => $card['user_id']]) ?>">Issue replacement card</a>
      </div></div>
      <?php endif; ?>
    </div>
  </div>
</form>
<?php if ($card && $card['status'] === 'active'): ?>
  <form method="post" action="<?= url('admin/cards/' . $card['id'] . '/revoke') ?>" class="card mt-2" style="max-width:640px" onsubmit="return confirm('Revoke card <?= e($card['card_no']) ?>? Scanning it will show REVOKED.')">
    <?= Csrf::field() ?>
    <div class="card-h"><h2 style="color:var(--red)"><?= icon('x', 'sm') ?> Revoke card</h2></div>
    <div class="card-b row wrap" style="gap:10px"><input class="input" style="flex:1;min-width:220px" name="reason" required maxlength="255" placeholder="Reason, e.g. lost card, left the company">
      <button class="btn btn-danger">Revoke</button></div>
  </form>
<?php endif; ?>
<script>
(function () {
  const form = document.getElementById('card-form'), box = document.getElementById('idc-preview');
  const fmt = (d) => { const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(d || ''); return m ? m[3] + '-' + m[2] + '-' + m[1] : ''; };
  const upper = ['holder_name', 'company', 'title'];
  const paint = (name) => {
    const el = form.elements[name]; if (!el) return;
    let val = el.value;
    if (upper.includes(name)) val = val.toUpperCase();
    if (name === 'card_no' && !val) return;
    if (name.endsWith('_date')) val = fmt(val);
    if (name === 'competence') return fitCompetence(val);
    box.querySelectorAll('[data-f="' + name + '"]').forEach((t) => { t.textContent = val; });
  };
  // same rules as InspectorCardService::fitCompetence()
  function fitCompetence(text) {
    const lines = text.toUpperCase().split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
    const layouts = [[{ left: 2.2, width: 56.6, lines }]];
    if (lines.length > 1) {
      const n = Math.ceil(lines.length / 2);
      layouts.push([{ left: 2.2, width: 27.3, lines: lines.slice(0, n) }, { left: 31.5, width: 27.3, lines: lines.slice(n) }]);
    }
    const rows = (c, pt, wrap) => {
      const per = Math.max(1, Math.floor(c.width / (pt * 0.3528 * 0.64)));
      let n = 0;
      for (const l of c.lines) { if (!wrap && l.length > per) return null; n += Math.ceil(l.length / per); }
      return n;
    };
    let best = null;
    for (const wrap of [false, true]) {
      layouts.forEach((cols) => {
        let pt = 5.4;
        for (; pt >= 3.0; pt = Math.round((pt - 0.1) * 10) / 10) {
          const need = cols.map((c) => rows(c, pt, wrap));
          if (!need.includes(null) && Math.max(0, ...need) * pt * 0.3528 * 1.3 <= 15.4) break;
        }
        if (pt < 3.0) return;
        if (!best || pt >= best.pt + 0.5) best = { pt, cols };
      });
      if (best) break;
    }
    if (!best) best = { pt: 3.0, cols: layouts[layouts.length - 1] };
    box.querySelectorAll('[data-f="competence"]').forEach((b) => {
      b.querySelectorAll('.bc').forEach((el) => el.remove());
      const lh = best.pt * 0.3528 * 1.3;
      best.cols.forEach((c) => {
        let row = 0;
        c.lines.forEach((l) => {
          const sp = document.createElement('span');
          sp.className = 'bc';
          sp.style.cssText = 'left:' + c.left + 'mm;top:' + (5.2 + row * lh).toFixed(2) + 'mm;width:' + c.width + 'mm;font-size:' + best.pt + 'pt;line-height:1.3';
          sp.textContent = l;
          b.appendChild(sp);
          row += rows({ width: c.width, lines: [l] }, best.pt, true);
        });
      });
    });
  }
  form.addEventListener('input', (e) => e.target.name && paint(e.target.name));
  const user = document.getElementById('card-user');
  user.addEventListener('change', () => {
    const o = user.selectedOptions[0]; if (!o || !o.value) return;
    const set = (n, v) => { if (form.elements[n] && v) { form.elements[n].value = v; paint(n); } };
    set('holder_name', o.dataset.name); set('certification', o.dataset.qual); set('title', (o.dataset.qual || '').toUpperCase());
    set('job_ref', o.dataset.emp); set('competence', o.dataset.skills);
    if (o.dataset.photo) showPhoto(o.dataset.photo);
  });
  function showPhoto(src) {
    box.querySelectorAll('[data-f="photo"]').forEach((p) => { p.innerHTML = '<img alt="" style="left:0;top:0;width:14.2mm;height:18.6mm;object-fit:cover">'; p.firstChild.src = src; });
  }
  document.getElementById('card-photo').addEventListener('change', (e) => {
    const f = e.target.files[0]; if (!f) return;
    const r = new FileReader();
    r.onload = () => showPhoto(r.result);
    r.readAsDataURL(f);
  });
})();
</script>
