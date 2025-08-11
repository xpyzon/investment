<?php
ob_start();
?>
<div class="max-w-lg">
  <h1 class="text-3xl font-extrabold mb-4">Request Withdrawal</h1>
  <div class="bw-card p-4">
    <form method="post" action="/withdrawals" class="space-y-3">
      <div>
        <label class="block text-sm font-semibold mb-1">Amount</label>
        <input class="bw-input" type="number" step="0.0001" name="amount" required />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Currency</label>
        <input class="bw-input" type="text" name="currency" placeholder="e.g., USDT" required />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Destination Address</label>
        <input class="bw-input" type="text" name="address" required />
      </div>
      <button class="bw-btn" type="submit">Submit</button>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/user.php';
?>