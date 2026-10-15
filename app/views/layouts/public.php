<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= Csrf::token() ?>">
<title><?= e(($title ?? 'Inspection Services') . ' · TUV EAST') ?></title>
<meta name="description" content="TUV EAST — third-party inspection, NDT and lifting equipment certification in the Kingdom of Saudi Arabia.">
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<header class="pub-nav">
  <a href="<?= url() ?>"><img src="<?= asset('img/logo.png') ?>" alt="TUV EAST"></a>
  <span class="sp"></span>
  <nav>
    <a href="<?= url() ?>#services">Services</a>
    <a href="<?= url() ?>#how">How it works</a>
    <a href="<?= url('verify') ?>">Verify Certificate</a>
  </nav>
  <a class="btn btn-secondary" href="<?= url('login') ?>">Sign in</a>
  <a class="btn btn-primary hide-sm" href="<?= url('request') ?>">Request Inspection</a>
</header>
<?php foreach (flashes() as $f): ?>
  <div class="pub-wrap" style="margin-top:16px"><div class="alert alert-<?= e($f['type']) ?>"><?= icon('info') ?><div><?= nl2br(e($f['message'])) ?></div></div></div>
<?php endforeach; ?>
<?= $content ?>
<footer class="pub-foot">
  <div class="pub-wrap row between wrap">
    <span>© <?= date('Y') ?> TUV EAST — Trading Utility Vendor for Inspection · C.R: <?= e(setting('company_cr')) ?></span>
    <span>Al Khobar, Kingdom of Saudi Arabia · <a href="mailto:<?= e(setting('company_email')) ?>" style="color:#c3cff0"><?= e(setting('company_email')) ?></a></span>
  </div>
</footer>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
