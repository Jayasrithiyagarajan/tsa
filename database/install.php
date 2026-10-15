<?php
/**
 * Installer:  php database/install.php            → schema + reference data + demo data
 *             php database/install.php --no-demo  → schema + reference data + admin account only
 * WARNING: drops and recreates all IMS tables in the configured database.
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require __DIR__ . '/../app/bootstrap.php';

$demo = !in_array('--no-demo', $argv, true);
$c = config('db');
$pdo = new PDO("mysql:host={$c['host']};port={$c['port']};charset=utf8mb4", $c['user'], $c['pass']);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$c['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$c['name']}`");
foreach (array_filter(array_map('trim', explode(";\n", file_get_contents(__DIR__ . '/schema.sql')))) as $stmt) {
    if ($stmt !== '' && !preg_match('/^(--[^\n]*\n?)+$/', $stmt)) $pdo->exec($stmt);
}
echo "✓ schema\n";

$_SESSION = [];
function act_as($uid): void
{
    $_SESSION['uid'] = $uid ? (int) $uid : null;
    $_SESSION['last_seen'] = time();
    $p = new ReflectionProperty(Auth::class, 'user');
    $p->setAccessible(true);
    $p->setValue(null, null);
}

// ------------------------------------------------------------------ reference data
foreach ([
    'company_name' => 'TUV EAST — Trading Utility Vendor for Inspection',
    'company_cr' => '2051229946',
    'company_email' => 'info@tuv-east.com',
    'company_phone' => '0138185501',
    'certificate_pattern' => '{YYYY}-{CLIENT}-{JOB}-{SEQ}',
] as $k => $v) {
    DB::insert('settings', ['key' => $k, 'value' => $v]);
}

$types = [];
foreach (require __DIR__ . '/seed_templates.php' as $def) {
    $typeId = DB::insert('inspection_types', $def['type']);
    $types[$def['type']['code']] = $typeId;
    if ($def['checklist']) {
        TemplateService::saveChecklist(null, $typeId, $def['checklist'], $def['checklist']['sections']);
    }
    if ($def['certificate']) {
        TemplateService::saveCertificate(null, $typeId, $def['certificate'] + ['show_image_area' => 1, 'result_options' => ['accepted', 'rejected', 'repair']], $def['certificate']['fields']);
    }
}
echo "✓ inspection types & templates\n";

// demo password for every seeded account — change after first login
const DEMO_PASSWORD = 'Tuv@2026!';
$pw = password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT);
$admin = DB::insert('users', ['name' => 'System Administrator', 'email' => 'admin@tuveast.test', 'password_hash' => $pw, 'role' => 'superadmin', 'phone' => '+966 13 818 5501']);

if (!$demo) {
    echo "✓ super admin account admin@tuveast.test (password in database/install.php)\n";
    exit;
}

// ------------------------------------------------------------------ demo signatures (synthetic scribbles)
function demo_signature(string $seed): string
{
    mt_srand(crc32($seed));
    $im = imagecreatetruecolor(360, 120);
    imagesavealpha($im, true);
    imagefill($im, 0, 0, imagecolorallocatealpha($im, 255, 255, 255, 127));
    $ink = imagecolorallocate($im, 30, 60, 170);
    imagesetthickness($im, 3);
    $x = 20; $y = 70;
    for ($i = 0; $i < 46; $i++) {
        $nx = $x + mt_rand(2, 10); $ny = max(15, min(105, $y + mt_rand(-28, 28)));
        imageline($im, $x, $y, $nx, $ny, $ink);
        $x = $nx; $y = $ny;
    }
    imageline($im, 30, 98, 300, 88, $ink);
    $dir = config('upload_dir') . '/signatures';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = 'signatures/demo-' . substr(md5($seed), 0, 10) . '.png';
    imagepng($im, config('upload_dir') . '/' . $name);
    imagedestroy($im);
    return $name;
}

// ------------------------------------------------------------------ staff
$staff = [
    ['Omar Khalid', 'omar.khalid@tuveast.test', 'inspector', 'NDT Level II · Lifting Inspector', 'Al Khobar', ['BOOM', 'MPI', 'FORK', 'GEN', 'MCR', 'TCR', 'ELEV', 'DRIL', 'TELE', 'MOTO', 'TRLR', 'PTNK', 'AIRC']],
    ['Faisal Rahman', 'faisal.rahman@tuveast.test', 'inspector', 'Lifting Equipment Inspector', 'Jubail', ['BOOM', 'FORK', 'GEN', 'MCR', 'TCR', 'ELEV', 'DRIL', 'TELE', 'MOTO', 'TRLR', 'PTNK', 'AIRC']],
    ['Yousef Haddad', 'yousef.haddad@tuveast.test', 'inspector', 'NDT Level II', 'Jeddah', ['MPI']],
    ['Sara Nasser', 'sara.nasser@tuveast.test', 'qc', 'Level III', null, []],
];
// operations admin: creates and follows up jobs, gives the final approval; master data is view-only
DB::insert('users', ['name' => 'Hana Al Qahtani', 'email' => 'ops.admin@tuveast.test', 'password_hash' => $pw, 'role' => 'admin', 'phone' => '+966 13 818 5502']);
$ids = [];
foreach ($staff as $i => [$name, $email, $role, $qual, $loc, $skills]) {
    $uid = DB::insert('users', ['name' => $name, 'email' => $email, 'password_hash' => $pw, 'role' => $role,
        'phone' => '+966 5' . (5000000 + $i * 12345), 'signature_path' => demo_signature($email)]);
    DB::insert('inspector_profiles', ['user_id' => $uid, 'employee_no' => sprintf('TE-%03d', 101 + $i), 'qualification' => $qual,
        'cert_no' => 'Q-' . (4400 + $i), 'cert_expiry' => date('Y-m-d', strtotime('+' . (8 + $i * 5) . ' months')), 'base_location' => $loc]);
    foreach ($skills as $s) DB::insert('inspector_skills', ['user_id' => $uid, 'inspection_type_id' => $types[$s]]);
    $ids[$email] = $uid;
}
$omar = $ids['omar.khalid@tuveast.test'];
$faisal = $ids['faisal.rahman@tuveast.test'];
$yousef = $ids['yousef.haddad@tuveast.test'];
$sara = $ids['sara.nasser@tuveast.test'];

// ------------------------------------------------------------------ clients, customer users, equipment
act_as($admin);
$clients = [
    ['22108', 'Al Noor Contracting Co.', 'Khalid Al Otaibi', 'projects@alnoor.test', '+966 13 811 2200', 'Dammam'],
    ['22131', 'Gulf Steel Industries', 'Ravi Menon', 'hse@gulfsteel.test', '+966 13 340 9911', 'Jubail'],
    ['22157', 'Red Sea Marine Services', 'Hassan Bakr', 'ops@redseamarine.test', '+966 12 650 3030', 'Jeddah'],
];
$cl = [];
foreach ($clients as [$code, $name, $person, $email, $phone, $city]) {
    $cl[$code] = ClientService::create(compact('code', 'name', 'email', 'phone', 'city') + ['contact_person' => $person, 'address' => "Industrial Area, $city"]);
}
DB::insert('users', ['name' => 'Khalid Al Otaibi', 'email' => 'customer@alnoor.test', 'password_hash' => $pw, 'role' => 'customer', 'client_id' => $cl['22108']]);
DB::insert('users', ['name' => 'Ravi Menon', 'email' => 'customer@gulfsteel.test', 'password_hash' => $pw, 'role' => 'customer', 'client_id' => $cl['22131']]);

$eq = function ($client, $type, $desc, $no, $serial, $mfg, $model, $cap, $year) use ($cl, $types) {
    return DB::insert('equipment', ['client_id' => $cl[$client], 'inspection_type_id' => $types[$type], 'description' => $desc,
        'equipment_no' => $no, 'serial_no' => $serial, 'manufacturer' => $mfg, 'model' => $model, 'capacity' => $cap, 'year_built' => $year]);
};
$e1 = $eq('22108', 'BOOM', 'BOOM TRUCK', 'BT-07', 'TDN1234567', 'Tadano', 'TM-ZE554', '5 Ton', 2019);
$e2 = $eq('22108', 'MPI', 'MAN BASKET', 'MB-03', 'MB-2021-03', 'Local Fabrication', 'MB-500', '500 Kg', 2021);
$e3 = $eq('22108', 'BOOM', 'BOOM TRUCK', 'BT-11', 'PLF88123', 'Palfinger', 'PK 23500', '8 Ton', 2020);
$e4 = $eq('22131', 'FORK', 'FORKLIFT', 'FL-02', 'TY7FD30-4411', 'Toyota', '7FD30', '3 Ton', 2018);
$e5 = $eq('22131', 'MPI', 'SPREADER BEAM', 'SB-12', 'SB-12-2017', 'Crosby', 'SB-20', '20 Ton', 2017);
$e6 = $eq('22131', 'BOOM', 'BOOM TRUCK', 'BT-21', 'FAS-55120', 'Fassi', 'F275A', '10 Ton', 2021);
$e7 = $eq('22157', 'FORK', 'FORKLIFT', 'FL-09', 'HY-50D-9921', 'Hyundai', '50D-9', '5 Ton', 2020);
$e8 = $eq('22157', 'MPI', 'LIFTING LUG', 'LL-4', 'LL-4-2022', 'Local Fabrication', '-', '12 Ton', 2022);
echo "✓ clients, customers & equipment\n";

StickerService::addBatch('TE-', 1001, 1150, 'green', 'B-2026-01');
StickerService::addBatch('TER-', 1, 20, 'red', 'B-2026-R1');
// hand printed stickers to the inspectors (the rest stays in office stock)
StickerService::allocate(StickerService::range('TE-001001', 'TE-001030'), $omar);
StickerService::allocate(StickerService::range('TE-001031', 'TE-001060'), $faisal);
StickerService::allocate(StickerService::range('TER-000001', 'TER-000005'), $omar);
StickerService::allocate(StickerService::range('TER-000006', 'TER-000010'), $faisal);

// ------------------------------------------------------------------ requests (all three channels + portal)
$item = fn ($type, $desc, $no = null, $qty = 1) => ['inspection_type_id' => $types[$type], 'equipment_description' => $desc, 'equipment_no' => $no, 'quantity' => $qty];
act_as(null);
$rWeb = RequestService::create(['company_name' => 'Eastern Cranes Est.', 'contact_name' => 'Majed Al Harbi', 'email' => 'majed@easterncranes.test',
    'phone' => '+966 55 300 1122', 'location' => 'Dammam 2nd Industrial City', 'preferred_date' => date('Y-m-d', strtotime('+6 days')),
    'description' => 'Annual third-party inspection for two boom trucks before site mobilisation.'], [$item('BOOM', 'Boom Truck 25T', null, 2)], 'website');
act_as($admin);
$rEmail = RequestService::create(['client_id' => $cl['22157'], 'company_name' => 'Red Sea Marine Services', 'contact_name' => 'Hassan Bakr',
    'email' => 'ops@redseamarine.test', 'phone' => '+966 12 650 3030', 'location' => 'Jeddah Islamic Port, Berth 14', 'po_no' => 'PO-RSM-7781',
    'description' => 'Received by email: forklift + lifting lug MPI.'], [$item('FORK', 'FORKLIFT', 'FL-09'), $item('MPI', 'LIFTING LUG', 'LL-4')], 'email');
RequestService::changeStatus($rEmail, 'under_review', 'Waiting for site access confirmation');
$rPhone = RequestService::create(['company_name' => 'Al Mansour Trading', 'contact_name' => 'Ali Mansour', 'phone' => '+966 50 777 1200',
    'location' => 'Riyadh', 'description' => 'Phone call — asked for quotation for 4 forklifts.'], [$item('FORK', 'Forklift 3T', null, 4)], 'phone');
RequestService::changeStatus($rPhone, 'quoted', 'Quotation Q-2026-118 sent');

// ------------------------------------------------------------------ jobs in every workflow state
$conv = function (int $reqId, array $job, array $subs) { return RequestService::convert($reqId, $job, $subs); };

// JOB 1 — Al Noor, mixed: 2 approved, 1 in progress
act_as(DB::value("SELECT id FROM users WHERE email='customer@alnoor.test'"));
$rPortal = RequestService::create(['client_id' => $cl['22108'], 'company_name' => 'Al Noor Contracting Co.', 'contact_name' => 'Khalid Al Otaibi',
    'email' => 'projects@alnoor.test', 'phone' => '+966 13 811 2200', 'location' => 'BSP Substation, Jeddah', 'po_no' => 'N/A',
    'description' => 'Quarterly inspection of site lifting equipment.'], [$item('BOOM', 'BOOM TRUCK', 'BT-07'), $item('MPI', 'MAN BASKET', 'MB-03'), $item('BOOM', 'BOOM TRUCK', 'BT-11')], 'portal');
act_as($admin);
$job1 = $conv($rPortal, ['client_id' => $cl['22108'], 'title' => 'Quarterly lifting equipment inspection', 'location' => 'BSP Substation, Jeddah',
    'po_no' => null, 'scheduled_date' => date('Y-m-d', strtotime('-3 days')), 'priority' => 'high'], [
    ['inspection_type_id' => $types['BOOM'], 'equipment_id' => $e1, 'inspector_id' => $omar],
    ['inspection_type_id' => $types['MPI'], 'equipment_id' => $e2, 'inspector_id' => $omar],
    ['inspection_type_id' => $types['BOOM'], 'equipment_id' => $e3, 'inspector_id' => $faisal],
]);
$sj = DB::all('SELECT id FROM sub_jobs WHERE job_id = ? ORDER BY seq', [$job1]);

$fillChecklist = function (int $sid, int $failAt = -1, int $limit = 0) {
    $cl = DB::one('SELECT * FROM checklists WHERE sub_job_id = ?', [$sid]);
    $resp = [];
    foreach (DB::all('SELECT id FROM checklist_template_items WHERE template_id = ? ORDER BY sort_order', [$cl['template_id']]) as $i => $it) {
        if ($limit && $i >= $limit) break;
        $resp[$it['id']] = ['result' => $i === $failAt ? 'fail' : (in_array($i, [13, 24]) ? 'na' : 'pass'), 'remarks' => $i === $failAt ? 'Hydraulic leak at slew motor seal' : ''];
    }
    InspectionService::saveChecklist($sid, $resp, ['serial_no' => null]);
};
$mpiFields = ['magnet_serial' => '4526', 'calibration_due' => date('Y-m-d', strtotime('+7 months')), 'magnetization' => 'Permanent',
    'magnet_model' => 'PM2004', 'lifting_capacity' => '18 Kg', 'particle_type' => 'Wet Visible', 'white_paint' => 'FLUXO-3',
    'particle_mfg' => 'FLUXO-4', 'particle_colour' => 'Black'];

act_as($omar);
InspectionService::start($sj[0]['id']);
$fillChecklist($sj[0]['id']);
InspectionService::saveCertificate($sj[0]['id'], ['fields' => ['plate_no' => 'RTD 4471', 'boom_length' => '18.5 m', 'load_test' => '110% of SWL'], 'result' => 'accepted', 'inspection_date' => date('Y-m-d', strtotime('-2 days'))]);
InspectionService::linkSticker($sj[0]['id'], 'TE-001001');
InspectionService::submit($sj[0]['id']);
InspectionService::start($sj[1]['id']);
InspectionService::saveCertificate($sj[1]['id'], ['fields' => $mpiFields, 'result' => 'accepted', 'inspection_date' => date('Y-m-d', strtotime('-2 days'))]);
InspectionService::submit($sj[1]['id']);
act_as($sara);
QcService::approve($sj[0]['id'], 'Checked against previous certificate. OK.');
QcService::approve($sj[1]['id']);
act_as($faisal);
InspectionService::start($sj[2]['id']);
$fillChecklist($sj[2]['id'], -1, 30);

// JOB 2 — Gulf Steel, both awaiting QC (one with a failed item → repair)
act_as($admin);
$job2 = JobService::create(['client_id' => $cl['22131'], 'title' => 'Annual certification — workshop equipment', 'location' => 'Jubail Industrial City, Plant 3',
    'po_no' => 'PO-GSI-55021', 'scheduled_date' => date('Y-m-d', strtotime('-1 days')), 'priority' => 'normal'], [
    ['inspection_type_id' => $types['FORK'], 'equipment_id' => $e4, 'inspector_id' => $faisal],
    ['inspection_type_id' => $types['MPI'], 'equipment_id' => $e5, 'inspector_id' => $omar],
    ['inspection_type_id' => $types['BOOM'], 'equipment_id' => $e6, 'inspector_id' => $faisal],
]);
$sj2 = DB::all('SELECT id FROM sub_jobs WHERE job_id = ? ORDER BY seq', [$job2]);
act_as($faisal);
InspectionService::start($sj2[0]['id']);
$fillChecklist($sj2[0]['id']);
InspectionService::saveCertificate($sj2[0]['id'], ['fields' => ['lift_height' => '4.5 m'], 'result' => 'accepted']);
InspectionService::linkSticker($sj2[0]['id'], 'TE-001031');
InspectionService::submit($sj2[0]['id']);
InspectionService::start($sj2[2]['id']);
$fillChecklist($sj2[2]['id'], 15);
InspectionService::saveCertificate($sj2[2]['id'], ['fields' => ['plate_no' => 'JBL 2290', 'boom_length' => '21 m'], 'result' => 'repair',
    'result_statement' => 'Equipment to be repaired and re-inspected before use', 'remarks' => 'Hydraulic leak observed at slew motor seal. Re-inspection required after repair.']);
InspectionService::linkSticker($sj2[2]['id'], 'TER-000006');   // repair result → red (Failed) sticker
InspectionService::submit($sj2[2]['id']);
act_as($omar);
InspectionService::start($sj2[1]['id']);
InspectionService::saveCertificate($sj2[1]['id'], ['fields' => $mpiFields, 'result' => 'accepted']);
InspectionService::submit($sj2[1]['id']);
act_as($sara);
QcService::rework($sj2[1]['id'], 'Calibration certificate of the magnet must be attached; please add photos of the inspected welds.');

// JOB 3 — converted email request, awaiting assignment
act_as($admin);
RequestService::convert($rEmail, ['client_id' => $cl['22157'], 'title' => 'Port equipment inspection', 'location' => 'Jeddah Islamic Port, Berth 14',
    'po_no' => 'PO-RSM-7781', 'scheduled_date' => date('Y-m-d', strtotime('+3 days')), 'priority' => 'urgent'], [
    ['inspection_type_id' => $types['FORK'], 'equipment_id' => $e7],
    ['inspection_type_id' => $types['MPI'], 'equipment_id' => $e8],
]);
// JOB 4 — assigned, not started
JobService::create(['client_id' => $cl['22108'], 'title' => 'Pre-mobilisation boom truck inspection', 'location' => 'Ras Tanura Site Camp',
    'scheduled_date' => date('Y-m-d', strtotime('+1 days')), 'inspector_id' => $faisal], [
    ['inspection_type_id' => $types['BOOM'], 'equipment_description' => 'BOOM TRUCK', 'equipment_no' => 'BT-15', 'serial_no' => 'UNIC-77310'],
    ['inspection_type_id' => $types['GEN'], 'equipment_description' => 'DIESEL GENERATOR', 'equipment_no' => 'DG-02', 'serial_no' => 'CAT-C18-55120'],
]);
echo "✓ demo requests, jobs, inspections, certificates\n";
echo "\nDemo accounts (password: " . DEMO_PASSWORD . ")\n";
foreach (DB::all('SELECT role, email FROM users ORDER BY FIELD(role,"superadmin","admin","inspector","qc","customer"), id') as $u) {
    printf("  %-10s %s\n", $u['role'], $u['email']);
}

// ------------------------------------------------------------------ 6 months of history for the KPI dashboards
require __DIR__ . '/demo_history.php';
