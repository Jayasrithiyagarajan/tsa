<?php
/** Public page opened by the QR code on an inspector ID card. @var ?array $m */
$c = $m['card'] ?? null;
$state = $m['state'] ?? null;
$msg = [
    'valid'    => ['ok', 'check', 'Valid inspector ID card', 'This card is genuine and active. The holder is an authorized ' . ($c ? e(mb_strtolower($c['title'])) : 'inspector') . ' of TUV EAST.'],
    'expired'  => ['bad', 'alert', 'ID card expired', 'This card expired on ' . ($c ? e(fmt_date($c['expiry_date'])) : '') . '. Do not accept it for site access — ask the holder for a current card.'],
    'revoked'  => ['bad', 'x', 'ID card revoked', 'This card has been withdrawn by TUV EAST and is no longer valid.'],
    'pending'  => ['bad', 'clock', 'ID card not yet issued', 'This card has not been issued to its holder yet, so it is not valid for site access.'],
    'inactive' => ['bad', 'x', 'Holder no longer authorized', 'The holder of this card is no longer an active TUV EAST inspector.'],
][$state] ?? ['bad', 'alert', 'ID card not found', 'This QR code does not match any TUV EAST inspector card. The card may be forged.'];
?>
<style><?= file_get_contents(ROOT_PATH . '/assets/css/idcard.css') ?>
.idc * { box-sizing: content-box; }
.idc-faces { display: flex; flex-wrap: wrap; gap: 24px; justify-content: center; margin-top: 24px; }
.idc-faces .cap { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
.idc-faces .idc { zoom: 1.3; box-shadow: 0 4px 18px rgba(15,23,42,.18); }
.idc-faces.void .idc { filter: grayscale(1); opacity: .55; }
@media (max-width: 760px) { .idc-faces .idc { zoom: 1; } }
</style>
<section class="section" style="padding-top:40px">
  <div class="pub-wrap" style="max-width:860px">
    <div class="eyebrow">Inspector identity verification</div>
    <h2>TUV EAST inspector ID card</h2>
    <div class="verify-result <?= $msg[0] ?> mt-2">
      <div class="vr-ic"><?= icon($msg[1], 'lg') ?></div>
      <div style="flex:1">
        <h3 style="font-size:18px"><?= e($msg[2]) ?></h3>
        <p class="mt-1"><?= $msg[3] ?></p>
        <?php if ($c): ?>
        <dl class="kv mt-2">
          <dt>Name</dt><dd><?= e($c['holder_name']) ?></dd>
          <dt>ID code</dt><dd class="mono"><?= e($c['card_no']) ?></dd>
          <dt>Title</dt><dd><?= e($c['title']) ?></dd>
          <dt>Company</dt><dd><?= e($c['company']) ?></dd>
          <dt>Valid</dt><dd><?= e(fmt_date($c['issue_date'])) ?> – <?= e(fmt_date($c['expiry_date'])) ?></dd>
          <?php if ($state === 'revoked' && $c['revoked_reason']): ?><dt>Reason</dt><dd><?= e($c['revoked_reason']) ?></dd><?php endif; ?>
          <dt>Checked</dt><dd><?= e(fmt_datetime(now())) ?></dd>
        </dl>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($c): ?>
      <p class="muted small mt-2">Compare the photo and details below with the physical card and the person presenting it.</p>
      <div class="idc-faces <?= $state === 'valid' ? '' : 'void' ?>">
        <div><div class="cap">Front</div><?php View::render('partials/id_card', ['m' => $m, 'side' => 'front', 'public' => true], null); ?></div>
        <div><div class="cap">Back</div><?php View::render('partials/id_card', ['m' => $m, 'side' => 'back', 'public' => true], null); ?></div>
      </div>
    <?php endif; ?>
    <p class="muted small mt-3">Questions? Contact <?= e(setting('company_email')) ?><?= setting('company_phone') ? ' · ' . e(setting('company_phone')) : '' ?>.</p>
  </div>
</section>
