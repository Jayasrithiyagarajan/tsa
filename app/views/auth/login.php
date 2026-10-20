<div class="auth-wrap">
  <section class="auth-art">
    <div style="position:relative;z-index:1">
      <div class="eyebrow" style="color:#9fb4ee">TUV EAST · Inspection Management System</div>
      <h1>Every inspection, from request to certificate, in one place.</h1>
      <p class="lead">Admin, inspectors, QC reviewers and customers work on the same live record — with controlled checklists and certificates that match TUV EAST forms exactly.</p>
      <div class="flow">
        <div><b>1</b>Client request — website, email or phone</div>
        <div><b>2</b>Admin creates the job and assigns qualified inspectors</div>
        <div><b>3</b>Inspector completes sub-job checklists on site</div>
        <div><b>4</b>QC reviews, approves and issues the certificate</div>
        <div><b>5</b>Sticker issued · customer downloads the certificate</div>
      </div>
    </div>
    <div class="small" style="color:#8f9cc0;position:relative;z-index:1">Saudi Accreditation Inspection N-I-00085 · LEEA Full Member</div>
  </section>
  <section class="auth-panel">
    <div class="auth-card">
      <a href="<?= url() ?>"><img class="logo" src="<?= asset('img/logo.png') ?>" alt="TUV EAST"></a>
      <h2>Sign in</h2>
      <p class="lead">Use your TUV EAST account to open your portal.</p>
      <?php foreach (flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= icon('alert') ?><div><?= e($f['message']) ?></div></div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('login') ?>" class="stack" data-no-lock>
        <?= Csrf::field() ?>
        <div class="field"><label for="email">Email</label>
          <input class="input" id="email" name="email" type="email" autocomplete="username" required value="<?= e(old('email')) ?>" autofocus></div>
        <div class="field"><label for="password">Password</label>
          <input class="input" id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in <?= icon('arrow-right', 'sm') ?></button>
      </form>
      <?php if (config('env') === 'local'): ?>
      <div class="demo-box">
        Demo accounts (local only) — pick one, password is in <span class="mono">database/install.php</span>.
        <div class="demo-grid">
          <button type="button" data-demo="admin@tuveast.test"><b>Super Admin</b>admin@tuveast.test</button>
  <button type="button" data-demo="ops.admin@tuveast.test"><b>Admin</b>ops.admin@tuveast.test</button>
          <button type="button" data-demo="omar.khalid@tuveast.test"><b>Inspector</b>omar.khalid@…</button>
          <button type="button" data-demo="sara.nasser@tuveast.test"><b>QC Reviewer</b>sara.nasser@…</button>
          <button type="button" data-demo="customer@alnoor.test"><b>Customer</b>customer@alnoor.test</button>
        </div>
      </div>
      <?php endif; ?>
      <p class="small muted mt-3">Need an inspection? <a href="<?= url('request') ?>">Submit a request</a> · <a href="<?= url('verify') ?>">Verify a certificate</a></p>
    </div>
  </section>
</div>
