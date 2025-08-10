<?php
ob_start();
?>
<div class="space-y-6 max-w-2xl">
  <h1 class="text-3xl font-extrabold">Account</h1>

  <div class="bw-card p-4">
    <h2 class="font-semibold mb-2">Change password</h2>
    <form method="post" action="/account/password" class="space-y-3">
      <div>
        <label class="block text-sm font-semibold mb-1">Current password</label>
        <input class="bw-input" type="password" name="current" required />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">New password</label>
        <input class="bw-input" type="password" name="new" required />
      </div>
      <button class="bw-btn" type="submit">Update password</button>
    </form>
  </div>

  <div class="bw-card p-4">
    <h2 class="font-semibold mb-2">Two-factor authentication (2FA)</h2>
    <?php if (!empty($twofa_enabled)): ?>
      <p class="mb-3">2FA is enabled.</p>
      <form method="post" action="/account/2fa/disable"><button class="bw-btn" type="submit">Disable 2FA</button></form>
    <?php else: ?>
      <p class="mb-3">2FA adds an extra layer of security to your account.</p>
      <a class="bw-btn" href="/account/2fa/setup">Enable 2FA</a>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/user.php';
?>