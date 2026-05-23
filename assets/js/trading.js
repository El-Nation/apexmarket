/* ═══════════════════════════════════════════════════════════════════
   APEX MARKETS — Trading Engine JS
   ═══════════════════════════════════════════════════════════════════ */

'use strict';

// ─── Global State ─────────────────────────────────────────────────────────────
const AM = {
  symbol:       'BTCUSDT',
  displaySymbol:'BTC/USDT',
  side:         'buy',    // 'buy' | 'sell'
  orderType:    'limit',  // 'limit' | 'market' | 'stop'
  chart:        null,
  priceInterval:null,
  obInterval:   null,
  tradesInterval:null,
  lastPrice:    0,
};

// ─── Toast Notifications ───────────────────────────────────────────────────────
function toast(msg, type = 'info', duration = 4000) {
  const container = document.getElementById('toast-container');
  const icons = {
    success: '✓', error: '✕', info: 'ℹ',
  };
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `
    <span style="font-size:16px;font-weight:700">${icons[type] || icons.info}</span>
    <span class="toast-msg">${msg}</span>
    <span class="toast-close" onclick="this.parentElement.remove()">×</span>
  `;
  container.appendChild(el);
  setTimeout(() => el.remove(), duration);
}

// ─── TradingView Chart ─────────────────────────────────────────────────────────
function initChart(interval = '1') {
  if (typeof TradingView === 'undefined') return;

  // Remove existing widget
  const container = document.getElementById('tradingview_chart');
  container.innerHTML = '';

  AM.chart = new TradingView.widget({
    autosize:          true,
    symbol:            'BINANCE:' + AM.symbol,
    interval:          interval,
    timezone:          'Etc/UTC',
    theme:             'dark',
    style:             '1',   // candlestick
    locale:            'en',
    toolbar_bg:        '#0d1421',
    gridColor:         'rgba(26,39,64,0.5)',
    enable_publishing: false,
    hide_top_toolbar:  false,
    hide_legend:       false,
    save_image:        false,
    withdateranges:    true,
    container_id:      'tradingview_chart',
    overrides: {
      'paneProperties.background':          '#030509',
      'paneProperties.backgroundType':      'solid',
      'paneProperties.vertGridProperties.color': 'rgba(30, 45, 69, 0.4)',
      'paneProperties.horzGridProperties.color': 'rgba(30, 45, 69, 0.4)',
      'scalesProperties.textColor':         '#94a3b8',
      'candleStyle.upColor':               '#10b981',
      'candleStyle.downColor':             '#ef4444',
      'candleStyle.wickUpColor':           '#10b981',
      'candleStyle.wickDownColor':         '#ef4444',
      'candleStyle.borderUpColor':         '#10b981',
      'candleStyle.borderDownColor':       '#ef4444',
    }
  });
}

// Timeframe button handler
function setTimeframe(tf, el) {
  document.querySelectorAll('.tf-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  // Map our labels to TradingView intervals
  const map = { '1m':'1','5m':'5','15m':'15','1h':'60','4h':'240','1D':'D' };
  initChart(map[tf] || '1');
}

// ─── Live Price Feed (Binance Public API) ──────────────────────────────────────
async function fetchTicker() {
  try {
    const res  = await fetch(`https://api.binance.com/api/v3/ticker/24hr?symbol=${AM.symbol}`);
    const data = await res.json();
    if (!data.lastPrice) throw new Error('Empty data');

    const price  = parseFloat(data.lastPrice);
    const change = parseFloat(data.priceChangePercent);
    const high   = parseFloat(data.highPrice);
    const low    = parseFloat(data.lowPrice);
    const vol    = parseFloat(data.volume);
    const qturn  = parseFloat(data.quoteVolume);

    AM.lastPrice = price;

    updateTickerUI(price, change, high, low, vol, qturn, data.priceChange);
  } catch (e) {
    console.warn('Ticker fetch failed, using fallback:', e.message);
    // Fallback Mock Data for Ticker
    const mockPrice = 64281.40;
    AM.lastPrice = mockPrice;
    updateTickerUI(mockPrice, 2.14, 65102.00, 62840.15, 12492.51, 781400000, 1240.12);
  }
}

function updateTickerUI(price, change, high, low, vol, turn, priceChange) {
    safeSet('ticker-price', formatPrice(price));
    const changeEl = document.getElementById('ticker-change');
    if (changeEl) {
      changeEl.textContent = `${change >= 0 ? '+' : ''}$${formatPrice(Math.abs(parseFloat(priceChange)))} (${change >= 0 ? '+' : ''}${change.toFixed(2)}%)`;
      changeEl.className   = 'ticker-change' + (change < 0 ? ' neg' : '');
    }
    safeSet('ticker-high',   formatPrice(high));
    safeSet('ticker-low',    formatPrice(low));
    safeSet('ticker-vol',    formatNum(vol));
    safeSet('ticker-turn',   formatLargeNum(turn));

    safeSet('ob-mid-price', formatPrice(price));
    const priceInput = document.getElementById('price-input');
    if (priceInput && priceInput.value === '' && AM.lastPrice > 0) {
      priceInput.value = formatPrice(price);
    }
    updateFeeEstimate();
}

// ─── Order Book (Binance Public API) ──────────────────────────────────────────
async function fetchOrderBook() {
  try {
    const res  = await fetch(`https://api.binance.com/api/v3/depth?symbol=${AM.symbol}&limit=10`);
    const data = await res.json();
    if (!data.asks || !data.bids) throw new Error('Invalid OB data');

    renderOrderBook(data.asks, data.bids);
  } catch (e) {
    console.warn('OrderBook fetch failed, using fallback:', e.message);
    const p = AM.lastPrice || 64281;
    const mockAsks = [[p+5, 0.14], [p+4, 1.25], [p+2, 0.02]];
    const mockBids = [[p-1, 0.45], [p-3, 2.14], [p-5, 0.89]];
    renderOrderBook(mockAsks, mockBids);
  }
}

function renderOrderBook(asks, bids) {
  const sellsEl = document.getElementById('ob-sells');
  const buysEl  = document.getElementById('ob-buys');
  if (!sellsEl || !buysEl) return;

  // Calculate max totals for bar width
  const askTotals = asks.map((a, i) => asks.slice(0,i+1).reduce((s,[,v]) => s+parseFloat(v), 0));
  const bidTotals = bids.map((b, i) => bids.slice(0,i+1).reduce((s,[,v]) => s+parseFloat(v), 0));
  const maxAsk    = Math.max(...askTotals) || 1;
  const maxBid    = Math.max(...bidTotals) || 1;

  sellsEl.innerHTML = asks.reverse().map(([p, v], i) => {
    const total = parseFloat(p) * parseFloat(v);
    const pct   = (askTotals[asks.length - 1 - i] / maxAsk * 100).toFixed(1);
    return `<div class="ob-row sell" onclick="setPrice(${parseFloat(p).toFixed(2)})">
      <span class="ob-price">${formatPrice(parseFloat(p))}</span>
      <span>${parseFloat(v).toFixed(4)}</span>
      <span>${formatNum(total)}</span>
      <div class="ob-row-bar sell" style="width:${pct}%"></div>
    </div>`;
  }).join('');

  buysEl.innerHTML = bids.map(([p, v], i) => {
    const total = parseFloat(p) * parseFloat(v);
    const pct   = (bidTotals[i] / maxBid * 100).toFixed(1);
    return `<div class="ob-row buy" onclick="setPrice(${parseFloat(p).toFixed(2)})">
      <span class="ob-price">${formatPrice(parseFloat(p))}</span>
      <span>${parseFloat(v).toFixed(4)}</span>
      <span>${formatNum(total)}</span>
      <div class="ob-row-bar buy" style="width:${pct}%"></div>
    </div>`;
  }).join('');
}

function setPrice(price) {
  const el = document.getElementById('price-input');
  if (el) { el.value = price; updateFeeEstimate(); }
}

// ─── Recent Trades (Binance Public API) ───────────────────────────────────────
async function fetchTrades() {
  try {
    const res   = await fetch(`https://api.binance.com/api/v3/trades?symbol=${AM.symbol}&limit=20`);
    const data  = await res.json();
    if (!Array.isArray(data)) throw new Error('Invalid trades data');

    updateTradesUI(data.reverse());
  } catch (e) {
    console.warn('Trades fetch failed, using fallback:', e.message);
    const p = AM.lastPrice || 64281;
    const t = Date.now();
    const mock = [
      {price: p, qty: 0.0051, time: t, isBuyerMaker:false},
      {price: p-0.1, qty: 1.25, time: t-5000, isBuyerMaker:true},
      {price: p+0.1, qty: 0.4215, time: t-10000, isBuyerMaker:false}
    ];
    updateTradesUI(mock);
  }
}

function updateTradesUI(trades) {
    const el = document.getElementById('trades-list');
    if (!el) return;

    el.innerHTML = trades.map(t => {
      const time = new Date(t.time).toLocaleTimeString('en-US', { hour12:false });
      return `<div class="trade-row">
        <span class="t-price ${t.isBuyerMaker ? 'sell' : 'buy'}">${formatPrice(parseFloat(t.price))}</span>
        <span>${parseFloat(t.qty).toFixed(4)}</span>
        <span class="t-time">${time}</span>
      </div>`;
    }).join('');
}

// ─── Order Form ────────────────────────────────────────────────────────────────
function setSide(side) {
  AM.side = side;
  document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll(`.form-tab.${side}`).forEach(t => t.classList.add('active'));
  const btn = document.getElementById('place-order-btn');
  if (btn) {
    btn.className   = `btn-buy ${side}-side`;
    btn.textContent = `${side === 'buy' ? 'BUY' : 'SELL'} ${AM.displaySymbol.split('/')[0]}`;
  }
  updateBalance();
}

function setOrderType(type, el) {
  AM.orderType = type;
  document.querySelectorAll('.ot-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');

  const priceField = document.getElementById('price-field');
  const stopField  = document.getElementById('stop-field');
  if (priceField) priceField.style.display = (type === 'market') ? 'none' : '';
  if (stopField)  stopField.style.display  = (type === 'stop')   ? '' : 'none';
  updateFeeEstimate();
}

function setPct(pct) {
  const availEl  = document.getElementById('avail-val');
  const amtInput = document.getElementById('amount-input');
  if (!availEl || !amtInput) return;

  const avail = parseFloat(availEl.dataset.raw || 0);
  if (AM.side === 'buy') {
    const price = parseFloat(document.getElementById('price-input')?.value || AM.lastPrice);
    if (price > 0) amtInput.value = ((avail * pct / 100) / price).toFixed(6);
  } else {
    amtInput.value = (avail * pct / 100).toFixed(6);
  }
  updateFeeEstimate();
}

function updateFeeEstimate() {
  const price  = parseFloat(document.getElementById('price-input')?.value || AM.lastPrice || 0);
  const amount = parseFloat(document.getElementById('amount-input')?.value || 0);
  const fee    = price * amount * 0.001;
  const feeEl  = document.getElementById('fee-val');
  if (feeEl) feeEl.textContent = isNaN(fee) ? '0.00 USDT' : fee.toFixed(4) + ' USDT';
}

function updateBalance() {
  // Balance is refreshed from PHP on page load; stored in data attributes
  const el = document.getElementById('avail-val');
  if (!el) return;
  const asset = AM.side === 'buy' ? 'USDT' : 'BTC';
  const bal   = parseFloat(el.dataset[asset.toLowerCase()] || 0);
  el.textContent = formatNum(bal) + ' ' + asset;
  el.dataset.raw  = bal;
}

async function placeOrder(e) {
  e.preventDefault();
  const btn = document.getElementById('place-order-btn');
  const price  = document.getElementById('price-input')?.value || '0';
  const amount = document.getElementById('amount-input')?.value || '0';
  const stop   = document.getElementById('stop-input')?.value  || '0';

  if (parseFloat(amount) <= 0) { toast('Enter a valid amount', 'error'); return; }
  if (AM.orderType === 'limit' && parseFloat(price) <= 0) { toast('Enter a valid price', 'error'); return; }

  btn.disabled    = true;
  btn.textContent = 'Placing…';

  try {
    const form = new FormData();
    form.append('side',       AM.side);
    form.append('order_type', AM.orderType);
    form.append('price',      price);
    form.append('stop_price', stop);
    form.append('amount',     amount);
    form.append('symbol',     AM.displaySymbol);
    form.append('csrf',       document.querySelector('meta[name=csrf]').content);

    const res  = await fetch('api/place_order.php', { method: 'POST', body: form });
    const data = await res.json();

    if (data.success) {
      toast(`${AM.side.toUpperCase()} order placed: ${amount} BTC @ ${price}`, 'success');
      document.getElementById('amount-input').value = '';
      loadOrders();
      loadBalances();
    } else {
      toast(data.error || 'Order failed', 'error');
    }
  } catch (err) {
    toast('Network error. Please retry.', 'error');
  }

  btn.disabled    = false;
  setSide(AM.side);
}

// ─── Open Orders ───────────────────────────────────────────────────────────────
async function loadOrders() {
  try {
    const res  = await fetch('api/get_orders.php?status=open');
    const data = await res.json();
    if (!data.success) return;

    const openTbody  = document.getElementById('open-orders-body');
    const histTbody  = document.getElementById('history-orders-body');
    const countBadge = document.getElementById('open-count');
    const orders     = data.orders || [];

    if (countBadge) countBadge.textContent = orders.filter(o => ['open','active','partial'].includes(o.status)).length || '';

    if (openTbody) {
      const open = orders.filter(o => ['open','active','partial'].includes(o.status));
      openTbody.innerHTML = open.length ? open.map(o => `
        <tr>
          <td>${formatDate(o.created_at)}</td>
          <td><b>${o.symbol}</b></td>
          <td class="${o.side === 'buy' ? 'side-buy' : 'side-sell'}">${o.side.toUpperCase()} / ${o.type.charAt(0).toUpperCase()+o.type.slice(1)}</td>
          <td>${formatPrice(o.price)}</td>
          <td>${parseFloat(o.amount).toFixed(4)}</td>
          <td>${parseFloat(o.filled || 0).toFixed(2)}%</td>
          <td><span class="status-badge status-${o.status}">${o.status.charAt(0).toUpperCase()+o.status.slice(1)}</span></td>
          <td style="text-align:right"><button class="action-btn" onclick="cancelOrder(${o.id})">Cancel</button></td>
        </tr>
      `).join('') : '<tr><td colspan="8" class="empty-msg">No open orders</td></tr>';
    }
    if (histTbody) {
      const hist = orders.filter(o => ['filled','cancelled'].includes(o.status));
      histTbody.innerHTML = hist.length ? hist.map(o => `
        <tr>
          <td>${formatDate(o.created_at)}</td>
          <td><b>${o.symbol}</b></td>
          <td class="${o.side === 'buy' ? 'side-buy' : 'side-sell'}">${o.side.toUpperCase()} / ${o.type.charAt(0).toUpperCase()+o.type.slice(1)}</td>
          <td>${formatPrice(o.price)}</td>
          <td>${parseFloat(o.amount).toFixed(4)}</td>
          <td>${parseFloat(o.filled || 0).toFixed(2)}%</td>
          <td><span class="status-badge status-${o.status}">${o.status.charAt(0).toUpperCase()+o.status.slice(1)}</span></td>
          <td style="text-align:right">—</td>
        </tr>
      `).join('') : '<tr><td colspan="8" class="empty-msg">No order history</td></tr>';
    }
  } catch (e) { console.warn('Load orders failed:', e); }
}

async function cancelOrder(id) {
  try {
    const form = new FormData();
    form.append('order_id', id);
    form.append('csrf', document.querySelector('meta[name=csrf]').content);
    const res  = await fetch('api/cancel_order.php', { method:'POST', body:form });
    const data = await res.json();
    if (data.success) { toast('Order cancelled', 'success'); loadOrders(); loadBalances(); }
    else toast(data.error || 'Failed to cancel', 'error');
  } catch(e) { toast('Network error', 'error'); }
}

async function cancelAllOrders() {
  if (!confirm('Cancel all open orders?')) return;
  try {
    const form = new FormData();
    form.append('all',  1);
    form.append('csrf', document.querySelector('meta[name=csrf]').content);
    const res  = await fetch('api/cancel_order.php', { method:'POST', body:form });
    const data = await res.json();
    if (data.success) { toast(`Cancelled ${data.count} orders`, 'success'); loadOrders(); loadBalances(); }
  } catch(e) { toast('Network error', 'error'); }
}

// ─── Balance ───────────────────────────────────────────────────────────────────
async function loadBalances() {
  try {
    const res  = await fetch('api/get_balance.php');
    const data = await res.json();
    if (!data.success) return;

    const balances = data.balances;
    const usdtFree = balances.USDT?.free  || 0;
    const btcFree  = balances.BTC?.free   || 0;
    const ethFree  = balances.ETH?.free   || 0;

    // Update wallet equity (approx USD)
    const equity = usdtFree + btcFree * AM.lastPrice + ethFree * (AM.lastPrice / 15);
    safeSet('wallet-equity', '$' + formatNum(equity));
    safeSet('wallet-sub',    '≈ ' + btcFree.toFixed(4) + ' BTC');

    // Update asset rows
    safeSet('bal-usdt', formatNum(usdtFree));
    safeSet('bal-btc',  btcFree.toFixed(6));
    safeSet('bal-eth',  ethFree.toFixed(6));

    safeSet('bal-usdt-usd', '$' + formatNum(usdtFree));
    safeSet('bal-btc-usd',  '$' + formatNum(btcFree * AM.lastPrice));
    safeSet('bal-eth-usd',  '$' + formatNum(ethFree * (AM.lastPrice / 15)));

    // Update form available
    const availEl = document.getElementById('avail-val');
    if (availEl) {
      availEl.dataset.usdt = usdtFree;
      availEl.dataset.btc  = btcFree;
      availEl.dataset.eth  = ethFree;
      updateBalance();
    }
  } catch(e) { console.warn('Balance fetch failed:', e); }
}

// ─── Deposit / Withdraw Modals ─────────────────────────────────────────────────
function openDeposit() {
  document.getElementById('deposit-modal').classList.remove('hidden');
}
function openWithdraw() {
  document.getElementById('withdraw-modal').classList.remove('hidden');
}
function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
}

async function submitDeposit(e) {
  e.preventDefault();
  const asset  = document.getElementById('dep-asset').value;
  const amount = document.getElementById('dep-amount').value;
  const form   = new FormData();
  form.append('asset',  asset);
  form.append('amount', amount);
  form.append('csrf',   document.querySelector('meta[name=csrf]').content);

  try {
    const res  = await fetch('api/deposit.php', { method:'POST', body:form });
    const data = await res.json();
    if (data.success) {
      toast(`Deposited ${amount} ${asset} successfully`, 'success');
      closeModal('deposit-modal');
      loadBalances();
    } else toast(data.error || 'Deposit failed', 'error');
  } catch(e) { toast('Network error', 'error'); }
}

async function submitWithdraw(e) {
  e.preventDefault();
  const asset    = document.getElementById('wd-asset').value;
  const amount   = document.getElementById('wd-amount').value;
  const address  = document.getElementById('wd-address').value;
  const form     = new FormData();
  form.append('asset',   asset);
  form.append('amount',  amount);
  form.append('address', address);
  form.append('csrf',    document.querySelector('meta[name=csrf]').content);

  try {
    const res  = await fetch('api/withdraw.php', { method:'POST', body:form });
    const data = await res.json();
    if (data.success) {
      toast(`Withdrawal of ${amount} ${asset} submitted`, 'success');
      closeModal('withdraw-modal');
      loadBalances();
    } else toast(data.error || 'Withdrawal failed', 'error');
  } catch(e) { toast('Network error', 'error'); }
}

// ─── Bottom Tabs ──────────────────────────────────────────────────────────────
function showTab(tab) {
  document.querySelectorAll('.btm-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
  document.querySelector(`.btm-tab[data-tab="${tab}"]`).classList.add('active');
  const el = document.getElementById('tab-' + tab);
  if (el) el.style.display = '';
}

// ─── Mobile Sidebar ───────────────────────────────────────────────────────────
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
}
document.addEventListener('click', e => {
  const sb = document.querySelector('.sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target) && !e.target.closest('.hamburger')) {
    sb.classList.remove('open');
  }
});

// ─── Helpers ───────────────────────────────────────────────────────────────────
function safeSet(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}
function formatPrice(n) {
  return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function formatNum(n) {
  if (n >= 1e9) return (n/1e9).toFixed(2) + 'B';
  if (n >= 1e6) return (n/1e6).toFixed(2) + 'M';
  if (n >= 1e3) return n.toLocaleString('en-US', { maximumFractionDigits: 2 });
  return n.toFixed(2);
}
function formatLargeNum(n) {
  if (n >= 1e9) return (n/1e9).toFixed(1) + 'B';
  if (n >= 1e6) return (n/1e6).toFixed(1) + 'M';
  return n.toFixed(0);
}
function formatDate(dt) {
  return new Date(dt).toLocaleString('en-US', { month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', hour12:false });
}

// ─── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Chart (TradingView loads async)
  const checkTV = setInterval(() => {
    if (typeof TradingView !== 'undefined') {
      clearInterval(checkTV);
      initChart('1');
    }
  }, 200);

  // Set initial form state
  setSide('buy');
  showTab('open-orders');

  // Live feeds
  fetchTicker();
  fetchOrderBook();
  fetchTrades();

  AM.priceInterval  = setInterval(fetchTicker,    5000);
  AM.obInterval     = setInterval(fetchOrderBook, 3000);
  AM.tradesInterval = setInterval(fetchTrades,    4000);

  // Load user data
  loadOrders();
  loadBalances();
  setInterval(loadOrders, 30000);
  setInterval(loadBalances, 15000);

  // Status Bar Live Updates
  setInterval(() => {
    const timeEl = document.getElementById('system-time');
    if (timeEl) {
      const now = new Date();
      timeEl.textContent = now.toISOString().replace('T', ' ').split('.')[0] + ' (UTC)';
    }
    const latEl = document.getElementById('lat-val');
    if (latEl) {
      const jitter = Math.floor(Math.random() * 5) + 22;
      latEl.textContent = jitter + 'ms';
    }
  }, 1000);

  // Order form events
  const form = document.getElementById('order-form');
  if (form) form.addEventListener('submit', placeOrder);

  const amtInput   = document.getElementById('amount-input');
  const priceInput = document.getElementById('price-input');
  if (amtInput)   amtInput.addEventListener('input',   updateFeeEstimate);
  if (priceInput) priceInput.addEventListener('input',  updateFeeEstimate);

  // Deposit/withdraw forms
  const depForm = document.getElementById('deposit-form');
  const wdForm  = document.getElementById('withdraw-form');
  if (depForm) depForm.addEventListener('submit', submitDeposit);
  if (wdForm)  wdForm.addEventListener('submit',  submitWithdraw);
});
