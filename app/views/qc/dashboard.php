<div class="page-head">
  <div class="ph-text"><div class="eyebrow">Quality control</div><h1>Review dashboard</h1><div class="sub">Every inspection is reviewed here before a certificate is issued.</div></div>
  <div class="actions"><a class="btn btn-primary" href="<?= url('qc/queue') ?>"><?= icon('shield', 'sm') ?> Open review queue</a></div>
</div>
<div class="kpis">
  <a class="kpi" href="<?= url('qc/queue') ?>"><span class="k-ic t-violet"><?= icon('shield') ?></span><span><div class="k-v"><?= (int) $k['queue'] ?></div><div class="k-l">Awaiting review</div><div class="k-t"><?= (int) $k['overdue'] ?> waiting > 24 h</div></span></a>
  <div class="kpi"><span class="k-ic t-amber"><?= icon('alert') ?></span><span><div class="k-v"><?= (int) $k['overrides'] ?></div><div class="k-l">Submitted with override</div><div class="k-t">need extra scrutiny</div></span></div>
  <div class="kpi"><span class="k-ic t-green"><?= icon('check') ?></span><span><div class="k-v"><?= (int) $k['approved30'] ?></div><div class="k-l">Approved by me (30 d)</div></span></div>
  <div class="kpi"><span class="k-ic t-red"><?= icon('rotate') ?></span><span><div class="k-v"><?= (int) $k['rework30'] ?></div><div class="k-l">Returned by me (30 d)</div></span></div>
  <div class="kpi"><span class="k-ic t-teal"><?= icon('award') ?></span><span><div class="k-v"><?= (int) $k['issued_today'] ?></div><div class="k-l">Certificates issued today</div></span></div>
</div>
<div class="grid grid-main">
  <div class="card"><div class="card-h"><h2><?= icon('clock', 'sm') ?> Oldest in queue</h2><a class="btn btn-ghost btn-sm" href="<?= url('qc/queue') ?>">All <?= icon('arrow-right', 'sm') ?></a></div>
    <?php if ($queue): foreach ($queue as $s) View::render('partials/subjob_row', ['s' => $s, 'href' => url('qc/review/' . $s['id'])], null);
    else: View::render('partials/empty', ['icon' => 'check', 'title' => 'Queue is empty', 'text' => 'Nothing is waiting for review.'], null); endif; ?>
  </div>
  <div class="card"><div class="card-h"><h2><?= icon('layers', 'sm') ?> Queue by inspection type</h2></div>
    <div class="card-b bars"><?php $max = max(1, ...array_map(fn ($r) => (int) $r['n'], $byType ?: [['n' => 1]]));
      foreach ($byType as $r): ?><div class="bar-row"><span><?= e($r['name']) ?></span><div class="track"><span style="width:<?= round($r['n'] / $max * 100) ?>%;background:var(--primary)"></span></div><span class="v"><?= (int) $r['n'] ?></span></div><?php endforeach; ?>
      <?php if (!$byType): ?><p class="muted small">No pending reviews.</p><?php endif; ?></div></div>
</div>
