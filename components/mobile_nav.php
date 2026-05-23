<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav">
  <?php
  $currentPage = basename($_SERVER['PHP_SELF']);
  $mobileNav = [
    ['icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1', 'label'=>'Trade', 'href'=>'dashboard.php'],
    ['icon'=>'M12 20V10M18 20V4M6 20V14', 'label'=>'Markets', 'href'=>'markets.php'],
    ['icon'=>'M16 8v8m-4-5v5m-4-2v2m-2 4h16a2 2 0 002-2V6a2 2 0 00-2-2H4a2 2 0 00-2 2v12a2 2 0 002 2z', 'label'=>'Portfolio', 'href'=>'portfolio.php'],
    ['icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1', 'label'=>'Wallet', 'href'=>'wallet.php'],
  ];
  foreach ($mobileNav as $item):
    $isActive = ($currentPage === $item['href']);
  ?>
  <a href="<?= $item['href'] ?>" class="mob-nav-item <?= $isActive ? 'active' : '' ?>">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="<?= $item['icon'] ?>"/>
    </svg>
    <span><?= $item['label'] ?></span>
  </a>
  <?php endforeach; ?>
</nav>
