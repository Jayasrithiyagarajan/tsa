<?php
$h = fn ($k, $d = '') => old($k, $tpl[$k] ?? $d);
$rows = old('fields') ?: $fields;
if (!$rows) $rows = [['label' => 'Client Name', 'source' => 'client.name'], ['label' => 'Inspection Location', 'source' => 'job.location']];
$opts = explode(',', $h('result_options', 'accepted,rejected,repair'));
$row = function ($i, $f) { $isOpt = ($f['type'] ?? 'text') === 'select'; ?>
  <div data-repeat-row style="padding:10px 0;border-bottom:1px dashed var(--border)">
    <div class="row" style="gap:8px;flex-wrap:wrap">
      <span class="row" style="gap:2px"><button type="button" class="btn btn-ghost btn-sm" data-move="up">↑</button><button type="button" class="btn btn-ghost btn-sm" data-move="down">↓</button></span>
      <input type="hidden" name="fields[<?= $i ?>][key]" value="<?= e($f['key'] ?? '') ?>">
      <input class="input" style="flex:2;min-width:180px" name="fields[<?= $i ?>][label]" value="<?= e($f['label'] ?? '') ?>" placeholder="Label printed on certificate" aria-label="Label">
      <select class="select" style="flex:2;min-width:200px" name="fields[<?= $i ?>][source]" aria-label="Value source">
        <?php foreach (CertificateService::SOURCES as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $f['source'] ?? '') ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
      <select class="select" style="flex:1;min-width:110px" name="fields[<?= $i ?>][type]" aria-label="Input type"><?php foreach (['text' => 'Text', 'date' => 'Date', 'number' => 'Number', 'select' => 'Dropdown'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $f['type'] ?? 'text') ?>><?= $l ?></option><?php endforeach; ?></select>
      <label class="check small"><input type="checkbox" name="fields[<?= $i ?>][required]" value="1"<?= checked(!empty($f['required'])) ?>> Required</label>
      <label class="check small" title="Pre-fill from the inspector's previous certificate (equipment / consumables)"><input type="checkbox" name="fields[<?= $i ?>][carry]" value="1"<?= checked(!empty($f['carry'])) ?>> Carry over</label>
      <button type="button" class="icon-btn" data-repeat-remove title="Remove"><?= icon('trash', 'sm') ?></button>
    </div>
    <input class="input mt-1" name="fields[<?= $i ?>][options]" value="<?= e(implode(', ', (array) ($f['options'] ?? []))) ?>" placeholder="Dropdown options, comma-separated (only for Dropdown)">
  </div>
<?php }; ?>
<div class="page-head">
  <div class="ph-text"><div class="eyebrow"><?= e($type['name']) ?></div><h1>Certificate template</h1><div class="sub">Rendered in the F-7.5.1-06 layout: two-column data grid, image area, Result (Accepted / Rejected / Repair), Remarks \ Notes and signature block. Fields fill left-to-right, two per row.</div></div>
  <div class="actions"><?php if ($tpl): ?><a class="btn btn-secondary" href="<?= url('admin/templates/preview/certificate/' . $tpl['id']) ?>" target="_blank"><?= icon('eye', 'sm') ?> Preview</a><?php endif; ?></div>
</div>
<form method="post" action="<?= url('admin/templates/certificate/' . $type['id']) ?>">
  <?= Csrf::field() ?>
  <div class="grid grid-main">
    <div class="stack">
      <div class="card">
        <div class="card-h"><h2><?= icon('grid', 'sm') ?> Data grid fields</h2><span class="hint">system-bound fields fill automatically; others are entered by the inspector</span></div>
        <div class="card-b" style="padding-top:4px">
          <div id="cert-fields"><?php foreach (array_values($rows) as $i => $f) $row($i, $f); ?></div>
          <template id="cert-fields-tpl"><?php $row('__i__', []); ?></template>
          <button type="button" class="btn btn-secondary mt-2" data-repeat-add="cert-fields"><?= icon('plus', 'sm') ?> Add field</button>
        </div>
      </div>
      <div class="card">
        <div class="card-h"><h2><?= icon('check', 'sm') ?> Result & notes</h2></div>
        <div class="card-b"><div class="form-grid">
          <div class="col-12"><label class="label">Result options</label><div class="row wrap" style="gap:8px 20px">
            <?php foreach (['accepted' => 'Accepted', 'rejected' => 'Rejected', 'repair' => 'Repair'] as $k => $l): ?><label class="check"><input type="checkbox" name="result_options[]" value="<?= $k ?>"<?= checked(in_array($k, $opts, true)) ?>> <?= $l ?></label><?php endforeach; ?></div></div>
          <div class="field col-12"><label>Default result statement</label><input class="input" name="default_statement" value="<?= e($h('default_statement')) ?>"></div>
          <div class="field col-12"><label>Standard notes (one bullet per line)</label><textarea class="textarea" name="notes" style="min-height:140px"><?= e($h('notes')) ?></textarea></div>
          <div class="field col-6"><label>Inspector label</label><input class="input" name="inspector_label" value="<?= e($h('inspector_label', 'Inspector:')) ?>"></div>
          <div class="col-6" style="align-self:end"><label class="check"><input type="checkbox" name="show_image_area" value="1"<?= checked((bool) $h('show_image_area', 1)) ?>> Show image / sketch area</label></div>
        </div></div>
      </div>
    </div>
    <div class="stack" style="position:sticky;top:76px">
      <div class="card"><div class="card-h"><h2><?= icon('file', 'sm') ?> Document header</h2></div>
        <div class="card-b stack">
          <div class="field"><label>Title (printed)</label><input class="input" name="title" value="<?= e($h('title')) ?>" required></div>
          <div class="field"><label>Doc Ref.</label><input class="input" name="doc_ref" value="<?= e($h('doc_ref')) ?>" required></div>
          <div class="form-grid"><div class="field col-6"><label>Rev.</label><input class="input" name="revision" value="<?= e($h('revision', '01')) ?>" required></div>
            <div class="field col-6"><label>Rev. date</label><input class="input" name="revision_date" value="<?= e($h('revision_date')) ?>"></div></div>
        </div>
        <div class="card-f"><button class="btn btn-primary btn-block"><?= icon('check', 'sm') ?> Save template</button></div></div>
      <div class="card"><div class="card-h"><h2><?= icon('history', 'sm') ?> Versions</h2></div><div class="card-b flush"><table class="t"><tbody>
        <?php foreach ($versions as $v): ?><tr><td><b>v<?= (int) $v['version'] ?></b> <?= $v['is_active'] ? badge('active') : '' ?><div class="sub"><?= e(fmt_date($v['created_at'])) ?></div></td><td class="sub right">used <?= (int) $v['used'] ?>×</td>
          <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('admin/templates/preview/certificate/' . $v['id']) ?>"><?= icon('eye', 'sm') ?></a></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    </div>
  </div>
</form>
