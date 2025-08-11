<?php
ob_start();
?>
<div class="space-y-4">
  <h1 class="text-3xl font-extrabold">Products</h1>
  <div class="bw-card p-4">
    <div class="overflow-x-auto">
      <table class="bw-table w-full text-sm">
        <thead>
          <tr class="text-left">
            <th>Name</th>
            <th>Type</th>
            <th>Symbol</th>
            <th>Min</th>
            <th>Max</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($products ?? []) as $p): ?>
            <tr>
              <td class="font-semibold"><?php echo htmlspecialchars($p['name']); ?></td>
              <td><?php echo htmlspecialchars($p['type']); ?></td>
              <td><?php echo htmlspecialchars($p['symbol'] ?? ''); ?></td>
              <td><?php echo (float)$p['min_invest']; ?></td>
              <td><?php echo (float)$p['max_invest']; ?></td>
              <td>
                <a class="bw-btn" href="/invest/<?php echo (int)$p['id']; ?>">Invest</a>
              </td>
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