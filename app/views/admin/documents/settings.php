<div class="page-head"><div class="ph-text"><h1>Settings</h1><div class="sub">Company details and document numbering.</div></div></div>
<form class="card" method="post" action="<?= url('admin/settings') ?>" style="max-width:820px">
  <?= Csrf::field() ?>
  <div class="card-b"><div class="form-grid">
    <div class="form-section"><?= icon('building', 'sm') ?> Company</div>
    <div class="field col-12"><label>Company name</label><input class="input" name="company_name" value="<?= e(setting('company_name')) ?>"></div>
    <div class="field col-4"><label>C.R. No.</label><input class="input" name="company_cr" value="<?= e(setting('company_cr')) ?>"></div>
    <div class="field col-4"><label>Email</label><input class="input" name="company_email" value="<?= e(setting('company_email')) ?>"></div>
    <div class="field col-4"><label>Phone</label><input class="input" name="company_phone" value="<?= e(setting('company_phone')) ?>"></div>
    <div class="form-section mt-1"><?= icon('award', 'sm') ?> Certificate numbering</div>
    <div class="field col-8"><label>Pattern</label><input class="input mono" name="certificate_pattern" value="<?= e(setting('certificate_pattern')) ?>">
      <div class="help">Tokens: <span class="mono">{YYYY}</span> job year · <span class="mono">{CLIENT}</span> client code · <span class="mono">{JOB}</span> job sequence · <span class="mono">{SEQ}</span> sub-job (3 digits). Example: 2026-22108-0001-001. Applies to inspections started after saving.</div></div>
  </div></div>
  <div class="card-f"><button class="btn btn-primary">Save settings</button></div>
</form>
