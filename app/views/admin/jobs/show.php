<?php
$open = !in_array($job['status'], ['completed', 'cancelled'], true);
$assignable = array_filter($subs, fn ($s) => in_array($s['status'], ['pending', 'assigned', 'in_progress', 'rework'], true));
$inspSkills = [];
foreach ($inspectors as $u) $inspSkills[$u['id']] = $u['skills'];
?>
<div class="page-head">
  <div class="ph-text">
    <div class="eyebrow">Job · <?= e($job['client_name']) ?></div>
    <h1 class="row wrap"><span class="mono" style="font-size:20px"><?= e($job['job_no']) ?></span> <?= badge($job['status']) ?> <?= badge($job['priority']) ?></h1>
    <div class="sub">
      <span><?= icon('briefcase', 'sm') ?> <?= e($job['title']) ?></span>
      <?php if ($job['location']): ?><span><?= icon('pin', 'sm') ?> <?= e($job['location']) ?></span><?php endif; ?>
      <?php if ($job['scheduled_date']): ?><span><?= icon('calendar', 'sm') ?> <?= e(fmt_date($job['scheduled_date'])) ?></span><?php endif; ?>
      <?php if ($job['request_no']): ?><span><?= icon('inbox', 'sm') ?> <a href="<?= url('admin/requests/' . $job['request_id']) ?>"><?= e($job['request_no']) ?></a> (<?= e(Workflow::label($job['channel'])) ?>)</span><?php endif; ?>
    </div>
  </div>
  <div class="actions">
    <?php if ($open): ?>
      <button class="btn btn-secondary" data-modal="edit-job"><?= icon('edit', 'sm') ?> Edit</button>
      <button class="btn btn-secondary" data-modal="add-sub"><?= icon('plus', 'sm') ?> Add sub-job</button>
      <button class="btn btn-danger" data-modal="cancel-job"><?= icon('x', 'sm') ?> Cancel job</button>
    <?php endif; ?>
  </div>
</div>

<div class="card card-b mb-2">
  <div class="progress-label"><span class="strong" style="color:var(--text)">Job progress</span><span><?= $progress['approved'] ?> of <?= $progress['total'] ?> sub-jobs approved · <?= $progress['submitted'] ?> at QC · <?= $progress['active'] ?> in inspection · <?= $progress['unassigned'] ?> unassigned</span></div>
  <div class="progress" style="height:10px"><span style="width:<?= $progress['pct'] ?>%"></span></div>
</div>

<div class="stack">
  <div class="stack">
    <form class="card" id="assign" method="post" action="<?= url('admin/jobs/' . $job['id'] . '/assign') ?>" data-qualified-filter>
      <?= Csrf::field() ?>
      <div class="card-h"><h2><?= icon('layers', 'sm') ?> Sub-jobs</h2><span class="hint"><?= count($subs) ?> total</span></div>
      <div class="table-wrap"><table class="t">
        <thead><tr>
          <th style="width:34px"><?php if ($assignable): ?><input type="checkbox" data-check-all aria-label="Select all"><?php endif; ?></th>
          <th>Sub-job</th><th>Inspection / equipment</th><th>Inspector</th><th>Documents</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($subs as $s): $can = in_array($s['status'], ['pending', 'assigned', 'in_progress', 'rework'], true); ?>
          <tr>
            <td><?php if ($can): ?><input type="checkbox" name="sub_job_ids[]" value="<?= $s['id'] ?>" data-type="<?= e($s['type_code']) ?>"<?= $s['status'] === 'pending' ? ' checked' : '' ?> aria-label="Select <?= e($s['sub_job_no']) ?>"><?php endif; ?></td>
            <td class="nowrap"><span class="mono strong" style="font-size:14px"><?= (int) substr($job['job_no'], -4) ?>.<?= (int) $s['seq'] ?></span><div class="sub mono" style="font-size:11px"><?= e($s['sub_job_no']) ?></div></td>
            <td><span class="strong"><?= e($s['type_name']) ?></span><div class="sub"><?= e($s['eq_description'] ?: 'Equipment not registered') ?><?= $s['equipment_no'] ? ' · ' . e($s['equipment_no']) : '' ?></div></td>
            <td><?= $s['inspector_name'] ? '<span class="person"><span class="avatar sm">' . e(initials($s['inspector_name'])) . '</span>' . e($s['inspector_name']) . '</span>' : '<span class="badge badge-amber"><i></i>Unassigned</span>' ?></td>
            <td class="nowrap">
              <?php if ($s['checklist']): ?><a class="pill" href="<?= url('documents/checklist/' . $s['id']) ?>" title="Checklist"><?= icon('clipboard', 'sm') ?> <?= $s['checklist']['status'] === 'completed' ? 'Done' : 'Draft' ?></a><?php endif; ?>
              <?php if ($s['cert']): ?><a class="pill" href="<?= url('documents/certificate/' . $s['id']) ?>" title="<?= e($s['cert']['certificate_no']) ?>"><?= icon('award', 'sm') ?> <?= e(Workflow::label($s['cert']['status'])) ?></a><?php endif; ?>
              <?php if ($s['sticker']): ?><span class="pill" title="Sticker"><?= icon('tag', 'sm') ?> <?= e($s['sticker']['sticker_no']) ?></span><?php endif; ?>
              <?php if (!$s['checklist'] && !$s['cert']): ?><span class="muted small">Not started</span><?php endif; ?>
            </td>
            <td><?= badge($s['status']) ?><?= $s['override_by'] ? '<div class="sub" title="' . e($s['override_reason']) . '">override</div>' : '' ?></td>
            <td class="actions">
              <div class="dd" data-dd style="display:inline-block">
                <button type="button" class="btn btn-ghost btn-sm" data-dd-toggle aria-label="Actions">•••</button>
                <div class="dd-menu" style="min-width:220px">
                  <a class="dd-item" href="<?= url('inspector/subjobs/' . $s['id']) ?>"><b>Open inspection workspace</b></a>
                  <?php if ($s['status'] === 'submitted'): ?><a class="dd-item" href="<?= url('qc/review/' . $s['id']) ?>"><b>Open QC review</b></a><?php endif; ?>
                  <?php if ($s['sticker']): ?><a class="dd-item" href="<?= url('documents/sticker/' . $s['sticker']['id']) ?>"><b>Print sticker</b></a><?php endif; ?>
                  <?php if (Workflow::can('sub_job', $s['status'], 'pending') && $s['inspector_id']): ?>
                    <button type="submit" class="dd-item" style="width:100%;text-align:left;border:0;background:none;cursor:pointer" formaction="<?= url('admin/subjobs/' . $s['id'] . '/unassign') ?>" formnovalidate><b>Remove inspector</b></button>
                  <?php endif; ?>
                  <?php if ($s['status'] === 'in_progress'): ?>
                    <a class="dd-item" href="#" data-modal="override-<?= $s['id'] ?>"><b>Force submit to QC…</b><span>Override incomplete inspection</span></a>
                  <?php endif; ?>
                  <?php if (Workflow::can('sub_job', $s['status'], 'cancelled')): ?>
                    <a class="dd-item" href="#" data-modal="cancel-sub-<?= $s['id'] ?>" style="color:var(--red)"><b>Cancel sub-job…</b></a>
                  <?php endif; ?>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php if ($assignable): ?>
      <div class="card-f" style="justify-content:flex-start;align-items:center">
        <span class="small muted"><b data-selected-count>0</b> selected</span>
        <select class="select" name="inspector_id" style="width:auto;min-width:240px" required>
          <option value="">Assign selected to…</option>
          <?php foreach ($inspectors as $u): ?>
            <option value="<?= $u['id'] ?>" data-skills="<?= e(str_replace(' ', '', (string) $u['skills'])) ?>" data-label="<?= e($u['name'] . ' · ' . $u['open_load'] . ' open') ?>"><?= e($u['name'] . ' · ' . $u['open_load'] . ' open') ?></option>
          <?php endforeach; ?>
        </select>
        <input class="input" name="note" placeholder="Note for inspector (optional)" style="width:auto;flex:1;min-width:160px">
        <button class="btn btn-primary"><?= icon('users', 'sm') ?> Assign</button>
      </div>
      <?php endif; ?>
    </form>
  </div>
  <div class="grid grid-2" style="align-items:start">
    <div class="card"><div class="card-h"><h2><?= icon('history', 'sm') ?> Activity</h2></div><div class="card-b"><ul class="timeline">
      <?php foreach ($history as $h): $d = json_decode($h['details'] ?? '', true) ?: []; ?>
        <li><b><?= e(ucfirst(str_replace(['.', '_'], [' · ', ' '], $h['action']))) ?></b> <span class="muted small"><?= e($h['entity_type'] === 'sub_job' ? 'sub-job #' . $h['entity_id'] : '') ?></span>
          <?php if (!empty($d['to'])): ?> → <?= e(Workflow::label($d['to'])) ?><?php endif; ?>
          <?php if (!empty($d['comments']) || !empty($d['reason'])): ?><div class="small">“<?= e($d['comments'] ?? $d['reason']) ?>”</div><?php endif; ?>
          <div class="tl-t"><?= e($h['user_name'] ?? 'System') ?> · <?= e(fmt_datetime($h['created_at'])) ?></div></li>
      <?php endforeach; ?>
    </ul></div></div>

  <div class="stack">
    <div class="card"><div class="card-h"><h2><?= icon('building', 'sm') ?> Client</h2><a class="btn btn-ghost btn-sm" href="<?= url('admin/clients/' . $job['client_id']) ?>">Open</a></div>
      <div class="card-b"><dl class="kv">
        <dt>Company</dt><dd><?= e($job['client_name']) ?> <span class="pill"><?= e($job['client_code']) ?></span></dd>
        <dt>Contact</dt><dd><?= e($job['contact_person'] ?: '—') ?></dd>
        <dt>Phone</dt><dd><?= e($job['client_phone'] ?: '—') ?></dd>
        <dt>Email</dt><dd><?= e($job['client_email'] ?: '—') ?></dd>
        <dt>P.O. No.</dt><dd><?= e($job['po_no'] ?: 'N/A') ?></dd>
        <dt>Lead inspector</dt><dd><?= e($job['lead_name'] ?: '—') ?></dd>
        <dt>Created by</dt><dd><?= e($job['created_name'] ?: 'System') ?> · <?= e(fmt_date($job['created_at'])) ?></dd>
      </dl></div></div>
    <?php if ($job['notes']): ?><div class="card"><div class="card-h"><h2><?= icon('file', 'sm') ?> Notes</h2></div><div class="card-b small" style="white-space:pre-line"><?= e($job['notes']) ?></div></div><?php endif; ?>
    <div class="card"><div class="card-h"><h2><?= icon('info', 'sm') ?> Assignment rules</h2></div><div class="card-b small muted">
      Inspectors can only be assigned to inspection types they are qualified for (Users → qualifications). Re-assigning an in-progress sub-job keeps the saved checklist. Submitted or approved sub-jobs are locked.</div></div>
  </div>
</div>
</div>

<?php if ($open): ?>
<dialog class="modal" id="edit-job"><form method="post" action="<?= url('admin/jobs/' . $job['id'] . '/update') ?>"><?= Csrf::field() ?>
  <div class="m-h"><h3>Edit job</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-12"><label>Title</label><input class="input" name="title" value="<?= e($job['title']) ?>" required></div>
    <div class="field col-6"><label>Location</label><input class="input" name="location" value="<?= e($job['location']) ?>"></div>
    <div class="field col-6"><label>P.O. No.</label><input class="input" name="po_no" value="<?= e($job['po_no']) ?>"></div>
    <div class="field col-4"><label>Scheduled</label><input class="input" type="date" name="scheduled_date" value="<?= e($job['scheduled_date']) ?>"></div>
    <div class="field col-4"><label>Due</label><input class="input" type="date" name="due_date" value="<?= e($job['due_date']) ?>"></div>
    <div class="field col-4"><label>Priority</label><select class="select" name="priority"><?php foreach (['low', 'normal', 'high', 'urgent'] as $p): ?><option value="<?= $p ?>"<?= selected($p, $job['priority']) ?>><?= e(Workflow::label($p)) ?></option><?php endforeach; ?></select></div>
    <div class="field col-12"><label>Notes</label><textarea class="textarea" name="notes"><?= e($job['notes']) ?></textarea></div>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Save</button></div>
</form></dialog>

<dialog class="modal" id="add-sub"><form method="post" action="<?= url('admin/jobs/' . $job['id'] . '/subjobs') ?>"><?= Csrf::field() ?>
  <div class="m-h"><h3>Add sub-job</h3><button type="button" class="icon-btn" data-modal-close><?= icon('x', 'sm') ?></button></div>
  <div class="m-b"><div class="form-grid">
    <div class="field col-12"><label>Inspection type</label><select class="select" name="inspection_type_id" required><option value="">Select…</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field col-12"><label>Registered equipment</label><select class="select" name="equipment_id"><option value="">— New equipment —</option><?php foreach ($equipment as $eq): ?><option value="<?= $eq['id'] ?>"><?= e(($eq['equipment_no'] ? $eq['equipment_no'] . ' · ' : '') . $eq['description']) ?></option><?php endforeach; ?></select></div>
    <div class="field col-6"><label>…or new equipment description</label><input class="input" name="equipment_description"></div>
    <div class="field col-3"><label>Equipment ID</label><input class="input" name="equipment_no"></div>
    <div class="field col-3"><label>Serial No.</label><input class="input" name="serial_no"></div>
    <div class="field col-12"><label>Inspector (optional)</label><select class="select" name="inspector_id"><option value="">Assign later</option><?php foreach ($inspectors as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['skills']) ?>)</option><?php endforeach; ?></select></div>
  </div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary">Add sub-job</button></div>
</form></dialog>

<dialog class="modal" id="cancel-job"><form method="post" action="<?= url('admin/jobs/' . $job['id'] . '/cancel') ?>"><?= Csrf::field() ?>
  <div class="m-h"><h3>Cancel job <?= e($job['job_no']) ?>?</h3></div>
  <div class="m-b"><p class="muted small">All open sub-jobs are cancelled and reserved stickers return to stock. Jobs with submitted or approved inspections cannot be cancelled.</p>
    <div class="field mt-2"><label>Reason</label><textarea class="textarea" name="reason" required></textarea></div></div>
  <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Keep job</button><button class="btn btn-danger solid">Cancel job</button></div>
</form></dialog>
<?php endif; ?>

<?php foreach ($subs as $s): ?>
  <?php if ($s['status'] === 'in_progress'): ?>
  <dialog class="modal" id="override-<?= $s['id'] ?>"><form method="post" action="<?= url('admin/subjobs/' . $s['id'] . '/override') ?>"><?= Csrf::field() ?>
    <div class="m-h"><h3>Force submit <?= e($s['sub_job_no']) ?></h3></div>
    <div class="m-b"><div class="alert alert-warning"><?= icon('alert') ?><div>Use only when an incomplete inspection must proceed to QC (e.g. item physically inaccessible). The override and reason are permanently recorded.</div></div>
      <div class="field"><label>Override reason</label><textarea class="textarea" name="reason" required></textarea></div></div>
    <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-danger solid">Submit with override</button></div>
  </form></dialog>
  <?php endif; ?>
  <?php if (Workflow::can('sub_job', $s['status'], 'cancelled')): ?>
  <dialog class="modal" id="cancel-sub-<?= $s['id'] ?>"><form method="post" action="<?= url('admin/subjobs/' . $s['id'] . '/cancel') ?>"><?= Csrf::field() ?>
    <div class="m-h"><h3>Cancel sub-job <?= e($s['sub_job_no']) ?>?</h3></div>
    <div class="m-b"><div class="field"><label>Reason</label><textarea class="textarea" name="reason" required></textarea></div></div>
    <div class="m-f"><button type="button" class="btn btn-ghost" data-modal-close>Keep</button><button class="btn btn-danger solid">Cancel sub-job</button></div>
  </form></dialog>
  <?php endif; ?>
<?php endforeach; ?>
