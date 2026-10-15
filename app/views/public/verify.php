<section class="section" style="padding-top:40px">
  <div class="pub-wrap" style="max-width:760px">
    <div class="eyebrow">Certificate verification</div>
    <h2>Verify a TUV EAST certificate</h2>
    <p class="lead">Scan the QR code on the certificate or sticker, or enter the certificate number.</p>
    <form class="row mt-2 wrap" method="get" action="<?= url('verify') ?>">
      <input class="input" style="flex:1;min-width:240px" name="certificate_no" value="<?= e($q) ?>" placeholder="Certificate No. e.g. 2026-22108-0001-001" required>
      <button class="btn btn-primary" style="height:40px"><?= icon('search', 'sm') ?> Verify</button>
    </form>
    <?php if ($searched): ?>
      <div class="mt-3">
      <?php if ($cert && $cert['status'] === 'issued'):
          $expired = $cert['next_inspection_date'] && $cert['next_inspection_date'] < date('Y-m-d'); ?>
        <div class="verify-result <?= $expired ? 'bad' : 'ok' ?>">
          <div class="vr-ic"><?= icon($expired ? 'alert' : 'check', 'lg') ?></div>
          <div style="flex:1">
            <h3 style="font-size:18px"><?= $expired ? 'Certificate expired' : 'Valid certificate' ?></h3>
            <p class="muted mt-1">Issued by TUV EAST after QC review.</p>
            <dl class="kv mt-2">
              <dt>Certificate No.</dt><dd class="mono"><?= e($cert['certificate_no']) ?></dd>
              <dt>Client</dt><dd><?= e($cert['client_name']) ?></dd>
              <dt>Inspection</dt><dd><?= e($cert['type_name']) ?></dd>
              <dt>Equipment</dt><dd><?= e($cert['eq_description']) ?> <?= $cert['equipment_no'] ? '· ' . e($cert['equipment_no']) : '' ?></dd>
              <dt>Result</dt><dd><?= badge($cert['result'] ?? 'accepted') ?></dd>
              <dt>Inspection date</dt><dd><?= e(fmt_date($cert['inspection_date'])) ?></dd>
              <dt>Valid until</dt><dd><?= e(fmt_date($cert['next_inspection_date'])) ?></dd>
            </dl>
          </div>
        </div>
      <?php elseif ($cert && $cert['status'] === 'revoked'): ?>
        <div class="verify-result bad"><div class="vr-ic"><?= icon('x', 'lg') ?></div>
          <div><h3 style="font-size:18px">Certificate revoked</h3><p class="mt-1">Certificate <span class="mono"><?= e($cert['certificate_no']) ?></span> has been revoked and is no longer valid.</p></div></div>
      <?php else: ?>
        <div class="verify-result bad"><div class="vr-ic"><?= icon('alert', 'lg') ?></div>
          <div><h3 style="font-size:18px">No issued certificate found</h3><p class="mt-1">Check the number and try again. If the problem persists contact <?= e(setting('company_email')) ?>.</p></div></div>
      <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
