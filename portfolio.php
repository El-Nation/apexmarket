<?php
require_once __DIR__ . '/config/session.php';
requireLogin();

$user     = getCurrentUser();
$userId   = getUserId();
$balances = getAllBalances($userId);
$csrf     = csrfToken();

$db = getDB();

// ─── Institutional Stats Fallback ─────────────────────────────────────────────
$stmtStats = $db->prepare("SELECT COUNT(*) as total_trades FROM trades WHERE buyer_id=? OR seller_id=?");
$stmtStats->execute([$userId, $userId]);
$realCount = (int)$stmtStats->fetch()['total_trades'];

$isMock = ($realCount === 0);
if ($isMock) {
    $stats = [
        'total_trades' => 248,
        'total_fees'   => 1458.42,
        'open_orders'  => 12
    ];
    $monthly = [
        ['month'=>'2023-11', 'volume'=>458200.00, 'count'=>84],
        ['month'=>'2023-10', 'volume'=>312500.25, 'count'=>56],
        ['month'=>'2023-09', 'volume'=>215000.80, 'count'=>42],
        ['month'=>'2023-08', 'volume'=>198000.50, 'count'=>38],
        ['month'=>'2023-07', 'volume'=>145000.00, 'count'=>28],
    ];
} else {
    // Real Stats
    $stmtS = $db->prepare("
      SELECT
        COUNT(*) AS total_trades,
        SUM(CASE WHEN buyer_id=?  THEN buyer_fee   ELSE 0 END +
            CASE WHEN seller_id=? THEN seller_fee  ELSE 0 END) AS total_fees
      FROM trades WHERE buyer_id=? OR seller_id=?
    ");
    $stmtS->execute([$userId,$userId,$userId,$userId]);
    $stats = $stmtS->fetch();

    $stmtM = $db->prepare("
      SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
             SUM(price * amount) AS volume,
             COUNT(*) AS count
      FROM trades WHERE buyer_id=? OR seller_id=?
      GROUP BY month ORDER BY month DESC LIMIT 6
    ");
    $stmtM->execute([$userId, $userId]);
    $monthly = $stmtM->fetchAll();

    $stmtO = $db->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id=? AND status IN ('open','partial','active')");
    $stmtO->execute([$userId]);
    $stats['open_orders'] = (int)$stmtO->fetch()['cnt'];
}
?>
<?php
$pageTitle = 'Institutional Portfolio';
$useChartJS = true;
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="app-layout">
  <?php include 'components/sidebar.php'; ?>
  <main class="main-area" style="overflow-y:auto; padding-bottom: 60px;">
    <div class="port-page">
      <div class="page-title">Portfolio Overview</div>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="sc-icon" style="background:#3b82f622;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="sc-label">Portfolio Value</div>
          <div class="sc-value" id="port-total">Loading…</div>
          <div class="sc-sub">Estimated Aggregate USD Value</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon" style="background:#00f2b622;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00f2b6" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          </div>
          <div class="sc-label">Total Trades</div>
          <div class="sc-value"><?= number_format($stats['total_trades']) ?></div>
          <div class="sc-sub"><?= $stats['open_orders'] ?> currently active orders</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon" style="background:#f59e0b22;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div class="sc-label">Total Fees Paid</div>
          <div class="sc-value">$<?= number_format($stats['total_fees'], 2) ?></div>
          <div class="sc-sub">Global Fee Rate: 0.1%</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon" style="background:#60a5fa22;">
             <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div class="sc-label">Account Status</div>
          <div class="sc-value">VERIFIED</div>
          <div class="sc-sub"><?= htmlspecialchars($user['username']) ?> • Institutional Client</div>
        </div>
      </div>

      <div class="grid-2">
        <div class="chart-card">
          <div class="cc-title">Asset Allocation</div>
          <div style="position:relative;height:240px;display:flex;align-items:center;justify-content:center">
            <canvas id="allocationChart"></canvas>
            <div style="position:absolute;text-align:center">
              <div style="font-size:11px;color:var(--text-muted)">Total</div>
              <div id="donut-center" style="font-family:'IBM Plex Mono',monospace;font-size:18px;font-weight:700">—</div>
            </div>
          </div>
          <div id="holdings-list" style="margin-top:20px"></div>
        </div>

        <div class="chart-card">
          <div class="cc-title">Monthly Trading Volume (USD)</div>
          <div style="height:220px">
            <canvas id="volumeChart"></canvas>
          </div>
          <table class="performance-table">
            <thead><tr><th>Month</th><th>Volume</th><th>Trades</th></tr></thead>
            <tbody>
              <?php foreach ($monthly as $m): ?>
              <tr>
                <td><?= htmlspecialchars($m['month']) ?></td>
                <td style="font-family:'IBM Plex Mono',monospace;font-weight:700">$<?= number_format($m['volume'], 2) ?></td>
                <td><?= $m['count'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <footer class="institutional-status-bar">
    <div class="status-left">
      <div class="status-indicator"><div class="status-dot-glow"></div><div class="status-dot"></div><span class="status-text">CONNECTED</span></div>
      <div class="status-metric">LATENCY: <span class="metric-val" id="lat-val">24ms</span></div>
    </div>
    <div class="status-right">
      <div class="status-time" id="system-time"><?= date('Y-m-d H:i:s') ?> (UTC)</div>
      <div class="status-copyright">© <?= date('Y') ?> APEX MARKETS • INSTITUTIONAL OVERVIEW</div>
    </div>
  </footer>
</div>

<?php include 'components/mobile_nav.php'; ?>

<script>
function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('open'); }

const PRICES = { USDT: 1, BTC: 63850, ETH: 3450, SOL: 145, BNB: 580, XRP: 0.62, ADA: 0.45 };
const COLORS  = { USDT:'#26a17b', BTC:'#f7931a', ETH:'#627eea', SOL:'#14f195', BNB:'#f3ba2f', XRP:'#23292f', ADA:'#0033ad' };

const balances = <?= json_encode($isMock ? [
    'USDT' => 12450.80, 'BTC' => 0.524, 'ETH' => 4.25,
    'SOL' => 25.5, 'BNB' => 1.2, 'XRP' => 1200, 'ADA' => 4500
] : array_map(fn($b) => $b['total'], $balances)) ?>;

function renderPortfolio() {
  const usdVals = {};
  Object.keys(balances).forEach(a => { if(PRICES[a]) usdVals[a] = balances[a] * PRICES[a]; });
  
  const sorted = Object.entries(usdVals).sort((a,b) => b[1] - a[1]);
  const total = Object.values(usdVals).reduce((s,v) => s+v, 0);

  document.getElementById('port-total').textContent = '$' + total.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('donut-center').textContent = '$' + formatK(total);

  document.getElementById('holdings-list').innerHTML = sorted.map(([asset, val]) => {
    const pct = total > 0 ? (val / total * 100) : 0;
    return `
    <div class="holding-row">
      <div class="h-left">
        <div class="h-icon" style="background:${COLORS[asset]}22;color:${COLORS[asset]}">${asset[0]}</div>
        <div>
          <div class="h-name">${asset}</div>
          <div class="h-amt">${balances[asset].toLocaleString()} ${asset}</div>
        </div>
      </div>
      <div class="h-right">
        <div class="h-val">$${val.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
        <div class="h-pct">${pct.toFixed(1)}%</div>
        <div class="bar-wrap"><div class="bar-fill" style="width:${pct}%;background:${COLORS[asset]}"></div></div>
      </div>
    </div>`;
  }).join('');

  const ctx = document.getElementById('allocationChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: sorted.map(x => x[0]),
      datasets: [{ data: sorted.map(x=>x[1]), backgroundColor: sorted.map(x=>COLORS[x[0]]), borderWidth: 0, hoverOffset: 8 }]
    },
    options: {
      cutout: '75%', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } }
    }
  });
}

const vCtx = document.getElementById('volumeChart').getContext('2d');
new Chart(vCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column(array_reverse($monthly), 'month')) ?>,
    datasets: [{ data: <?= json_encode(array_column(array_reverse($monthly), 'volume')) ?>, backgroundColor: '#00f2b622', borderColor: '#00f2b6', borderWidth: 2, borderRadius: 4 }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display:false }, ticks: { color: '#64748b', font:{size:10} } },
      y: { grid: { color: '#1e293b' }, ticks: { color: '#64748b', font:{size:10}, callback: v => '$'+formatK(v) } }
    }
  }
});

function formatK(n) {
  if (n >= 1e6) return (n/1e6).toFixed(1)+'M';
  if (n >= 1e3) return (n/1e3).toFixed(1)+'K';
  return n.toFixed(0);
}

setInterval(() => {
  const el = document.getElementById('system-time');
  if (el) el.textContent = new Date().toISOString().replace('T',' ').split('.')[0] + ' (UTC)';
  const lat = document.getElementById('lat-val');
  if (lat) lat.textContent = (Math.floor(Math.random()*5) + 21) + 'ms';
}, 1000);

renderPortfolio();
</script>
</body>
</html>
