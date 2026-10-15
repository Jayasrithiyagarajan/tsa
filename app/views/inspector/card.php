<?php
/** Inspector's own ID card (only after hand-over) + ID photo upload. @var ?array $m @var ?string $photo @var bool $pending */
$c = $m['card'] ?? null;
?>
<style><?= file_get_contents(ROOT_PATH . '/assets/css/idcard.css') ?>
.idc * { box-sizing: content-box; }
.idc-faces { display: flex; flex-wrap: wrap; gap: 24px; justify-content: center; }
.idc-faces .cap { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
.idc-faces .idc { zoom: 1.3; box-shadow: 0 4px 18px rgba(15,23,42,.18); }
.id-photo { width: 120px; height: 160px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-2); object-fit: cover; display: grid; place-items: center; color: var(--muted); flex: none; }
@media (max-width: 760px) { .idc-faces .idc { zoom: 1; } }
</style>
<div class="page-head">
  <div class="ph-text"><h1>My ID card</h1><div class="sub">Show this to the client on site. Scanning the QR code opens the live verification page.</div></div>
  <?php if ($c): ?><div class="actions"><a class="btn btn-secondary" href="<?= url('verify/card/' . $c['verify_token']) ?>" target="_blank" rel="noopener"><?= icon('qr', 'sm') ?> Open verification page</a></div><?php endif; ?>
</div>
<?php if (!$c): ?>
  <div class="card mb-2"><?php View::render('partials/empty', ['icon' => 'user',
      'title' => $pending ? 'Your ID card is being prepared' : 'No ID card issued yet',
      'text' => $pending ? 'It will appear here as soon as the administrator hands the printed card to you.' : 'Upload your ID photo below; the administrator will then issue your card.'], null); ?></div>
<?php else: ?>
  <?php if ($m['state'] !== 'valid'): ?>
    <div class="alert alert-error"><?= icon('alert') ?><div>This card is <b><?= e($m['state']) ?></b><?= $c['revoked_reason'] ? ' — ' . e($c['revoked_reason']) : '' ?>. Contact the administrator for a new card.</div></div>
  <?php elseif ($c['expiry_date'] <= date('Y-m-d', strtotime('+30 days'))): ?>
    <div class="alert alert-warning"><?= icon('clock') ?><div>Your card expires on <?= e(fmt_date($c['expiry_date'])) ?>. Ask the administrator to renew it.</div></div>
  <?php endif; ?>
  <?php if ($pending): ?>
    <div class="alert alert-info"><?= icon('info') ?><div>A new card is being prepared for you. This card stays valid until the new one is handed over.</div></div>
  <?php endif; ?>
  <div class="card mb-2"><div class="card-b">
    <div class="idc-faces">
      <div><div class="cap">Front</div><?php View::render('partials/id_card', ['m' => $m, 'side' => 'front'], null); ?></div>
      <div><div class="cap">Back</div><?php View::render('partials/id_card', ['m' => $m, 'side' => 'back'], null); ?></div>
    </div>
  </div></div>
<?php endif; ?>

<div class="card" style="max-width:720px">
  <div class="card-h"><h2><?= icon('camera', 'sm') ?> ID photo</h2></div>
  <form class="card-b row wrap" style="gap:18px;align-items:flex-start" method="post" action="<?= url('inspector/card/photo') ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <?php if ($photo): ?><img class="id-photo" src="<?= url('id-photo/' . Auth::id()) ?>" alt="My ID photo" id="photo-preview">
    <?php else: ?><span class="id-photo" id="photo-preview"><?= icon('user', 'lg') ?></span><?php endif; ?>
    <div style="flex:1;min-width:220px">
      <p class="small muted">Passport-style photo: face centred, plain light background, portrait (3 : 4). JPG or PNG, up to <?= (int) config('upload_max_mb') ?> MB.
        <?= $c ? 'A new photo is used for your next card; the card you hold now does not change.' : '' ?></p>
      <input class="input mt-1" type="file" name="photo" id="photo-input" accept="image/jpeg,image/png,image/webp" required style="padding-top:7px">
      <button class="btn btn-primary mt-1"><?= icon('upload', 'sm') ?> <?= $photo ? 'Replace photo' : 'Upload photo' ?></button>
    </div>
  </form>
</div>
<script>
document.getElementById('photo-input').addEventListener('change', (e) => {
  const f = e.target.files[0]; if (!f) return;
  const img = document.createElement('img'); img.className = 'id-photo'; img.id = 'photo-preview'; img.alt = '';
  img.src = URL.createObjectURL(f);
  document.getElementById('photo-preview').replaceWith(img);
});
</script>
