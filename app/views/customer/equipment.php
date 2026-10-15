<?php $today = date('Y-m-d'); $soon = date('Y-m-d', strtotime('+30 days')); ?>
<div class="page-head">
  <div class="ph-text"><h1>Equipment register</h1><div class="sub">Every item we have inspected for you, with its current certificate and next due date.</div></div>
  <div class="actions"><?php View::render('partials/export', [], null); ?><a class="btn btn-primary" href="<?= url('portal/requests/new') ?>"><?= icon('plus', 'sm') ?> Book inspection</a></div>
</div>
<div class="card">
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Equipment</th><th>Make / model</th><th>Capacity</th><th>Last inspected</th><th>Next due</th><th>Certificate</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($rows as $e): $n = $e['next_inspection_date']; ?>
      <tr>
        <td data-label="Equipment"><span class="strong"><?= e($e['description']) ?></span><div class="sub mono"><?= e($e['equipment_no']) ?><?= $e['serial_no'] ? ' · S/N ' . e($e['serial_no']) : '' ?></div></td>
        <td data-label="Make"><?= e(trim($e['manufacturer'] . ' ' . $e['model']) ?: '—') ?></td>
        <td data-label="Capacity"><?= e($e['capacity'] ?: '—') ?></td>
        <td data-label="Last"><?= e(fmt_date($e['last_inspection_date']) ?: '—') ?></td>
        <td data-label="Next due"><?php if ($n): ?><span class="badge badge-<?= $n < $today ? 'red' : ($n <= $soon ? 'amber' : 'green') ?>"><i></i><?= $n < $today ? 'Overdue · ' : '' ?><?= e(fmt_date($n)) ?></span><?php else: ?><span class="muted">Not yet inspected</span><?php endif; ?></td>
        <td data-label="Certificate"><?= $e['last_cert'] ? '<a class="mono small" href="' . url('documents/certificate/' . $e['last_cert_sj']) . '">' . e($e['last_cert']) . '</a>' : '—' ?></td>
        <td data-label="Status"><?= badge($e['status']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: View::render('partials/empty', ['icon' => 'truck', 'title' => 'No equipment yet', 'text' => 'Equipment is added automatically when we inspect it.'], null); endif; ?>
</div>
