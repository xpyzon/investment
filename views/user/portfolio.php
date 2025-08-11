<?php
ob_start();
?>
<div class="space-y-6">
  <h1 class="text-3xl font-extrabold">Portfolio</h1>

  <div class="bw-card p-4">
    <h2 class="font-semibold mb-2">Investments</h2>
    <div class="overflow-x-auto">
      <table class="bw-table w-full text-sm">
        <thead>
          <tr class="text-left">
            <th>Product</th>
            <th>Type</th>
            <th>Units</th>
            <th>Entry Price</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($investments ?? []) as $inv): ?>
            <tr>
              <td><?php echo htmlspecialchars($inv['name']); ?></td>
              <td><?php echo htmlspecialchars($inv['type']); ?></td>
              <td><?php echo (float)$inv['units']; ?></td>
              <td><?php echo (float)$inv['entry_price']; ?></td>
              <td><?php echo htmlspecialchars($inv['status']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bw-card p-4">
    <h2 class="font-semibold mb-2">Recent transactions</h2>
    <div class="overflow-x-auto">
      <table class="bw-table w-full text-sm">
        <thead>
          <tr class="text-left">
            <th>Type</th>
            <th>Amount</th>
            <th>Currency</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($transactions ?? []) as $t): ?>
            <tr>
              <td><?php echo htmlspecialchars($t['type']); ?></td>
              <td><?php echo (float)$t['amount']; ?></td>
              <td><?php echo htmlspecialchars($t['currency']); ?></td>
              <td><?php echo htmlspecialchars($t['created_at']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/user.php';
?>