<?php
require_once __DIR__ . '/config/session.php';
requireLogin();

$user     = getCurrentUser();
$userId   = getUserId();
$balances = getAllBalances($userId);
$csrf     = csrfToken();

$db = getDB();
$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

// ─── 7 COINS CONFIG ───────────────────────────────────────────────────────────
$assetDetails = [
    'USDT' => ['name'=>'Tether',     'color'=>'#26a17b', 'icon'=>'₮', 'price'=>1],
    'BTC'  => ['name'=>'Bitcoin',    'color'=>'#f7931a', 'icon'=>'₿', 'price'=>63850],
    'ETH'  => ['name'=>'Ethereum',   'color'=>'#627eea', 'icon'=>'Ξ', 'price'=>3450],
    'SOL'  => ['name'=>'Solana',     'color'=>'#14f195', 'icon'=>'S', 'price'=>145],
    'BNB'  => ['name'=>'Binance BSC','color'=>'#f3ba2f', 'icon'=>'B', 'price'=>580],
    'XRP'  => ['name'=>'Ripple',     'color'=>'#23292f', 'icon'=>'X', 'price'=>0.62],
    'ADA'  => ['name'=>'Cardano',    'color'=>'#0033ad', 'icon'=>'A', 'price'=>0.45],
];

// Mock Balances for "Full Portfolio" Look if empty
$isEmpty = true;
foreach($assetDetails as $asset => $info) {
    if (($balances[$asset]['free'] ?? 0) > 0) { $isEmpty = false; break; }
}

if ($isEmpty) {
    $balances = [
        'USDT' => ['free'=>12450.80, 'locked'=>0],
        'BTC'  => ['free'=>0.524432,  'locked'=>0.05],
        'ETH'  => ['free'=>4.250000,  'locked'=>0],
        'SOL'  => ['free'=>25.50,     'locked'=>0],
        'BNB'  => ['free'=>1.20,      'locked'=>0],
        'XRP'  => ['free'=>1200.00,   'locked'=>0],
        'ADA'  => ['free'=>4500.00,   'locked'=>0],
    ];
}
?>
<?php
$pageTitle = 'Institutional Wallet';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="app-layout">
  <?php include 'components/sidebar.php'; ?>
  <main class="main-area" style="overflow-y:auto; padding-bottom: 60px;">
    <div class="wallet-page">
      <div class="page-title">Wallet Overview</div>

      <div class="wallet-grid">
        <?php foreach ($assetDetails as $asset => $info):
          $free   = $balances[$asset]['free']   ?? 0;
          $locked = $balances[$asset]['locked'] ?? 0;
          $total  = $free + $locked;
          $usdVal = $total * $info['price'];
        ?>
        <div class="asset-card">
          <div class="ac-header">
            <div class="ac-icon" style="background:<?= $info['color'] ?>22;color:<?= $info['color'] ?>"><?= $info['icon'] ?></div>
            <div>
              <div class="ac-name"><?= $asset ?></div>
              <div class="ac-full"><?= $info['name'] ?></div>
            </div>
          </div>
          <div class="ac-bal">
            <?= $asset === 'USDT' || $asset === 'XRP' || $asset === 'ADA' || $asset === 'SOL' ? number_format($free,2) : number_format($free,6) ?>
          </div>
          <div class="ac-usd">
            ≈ $<?= number_format($usdVal, 2) ?> USD
          </div>
          <?php if ($locked > 0): ?>
          <div style="font-size:11px;color:var(--yellow);margin-bottom:16px;font-family:'IBM Plex Mono',monospace;">
            LOCKED: <?= number_format($locked, 4) ?> <?= $asset ?>
          </div>
          <?php endif; ?>
          <div class="ac-row">
            <button class="ac-btn dep" onclick="openDeposit('<?= $asset ?>')">Deposit</button>
            <button class="ac-btn wd"  onclick="openWithdraw('<?= $asset ?>')">Withdraw</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="page-title" style="font-size:20px; margin-bottom:20px;">Recent Activity</div>
      <div style="overflow-x:auto;">
        <table class="tx-table">
          <thead>
            <tr><th>Date</th><th>Type</th><th>Asset</th><th>Amount</th><th>Status</th><th>Notes</th></tr>
          </thead>
          <tbody>
            <?php if (empty($transactions) && !$isEmpty): ?>
            <tr><td colspan="6" class="empty-msg" style="text-align:center;padding:40px;color:var(--text-muted)">No transactions recorded.</td></tr>
            <?php 
            else: 
              $displayTx = (empty($transactions) && $isEmpty) ? [
                ['created_at'=>'2023-11-24 15:45:12','type'=>'deposit', 'asset'=>'USDT','amount'=>5000.00, 'status'=>'completed','notes'=>'Direct Bank Settlement'],
                ['created_at'=>'2023-11-24 12:20:45','type'=>'deposit', 'asset'=>'BTC', 'amount'=>0.1450,  'status'=>'completed','notes'=>'External Wallet Transfer'],
                ['created_at'=>'2023-11-24 09:05:12','type'=>'withdraw','asset'=>'ETH', 'amount'=>1.2000,  'status'=>'completed','notes'=>'Institutional Custody Transfer'],
                ['created_at'=>'2023-11-23 22:15:00','type'=>'deposit', 'asset'=>'SOL', 'amount'=>145.00,  'status'=>'completed','notes'=>'Staking Yield Reward'],
                ['created_at'=>'2023-11-23 18:45:30','type'=>'withdraw','asset'=>'USDT','amount'=>1200.00, 'status'=>'completed','notes'=>'Treasury Settlement'],
                ['created_at'=>'2023-11-23 14:02:11','type'=>'deposit', 'asset'=>'ADA', 'amount'=>4500.00, 'status'=>'completed','notes'=>'Validator Commission'],
                ['created_at'=>'2023-11-22 19:30:55','type'=>'deposit', 'asset'=>'XRP', 'amount'=>1200.00, 'status'=>'completed','notes'=>'Cross-Border Inbound'],
                ['created_at'=>'2023-11-22 11:12:00','type'=>'withdraw','asset'=>'BNB', 'amount'=>2.50,    'status'=>'completed','notes'=>'Binance Smart Chain Bridge'],
                ['created_at'=>'2023-11-22 08:44:55','type'=>'deposit', 'asset'=>'USDT','amount'=>2500.00, 'status'=>'completed','notes'=>'OTC Desk Purchase'],
                ['created_at'=>'2023-11-21 21:05:12','type'=>'withdraw','asset'=>'BTC', 'amount'=>0.0050,  'status'=>'completed','notes'=>'Cold Storage Synchronization'],
                ['created_at'=>'2023-11-21 15:33:02','type'=>'deposit', 'asset'=>'SOL', 'amount'=>10.00,   'status'=>'completed','notes'=>'Ecosystem Airdrop'],
              ] : $transactions;
              
              foreach ($displayTx as $t): ?>
            <tr>
              <td style="color:var(--text-second)"><?= date('M d, Y H:i', strtotime($t['created_at'])) ?></td>
              <td class="type-<?= $t['type'] ?>">
                <?= strtoupper($t['type']) ?>
              </td>
              <td style="font-weight:700;"><?= htmlspecialchars($t['asset']) ?></td>
              <td style="font-family:'IBM Plex Mono',monospace"><?= number_format($t['amount'], ($t['asset']==='USDT'?2:6)) ?></td>
              <td>
                <span class="status-badge status-<?= $t['status'] === 'completed' ? 'filled' : ($t['status'] === 'pending' ? 'open' : 'cancelled') ?>">
                  <?= ucfirst($t['status'] ?? 'Completed') ?>
                </span>
              </td>
              <td style="color:var(--text-muted); font-size:11px;"><?= htmlspecialchars($t['notes'] ?? 'System Generated') ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Institutional Status Bar -->
  <footer class="institutional-status-bar">
    <div class="status-left">
      <div class="status-indicator">
        <div class="status-dot-glow"></div>
        <div class="status-dot"></div>
        <span class="status-text">CONNECTED</span>
      </div>
      <div class="status-metric">
        LATENCY: <span class="metric-val" id="lat-val">22ms</span>
      </div>
      <div class="status-metric">
        PORTFOLIO: <span class="metric-val" style="color:#00f2b6">STABLE</span>
      </div>
    </div>
    <div class="status-right">
      <div class="status-time" id="system-time"><?= date('Y-m-d H:i:s') ?> (UTC)</div>
      <div class="status-copyright">© <?= date('Y') ?> APEX MARKETS LLC • SECURE WALLET</div>
    </div>
  </footer>
</div>

<?php include 'components/mobile_nav.php'; ?>

<!-- Modals -->
<div class="modal-overlay hidden" id="deposit-modal">
  <div class="modal">
    <div class="modal-head"><div class="modal-title">Deposit Asset</div>
      <button class="modal-close" onclick="closeModal('deposit-modal')">×</button>
    </div>
    <form id="deposit-form">
      <div class="modal-field">
        <label class="modal-label">Choose Asset</label>
        <select class="modal-select modal-input" id="dep-asset">
          <?php foreach($assetDetails as $symbol => $info): ?>
          <option value="<?= $symbol ?>"><?= $symbol ?> — <?= $info['name'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-field">
        <label class="modal-label">Amount</label>
        <input class="modal-input" type="number" id="dep-amount" placeholder="0.00" step="0.000001" min="0" required>
      </div>
      <div style="background:rgba(0,212,170,0.06);border:1px solid rgba(0,212,170,0.2);border-radius:8px;padding:12px;margin-bottom:14px;font-size:12px;color:var(--accent)">
        ℹ Use this for testing. Demo funds are added to your balance instantly.
      </div>
      <button type="submit" class="modal-btn green">Confirm Deposit</button>
    </form>
  </div>
</div>

<div class="modal-overlay hidden" id="withdraw-modal">
  <div class="modal">
    <div class="modal-head"><div class="modal-title">Withdraw Asset</div>
      <button class="modal-close" onclick="closeModal('withdraw-modal')">×</button>
    </div>
    <form id="withdraw-form">
      <div class="modal-field">
        <label class="modal-label">Choose Asset</label>
        <select class="modal-select modal-input" id="wd-asset">
          <?php foreach($assetDetails as $symbol => $info): ?>
          <option value="<?= $symbol ?>"><?= $symbol ?> — <?= $info['name'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-field">
        <label class="modal-label">Withdrawal Address</label>
        <input class="modal-input" type="text" id="wd-address" placeholder="Destination Address" required>
      </div>
      <div class="modal-field">
        <label class="modal-label">Amount</label>
        <input class="modal-input" type="number" id="wd-amount" placeholder="0.00" step="0.000001" min="0" required>
      </div>
      <button type="submit" class="modal-btn red">Confirm Withdrawal</button>
    </form>
  </div>
</div>

<div id="toast-container"></div>

<script>
function openDeposit(asset) {
  document.getElementById('dep-asset').value = asset || 'USDT';
  document.getElementById('deposit-modal').classList.remove('hidden');
}
function openWithdraw(asset) {
  document.getElementById('wd-asset').value = asset || 'USDT';
  document.getElementById('withdraw-modal').classList.remove('hidden');
}
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.add('hidden'); });
});

function toast(msg, type = 'info') {
  const c = document.getElementById('toast-container');
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<span>${msg}</span><span class="toast-close" onclick="this.parentElement.remove()">×</span>`;
  c.appendChild(el); setTimeout(() => el.remove(), 4000);
}

setInterval(() => {
  const el = document.getElementById('system-time');
  if (el) el.textContent = new Date().toISOString().replace('T',' ').split('.')[0] + ' (UTC)';
  const lat = document.getElementById('lat-val');
  if (lat) lat.textContent = (Math.floor(Math.random()*5) + 21) + 'ms';
}, 1000);

async function submitDeposit(e) {
  e.preventDefault();
  const f = new FormData();
  f.append('asset', document.getElementById('dep-asset').value);
  f.append('amount', document.getElementById('dep-amount').value);
  f.append('csrf', document.querySelector('meta[name=csrf]').content);
  const r = await fetch('api/deposit.php', {method:'POST',body:f});
  const d = await r.json();
  if (d.success) { 
    toast('Deposit successful!', 'success'); 
    closeModal('deposit-modal'); 
    setTimeout(()=>location.reload(), 1500); 
  } else toast(d.error || 'Deposit failed', 'error');
}

async function submitWithdraw(e) {
  e.preventDefault();
  const f = new FormData();
  f.append('asset',   document.getElementById('wd-asset').value);
  f.append('amount',  document.getElementById('wd-amount').value);
  f.append('address', document.getElementById('wd-address').value);
  f.append('csrf',    document.querySelector('meta[name=csrf]').content);
  const r = await fetch('api/withdraw.php', {method:'POST',body:f});
  const d = await r.json();
  if (d.success) { 
    toast('Withdrawal submitted!', 'success'); 
    closeModal('withdraw-modal'); 
    setTimeout(()=>location.reload(), 1500); 
  } else toast(d.error || 'Failed', 'error');
}

document.getElementById('deposit-form').addEventListener('submit', submitDeposit);
document.getElementById('withdraw-form').addEventListener('submit', submitWithdraw);
function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('open'); }
</script>
</body>
</html>
