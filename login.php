<?php
require_once __DIR__ . '/config/session.php';
requireGuest();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user']          = [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'email'     => $user['email'],
                'full_name' => $user['full_name'],
            ];
            $_SESSION['last_refresh']  = time();
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — APEX MARKETS</title>
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth-bg"></div>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-icon">AM</div>
      <div class="auth-logo-text">APEX<span> MARKETS</span></div>
    </div>

    <div class="demo-badge">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Demo login: <strong>demo</strong> / <strong>Demo@1234</strong>
    </div>

    <h1 class="auth-heading">Welcome Back</h1>
    <p class="auth-sub">Sign in to your trading account</p>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
      <div class="form-group">
        <label class="form-label">Username or Email</label>
        <input class="form-input" type="text" name="login"
               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
               placeholder="Enter username or email" autocomplete="username" required>
      </div>

      <div class="form-group">
        <div class="form-row">
          <label class="form-label" style="margin:0">Password</label>
          <a href="#" class="form-link">Forgot password?</a>
        </div>
        <div class="input-wrap">
          <input class="form-input" type="password" name="password" id="pwdInput"
                 placeholder="Enter your password" autocomplete="current-password" required>
          <span class="input-icon" onclick="togglePwd()">
            <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </span>
        </div>
      </div>

      <button type="submit" class="btn-primary" id="loginBtn">
        Sign In
      </button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="register.php">Create Account</a>
    </div>
  </div>
</div>

<script>
function togglePwd() {
  const inp = document.getElementById('pwdInput');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  btn.textContent = 'Signing in…';
  btn.classList.add('loading');
});
</script>
</body>
</html>
