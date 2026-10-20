<?php
// Copy to config.local.php to override per environment (that file is not required to exist).
$config = [
    'app_name'  => 'TUV EAST · Inspection Management System',
    'app_short' => 'TUV EAST IMS',
    'env'       => 'local',               // local | production
    'timezone'  => 'Asia/Riyadh',
    // Public address printed in sticker / certificate QR codes, e.g. https://ims.tuv-east.com (empty = the address in use)
    'public_url' => '',

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'tuvsa_ims',
        'user' => 'root',
        'pass' => '',
    ],

    // uploads
    'upload_dir'      => __DIR__ . '/../storage/uploads',
    'upload_max_mb'   => 8,
    'upload_mimes'    => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],

    // session
    'session_name'    => 'tuvims',
    'session_minutes' => 120,

    // outgoing e-mail (job created / job closed / customer account). Leave host empty to only save mails to storage/mail/.
    // Put real values in config/config.local.php, e.g. 'mail' => ['host' => 'smtp.office365.com', 'port' => 587, 'secure' => 'tls', 'user' => '...', 'pass' => '...', 'from' => 'noreply@tuv-east.com']
    'mail' => [
        'host'      => '',
        'port'      => 587,
        'secure'    => 'tls',             // tls (STARTTLS) | ssl | '' (none)
        'user'      => '',
        'pass'      => '',
        'from'      => 'no-reply@tuv-east.com',
        'from_name' => 'TUV EAST',
    ],
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}
return $config;
