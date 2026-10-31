<?php
/** Print Sticker: stickers still in office stock + a popup to print / download a numbered range. @var array $rows @var array $meta @var string $q @var string $prefix @var array $series @var int $total */
$swatch = fn ($c) => '<i style="display:inline-block;width:10px;height:10px;border-radius:3px;flex:none;background:' . ($c === 'red' ? 'var(--red)' : '#1d4fb8') . '"></i>';
$pad = fn ($n) => str_pad((string) $n, 6, '0', STR_PAD_LEFT);
// lowest number still in office stock, per series
$first = [];
foreach ($series as $sr) $first[$sr['prefix']] = (int) (preg_match('/(\d+)$/', (string) $sr['next_free'], $m) ? $m[1] : 1);
?>
<div class="page-head">
  <div class="ph-text"><h1>Print Sticker</h1><div class="sub">All stickers still in office stock (<?= (int) $total ?>). Choose a start number and a count to open them as a print sheet, or download them as one PDF.</div></div>
  <div class="actions">
    <?php if (Auth::isSuper()): ?>
      <button class="btn btn-primary" data-modal="pstk" <?= $series ? '' : 'disabled' ?>><?= icon('printer', 'sm') ?> Print</button>
    <?php else: ?><span class="pill">View only</span><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <span class="small muted"><b><?= (int) $meta['total'] ?></b> in stock<?= $prefix !== '' || $q !== '' ? ' (filtered)' : '' ?></span>
    <span class="grow"></span>
    <form class="row" method="get" style="gap:8px" data-autosubmit>
      <select class="select" name="series" style="width:auto"><option value="">All series</option>
        <?php foreach ($series as $sr): ?><option value="<?= e($sr['prefix']) ?>" <?= $prefix === $sr['prefix'] ? 'selected' : '' ?>><?= e($sr['prefix']) ?> · <?= $sr['color'] === 'red' ? 'Red' : 'Blue' ?> (<?= (int) $sr['in_stock'] ?>)</option><?php endforeach; ?></select>
      <div class="search"><?= icon('search', 'sm') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Sticker or batch…"></div>
    </form>
  </div>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Sticker</th><th>Result</th><th>Batch</th><th>Generated</th></tr></thead>
    <tbody><?php foreach ($rows as $s): ?>
      <tr>
        <td data-label="Sticker"><span class="row" style="gap:8px"><?= $swatch($s['color']) ?><span class="mono strong"><?= e($s['sticker_no']) ?></span></span></td>
        <td data-label="Result"><?= $s['color'] === 'red' ? 'Rejected (red)' : 'Accepted (blue)' ?></td>
        <td data-label="Batch" class="sub"><?= e($s['batch_no'] ?: '—') ?></td>
        <td data-label="Generated" class="small nowrap"><?= e(fmt_date($s['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php if (!$rows) View::render('partials/empty', ['icon' => 'tag', 'title' => 'No stickers in stock', 'text' => 'Generate a batch on Sticker Stock to get started.'], null); ?>
  <?php View::render('partials/pager', ['meta' => $meta], null); ?>
</div>

<?php if (Auth::isSuper() && $series): ?>
<dialog class="modal" id="pstk"><form method="get" action="<?= url('admin/stickers/print') ?>" target="_blank" data-no-lock="1" data-pstk>
  <input type="hidden" name="stock" value="1"><input type="hidden" name="from"><input type="hidden" name="to">
  <div class="m-h"><h3>Print stickers</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-12"><label>Series</label><select class="select" name="series">
      <?php foreach ($series as $sr): ?><option value="<?= e($sr['prefix']) ?>"><?= e($sr['prefix']) ?> · <?= $sr['color'] === 'red' ? 'Red — Rejected' : 'Blue — Accepted' ?> · <?= (int) $sr['in_stock'] ?> in stock</option><?php endforeach; ?></select></div>
    <div class="field col-4"><label>Start number</label><input class="input" type="number" name="start" min="1" value="<?= (int) reset($first) ?>" required></div>
    <div class="field col-4"><label>Count</label><input class="input" type="number" name="count" min="1" max="2000" value="50" required></div>
    <div class="field col-4"><label>End number</label><input class="input" type="number" name="end" readonly tabindex="-1"></div>
    <p class="col-12 small muted" data-hint></p>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary"><?= icon('printer', 'sm') ?> Generate</button></div>
</form></dialog>
<script>
(function () {
  var f = document.querySelector('[data-pstk]'); if (!f) return;
  var FIRST = <?= json_encode($first, JSON_HEX_TAG) ?>, STOCK = <?= json_encode(array_column($series, 'in_stock', 'prefix'), JSON_HEX_TAG) ?>;
  var pad = function (n) { return String(n).padStart(6, '0'); };
  var hint = f.querySelector('[data-hint]');
  var sync = function () {
    var s = +f.start.value || 0, c = Math.min(2000, Math.max(0, +f.count.value || 0)), p = f.series.value;
    f.end.value = s && c ? s + c - 1 : '';
    f.from.value = s ? p + pad(s) : ''; f.to.value = s && c ? p + pad(s + c - 1) : '';
    hint.textContent = s && c ? 'Prints ' + p + pad(s) + ' … ' + p + pad(s + c - 1) + ' (' + c + ' stickers, 100 × 100 mm). Numbers that are not in office stock are skipped. Up to 2,000 at a time.' : '';
  };
  f.series.addEventListener('change', function () { f.start.value = FIRST[f.series.value] || 1; sync(); });
  f.start.addEventListener('input', sync); f.count.addEventListener('input', sync);
  f.addEventListener('submit', function () { sync(); setTimeout(function () { f.closest('dialog').close(); }, 50); });
  sync();
})();
</script>
<?php endif; ?>
