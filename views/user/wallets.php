<?php
ob_start();
?>
<div class="space-y-4">
  <h1 class="text-3xl font-extrabold">Deposit wallets</h1>
  <div class="bw-card p-4">
    <div class="overflow-x-auto">
      <table class="bw-table w-full text-sm">
        <thead>
          <tr class="text-left">
            <th>Name</th>
            <th>Currency</th>
            <th>Network</th>
            <th>Address</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($wallets ?? []) as $w): ?>
            <tr>
              <td class="font-semibold"><?php echo htmlspecialchars($w['name']); ?></td>
              <td><?php echo htmlspecialchars($w['currency']); ?></td>
              <td><?php echo htmlspecialchars($w['network']); ?></td>
              <td class="font-mono text-xs break-all"><?php echo htmlspecialchars($w['deposit_address'] ?? ''); ?></td>
              <td>
                <form method="post" action="/wallets/<?php echo (int)$w['wallet_admin_id']; ?>/generate">
                  <button class="bw-btn" type="submit"><?php echo empty($w['deposit_address']) ? 'Generate' : 'Regenerate'; ?></button>
                </form>
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