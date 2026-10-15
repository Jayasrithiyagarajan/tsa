<div class="page-head"><div class="ph-text"><h1>My profile</h1><div class="sub"><?= badge($u['role']) ?> <?= e($u['email']) ?></div></div></div>
<div class="grid grid-2">
  <div class="stack">
    <form class="card" method="post" action="<?= url('profile') ?>"><?= Csrf::field() ?>
      <div class="card-h"><h2><?= icon('user', 'sm') ?> Details</h2></div>
      <div class="card-b stack">
        <div class="field"><label>Full name</label><input class="input" name="name" value="<?= e(old('name', $u['name'])) ?>" required><?= field_error('name') ?></div>
        <div class="field"><label>Phone</label><input class="input" name="phone" value="<?= e(old('phone', $u['phone'])) ?>"><?= field_error('phone') ?></div>
        <div class="field"><label>Email</label><div class="bound"><?= icon('lock', 'sm') ?><?= e($u['email']) ?></div><div class="help">Ask an administrator to change your sign-in email.</div></div>
        <?php if ($u['role'] === 'customer'): ?><div class="field"><label>Company</label><div class="bound"><?= icon('building', 'sm') ?><?= e($u['client_name']) ?></div></div><?php endif; ?>
      </div>
      <div class="card-f"><button class="btn btn-primary">Save</button></div>
    </form>
    <form class="card" method="post" action="<?= url('profile/password') ?>"><?= Csrf::field() ?>
      <div class="card-h"><h2><?= icon('lock', 'sm') ?> Change password</h2></div>
      <div class="card-b stack">
        <div class="field"><label>Current password</label><input class="input" type="password" name="current" autocomplete="current-password" required><?= field_error('current') ?></div>
        <div class="field"><label>New password</label><input class="input" type="password" name="new" autocomplete="new-password" required><?= field_error('new') ?></div>
        <div class="field"><label>Confirm new password</label><input class="input" type="password" name="confirm" autocomplete="new-password" required><?= field_error('confirm') ?></div>
      </div>
      <div class="card-f"><button class="btn btn-primary">Change password</button></div>
    </form>
  </div>
  <div class="stack">
    <?php if (in_array($u['role'], ['inspector', 'qc', 'admin'], true)): ?>
    <form class="card" method="post" action="<?= url('profile/signature') ?>" enctype="multipart/form-data"><?= Csrf::field() ?>
      <div class="card-h"><h2><?= icon('edit', 'sm') ?> Signature</h2></div>
      <div class="card-b">
        <div style="border:1px dashed var(--border-strong);border-radius:12px;padding:16px;text-align:center;background:#fff;min-height:100px;display:grid;place-items:center">
          <?php if ($u['signature_path']): ?><img src="<?= url('documents/signature/' . $u['id']) ?>" alt="Your signature" style="max-height:80px"><?php else: ?><span class="muted small">No signature uploaded</span><?php endif; ?></div>
        <div class="field mt-2"><label>Upload a scan (PNG with transparent background works best)</label><input class="input" type="file" name="signature" accept="image/png,image/jpeg" required style="padding-top:7px"></div>
        <p class="small muted mt-1">Printed on checklists you submit and on certificates you sign or review.</p>
      </div>
      <div class="card-f"><button class="btn btn-primary"><?= icon('upload', 'sm') ?> Upload signature</button></div>
    </form>
    <?php endif; ?>
    <?php if ($profile): ?>
    <div class="card"><div class="card-h"><h2><?= icon('award', 'sm') ?> Qualification</h2></div><div class="card-b"><dl class="kv">
      <dt>Employee No.</dt><dd><?= e($profile['employee_no'] ?: '—') ?></dd>
      <dt>Qualification</dt><dd><?= e($profile['qualification'] ?: '—') ?></dd>
      <dt>Certificate No.</dt><dd><?= e($profile['cert_no'] ?: '—') ?></dd>
      <dt>Expires</dt><dd><?= e(fmt_date($profile['cert_expiry']) ?: '—') ?></dd>
      <?php if ($skills): ?><dt>Qualified for</dt><dd><?php foreach ($skills as $s): ?><span class="pill" style="margin:0 4px 4px 0"><?= e($s['name']) ?></span><?php endforeach; ?></dd><?php endif; ?>
    </dl></div></div>
    <?php endif; ?>
  </div>
</div>
