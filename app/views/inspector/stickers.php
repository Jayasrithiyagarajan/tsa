<?php $blue = array_values(array_filter($hand, fn ($s) => $s['color'] !== 'red')); $red = array_values(array_filter($hand, fn ($s) => $s['color'] === 'red')); ?>
<div class="page-head"><div class="ph-text"><h1>My stickers</h1><div class="sub">Physical stickers the office has handed to you. Link one to a job from the job's Sticker tab — once linked it is closed and locked to that job.</div></div></div>
<div class="kpis">
  <div class="kpi"><span class="k-ic t-blue"><?= icon('tag') ?></span><span><div class="k-v"><?= count($blue) ?></div><div class="k-l">Blue in hand</div><div class="k-t">For Accepted results</div></span></div>
  <div class="kpi"><span class="k-ic t-red"><?= icon('tag') ?></span><span><div class="k-v"><?= count($red) ?></div><div class="k-l">Red in hand</div><div class="k-t">For Failed / repair results</div></span></div>
  <div class="kpi"><span class="k-ic t-gray"><?= icon('lock') ?></span><span><div class="k-v"><?= (int) $meta['total'] ?></div><div class="k-l">Linked to jobs</div><div class="k-t">Closed</div></span></div>
</div>
<div class="card">
  <div class="toolbar"><div class="seg-tabs">
    <a class="<?= $tab === 'hand' ? 'on' : '' ?>" href="<?= url('inspector/stickers') ?>">In hand<span class="n"><?= count($hand) ?></span></a>
    <a class="<?= $tab === 'used' ? 'on' : '' ?>" href="<?= url('inspector/stickers', ['tab' => 'used']) ?>">Linked to jobs<span class="n"><?= (int) $meta['total'] ?></span></a>
  </div></div>
  <?php if ($tab === 'hand'): ?>
    <?php if ($hand): ?>
      <div class="card-b stack">
        <?php foreach (['Blue · Accepted' => $blue, 'Red · Failed' => $red] as $label => $list): if (!$list) continue; ?>
          <div><div class="small strong mb-2"><?= e($label) ?> (<?= count($list) ?>)</div>
            <div class="row wrap" style="gap:6px"><?php foreach ($list as $s): ?>
              <span class="pill mono" title="Allocated <?= e(fmt_datetime($s['allocated_at'])) ?>"><i style="display:inline-block;width:8px;height:8px;border-radius:2px;margin-right:5px;background:<?= $s['color'] === 'red' ? 'var(--red)' : '#1d4fb8' ?>"></i><?= e($s['sticker_no']) ?></span>
            <?php endforeach; ?></div></div>
        <?php endforeach; ?>
      </div>
    <?php else: View::render('partials/empty', ['icon' => 'tag', 'title' => 'No stickers in hand', 'text' => 'Ask the office to allocate a batch of stickers to you.'], null); endif; ?>
  <?php else: ?>
    <?php if ($used): ?>
    <div class="table-wrap"><table class="t stack-sm">
      <thead><tr><th>Sticker</th><th>Job</th><th>Equipment</th><th>Linked</th><th>Status</th><th></th></tr></thead>
      <tbody><?php foreach ($used as $s): ?>
        <tr>
          <td data-label="Sticker"><span class="row" style="gap:8px"><i style="width:10px;height:10px;border-radius:3px;background:<?= $s['color'] === 'red' ? 'var(--red)' : '#1d4fb8' ?>"></i><span class="mono strong"><?= e($s['sticker_no']) ?></span></span></td>
          <td data-label="Job"><a class="mono" href="<?= url('inspector/subjobs/' . $s['sub_job_id']) ?>"><?= e($s['sub_job_no']) ?></a><div class="sub"><?= e($s['client_name']) ?></div></td>
          <td data-label="Equipment"><?= e($s['eq_description']) ?> <span class="sub"><?= e($s['equipment_no']) ?></span></td>
          <td data-label="Linked" class="nowrap small"><?= e(fmt_datetime($s['linked_at'])) ?></td>
          <td data-label="Status"><?= badge($s['status']) ?> <?= badge($s['sj_status']) ?><?= $s['void_reason'] ? '<div class="sub">' . e($s['void_reason']) . '</div>' : '' ?></td>
          <td class="actions"><?php if ($s['status'] === 'closed'): ?><a class="btn btn-ghost btn-sm" href="<?= url('documents/sticker/' . $s['id']) ?>" title="Digital sticker"><?= icon('eye', 'sm') ?></a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php View::render('partials/pager', ['meta' => $meta], null); ?>
    <?php else: View::render('partials/empty', ['icon' => 'lock', 'title' => 'No stickers linked yet', 'text' => ''], null); endif; ?>
  <?php endif; ?>
</div>
