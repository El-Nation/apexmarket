<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="csrf" content="<?= $csrf ?? '' ?>">
  <title><?= $pageTitle ?? 'Trading Terminal' ?> — APEX MARKETS</title>
  
  <!-- Favicon -->
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rajdhani:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  
  <!-- Styles -->
  <link rel="stylesheet" href="assets/css/main.css">
  
  <!-- TradingView Script (Loaded only if needed) -->
  <?php if (isset($useTradingView) && $useTradingView): ?>
  <script src="https://s3.tradingview.com/tv.js" async></script>
  <?php endif; ?>
  
  <!-- Chart.js (Loaded only if needed) -->
  <?php if (isset($useChartJS) && $useChartJS): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <?php endif; ?>
</head>
<body>
