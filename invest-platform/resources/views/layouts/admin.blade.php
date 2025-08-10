<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'Admin') • Invest</title>
  @vite('resources/css/app.css')
</head>
<body class="bg-white text-black">
  <div class="min-h-screen grid grid-cols-12">
    <aside class="col-span-2 border-r border-black p-6">
      <div class="font-bold text-xl mb-8">INVEST</div>
      <nav class="space-y-4 text-sm">
        <a href="{{ route('admin.wallets.index') }}" class="block hover:underline">Wallets</a>
      </nav>
    </aside>
    <main class="col-span-10 p-10">
      <h1 class="text-3xl font-extrabold mb-8">@yield('heading')</h1>
      @if (session('status'))
        <div class="border border-black p-3 mb-6">{{ session('status') }}</div>
      @endif
      @yield('content')
    </main>
  </div>
</body>
</html>