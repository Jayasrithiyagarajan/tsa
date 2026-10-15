<?php
/**
 * Demo history for the KPI dashboards: ~6 months of completed workflow with realistic, backdated timestamps.
 *   php database/demo_history.php          (also run automatically by install.php)
 * Safe to run once per database (guarded by the "demo_history_loaded" setting). Never run it on production data.
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
if (!defined('ROOT_PATH')) require __DIR__ . '/../app/bootstrap.php';
if (setting('demo_history_loaded')) { echo "· demo history already loaded\n"; return; }

mt_srand(2026);
$rnd = fn (float $a, float $b) => $a + (mt_rand() / mt_getrandmax()) * ($b - $a);
$pick = function (array $weights) { $t = mt_rand(1, array_sum($weights)); foreach ($weights as $k => $w) { if (($t -= $w) <= 0) return $k; } return array_key_first($weights); };
$ts = fn (int $t) => date('Y-m-d H:i:s', $t);
$audit = function (?int $uid, string $action, string $type, ?int $id, int $t, array $d = []) use ($ts) {
    DB::insert('audit_logs', ['user_id' => $uid, 'action' => $action, 'entity_type' => $type, 'entity_id' => $id,
        'details' => $d ? json_encode($d) : null, 'ip_address' => 'demo', 'created_at' => $ts($t)]);
};
$workHour = function (int $t) { $h = (int) date('G', $t); if ($h < 7) return strtotime(date('Y-m-d 07:30', $t)); if ($h > 17) return strtotime(date('Y-m-d 07:30', $t + 86400)); return $t; };

DB::tx(function () use ($rnd, $pick, $ts, $audit, $workHour) {
    $admin = (int) DB::value("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
    // second QC reviewer
    $nadia = DB::value("SELECT id FROM users WHERE email = 'nadia.farouk@tuveast.test'");
    if (!$nadia) {
        $nadia = DB::insert('users', ['name' => 'Nadia Farouk', 'email' => 'nadia.farouk@tuveast.test', 'role' => 'qc', 'phone' => '+966 55 7788 120',
            'password_hash' => DB::value("SELECT password_hash FROM users WHERE email = 'sara.nasser@tuveast.test'") ?: password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)]);
        DB::insert('inspector_profiles', ['user_id' => $nadia, 'employee_no' => 'TE-109', 'qualification' => 'Level III', 'base_location' => 'Jeddah']);
    }
    $qcIds = ['sara.nasser@tuveast.test' => 65, 'nadia.farouk@tuveast.test' => 35];
    $qcUser = [];
    foreach (array_keys($qcIds) as $em) $qcUser[$em] = (int) DB::value('SELECT id FROM users WHERE email = ?', [$em]);

    // inspector profiles: speed factor, rework probability, lateness probability
    $prof = [
        'omar.khalid@tuveast.test'   => ['speed' => 0.9, 'rework' => 0.07, 'late' => 0.06],
        'faisal.rahman@tuveast.test' => ['speed' => 1.12, 'rework' => 0.17, 'late' => 0.15],
        'yousef.haddad@tuveast.test' => ['speed' => 1.0, 'rework' => 0.10, 'late' => 0.08],
    ];
    $insp = [];
    foreach ($prof as $em => $p) {
        $id = (int) DB::value('SELECT id FROM users WHERE email = ?', [$em]);
        if (!$id) continue;
        $skills = array_column(DB::all('SELECT t.code FROM inspector_skills k JOIN inspection_types t ON t.id = k.inspection_type_id WHERE k.user_id = ?', [$id]), 'code');
        $insp[$id] = $p + ['skills' => $skills];
    }
    $types = [];
    foreach (DB::all("SELECT * FROM inspection_types WHERE status = 'active'") as $t) $types[$t['code']] = $t;
    $typeWeights = array_intersect_key(['BOOM' => 16, 'MPI' => 14, 'FORK' => 14, 'GEN' => 8, 'MCR' => 9, 'TCR' => 5, 'ELEV' => 6, 'DRIL' => 4, 'TELE' => 7, 'MOTO' => 4, 'TRLR' => 5, 'PTNK' => 3, 'AIRC' => 6], $types);
    $hours = ['BOOM' => [3, 6], 'MPI' => [1.5, 3], 'FORK' => [2, 4], 'GEN' => [2, 4], 'MCR' => [4, 8], 'TCR' => [5, 9], 'ELEV' => [4, 7],
              'DRIL' => [3, 6], 'TELE' => [2.5, 4.5], 'MOTO' => [1, 2], 'TRLR' => [3, 5], 'PTNK' => [4, 7], 'AIRC' => [1.5, 3]];
    $desc = ['BOOM' => 'BOOM TRUCK', 'MPI' => 'LIFTING LUG', 'FORK' => 'FORKLIFT', 'GEN' => 'DIESEL GENERATOR', 'MCR' => 'MOBILE CRANE', 'TCR' => 'TOWER CRANE',
             'ELEV' => 'PASSENGER ELEVATOR', 'DRIL' => 'DRILLING RIG', 'TELE' => 'TELEHANDLER', 'MOTO' => 'MOTORCYCLE', 'TRLR' => 'SEMI-TRAILER', 'PTNK' => 'FUEL TANKER', 'AIRC' => 'AIR COMPRESSOR'];
    $mfg = ['Tadano', 'Liebherr', 'Toyota', 'Caterpillar', 'Manitou', 'JCB', 'Atlas Copco', 'KONE', 'Palfinger', 'Hyundai', 'Volvo'];

    // extra clients for a richer client table
    foreach ([['22172', 'Arabian Lifting Services', 'Riyadh'], ['22188', 'Dammam Port Logistics', 'Dammam']] as [$code, $name, $city]) {
        if (!DB::value('SELECT id FROM clients WHERE code = ?', [$code])) {
            DB::insert('clients', ['code' => $code, 'name' => $name, 'city' => $city, 'contact_person' => 'Operations Manager',
                'email' => 'ops' . $code . '@example.test', 'phone' => '+966 13 800 ' . substr($code, -4), 'created_at' => $ts(strtotime('-200 days'))]);
        }
    }
    $clients = DB::all('SELECT * FROM clients');
    $clientW = [];
    foreach ($clients as $i => $c) $clientW[$i] = [6, 5, 3, 4, 3][$i] ?? 2;
    $sites = ['Jubail Industrial City', 'Ras Tanura Site', 'Dammam 2nd Industrial City', 'Jeddah Islamic Port', 'Riyadh Logistics Park', 'Al Khobar Workshop', 'Yanbu Refinery Area'];
    $channels = ['website' => 35, 'email' => 30, 'phone' => 15, 'portal' => 20];

    $now = time();
    $jobsMade = 0; $subMade = 0; $certMade = 0;
    // ---------------------------------------------------------------- completed jobs over the last ~6 months
    for ($j = 0; $j < 46; $j++) {
        $daysAgo = (int) round($rnd(9, 178));
        $created = $workHour(strtotime(date('Y-m-d', $now - $daysAgo * 86400)) + (int) $rnd(7.5 * 3600, 15 * 3600));
        $client = $clients[$pick($clientW)];
        $site = $sites[array_rand($sites)];

        // originating request (most jobs)
        $reqId = null;
        if (mt_rand(1, 100) <= 80) {
            $ch = $pick($channels);
            $reqAt = $created - (int) $rnd(2 * 3600, 40 * 3600);
            $reqId = DB::insert('service_requests', [
                'request_no' => Numbering::request(), 'channel' => $ch, 'client_id' => $client['id'], 'company_name' => $client['name'],
                'contact_name' => $client['contact_person'] ?: 'Site Engineer', 'email' => $client['email'], 'phone' => $client['phone'], 'location' => $site,
                'description' => 'Periodic third-party inspection.', 'status' => 'converted', 'received_by' => in_array($ch, ['email', 'phone'], true) ? $admin : null,
                'created_at' => $ts($reqAt), 'updated_at' => $ts($created),
            ]);
            $audit($ch === 'website' ? null : $admin, 'request.created', 'service_request', $reqId, $reqAt, ['channel' => $ch]);
            $firstResp = min($created, $reqAt + (int) $rnd(0.5 * 3600, mt_rand(1, 100) <= 80 ? 20 * 3600 : 50 * 3600));
            if ($firstResp < $created) $audit($admin, 'request.status', 'service_request', $reqId, $firstResp, ['from' => 'new', 'to' => 'under_review']);
            $audit($admin, 'request.converted', 'service_request', $reqId, $created);
        }
        $sched = strtotime(date('Y-m-d', $created + (int) $rnd(1, 6) * 86400));
        $due = mt_rand(1, 100) <= 40 ? date('Y-m-d', $sched + 7 * 86400) : null;
        $jobNo = Numbering::job();
        $jobId = DB::insert('jobs', [
            'job_no' => $jobNo, 'client_id' => $client['id'], 'request_id' => $reqId, 'title' => ['Periodic inspection', 'Annual certification', 'Pre-mobilisation inspection', 'Third-party inspection'][mt_rand(0, 3)] . ' — ' . $site,
            'location' => $site, 'po_no' => mt_rand(0, 1) ? 'PO-' . mt_rand(10000, 99999) : null, 'scheduled_date' => date('Y-m-d', $sched), 'due_date' => $due,
            'priority' => $pick(['normal' => 70, 'high' => 20, 'urgent' => 7, 'low' => 3]), 'status' => 'completed', 'created_by' => $admin, 'created_at' => $ts($created),
        ]);
        if ($reqId) DB::update('service_requests', ['converted_job_id' => $jobId], 'id = ?', [$reqId]);
        $audit($admin, 'job.created', 'job', $jobId, $created, ['job_no' => $jobNo]);
        $jobsMade++;

        $nSub = mt_rand(1, 4);
        $lastApproved = $created;
        $lead = null;
        for ($s = 1; $s <= $nSub; $s++) {
            $code = $pick($typeWeights);
            $cands = array_keys(array_filter($insp, fn ($p) => in_array($code, $p['skills'], true)));
            if (!$cands) continue;
            $iid = $cands[array_rand($cands)];
            $p = $insp[$iid];
            $lead = $lead ?? $iid;
            $t = $types[$code];
            $eqId = DB::insert('equipment', [
                'client_id' => $client['id'], 'inspection_type_id' => $t['id'], 'description' => $desc[$code] ?? 'EQUIPMENT',
                'equipment_no' => strtoupper(substr($code, 0, 2)) . '-' . mt_rand(10, 99), 'serial_no' => strtoupper(bin2hex(random_bytes(3))) . mt_rand(100, 999),
                'manufacturer' => $mfg[array_rand($mfg)], 'model' => strtoupper(substr($code, 0, 1)) . mt_rand(100, 900), 'capacity' => mt_rand(2, 25) . ' Ton',
                'year_built' => mt_rand(2012, 2024), 'location' => $site, 'created_at' => $ts($created),
            ]);
            $assigned = $workHour($created + (int) $rnd(0.3 * 3600, mt_rand(1, 100) <= 85 ? 20 * 3600 : 60 * 3600));
            $startDay = $sched + (mt_rand(1, 100) <= $p['late'] * 100 ? (int) $rnd(2, 8) * 86400 : (int) $rnd(0, 1) * 86400);
            $started = max($assigned + 3600, $startDay + (int) $rnd(7.5 * 3600, 10 * 3600));
            [$h1, $h2] = $hours[$code] ?? [2, 5];
            $submitted = $started + (int) ($rnd($h1, $h2) * $p['speed'] * 3600);
            $seq = $s;
            $subNo = $jobNo . '.' . $seq;
            $sid = DB::insert('sub_jobs', [
                'job_id' => $jobId, 'seq' => $seq, 'sub_job_no' => $subNo, 'inspection_type_id' => $t['id'], 'equipment_id' => $eqId, 'inspector_id' => $iid,
                'scheduled_date' => date('Y-m-d', $sched), 'status' => 'approved', 'started_at' => $ts($started), 'created_at' => $ts($created),
            ]);
            DB::insert('job_assignments', ['job_id' => $jobId, 'sub_job_id' => $sid, 'inspector_id' => $iid, 'assigned_by' => $admin, 'assigned_at' => $ts($assigned)]);
            $audit($admin, 'subjob.assigned', 'sub_job', $sid, $assigned, ['inspector_id' => $iid]);
            $audit($iid, 'inspection.started', 'sub_job', $sid, $started);
            $audit($iid, 'inspection.submitted', 'sub_job', $sid, $submitted);

            $reviewerEmail = $pick($qcIds);
            $rid = $qcUser[$reviewerEmail];
            $slow = $reviewerEmail === 'nadia.farouk@tuveast.test' ? 1.6 : 1.0;
            $decision = $submitted + (int) ($rnd(1, mt_rand(1, 100) <= 85 ? 20 : 55) * $slow * 3600);
            if (mt_rand(1, 1000) <= $p['rework'] * 1000) {
                $reason = ['Photos of the nameplate are missing — please attach.', 'Checklist item remarks incomplete for failed items.', 'Load test value not recorded on the certificate.',
                           'Calibration due date of the test equipment is missing.', 'Serial number on the checklist does not match the equipment record.'][mt_rand(0, 4)];
                DB::insert('qc_reviews', ['sub_job_id' => $sid, 'reviewer_id' => $rid, 'decision' => 'rework', 'comments' => $reason, 'created_at' => $ts($decision)]);
                $audit($rid, 'qc.rework', 'sub_job', $sid, $decision, ['comments' => $reason]);
                $submitted = $decision + (int) $rnd(3 * 3600, 30 * 3600);
                $audit($iid, 'inspection.submitted', 'sub_job', $sid, $submitted);
                $decision = $submitted + (int) ($rnd(1, 18) * $slow * 3600);
            }
            DB::insert('qc_reviews', ['sub_job_id' => $sid, 'reviewer_id' => $rid, 'decision' => 'approved', 'comments' => mt_rand(0, 2) ? null : 'Checked — OK.', 'created_at' => $ts($decision)]);
            $audit($rid, 'qc.approved', 'sub_job', $sid, $decision);
            $result = $pick(['accepted' => 90, 'repair' => 7, 'rejected' => 3]);
            DB::update('sub_jobs', ['submitted_at' => $ts($submitted), 'approved_at' => $ts($decision), 'result' => $result], 'id = ?', [$sid]);
            $inspDate = date('Y-m-d', $started);
            $next = date('Y-m-d', strtotime($inspDate . ' +' . (int) $t['validity_months'] . ' months -1 day'));
            if ($t['requires_certificate']) {
                $tplId = DB::value('SELECT id FROM certificate_templates WHERE inspection_type_id = ? AND is_active = 1 ORDER BY version DESC LIMIT 1', [$t['id']]);
                $certNo = Numbering::certificate(['job_no' => $jobNo, 'client_code' => $client['code'], 'job_id' => $jobId, 'seq' => $seq]);
                $cid = DB::insert('certificates', [
                    'sub_job_id' => $sid, 'template_id' => $tplId ?: null, 'certificate_no' => $certNo, 'source' => $tplId ? 'generated' : 'uploaded', 'data' => '{}',
                    'result' => $result, 'result_statement' => $result === 'accepted' ? 'Found satisfactory at the time of inspection' : 'Repair required before use',
                    'inspection_date' => $inspDate, 'issue_date' => date('Y-m-d', $decision), 'next_inspection_date' => $next,
                    'inspector_id' => $iid, 'reviewer_id' => $rid, 'status' => 'issued', 'verify_token' => bin2hex(random_bytes(16)), 'created_at' => $ts($started),
                ]);
                $audit($rid, 'certificate.issued', 'certificate', $cid, $decision, ['certificate_no' => $certNo]);
                $certMade++;
            }
            DB::update('equipment', ['last_inspection_date' => $inspDate, 'next_inspection_date' => $result === 'rejected' ? null : $next,
                'status' => $result === 'rejected' ? 'out_of_service' : 'active'], 'id = ?', [$eqId]);
            $lastApproved = max($lastApproved, $decision);
            $subMade++;
        }
        DB::update('jobs', ['completed_at' => $ts($lastApproved), 'lead_inspector_id' => $lead, 'updated_at' => $ts($lastApproved)], 'id = ?', [$jobId]);
        $audit(null, 'job.status', 'job', $jobId, $lastApproved, ['from' => 'under_review', 'to' => 'completed']);
    }

    // ---------------------------------------------------------------- requests that did not become jobs (conversion rate)
    for ($k = 0; $k < 14; $k++) {
        $at = $now - (int) $rnd(5, 170) * 86400 + (int) $rnd(8 * 3600, 16 * 3600);
        $client = $clients[$pick($clientW)];
        $st = $pick(['rejected' => 5, 'quoted' => 5, 'cancelled' => 4]);
        $id = DB::insert('service_requests', ['request_no' => Numbering::request(), 'channel' => $pick($channels), 'client_id' => $client['id'], 'company_name' => $client['name'],
            'contact_name' => 'Procurement', 'email' => $client['email'], 'location' => $sites[array_rand($sites)], 'status' => $st,
            'status_note' => ['rejected' => 'Outside accreditation scope', 'quoted' => 'Quotation sent — awaiting PO', 'cancelled' => 'Client postponed'][$st], 'created_at' => $ts($at)]);
        $audit($admin, 'request.status', 'service_request', $id, $at + (int) $rnd(2 * 3600, 30 * 3600), ['from' => 'new', 'to' => $st]);
    }

    // ---------------------------------------------------------------- overdue open work (now)
    foreach ([[13, 'faisal.rahman@tuveast.test', 'FORK'], [11, 'omar.khalid@tuveast.test', 'BOOM']] as [$ago, $em, $code]) {
        if (!isset($types[$code])) continue;
        $iid = (int) DB::value('SELECT id FROM users WHERE email = ?', [$em]);
        $created = $now - $ago * 86400;
        $client = $clients[0];
        $jobNo = Numbering::job();
        $jobId = DB::insert('jobs', ['job_no' => $jobNo, 'client_id' => $client['id'], 'title' => 'Site inspection — overdue example', 'location' => 'Jubail Industrial City',
            'scheduled_date' => date('Y-m-d', $created + 2 * 86400), 'status' => 'assigned', 'created_by' => $admin, 'created_at' => $ts($created), 'lead_inspector_id' => $iid]);
        $eqId = DB::insert('equipment', ['client_id' => $client['id'], 'inspection_type_id' => $types[$code]['id'], 'description' => $desc[$code], 'equipment_no' => 'OV-' . mt_rand(10, 99), 'location' => 'Jubail Industrial City']);
        $sid = DB::insert('sub_jobs', ['job_id' => $jobId, 'seq' => 1, 'sub_job_no' => $jobNo . '.1', 'inspection_type_id' => $types[$code]['id'], 'equipment_id' => $eqId,
            'inspector_id' => $iid, 'scheduled_date' => date('Y-m-d', $created + 2 * 86400), 'status' => 'assigned', 'created_at' => $ts($created)]);
        DB::insert('job_assignments', ['job_id' => $jobId, 'sub_job_id' => $sid, 'inspector_id' => $iid, 'assigned_by' => $admin, 'assigned_at' => $ts($created + 5 * 3600)]);
    }

    // ---------------------------------------------------------------- renewal pipeline: equipment certified ~a year ago, due soon
    for ($k = 0; $k < 9; $k++) {
        $client = $clients[$pick($clientW)];
        $code = $pick($typeWeights);
        $next = date('Y-m-d', $now + (int) $rnd(-6, 40) * 86400);
        DB::insert('equipment', ['client_id' => $client['id'], 'inspection_type_id' => $types[$code]['id'], 'description' => $desc[$code], 'equipment_no' => 'RN-' . mt_rand(100, 999),
            'manufacturer' => $mfg[array_rand($mfg)], 'last_inspection_date' => date('Y-m-d', strtotime($next . ' -1 year +1 day')), 'next_inspection_date' => $next, 'location' => $sites[array_rand($sites)]]);
    }

    DB::run("INSERT INTO settings (`key`, `value`) VALUES ('demo_history_loaded', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)", [date('Y-m-d H:i')]);
    echo "✓ demo history: $jobsMade jobs, $subMade inspections, $certMade certificates over 6 months\n";
});
