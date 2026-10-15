<div class="page-head"><div class="ph-text"><h1>Search results</h1><div class="sub">for “<?= e($q) ?>”</div></div></div>
<?php $total = array_sum(array_map('count', $res));
if (!$total): ?>
  <div class="card"><?php View::render('partials/empty', ['icon' => 'search', 'title' => 'No matches', 'text' => 'Try a job no., certificate no., equipment ID, serial no. or client name (min. 2 characters).'], null); ?></div>
<?php else: ?>
<div class="grid grid-2">
  <?php if ($res['jobs']): ?><div class="card"><div class="card-h"><h2>Jobs</h2></div><div class="card-b flush"><table class="t"><tbody>
    <?php foreach ($res['jobs'] as $r): ?><tr class="row-link" data-href="<?= url('admin/jobs/' . $r['id']) ?>"><td><span class="mono strong"><?= e($r['job_no']) ?></span><div class="sub"><?= e($r['title']) ?> · <?= e($r['client_name']) ?></div></td><td class="right"><?= badge($r['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div><?php endif; ?>
  <?php if ($res['subjobs']): ?><div class="card"><div class="card-h"><h2>Sub-jobs & equipment</h2></div><div class="card-b flush"><table class="t"><tbody>
    <?php foreach ($res['subjobs'] as $r): ?><tr class="row-link" data-href="<?= url('admin/jobs/' . $r['job_id']) ?>"><td><span class="mono strong"><?= e($r['sub_job_no']) ?></span><div class="sub"><?= e($r['type_name']) ?> · <?= e($r['eq_description']) ?> <?= e($r['equipment_no']) ?></div></td><td class="right"><?= badge($r['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div><?php endif; ?>
  <?php if ($res['certs']): ?><div class="card"><div class="card-h"><h2>Certificates</h2></div><div class="card-b flush"><table class="t"><tbody>
    <?php foreach ($res['certs'] as $r): ?><tr class="row-link" data-href="<?= url('documents/certificate/' . $r['sub_job_id']) ?>"><td><span class="mono strong"><?= e($r['certificate_no']) ?></span><div class="sub"><?= e($r['sub_job_no']) ?></div></td><td class="right"><?= badge($r['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div><?php endif; ?>
  <?php if ($res['clients']): ?><div class="card"><div class="card-h"><h2>Clients</h2></div><div class="card-b flush"><table class="t"><tbody>
    <?php foreach ($res['clients'] as $r): ?><tr class="row-link" data-href="<?= url('admin/clients/' . $r['id']) ?>"><td><span class="strong"><?= e($r['name']) ?></span><div class="sub"><?= e($r['code']) ?> · <?= e($r['city']) ?></div></td></tr><?php endforeach; ?>
  </tbody></table></div></div><?php endif; ?>
  <?php if ($res['requests']): ?><div class="card"><div class="card-h"><h2>Requests</h2></div><div class="card-b flush"><table class="t"><tbody>
    <?php foreach ($res['requests'] as $r): ?><tr class="row-link" data-href="<?= url('admin/requests/' . $r['id']) ?>"><td><span class="mono strong"><?= e($r['request_no']) ?></span><div class="sub"><?= e($r['company_name']) ?></div></td><td class="right"><?= badge($r['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div><?php endif; ?>
</div>
<?php endif; ?>
