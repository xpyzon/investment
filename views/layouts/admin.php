<?php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin · Investment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { color-scheme: light dark; }
    body { background: #fff; color: #000; }
    .bw-card { border: 1px solid #000; background: #fff; }
    .bw-btn { border: 1px solid #000; padding: .5rem .75rem; font-weight: 600; }
    .bw-btn:hover { background: #000; color: #fff; }
    .bw-input { border: 1px solid #000; padding: .5rem .75rem; width: 100%; }
    .bw-table th, .bw-table td { border-bottom: 1px solid #000; padding: .5rem .75rem; }
    a { text-decoration: underline; }
  </style>
</head>
<body class="min-h-screen">
  <header class="border-b border-black">
    <div class="max-w-5xl mx-auto flex items-center justify-between py-4">
      <div class="text-2xl font-bold tracking-tight">Investment Admin</div>
      <nav class="text-sm">
        <a href="/admin/ui/wallets" class="mr-4">Wallets</a>
      </nav>
    </div>
  </header>
  <main class="max-w-5xl mx-auto py-8">
    <?php echo $content ?? ''; ?>
  </main>
  <footer class="border-t border-black mt-8">
    <div class="max-w-5xl mx-auto py-4 text-sm">© <?php echo date('Y'); ?> Investment Platform</div>
  </footer>
</body>
</html>