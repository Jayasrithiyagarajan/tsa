<?php /** @var array $meta */
if ($meta['total'] === 0) return;
$q = $_GET; ?>
<div class="pager">
  <span>Showing <?= ($meta['page'] - 1) * $meta['per'] + 1 ?>–<?= min($meta['total'], $meta['page'] * $meta['per']) ?> of <?= $meta['total'] ?></span>
  <?php if ($meta['pages'] > 1): ?>
  <div class="pages">
    <?php for ($p = max(1, $meta['page'] - 2); $p <= min($meta['pages'], $meta['page'] + 2); $p++): $q['page'] = $p; ?>
      <?php if ($p === $meta['page']): ?><span class="cur"><?= $p ?></span>
      <?php else: ?><a href="?<?= e(http_build_query($q)) ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
