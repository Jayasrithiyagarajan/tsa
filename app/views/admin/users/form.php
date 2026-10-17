<?php
$u = $user ?? [];
$p = $profile ?? [];
$v = fn ($k, $d = '') => old($k, $u[$k] ?? $p[$k] ?? $d);
$role = $v('role', $_GET['role'] ?? 'inspector');
$oldSkills = old('skills') ?: $skills;
$readOnly = !Auth::isSuper();          // only a Super Admin creates or edits user accounts
?>
<div class="page-head">
  <div class="ph-text"><h1><?= $user ? e($u['name']) : 'Create user' ?></h1><?php if ($user): ?><div class="sub"><?= badge($u['role']) ?> <span>Last login <?= e($u['last_login_at'] ? fmt_datetime($u['last_login_at']) : 'never') ?></span><?= $readOnly ? ' <span class="pill">View only</span>' : '' ?></div><?php endif; ?></div>
</div>
<form method="post" action="<?= url($user ? 'admin/users/' . $u['id'] : 'admin/users') ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <fieldset<?= $readOnly ? ' disabled' : '' ?> style="border:0;padding:0;margin:0;min-width:0">
  <div class="grid grid-main">
    <div class="stack">
      <div class="card"><div class="card-h"><h2><?= icon('user', 'sm') ?> Account</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-6"><label>Full name <span class="req">*</span></label><input class="input" name="name" value="<?= e($v('name')) ?>" required><?= field_error('name') ?></div>
          <div class="field col-6"><label>Email <span class="req">*</span></label><input class="input" type="email" name="email" value="<?= e($v('email')) ?>" required autocomplete="off"><?= field_error('email') ?></div>
          <div class="field col-4"><label>Role / portal <span class="req">*</span></label>
            <select class="select" name="role" id="role-select"><?php foreach (['inspector', 'qc', 'admin', 'superadmin', 'customer'] as $r): ?><option value="<?= $r ?>"<?= selected($r, $role) ?>><?= e(Workflow::label($r)) ?></option><?php endforeach; ?></select><?= field_error('role') ?></div>
          <div class="field col-4"><label>Phone</label><input class="input" name="phone" value="<?= e($v('phone')) ?>"><?= field_error('phone') ?></div>
          <div class="field col-4"><label>Status</label><select class="select" name="status"><?php foreach (['active', 'inactive'] as $s): ?><option value="<?= $s ?>"<?= selected($s, $v('status', 'active')) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
          <div class="field col-6" data-role-only="customer"><label>Client company <span class="req">*</span></label>
            <select class="select" name="client_id"><option value="">Select…</option><?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], $v('client_id', $_GET['client'] ?? '')) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?= field_error('client_id') ?></div>
          <div class="field col-6"><label><?= $user ? 'Reset password' : 'Password <span class="req">*</span>' ?></label><input class="input" type="password" name="password" autocomplete="new-password" placeholder="<?= $user ? 'Leave blank to keep current' : 'Minimum 8 characters' ?>"><?= field_error('password') ?></div>
        </div></div></div>

      <div class="card" data-role-only="inspector qc"><div class="card-h"><h2><?= icon('award', 'sm') ?> Qualification</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-4"><label>Employee No.</label><input class="input" name="employee_no" value="<?= e($v('employee_no')) ?>"></div>
          <div class="field col-8"><label>Qualification (printed on certificates)</label><input class="input" name="qualification" value="<?= e($v('qualification')) ?>" placeholder="e.g. Level II"></div>
          <div class="field col-4"><label>Certificate No.</label><input class="input" name="cert_no" value="<?= e($v('cert_no')) ?>"></div>
          <div class="field col-4"><label>Qualification expiry</label><input class="input" type="date" name="cert_expiry" value="<?= e($v('cert_expiry')) ?>"></div>
          <div class="field col-4"><label>Base location</label><input class="input" name="base_location" value="<?= e($v('base_location')) ?>"></div>
          <div class="col-12" data-role-only="inspector"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px"><label class="label" style="margin:0">Qualified to inspect</label><span style="font-size:.8rem"><a href="#" id="skills-select-all" style="color:var(--accent)">Select all</a> · <a href="#" id="skills-deselect-all" style="color:var(--muted)">Deselect all</a></span></div>
            <div class="row wrap" style="gap:8px 18px" id="skills-list">
              <?php foreach ($types as $t): ?><label class="check"><input type="checkbox" name="skills[]" value="<?= $t['id'] ?>"<?= checked(in_array((int) $t['id'], array_map('intval', (array) $oldSkills), true)) ?>> <?= e($t['name']) ?> <span class="pill"><?= e($t['code']) ?></span></label><?php endforeach; ?>
            </div><div class="help">The assignment screens only allow these inspection types for this inspector.</div></div>
        </div></div></div>
    </div>
    <div class="stack">
      <div class="card" data-role-only="inspector qc"><div class="card-h"><h2><?= icon('edit', 'sm') ?> Signature</h2></div>
        <div class="card-b">
          <?php if (!empty($u['signature_path'])): ?><div style="border:1px dashed var(--border-strong);border-radius:10px;padding:10px;text-align:center;background:#fff"><img src="<?= url('documents/signature/' . $u['id']) ?>" alt="Signature" style="max-height:70px"></div><?php endif; ?>
          <div class="field mt-1"><label>Upload signature (PNG / JPG)</label><input class="input" type="file" name="signature" accept="image/png,image/jpeg" style="padding-top:7px"></div>
          <p class="small muted mt-1">Printed on checklists and certificates once submitted / issued.</p>
        </div></div>
      <div class="card" data-role-only="inspector qc"><div class="card-h"><h2><?= icon('qr', 'sm') ?> ID card</h2></div>
        <div class="card-b">
          <?php if ($user):
              $idc = DB::one("SELECT * FROM inspector_cards WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1", [$u['id']]); ?>
            <?php if ($idc): ?>
              <p class="small"><span class="mono"><?= e($idc['card_no']) ?></span> · expires <?= e(fmt_date($idc['expiry_date'])) ?>
                <br><?= InspectorCardService::badge(InspectorCardService::state($idc + ['user_status' => $u['status']])) ?></p>
              <a class="btn btn-secondary btn-block mt-1" href="<?= url('admin/cards/' . $idc['id']) ?>">View / print card</a>
            <?php else: ?>
              <p class="small muted">No active ID card.</p>
              <?php if (Auth::isSuper()): ?><a class="btn btn-secondary btn-block mt-1" href="<?= url('admin/cards/new', ['user' => $u['id']]) ?>">Issue ID card</a><?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
          <?php if (!empty($p['photo_path'])): ?><div class="mt-2" style="text-align:center"><img src="<?= url('id-photo/' . $u['id']) ?>" alt="ID photo" style="width:90px;height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--border)"></div><?php endif; ?>
          <div class="field mt-1"><label>ID photo (JPG / PNG, portrait 3 : 4)</label><input class="input" type="file" name="id_photo" accept="image/jpeg,image/png,image/webp" style="padding-top:7px"></div>
          <p class="small muted mt-1">The inspector can also upload this from their portal. Used for the next ID card issued.</p>
        </div></div>
      <?php if (!$readOnly): ?><div class="card"><div class="card-b"><button class="btn btn-primary btn-block"><?= icon('check', 'sm') ?> Save user</button><a class="btn btn-ghost btn-block mt-1" href="<?= url('admin/users') ?>">Cancel</a></div></div><?php endif; ?>
    </div>
  </div>
</fieldset>
</form>
<script>
(function () {
  const sel = document.getElementById('role-select');
  const apply = () => document.querySelectorAll('[data-role-only]').forEach((el) => { el.hidden = !el.dataset.roleOnly.split(' ').includes(sel.value); });
  sel.addEventListener('change', apply); apply();

  const list = document.getElementById('skills-list');
  const boxes = () => list ? list.querySelectorAll('input[type="checkbox"]') : [];
  const sa = document.getElementById('skills-select-all');
  const da = document.getElementById('skills-deselect-all');
  if (sa) sa.addEventListener('click', (e) => { e.preventDefault(); boxes().forEach((cb) => { cb.checked = true; }); });
  if (da) da.addEventListener('click', (e) => { e.preventDefault(); boxes().forEach((cb) => { cb.checked = false; }); });
})();
</script>
