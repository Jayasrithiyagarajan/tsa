<section class="hero">
  <div class="pub-wrap">
    <div>
      <div class="eyebrow" style="color:#9fb4ee">Third-party inspection · NDT · Lifting equipment</div>
      <h1>Certified inspection, tracked from request to certificate.</h1>
      <p>Request an inspection online, follow your job in real time, and download QC-approved certificates the moment they are issued — every certificate carries a QR code anyone can verify.</p>
      <div class="cta">
        <a class="btn btn-primary btn-lg" href="<?= url('request') ?>"><?= icon('send') ?> Request an inspection</a>
        <a class="btn btn-secondary btn-lg" href="<?= url('login') ?>"><?= icon('user') ?> Customer portal</a>
      </div>
    </div>
    <form class="verify-card" action="<?= url('verify') ?>" method="get">
      <h3><?= icon('qr') ?> Verify a certificate</h3>
      <p class="muted small mt-1">Enter the certificate number printed on the report or sticker.</p>
      <div class="field mt-2"><input class="input" name="certificate_no" placeholder="e.g. 2026-22108-0001-001" required></div>
      <button class="btn btn-primary btn-block mt-1">Check validity</button>
    </form>
  </div>
</section>

<section class="section" id="services">
  <div class="pub-wrap">
    <h2>Inspection services</h2>
    <p class="lead">Accredited inspection by qualified inspectors, every result reviewed by a Level III QC engineer before the certificate is issued.</p>
    <div class="portal-cards">
      <?php $tones = ['Lifting Equipment' => 't-blue', 'NDT' => 't-violet'];
      foreach ($types as $t): ?>
        <div class="portal-card">
          <div class="pc-ic <?= $tones[$t['category']] ?? 't-teal' ?>"><?= icon($t['category'] === 'NDT' ? 'sparkle' : 'truck') ?></div>
          <h3><?= e($t['name']) ?></h3>
          <p><?= e($t['category']) ?> · <?= e($t['standard_ref']) ?><br>Certificate valid <?= (int) $t['validity_months'] ?> months</p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="how" style="background:#fff;border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
  <div class="pub-wrap">
    <h2>How it works</h2>
    <p class="lead">One workflow, four portals — so nothing is lost between the phone call and the signed certificate.</p>
    <div class="flow-strip">
      <div><b>STEP 1</b><h4>Request</h4><p>Website form, email or phone — every request is logged with a reference number.</p></div>
      <div><b>STEP 2</b><h4>Assignment</h4><p>Admin opens the job, splits it into sub-jobs and assigns qualified inspectors.</p></div>
      <div><b>STEP 3</b><h4>Inspection</h4><p>Inspectors complete the controlled checklist and certificate data on site.</p></div>
      <div><b>STEP 4</b><h4>QC review</h4><p>A QC reviewer checks every record and approves or returns it for rework.</p></div>
      <div><b>STEP 5</b><h4>Certificate</h4><p>Certificate and sticker are issued — download from the customer portal.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="pub-wrap">
    <h2>Portals</h2>
    <div class="portal-cards">
      <a class="portal-card" href="<?= url('login') ?>"><div class="pc-ic t-indigo"><?= icon('grid') ?></div><h3>Admin</h3><p>Requests, jobs, assignment, templates, stickers and audit.</p></a>
      <a class="portal-card" href="<?= url('login') ?>"><div class="pc-ic t-teal"><?= icon('clipboard') ?></div><h3>Inspector</h3><p>Assigned jobs, mobile checklists, photos and certificate data.</p></a>
      <a class="portal-card" href="<?= url('login') ?>"><div class="pc-ic t-violet"><?= icon('shield') ?></div><h3>QC</h3><p>Review queue, approve or return for rework, issue certificates.</p></a>
      <a class="portal-card" href="<?= url('login') ?>"><div class="pc-ic t-blue"><?= icon('building') ?></div><h3>Customer</h3><p>Track jobs, download certificates and watch due dates.</p></a>
    </div>
  </div>
</section>
