<?php
ob_start();
?>
<div class="max-w-lg">
  <h1 class="text-3xl font-extrabold mb-4">Enable 2FA</h1>
  <div class="bw-card p-4 space-y-3">
    <p>Scan this in Google Authenticator / Authy or add the code manually.</p>
    <div>
      <div class="text-sm font-semibold mb-1">Secret</div>
      <div class="font-mono text-sm p-2 border border-black inline-block"><?php echo htmlspecialchars($secret); ?></div>
    </div>
    <div>
      <div class="text-sm font-semibold mb-1">URI</div>
      <div class="font-mono text-xs p-2 border border-black break-all"><?php echo htmlspecialchars($uri); ?></div>
    </div>
    <?php if (!empty($error)): ?>
      <div class="bw-card p-2"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="/account/2fa/enable" class="space-y-3">
      <input type="hidden" name="secret" value="<?php echo htmlspecialchars($secret); ?>" />
      <div>
        <label class="block text-sm font-semibold mb-1">Enter 6-digit code</label>
        <input class="bw-input" type="text" name="code" pattern="\d{6}" required />
      </div>
      <button class="bw-btn" type="submit">Enable 2FA</button>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/user.php';
?>