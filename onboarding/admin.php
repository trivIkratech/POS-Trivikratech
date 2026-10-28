<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: welcome.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Step 4: Admin Account - Trivikratech POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 500px; width: 100%; padding: 36px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <span style="font-size: 12px; font-weight: 800; color: var(--color-primary); text-transform: uppercase;">Step 4 of 5</span>
    <span style="font-size: 12px; color: #6B7280;">Super Admin Account</span>
  </div>

  <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 6px;">Super Admin Credentials</h2>
  <p style="font-size: 13.5px; color: #6B7280; margin: 0 0 24px;">Master account holding vault access and audit authority</p>

  <form method="POST" action="admin.php">
    <div class="form-group">
      <label class="form-label">Administrator Full Name</label>
      <input type="text" name="admin_name" class="form-control" value="<?= htmlspecialchars($_SESSION['user_name'] ?? 'Ajinkya Patil') ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label">Master Admin Email</label>
      <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($_SESSION['user_email'] ?? 'admin@jeweller.com') ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label">Admin Security Role</label>
      <input type="text" class="form-control" value="Super Admin (Full System Access & Bullion Rates)" readonly style="background: var(--color-surface-2); font-weight: 700; color: #7E22CE;">
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; border-radius: 10px; margin-top: 10px;">
      <span>Complete Setup & Launch</span>
      <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
    </button>
  </form>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
