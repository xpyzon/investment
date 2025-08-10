<?php
ob_start();
?>
<h1 class="text-3xl font-extrabold mb-6">New Wallet</h1>
<form action="/admin/ui/wallets/create" method="post" class="bw-card p-4 space-y-4">
  <div>
    <label class="block text-sm font-semibold mb-1">Name</label>
    <input name="name" required class="bw-input" placeholder="USDT ERC20" />
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-semibold mb-1">Currency</label>
      <input name="currency" required class="bw-input" placeholder="USDT" />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Network</label>
      <input name="network" required class="bw-input" placeholder="erc20|tron|bitcoin|ripple" />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Confirmations</label>
      <input type="number" name="confirmations" class="bw-input" value="12" />
    </div>
  </div>
  <div>
    <label class="block text-sm font-semibold mb-1">Address Template</label>
    <input name="address_template" class="bw-input" placeholder="0x... or xpub:..." />
    <p class="text-xs mt-1">Use <code>xpub:</code> prefix for per-user derivation (demo).</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-semibold mb-1">Requires Tag/Memo</label>
      <input type="checkbox" name="requires_tag" class="mr-2" /> <span class="text-sm">Yes</span>
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Tag Label</label>
      <input name="tag_label" class="bw-input" placeholder="memo|destination_tag" />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Icon URL</label>
      <input name="icon_url" class="bw-input" placeholder="https://.../icon.svg" />
    </div>
  </div>
  <div>
    <label class="block text-sm font-semibold mb-1">Enabled</label>
    <input type="checkbox" name="is_enabled" class="mr-2" checked /> <span class="text-sm">Enabled</span>
  </div>
  <div class="flex items-center gap-3">
    <button class="bw-btn" type="submit">Create</button>
    <a href="/admin/ui/wallets" class="bw-btn">Cancel</a>
  </div>
</form>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>