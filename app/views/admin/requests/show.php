<?php $canConvert = Workflow::can('request', $r['status'], 'converted');
$next = array_values(array_diff(Workflow::TRANSITIONS['request'][$r['status']] ?? [], ['converted'])); ?>
<div class="page-head">
  <div class="ph-text">
    <div class="eyebrow"><?= e(Workflow::label($r['channel'])) ?> request</div>
    <h1 class="row wrap"><span class="mono" style="font-size:20px"><?= e($r['request_no']) ?></span> <?= badge($r['status']) ?></h1>
    <div class="sub"><span><?= icon('building', 'sm') ?> <?= e($r['company_name']) ?></span><span><?= icon('clock', 'sm') ?> <?= e(fmt_datetime($r['created_at'])) ?></span>
      <?php if ($r['job_no']): ?><span><?= icon('briefcase', 'sm') ?> <a href="<?= url('admin/jobs/' . $r['converted_job_id']) ?>"><?= e($r['job_no']) ?></a></span><?php endif; ?></div>
  </div>
  <div class="actions">
    <?php if ($next): ?><button class="btn btn-secondary" data-modal="status-modal"><?= icon('edit', 'sm') ?> Change status</button><?php endif; ?>
    <?php if ($canConvert): ?><a class="btn btn-primary" href="<?= url('admin/requests/' . $r['id'] . '/convert') ?>"><?= icon('briefcase', 'sm') ?> Convert to job</a><?php endif; ?>
  </div>
</div>

<div class="stepper card card-b mb-2">
  <?php $s = $r['status']; ?>
  <div class="step done">Request received</div>
  <div class="step <?= in_array($s, ['under_review', 'quoted', 'converted'], true) ? 'done' : ($s === 'new' ? 'cur' : 'bad') ?>">Reviewed</div>
  <div class="step <?= $s === 'converted' ? 'done' : ($s === 'quoted' ? 'cur' : '') ?>">Job created</div>
  <div class="step">Inspector assigned</div>
  <div class="step">Certificate issued</div>
</div>

<div class="grid grid-main">
  <div class="stack">
    <div class="card">
      <div class="card-h"><h2><?= icon('truck', 'sm') ?> Requested inspections</h2></div>
      <div class="table-wrap"><table class="t">
        <thead><tr><th>Service</th><th>Equipment</th><th>Equipment ID</th><th class="num">Qty</th></tr></thead>
        <tbody><?php foreach ($items as $i): ?>
          <tr><td><?= $i['type_name'] ? e($i['type_name']) : '<span class="muted">Not specified</span>' ?></td><td class="strong"><?= e($i['equipment_description']) ?></td><td class="mono"><?= e($i['equipment_no']) ?></td><td class="num"><?= (int) $i['quantity'] ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
    <?php if ($r['description']): ?>
    <div class="card"><div class="card-h"><h2><?= icon('file', 'sm') ?> Notes</h2></div><div class="card-b" style="white-space:pre-line"><?= e($r['description']) ?></div></div>
    <?php endif; ?>
    <?php if ($r['status_note']): ?><div class="alert alert-info"><?= icon('info') ?><div><b>Status note:</b> <?= e($r['status_note']) ?></div></div><?php endif; ?>
  </div>
  <div class="stack">
    <div class="card">
      <div class="card-h"><h2><?= icon('user', 'sm') ?> Requester</h2></div>
      <div class="card-b"><dl class="kv">
        <dt>Company</dt><dd><?= e($r['company_name']) ?></dd>
        <dt>Contact</dt><dd><?= e($r['contact_name']) ?></dd>
        <dt>Email</dt><dd><?= $r['email'] ? '<a href="mailto:' . e($r['email']) . '">' . e($r['email']) . '</a>' : '—' ?></dd>
        <dt>Phone</dt><dd><?= e($r['phone'] ?: '—') ?></dd>
        <dt>Location</dt><dd><?= e($r['location'] ?: '—') ?></dd>
        <dt>P.O. / ref.</dt><dd><?= e($r['po_no'] ?: '—') ?></dd>
        <dt>Preferred date</dt><dd><?= e(fmt_date($r['preferred_date']) ?: '—') ?></dd>
        <dt>Logged by</dt><dd><?= e($r['received_name'] ?? $r['submitted_name'] ?? 'Website form') ?></dd>
      </dl>
      <?php if (!$r['client_id']): ?>
        <div class="alert alert-warning mt-2" style="margin-bottom:0"><?= icon('alert') ?><div>Not linked to a registered client.<?php if ($match): ?> Possible match: <b><?= e($match['name']) ?></b> (<?= e($match['code']) ?>).<?php endif; ?> You can pick or register the client when converting.</div></div>
      <?php endif; ?>
      </div>
    </div>
    <div class="card"><div class="card-h"><h2><?= icon('history', 'sm') ?> History</h2></div><div class="card-b"><ul class="timeline">
      <?php foreach ($history as $h): $d = json_decode($h['details'] ?? '', true) ?: []; ?>
        <li><b><?= e(ucfirst(str_replace(['request.', '_'], ['', ' '], $h['action']))) ?></b><?= isset($d['to']) ? ' → ' . e(Workflow::label($d['to'])) : '' ?><?= !empty($d['note']) ? '<div class="small">' . e($d['note']) . '</div>' : '' ?>
          <div class="tl-t"><?= e($h['user_name'] ?? 'Website') ?> · <?= e(fmt_datetime($h['created_at'])) ?></div></li>
      <?php endforeach; ?>
    </ul></div></div>
  </div>
</div>

<?php if ($next): ?>
<dialog class="modal" id="status-modal">
  <form method="post" action="<?= url('admin/requests/' . $r['id'] . '/status') ?>">
    <?= Csrf::field() ?>
    <div class="m-h"><h3>Change request status</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
    <div class="m-b stack">
      <div class="field"><label>New status</label><select class="select" name="status"><?php foreach ($next as $n): ?><option value="<?= $n ?>"><?= e(Workflow::label($n)) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Note (visible to the customer for portal requests)</label><textarea class="textarea" name="note" placeholder="e.g. Quotation Q-2026-118 sent"></textarea></div>
    </div>
    <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Update status</button></div>
  </form>
</dialog>
<?php endif; ?>
