<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome to Trivikratech POS!</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 28px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 520px; width: 100%; padding: 44px; text-align: center;">
  <div style="width: 72px; height: 72px; background: linear-gradient(135deg, #059669, #10B981); border-radius: 24px; display: inline-flex; align-items: center; justify-content: center; color: #fff; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(5,150,105,0.3);">
    <i data-lucide="check" style="width: 40px; height: 40px; stroke-width: 2.5;"></i>
  </div>

  <h1 style="font-size: 26px; font-weight: 900; color: #111827; margin: 0 0 8px;">Your POS Is Ready!</h1>
  <p style="font-size: 14.5px; color: #6B7280; margin: 0 0 28px; line-height: 1.6;">
    Congratulations! <strong>Trivikratech Jewellers</strong> showroom is live with instant GST billing, daily bullion price feeds, and cloud vault syncing.
  </p>

  <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
    <a href="../pos.php" class="btn btn-primary btn-lg" style="padding: 12px 28px; border-radius: 12px; box-shadow: 0 4px 14px rgba(15,118,110,0.3);">
      <i data-lucide="shopping-cart" style="width: 18px; height: 18px;"></i>
      <span>Open POS Billing Counter</span>
    </a>
    <a href="../dashboard.php" class="btn btn-secondary btn-lg" style="padding: 12px 24px; border-radius: 12px;">
      <span>Explore Dashboard</span>
    </a>
  </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
