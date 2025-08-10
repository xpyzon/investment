<?php
ob_start();
?>
<div class="max-w-md mx-auto">
  <h1 class="text-3xl font-extrabold mb-4">Sign in</h1>
  <?php if (!empty($error)): ?>
    <div class="bw-card p-3 mb-4"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <form method="post" action="/login" class="space-y-3">
    <div>
      <label class="block text-sm font-semibold mb-1">Email</label>
      <input class="bw-input" type="email" name="email" required />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Password</label>
      <input class="bw-input" type="password" name="password" required />
    </div>
    <button class="bw-btn" type="submit">Sign in</button>
  </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/user.php';
?>