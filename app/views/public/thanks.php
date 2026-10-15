<section class="section">
  <div class="pub-wrap" style="max-width:640px;text-align:center">
    <div class="empty">
      <div class="e-ic t-green" style="width:64px;height:64px;border-radius:50%"><?= icon('check', 'lg') ?></div>
      <h2 style="font-size:26px;color:var(--text)">Request received</h2>
      <?php if ($no): ?><p class="mt-1" style="font-size:15px">Your reference number is <b class="mono" style="font-size:15px;color:var(--text)"><?= e($no) ?></b>.</p><?php endif; ?>
      <p class="mt-1">Our operations team will contact you within one working day to confirm the schedule.</p>
      <div class="row mt-3" style="justify-content:center"><a class="btn btn-secondary" href="<?= url() ?>">Back to home</a><a class="btn btn-primary" href="<?= url('request') ?>">Submit another</a></div>
    </div>
  </div>
</section>
