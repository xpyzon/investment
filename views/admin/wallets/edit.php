<?php
ob_start();
?>
<h1 class="text-3xl font-extrabold mb-6">Edit Wallet</h1>
<form action="/admin/ui/wallets/<?php echo $wa['id']; ?>/edit" method="post" class="bw-card p-4 space-y-4">
  <div>
    <label class="block text-sm font-semibold mb-1">Name</label>
    <input name="name" required class="bw-input" value="<?php echo htmlspecialchars($wa['name']); ?>" />
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-semibold mb-1">Currency</label>
      <input name="currency" required class="bw-input" value="<?php echo htmlspecialchars($wa['currency']); ?>" />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Network</label>
      <input name="network" required class="bw-input" value="<?php echo htmlspecialchars($wa['network']); ?>" />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Confirmations</label>
      <input type="number" name="confirmations" class="bw-input" value="<?php echo (int)$wa['confirmations']; ?>" />
    </div>
  </div>
  <div>
    <label class="block text-sm font-semibold mb-1">Address Template</label>
    <input name="address_template" class="bw-input" value="<?php echo htmlspecialchars((string)($wa['address_template'] ?? '')); ?>" />
    <p class="text-xs mt-1">Use <code>xpub:</code> prefix for per-user derivation (demo).</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-semibold mb-1">Requires Tag/Memo</label>
      <input type="checkbox" name="requires_tag" class="mr-2" <?php echo ((int)$wa['requires_tag']) ? 'checked' : ''; ?> /> <span class="text-sm">Yes</span>
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Tag Label</label>
      <input name="tag_label" class="bw-input" value="<?php echo htmlspecialchars((string)($wa['tag_label'] ?? '')); ?>" />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Icon URL</label>
      <input name="icon_url" class="bw-input" value="<?php echo htmlspecialchars((string)($wa['icon_url'] ?? '')); ?>" />
    </div>
  </div>
  <div>
    <label class="block text-sm font-semibold mb-1">Enabled</label>
    <input type="checkbox" name="is_enabled" class="mr-2" <?php echo ((int)$wa['is_enabled']) ? 'checked' : ''; ?> /> <span class="text-sm">Enabled</span>
  </div>
  <div class="flex items-center gap-3">
    <button class="bw-btn" type="submit">Save</button>
    <a href="/admin/ui/wallets" class="bw-btn">Cancel</a>
  </div>
</form>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>