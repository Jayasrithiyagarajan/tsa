<?php $c = $client ?? []; $v = fn ($k) => old($k, $c[$k] ?? ''); ?>
<div class="page-head"><div class="ph-text"><h1><?= $client ? 'Edit client' : 'New client' ?></h1></div></div>
<form class="card" method="post" action="<?= url($client ? 'admin/clients/' . $c['id'] : 'admin/clients') ?>" style="max-width:860px">
  <?= Csrf::field() ?>
  <div class="card-b"><div class="form-grid">
    <?php if (!$client): ?>
      <div class="field col-4"><label>Client code</label><input class="input mono" value="<?= e($nextCode ?? '') ?>" readonly disabled><div class="help">Generated automatically (next in sequence) when you save. Appears in certificate numbers and cannot be changed later.</div></div>
    <?php endif; ?>
    <div class="field col-8"><label>Company name <span class="req">*</span></label><input class="input" name="name" value="<?= e($v('name')) ?>" required><?= field_error('name') ?></div>
    <div class="field col-6"><label>Contact person</label><input class="input" name="contact_person" value="<?= e($v('contact_person')) ?>"></div>
    <div class="field col-6"><label>Email</label><input class="input" type="email" name="email" value="<?= e($v('email')) ?>"><?= field_error('email') ?></div>
    <div class="field col-6"><label>Phone</label><input class="input" name="phone" value="<?= e($v('phone')) ?>"><?= field_error('phone') ?></div>
    <div class="field col-6"><label>VAT No.</label><input class="input" name="vat_no" value="<?= e($v('vat_no')) ?>"></div>
    <div class="field col-8"><label>Address</label><input class="input" name="address" value="<?= e($v('address')) ?>"></div>
    <div class="field col-4"><label>City</label><input class="input" name="city" value="<?= e($v('city')) ?>"></div>
    <?php if ($client): ?><div class="field col-4"><label>Status</label><select class="select" name="status"><?php foreach (['active', 'inactive'] as $s): ?><option value="<?= $s ?>"<?= selected($s, $v('status')) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div><?php endif; ?>
  </div></div>
  <div class="card-f"><a class="btn btn-ghost" href="<?= url('admin/clients') ?>">Cancel</a><button class="btn btn-primary">Save client</button></div>
</form>
