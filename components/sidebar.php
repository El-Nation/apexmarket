<aside class="sidebar">
  <div style="padding:24px; border-bottom:1px solid var(--border); margin-bottom:8px;">
    <div style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:1.5px; margin-bottom:4px; font-weight:700;">Trading</div>
    <div style="font-size:14px; color:#4a90e2; font-weight:700; font-family:'Rajdhani',sans-serif;">Institutional Grade</div>
  </div>
  <nav class="sidebar-nav">
    <?php
    $currentFile = basename($_SERVER['PHP_SELF']);
    $sideItems = [
      ['icon'=>'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z', 'label'=>'Terminal',  'href'=>'dashboard.php', 'match'=>'dashboard.php'],
      ['icon'=>'M9 17H7A5 5 0 0 1 7 7h2M15 7h2a5 5 0 1 1 0 10h-2M8 12h8', 'label'=>'Exchange',  'href'=>'dashboard.php', 'match'=>'#'],
      ['icon'=>'M22 12h-4l-3 9L9 3l-3 9H2',                             'label'=>'Futures',   'href'=>'dashboard.php', 'match'=>'#'],
      ['icon'=>'M12 20V10M18 20V4M6 20V14',                              'label'=>'Markets',   'href'=>'markets.php',   'match'=>'markets.php'],
      ['icon'=>'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z','label'=>'Assets','href'=>'wallet.php', 'match'=>'wallet.php'],
    ];
    foreach ($sideItems as $item): 
      $isActive = ($currentFile === $item['match']);
    ?>
    <a href="<?= $item['href'] ?>" class="sidebar-item <?= $isActive ? 'active' : '' ?>">
      <span class="sidebar-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="<?= $item['icon'] ?>"/>
        </svg>
      </span>
      <span class="sidebar-label"><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>
  <div style="padding:16px; border-top:1px solid var(--border);">
    <a href="settings.php" class="sidebar-item" style="padding:12px 0; text-decoration:none;">
      <span class="sidebar-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
      </span>
      <span class="sidebar-label">Settings</span>
    </a>
  </div>
</aside>
