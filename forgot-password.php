<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    
    if (!empty($email) && !empty($newPassword)) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                $update->execute([$hash, $email]);
                $message = 'Password successfully updated! You can now log in.';
            } else {
                $error = 'Email not found in our records.';
            }
        } catch (Exception $e) {
            $error = 'Error resetting password: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - Trivikratech POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 440px; width: 100%; padding: 36px;">
  <div style="text-align: center; margin-bottom: 24px;">
    <div style="width: 56px; height: 56px; background: #FEF3C7; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; color: #D97706; margin-bottom: 12px;">
      <i data-lucide="key" style="width: 28px; height: 28px;"></i>
    </div>
    <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 6px;">Reset Password</h2>
    <p style="font-size: 13.5px; color: #6B7280; margin: 0;">Enter your account email and new password</p>
  </div>

  <?php if (!empty($message)): ?>
    <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 18px;">
      <?= htmlspecialchars($message) ?>
      <div style="margin-top: 8px;"><a href="login.php" style="color: var(--color-primary); font-weight: 800; text-decoration: underline;">Click here to Login</a></div>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 18px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="forgot-password.php">
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="admin@jeweller.com" value="admin@jeweller.com" required>
    </div>

    <div class="form-group">
      <label class="form-label">New Password</label>
      <input type="password" name="new_password" class="form-control" placeholder="New password" required>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; border-radius: 10px; margin-top: 8px;">
      <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
      <span>Reset Password</span>
    </button>
  </form>

  <div style="margin-top: 20px; text-align: center; font-size: 13px;">
    <a href="login.php" style="color: var(--color-primary); font-weight: 700;">Back to Login</a>
  </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
