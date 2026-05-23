<nav class="top-nav">
  <button class="hamburger" onclick="toggleSidebar()" aria-label="Menu">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6"  x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <div class="nav-brand">
    <div class="nav-brand-icon">AM</div>
    <div class="nav-brand-text">APEX <span style="color:var(--accent)">MARKETS</span></div>
  </div>
  <div class="nav-tabs">
    <?php
    $current = basename($_SERVER['PHP_SELF']);
    $tabs = [
      ['label'=>'Trade',     'href'=>'dashboard.php'],
      ['label'=>'Markets',   'href'=>'markets.php'],
      ['label'=>'Portfolio', 'href'=>'portfolio.php'],
      ['label'=>'History',   'href'=>'history.php'],
      ['label'=>'Wallet',    'href'=>'wallet.php'],
    ];
    foreach($tabs as $tab): ?>
    <a href="<?= $tab['href'] ?>" class="nav-tab <?= ($current === $tab['href']) ? 'active' : '' ?>"><?= $tab['label'] ?></a>
    <?php endforeach; ?>
  </div>
  <div class="nav-right" style="gap:16px; display:flex; align-items:center;">
    <div style="display:flex; align-items:center; gap:12px; margin-right:8px; border-right:1px solid var(--border); padding-right:16px;">
      <button class="nav-icon-btn" title="Notifications">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
      </button>
      <button class="nav-icon-btn" title="Settings">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
      </button>
      <button class="nav-icon-btn" title="Help">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
      </button>
    </div>
    
    <button style="padding:10px 24px; font-size:14px; font-weight:700; background:#3b82f6; border-radius:8px; border:none; color:white; cursor:pointer;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">Connect Wallet</button>

    <div class="nav-user" onclick="window.location='wallet.php'" style="margin-left:8px; display:flex; align-items:center; gap:10px; cursor:pointer;">
      <div class="nav-avatar" style="background:var(--accent-glow); color:var(--accent); border:1px solid var(--accent); width:32px; height:32px; font-size:12px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:700;"><?= strtoupper(substr($user['username'] ?? 'U', 0, 2)) ?></div>
      <span class="nav-username" style="font-weight:600; color:var(--text-primary); font-size:14px;"><?= htmlspecialchars($user['username'] ?? 'User') ?></span>
    </div>
  </div>
</nav>
