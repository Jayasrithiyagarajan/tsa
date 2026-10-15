<div class="page-head"><div class="ph-text"><h1>Notifications</h1></div>
  <form method="post" action="<?= url('notifications/read-all') ?>"><?= Csrf::field() ?><button class="btn btn-secondary"><?= icon('check', 'sm') ?> Mark all read</button></form></div>
<div class="card">
  <?php if ($rows): foreach ($rows as $n): ?>
    <a class="dd-item <?= $n['read_at'] ? '' : 'unread' ?>" href="<?= url('notifications/' . $n['id']) ?>" style="padding:14px 18px">
      <b><?= e($n['title']) ?></b><span><?= e($n['body']) ?> · <?= e(fmt_datetime($n['created_at'])) ?></span></a>
  <?php endforeach; View::render('partials/pager', ['meta' => $meta], null);
  else: View::render('partials/empty', ['icon' => 'bell', 'title' => 'No notifications', 'text' => 'Assignments, QC decisions and certificate releases appear here.'], null); endif; ?>
</div>
