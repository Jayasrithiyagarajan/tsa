<?php
/** Equipment repeater shared by website, admin (email/phone) and customer portal request forms. @var array $types */
$rows = old('items') ?: [['inspection_type_id' => '', 'equipment_description' => '', 'equipment_no' => '', 'quantity' => 1]];
$row = function ($i, $it) use ($types) { ?>
  <div class="form-grid" data-repeat-row style="padding:12px 0;border-bottom:1px dashed var(--border)">
    <div class="field col-4"><label>Service</label>
      <select class="select" name="items[<?= $i ?>][inspection_type_id]">
        <option value="">Not sure / other</option>
        <?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"<?= selected($t['id'], $it['inspection_type_id'] ?? '') ?>><?= e($t['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="field col-4"><label>Equipment description <span class="req">*</span></label>
      <input class="input" name="items[<?= $i ?>][equipment_description]" value="<?= e($it['equipment_description'] ?? '') ?>" placeholder="e.g. Boom truck 25T"></div>
    <div class="field col-2"><label>Equipment ID</label>
      <input class="input" name="items[<?= $i ?>][equipment_no]" value="<?= e($it['equipment_no'] ?? '') ?>" placeholder="Optional"></div>
    <div class="field col-2"><label>Qty</label>
      <div class="row"><input class="input" type="number" min="1" max="500" name="items[<?= $i ?>][quantity]" value="<?= e($it['quantity'] ?? 1) ?>">
      <button type="button" class="icon-btn" data-repeat-remove title="Remove" aria-label="Remove row"><?= icon('trash', 'sm') ?></button></div></div>
  </div>
<?php }; ?>
<div id="req-items">
  <?php foreach (array_values($rows) as $i => $it) $row($i, $it); ?>
</div>
<template id="req-items-tpl"><?php $row('__i__', []); ?></template>
<?= field_error('items') ?>
<button type="button" class="btn btn-secondary btn-sm mt-1" data-repeat-add="req-items"><?= icon('plus', 'sm') ?> Add equipment</button>
