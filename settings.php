<?php
require_once __DIR__ . '/config/session.php';
requireLogin();

$user = getCurrentUser();
$userId = getUserId();
?>
<?php
$pageTitle = 'Institutional Settings';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="app-layout">
  <?php include 'components/sidebar.php'; ?>
  <main class="main-area" style="overflow-y:auto; padding-bottom: 60px;">
    <div class="port-page">
      <div class="page-title">Institutional Settings</div>

      <div class="grid-2">
        <div class="chart-card">
          <div class="cc-title">Profile Configuration</div>
          <div class="form-field">
            <div class="field-label">Username</div>
            <input class="field-input" type="text" value="<?= htmlspecialchars($user['username']) ?>" readonly>
          </div>
          <div class="form-field">
            <div class="field-label">Email Address</div>
            <input class="field-input" type="text" value="<?= htmlspecialchars($user['email'] ?? 'client@apex-markets.com') ?>" readonly>
          </div>
          <div class="form-field">
            <div class="field-label">Account Tier</div>
            <div style="padding:12px; background:rgba(0,242,182,0.1); border:1px solid #00f2b6; border-radius:8px; color:#00f2b6; font-weight:700; font-family:'Rajdhani',sans-serif;">
              INSTITUTIONAL • PRO
            </div>
          </div>
        </div>

        <div class="chart-card">
          <div class="cc-title">Security & API</div>
          <div class="form-field">
            <div class="field-label">2FA Status</div>
            <div style="font-size:13px; color:var(--green); font-weight:700;">ENABLED</div>
          </div>
          <div class="form-field">
            <div class="field-label">API Access</div>
            <div style="font-size:13px; color:var(--text-muted);">Restricted to whitelisted IPs</div>
          </div>
          <button class="btn-buy buy-side" style="margin-top:20px;">Manage Sessions</button>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
