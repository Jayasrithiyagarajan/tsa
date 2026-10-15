<?php
$tab = fn ($s, $label, $n) => '<a class="' . ($status === $s ? 'on' : '') . '" href="' . url('admin/cards', array_filter(['status' => $s, 'type' => $type])) . '">' . e($label) . '<span class="n">' . (int) $n . '</span></a>';
$ttab = fn ($t, $label, $n) => '<a class="' . ($type === $t ? 'on' : '') . '" href="' . url('admin/cards', array_filter(['type' => $t, 'status' => $status])) . '">' . e($label) . '<span class="n">' . (int) $n . '</span></a>';
?>
<div class="page-head">
  <div class="ph-text"><h1>ID cards</h1><div class="sub">Cards for TUV EAST inspectors and for assessed client workers (riggers, operators …). The QR code on the back opens the card online with a live valid / expired / revoked message.</div></div>
  <div class="actions">
    <?php View::render('partials/export', [], null); ?>
    <?php if (Auth::isSuper()): ?>
    <button class="btn btn-secondary" data-modal="card-settings"><?= icon('settings', 'sm') ?> Card settings</button>
    <a class="btn btn-secondary" href="<?= url('admin/cards/new', ['type' => 'worker']) ?>"><?= icon('plus', 'sm') ?> Client worker card</a>
    <a class="btn btn-primary" href="<?= url('admin/cards/new') ?>"><?= icon('plus', 'sm') ?> Inspector card</a>
    <?php else: ?><a class="btn btn-secondary" href="<?= url('admin/cards/validate') ?>"><?= icon('shield', 'sm') ?> Validate card</a><?php endif; ?>
  </div>
</div>
<div class="kpis">
  <div class="kpi"><span class="k-ic t-amber"><?= icon('briefcase') ?></span><span><div class="k-v"><?= (int) $counts['pending'] ?></div><div class="k-l">Awaiting hand-over</div></span></div>
  <div class="kpi"><span class="k-ic t-green"><?= icon('check') ?></span><span><div class="k-v"><?= (int) $counts['valid'] ?></div><div class="k-l">Valid</div></span></div>
  <div class="kpi"><span class="k-ic t-amber"><?= icon('clock') ?></span><span><div class="k-v"><?= (int) $counts['expiring'] ?></div><div class="k-l">Expiring in 30 days</div></span></div>
  <div class="kpi"><span class="k-ic t-red"><?= icon('x') ?></span><span><div class="k-v"><?= (int) $counts['revoked'] ?></div><div class="k-l">Revoked</div></span></div>
</div>
<div class="card">
  <div class="toolbar">
    <div class="seg-tabs"><?= $tab('', 'All', array_sum([(int) $counts['pending'], (int) $counts['valid'], (int) $counts['expired'], (int) $counts['revoked']])) ?><?= $tab('pending', 'Awaiting hand-over', $counts['pending']) ?><?= $tab('valid', 'Valid', $counts['valid']) ?><?= $tab('expired', 'Expired', $counts['expired']) ?><?= $tab('revoked', 'Revoked', $counts['revoked']) ?></div>
    <div class="seg-tabs"><?= $ttab('', 'Everyone', array_sum($types)) ?><?= $ttab('inspector', 'Inspectors', $types['inspector'] ?? 0) ?><?= $ttab('worker', 'Client workers', $types['worker'] ?? 0) ?></div>
    <span class="grow"></span>
    <form class="search" method="get"><input type="hidden" name="status" value="<?= e($status) ?>"><input type="hidden" name="type" value="<?= e($type) ?>"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="ID code, name, Iqama, title…"></form>
  </div>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Holder</th><th>Company</th><th>ID code</th><th>Title</th><th>Issued</th><th>Expires</th><th>Handed over</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $c): $st = InspectorCardService::state($c); ?>
      <tr class="row-link" data-href="<?= url('admin/cards/' . $c['id']) ?>">
        <td data-label="Holder"><span class="person">
          <?php if ($c['photo_path']): ?><img src="<?= url('admin/cards/' . $c['id'] . '/photo') ?>" alt="" style="width:32px;height:40px;object-fit:cover;border-radius:6px;border:1px solid var(--border)"><?php else: ?><span class="avatar sm"><?= e(initials($c['holder_name'])) ?></span><?php endif; ?>
          <span><span class="p-n"><?= e($c['holder_name']) ?></span><br><span class="p-s"><?= e($c['id_number'] ?: $c['user_email']) ?></span></span></span></td>
        <td data-label="Company" class="small"><?= e($c['company']) ?><div class="sub"><?= $c['holder_type'] === 'worker' ? 'Client worker' : 'Inspector' ?></div></td>
        <td data-label="ID code" class="mono small"><?= e($c['card_no']) ?></td>
        <td data-label="Title" class="small"><?= e($c['title']) ?></td>
        <td data-label="Issued" class="nowrap"><?= e(fmt_date($c['issue_date'])) ?></td>
        <td data-label="Expires" class="nowrap"><?= e(fmt_date($c['expiry_date'])) ?></td>
        <td data-label="Handed over" class="nowrap sub"><?= e($c['handed_over_at'] ? fmt_date($c['handed_over_at']) : '—') ?></td>
        <td data-label="Status"><?= InspectorCardService::badge($st) ?>
          <?= $c['revoked_reason'] ? '<div class="sub">' . e($c['revoked_reason']) . '</div>' : '' ?></td>
        <td class="actions">
          <?php if (Auth::isSuper()): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/cards/' . $c['id'] . '/print', ['side' => 'front']) ?>" title="Print"><?= icon('printer', 'sm') ?></a><?php endif; ?>
          <a class="btn btn-ghost btn-sm" href="<?= url('verify/card/' . $c['verify_token']) ?>" target="_blank" rel="noopener" title="Open verification page"><?= icon('qr', 'sm') ?></a>
        </td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php if (!$rows): ?><?php View::render('partials/empty', ['icon' => 'user', 'title' => 'No ID cards yet', 'text' => 'Issue the first inspector ID card to print it and make it verifiable by QR code.'], null); ?><?php endif; ?>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
</div>
<dialog class="modal" id="card-settings"><form method="post" action="<?= url('admin/cards/settings') ?>" enctype="multipart/form-data"><?= Csrf::field() ?>
  <div class="m-h"><h3>Card settings</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-12"><label>Verification e-mails (back side)</label><input class="input" name="card_emails" value="<?= e(setting('card_emails', 'info@tuv-east.com - operations@tuv-east.com')) ?>"><div class="help">Separate with " - " or commas.</div></div>
    <div class="field col-12"><label>Footer address (back side)</label><input class="input" name="card_address" value="<?= e(setting('card_address', 'Main Office:  Eastern Province - Dhahran, King Abdulaziz Rd – Al Waleedi Business Tower, Saudi Arabia')) ?>"></div>
    <div class="field col-12"><label>Accreditation logos under the photo (PNG / JPG, about 3.5 : 1)</label><input class="input" type="file" name="card_logos" accept="image/png,image/jpeg,image/webp" style="padding-top:7px">
      <div class="help">Upload one image with your accreditation marks (e.g. UAF · LEEA · SASO). Without it the Saudi Accreditation mark from the company logo is used.</div>
      <?php if (setting('card_logos_path')): ?><label class="check mt-1"><input type="checkbox" name="reset_logos" value="1"> Remove uploaded logos and use the default</label><?php endif; ?></div>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Save</button></div>
</form></dialog>
