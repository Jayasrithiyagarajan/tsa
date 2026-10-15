<?php
/** Bulk sticker stock: generate (Super Admin only) → print → allocate to inspectors. Stickers linked to jobs live on Project Stickers. */
$bulkTotal = (int) $counts['in_stock'] + (int) $counts['allocated'] + (int) $counts['void'];
$tab = fn ($s, $label) => '<a class="' . ($status === $s ? 'on' : '') . '" href="' . url('admin/stickers', array_filter(['status' => $s, 'inspector' => $insp ?: null])) . '">' . e($label) . '<span class="n">' . ($s ? (int) $counts[$s] : $bulkTotal) . '</span></a>';
$swatch = fn ($c) => '<i style="display:inline-block;width:10px;height:10px;border-radius:3px;flex:none;background:' . ($c === 'red' ? 'var(--red)' : '#1d4fb8') . '"></i>';
$inspOptions = function (bool $blank = true) use ($inspectors) {
    $h = $blank ? '<option value="">— Choose inspector —</option>' : '';
    foreach ($inspectors as $i) $h .= '<option value="' . (int) $i['id'] . '">' . e($i['name']) . ' (' . (int) $i['in_hand'] . ' in hand)</option>';
    return $h;
};
$pad = fn ($n) => str_pad((string) $n, 6, '0', STR_PAD_LEFT);
$blueSeries = null; $redSeries = null;
foreach ($series as $sr) { if ($sr['color'] === 'red') $redSeries ??= $sr; else $blueSeries ??= $sr; }
$printFrom = (string) input('print_from', '');
?>
<div class="page-head">
  <div class="ph-text"><h1>Sticker stock</h1><div class="sub">Bulk stickers generated here (Super Admin only) → printed → allocated to inspectors. Once an inspector links a sticker to a job it moves to <a href="<?= url('admin/project-stickers') ?>">Project Stickers</a> and is closed.</div></div>
  <div class="actions">
    <?php View::render('partials/export', [], null); ?>
    <?php if (Auth::isSuper()): ?>
    <button class="btn btn-secondary" data-modal="print"><?= icon('printer', 'sm') ?> Print</button>
    <button class="btn btn-secondary" data-modal="allocate"><?= icon('send', 'sm') ?> Allocate</button>
    <button class="btn btn-primary" data-modal="batch"><?= icon('plus', 'sm') ?> Generate batch</button>
    <?php else: ?><span class="pill">View only</span><?php endif; ?>
  </div>
</div>

<?php if ($printFrom !== ''): ?>
  <div class="alert alert-success"><?= icon('check') ?><div>Stickers <b class="mono"><?= e($printFrom) ?></b> … <b class="mono"><?= e((string) input('print_to', '')) ?></b> generated.
    <a class="btn btn-primary btn-sm" style="margin-left:8px" target="_blank" href="<?= url('admin/stickers/print', ['from' => $printFrom, 'to' => (string) input('print_to', '')]) ?>"><?= icon('printer', 'sm') ?> Print these stickers</a></div></div>
<?php endif; ?>

<div class="kpis">
  <a class="kpi" href="<?= url('admin/stickers', ['status' => 'in_stock']) ?>"><span class="k-ic t-gray"><?= icon('layers') ?></span><span><div class="k-v"><?= (int) $counts['in_stock'] ?></div><div class="k-l">In office stock</div></span></a>
  <a class="kpi" href="<?= url('admin/stickers', ['status' => 'allocated']) ?>"><span class="k-ic t-amber"><?= icon('users') ?></span><span><div class="k-v"><?= (int) $counts['allocated'] ?></div><div class="k-l">Allocated to inspectors</div></span></a>
  <a class="kpi" href="<?= url('admin/project-stickers') ?>"><span class="k-ic t-blue"><?= icon('lock') ?></span><span><div class="k-v"><?= (int) $counts['linked'] ?></div><div class="k-l">Linked to projects</div><div class="k-t">See Project Stickers</div></span></a>
  <a class="kpi" href="<?= url('admin/stickers', ['status' => 'void']) ?>"><span class="k-ic t-red"><?= icon('x') ?></span><span><div class="k-v"><?= (int) $counts['void'] ?></div><div class="k-l">Void (unused)</div></span></a>
</div>

<div class="grid grid-2 mb-2">
  <div class="card">
    <div class="card-h"><h2><?= icon('layers', 'sm') ?> Sticker series</h2></div>
    <?php if ($series): ?>
    <div class="table-wrap"><table class="t">
      <thead><tr><th>Series</th><th>Generated</th><th>In stock</th><th>Next to allocate</th><th>Next to generate</th></tr></thead>
      <tbody><?php foreach ($series as $sr): ?>
        <tr>
          <td><span class="row" style="gap:8px"><?= $swatch($sr['color']) ?><span class="mono strong"><?= e($sr['prefix']) ?></span></span></td>
          <td><?= (int) $sr['total'] ?></td>
          <td><?= (int) $sr['in_stock'] ?></td>
          <td class="mono small"><?= e($sr['next_free'] ?: '—') ?></td>
          <td class="mono small"><?= e($sr['prefix'] . $pad($sr['next_generate'])) ?></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php else: View::render('partials/empty', ['icon' => 'layers', 'title' => 'No stickers generated yet', 'text' => ''], null); endif; ?>
  </div>
  <div class="card">
    <div class="card-h"><h2><?= icon('users', 'sm') ?> Held by inspectors</h2></div>
    <div class="table-wrap"><table class="t">
      <thead><tr><th>Inspector</th><th>Blue</th><th>Red</th><th>Used</th><th></th></tr></thead>
      <tbody><?php foreach ($inspectors as $i): ?>
        <tr>
          <td class="strong"><?= e($i['name']) ?><div class="sub"><?= $i['last_allocated'] ? 'Last allocation ' . e(fmt_date($i['last_allocated'])) : 'Nothing allocated yet' ?></div></td>
          <td><?= (int) $i['in_hand'] - (int) $i['in_hand_red'] ?></td>
          <td><?= (int) $i['in_hand_red'] ?></td>
          <td><a href="<?= url('admin/project-stickers', ['inspector' => $i['id']]) ?>"><?= (int) $i['used'] ?></a></td>
          <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('admin/stickers', ['inspector' => $i['id'], 'status' => 'allocated']) ?>">View</a></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="seg-tabs"><?= $tab('', 'All') ?><?= $tab('allocated', 'Allocated') ?><?= $tab('in_stock', 'In stock') ?><?= $tab('void', 'Void') ?></div>
    <span class="grow"></span>
    <form class="row" method="get" style="gap:8px" data-autosubmit>
      <input type="hidden" name="status" value="<?= e($status) ?>">
      <select class="select" name="inspector" style="width:auto"><option value="">All inspectors</option>
        <?php foreach ($inspectors as $i): ?><option value="<?= (int) $i['id'] ?>" <?= $insp === (int) $i['id'] ? 'selected' : '' ?>><?= e($i['name']) ?></option><?php endforeach; ?></select>
      <div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Sticker or batch…"></div>
    </form>
  </div>

  <form method="post" id="bulk" action="<?= url('admin/stickers/allocate') ?>"><?= Csrf::field() ?>
    <div class="toolbar" style="border-top:1px solid var(--border);gap:8px;flex-wrap:wrap"<?= Auth::isSuper() ? '' : ' hidden' ?>>
      <span class="small muted"><b data-sel>0</b> selected</span>
      <select class="select" name="inspector_id" style="width:auto"><?= $inspOptions() ?></select>
      <button class="btn btn-secondary btn-sm" data-need-insp><?= icon('send', 'sm') ?> Allocate selected</button>
      <button class="btn btn-ghost btn-sm" formaction="<?= url('admin/stickers/return') ?>" data-confirm-btn="Return the selected unused stickers to office stock?"><?= icon('rotate', 'sm') ?> Return to stock</button>
      <button class="btn btn-ghost btn-sm" formaction="<?= url('admin/stickers/print') ?>" formtarget="_blank"><?= icon('printer', 'sm') ?> Print selected</button>
    </div>
    <div class="table-wrap"><table class="t stack-sm">
      <thead><tr><th style="width:32px"><input type="checkbox" data-all title="Select all on this page"></th><th>Sticker</th><th>Batch</th><th>Generated</th><th>Allocated to</th><th>Status</th><th></th></tr></thead>
      <tbody><?php foreach ($rows as $s): ?>
        <tr>
          <td><?php if ($s['status'] !== 'void'): ?><input type="checkbox" name="ids[]" value="<?= (int) $s['id'] ?>"><?php endif; ?></td>
          <td data-label="Sticker"><span class="row" style="gap:8px"><?= $swatch($s['color']) ?><span class="mono strong"><?= e($s['sticker_no']) ?></span></span></td>
          <td data-label="Batch" class="sub"><?= e($s['batch_no'] ?: '—') ?></td>
          <td data-label="Generated" class="small nowrap"><?= e(fmt_date($s['created_at'])) ?></td>
          <td data-label="Allocated to" class="small"><?php if ($s['status'] === 'allocated'): ?><?= e($s['inspector_name']) ?><div class="sub">since <?= e(fmt_date($s['allocated_at'])) ?><?= $s['allocated_by_name'] ? ' · by ' . e($s['allocated_by_name']) : '' ?></div><?php else: ?><span class="muted">Office</span><?php endif; ?></td>
          <td data-label="Status"><?= badge($s['status']) ?><?= $s['void_reason'] ? '<div class="sub">' . e($s['void_reason']) . '</div>' : '' ?></td>
          <td class="actions">
            <?php if (Workflow::can('sticker', $s['status'], 'void') && Auth::isSuper()): ?>
              <button type="button" class="btn btn-ghost btn-sm" style="color:var(--red)" title="Void" data-void="<?= url('admin/stickers/' . $s['id'] . '/void') ?>" data-no="<?= e($s['sticker_no']) ?>"><?= icon('x', 'sm') ?></button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </form>
  <?php if (!$rows) View::render('partials/empty', ['icon' => 'tag', 'title' => 'No stickers here', 'text' => 'Generate a batch to get started.'], null); ?>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
</div>
<form method="post" id="void-form"><?= Csrf::field() ?><input type="hidden" name="reason"></form>

<dialog class="modal" id="batch"><form method="post" action="<?= url('admin/stickers') ?>" data-gen><?= Csrf::field() ?>
  <div class="m-h"><h3>Generate sticker batch</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-6"><label>Colour</label><select class="select" name="color"><option value="green">Blue — Accepted</option><option value="red">Red — Failed</option></select></div>
    <div class="field col-6"><label>Prefix</label><input class="input mono" name="prefix" value="<?= e($blueSeries['prefix'] ?? 'TE-') ?>" required style="text-transform:uppercase"></div>
    <div class="field col-4"><label>From No.</label><input class="input" type="number" min="1" name="from" value="<?= (int) ($blueSeries['next_generate'] ?? 1) ?>" required></div>
    <div class="field col-4"><label>Quantity</label><input class="input" type="number" min="1" max="2000" name="qty" value="50" required></div>
    <div class="field col-4"><label>To No.</label><input class="input" type="number" min="1" name="to" value="<?= (int) ($blueSeries['next_generate'] ?? 1) + 49 ?>" required></div>
    <div class="field col-6"><label>Batch No.</label><input class="input" name="batch_no" placeholder="B-<?= date('Y') ?>-02"></div>
    <div class="field col-6"><label>Allocate straight to (optional)</label><select class="select" name="inspector_id"><option value="">— Keep in office stock —</option><?= $inspOptions(false) ?></select></div>
    <label class="check col-12"><input type="checkbox" name="print" value="1" checked> Open the print sheet after generating</label>
    <p class="col-12 small muted" data-gen-hint></p>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Generate</button></div>
</form></dialog>

<dialog class="modal" id="allocate"><form method="post" action="<?= url('admin/stickers/allocate') ?>" data-alloc><?= Csrf::field() ?>
  <div class="m-h"><h3>Allocate stickers to an inspector</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-12"><label>Series</label><select class="select" name="series">
      <?php foreach ($series as $sr): if (!$sr['next_free']) continue; ?>
        <option value="<?= e($sr['prefix']) ?>" data-next="<?= e($sr['next_free']) ?>" data-stock="<?= (int) $sr['in_stock'] ?>"><?= e($sr['prefix']) ?> · <?= $sr['color'] === 'red' ? 'Red — Failed' : 'Blue — Accepted' ?> · <?= (int) $sr['in_stock'] ?> in stock</option>
      <?php endforeach; ?></select></div>
    <div class="field col-6"><label>From sticker No.</label><input class="input mono" name="from" required style="text-transform:uppercase" value="<?= e(($blueSeries ?: $redSeries)['next_free'] ?? '') ?>"></div>
    <div class="field col-6"><label>Quantity</label><input class="input" type="number" name="qty" min="1" max="2000" value="25" required></div>
    <div class="field col-12"><label>Inspector</label><select class="select" name="inspector_id" required><?= $inspOptions() ?></select></div>
    <p class="col-12 small muted" data-alloc-hint>The next stickers still in office stock are allocated, starting at the lowest free number in the series.</p>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Allocate</button></div>
</form></dialog>

<dialog class="modal" id="print"><form method="get" action="<?= url('admin/stickers/print') ?>" target="_blank" data-no-lock="1">
  <div class="m-h"><h3>Print stickers</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-6"><label>From sticker No.</label><input class="input mono" name="from" placeholder="TE-001151" style="text-transform:uppercase"></div>
    <div class="field col-6"><label>To sticker No.</label><input class="input mono" name="to" placeholder="TE-001200" style="text-transform:uppercase"></div>
    <div class="field col-12"><label>…or a whole batch</label><select class="select" name="batch"><option value="">—</option><?php foreach ($batches as $b): ?><option><?= e($b) ?></option><?php endforeach; ?></select></div>
    <p class="col-12 small muted">Prints the blank stickers (number, colour and result pre-printed; the inspector fills in the rest on site), as 100 × 100 mm labels (one per page) or 4 per A4 sheet.</p>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary"><?= icon('printer', 'sm') ?> Open print sheet</button></div>
</form></dialog>

<script>
(function () {
  // series → next number to generate, per prefix (last generated + 1)
  var NEXT = <?= json_encode(array_column(array_map(fn ($s) => [$s['prefix'], ['next' => (int) $s['next_generate'], 'color' => $s['color']]], $series), 1, 0), JSON_HEX_TAG) ?>;
  var DEF = { green: <?= json_encode($blueSeries['prefix'] ?? 'TE-') ?>, red: <?= json_encode($redSeries['prefix'] ?? 'TER-') ?> };
  var pad = function (n) { return String(n).padStart(6, '0'); };

  var g = document.querySelector('[data-gen]');
  var gHint = g.querySelector('[data-gen-hint]');
  var syncTo = function () { var f = +g.from.value || 0, q = +g.qty.value || 0; if (f && q) g.to.value = f + q - 1; hint(); };
  var syncQty = function () { var f = +g.from.value || 0, t = +g.to.value || 0; if (f && t >= f) g.qty.value = t - f + 1; hint(); };
  var hint = function () {
    var p = g.prefix.value.toUpperCase(), s = NEXT[p], f = +g.from.value || 0, t = +g.to.value || 0;
    gHint.textContent = (f && t >= f ? 'Creates ' + p + pad(f) + ' … ' + p + pad(t) + ' (' + (t - f + 1) + ' stickers). ' : '') +
      (s ? 'Last generated in ' + p + ' is ' + p + pad(s.next - 1) + '.' : 'New series ' + p + '.') + ' Up to 2,000 per batch, printed as 100 × 100 mm labels.';
  };
  var fromPrefix = function () { var s = NEXT[g.prefix.value.toUpperCase()]; g.from.value = s ? s.next : 1; syncTo(); };
  g.color.addEventListener('change', function () { g.prefix.value = DEF[g.color.value]; fromPrefix(); });
  g.prefix.addEventListener('change', fromPrefix);
  g.from.addEventListener('input', syncTo); g.qty.addEventListener('input', syncTo); g.to.addEventListener('input', syncQty);
  hint();

  var a = document.querySelector('[data-alloc]');
  if (a.series) {
    var aHint = a.querySelector('[data-alloc-hint]');
    var pick = function () { var o = a.series.selectedOptions[0]; if (!o) return; a.from.value = o.dataset.next; a.qty.max = o.dataset.stock;
      aHint.textContent = o.dataset.stock + ' in office stock in this series. The next ' + (a.qty.value || 0) + ' free sticker(s) from ' + o.dataset.next + ' are allocated; numbers already handed out are skipped.'; };
    a.series.addEventListener('change', pick); a.qty.addEventListener('input', pick); pick();
  }

  var bulk = document.getElementById('bulk'), all = bulk.querySelector('[data-all]'), sel = bulk.querySelector('[data-sel]');
  var boxes = function () { return Array.prototype.slice.call(bulk.querySelectorAll('input[name="ids[]"]')); };
  var count = function () { sel.textContent = boxes().filter(function (b) { return b.checked; }).length; };
  all.addEventListener('change', function () { boxes().forEach(function (b) { b.checked = all.checked; }); count(); });
  bulk.addEventListener('change', count);
  bulk.addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b || b.type === 'button') return;
    if (!boxes().some(function (x) { return x.checked; })) { e.preventDefault(); alert('Select at least one sticker.'); return; }
    if (b.hasAttribute('data-need-insp') && !bulk.inspector_id.value) { e.preventDefault(); alert('Choose the inspector to allocate to.'); return; }
    if (b.dataset.confirmBtn && !confirm(b.dataset.confirmBtn)) e.preventDefault();
    if (b.getAttribute('formtarget')) bulk.dataset.noLock = '1'; else delete bulk.dataset.noLock;
  });
  document.addEventListener('click', function (e) {
    var v = e.target.closest('[data-void]'); if (!v) return;
    var r = prompt('Reason for voiding ' + v.dataset.no + ':'); if (!r) return;
    var f = document.getElementById('void-form'); f.action = v.dataset.void; f.reason.value = r; f.submit();
  });
})();
</script>
