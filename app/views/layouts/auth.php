<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') . ' · ' . config('app_short')) ?></title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<?= $content ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
