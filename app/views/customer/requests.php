<div class="page-head">
  <div class="ph-text"><h1>Inspection requests</h1><div class="sub">Requests from your company — submitted here, on our website, by email or by phone.</div></div>
  <div class="actions"><a class="btn btn-primary" href="<?= url('portal/requests/new') ?>"><?= icon('plus', 'sm') ?> New request</a></div>
</div>
<div class="card">
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="t stack-sm">
    <thead><tr><th>Reference</th><th>Location</th><th>Preferred date</th><th class="num">Items</th><th>Received via</th><th>Status</th><th>Job</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr>
        <td data-label="Reference"><span class="mono strong"><?= e($r['request_no']) ?></span><div class="sub"><?= e(fmt_datetime($r['created_at'])) ?></div></td>
        <td data-label="Location"><?= e($r['location']) ?></td>
        <td data-label="Preferred"><?= e(fmt_date($r['preferred_date']) ?: '—') ?></td>
        <td class="num" data-label="Items"><?= (int) $r['items'] ?></td>
        <td data-label="Channel"><?= badge($r['channel']) ?></td>
        <td data-label="Status"><?= badge($r['status']) ?><?= $r['status_note'] ? '<div class="sub">' . e($r['status_note']) . '</div>' : '' ?></td>
        <td data-label="Job"><?= $r['job_no'] ? '<a class="mono" href="' . url('portal/jobs/' . $r['converted_job_id']) . '">' . e($r['job_no']) . '</a>' : '—' ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: View::render('partials/empty', ['icon' => 'inbox', 'title' => 'No requests yet', 'text' => 'Request an inspection and follow it here.', 'action' => '<a class="btn btn-primary" href="' . url('portal/requests/new') . '">New request</a>'], null); endif; ?>
</div>
