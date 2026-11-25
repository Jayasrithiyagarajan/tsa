<?php
/** Inspection workspace — one sub-job. Mobile-first; autosaves checklist and certificate. */
$st = $sj['status'];
$dis = $editable ? '' : ' disabled';
$stepState = function (string $step) use ($st, $sj, $clStats, $cert) {
    $order = ['assigned' => 1, 'in_progress' => 2, 'rework' => 2, 'submitted' => 4, 'approved' => 5];
    $pos = $order[$st] ?? 0;
    switch ($step) {
        case 'assigned': return $pos >= 1 ? 'done' : 'cur';
        case 'started': return $pos >= 2 ? 'done' : ($pos === 1 ? 'cur' : '');
        case 'docs': return $pos >= 4 ? 'done' : ($pos === 2 ? ($st === 'rework' ? 'bad' : 'cur') : '');
        case 'qc': return $pos >= 5 ? 'done' : ($pos === 4 ? 'cur' : '');
        case 'issued': return $pos >= 5 ? 'done' : '';
    }
    return '';
};
$certBadge = $cert ? Workflow::label($cert['status']) : '—';
?>
<div class="ws-head">
  <div class="row between wrap" style="align-items:flex-start;gap:14px">
    <div style="min-width:0;flex:1 1 280px">
      <div class="eyebrow"><?= e($sj['type_name']) ?> · <?= e($sj['type_category']) ?></div>
      <h1 style="font-size:21px" class="row wrap"><span class="mono"><?= (int) substr($sj['job_no'], -4) ?>.<?= (int) $sj['seq'] ?></span> <?= e($sj['eq_description'] ?: 'Equipment') ?> <?= $sj['equipment_no'] ? '<span class="pill mono">' . e($sj['equipment_no']) . '</span>' : '' ?> <?= badge($st) ?></h1>
      <div class="muted small mt-1 mono"><?= e($sj['sub_job_no']) ?></div>
    </div>
    <div class="row wrap">
      <?php if (in_array($st, ['assigned', 'rework'], true)): ?>
        <form method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/start') ?>"><?= Csrf::field() ?><button class="btn btn-primary btn-lg"><?= icon('play', 'sm') ?> <?= $st === 'rework' ? 'Resume for rework' : 'Start inspection' ?></button></form>
      <?php elseif ($st === 'in_progress'): ?>
        <a class="btn btn-primary btn-lg" href="#tab-submit" data-tab-link="tab-submit"><?= icon('send', 'sm') ?> Review & submit</a>
      <?php endif; ?>
      <?php if ($qcEdit): ?><a class="btn btn-primary btn-lg" href="<?= url('qc/review/' . $sj['id']) ?>"><?= icon('shield', 'sm') ?> Back to QC review</a><?php endif; ?>
      <?php if ($checklist): ?><a class="btn btn-secondary" href="<?= url('documents/checklist/' . $sj['id']) ?>" target="_blank"><?= icon('clipboard', 'sm') ?> Checklist</a><?php endif; ?>
      <?php if ($cert && $cert['template_id']): ?><a class="btn btn-secondary" href="<?= url('documents/certificate/' . $sj['id']) ?>" target="_blank"><?= icon('award', 'sm') ?> Certificate</a><?php endif; ?>
      <?php if (JobService::deleteBlocker($sj) === null): ?>
        <form method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/delete') ?>" data-confirm="<?= e("Permanently delete sub-job " . $sj['sub_job_no'] . "?\n\nThis removes its checklist, certificate, QC history and every uploaded photo / file. This cannot be undone.") ?>"><?= Csrf::field() ?><button class="btn btn-danger"><?= icon('trash', 'sm') ?> Delete sub-job</button></form>
      <?php endif; ?>
    </div>
  </div>
  <div class="ws-meta">
    <div><small>Client</small><b><?= e($sj['client_name']) ?></b></div>
    <div><small>Location</small><b><?= e($sj['location'] ?: '—') ?></b></div>
    <div><small>Scheduled</small><b><?= e(fmt_date($sj['scheduled_date']) ?: '—') ?></b></div>
    <div><small>Equipment Serial No</small><b><?= e($sj['eq_serial_no'] ?: '—') ?></b></div>
    <div><small>Make / model</small><b><?= e(trim($sj['manufacturer'] . ' ' . $sj['eq_model']) ?: '—') ?></b></div>
    <div><small>Certificate No.</small><b class="mono" style="font-size:12.5px"><?= e($cert['certificate_no'] ?? 'Assigned on start') ?></b></div>
  </div>
  <div class="stepper mt-2">
    <div class="step <?= $stepState('assigned') ?>">Assigned</div>
    <div class="step <?= $stepState('started') ?>">Started</div>
    <div class="step <?= $stepState('docs') ?>">Checklist & certificate</div>
    <div class="step <?= $stepState('qc') ?>">QC review</div>
    <div class="step <?= $stepState('issued') ?>">Issued</div>
  </div>
</div>

<?php if (in_array($st, ['rework', 'in_progress'], true) && $reviews && $reviews[0]['decision'] === 'rework'): ?>
  <div class="alert alert-error"><?= icon('alert') ?><div><b>Returned by QC (<?= e($reviews[0]['reviewer']) ?>, <?= e(time_ago($reviews[0]['created_at'])) ?>):</b><br><?= nl2br(e($reviews[0]['comments'])) ?></div></div>
<?php endif; ?>
<?php if ($st === 'assigned'): ?>
  <div class="alert alert-info"><?= icon('info') ?><div>Press <b>Start inspection</b> when you are at the equipment. The checklist (<?= $sj['requires_checklist'] ? 'required' : 'not required' ?>) and the certificate draft are created with the current controlled template versions.</div></div>
<?php elseif (in_array($st, ['submitted', 'approved'], true)): ?>
  <div class="alert alert-<?= $st === 'approved' ? 'success' : 'info' ?>"><?= icon($st === 'approved' ? 'check' : 'clock') ?><div><?= $st === 'approved' ? 'Approved by QC — certificate issued. This record is locked.' : ($qcEdit ? 'Under QC review - you can correct the checklist and certificate below; changes save automatically. When done, go back to the review to approve or return it.' : 'Submitted to QC on ' . e(fmt_datetime($sj['submitted_at'])) . '. Editing is locked until QC decides.') ?></div></div>
<?php endif; ?>

<div class="card">
  <div class="tabs" data-tabs role="tablist">
    <?php if ($sj['requires_checklist']): ?><button data-tab="tab-checklist" role="tab"><?= icon('clipboard', 'sm') ?> Checklist <span class="t-count <?= $clStats ? ($clStats['missing'] ? 'warn' : 'ok') : '' ?>" id="tab-count-checklist"><?= $clStats ? $clStats['answered'] . '/' . $clStats['total'] : '—' ?></span></button><?php endif; ?>
    <?php if ($sj['requires_certificate']): ?><button data-tab="tab-certificate" role="tab"><?= icon('award', 'sm') ?> Certificate <span class="t-count"><?= e($certBadge) ?></span></button><?php endif; ?>
    <button data-tab="tab-files" role="tab"><?= icon('camera', 'sm') ?> Photos & files <span class="t-count"><?= count($attachments) ?></span></button>
    <?php if ($sj['requires_sticker']): ?><button data-tab="tab-sticker" role="tab"><?= icon('tag', 'sm') ?> Sticker <span class="t-count <?= $sticker ? 'ok' : '' ?>"><?= $sticker ? e($sticker['sticker_no']) : 'none' ?></span></button><?php endif; ?>
    <button data-tab="tab-submit" role="tab"><?= icon('send', 'sm') ?> Submit <?= $issues ? '<span class="t-count warn">' . count($issues) . '</span>' : '' ?></button>
  </div>

  <?php if ($sj['requires_checklist']): ?>
  <section class="tab-panel" id="tab-checklist">
    <?php if (!$checklist): ?>
      <?php View::render('partials/empty', ['icon' => 'clipboard', 'title' => 'Checklist not started', 'text' => 'Start the inspection to open the checklist.'], null); ?>
    <?php else: $n = 0; $sections = []; foreach ($items as $it) $sections[$it['section']][] = $it; ?>
      <form id="checklist-form" method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/checklist') ?>" data-api="<?= url('api/v1/subjobs/' . $sj['id'] . '/checklist') ?>">
        <?= Csrf::field() ?>
        <?php
          $clLayout = TemplateService::checklistLayout(DB::one('SELECT layout FROM checklist_templates WHERE id = ?', [$checklist['template_id']]) ?: []);
          $isReport = $clLayout['kind'] === 'report';
          $answers = $isReport ? ($clLayout['report']['answers'] ?? [['pass', 'Yes'], ['fail', 'No'], ['na', 'N/A']]) : [['pass', 'PASS'], ['fail', 'FAIL'], ['na', 'N/A']];
          $segClass = ['pass' => 'p', 'fail' => 'f', 'na' => 'na'];
          $clData = json_decode((string) ($checklist['data'] ?? ''), true) ?: [];
          // '## ' = underlined heading line, '>> ' = right-aligned reference line (report forms)
          $fmtLabel = function (string $label): string {
              $out = [];
              foreach (preg_split('/\R/', $label) as $ln) {
                  if (str_starts_with($ln, '## ')) $out[] = '<u><b>' . e(substr($ln, 3)) . '</b></u>';
                  elseif (str_starts_with($ln, '>> ')) $out[] = '<span class="muted" style="display:block;text-align:right">' . e(substr($ln, 3)) . '</span>';
                  else $out[] = e($ln);
              }
              return implode('<br>', $out);
          };
        ?>
        <div class="card-b" style="border-bottom:1px solid var(--border)"><div class="form-grid">
          <div class="field col-4"><label><?= $isReport ? 'Report No.' : 'Certificate No.' ?></label><div class="bound mono"><?= icon('lock', 'sm') ?><?= e($cert['certificate_no'] ?? Numbering::certificate($sj)) ?></div></div>
          <div class="field col-6"><label>Equipment Type</label><input class="input" type="text" name="eq_description" value="<?= e($sj['eq_description']) ?>" maxlength="190"<?= $dis ?>></div>
          <div class="field col-6"><label>Equipment ID / No</label><input class="input" type="text" name="equipment_no" value="<?= e($sj['equipment_no']) ?>" maxlength="80"<?= $dis ?>></div>
          <div class="field col-4"><label>Equipment Serial No</label><input class="input" type="text" name="serial_no" value="<?= e($checklist['serial_no']) ?>"<?= $dis ?>></div>
          <div class="field col-4"><label>Date of inspection</label><input class="input" type="date" name="inspection_date" value="<?= e($checklist['inspection_date']) ?>"<?= $dis ?>></div>
          <?php if ($isReport): ?>
            <div class="form-section mt-1"><?= icon('file', 'sm') ?> Report cover data <span class="muted small" style="font-weight:500">— client, model, brand, year and dates are filled from the job record</span></div>
            <?php foreach (TemplateService::reportManualKeys($clLayout) as $key => $label): ?>
              <div class="field <?= $key === 'vin' ? 'col-8' : 'col-4' ?>"><label><?= e($label) ?></label>
                <input class="input<?= $key === 'vin' ? ' mono' : '' ?>" type="text" name="cl_data[<?= e($key) ?>]" value="<?= e(($clData[$key] ?? '') !== '' ? $clData[$key] : (isset(TemplateService::REPORT_DEFAULTS[$key]) ? ($sj[['description' => 'eq_description', 'model' => 'eq_model', 'manufacturer' => 'manufacturer', 'year_built' => 'eq_year'][TemplateService::REPORT_DEFAULTS[$key]]] ?? '') : '')) ?>"<?= $key === 'vin' ? ' maxlength="17" style="text-transform:uppercase;letter-spacing:.15em"' : '' ?><?= $dis ?>></div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div></div>
        <?php foreach ($sections as $secName => $secItems): ?>
          <div class="cl-section"><span><?= e(TemplateService::sectionLabel($secName)) ?> · <?= count($secItems) ?> items</span>
            <?php if ($editable): ?><button type="button" class="btn btn-ghost btn-sm" style="height:24px;color:inherit" data-mark-all="<?= e($secName) ?>"><?= icon('check', 'sm') ?> Mark unanswered as <?= e($answers[array_search('pass', array_column($answers, 0), true)][1]) ?></button><?php endif; ?></div>
          <?php foreach ($secItems as $it): $n++; $id = (int) $it['id']; ?>
            <div class="cl-item<?= $it['remarks'] ? ' show-rem' : '' ?>" data-section="<?= e($secName) ?>">
              <span class="n"><?= $n ?></span>
              <span class="lbl"><?= $fmtLabel((string) $it['label']) ?><?= $it['is_mandatory'] ? '' : ' <span class="muted small">(optional)</span>' ?></span>
              <span class="ctrl row" style="gap:6px">
                <span class="seg" role="radiogroup" aria-label="<?= e($it['label']) ?>">
                  <?php foreach ($answers as [$code, $alabel]): ?>
                    <label><input type="radio" name="r[<?= $id ?>][result]" value="<?= e($code) ?>"<?= checked($it['result'] === $code) . $dis ?>><span class="<?= $segClass[$code] ?? 'na' ?>"><?= e($isReport ? $alabel : strtoupper($alabel)) ?></span></label>
                  <?php endforeach; ?>
                </span>
                <button type="button" class="rem-toggle<?= $it['remarks'] ? ' has' : '' ?>" title="Remarks" aria-label="Remarks"><?= icon('edit', 'sm') ?></button>
              </span>
              <div class="rem"><input class="input" type="text" name="r[<?= $id ?>][remarks]" value="<?= e($it['remarks']) ?>" placeholder="Remarks (optional)" maxlength="255"<?= $dis ?>></div>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if ($clLayout['remarks_box'] > 0 || !empty($clLayout['report']['notes_box'])): ?>
          <div class="card-b" style="border-top:1px solid var(--border)"><div class="field"><label><?= $isReport ? e($clLayout['report']['notes_box']['label'] ?? 'Inspector Notes:') : 'Remarks /' ?> (printed on the <?= $isReport ? 'report' : 'checklist' ?>)</label>
            <textarea class="textarea" name="cl_remarks"<?= $dis ?>><?= e($checklist['remarks'] ?? '') ?></textarea></div></div>
        <?php endif; ?>
        <div class="cl-bar">
          <div class="stat"><span>Pass <b id="cl-pass">0</b></span><span>Fail <b id="cl-fail" style="color:var(--red)">0</b></span><span>N/A <b id="cl-na">0</b></span><span>Open <b id="cl-open">0</b></span>
            <span style="flex:1;min-width:120px;max-width:220px;align-self:center"><span class="progress" style="display:block"><span id="cl-progress" style="width:0"></span></span></span><b id="cl-pct">0%</b></div>
          <?php if ($editable): ?><span class="save-state" id="cl-save-state">Autosave on</span><button class="btn btn-secondary btn-sm">Save now</button><?php endif; ?>
        </div>
      </form>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($sj['requires_certificate']): ?>
  <section class="tab-panel" id="tab-certificate">
    <?php if (!$cert): ?>
      <?php View::render('partials/empty', ['icon' => 'award', 'title' => 'Certificate not prepared', 'text' => 'Start the inspection to reserve the certificate number and open the draft.'], null); ?>
    <?php elseif (!$certTpl || !$cert['template_id']): ?>
      <div class="card-b"><div class="alert alert-info"><?= icon('info') ?><div>No certificate template is configured for <?= e($sj['type_name']) ?>. Upload the signed certificate as a PDF in <b>Photos &amp; files</b> with type "Certificate (signed PDF)".</div></div></div>
    <?php else: ?>

      <?php
        // Multi-page: if there are multiple cert pages show a page-picker + clone UI
        $totalPages = count($allCerts);
        $isMultiPage = $totalPages > 1;
      ?>

      <?php if ($editable || $isMultiPage): ?>
      <!-- Clone / page-count panel (editable) and the page tabs (always, so a submitted / approved multi-page certificate can still be viewed page by page) -->
      <div class="card-b" style="padding-bottom:0">
        <?php if ($editable): ?>
        <div class="row wrap" style="gap:12px;align-items:center">
          <div class="strong small"><?= icon('copy', 'sm') ?> Certificate pages</div>
          <form method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/certificate/clone') ?>" class="row" style="gap:8px;align-items:center">
            <?= Csrf::field() ?>
            <label class="small" for="clone-qty">Total pages needed:</label>
            <input id="clone-qty" class="input" type="number" name="qty" min="1" max="100" value="<?= $totalPages ?>" style="width:70px">
            <button class="btn btn-secondary btn-sm" type="submit"><?= icon('copy', 'sm') ?> Apply</button>
          </form>
          <?php if ($isMultiPage && $certSeq > 1): ?>
          <form method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/certificate/' . $certSeq . '/delete') ?>" data-confirm="<?= e('Delete certificate page ' . $certSeq . '?' . "\n\n" . 'This removes the page, its data and the photos set to print on this page only. Later pages are renumbered. This cannot be undone.') ?>">
            <?= Csrf::field() ?>
            <button class="btn btn-ghost btn-sm" type="submit" style="color:var(--red)"><?= icon('trash', 'sm') ?> Delete page <?= (int) $certSeq ?></button>
          </form>
          <?php endif; ?>
          <span class="small muted">Fill page 1, set the quantity, then click Apply — all other pages will be cloned from page 1. You can edit each page independently.</span>
        </div>
        <?php endif; ?>
        <?php if ($isMultiPage): ?>
        <!-- Page tabs -->
        <div class="tab-row mt-2" style="border-bottom:none">
          <?php foreach ($allCerts as $c): ?>
            <a class="tab-btn<?= (int)$c['seq'] === $certSeq ? ' active' : '' ?>"
               href="<?= url('inspector/subjobs/' . $sj['id'] . '?cert_page=' . $c['seq'] . '#tab-certificate') ?>">
              Page <?= (int)$c['seq'] ?>
              <?php if ($c['result']): ?><span class="badge badge-<?= $c['result'] === 'accepted' ? 'green' : 'red' ?> ms-1" style="font-size:10px"><?= e(Workflow::label($c['result'])) ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <form id="certificate-form" method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/certificate') ?>" data-api="<?= $editable ? url('api/v1/subjobs/' . $sj['id'] . '/certificate') : '' ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="seq" value="<?= $certSeq ?>">
        <div class="card-b">
          <div class="row between wrap mb-2">
            <div>
              <div class="strong"><?= e($certTpl['title']) ?><?= $isMultiPage ? ' — Page ' . $certSeq . ' of ' . $totalPages : '' ?></div>
              <div class="small muted">Doc Ref. <?= e($certTpl['doc_ref']) ?> · Rev. <?= e($certTpl['revision']) ?> · fields with <?= icon('lock', 'sm') ?> are filled from the job record</div>
            </div>
            <?php if ($editable): ?><span class="save-state" id="cf-save-state">Autosave on</span><?php endif; ?>
          </div>
          <div class="form-grid">
            <?php $lastSec = null; $anTbl = ($certTpl['layout'] ?? '') === 'an'; $utTbl = ($certTpl['layout'] ?? '') === 'ut'; $chTbl = in_array($certTpl['layout'] ?? '', ['ch', 'pp', 'tr', 'mo'], true); $chMo = ($certTpl['layout'] ?? '') === 'mo'; $chAns = $chMo ? ['Pass' => 'p', 'Fail' => 'f', 'N/A' => 'na'] : ['Yes' => 'p', 'No' => 'f', 'N/A' => 'na']; $chA = array_keys($chAns); $chSec = null; $chN = 0; $fk = array_column($fields, null, 'key'); foreach ($fields as $f): ?>
              <?php $scH = ['client' => 'Report details', 'eq_item' => 'Inspected item', 'stage' => 'Stage of inspection', 'type_insp' => 'Type of inspection', 'intro' => 'Introduction', 'conclusions' => 'Conclusions and remarks', 'attendees' => 'Attendees and scope of inspection', 'gen_desc' => 'Detail of inspection activities'];
                if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && isset($scH[$f['key']])): ?>
                <div class="form-section mt-1"><?= icon('check', 'sm') ?> <?= e($scH[$f['key']]) ?></div>
                <?php if ($f['key'] === 'gen_desc'): ?><div class="col-12 small muted" style="margin:-4px 0 6px">Drawings and pictures are added in the Files tab: images whose caption contains "draw" print on the drawing pages (up to 3), all others as inspection pictures (up to 16).</div><?php endif; ?>
              <?php endif; ?>
              <?php if ($chTbl && substr($f['key'], -2) === '_r' && isset($fk[substr($f['key'], 0, -2)]['item'])) continue;   // remarks are shown inside their check row ?>
              <?php if ($chTbl && isset($f['item'], $f['section'])): ?>
                <?php if ($chSec !== $f['section']): $chSec = $f['section']; ?>
                  <?php if ($chN === 0 && $editable): ?>
                    <div class="col-12 row" style="gap:8px;align-items:center;flex-wrap:wrap"><b>All sections:</b>
                      <button type="button" class="btn btn-secondary btn-sm" data-ch-mark="<?= e($chA[0]) ?>" data-ch-sec="">Mark all as <?= e($chA[0]) ?></button>
                      <button type="button" class="btn btn-secondary btn-sm" data-ch-mark="<?= e($chA[1]) ?>" data-ch-sec="">Mark all as <?= e($chA[1]) ?></button>
                      <button type="button" class="btn btn-ghost btn-sm" data-ch-mark="N/A" data-ch-sec="">Mark all as N/A</button>
                    </div>
                    <script>document.addEventListener('click', function (e) {
                      var b = e.target.closest('[data-ch-mark]'); if (!b) return;
                      var sec = b.getAttribute('data-ch-sec'), val = b.getAttribute('data-ch-mark'), form = document.getElementById('certificate-form');
                      form.querySelectorAll('.cl-item[data-ch-sec]').forEach(function (row) {
                        if (sec !== '' && row.getAttribute('data-ch-sec') !== sec) return;
                        var r = row.querySelector('input[type=radio][value="' + val + '"]'); if (r && !r.disabled) r.checked = true;
                      });
                      form.dispatchEvent(new Event('change', { bubbles: true }));   // triggers the autosave
                    });</script>
                  <?php endif; ?>
                  <div class="col-12 cl-section" style="border-radius:8px;margin-top:6px"><span><?php if ($chMo) { $sp = explode('|', $chSec); echo e(($sp[1] ?? '') !== '' ? $sp[0] . '. ' . $sp[1] : 'Section ' . $sp[0]); } else echo e(rtrim($chSec, ': ')); ?></span>
                    <?php if ($editable): ?><span class="row" style="gap:6px">
                      <button type="button" class="btn btn-ghost btn-sm" style="height:24px;color:inherit" data-ch-mark="<?= e($chA[0]) ?>" data-ch-sec="<?= e($chSec) ?>">Mark all as <?= e($chA[0]) ?></button>
                      <button type="button" class="btn btn-ghost btn-sm" style="height:24px;color:inherit" data-ch-mark="<?= e($chA[1]) ?>" data-ch-sec="<?= e($chSec) ?>">Mark all as <?= e($chA[1]) ?></button>
                    </span><?php endif; ?></div>
                <?php endif; $chN++; $rk = $f['key'] . '_r'; ?>
                <div class="col-12"><div class="cl-item<?= $chMo ? '' : ' show-rem' ?>" data-ch-sec="<?= e($chSec) ?>" style="padding:10px 4px">
                  <span class="n"><?php if ($chMo && preg_match('/^@(\S+)/', (string) $f['item'], $mm)) echo e($mm[1]); else echo $chN; ?></span>
                  <span class="lbl"><?php $chTxt = (string) $f['item']; if ($chMo) $chTxt = preg_replace('/^@\S+\R?/', '', $chTxt); if (in_array($certTpl['layout'] ?? '', ['tr', 'mo'], true)) { $en = trim(preg_replace('/^.*\p{Arabic}.*\R?/mu', '', $chTxt)); if ($en !== '') $chTxt = $en; }   // bilingual form: the inspector form shows the English text ?><?= nl2br(e($chTxt)) ?></span>
                  <span class="ctrl row" style="gap:6px"><span class="seg" role="radiogroup" aria-label="<?= e($f['item']) ?>">
                    <?php foreach ($chAns as $ans => $cls): ?>
                      <label><input type="radio" name="fields[<?= e($f['key']) ?>]" value="<?= e($ans) ?>"<?= checked((string) $f['value'] === $ans) . $dis ?>><span class="<?= $cls ?>"><?= e($ans) ?></span></label>
                    <?php endforeach; ?>
                  </span></span>
                  <?php if (!$chMo): ?><div class="rem"><input class="input" type="text" name="fields[<?= e($rk) ?>]" value="<?= e((string) ($fk[$rk]['value'] ?? '')) ?>" placeholder="Remarks" maxlength="190"<?= $dis ?>></div><?php endif; ?>
                </div></div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && in_array($f['key'], ['scope_scope', 'scope_remarks'], true)) continue;   // shown inside the Scope table ?>
              <?php if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && $f['key'] === 'standard') continue;   // replaced by the Reference Standard table (ref_std) ?>
              <?php if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && $f['key'] === 'ref_std'): $rsSrc = trim((string) $f['value']) !== '' ? (string) $f['value'] : (is_array($certTpl['notes'] ?? null) ? implode("\n", $certTpl['notes']) : (string) ($certTpl['notes'] ?? '')); $rsRows = array_values(array_filter(array_map('trim', preg_split('/\R/u', $rsSrc)), 'strlen')); if (!$rsRows) $rsRows = ['']; ?>
                <div class="form-section mt-1"><?= icon('check', 'sm') ?> Reference standard</div>
                <div class="field col-12">
                  <input type="hidden" name="fields[ref_std]" value="<?= e(implode("\n", $rsRows)) ?>" data-rs-hidden>
                  <table style="width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #9aa3b2;background:#fff">
                    <tr><td colspan="4" style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px;background:#fff;font-size:13px">REFERENCE STANDARD</td></tr>
                    <tr><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">TITLE</td><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">STANDARD</td><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">REMARKS</td><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px;width:44px"></td></tr>
                    <tbody data-rs-rows>
                    <?php foreach ($rsRows as $rr): $rp = array_map('trim', explode('|', $rr)) + ['', '', '']; ?>
                      <tr data-rs-row><?php foreach ([0, 1, 2] as $ci): ?><td style="border:1px solid #9aa3b2;padding:4px"><input class="input" type="text" maxlength="160" value="<?= e($rp[$ci]) ?>" style="text-align:center"<?= $dis ?>></td><?php endforeach; ?><td style="border:1px solid #9aa3b2;padding:4px;text-align:center"><?php if ($editable): ?><button type="button" class="icon-btn" data-rs-del title="Remove row"><?= icon('trash', 'sm') ?></button><?php endif; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                  <?php if ($editable): ?><button type="button" class="btn btn-secondary btn-sm mt-1" data-rs-add><?= icon('plus', 'sm') ?> Add standard</button><?php endif; ?>
                  <script>(function () {
                    var w = document.currentScript.parentNode, hid = w.querySelector('[data-rs-hidden]'), body = w.querySelector('[data-rs-rows]');
                    function sync() { var out = []; body.querySelectorAll('[data-rs-row]').forEach(function (r) { var v = [].map.call(r.querySelectorAll('input'), function (i) { return i.value.replace(/\|/g, '/').trim(); }); if (v.join('') !== '') out.push(v.join(' | ')); }); hid.value = out.join('\n'); hid.dispatchEvent(new Event('change', { bubbles: true })); }
                    w.addEventListener('input', sync);
                    w.addEventListener('click', function (e) {
                      if (e.target.closest('[data-rs-add]')) { var r = body.querySelector('[data-rs-row]').cloneNode(true); r.querySelectorAll('input').forEach(function (i) { i.value = ''; }); body.appendChild(r); r.querySelector('input').focus(); }
                      var d = e.target.closest('[data-rs-del]'); if (d) { if (body.querySelectorAll('[data-rs-row]').length > 1) d.closest('[data-rs-row]').remove(); else d.closest('[data-rs-row]').querySelectorAll('input').forEach(function (i) { i.value = ''; }); sync(); }
                    });
                  })();</script>
                </div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && $f['key'] === 'ncr_outstanding') continue;   // shown with the NCR block ?>
              <?php if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && $f['key'] === 'ncr_issued'): ?>
                <div class="form-section mt-1"><?= icon('check', 'sm') ?> Result of inspection</div>
                <div class="col-12 result-pick" style="grid-template-columns:repeat(2,1fr)">
                  <input type="hidden" name="result" value="">
                  <?php foreach (["accepted" => "SATISFACTORY", "rejected" => "UNSATISFACTORY"] as $o => $lbl): ?>
                    <label class="<?= $o ?>"><input type="checkbox" name="result" value="<?= $o ?>"<?= checked(($cert['result'] === $o) || ($o === 'rejected' && $cert['result'] === 'repair')) . $dis ?> onchange="if(this.checked){var s=this;this.closest('.result-pick').querySelectorAll('input[type=checkbox]').forEach(function(x){if(x!==s)x.checked=false})}"> <?= $lbl ?></label>
                  <?php endforeach; ?>
                </div>
                <div class="col-12" style="font-weight:700;margin-top:6px">NON-CONFORMITIES REPORTS (NCR):</div>
                <div class="field col-12"><label>1. NCRs issued during reports:</label><input class="input" type="text" name="fields[ncr_issued]" value="<?= e((string) $f['value']) ?>" placeholder="N/A" maxlength="190"<?= $dis ?>></div>
                <div class="field col-12"><label>2. List of outstanding NCRs:</label><input class="input" type="text" name="fields[ncr_outstanding]" value="<?= e((string) ($fk['ncr_outstanding']['value'] ?? '')) ?>" placeholder="N/A" maxlength="190"<?= $dis ?>></div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && $f['key'] === 'conclusions'): $cnRows = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $f['value'])), 'strlen')); if (!$cnRows) $cnRows = ['']; ?>
                <div class="field col-12">
                  <label>Conclusions and remarks <span class="muted" style="font-weight:400">— one numbered point per row</span></label>
                  <input type="hidden" name="fields[conclusions]" value="<?= e((string) $f['value']) ?>" data-cn-hidden>
                  <table style="width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #9aa3b2;background:#fff"><colgroup><col style="width:42px"><col><col style="width:44px"></colgroup><tbody data-cn-rows>
                  <?php foreach ($cnRows as $cr): ?>
                    <tr data-cn-row><td style="border:1px solid #9aa3b2;padding:4px;text-align:center;font-weight:700" data-cn-n></td><td style="border:1px solid #9aa3b2;padding:4px"><input class="input" type="text" maxlength="400" value="<?= e($cr) ?>"<?= $dis ?>></td><td style="border:1px solid #9aa3b2;padding:4px;text-align:center"><?php if ($editable): ?><button type="button" class="icon-btn" data-cn-del title="Remove point"><?= icon('trash', 'sm') ?></button><?php endif; ?></td></tr>
                  <?php endforeach; ?>
                  </tbody></table>
                  <?php if ($editable): ?><button type="button" class="btn btn-secondary btn-sm mt-1" data-cn-add><?= icon('plus', 'sm') ?> Add point</button><?php endif; ?>
                  <script>(function () {
                    var w = document.currentScript.parentNode, hid = w.querySelector('[data-cn-hidden]'), body = w.querySelector('[data-cn-rows]');
                    function num() { body.querySelectorAll('[data-cn-row]').forEach(function (r, i) { r.querySelector('[data-cn-n]').textContent = (i + 1) + '.'; }); }
                    function sync() { var out = []; body.querySelectorAll('[data-cn-row] input').forEach(function (i) { if (i.value.trim() !== '') out.push(i.value.trim()); }); hid.value = out.join('\n'); hid.dispatchEvent(new Event('change', { bubbles: true })); }
                    num(); w.addEventListener('input', sync);
                    w.addEventListener('click', function (e) {
                      if (e.target.closest('[data-cn-add]')) { var r = body.querySelector('[data-cn-row]').cloneNode(true); r.querySelector('input').value = ''; body.appendChild(r); num(); r.querySelector('input').focus(); }
                      var d = e.target.closest('[data-cn-del]'); if (d) { if (body.querySelectorAll('[data-cn-row]').length > 1) d.closest('[data-cn-row]').remove(); else d.closest('[data-cn-row]').querySelector('input').value = ''; num(); sync(); }
                    });
                  })();</script>
                </div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && $f['key'] === 'attendees'): $attRows = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $f['value'])), 'strlen')); if (!$attRows) $attRows = ['', '']; ?>
                <div class="field col-12" data-att>
                  <label>Attendees</label>
                  <input type="hidden" name="fields[attendees]" value="<?= e((string) $f['value']) ?>" data-att-hidden>
                  <table style="width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #9aa3b2;background:#fff">
                    <tr><td colspan="4" style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px;background:#fff;font-size:13px">ATTENDEES</td></tr>
                    <tr><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">NAME</td><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">DESIGNATION</td><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">REPRESENTATIVE</td><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px;width:44px"></td></tr>
                    <tbody data-att-rows>
                    <?php foreach ($attRows as $ar): $ap = array_map('trim', explode('|', $ar)) + ['', '', '']; ?>
                      <tr data-att-row><?php foreach ([0, 1, 2] as $ci): ?><td style="border:1px solid #9aa3b2;padding:4px"><input class="input" type="text" maxlength="120" value="<?= e($ap[$ci]) ?>" style="text-align:center"<?= $dis ?>></td><?php endforeach; ?><td style="border:1px solid #9aa3b2;padding:4px;text-align:center"><?php if ($editable): ?><button type="button" class="icon-btn" data-att-del title="Remove row"><?= icon('trash', 'sm') ?></button><?php endif; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                  <?php if ($editable): ?><button type="button" class="btn btn-secondary btn-sm mt-1" data-att-add><?= icon('plus', 'sm') ?> Add attendee</button><?php endif; ?>
                  <script>(function () {
                    var w = document.currentScript.parentNode, hid = w.querySelector('[data-att-hidden]'), body = w.querySelector('[data-att-rows]');
                    function sync() { var out = []; body.querySelectorAll('[data-att-row]').forEach(function (r) { var v = [].map.call(r.querySelectorAll('input'), function (i) { return i.value.replace(/\|/g, '/').trim(); }); if (v.join('') !== '') out.push(v.join(' | ')); }); hid.value = out.join('\n'); hid.dispatchEvent(new Event('change', { bubbles: true })); }
                    w.addEventListener('input', sync);
                    w.addEventListener('click', function (e) {
                      if (e.target.closest('[data-att-add]')) { var r = body.querySelector('[data-att-row]').cloneNode(true); r.querySelectorAll('input').forEach(function (i) { i.value = ''; }); body.appendChild(r); r.querySelector('input').focus(); }
                      var d = e.target.closest('[data-att-del]'); if (d) { if (body.querySelectorAll('[data-att-row]').length > 1) d.closest('[data-att-row]').remove(); else d.closest('[data-att-row]').querySelectorAll('input').forEach(function (i) { i.value = ''; }); sync(); }
                    });
                  })();</script>
                </div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if (in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) && $f['key'] === 'scope_method'): ?>
                <div class="field col-12">
                  <label>Scope of inspection</label>
                  <table style="width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #9aa3b2;background:#fff">
                    <tr><td colspan="3" style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px;background:#fff;font-size:13px">SCOPE OF INSPECTION</td></tr>
                    <tr><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">INSPECTION METHOD</td><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">SCOPE</td><td style="border:1px solid #9aa3b2;background:#aabee3;font-weight:700;text-align:center;padding:8px;font-size:12px">REMARKS</td></tr>
                    <tr><?php foreach (['scope_method', 'scope_scope', 'scope_remarks'] as $sk): ?><td style="border:1px solid #9aa3b2;padding:4px"><input class="input" type="text" maxlength="190" name="fields[<?= $sk ?>]" value="<?= e((string) ($fk[$sk]['value'] ?? '')) ?>" style="text-align:center"<?= $dis ?>></td><?php endforeach; ?></tr>
                  </table>
                </div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if ($f['key'] === 'cert_remarks'): ?>
                <div class="field col-12"><label>Remarks \ Notes <span class="muted" style="font-weight:400">— printed on the certificate</span></label>
                  <textarea class="input" rows="4" style="min-height:100px;resize:vertical;line-height:1.5" name="fields[cert_remarks]"<?= $dis ?>><?= e($f['value'] ?? '') ?></textarea></div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if ($utTbl && in_array($f['key'], ['ut_remarks', 'ut_notes'], true)): ?>
                <?php if ($f['key'] === 'ut_notes'): ?>
                <div class="field col-12">
                  <label>Remarks</label>
                  <table style="width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #9aa3b2;background:#fff">
                    <tr>
                      <td style="border:1px solid #9aa3b2;padding:10px"><textarea class="input" rows="6" style="min-height:140px;resize:vertical;line-height:1.5" name="fields[ut_notes]"<?= $dis ?>><?= e(trim((string) ($f['value'] ?? '')) !== '' ? $f['value'] : implode("\n", (array) ($certTpl['notes'] ?? []))) ?></textarea>
                        <div class="small muted" style="margin-top:4px">Standard notes are pre-filled — edit them if needed (one line per note).</div></td>
                    </tr>
                  </table>
                </div>
                <?php endif; ?>
                <?php continue; ?>
              <?php endif; ?>
              <?php if ($anTbl && $f['key'] === 'an_notes'): ?>
                <div class="field col-12">
                  <table class="an-input" style="width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #9aa3b2;background:#fff">
                    <colgroup><col style="width:18%"><col style="width:82%"></colgroup>
                    <tr>
                      <td style="border:1px solid #9aa3b2;padding:10px;text-align:center;font-weight:700;vertical-align:middle">Notes:</td>
                      <td style="border:1px solid #9aa3b2;padding:10px"><textarea class="input" rows="7" style="min-height:170px;resize:vertical;line-height:1.5" name="fields[an_notes]"<?= $dis ?>><?= e(trim((string) $f['value']) !== '' ? $f['value'] : (string) ($certTpl['notes'] ?? '')) ?></textarea>
                        <div class="small muted" style="margin-top:4px">Standard notes are pre-filled — edit them if needed (one line per note).</div></td>
                    </tr>
                  </table>
                </div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if ($anTbl && (in_array($f['key'], ['qty', 'cal_data'], true) || preg_match('/^(n[123]|r[123]_[123]|m[123]|e[123]|k[123]|tr_(ref|mfr|id|cert|exp))$/', $f['key']))): ?>
                <?php
                $cell = function (array $c) use ($dis) {   // one compact centred input for the Anemometer tables
                    $nm = 'fields[' . e($c['key']) . ']'; $st = 'text-align:center;font-weight:700;padding:6px 4px';
                    if (($c['type'] ?? '') === 'select') {
                        $o = '<option value="">—</option>'; foreach ($c['options'] ?? [] as $op) $o .= '<option' . selected($op, $c['value']) . '>' . e($op) . '</option>';
                        return '<select class="select" style="' . $st . '" name="' . $nm . '"' . $dis . '>' . $o . '</select>';
                    }
                    if (!empty($c['is_date'])) return '<input class="input" type="date" style="' . $st . '" name="' . $nm . '" value="' . e($c['iso']) . '"' . $dis . '>';
                    return '<input class="input" type="text" style="' . $st . '" name="' . $nm . '" value="' . e($c['value']) . '"' . $dis . '>';
                };
                $th = 'border:1px solid #9aa3b2;padding:8px;text-align:center;font-weight:700';
                $td = 'border:1px solid #9aa3b2;padding:6px';
                $tbl = 'width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #9aa3b2;background:#fff';
                ?>
                <?php if ($f['key'] === 'n1' && isset($fk['n1'], $fk['k3'])): ?>
                <div class="field col-12">
                  <table class="an-input" style="<?= $tbl ?>">
                    <colgroup><col style="width:15%"><col style="width:14%"><col style="width:14%"><col style="width:14%"><col style="width:15%"><col style="width:14%"><col style="width:14%"></colgroup>
                    <tr style="background:#9bb7e4"><td colspan="7" style="<?= $th ?>">Air Velocity</td></tr>
                    <tr><td style="<?= $th ?>">Normal Value<br><span style="font-weight:600">(m/s)</span></td><td colspan="3" style="<?= $th ?>">Measured Value (m/s)<div style="display:flex;font-weight:600;margin-top:6px"><span style="flex:1">Trail. 1</span><span style="flex:1">Trail. 2</span><span style="flex:1">Trail. 3</span></div></td><td style="<?= $th ?>">Means (m/s)</td><td style="<?= $th ?>">Error</td><td style="<?= $th ?>">Remarks</td></tr>
                    <?php foreach ([1, 2, 3] as $i): ?>
                    <tr>
                      <?php foreach (["n$i", "r{$i}_1", "r{$i}_2", "r{$i}_3", "m$i", "e$i", "k$i"] as $k): if (isset($fk[$k])): ?><td style="<?= $td ?>"><?= $cell($fk[$k]) ?></td><?php else: ?><td style="<?= $td ?>"></td><?php endif; endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                  </table>
                </div>
                <?php elseif ($f['key'] === 'tr_ref' && isset($fk['tr_exp'])): ?>
                <div class="field col-12">
                  <table class="an-input" style="<?= $tbl ?>">
                    <colgroup><col style="width:20%"><col style="width:20%"><col style="width:16%"><col style="width:26%"><col style="width:18%"></colgroup>
                    <tr style="background:#9bb7e4"><td colspan="5" style="<?= $th ?>">Traceability Information <span class="req">*</span></td></tr>
                    <tr><td style="<?= $th ?>">Ref. Instrument</td><td style="<?= $th ?>">Manufacturer</td><td style="<?= $th ?>">ID No.</td><td style="<?= $th ?>">Calibration Certificate</td><td style="<?= $th ?>">Expiration</td></tr>
                    <tr><?php foreach (['tr_ref', 'tr_mfr', 'tr_id', 'tr_cert', 'tr_exp'] as $k): ?><td style="<?= $td ?>"><?= isset($fk[$k]) ? $cell($fk[$k]) : '' ?></td><?php endforeach; ?></tr>
                  </table>
                </div>
                <?php endif; ?>
                <?php continue; ?>
              <?php endif; ?>
              <?php if ($anTbl && $f['key'] === 'eq_id' && isset($fk['qty'], $fk['cal_data'])): $q = $fk['qty']; $cd = $fk['cal_data']; ?>
                <div class="field col-12">
                  <table class="an-input" style="width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #9aa3b2;background:#fff">
                    <colgroup><col style="width:28%"><col style="width:12%"><col style="width:60%"></colgroup>
                    <tr style="background:#9bb7e4;color:#111;font-weight:700;text-align:center">
                      <td style="border:1px solid #9aa3b2;padding:9px">ID / SN <span class="req">*</span></td>
                      <td style="border:1px solid #9aa3b2;padding:9px">Qty <span class="req">*</span></td>
                      <td style="border:1px solid #9aa3b2;padding:9px">Calibration Data <span class="req">*</span></td>
                    </tr>
                    <tr>
                      <td style="border:1px solid #9aa3b2;padding:10px;vertical-align:middle"><input class="input" type="text" style="text-align:center;font-weight:700" name="fields[<?= e($f['key']) ?>]" value="<?= e($f['value']) ?>" placeholder="e.g. RT-325 / 243445"<?= $dis ?>></td>
                      <td style="border:1px solid #9aa3b2;padding:10px;vertical-align:middle"><input class="input" type="text" style="text-align:center;font-weight:700" name="fields[<?= e($q['key']) ?>]" value="<?= e($q['value']) ?>" placeholder="01"<?= $dis ?>></td>
                      <td style="border:1px solid #9aa3b2;padding:10px"><textarea class="input" rows="6" style="min-height:150px;resize:vertical;line-height:1.6" name="fields[<?= e($cd['key']) ?>]" placeholder="ANEMOMETER&#10;Model: GS320"<?= $dis ?>><?= e($cd['value']) ?></textarea>
                        <div class="small muted" style="margin-top:4px">First line prints in bold (instrument name); following lines are normal text.</div></td>
                    </tr>
                  </table>
                </div>
                <?php continue; ?>
              <?php endif; ?>
              <?php if (!empty($f['section']) && $f['section'] !== $lastSec): $lastSec = $f['section']; $sp = explode('|', $f['section']); ?>
                <div class="form-section mt-1"><?= icon('check', 'sm') ?> <?= e(($sp[1] ?? '') !== '' ? $sp[0] . '. ' . $sp[1] : 'Section ' . $sp[0]) ?></div>
              <?php endif; ?>
              <div class="field <?= in_array($f['type'] ?? '', ['textarea', 'checks'], true) || ($f['key'] === 'vin' && in_array($certTpl['layout'] ?? '', ['pp', 'tr'], true)) ? 'col-12' : 'col-6' ?>"><label><?= e(rtrim($f['label'], ':')) ?><?= !empty($f['required']) && (!$f['bound'] || !empty($f['is_cap'])) ? ' <span class="req">*</span>' : '' ?></label>
                <?php if ($f['key'] === 'vin' && in_array($certTpl['layout'] ?? '', ['pp', 'tr'], true)): $vc = preg_split('//u', strtoupper((string) $f['value']), -1, PREG_SPLIT_NO_EMPTY); /* one box per character, as on the form */ ?>
                  <input type="hidden" name="fields[vin]" value="<?= e((string) $f['value']) ?>" data-vin-hidden>
                  <div class="row" data-vin-boxes style="gap:4px;flex-wrap:wrap">
                    <?php for ($i = 0; $i < 17; $i++): ?><span style="display:flex;flex-direction:column;align-items:center;gap:2px"><small class="muted" style="font-size:10px"><?= $i + 1 ?></small><input class="input" type="text" maxlength="1" autocomplete="off" autocapitalize="characters" aria-label="VIN character <?= $i + 1 ?>" value="<?= e(trim((string) ($vc[$i] ?? ''))) ?>" style="width:34px;padding:6px 0;text-align:center;font-weight:700;text-transform:uppercase"<?= $dis ?>></span><?php endfor; ?>
                  </div>
                  <div class="help">One character per box (17 characters).</div>
                  <script>(function () {
                    var w = document.currentScript.parentNode, hid = w.querySelector('[data-vin-hidden]'), bx = [].slice.call(w.querySelectorAll('[data-vin-boxes] input'));
                    function sync() { hid.value = bx.map(function (b) { return b.value || ' '; }).join('').replace(/\s+$/, ''); hid.dispatchEvent(new Event('change', { bubbles: true })); }
                    function clean(v) { return v.toUpperCase().replace(/[^A-Z0-9]/g, ''); }
                    bx.forEach(function (b, i) {
                      b.addEventListener('input', function () { var v = clean(b.value); if (v.length > 1) { for (var j = 0; j < v.length && i + j < 17; j++) bx[i + j].value = v.charAt(j); bx[Math.min(16, i + v.length)].focus(); } else { b.value = v; if (v && bx[i + 1]) bx[i + 1].focus(); } sync(); });
                      b.addEventListener('keydown', function (e) { if (e.key === 'Backspace' && !b.value && bx[i - 1]) { bx[i - 1].focus(); bx[i - 1].value = ''; sync(); e.preventDefault(); } else if (e.key === 'ArrowLeft' && bx[i - 1]) bx[i - 1].focus(); else if (e.key === 'ArrowRight' && bx[i + 1]) bx[i + 1].focus(); });
                      b.addEventListener('paste', function (e) { var t = clean((e.clipboardData || window.clipboardData).getData('text')); if (!t) return; e.preventDefault(); for (var j = 0; j < t.length && i + j < 17; j++) bx[i + j].value = t.charAt(j); bx[Math.min(16, i + t.length)].focus(); sync(); });
                    });
                  })();</script>
                <?php elseif ($f['bound']): ?>
                  <?php if (!empty($f['is_date'])): ?>
                  <input class="input" type="date" name="fields[<?= e($f['key']) ?>]" value="<?= e($f['iso']) ?>" title="<?= $f['source'] === 'cert.issue_date' ? 'Defaults to today — pick another date if needed' : 'Pre-filled from the job record — you can edit it' ?>"<?= $dis ?>>
                  <?php else: ?>
                  <input class="input" type="text" name="fields[<?= e($f['key']) ?>]" value="<?= e($f['value']) ?>" placeholder="From job record" title="Pre-filled from the job record — you can edit it"<?= $dis ?>>
                  <?php endif; ?>
                <?php elseif (($f['type'] ?? '') === 'checks'): $sel = array_map(fn ($x) => $x === 'Visual Examination/Checks' ? 'Visual' : $x, array_map('trim', explode('|', (string) $f['value']))); ?>
                  <input type="hidden" name="fields[<?= e($f['key']) ?>][]" value="">
                  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px 16px;padding:4px 0"><?php foreach ($f['options'] ?? [] as $o): ?><label class="check"><input type="checkbox" name="fields[<?= e($f['key']) ?>][]" value="<?= e($o) ?>"<?= checked(in_array($o, $sel, true)) . $dis ?>> <?= e($o) ?></label><?php endforeach; ?></div>
                <?php elseif (($f['type'] ?? 'text') === 'select'): ?>
                  <select class="select" name="fields[<?= e($f['key']) ?>]"<?= $dis ?>><option value="">Select…</option><?php foreach ($f['options'] ?? [] as $o): ?><option<?= selected($o, $f['value']) ?>><?= e($o) ?></option><?php endforeach; ?></select>
                <?php elseif (($f['type'] ?? 'text') === 'textarea'): ?>
                  <textarea class="input" rows="<?= in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) ? 6 : 24 ?>" style="min-height:<?= in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) ? 140 : 520 ?>px;resize:vertical;line-height:1.5" name="fields[<?= e($f['key']) ?>]"<?= $dis ?>><?= e($f['value']) ?></textarea>
                <?php else: ?>
                  <?php if (!empty($f['is_date'])): ?>
                  <input class="input" type="date" name="fields[<?= e($f['key']) ?>]" value="<?= e($f['iso']) ?>"<?= $dis ?>>
                  <?php else: ?>
                  <input class="input" type="<?= ($f['type'] ?? '') === 'number' ? 'number' : 'text' ?>" name="fields[<?= e($f['key']) ?>]" value="<?= e($f['value']) ?>"<?= !empty($f['suggest']) ? ' list="dl-' . e($f['key']) . '" autocomplete="off"' : '' ?><?= $dis ?>>
                  <?php if (!empty($f['suggest'])): ?><datalist id="dl-<?= e($f['key']) ?>"><?php foreach ($f['suggest'] as $o): ?><option value="<?= e($o) ?>"><?php endforeach; ?></datalist><?php endif; ?>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <div class="form-section mt-1"><?= icon('calendar', 'sm') ?> Dates</div>
            <div class="field col-6"><label>Inspection date</label><input class="input" type="date" name="inspection_date" value="<?= e($cert['inspection_date']) ?>"<?= $dis ?>></div>
            <div class="field col-6"><label>Next inspection date</label><input class="input" type="date" name="next_inspection_date" value="<?= e($cert['next_inspection_date']) ?>"<?= $dis ?>><div class="help">Defaults to inspection date + <?= (int) $sj['validity_months'] ?> months − 1 day.</div>
            <script>(function () {
              var f = document.getElementById('certificate-form'); if (!f) return;
              var iv = f.querySelector('[name^="fields["][name*="interval"]'), d = f.querySelector('[name="inspection_date"]'), n = f.querySelector('[name="next_inspection_date"]');
              if (!iv || !d || !n) return;
              function calc() {
                var m = /(\d+)\s*(year|month)/i.exec(iv.value || ''), p = /^(\d{4})-(\d{2})-(\d{2})$/.exec(d.value || ''); if (!m || !p) return;
                var x = new Date(Date.UTC(+p[1], +p[2] - 1 + (+m[1]) * (/year/i.test(m[2]) ? 12 : 1), +p[3])); x.setUTCDate(x.getUTCDate() - 1);   // + N months - 1 day
                n.value = x.getUTCFullYear() + '-' + ('0' + (x.getUTCMonth() + 1)).slice(-2) + '-' + ('0' + x.getUTCDate()).slice(-2);
                n.dispatchEvent(new Event('change', { bubbles: true }));
              }
              iv.addEventListener('change', calc); d.addEventListener('change', calc);
            })();</script></div>
            <div class="form-section mt-1"><?= icon('check', 'sm') ?> <?= in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) ? 'Certification statement' : 'Result' ?></div>
            <?php $scRes = in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true); ?>
            <?php if (!$scRes): ?>
            <div class="col-12 result-pick">
              <?php if ($scRes): /* tick-box result, as printed on the form: SATISFACTORY / UNSATISFACTORY */ ?>
                <input type="hidden" name="result" value="">
                <?php foreach (["accepted" => "SATISFACTORY", "rejected" => "UNSATISFACTORY"] as $o => $lbl): ?>
                  <label class="<?= $o ?>"><input type="checkbox" name="result" value="<?= $o ?>"<?= checked(($cert['result'] === $o) || ($o === 'rejected' && $cert['result'] === 'repair')) . $dis ?> onchange="if(this.checked){var s=this;this.closest('.result-pick').querySelectorAll('input[type=checkbox]').forEach(function(x){if(x!==s)x.checked=false})}"> <?= $lbl ?></label>
                <?php endforeach; ?>
              <?php else: ?>
              <?php foreach (explode(',', $certTpl['result_options']) as $o): ?>
                <label class="<?= e($o) ?>"><input type="radio" name="result" value="<?= e($o) ?>"<?= checked($cert['result'] === $o) . $dis ?>> <?= e(Workflow::label($o)) ?></label>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="field col-12"><label><?= in_array($certTpl['layout'] ?? '', ['sc', 'ss'], true) ? 'Statement (printed in the RESULT OF INSPECTION band at the end of the report)' : 'Result statement (printed under the result)' ?></label><textarea class="input" name="result_statement" rows="5" style="min-height:130px;resize:vertical;line-height:1.5"<?= $dis ?>><?= e($cert['result_statement']) ?></textarea></div>
            <div class="field col-12"><label>Message to QC <span class="muted" style="font-weight:400">— type here if you want to send a message to QC, otherwise leave it blank</span></label><textarea class="textarea" name="remarks" placeholder="Optional — leave blank if there is nothing for QC"<?= $dis ?>><?= e($cert['remarks']) ?></textarea></div>
          </div>
        </div>
        <?php if ($editable): ?>
        <div class="card-f">
          <a class="btn btn-ghost" href="<?= url('documents/certificate/' . $sj['id'] . ($certSeq > 1 ? '?seq=' . $certSeq : '')) ?>" target="_blank"><?= icon('eye', 'sm') ?> Preview<?= $isMultiPage ? ' page ' . $certSeq : ' certificate' ?></a>
          <button class="btn btn-secondary">Save</button>
        </div>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <section class="tab-panel" id="tab-files">
    <?php $pageCount = count($allCerts ?? []); ?>
    <div class="card-b">
      <?php if ($editable): ?>
      <form method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/upload') ?>" enctype="multipart/form-data" class="dropzone mb-2">
        <?= Csrf::field() ?>
        <div class="row wrap" style="justify-content:center;gap:10px">
          <?= icon('camera', 'lg') ?>
          <input type="file" name="files[]" multiple accept="image/*,application/pdf,image/heic,image/heif,.heic,.heif" capture="environment" class="input" style="max-width:320px;padding-top:7px">
          <select class="select" name="kind" style="width:auto"><option value="photo">Photo</option><option value="document">Supporting document</option><?php if ($sj['requires_certificate']): ?><option value="certificate_upload">Certificate (signed PDF)</option><?php endif; ?></select>
          <input class="input" name="caption" placeholder="Caption (optional)" style="max-width:220px">
          <label class="check small"><input type="checkbox" name="on_certificate" value="1"<?= $pageCount > 1 ? ' checked' : '' ?>> Show on certificate</label>
          <?php if ($pageCount > 1): ?>
            <select class="select" name="cert_seq" style="width:auto" title="Which certificate page this photo prints on">
              <option value="0">All certificate pages</option>
              <?php foreach ($allCerts as $c): ?><option value="<?= (int) $c['seq'] ?>"<?= (int) $c['seq'] === (int) ($certSeq ?? 0) && $certSeq > 1 ? ' selected' : '' ?>>Certificate page <?= (int) $c['seq'] ?> only</option><?php endforeach; ?>
            </select>
          <?php endif; ?>
          <button class="btn btn-primary"><?= icon('upload', 'sm') ?> Upload</button>
        </div>
        <?php $cap = DocumentService::photoCapacity($certTpl['layout'] ?? null); $capUsed = 0; foreach ($attachments as $ca) if ($ca['on_certificate'] && str_starts_with((string) $ca['mime'], 'image/')) $capUsed++; ?>
        <div class="small mt-1">JPG, PNG, WEBP or PDF · max <?= (int) config('upload_max_mb') ?> MB each.
          <?php if ($cap['max'] > 0): ?><b>This certificate prints up to <?= (int) $cap['max'] ?> photo<?= $cap['max'] === 1 ? '' : 's' ?></b> marked "Show on certificate" — <?= e($cap['note']) ?>
            <span class="badge <?= $capUsed > $cap['max'] ? 'badge-red' : 'badge-green' ?>" style="margin-left:6px"><?= $capUsed ?> / <?= (int) $cap['max'] ?> used</span>
            <?php if ($capUsed > $cap['max']): ?><span style="color:#b91c1c"> — the extra <?= $capUsed - $cap['max'] ?> will not print.</span><?php endif; ?>
          <?php else: ?><b>This certificate has no photo area</b> — photos stay in the job record only.<?php endif; ?></div>
      </form>
      <?php endif; ?>
      <?php if ($attachments): ?>
        <div class="photos">
          <?php $prN = 0; foreach ($attachments as $a): $img = str_starts_with($a['mime'], 'image/'); if ($img && $a['on_certificate']) $prN++; ?>
            <div class="photo">
              <?php if ($a['on_certificate']): ?><span class="tagc badge badge-green"><i></i>On certificate · #<?= $prN ?></span><?php endif; ?>
              <a href="<?= url('documents/attachment/' . $a['id']) ?>" target="_blank"><?php if ($img): ?><img src="<?= url('documents/attachment/' . $a['id']) ?>" alt="<?= e($a['caption'] ?: $a['original_name']) ?>" loading="lazy"><?php else: ?><div style="height:120px;display:grid;place-items:center;color:var(--muted)"><?= icon('file', 'lg') ?></div><?php endif; ?></a>
              <div class="ph-b"><span title="<?= e($a['original_name']) ?>"><?= e($a['caption'] ?: $a['original_name']) ?><br><span class="muted"><?= e(Workflow::label($a['kind'])) ?></span></span>
                <?php if ($editable): ?><form method="post" action="<?= url('inspector/attachments/' . $a['id'] . '/delete') ?>" data-confirm="Delete this file?"><?= Csrf::field() ?><button class="btn btn-ghost btn-sm" title="Delete"><?= icon('trash', 'sm') ?></button></form><?php endif; ?></div>
              <?php if ($editable): ?>
                <form method="post" action="<?= url('inspector/attachments/' . $a['id'] . '/update') ?>" style="padding:0 10px 10px;display:grid;gap:6px">
                  <?= Csrf::field() ?>
                  <input class="input" name="caption" value="<?= e((string) $a['caption']) ?>" placeholder="Caption" maxlength="190" style="height:30px;font-size:12.5px">
                  <?php if ($img): ?><label class="check small"><input type="checkbox" name="on_certificate" value="1"<?= $a['on_certificate'] ? ' checked' : '' ?>> Show on certificate</label><?php endif; ?>
                  <button class="btn btn-secondary btn-sm">Save</button>
                </form>
                <?php if ($img): ?><div class="row" style="gap:6px;padding:0 10px 10px"><?php foreach (['up' => '← Earlier', 'down' => 'Later →'] as $dr => $dl): ?><form method="post" action="<?= url('inspector/attachments/' . $a['id'] . '/move') ?>" style="flex:1"><?= Csrf::field() ?><input type="hidden" name="dir" value="<?= $dr ?>"><button class="btn btn-ghost btn-sm" style="width:100%" title="Change the print order"><?= $dl ?></button></form><?php endforeach; ?></div><?php endif; ?>
              <?php endif; ?>
              <?php if ($pageCount > 1 && $a['on_certificate']): ?>
                <div style="padding:0 10px 10px">
                  <?php if ($editable): ?>
                    <form method="post" action="<?= url('inspector/attachments/' . $a['id'] . '/page') ?>">
                      <?= Csrf::field() ?>
                      <select class="select" name="cert_seq" onchange="this.form.submit()" style="height:30px;font-size:12.5px;width:100%" title="Certificate page">
                        <option value="0"<?= $a['cert_seq'] === null ? ' selected' : '' ?>>All certificate pages</option>
                        <?php foreach ($allCerts as $c): ?><option value="<?= (int) $c['seq'] ?>"<?= (int) $a['cert_seq'] === (int) $c['seq'] ? ' selected' : '' ?>>Page <?= (int) $c['seq'] ?> only</option><?php endforeach; ?>
                      </select>
                    </form>
                  <?php else: ?>
                    <span class="small muted"><?= $a['cert_seq'] === null ? 'All certificate pages' : 'Certificate page ' . (int) $a['cert_seq'] ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: View::render('partials/empty', ['icon' => 'camera', 'title' => 'No photos or files yet', 'text' => 'Add photos of the equipment, nameplate and any defects.'], null); endif; ?>
    </div>
  </section>

  <?php if ($sj['requires_sticker']): ?>
  <section class="tab-panel" id="tab-sticker">
    <div class="card-b">
      <?php if ($sticker): $red = $sticker['color'] === 'red'; ?>
        <div class="row wrap" style="gap:16px;align-items:center">
          <div class="kpi" style="flex:1;min-width:240px"><span class="k-ic <?= $red ? 't-red' : 't-blue' ?>"><?= icon('lock') ?></span><span>
            <div class="k-v mono" style="font-size:20px"><?= e($sticker['sticker_no']) ?></div>
            <div class="k-l"><?= badge('closed') ?> <?= $red ? 'Red · Failed' : 'White · Accepted' ?> sticker</div>
            <div class="k-t">Linked <?= e(fmt_datetime($sticker['linked_at'])) ?> · locked to this job<?= $sticker['issued_at'] ? ' · valid until ' . e(fmt_date($sticker['expiry_date'])) : '' ?></div></span></div>
          <a class="btn btn-secondary" href="<?= url('documents/sticker/' . $sticker['id']) ?>" target="_blank"><?= icon('eye', 'sm') ?> Digital sticker</a>
        </div>
        <p class="small muted mt-1">A linked sticker cannot be changed. If it was damaged or linked by mistake, ask an administrator to void it — you can then link a new one.</p>
      <?php elseif ($editable): ?>
        <form class="row wrap" method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/sticker') ?>" style="max-width:620px"
              data-confirm="Link this sticker to <?= e($sj['sub_job_no']) ?>? Once linked it is closed and cannot be changed.">
          <?= Csrf::field() ?>
          <div class="field" style="flex:1;min-width:220px"><label>Sticker number affixed to the equipment</label>
            <input class="input mono" name="sticker_no" list="my-stickers" placeholder="TE-001003" required autocomplete="off" style="text-transform:uppercase">
            <datalist id="my-stickers"><?php foreach ($myStickers as $m): ?><option value="<?= e($m['sticker_no']) ?>"><?= $m['color'] === 'red' ? 'Red · Failed' : 'White · Accepted' ?></option><?php endforeach; ?></datalist></div>
          <button class="btn btn-primary" style="align-self:flex-end"><?= icon('lock', 'sm') ?> Link sticker</button>
        </form>
        <p class="small muted mt-1">
          <?php $blue = count(array_filter($myStickers, fn ($m) => $m['color'] !== 'red')); ?>
          You hold <?= $blue ?> white and <?= count($myStickers) - $blue ?> red sticker(s). Use <b>white</b> for an Accepted result and <b>red</b> for Failed / repair.
          Only stickers allocated to you can be linked, and a linked sticker is locked to this job.</p>
      <?php else: View::render('partials/empty', ['icon' => 'tag', 'title' => 'No sticker linked', 'text' => ''], null); endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="tab-panel" id="tab-submit">
    <div class="card-b">
      <?php if ($st === 'in_progress'): ?>
        <h3 style="font-size:15px" class="mb-2">Completeness check</h3>
        <?php if ($issues): ?>
          <ul class="issue-list"><?php foreach ($issues as $i): ?><li><?= icon('alert', 'sm') ?><span><?= e($i) ?></span></li><?php endforeach; ?></ul>
          <p class="small muted mt-1">Incomplete inspections cannot be submitted. If an item genuinely cannot be completed, ask an administrator to force-submit with an override reason.</p>
        <?php else: ?>
          <div class="ok-line"><?= icon('check') ?> Everything required is complete. Ready for QC.</div>
        <?php endif; ?>
        <form method="post" action="<?= url('inspector/subjobs/' . $sj['id'] . '/submit') ?>" class="mt-2" data-confirm="Submit this inspection to QC? You will not be able to edit it until QC responds.">
          <?= Csrf::field() ?>
          <button class="btn btn-success btn-lg"<?= $issues ? ' disabled' : '' ?>><?= icon('send', 'sm') ?> Submit to QC</button>
        </form>
      <?php else: ?>
        <p class="muted"><?= $st === 'assigned' ? 'Start the inspection first.' : 'Current status: ' . e(Workflow::label($st)) ?></p>
      <?php endif; ?>
      <?php if ($reviews): ?>
        <div class="divider"></div>
        <h3 style="font-size:15px" class="mb-2">QC review history</h3>
        <ul class="timeline"><?php foreach ($reviews as $rv): ?>
          <li><?= badge($rv['decision'] === 'approved' ? 'approved' : 'rework') ?> <b><?= e($rv['reviewer']) ?></b><?= $rv['comments'] ? '<div class="small">' . nl2br(e($rv['comments'])) . '</div>' : '' ?><div class="tl-t"><?= e(fmt_datetime($rv['created_at'])) ?></div></li>
        <?php endforeach; ?></ul>
      <?php endif; ?>
      <?php if ($sj['override_by']): ?><div class="alert alert-warning mt-2"><?= icon('alert') ?><div>Submitted with an authorized override: <?= e($sj['override_reason']) ?></div></div><?php endif; ?>
    </div>
  </section>
</div>
<script>
document.querySelectorAll('[data-tab-link]').forEach((a) => a.addEventListener('click', (e) => { e.preventDefault(); const b = document.querySelector('[data-tab="' + a.dataset.tabLink + '"]'); b && b.click(); b && b.scrollIntoView({ behavior: 'smooth', block: 'start' }); }));
</script>
