<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessName = trim($_POST['business_name'] ?? '');
    $ownerName = trim($_POST['owner_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($businessName) || empty($ownerName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $db = getDBConnection();
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $db->prepare("INSERT INTO users (name, email, password, mobile, business_name, role, branch) VALUES (?, ?, ?, ?, ?, 'Super Admin', 'Bandra West')");
                $insertStmt->execute([$ownerName, $email, $hashed, $mobile, $businessName]);
                
                $_SESSION['temp_signup_email'] = $email;
                header('Location: verify.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - Trivikratech Jewellery POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 500px; width: 100%; overflow: hidden;">
  <div style="padding: 32px 36px 20px; text-align: center; border-bottom: 1px solid var(--color-border);">
    <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #0F766E, #14B8A6); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; font-weight: 900; margin-bottom: 12px;">
      T
    </div>
    <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 6px;">Create Your Store Account</h2>
    <p style="font-size: 13.5px; color: #6B7280; margin: 0;">14-day free trial. No credit card required.</p>
  </div>

  <div style="padding: 28px 36px;">
    <?php if (!empty($error)): ?>
      <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="signup.php">
      <div class="form-group">
        <label class="form-label">Jewellery Business / Store Name</label>
        <input type="text" name="business_name" class="form-control" placeholder="e.g. Royal Gold Jewellers" required>
      </div>

      <div class="form-group">
        <label class="form-label">Owner Full Name</label>
        <input type="text" name="owner_name" class="form-control" placeholder="e.g. Ajinkya Patil" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="admin@store.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Mobile Number</label>
          <input type="text" name="mobile" class="form-control" placeholder="9876543210" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Create Password</label>
        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; border-radius: 10px; margin-top: 8px;">
        <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
        <span>Get Started & Setup Store</span>
      </button>
    </form>
  </div>

  <div style="padding: 16px 36px 24px; text-align: center; background: var(--color-surface-2); border-top: 1px solid var(--color-border); font-size: 13px; color: #6B7280;">
    Already have an account? <a href="login.php" style="color: var(--color-primary); font-weight: 700;">Sign In</a>
  </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
