<section class="section" style="padding-top:40px">
  <div class="pub-wrap" style="max-width:920px">
    <div class="eyebrow">Website request</div>
    <h2>Request an inspection</h2>
    <p class="lead">Tell us what needs inspecting. Our operations team confirms the schedule and assigns a qualified inspector — you will receive a reference number immediately.</p>
    <form class="card mt-3" method="post" action="<?= url('request') ?>">
      <?= Csrf::field() ?>
      <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
      <div class="card-b">
        <div class="form-grid">
          <div class="form-section"><?= icon('building', 'sm') ?> Company & contact</div>
          <div class="field col-6"><label>Company name <span class="req">*</span></label><input class="input" name="company_name" value="<?= e(old('company_name')) ?>" required><?= field_error('company_name') ?></div>
          <div class="field col-6"><label>Contact person <span class="req">*</span></label><input class="input" name="contact_name" value="<?= e(old('contact_name')) ?>" required><?= field_error('contact_name') ?></div>
          <div class="field col-6"><label>Email <span class="req">*</span></label><input class="input" type="email" name="email" value="<?= e(old('email')) ?>" required><?= field_error('email') ?></div>
          <div class="field col-6"><label>Phone <span class="req">*</span></label><input class="input" name="phone" value="<?= e(old('phone')) ?>" required placeholder="+966 …"><?= field_error('phone') ?></div>
          <div class="form-section mt-1"><?= icon('pin', 'sm') ?> Inspection details</div>
          <div class="field col-8"><label>Inspection location <span class="req">*</span></label><input class="input" name="location" value="<?= e(old('location')) ?>" required placeholder="Site / city"><?= field_error('location') ?></div>
          <div class="field col-4"><label>Preferred date</label><input class="input" type="date" name="preferred_date" min="<?= date('Y-m-d') ?>" value="<?= e(old('preferred_date')) ?>"></div>
          <div class="field col-12"><label>Notes</label><textarea class="textarea" name="description" placeholder="Access requirements, P.O. number, standards required…"><?= e(old('description')) ?></textarea></div>
          <div class="form-section mt-1"><?= icon('truck', 'sm') ?> Equipment to inspect</div>
          <div class="col-12"><?php View::render('partials/request_items', ['types' => $types], null); ?></div>
        </div>
      </div>
      <div class="card-f"><span class="muted small" style="flex:1">By submitting you agree to be contacted about this request.</span><button class="btn btn-primary btn-lg"><?= icon('send') ?> Submit request</button></div>
    </form>
  </div>
</section>
