<?php
require_once __DIR__ . '/config/session.php';
requireGuest();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';
    $agree     = isset($_POST['agree']);

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'Username must be 3–30 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, and underscores.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$agree) {
        $error = 'You must agree to the Terms of Service.';
    } else {
        $db = getDB();
        // Check unique
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = 'Username or email is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->beginTransaction();
            try {
                $stmt = $db->prepare('INSERT INTO users (username, email, password, full_name) VALUES (?,?,?,?)');
                $stmt->execute([$username, $email, $hash, $full_name]);
                $userId = (int)$db->lastInsertId();

                // Give starter balance
                $db->prepare('INSERT INTO balances (user_id, asset, amount) VALUES (?,?,?)')
                   ->execute([$userId, 'USDT', 10000.00]);
                $db->prepare('INSERT INTO balances (user_id, asset, amount) VALUES (?,?,?)')
                   ->execute([$userId, 'BTC', 0.10000000]);
                $db->prepare('INSERT INTO balances (user_id, asset, amount) VALUES (?,?,?)')
                   ->execute([$userId, 'ETH', 1.00000000]);

                $db->commit();

                // Auto-login
                session_regenerate_id(true);
                $_SESSION['user_id']      = $userId;
                $_SESSION['user']         = [
                    'id'        => $userId,
                    'username'  => $username,
                    'email'     => $email,
                    'full_name' => $full_name,
                ];
                $_SESSION['last_refresh'] = time();
                header('Location: dashboard.php');
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — APEX MARKETS</title>
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth-bg"></div>
<div class="auth-wrap">
  <div class="auth-card" style="max-width:480px">
    <div class="auth-logo">
      <div class="auth-logo-icon">AM</div>
      <div class="auth-logo-text">APEX<span> MARKETS</span></div>
    </div>

    <h1 class="auth-heading">Create Account</h1>
    <p class="auth-sub">Start trading on the world's most advanced platform</p>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="regForm">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input class="form-input" type="text" name="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="trader123" required>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input class="form-input" type="text" name="full_name"
                 value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                 placeholder="John Doe">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input class="form-input" type="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password *</label>
        <div class="input-wrap">
          <input class="form-input" type="password" name="password" id="pwdInput"
                 placeholder="Min. 8 characters" oninput="checkStrength(this.value)"
                 autocomplete="new-password" required>
          <span class="input-icon" onclick="togglePwd('pwdInput')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </span>
        </div>
        <div class="strength-bar"><div class="strength-bar-fill" id="strengthBar"></div></div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <input class="form-input" type="password" name="confirm"
               placeholder="Repeat password" autocomplete="new-password" required>
      </div>

      <div class="checkbox-group">
        <input type="checkbox" id="agree" name="agree" <?= isset($_POST['agree']) ? 'checked' : '' ?>>
        <label for="agree">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
      </div>

      <button type="submit" class="btn-primary" id="regBtn">Create Account</button>
    </form>

    <div class="auth-footer">Already have an account? <a href="login.php">Sign In</a></div>
  </div>
</div>

<script>
function togglePwd(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
function checkStrength(val) {
  let score = 0;
  if (val.length >= 8)  score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const bar = document.getElementById('strengthBar');
  const colors = ['', '#ef4444','#f59e0b','#10b981','#00d4aa'];
  bar.style.width  = (score * 25) + '%';
  bar.style.background = colors[score] || '';
}
document.getElementById('regForm').addEventListener('submit', function() {
  const btn = document.getElementById('regBtn');
  btn.textContent = 'Creating account…';
  btn.classList.add('loading');
});
</script>
</body>
</html>
