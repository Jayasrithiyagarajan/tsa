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
      <?php if (isset($sticker) && !$cert): ?>
        <div class="verify-result bad"><div class="vr-ic"><?= icon($sticker['sticker_no'] ? 'info' : 'alert', 'lg') ?></div>
          <div><?php if (!$sticker['sticker_no']): ?>
            <h3 style="font-size:18px">Unknown sticker</h3><p class="mt-1">This QR code does not belong to a TUV EAST sticker. Contact <?= e(setting('company_email')) ?>.</p>
          <?php elseif ($sticker['status'] === 'void'): ?>
            <h3 style="font-size:18px">Sticker <?= e($sticker['sticker_no']) ?> is void</h3><p class="mt-1">This sticker has been cancelled and must not be relied on.</p>
          <?php else: ?>
            <h3 style="font-size:18px">Sticker <?= e($sticker['sticker_no']) ?> — not yet issued</h3><p class="mt-1">This sticker is registered, but no certificate has been issued for it yet. The equipment is not certified until TUV EAST QC approves the inspection.</p>
          <?php endif; ?></div></div>
      <?php elseif ($cert && $cert['status'] === 'issued'):
          $expired = $cert['next_inspection_date'] && $cert['next_inspection_date'] < date('Y-m-d'); ?>
        <div class="verify-result <?= $expired || $cert['result'] !== 'accepted' ? 'bad' : 'ok' ?>">
          <div class="vr-ic"><?= icon($expired ? 'alert' : 'check', 'lg') ?></div>
          <div style="flex:1">
            <h3 style="font-size:18px"><?= isset($sticker) && $sticker['sticker_no'] ? 'Sticker ' . e($sticker['sticker_no']) . ' · ' : '' ?><?= $expired ? 'Certificate expired' : ($cert['result'] === 'accepted' ? 'Valid certificate' : 'Inspection failed — do not use') ?></h3>
            <p class="muted mt-1">Issued by TUV EAST after QC review.</p>
            <dl class="kv mt-2">
              <dt>Certificate No.</dt><dd class="mono"><?= e($cert['certificate_no']) ?></dd>
              <?php if (!empty($cert['sticker_no'])): ?><dt>Sticker No.</dt><dd class="mono"><?= e($cert['sticker_no']) ?></dd><?php endif; ?>
              <dt>Job / Sub-job</dt><dd class="mono"><?= e($cert['job_no']) ?> · <?= e($cert['sub_job_no']) ?></dd>
              <dt>Client</dt><dd><?= e($cert['client_name']) ?></dd>
              <?php if (!empty($cert['location'])): ?><dt>Location</dt><dd><?= e($cert['location']) ?></dd><?php endif; ?>
              <dt>Inspection</dt><dd><?= e($cert['type_name']) ?></dd>
              <dt>Equipment</dt><dd><?= e($cert['eq_description']) ?> <?= $cert['equipment_no'] ? '· ' . e($cert['equipment_no']) : '' ?></dd>
              <?php if (!empty($cert['eq_serial_no'])): ?><dt>Serial No.</dt><dd><?= e($cert['eq_serial_no']) ?></dd><?php endif; ?>
              <?php if (!empty($cert['manufacturer']) || !empty($cert['eq_model'])): ?><dt>Make / Model</dt><dd><?= e(trim(($cert['manufacturer'] ?? '') . ' ' . ($cert['eq_model'] ?? ''))) ?></dd><?php endif; ?>
              <?php if (!empty($cert['eq_capacity'])): ?><dt>Capacity / SWL</dt><dd><?= e($cert['eq_capacity']) ?></dd><?php endif; ?>
              <?php if (!empty($cert['eq_year'])): ?><dt>Year built</dt><dd><?= e($cert['eq_year']) ?></dd><?php endif; ?>
              <dt>Result</dt><dd><?= badge($cert['result'] ?? 'accepted') ?></dd>
              <dt>Inspection date</dt><dd><?= e(fmt_date($cert['inspection_date'])) ?></dd>
              <dt>Valid until</dt><dd><?= e(fmt_date($cert['next_inspection_date'])) ?></dd>
              <?php if (!empty($cert['inspector_name'])): ?><dt>Inspected by</dt><dd><?= e($cert['inspector_name']) ?></dd><?php endif; ?>
              <?php if (!empty($cert['reviewer_name'])): ?><dt>QC approved by</dt><dd><?= e($cert['reviewer_name']) ?></dd><?php endif; ?>
              <?php if (!empty($cert['approved_at'])): ?><dt>QC approved on</dt><dd><?= e(date('d-M-Y H:i', strtotime($cert['approved_at']))) ?></dd><?php endif; ?>
            </dl>
            <?php if (!empty($docs)): ?>
              <div class="mt-2"><div class="small muted" style="margin-bottom:6px">Documents</div>
                <div class="row wrap" style="gap:8px">
                  <?php foreach ($docs as $d): ?><a class="btn btn-secondary btn-sm" href="<?= e($d['url']) ?>" target="_blank" rel="noopener"><?= icon($d['icon'], 'sm') ?> <?= e($d['label']) ?></a><?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
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
