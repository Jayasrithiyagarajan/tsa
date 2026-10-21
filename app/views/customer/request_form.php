<div class="page-head"><div class="ph-text"><h1>Request an inspection</h1><div class="sub">Submitted on behalf of <?= e($client['name']) ?>. Contact details are taken from your profile.</div></div></div>
<form class="card" method="post" action="<?= url('portal/requests') ?>" style="max-width:980px">
  <?= Csrf::field() ?>
  <div class="card-b"><div class="form-grid">
    <div class="field col-6 field-location"><label>Inspection location <span class="req">*</span></label><input class="input" name="location" value="<?= e(old('location', $client['address'] ?? '')) ?>" required><?= field_error('location') ?></div>
    <div class="field col-3 field-po"><label>P.O. / reference</label><input class="input" name="po_no" value="<?= e(old('po_no')) ?>"></div>
    <div class="field col-3 field-date"><label>Preferred date</label><input class="input" type="date" name="preferred_date" min="<?= date('Y-m-d') ?>" value="<?= e(old('preferred_date')) ?>"></div>
    <div class="field col-12"><label>Notes</label><textarea class="textarea" name="description" placeholder="Site access, permits, contact on site…"><?= e(old('description')) ?></textarea></div>
    <div class="form-section"><?= icon('truck', 'sm') ?> Equipment</div>
    <div class="col-12"><?php View::render('partials/request_items', ['types' => $types], null); ?></div>
  </div></div>
  <div class="card-f"><a class="btn btn-ghost" href="<?= url('portal/requests') ?>">Cancel</a><button class="btn btn-primary"><?= icon('send', 'sm') ?> Submit request</button></div>
</form>
