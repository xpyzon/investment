<?php
ob_start();
?>
<div class="space-y-6">
  <h1 class="text-3xl font-extrabold">Market</h1>
  <div class="bw-card p-4">
    <h2 class="font-semibold mb-2">BTC/USD (24h)</h2>
    <canvas id="chart" height="120"></canvas>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1"></script>
<script>
(async function(){
  const res = await fetch('/api/market/chart?id=bitcoin&vs=usd&days=1&interval=hourly');
  const data = await res.json();
  const points = (data.prices||[]).map(([ts, price]) => ({x: new Date(ts), y: price}));
  const ctx = document.getElementById('chart');
  new Chart(ctx, {
    type: 'line',
    data: { datasets: [{ label: 'BTC', data: points, borderColor: '#000', pointRadius: 0, tension: 0.2 }] },
    options: { parsing: false, scales: { x: { type: 'time', time: { unit: 'hour' } } } }
  });
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/user.php';
?>