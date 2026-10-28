<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = loginUser($email, $password);
    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Trivikratech Jewellery POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 460px; width: 100%; overflow: hidden;">
  <div style="padding: 36px 36px 24px; text-align: center; border-bottom: 1px solid var(--color-border);">
    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #0F766E, #14B8A6); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 26px; font-weight: 900; margin-bottom: 14px; box-shadow: 0 4px 14px rgba(15,118,110,0.3);">
      T
    </div>
    <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 6px;">Welcome Back</h2>
    <p style="font-size: 13.5px; color: #6B7280; margin: 0;">Sign in to your Jewellery POS & ERP portal</p>
  </div>

  <div style="padding: 32px 36px;">
    <?php if (!empty($error)): ?>
      <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" id="loginEmail" class="form-control" placeholder="name@jeweller.com" value="admin@jeweller.com" required>
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin: 0;">Password</label>
          <a href="forgot-password.php" style="font-size: 12px; color: var(--color-primary); font-weight: 600;">Forgot Password?</a>
        </div>
        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" value="admin" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; border-radius: 10px; margin-top: 8px;">
        <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
        <span>Sign In to POS</span>
      </button>
    </form>

    <!-- Quick 1-Click Demo Logins -->
    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed var(--color-border);">
      <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--color-text-3); text-align: center; margin-bottom: 12px; letter-spacing: 0.05em;">
        ⚡ Instant 1-Click Demo Access
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <button type="button" onclick="fillCredentials('admin@jeweller.com', 'admin')" class="btn btn-ghost btn-sm" style="background: #FAF5FF; border-color: #E9D5FF; color: #7E22CE; font-weight: 700; padding: 8px;">
          👑 Super Admin
        </button>
        <button type="button" onclick="fillCredentials('employee@jeweller.com', 'employee')" class="btn btn-ghost btn-sm" style="background: #EFF6FF; border-color: #BFDBFE; color: #1D4ED8; font-weight: 700; padding: 8px;">
          👤 Employee
        </button>
      </div>
    </div>
  </div>

  <div style="padding: 16px 36px 24px; text-align: center; background: var(--color-surface-2); border-top: 1px solid var(--color-border); font-size: 13px; color: #6B7280;">
    Don't have an account? <a href="signup.php" style="color: var(--color-primary); font-weight: 700;">Sign Up Free</a>
  </div>
</div>

<script>
  function fillCredentials(email, pass) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = pass;
    document.forms[0].submit();
  }
  lucide.createIcons();
</script>
</body>
</html>
