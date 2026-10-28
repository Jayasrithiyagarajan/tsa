<?php
/** Inspector: operator assessment sub-job — issued ID card + document upload. @var array $sj @var ?array $card @var ?array $doc */
$open = $sj['status'] === 'in_progress';
$preview = $card ? InspectorCardService::model($card) : null;
$od = json_decode((string) ($sj['operator_data'] ?? ''), true) ?: [];
?>
<style><?= file_get_contents(ROOT_PATH . '/assets/css/idcard.css') ?>
.idc * { box-sizing: content-box; }
.idc-faces { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 18px; background: #eef1f6; border-radius: 12px; }
.idc-faces .cap { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
.idc-faces .idc { zoom: 1.2; box-shadow: 0 2px 10px rgba(15,23,42,.18); }
@media (max-width: 760px) { .idc-faces { padding: 10px; } .idc-faces .idc { zoom: .9; } }
</style>
<div class="page-head">
  <div class="ph-text">
    <div class="eyebrow"><?= e($sj['client_name']) ?> · <?= e($sj['job_no']) ?></div>
    <h1>Operator assessment <span class="mono"><?= e($sj['sub_job_no']) ?></span></h1>
    <div class="sub"><?= $open ? 'ID card issued. Upload the assessment document to close this sub-job.' : 'Closed' . ($sj['approved_at'] ? ' on ' . e(fmt_datetime($sj['approved_at'])) : '') . '.' ?></div>
  </div>
  <div class="actions"><a class="btn btn-secondary" href="<?= url('inspector/jobs/' . $sj['job_id']) ?>">&larr; Back to job</a></div>
</div>
<div class="grid grid-main">
  <div class="stack">
    <?php if ($card): ?>
    <div class="card"><div class="card-h"><h2><?= icon('user', 'sm') ?> <?= e($card['holder_name']) ?> <span class="mono small muted"><?= e($card['card_no']) ?></span> <?= InspectorCardService::badge(InspectorCardService::state($card)) ?></h2></div>
      <div class="card-b">
        <div class="idc-faces">
          <div><div class="cap">Front</div><?php View::render('partials/id_card', ['m' => $preview, 'side' => 'front'], null); ?></div>
          <div><div class="cap">Back</div><?php View::render('partials/id_card', ['m' => $preview, 'side' => 'back'], null); ?></div>
        </div>
        <p class="small muted mt-1" style="text-align:center"><a href="<?= url('verify/card/' . $card['verify_token']) ?>" target="_blank" rel="noopener">Verification page</a> · valid <?= e(fmt_date($card['issue_date'])) ?> – <?= e(fmt_date($card['expiry_date'])) ?></p>
      </div></div>
    <?php endif; ?>
  </div>
  <div class="stack" style="position:sticky;top:76px">
    <div class="card">
      <div class="card-h"><h2><?= icon('upload', 'sm') ?> Assessment document</h2></div>
      <div class="card-b">
      <?php if ($open): ?>
        <form method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/operator-document') ?>" enctype="multipart/form-data">
          <?= Csrf::field() ?>
          <div class="field"><label>Document (PDF or image) <span class="req">*</span></label><input class="input" type="file" name="document" required accept=".pdf,image/*" style="padding-top:7px"></div>
          <div class="field mt-1"><label>Note (optional)</label><input class="input" name="caption" maxlength="190"></div>
          <hr style="margin:14px 0;border:0;border-top:1px solid var(--border)">
          <div class="small" style="font-weight:600;margin-bottom:6px">Certificate of Competence</div>
          <div class="field"><label>Training program <span class="req">*</span></label><input class="input" name="program" required maxlength="190" placeholder="e.g. FIRE WATCH AWARENESS" value="<?= e(old('program')) ?>"></div>
          <div class="field mt-1"><label>Completion date <span class="req">*</span></label><input class="input" type="date" name="completion_date" required value="<?= e(old('completion_date', date('Y-m-d'))) ?>"></div>
          <div class="field mt-1"><label>Recommended renewal date</label><input class="input" type="date" name="renewal_date" value="<?= e(old('renewal_date', $card ? $card['expiry_date'] : '')) ?>"></div>
          <div class="field mt-1"><label>Trainer / Instructor</label><input class="input" name="trainer" maxlength="120" value="<?= e(old('trainer', $card['assessor'] ?? (Auth::user()['name'] ?? ''))) ?>"></div>
          <button class="btn btn-primary btn-block mt-1" onclick="return confirm('Upload the document and close this operator assessment?')"><?= icon('check', 'sm') ?> Upload &amp; close</button>
          <p class="small muted mt-1">Uploading the document closes <?= e($sj['sub_job_no']) ?>.</p>
        </form>
      <?php else: ?>
        <p class="small"><?= icon('check', 'sm') ?> Closed.</p>
        <?php if (empty($od['program'])): ?>
        <form method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/operator-certificate') ?>" class="mb-1">
          <?= Csrf::field() ?>
          <div class="alert alert-warning small">Certificate details are missing. Fill them in to generate the Certificate of Competence.</div>
          <div class="field"><label>Training program <span class="req">*</span></label><input class="input" name="program" required maxlength="190" value="<?= e(old('program')) ?>"></div>
          <div class="field mt-1"><label>Completion date <span class="req">*</span></label><input class="input" type="date" name="completion_date" required value="<?= e(old('completion_date', date('Y-m-d'))) ?>"></div>
          <div class="field mt-1"><label>Recommended renewal date</label><input class="input" type="date" name="renewal_date" value="<?= e(old('renewal_date', $card ? $card['expiry_date'] : '')) ?>"></div>
          <div class="field mt-1"><label>Trainer / Instructor</label><input class="input" name="trainer" maxlength="120" value="<?= e(old('trainer', $card['assessor'] ?? (Auth::user()['name'] ?? ''))) ?>"></div>
          <button class="btn btn-primary btn-block mt-1">Save certificate details</button>
        </form>
        <?php endif; ?>
        <a class="btn btn-primary btn-block mb-1" href="<?= url('documents/operator-certificate/' . $sj['id']) ?>"><?= icon('file', 'sm') ?> Certificate of Competence<?= !empty($od['cert_no']) ? ' · ' . e($od['cert_no']) : '' ?></a>
        <?php if ($doc): ?><a class="btn btn-secondary btn-block" href="<?= url('documents/attachment/' . $doc['id']) ?>" target="_blank"><?= icon('file', 'sm') ?> <?= e($doc['original_name']) ?></a><?php endif; ?>
      <?php endif; ?>
      </div>
    </div>
  </div>
</div>
