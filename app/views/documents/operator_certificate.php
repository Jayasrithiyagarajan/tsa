<?php
/** Certificate of Competence (operator assessment) — layout of OP-F-7.5.1-01 CT. @var array $m @var ?array $toolbar */
$sj = $m['sj']; $c = $m['card']; $od = $m['od'];
$d = fn ($x) => $x ? e(date('j F Y', strtotime($x))) : '';
$body = '<div class="oc">'
  . '<div class="oc-title">CERTIFICATE OF COMPETENCE</div>'
  . '<table class="oc-no"><tr><td class="l">Certificate No.:</td><td class="v">' . e($od['cert_no'] ?? '') . '</td></tr></table>'
  . ($m['photo'] ? '<img class="oc-photo" src="' . $m['photo'] . '" alt="">' : '')
  . '<div class="oc-line l1">Name: ' . e($c['holder_name']) . '</div>'
  . '<div class="oc-line l2">Iqama / ID No: ' . e($c['id_number']) . '</div>'
  . '<div class="oc-line l3">Company: ' . e($c['company']) . '</div>'
  . '<div class="oc-stmt">For the successful completion of the following Training Program:</div>'
  . '<div class="oc-prog">' . e($od['program'] ?? '') . '</div>'
  . '<div class="oc-line l4">Completion Date: ' . $d($od['completion_date'] ?? '') . '</div>'
  . '<div class="oc-line l5">Recommended Renewal Date: ' . $d($od['renewal_date'] ?? '') . '</div>'
  . '<div class="oc-line l6">Trainer / Instructor:<br>' . e($od['trainer'] ?? '') . '</div>'
  . '</div>';
echo View::partial('documents/frame', [
    'title'    => 'Certificate of Competence · ' . ($od['cert_no'] ?? ''),
    'pages'    => [['body' => $body, 'top' => 39.7, 'left' => 14, 'width' => 188.6]],
    'sidetext' => 'Doc Ref.  OP-F-7.5.1-01 CT   l   Rev. 00',
    'qr'       => $m['qr'],
    'toolbar'  => $toolbar ?? null,
]);
