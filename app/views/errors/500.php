<div class="auth-panel" style="min-height:100vh">
  <div class="empty" style="max-width:720px">
    <div class="e-ic t-red"><?= icon('alert', 'lg') ?></div>
    <h3 style="font-size:20px">Something went wrong</h3>
    <p>The error has been logged. Please try again.</p>
    <?php if (!empty($error)): ?><pre style="text-align:left;white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:10px;font-size:12px;margin-top:16px"><?= e($error->getMessage() . "\n" . $error->getFile() . ':' . $error->getLine()) ?></pre><?php endif; ?>
    <div class="mt-2"><a class="btn btn-primary" href="<?= url() ?>">Go home</a></div>
  </div>
</div>
