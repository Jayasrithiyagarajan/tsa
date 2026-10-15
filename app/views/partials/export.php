<?php /** Excel + CSV export buttons for the current filtered list. */ $q = $_GET; ?>
<span class="row" style="gap:6px">
  <a class="btn btn-secondary" href="?<?= e(http_build_query(array_merge($q, ['export' => 'xlsx']))) ?>" title="Download as Excel workbook"><?= icon('download', 'sm') ?> Excel</a>
  <a class="btn btn-ghost" href="?<?= e(http_build_query(array_merge($q, ['export' => 'csv']))) ?>" title="Download as CSV">CSV</a>
</span>
