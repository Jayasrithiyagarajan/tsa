<?php
$rows = old('items') ?: array_map(fn ($i) => ['section' => $i['section'], 'label' => $i['label'], 'mandatory' => $i['is_mandatory']], $items);
if (!$rows) $rows = [['section' => 'General Requirements', 'label' => '', 'mandatory' => 1]];
$h = fn ($k, $d = '') => old($k, $tpl[$k] ?? $d);
$row = function ($i, $r) { ?>
  <div class="row" data-repeat-row style="padding:6px 0;border-bottom:1px dashed var(--border);gap:8px">
    <span class="row" style="gap:2px;flex:none"><button type="button" class="btn btn-ghost btn-sm" data-move="up" title="Move up">↑</button><button type="button" class="btn btn-ghost btn-sm" data-move="down" title="Move down">↓</button></span>
    <input class="input" style="max-width:220px" name="items[<?= $i ?>][section]" value="<?= e($r['section'] ?? 'General Requirements') ?>" list="sections" aria-label="Section">
    <textarea class="textarea" name="items[<?= $i ?>][label]" rows="<?= max(1, substr_count((string) ($r['label'] ?? ''), "
") + 1) ?>" style="min-height:40px;padding-top:9px" placeholder="Checklist item, e.g. Wire Rope – Main" aria-label="Item"><?= e($r['label'] ?? '') ?></textarea>
    <label class="check small nowrap" title="Mandatory"><input type="checkbox" name="items[<?= $i ?>][mandatory]" value="1"<?= checked(!empty($r['mandatory'])) ?>> Req.</label>
    <button type="button" class="icon-btn" data-repeat-remove title="Remove"><?= icon('trash', 'sm') ?></button>
  </div>
<?php }; ?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow"><?= e($type['name']) ?></div><h1>Checklist template</h1><div class="sub">Rendered in the F-7.5.1-CL layout: DESCRIPTION · PASS · FAIL · N/A · REMARKS, with inspector name, signature and date.</div></div>
  <div class="actions"><?php if ($tpl): ?><a class="btn btn-secondary" href="<?= url('admin/templates/preview/checklist/' . $tpl['id']) ?>" target="_blank"><?= icon('eye', 'sm') ?> Preview</a><?php endif; ?></div>
</div>
<form method="post" action="<?= url('admin/templates/checklist/' . $type['id']) ?>">
  <?= Csrf::field() ?>
  <div class="grid grid-main">
    <div class="card">
      <div class="card-h"><h2><?= icon('clipboard', 'sm') ?> Items</h2><span class="hint">grouped by section, in order</span></div>
      <div class="card-b">
        <datalist id="sections"><?php foreach (array_unique(array_column($rows, 'section')) as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>
        <div id="cl-items"><?php foreach (array_values($rows) as $i => $r) $row($i, $r); ?></div>
        <template id="cl-items-tpl"><?php $row('__i__', ['section' => end($rows)['section'] ?? 'General Requirements', 'mandatory' => 1]); ?></template>
        <button type="button" class="btn btn-secondary mt-2" data-repeat-add="cl-items"><?= icon('plus', 'sm') ?> Add item</button>
        <p class="small muted mt-2">Section names: <span class="mono">~Name</span> prints a blank shaded band (unlabelled on the form, inspectors still see “Name”) · <span class="mono">^Name</span> prints the band split into the PASS / FAIL / N/A / REMARKS columns. Press Enter inside an item for a new line (bullet lists).</p>
      </div>
    </div>
    <div class="stack" style="position:sticky;top:76px">
      <div class="card"><div class="card-h"><h2><?= icon('file', 'sm') ?> Document header</h2></div>
        <div class="card-b stack">
          <div class="field"><label>Title (printed)</label><input class="input" name="title" value="<?= e($h('title')) ?>" required></div>
          <div class="field"><label>Doc Ref.</label><input class="input" name="doc_ref" value="<?= e($h('doc_ref')) ?>" required placeholder="F-7.5.1-CL 01"></div>
          <div class="form-grid"><div class="field col-6"><label>Rev.</label><input class="input" name="revision" value="<?= e($h('revision', '01')) ?>" required></div>
            <div class="field col-6"><label>Rev. date</label><input class="input" name="revision_date" value="<?= e($h('revision_date')) ?>" placeholder="09/Feb./23"></div></div>
        </div>
        <div class="card-f"><button class="btn btn-primary btn-block"><?= icon('check', 'sm') ?> Save template</button></div>
      </div>
      <?php $L = TemplateService::checklistLayout($tpl ?? []); $lo = fn ($k) => (old('layout') ?: [])[$k] ?? (is_array($L[$k]) ? implode(', ', $L[$k]) : $L[$k]); ?>
      <div class="card"><div class="card-h"><h2><?= icon('sliders', 'sm') ?> Print layout</h2><span class="hint">mm / pt</span></div>
        <div class="card-b"><div class="form-grid">
          <div class="field col-12"><label>Column widths</label><input class="input mono" name="layout[cols]" value="<?= e($lo('cols')) ?>">
            <div class="help">Cert. label, cert. value (together = description), PASS, FAIL, N/A, REMARKS. Total = table width (max 190).</div></div>
          <div class="field col-6"><label>Left margin</label><input class="input" name="layout[left]" value="<?= e($lo('left')) ?>"></div>
          <div class="field col-6"><label>Row height</label><input class="input" name="layout[row_h]" value="<?= e($lo('row_h')) ?>"></div>
          <div class="field col-6"><label>Item font (pt)</label><input class="input" name="layout[font]" value="<?= e($lo('font')) ?>"></div>
          <div class="field col-6"><label>Title font (pt)</label><input class="input" name="layout[title_font]" value="<?= e($lo('title_font')) ?>"></div>
          <div class="field col-6"><label>Table top p.1</label><input class="input" name="layout[top]" value="<?= e($lo('top')) ?>"></div>
          <div class="field col-6"><label>Table top p.2+</label><input class="input" name="layout[top_next]" value="<?= e($lo('top_next')) ?>"></div>
          <div class="field col-6"><label>Gap above signature</label><input class="input" name="layout[sig_gap]" value="<?= e($lo('sig_gap')) ?>"></div>
          <div class="field col-6"><label>Description header</label><input class="input" name="layout[desc_label]" value="<?= e($lo('desc_label')) ?>"></div>
          <div class="field col-6"><label>Header rows height</label><input class="input" name="layout[head_h]" value="<?= e($lo('head_h')) ?>"></div>
          <div class="field col-6"><label>Line spacing (× font)</label><input class="input" name="layout[line]" value="<?= e($lo('line')) ?>"></div>
          <div class="field col-6"><label>Title row height</label><input class="input" name="layout[title_h]" value="<?= e($lo('title_h')) ?>"></div>
          <div class="field col-6"><label>Page bottom limit</label><input class="input" name="layout[bottom]" value="<?= e($lo('bottom')) ?>"></div>
          <div class="field col-12"><label>Identification rows</label><select class="select" name="layout[header]">
            <?php foreach (['cert_serial' => 'Certificate No. | Serial No. (CL 01–05)', 'cert_serial_std' => 'Certificate / Serial + Ref. Standard block (CL 07–09)', 'elevator' => 'S.N. / Job No. · Location / Date · Ref. Standard (CL 06)'] as $k => $lbl): ?>
              <option value="<?= $k ?>"<?= selected($k, $L['header']) ?>><?= e($lbl) ?></option><?php endforeach; ?></select></div>
          <div class="field col-6"><label>Identification row height</label><input class="input" name="layout[hdr_row_h]" value="<?= e($lo('hdr_row_h')) ?>"></div>
          <div class="field col-6"><label>Ref. Standard label</label><input class="input" name="layout[std_label]" value="<?= e($lo('std_label')) ?>"></div>
          <div class="field col-12"><label>Ref. Standard text</label><textarea class="textarea" name="layout[std_text]" style="min-height:56px"><?= e($lo('std_text')) ?></textarea></div>
          <div class="field col-6"><label>Remarks column header</label><input class="input" name="layout[rem_label]" value="<?= e($lo('rem_label')) ?>"></div>
          <div class="field col-6"><label>Section band text</label><select class="select" name="layout[band_align]"><?php foreach (['left' => 'Left aligned', 'center' => 'Centred'] as $k => $lbl): ?><option value="<?= $k ?>"<?= selected($k, $L['band_align']) ?>><?= $lbl ?></option><?php endforeach; ?></select></div>
          <div class="field col-6"><label>"Remarks /" box height (0 = none)</label><input class="input" name="layout[remarks_box]" value="<?= e($lo('remarks_box')) ?>"></div>
          <div class="field col-12"><label>Document style</label><select class="select" name="layout[kind]"><?php foreach (['checklist' => 'Checklist grid (PASS / FAIL / N/A)', 'report' => 'Inspection report (cover page + Yes / No / N/A tables)'] as $k => $lbl): ?><option value="<?= $k ?>"<?= selected($k, $L['kind']) ?>><?= $lbl ?></option><?php endforeach; ?></select></div>
          <?php if ($L['kind'] === 'report'): ?>
          <div class="field col-12"><label>Report settings (JSON — cover rows, answer columns, sections, pictures, notes)</label>
            <textarea class="textarea mono" name="layout[report]" style="min-height:220px;font-size:11.5px"><?= e(json_encode($L['report'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></textarea></div>
          <?php endif; ?>
          <div class="col-6" style="align-self:end"><input type="hidden" name="layout[item_bold]" value="0"><label class="check"><input type="checkbox" name="layout[item_bold]" value="1"<?= checked($L['item_bold']) ?>> Bold item text</label></div>
          <div class="col-6" style="align-self:end"><input type="hidden" name="layout[sig_review]" value="0"><label class="check"><input type="checkbox" name="layout[sig_review]" value="1"<?= checked($L['sig_review']) ?>> "Reviewed By" signature block</label></div>
        </div></div></div>
      <div class="card"><div class="card-h"><h2><?= icon('history', 'sm') ?> Versions</h2></div><div class="card-b flush"><table class="t"><tbody>
        <?php foreach ($versions as $v): ?><tr><td><b>v<?= (int) $v['version'] ?></b> <?= $v['is_active'] ? badge('active') : '' ?><div class="sub">Rev. <?= e($v['revision']) ?> · <?= e(fmt_date($v['created_at'])) ?></div></td><td class="sub right">used <?= (int) $v['used'] ?>×</td>
          <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('admin/templates/preview/checklist/' . $v['id']) ?>"><?= icon('eye', 'sm') ?></a></td></tr><?php endforeach; ?>
        <?php if (!$versions): ?><tr><td class="muted">No versions yet.</td></tr><?php endif; ?>
      </tbody></table></div></div>
    </div>
  </div>
</form>
