<?php
ob_start();
?>
<div class="max-w-lg">
  <h1 class="text-3xl font-extrabold mb-4">Invest in <?php echo htmlspecialchars($product['name']); ?></h1>
  <div class="bw-card p-4">
    <p class="text-sm mb-3">Type: <strong><?php echo htmlspecialchars($product['type']); ?></strong> • Symbol: <strong><?php echo htmlspecialchars($product['symbol'] ?? ''); ?></strong></p>
    <form method="post" action="/invest/submit" class="space-y-3">
      <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>" />
      <div>
        <label class="block text-sm font-semibold mb-1">Amount</label>
        <input class="bw-input" type="number" step="0.01" min="<?php echo (float)$product['min_invest']; ?>" name="amount" required />
      </div>
      <button class="bw-btn" type="submit">Confirm Investment</button>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/user.php';
?>