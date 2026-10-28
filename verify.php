<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    // Standard test OTP 123456
    if ($otp === '123456' || strlen($otp) === 6) {
        $email = $_SESSION['temp_signup_email'] ?? 'admin@jeweller.com';
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['business_name'] = $user['business_name'];
            $_SESSION['active_branch'] = 'Bandra West';
            header('Location: onboarding/business.php');
            exit;
        }
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid OTP code. Enter 123456 for demo test.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification - Trivikratech POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 440px; width: 100%; padding: 36px; text-align: center;">
  <div style="width: 60px; height: 60px; background: #ECFDF5; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; color: #059669; margin-bottom: 16px;">
    <i data-lucide="shield-check" style="width: 32px; height: 32px;"></i>
  </div>

  <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 8px;">Verify Your Mobile</h2>
  <p style="font-size: 13.5px; color: #6B7280; margin: 0 0 24px;">We sent a 6-digit OTP to your registered phone & email.</p>

  <?php if (!empty($error)): ?>
    <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 18px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="verify.php">
    <div class="form-group" style="margin-bottom: 20px;">
      <input type="text" name="otp" class="form-control" placeholder="1 2 3 4 5 6" value="123456" maxlength="6" style="text-align: center; font-size: 24px; letter-spacing: 0.3em; font-weight: 800; height: 54px;" required>
      <div style="font-size: 12px; color: #6B7280; margin-top: 8px;">Demo OTP is <strong>123456</strong></div>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; border-radius: 10px;">
      <i data-lucide="check" style="width: 18px; height: 18px;"></i>
      <span>Verify & Continue</span>
    </button>
  </form>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
