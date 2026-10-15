<?php
/** Admin card check. @var string $q @var bool $searched @var ?array $m @var array $history @var ?array $profile */
$c = $m['card'] ?? null;
$state = $m['state'] ?? null;
$msg = [
    'valid'    => ['ok', 'check', 'Valid card', 'Genuine, handed over and within its validity period.'],
    'pending'  => ['bad', 'clock', 'Not yet handed over', 'The card was printed but is not marked as handed over to the inspector, so it does not verify yet.'],
    'expired'  => ['bad', 'alert', 'Card expired', 'The validity period has ended.'],
    'revoked'  => ['bad', 'x', 'Card revoked', $c['revoked_reason'] ?? ''],
    'inactive' => ['bad', 'x', 'Holder inactive', 'The holder\'s user account is deactivated.'],
][$state] ?? ['bad', 'alert', 'No card found', 'Nothing matches this ID code or QR link. The card may be forged.'];
?>
<style><?= file_get_contents(ROOT_PATH . '/assets/css/idcard.css') ?>
.idc * { box-sizing: content-box; }
.idc-faces { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 18px; background: #eef1f6; border-radius: 12px; }
.idc-faces .cap { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
.idc-faces .idc { zoom: 1.2; box-shadow: 0 2px 10px rgba(15,23,42,.18); }
#scan-video { width: 100%; max-width: 420px; border-radius: 12px; background: #000; display: block; margin: 12px auto 0; }
@media (max-width: 760px) { .idc-faces { padding: 10px; } .idc-faces .idc { zoom: .95; } }
</style>
<div class="page-head">
  <div class="ph-text"><h1>Validate ID card</h1><div class="sub">Scan the QR on the back of a card, paste its link, or type the ID code to see the full record.</div></div>
</div>
<div class="card mb-2"><div class="card-b">
  <form class="row wrap" method="get" action="<?= url('admin/cards/validate') ?>" style="gap:10px" id="validate-form">
    <input class="input mono" style="flex:1;min-width:240px" name="q" id="validate-q" value="<?= e($q) ?>" placeholder="ID code (TE-IC-2026-0001) or verification link" autofocus required>
    <button type="button" class="btn btn-secondary" id="scan-btn" hidden><?= icon('camera', 'sm') ?> Scan QR</button>
    <button class="btn btn-primary"><?= icon('search', 'sm') ?> Validate</button>
  </form>
  <video id="scan-video" playsinline muted hidden></video>
  <p class="small muted mt-1" id="scan-note" hidden>Point the camera at the QR code on the back of the card.</p>
</div></div>

<?php if ($searched): ?>
  <div class="verify-result <?= $msg[0] ?> mb-2">
    <div class="vr-ic"><?= icon($msg[1], 'lg') ?></div>
    <div style="flex:1"><h3 style="font-size:18px"><?= e($msg[2]) ?></h3><p class="mt-1"><?= e($msg[3]) ?></p></div>
    <?php if ($c): ?><div class="row wrap" style="gap:8px">
      <a class="btn btn-secondary btn-sm" href="<?= url('admin/cards/' . $c['id']) ?>"><?= icon('eye', 'sm') ?> Open card</a>
      <a class="btn btn-secondary btn-sm" href="<?= url('verify/card/' . $c['verify_token']) ?>" target="_blank" rel="noopener"><?= icon('external', 'sm') ?> Public page</a>
    </div><?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($c): ?>
  <div class="card mb-2"><div class="card-b">
    <div class="idc-faces">
      <div><div class="cap">Front</div><?php View::render('partials/id_card', ['m' => $m, 'side' => 'front'], null); ?></div>
      <div><div class="cap">Back</div><?php View::render('partials/id_card', ['m' => $m, 'side' => 'back'], null); ?></div>
    </div>
  </div></div>
  <div class="grid grid-2">
    <div class="card"><div class="card-h"><h2><?= icon('qr', 'sm') ?> Card</h2></div><div class="card-b">
      <dl class="kv">
        <dt>ID code</dt><dd class="mono"><?= e($c['card_no']) ?></dd>
        <dt>Status</dt><dd><?= InspectorCardService::badge($state) ?></dd>
        <dt>Name on card</dt><dd><?= e($c['holder_name']) ?></dd>
        <dt>Iqama / ID No.</dt><dd class="mono"><?= e($c['id_number'] ?: '—') ?></dd>
        <dt>Company</dt><dd><?= e($c['company']) ?></dd>
        <dt>Title</dt><dd><?= e($c['title']) ?></dd>
        <dt>Certification</dt><dd><?= e($c['certification'] ?: '—') ?></dd>
        <dt>Assessor</dt><dd><?= e($c['assessor'] ?: '—') ?></dd>
        <dt>Job Ref</dt><dd class="mono"><?= e($c['job_ref'] ?: '—') ?></dd>
        <dt>Validity</dt><dd><?= e(fmt_date($c['issue_date'])) ?> – <?= e(fmt_date($c['expiry_date'])) ?></dd>
        <dt>Handed over</dt><dd><?= e($c['handed_over_at'] ? fmt_datetime($c['handed_over_at']) : 'Not yet') ?></dd>
        <?php if ($c['revoked_reason']): ?><dt>Revoked</dt><dd><?= e($c['revoked_reason']) ?></dd><?php endif; ?>
      </dl>
    </div></div>
    <?php if (!$profile): ?>
    <div class="card"><div class="card-h"><h2><?= icon('building', 'sm') ?> Holder</h2></div><div class="card-b">
      <dl class="kv">
        <dt>Type</dt><dd>Client worker (no login)</dd>
        <dt>Company</dt><dd><?= $c['client_id'] ? '<a href="' . url('admin/clients/' . $c['client_id']) . '">' . e($c['client_name']) . '</a>' : e($c['company']) ?></dd>
        <dt>Iqama / ID No.</dt><dd class="mono"><?= e($c['id_number'] ?: '—') ?></dd>
      </dl>
    </div></div>
    <?php else: ?>
    <div class="card"><div class="card-h"><h2><?= icon('user', 'sm') ?> Holder account</h2></div><div class="card-b">
      <dl class="kv">
        <dt>User</dt><dd><a href="<?= url('admin/users/' . $c['user_id']) ?>"><?= e($profile['name']) ?></a> <?= badge($profile['role']) ?></dd>
        <dt>Account</dt><dd><?= badge($profile['status']) ?></dd>
        <dt>Email</dt><dd><?= e($profile['email']) ?></dd>
        <dt>Phone</dt><dd><?= e($profile['phone'] ?: '—') ?></dd>
        <dt>Employee No.</dt><dd><?= e($profile['employee_no'] ?: '—') ?></dd>
        <dt>Qualification</dt><dd><?= e($profile['qualification'] ?: '—') ?><?= $profile['cert_no'] ? ' · ' . e($profile['cert_no']) : '' ?></dd>
        <dt>Qualification expiry</dt><dd><?= e($profile['cert_expiry'] ? fmt_date($profile['cert_expiry']) : '—') ?></dd>
        <dt>Base</dt><dd><?= e($profile['base_location'] ?: '—') ?></dd>
      </dl>
    </div></div>
    <?php endif; ?>
  </div>
  <div class="card mt-2"><div class="card-h"><h2><?= icon('history', 'sm') ?> All cards of this holder</h2></div>
    <div class="table-wrap"><table class="t stack-sm">
      <thead><tr><th>ID code</th><th>Validity</th><th>Handed over</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($history as $h): $h['user_status'] = $c['user_status']; ?>
        <tr class="row-link" data-href="<?= url('admin/cards/' . $h['id']) ?>">
          <td data-label="ID code" class="mono"><?= e($h['card_no']) ?><?= (int) $h['id'] === (int) $c['id'] ? ' <span class="pill">scanned</span>' : '' ?></td>
          <td data-label="Validity" class="nowrap"><?= e(fmt_date($h['issue_date'])) ?> – <?= e(fmt_date($h['expiry_date'])) ?></td>
          <td data-label="Handed over" class="sub"><?= e($h['handed_over_at'] ? fmt_datetime($h['handed_over_at']) . ($h['handed_by'] ? ' · ' . $h['handed_by'] : '') : '—') ?></td>
          <td data-label="Status"><?= InspectorCardService::badge(InspectorCardService::state($h)) ?><?= $h['revoked_reason'] ? '<div class="sub">' . e($h['revoked_reason']) . '</div>' : '' ?></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div></div>
<?php endif; ?>
<script>
(function () {
  // Camera scanning where the browser has a built-in QR detector (Chrome / Edge / Android); otherwise type or paste.
  if (!('BarcodeDetector' in window) || !navigator.mediaDevices) return;
  const btn = document.getElementById('scan-btn'), video = document.getElementById('scan-video'), note = document.getElementById('scan-note');
  btn.hidden = false;
  let stream = null;
  btn.addEventListener('click', async () => {
    if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; video.hidden = note.hidden = true; return; }
    try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }); } catch (e) { alert('Camera not available: ' + e.message); return; }
    video.srcObject = stream; video.hidden = note.hidden = false; await video.play();
    const det = new BarcodeDetector({ formats: ['qr_code'] });
    const tick = async () => {
      if (!stream) return;
      const codes = await det.detect(video).catch(() => []);
      if (codes.length) {
        stream.getTracks().forEach((t) => t.stop()); stream = null;
        document.getElementById('validate-q').value = codes[0].rawValue;
        document.getElementById('validate-form').submit();
        return;
      }
      requestAnimationFrame(tick);
    };
    tick();
  });
})();
</script>
