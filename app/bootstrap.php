<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);

require ROOT_PATH . '/vendor/autoload.php';
require APP_PATH . '/core/helpers.php';
foreach (['DB', 'Router', 'Auth', 'Csrf', 'View', 'Audit', 'Workflow', 'Xlsx'] as $f) {
    require APP_PATH . "/core/$f.php";
}
foreach (glob(APP_PATH . '/services/*.php') as $f) {
    require $f;
}
spl_autoload_register(function (string $class) {
    $f = APP_PATH . '/controllers/' . $class . '.php';
    if (is_file($f)) require $f;
});

date_default_timezone_set(config('timezone'));
error_reporting(E_ALL);
ini_set('display_errors', config('env') === 'local' ? '1' : '0');

if (PHP_SAPI !== 'cli') {
    session_name(config('session_name'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() ?: '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
    // one-request lifetime for old input + validation errors
    $_SESSION['old_view'] = $_SESSION['old'] ?? [];
    $_SESSION['errors_view'] = $_SESSION['errors'] ?? [];
    unset($_SESSION['old'], $_SESSION['errors']);

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}
