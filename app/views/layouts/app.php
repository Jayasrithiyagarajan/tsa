<?php
/** Portal shell. @var string $content @var string $title @var array $crumbs */
$u = Auth::user();
$role = $u['role'];
$portal = ['superadmin' => 'Super Admin Portal', 'admin' => 'Admin Portal', 'inspector' => 'Inspector Portal', 'qc' => 'QC Portal', 'customer' => 'Customer Portal'][$role];
$unread = (int) DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$u['id']]);
$notes = DB::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8', [$u['id']]);

$c = [];
if (Auth::is('admin')) {
    $c = DB::one("SELECT (SELECT COUNT(*) FROM service_requests WHERE status IN ('new','under_review')) req,
                         (SELECT COUNT(*) FROM sub_jobs WHERE status='pending') unassigned,
                         (SELECT COUNT(*) FROM jobs WHERE status='awaiting_approval') approvals,
                         (SELECT COUNT(*) FROM inspector_cards WHERE status='active' AND handed_over_at IS NULL) cards_pending,
                         (SELECT COUNT(*) FROM sub_jobs WHERE status='submitted') queue");
} elseif ($role === 'inspector') {
    $c = DB::one("SELECT SUM(status IN ('assigned','in_progress')) open, SUM(status='rework') rework FROM sub_jobs WHERE inspector_id = ?", [$u['id']]);
} elseif ($role === 'qc') {
    $c = DB::one("SELECT COUNT(*) queue FROM sub_jobs WHERE status='submitted'");
}
$cnt = fn ($n, $hot = false) => (int) $n > 0 ? '<span class="count' . ($hot ? ' hot' : '') . '">' . (int) $n . '</span>' : '';

$nav = [
    'admin' => [
        ['Overview', [['admin', 'grid', 'Dashboard', ''], ['admin/kpi', 'chart', 'KPI & Reports', '']]],
        ['Operations', [
            ['admin/requests', 'inbox', 'Client Requests', $cnt($c['req'] ?? 0, true)],
            ['admin/jobs', 'briefcase', 'Jobs & Sub-jobs', ''],
            ['admin/assign', 'users', 'Assignment Board', $cnt($c['unassigned'] ?? 0)],
            ['admin/jobs?status=awaiting_approval', 'check', 'Final Approval', $cnt($c['approvals'] ?? 0, true)],
        ]],
        ['Documents', [
            ['admin/certificates', 'award', 'Certificates', ''],
            ['admin/stickers', 'layers', 'Sticker Stock', ''],
            ['admin/print-stickers', 'printer', 'Print Sticker', ''],
            ['admin/project-stickers', 'tag', 'Project Stickers', ''],
            ['admin/templates', 'layers', 'Templates', ''],
        ]],
        ['ID Card System', [
            ['admin/cards', 'qr', 'Inspector ID Cards', $cnt($c['cards_pending'] ?? 0)],
            ['admin/cards/new', 'plus', 'Issue Card', ''],
            ['admin/cards/validate', 'shield', 'Validate Card', ''],
        ]],
        ['Master Data', [
            ['admin/clients', 'building', 'Clients', ''],
            ['admin/equipment', 'truck', 'Equipment', ''],
            ['admin/users', 'user', 'Users & Inspectors', ''],
        ]],
        ['System', [
            ['admin/audit', 'history', 'Audit Log', ''],
            ['admin/settings', 'settings', 'Settings', ''],
        ]],
    ],
    'inspector' => [
        ['Work', [
            ['inspector', 'grid', 'My Day', ''],
            ['inspector/jobs', 'briefcase', 'Assigned Jobs', $cnt(($c['open'] ?? 0) + ($c['rework'] ?? 0), (int) ($c['rework'] ?? 0) > 0)],
            ['inspector/history', 'history', 'Submitted & Approved', ''],
            ['inspector/stickers', 'tag', 'My Stickers', ''],
            ['inspector/kpi', 'chart', 'My KPIs', ''],
            ['inspector/card', 'qr', 'My ID Card', ''],
        ]],
    ],
    'qc' => [
        ['Quality Control', [
            ['qc', 'grid', 'Dashboard', ''],
            ['qc/queue', 'shield', 'Review Queue', $cnt($c['queue'] ?? 0, true)],
            ['qc/history', 'history', 'My Decisions', ''],
            ['qc/certificates', 'award', 'Issued Certificates', ''],
            ['qc/kpi', 'chart', 'QC KPIs', ''],
        ]],
    ],
    'customer' => [
        ['My Account', [
            ['portal', 'grid', 'Overview', ''],
            ['portal/requests', 'inbox', 'Inspection Requests', ''],
            ['portal/jobs', 'briefcase', 'Jobs & Progress', ''],
            ['portal/certificates', 'award', 'Certificates', ''],
            ['portal/equipment', 'truck', 'Equipment Register', ''],
        ]],
    ],
][$role === 'superadmin' ? 'admin' : $role];
if ($role === 'superadmin') {
    // Super Admin has every role's screens: add the QC group to the admin menu
    $nav[] = ['Quality Control', [
        ['qc/queue', 'shield', 'QC Review Queue', $cnt($c['queue'] ?? 0, true)],
        ['qc/history', 'history', 'QC Decisions', ''],
        ['qc/certificates', 'award', 'Issued Certificates', ''],
        ['qc/kpi', 'chart', 'QC KPIs', ''],
    ]];
}
if ($role === 'admin') {
    // operations admin: no template / settings / audit screens and no card issuing (Super Admin only)
    $superOnly = ['admin/templates', 'admin/cards/new', 'admin/audit', 'admin/settings'];
    foreach ($nav as $i => [$group, $links]) {
        $nav[$i][1] = array_values(array_filter($links, fn ($l) => !in_array($l[0], $superOnly, true)));
        if (!$nav[$i][1]) unset($nav[$i]);
    }
}
$homeKey = Auth::home();
// highlight only the most specific matching link (admin/cards/new must not also light up admin/cards)
$activeHref = '';
foreach ($nav as [, $links]) {
    foreach ($links as [$href]) {
        $hit = $href === $homeKey ? trim(request_path(), '/') === $href : is_active_nav($href);
        if ($hit && strlen($href) > strlen($activeHref)) $activeHref = $href;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= Csrf::token() ?>">
<title><?= e(($title ?? 'Dashboard') . ' · ' . config('app_short')) ?></title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="portal-<?= e($role) ?>" data-base="<?= e(url()) ?>">
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= url($homeKey) ?>" style="text-decoration:none">
      <img src="<?= asset('img/logo.png') ?>" alt="TUV EAST">
      <span class="b-txt"><strong>Inspection</strong>Management System</span>
    </a>
    <div class="portal-chip"><i></i><?= e($portal) ?></div>
    <nav class="nav">
      <?php foreach ($nav as [$group, $links]): ?>
        <div class="nav-label"><?= e($group) ?></div>
        <?php foreach ($links as [$href, $ic, $label, $extra]):
            $active = $href === $activeHref; ?>
          <a href="<?= url($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= icon($ic) ?><span><?= e($label) ?></span><?= $extra ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <div class="nav-label">Account</div>
      <a href="<?= url('profile') ?>" class="<?= is_active_nav('profile') ? 'active' : '' ?>"><?= icon('user') ?><span>My Profile</span></a>
    </nav>
    <div class="side-foot">
      <span class="avatar"><?= e(initials($u['name'])) ?></span>
      <span class="who"><b><?= e($u['name']) ?></b><span><?= e($role === 'customer' ? ($u['client_name'] ?? 'Customer') : Workflow::label($role)) ?></span></span>
      <form method="post" action="<?= url('logout') ?>"><?= Csrf::field() ?><button title="Sign out" aria-label="Sign out"><?= icon('logout') ?></button></form>
    </div>
  </aside>
  <div class="scrim" data-close-nav></div>

  <div class="main">
    <header class="topbar">
      <button class="icon-btn menu-btn" data-open-nav aria-label="Open menu"><?= icon('menu') ?></button>
      <div class="crumbs">
        <a href="<?= url($homeKey) ?>"><?= e($portal) ?></a>
        <?php foreach (($crumbs ?? []) as $label => $href): ?>
          <?= icon('chevron-right', 'sm') ?>
          <?php if ($href): ?><a href="<?= url($href) ?>"><?= e($label) ?></a><?php else: ?><b><?= e($label) ?></b><?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php if (Auth::is('admin')): ?>
        <form class="topsearch" action="<?= url('admin/search') ?>" method="get" role="search">
          <?= icon('search', 'sm') ?>
          <input name="q" placeholder="Search jobs, certificates, clients…" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search">
        </form>
      <?php endif; ?>
      <div class="dd" data-dd>
        <button class="icon-btn" data-dd-toggle aria-label="Notifications"><?= icon('bell') ?><?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?></button>
        <div class="dd-menu">
          <div class="dd-head">Notifications
            <?php if ($unread): ?><form method="post" action="<?= url('notifications/read-all') ?>"><?= Csrf::field() ?><button class="btn btn-ghost btn-sm">Mark all read</button></form><?php endif; ?>
          </div>
          <div class="dd-list">
            <?php foreach ($notes as $n): ?>
              <a class="dd-item <?= $n['read_at'] ? '' : 'unread' ?>" href="<?= url('notifications/' . $n['id']) ?>">
                <b><?= e($n['title']) ?></b><span><?= e($n['body']) ?> · <?= e(time_ago($n['created_at'])) ?></span>
              </a>
            <?php endforeach; ?>
            <?php if (!$notes): ?><div class="empty" style="padding:24px">You're all caught up.</div><?php endif; ?>
          </div>
          <a class="dd-item" style="text-align:center;font-weight:600" href="<?= url('notifications') ?>">View all</a>
        </div>
      </div>
    </header>

    <main class="content">
      <?php foreach (flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= icon($f['type'] === 'success' ? 'check' : ($f['type'] === 'error' ? 'alert' : 'info')) ?><div><?= nl2br(e($f['message'])) ?></div></div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<div class="toast-wrap" id="toasts" aria-live="polite"></div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
