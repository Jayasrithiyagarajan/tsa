<div class="page-head">
  <div class="ph-text"><div class="eyebrow">Email & phone channel</div><h1>Log a client request</h1><div class="sub">Record a request received by email or phone so it follows the same workflow as website requests.</div></div>
</div>
<form method="post" action="<?= url('admin/requests') ?>" class="card">
  <?= Csrf::field() ?>
  <div class="card-b">
    <div class="form-grid">
      <div class="field col-4"><label>Received via <span class="req">*</span></label>
        <div class="seg" style="width:100%">
          <label style="flex:1"><input type="radio" name="channel" value="email"<?= checked(old('channel', 'email') === 'email') ?>><span class="pr" style="width:100%"><?= icon('mail', 'sm') ?>&nbsp;Email</span></label>
          <label style="flex:1"><input type="radio" name="channel" value="phone"<?= checked(old('channel') === 'phone') ?>><span class="pr" style="width:100%"><?= icon('phone', 'sm') ?>&nbsp;Phone call</span></label>
        </div><?= field_error('channel') ?></div>
      <div class="field col-8"><label>Existing client</label>
        <select class="select" name="client_id"><option value="">— New / unregistered company —</option>
          <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], old('client_id')) ?>><?= e($c['name']) ?> (<?= e($c['code']) ?>)</option><?php endforeach; ?></select>
        <div class="help">Choosing a client fills empty contact fields from the client record.</div></div>
      <div class="field col-6"><label>Company name <span class="req">*</span></label><input class="input" name="company_name" value="<?= e(old('company_name')) ?>"><?= field_error('company_name') ?></div>
      <div class="field col-6"><label>Contact person <span class="req">*</span></label><input class="input" name="contact_name" value="<?= e(old('contact_name')) ?>"><?= field_error('contact_name') ?></div>
      <div class="field col-6"><label>Email</label><input class="input" type="email" name="email" value="<?= e(old('email')) ?>"><?= field_error('email') ?></div>
      <div class="field col-6"><label>Phone</label><input class="input" name="phone" value="<?= e(old('phone')) ?>"><?= field_error('phone') ?></div>
      <div class="field col-6 field-location"><label>Inspection location</label><input class="input" name="location" value="<?= e(old('location')) ?>"></div>
      <div class="field col-3 field-po"><label>P.O. / reference</label><input class="input" name="po_no" value="<?= e(old('po_no')) ?>"></div>
      <div class="field col-3 field-date"><label>Preferred date</label><input class="input" type="date" name="preferred_date" value="<?= e(old('preferred_date')) ?>"></div>
      <div class="field col-12"><label>Notes (paste the email or call summary)</label><textarea class="textarea" name="description"><?= e(old('description')) ?></textarea></div>
      <div class="form-section"><?= icon('truck', 'sm') ?> Requested inspections</div>
      <div class="col-12"><?php View::render('partials/request_items', ['types' => $types], null); ?></div>
    </div>
  </div>
  <div class="card-f"><a class="btn btn-ghost" href="<?= url('admin/requests') ?>">Cancel</a><button class="btn btn-primary"><?= icon('check', 'sm') ?> Save request</button></div>
</form>
