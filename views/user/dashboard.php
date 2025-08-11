<?php
ob_start();
?>
<div class="space-y-4">
  <h1 class="text-3xl font-extrabold">Welcome<?php echo isset($user['name']) ? ', '.htmlspecialchars($user['name']) : ''; ?></h1>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <a class="bw-card p-4 block" href="/products">
      <div class="font-semibold">Browse Products</div>
      <div class="text-sm">Invest in crypto, stocks, forex, fixed.</div>
    </a>
    <a class="bw-card p-4 block" href="/wallets">
      <div class="font-semibold">Deposit</div>
      <div class="text-sm">Generate deposit addresses.</div>
    </a>
    <a class="bw-card p-4 block" href="/withdrawals">
      <div class="font-semibold">Withdraw</div>
      <div class="text-sm">Request a withdrawal.</div>
    </a>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/user.php';
?>