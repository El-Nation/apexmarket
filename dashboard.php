<?php
require_once __DIR__ . '/config/session.php';
requireLogin();

$user     = getCurrentUser();
$userId   = getUserId();
$balances = getAllBalances($userId);

$usdt = $balances['USDT']['free'] ?? 0;
$btc  = $balances['BTC']['free']  ?? 0;
$eth  = $balances['ETH']['free']  ?? 0;
$csrf = csrfToken();

// Load open orders for initial render
$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
?>
<?php
$pageTitle = 'BTC/USDT';
$useTradingView = true;
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="app-layout">
  <?php include 'components/sidebar.php'; ?>

  <!-- ─── MAIN ──────────────────────────────────────────────────────────────── -->
  <main class="main-area">

    <!-- TICKER BAR -->
    <div class="ticker-bar" style="height:80px; padding:0 32px; gap:40px; background:var(--bg-card); border-bottom:2px solid var(--border);">
      <div class="ticker-pair">
        <div class="ticker-icon" style="width:40px; height:40px; font-size:18px;">₿</div>
        <div>
          <div class="ticker-symbol" style="font-size:22px; font-weight:700; font-family:'Rajdhani',sans-serif;">BTC/USDT</div>
          <div class="ticker-names" style="font-size:12px; font-weight:500; color:var(--text-muted);">Bitcoin / Tether</div>
        </div>
      </div>
      <div class="ticker-price" id="ticker-price" style="font-size:28px; font-weight:700; font-family:'IBM Plex Mono',monospace;">—</div>
      <div class="ticker-change" id="ticker-change" style="font-size:15px; font-weight:600;">Loading…</div>
      <div class="ticker-sep" style="height:40px; width:2px;"></div>
      <div class="ticker-stat">
        <div class="ticker-stat-label" style="font-size:11px; font-weight:600; letter-spacing:1px;">24H HIGH</div>
        <div class="ticker-stat-value" id="ticker-high" style="font-size:15px; font-weight:600; font-family:'IBM Plex Mono',monospace;">—</div>
      </div>
      <div class="ticker-sep" style="height:40px; width:2px;"></div>
      <div class="ticker-stat">
        <div class="ticker-stat-label" style="font-size:11px; font-weight:600; letter-spacing:1px;">24H LOW</div>
        <div class="ticker-stat-value" id="ticker-low" style="font-size:15px; font-weight:600; font-family:'IBM Plex Mono',monospace;">—</div>
      </div>
      <div class="ticker-sep" style="height:40px; width:2px;"></div>
      <div class="ticker-stat">
        <div class="ticker-stat-label" style="font-size:11px; font-weight:600; letter-spacing:1px;">24H VOLUME(BTC)</div>
        <div class="ticker-stat-value" id="ticker-vol" style="font-size:15px; font-weight:600; font-family:'IBM Plex Mono',monospace;">—</div>
      </div>
    </div>

    <!-- TRADING BODY -->
    <div class="trading-body">

      <!-- ── CHART PANEL ───────────────────────────────────────────────────── -->
      <section class="chart-panel">
        <div class="chart-toolbar">
          <button class="tf-btn active" onclick="setTimeframe('1m',this)">1m</button>
          <button class="tf-btn"        onclick="setTimeframe('5m',this)">5m</button>
          <button class="tf-btn"        onclick="setTimeframe('15m',this)">15m</button>
          <button class="tf-btn"        onclick="setTimeframe('1h',this)">1h</button>
          <button class="tf-btn"        onclick="setTimeframe('4h',this)">4h</button>
          <button class="tf-btn"        onclick="setTimeframe('1D',this)">1D</button>
          <div class="chart-sep"></div>
          <button class="chart-tool-btn" title="Fullscreen">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/>
              <line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>
            </svg>
          </button>
          <button class="chart-tool-btn" title="Screenshot">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
          </button>
        </div>
        <div class="chart-container">
          <div id="tradingview_chart"></div>
        </div>
      </section>

      <!-- ── ORDER BOOK + TRADES PANEL ────────────────────────────────────── -->
      <section class="order-panel">
        <!-- Order Book -->
        <div class="panel-section" style="flex:1;overflow:hidden">
          <div class="panel-head">
            <span>Order Book</span>
            <div class="panel-head-actions">
              <button class="panel-icon-btn" title="Config">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="4" y1="6"  x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/>
                  <line x1="4" y1="18" x2="20" y2="18"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="ob-panel">
            <div class="ob-header">
              <span>Price</span><span>Amount</span><span>Total</span>
            </div>
            <div class="ob-sells" id="ob-sells">
              <?php for($i=0;$i<6;$i++): ?>
              <div class="ob-row sell" style="opacity:.3">
                <span class="ob-price">—</span><span>—</span><span>—</span>
              </div>
              <?php endfor; ?>
            </div>
             <div class="ob-mid" style="background:rgba(0, 242, 182, 0.08); border-top:1px solid rgba(0, 242, 182, 0.15); border-bottom:1px solid rgba(0, 242, 182, 0.15); padding:10px 12px; margin:4px 0; display:flex; align-items:center; justify-content:space-between;">
               <div style="display:flex; align-items:center; gap:8px;">
                 <span class="ob-mid-price" id="ob-mid-price" style="font-size:18px; font-weight:700; color:#00f2b6; font-family:'IBM Plex Mono',monospace;">—</span>
                 <svg width="12" height="12" viewBox="0 0 24 24" fill="#00f2b6"><path d="M12 21l-12-18h24z"/></svg>
               </div>
               <span class="ob-mid-index" style="color:var(--text-muted); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Index</span>
             </div>
            <div class="ob-buys" id="ob-buys">
              <?php for($i=0;$i<6;$i++): ?>
              <div class="ob-row buy" style="opacity:.3">
                <span class="ob-price">—</span><span>—</span><span>—</span>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Recent Trades -->
        <div class="panel-section trades-panel">
          <div class="panel-head">
            <span>Recent Trades</span>
          </div>
          <div class="ob-header">
            <span>Price</span><span>Amount</span><span>Time</span>
          </div>
          <div class="trades-list" id="trades-list">
            <div class="empty-msg">Loading trades…</div>
          </div>
        </div>
      </section>

      <!-- ── ORDER FORM PANEL ──────────────────────────────────────────────── -->
      <section class="form-panel">
        <!-- Buy / Sell tabs -->
        <div class="form-tabs">
          <button class="form-tab buy active" onclick="setSide('buy')">Buy</button>
          <button class="form-tab sell"       onclick="setSide('sell')">Sell</button>
        </div>

        <form class="order-form" id="order-form">
          <!-- Order type -->
          <div class="order-type-tabs">
            <button type="button" class="ot-tab active" onclick="setOrderType('limit',this)">Limit</button>
            <button type="button" class="ot-tab"        onclick="setOrderType('market',this)">Market</button>
            <button type="button" class="ot-tab"        onclick="setOrderType('stop',this)">Stop</button>
          </div>

          <!-- Stop Price (hidden by default) -->
          <div class="form-field" id="stop-field" style="display:none">
            <div class="field-label">Stop Price <span>USDT</span></div>
            <input class="field-input" type="number" id="stop-input" placeholder="0.00" step="0.01" min="0">
          </div>

          <!-- Price -->
          <div class="form-field" id="price-field">
            <div class="field-label">Price <span>USDT</span></div>
            <input class="field-input" type="number" id="price-input" placeholder="0.00" step="0.01" min="0">
          </div>

          <!-- Amount -->
          <div class="form-field">
            <div class="field-label">Amount <span>BTC</span></div>
            <input class="field-input" type="number" id="amount-input" placeholder="0.000000" step="0.000001" min="0">
          </div>

          <!-- Percentage -->
          <div class="pct-row">
            <button type="button" class="pct-btn" onclick="setPct(25)">25%</button>
            <button type="button" class="pct-btn" onclick="setPct(50)">50%</button>
            <button type="button" class="pct-btn" onclick="setPct(75)">75%</button>
            <button type="button" class="pct-btn" onclick="setPct(100)">100%</button>
          </div>

          <!-- Available balance -->
          <div class="avail-row">
            <span class="avail-label">Available</span>
            <span class="avail-val" id="avail-val"
                  data-usdt="<?= $usdt ?>"
                  data-btc="<?= $btc ?>"
                  data-eth="<?= $eth ?>"
                  data-raw="<?= $usdt ?>">
              <?= number_format($usdt, 2) ?> USDT
            </span>
          </div>

          <!-- Fee estimate -->
          <div class="fee-row">
            <span>Est. Fee (0.1%)</span>
            <span class="fee-val" id="fee-val">0.00 USDT</span>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-buy buy-side" id="place-order-btn">
            BUY BTC
          </button>
        </form>

        <!-- Wallet Equity (below form) -->
        <!-- Wallet Equity (High Fidelity Match) -->
        <div style="border-top:1px solid var(--border); padding:24px; background:rgba(13, 20, 33, 0.4); border-radius:0 0 12px 12px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
            <div>
              <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; font-weight:600;">Wallet Equity</div>
              <div style="font-size:32px; font-weight:700; font-family:'Rajdhani',sans-serif; color:white; line-height:1;" id="wallet-equity">
                $<?= number_format($usdt + $btc * 64000 + $eth * 3200, 2) ?>
              </div>
              <div style="font-size:14px; color:var(--text-muted); margin-top:6px; font-family:'IBM Plex Mono',monospace;" id="wallet-sub">
                ≈ <?= number_format($btc + ($usdt/64000), 4) ?> BTC
              </div>
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:24px;">
            <button class="wal-btn" onclick="openDeposit()" style="background:#3b82f6; color:white; display:flex; align-items:center; justify-content:center; gap:8px; padding:12px; border:none; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
              Deposit
            </button>
            <button class="wal-btn" onclick="openWithdraw()" style="background:#1e293b; color:var(--text-primary); display:flex; align-items:center; justify-content:center; gap:8px; padding:12px; border:1px solid var(--border); border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>
              Withdraw
            </button>
          </div>

          <div class="asset-list" style="display:flex; flex-direction:column; gap:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:8px; height:8px; border-radius:50%; background:#3b82f6;"></div>
                <div style="font-size:13px; font-weight:600; color:var(--text-second);">USDT</div>
              </div>
              <div style="text-align:right;">
                <div style="font-size:13px; font-weight:700; font-family:'IBM Plex Mono',monospace;"><?= number_format($usdt, 2) ?></div>
              </div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:8px; height:8px; border-radius:50%; background:#f59e0b;"></div>
                <div style="font-size:13px; font-weight:600; color:var(--text-second);">BTC</div>
              </div>
              <div style="text-align:right;">
                <div style="font-size:13px; font-weight:700; font-family:'IBM Plex Mono',monospace;"><?= number_format($btc, 5) ?></div>
              </div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:8px; height:8px; border-radius:50%; background:#6366f1;"></div>
                <div style="font-size:13px; font-weight:600; color:var(--text-second);">ETH</div>
              </div>
              <div style="text-align:right;">
                <div style="font-size:13px; font-weight:700; font-family:'IBM Plex Mono',monospace;"><?= number_format($eth, 5) ?></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── BOTTOM ORDERS BAR ─────────────────────────────────────────────── -->
      <section class="bottom-bar">
        <div class="bottom-tabs">
          <div style="display:flex; align-items:center; gap:20px;">
            <button class="btm-tab active" data-tab="open-orders" onclick="showTab('open-orders')">Open Orders (2)</button>
            <button class="btm-tab"        data-tab="positions"   onclick="showTab('positions')">Positions</button>
            <button class="btm-tab"        data-tab="history"     onclick="showTab('history')">Order History</button>
            <button class="btm-tab"        data-tab="trades"      onclick="showTab('trades')">Trade History</button>
          </div>
          <button class="btm-cancel-all" onclick="cancelAllOrders()">Cancel All</button>
        </div>
        <div class="orders-table-wrap">

          <!-- Open Orders -->
          <div id="tab-open-orders" class="tab-content">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Time</th><th>Symbol</th><th>Side</th>
                  <th>Price</th><th>Amount</th><th>Filled</th>
                  <th>Status</th><th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody id="open-orders-body">
                <!-- Mock Data for Parity (Matches Image 2) -->
                <tr>
                  <td>2023-11-24 11:45:02</td>
                  <td><b>BTC/USDT</b></td>
                  <td class="side-buy">BUY / Limit</td>
                  <td>63,850.00</td>
                  <td>0.0500</td>
                  <td>0.00%</td>
                  <td><span class="status-badge status-open">Open</span></td>
                  <td style="text-align:right"><button class="action-btn" onclick="cancelOrder(1)">Cancel</button></td>
                </tr>
                <tr>
                  <td>2023-11-24 10:12:15</td>
                  <td><b>BTC/USDT</b></td>
                  <td class="side-sell">SELL / Stop</td>
                  <td>61,200.00</td>
                  <td>0.1200</td>
                  <td>0.00%</td>
                  <td><span class="status-badge status-active">Active</span></td>
                  <td style="text-align:right"><button class="action-btn" onclick="cancelOrder(2)">Cancel</button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Positions (New Tab) -->
          <div id="tab-positions" class="tab-content" style="display:none">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Time</th><th>Symbol</th><th>Side</th>
                  <th>Entry Price</th><th>Mark Price</th><th>Size</th>
                  <th>PnL (ROE%)</th><th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr><td colspan="8" class="empty-msg">No active positions</td></tr>
              </tbody>
            </table>
          </div>

          <!-- Order History -->
          <div id="tab-history" class="tab-content" style="display:none">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Time</th><th>Symbol</th><th>Side</th>
                  <th>Price</th><th>Amount</th><th>Filled</th>
                  <th>Status</th><th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody id="history-orders-body">
                <tr><td colspan="8" class="empty-msg">No order history</td></tr>
              </tbody>
            </table>
          </div>

          <!-- Trade History -->
          <div id="tab-trades" class="tab-content" style="display:none">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Time</th><th>Symbol</th><th>Side</th>
                  <th>Price</th><th>Amount</th><th>Fee</th><th style="text-align:right">Total</th>
                </tr>
              </thead>
              <tbody id="trades-list-body">
                <tr><td colspan="7" class="empty-msg">No trade history</td></tr>
              </tbody>
            </table>
          </div>

        </div>
      </section>

    </div><!-- /trading-body -->
  </main>
</div><!-- /app-layout -->

<!-- Status Footer (Institutional Grade Footer - Relocated for Visibility) -->
<footer class="institutional-status-bar">
  <div class="status-left">
    <div class="status-indicator">
      <div class="status-dot-glow"></div>
      <div class="status-dot"></div>
      <span class="status-text">CONNECTED</span>
    </div>
    <div class="status-metric">
      LATENCY: <span class="metric-val" id="lat-val">24ms</span>
    </div>
    <div class="status-metric">
      NETWORK: <span class="metric-val">STABLE</span>
    </div>
  </div>
  <div class="status-right">
    <div class="status-time" id="system-time"><?= date('Y-m-d H:i:s') ?> (UTC)</div>
    <div class="status-copyright">© <?= date('Y') ?> APEX MARKETS LLC • INSTITUTIONAL TERMINAL</div>
  </div>
</footer>

<?php include 'components/mobile_nav.php'; ?>

<!-- ═══════ MODALS ═══════════════════════════════════════════════════════════ -->
<!-- Deposit -->
<div class="modal-overlay hidden" id="deposit-modal">
  <div class="modal">
    <div class="modal-head">
      <div class="modal-title">Deposit Funds</div>
      <button class="modal-close" onclick="closeModal('deposit-modal')">×</button>
    </div>
    <form id="deposit-form">
      <div class="modal-field">
        <label class="modal-label">Asset</label>
        <select class="modal-select modal-input" id="dep-asset">
          <option value="USDT">USDT — Tether</option>
          <option value="BTC">BTC — Bitcoin</option>
          <option value="ETH">ETH — Ethereum</option>
        </select>
      </div>
      <div class="modal-field">
        <label class="modal-label">Amount</label>
        <input class="modal-input" type="number" id="dep-amount" placeholder="0.00" step="0.000001" min="0" required>
      </div>
      <div style="background:rgba(0,212,170,0.06);border:1px solid rgba(0,212,170,0.2);border-radius:8px;padding:10px;margin-bottom:14px;font-size:12px;color:var(--accent)">
        ℹ Demo mode: Funds are added instantly for testing purposes.
      </div>
      <button type="submit" class="modal-btn green">Confirm Deposit</button>
    </form>
  </div>
</div>

<!-- Withdraw -->
<div class="modal-overlay hidden" id="withdraw-modal">
  <div class="modal">
    <div class="modal-head">
      <div class="modal-title">Withdraw Funds</div>
      <button class="modal-close" onclick="closeModal('withdraw-modal')">×</button>
    </div>
    <form id="withdraw-form">
      <div class="modal-field">
        <label class="modal-label">Asset</label>
        <select class="modal-select modal-input" id="wd-asset">
          <option value="USDT">USDT — Tether</option>
          <option value="BTC">BTC — Bitcoin</option>
          <option value="ETH">ETH — Ethereum</option>
        </select>
      </div>
      <div class="modal-field">
        <label class="modal-label">Withdrawal Address</label>
        <input class="modal-input" type="text" id="wd-address" placeholder="0x... or bc1..." required>
      </div>
      <div class="modal-field">
        <label class="modal-label">Amount</label>
        <input class="modal-input" type="number" id="wd-amount" placeholder="0.00" step="0.000001" min="0" required>
      </div>
      <button type="submit" class="modal-btn red">Confirm Withdrawal</button>
    </form>
  </div>
</div>

<!-- ═══════ TOAST CONTAINER ═══════════════════════════════════════════════════ -->
<div id="toast-container"></div>

<!-- Close modal on overlay click -->
<script>
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.add('hidden'); });
});
</script>
<script src="assets/js/trading.js"></script>
</body>
</html>
