<div class="auth-panel" style="min-height:100vh">
  <div class="empty">
    <div class="e-ic"><?= icon('search', 'lg') ?></div>
    <h3 style="font-size:20px">Page not found</h3>
    <p>The page you are looking for does not exist or has moved.</p>
    <div class="mt-2"><a class="btn btn-primary" href="<?= url(Auth::check() ? Auth::home() : '') ?>">Go home</a></div>
  </div>
</div>
