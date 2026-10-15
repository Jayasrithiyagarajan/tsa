<?php
// Copy to config.local.php to override per environment (that file is not required to exist).
$config = [
    'app_name'  => 'TUV EAST · Inspection Management System',
    'app_short' => 'TUV EAST IMS',
    'env'       => 'local',               // local | production
    'timezone'  => 'Asia/Riyadh',

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
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}
return $config;
