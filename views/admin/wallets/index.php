<?php
ob_start();
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-extrabold">Wallets</h1>
  <a href="/admin/ui/wallets/create" class="bw-btn">New Wallet</a>
</div>
<div class="bw-card p-4">
  <div class="overflow-x-auto">
    <table class="bw-table w-full text-sm">
      <thead>
        <tr class="text-left">
          <th>Name</th>
          <th>Currency</th>
          <th>Network</th>
          <th>Confirmations</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($wallets ?? []) as $w): ?>
          <tr>
            <td class="font-semibold"><?php echo htmlspecialchars($w['name']); ?></td>
            <td><?php echo htmlspecialchars($w['currency']); ?></td>
            <td><?php echo htmlspecialchars($w['network']); ?></td>
            <td><?php echo (int)$w['confirmations']; ?></td>
            <td><?php echo ((int)$w['is_enabled']) ? 'Enabled' : 'Disabled'; ?></td>
            <td class="space-x-2">
              <a class="bw-btn" href="/admin/ui/wallets/<?php echo $w['id']; ?>/edit">Edit</a>
              <form class="inline" action="/admin/ui/wallets/<?php echo $w['id']; ?>/toggle" method="post" style="display:inline">
                <button class="bw-btn" type="submit">Toggle</button>
              </form>
              <form class="inline" action="/admin/ui/wallets/<?php echo $w['id']; ?>/assign" method="post" style="display:inline">
                <button class="bw-btn" type="submit">Assign All</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>