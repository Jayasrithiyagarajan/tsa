<?php $pending = $sj['status'] === 'submitted'; $self = (int) $sj['inspector_id'] === Auth::id(); ?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow">QC review · <?= e($sj['type_name']) ?></div>
    <h1 class="row wrap"><span class="mono" style="font-size:20px"><?= e($sj['sub_job_no']) ?></span> <?= badge($sj['status']) ?> <?= $cert && $cert['result'] ? badge($cert['result']) : '' ?></h1>
    <div class="sub"><span><?= icon('building', 'sm') ?> <?= e($sj['client_name']) ?></span><span><?= icon('truck', 'sm') ?> <?= e($sj['eq_description']) ?> <?= e($sj['equipment_no']) ?></span><span><?= icon('user', 'sm') ?> <?= e($sj['inspector_name']) ?></span><span><?= icon('clock', 'sm') ?> submitted <?= e(fmt_datetime($sj['submitted_at'])) ?></span></div></div>
</div>
<?php if ($override): ?><div class="alert alert-warning"><?= icon('alert') ?><div><b>Submitted with an authorized override by <?= e($override) ?>:</b> <?= e($sj['override_reason']) ?></div></div><?php endif; ?>

<div class="grid grid-main">
  <div class="stack">
    <div class="card">
      <div class="tabs" data-tabs>
        <?php if ($cert && $cert['template_id']): ?><button data-tab="doc-cert"><?= icon('award', 'sm') ?> Certificate</button><?php endif; ?>
        <?php if ($checklist): ?><button data-tab="doc-cl"><?= icon('clipboard', 'sm') ?> Checklist</button><?php endif; ?>
        <button data-tab="doc-files"><?= icon('camera', 'sm') ?> Photos & files <span class="t-count"><?= count($attachments) ?></span></button>
      </div>
      <?php if ($cert && $cert['template_id']): ?><section class="tab-panel" id="doc-cert"><iframe src="<?= url('documents/certificate/' . $sj['id']) ?>" title="Certificate" style="width:100%;height:78vh;border:0;display:block;background:#525a6b"></iframe></section><?php endif; ?>
      <?php if ($checklist): ?><section class="tab-panel" id="doc-cl"><iframe src="<?= url('documents/checklist/' . $sj['id']) ?>" title="Checklist" style="width:100%;height:78vh;border:0;display:block;background:#525a6b" loading="lazy"></iframe></section><?php endif; ?>
      <section class="tab-panel" id="doc-files"><div class="card-b">
        <?php if ($attachments): ?><div class="photos"><?php foreach ($attachments as $a): $img = str_starts_with($a['mime'], 'image/'); ?>
          <a class="photo" href="<?= url('documents/attachment/' . $a['id']) ?>" target="_blank"><?php if ($a['on_certificate']): ?><span class="tagc badge badge-green"><i></i>On certificate</span><?php endif; ?>
            <?php if ($img): ?><img src="<?= url('documents/attachment/' . $a['id']) ?>" alt="" loading="lazy"><?php else: ?><div style="height:120px;display:grid;place-items:center;color:var(--muted)"><?= icon('file', 'lg') ?></div><?php endif; ?>
            <div class="ph-b"><span><?= e($a['caption'] ?: $a['original_name']) ?></span></div></a>
        <?php endforeach; ?></div><?php else: ?><p class="muted">No photos or files were attached.</p><?php endif; ?>
      </div></section>
    </div>
  </div>

  <div class="stack" style="position:sticky;top:76px">
    <?php if ($pending): ?>
    <form class="card" method="post" action="<?= url('qc/review/' . $sj['id']) ?>">
      <?= Csrf::field() ?>
      <div class="card-h"><h2><?= icon('shield', 'sm') ?> Decision</h2></div>
      <div class="card-b stack">
        <?php if ($issues): ?><div class="alert alert-warning" style="margin:0"><?= icon('alert') ?><div><b>Completeness warnings</b><ul><?php foreach ($issues as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul></div></div>
        <?php else: ?><div class="ok-line"><?= icon('check') ?> All completeness rules pass</div><?php endif; ?>
        <div class="field"><label>Comments to inspector <span class="muted">(required for rework)</span></label><textarea class="textarea" name="comments" placeholder="What was checked, or what must be corrected…"></textarea></div>
        <?php if ($self): ?><div class="alert alert-error" style="margin:0"><?= icon('lock') ?><div>Four-eyes rule: you performed this inspection and cannot approve it.</div></div><?php endif; ?>
      </div>
      <div class="card-f" style="justify-content:stretch">
        <button class="btn btn-danger" name="decision" value="rework" style="flex:1"><?= icon('rotate', 'sm') ?> Return for rework</button>
        <button class="btn btn-success" name="decision" value="approve" style="flex:1"<?= $self ? ' disabled' : '' ?> data-confirm><?= icon('check', 'sm') ?> Approve & issue</button>
      </div>
    </form>
    <?php endif; ?>
    <div class="card"><div class="card-h"><h2><?= icon('clipboard', 'sm') ?> Summary</h2></div><div class="card-b">
      <dl class="kv small" style="grid-template-columns:120px 1fr">
        <dt>Certificate No.</dt><dd class="mono"><?= e($cert['certificate_no'] ?? '—') ?></dd>
        <dt>Inspection date</dt><dd><?= e(fmt_date($cert['inspection_date'] ?? null) ?: '—') ?></dd>
        <dt>Next due</dt><dd><?= e(fmt_date($cert['next_inspection_date'] ?? null) ?: '—') ?></dd>
        <?php if ($stats): ?><dt>Checklist</dt><dd><?= (int) $stats['pass'] ?> pass · <b style="color:var(--red)"><?= (int) $stats['fail'] ?> fail</b> · <?= (int) $stats['na'] ?> N/A · <?= (int) $stats['missing'] ?> open</dd><?php endif; ?>
        <?php if ($sj['requires_sticker']): ?><dt>Sticker</dt><dd><?= $sticker ? '<span class="mono">' . e($sticker['sticker_no']) . '</span> (' . e(Workflow::label($sticker['status'])) . ')' : '<span class="muted">none</span>' ?></dd><?php endif; ?>
      </dl>
      <?php if ($failed): ?><div class="divider"></div><div class="strong small mb-2">Failed items & remarks</div>
        <ul class="issue-list"><?php foreach ($failed as $f): ?><li><?= badge($f['result'] ?? 'na') ?><span><b><?= nl2br(e(preg_replace('/^(## |>> )/m', '', $f['label']))) ?></b><?= $f['remarks'] ? '<br><span class="muted">' . e($f['remarks']) . '</span>' : '' ?></span></li><?php endforeach; ?></ul><?php endif; ?>
      <?php if ($cert && $cert['remarks']): ?><div class="divider"></div><div class="strong small">Inspector remarks</div><p class="small mt-1" style="white-space:pre-line"><?= e($cert['remarks']) ?></p><?php endif; ?>
    </div></div>
    <?php if ($reviews): ?><div class="card"><div class="card-h"><h2><?= icon('history', 'sm') ?> Previous reviews</h2></div><div class="card-b"><ul class="timeline">
      <?php foreach ($reviews as $rv): ?><li><?= badge($rv['decision'] === 'approved' ? 'approved' : 'rework') ?> <?= e($rv['reviewer']) ?><?= $rv['comments'] ? '<div class="small">' . e($rv['comments']) . '</div>' : '' ?><div class="tl-t"><?= e(fmt_datetime($rv['created_at'])) ?></div></li><?php endforeach; ?>
    </ul></div></div><?php endif; ?>
  </div>
</div>
