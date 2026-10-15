<?php /** @var string $title @var string $text @var string $icon @var ?string $action */ ?>
<div class="empty">
  <div class="e-ic"><?= icon($icon ?? 'inbox', 'lg') ?></div>
  <h3><?= e($title) ?></h3>
  <p><?= e($text ?? '') ?></p>
  <?php if (!empty($action)): ?><div class="mt-2"><?= $action ?></div><?php endif; ?>
</div>
