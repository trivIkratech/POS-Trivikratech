<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['onboarding_plan'] = $_POST['plan'] ?? 'professional';
    header('Location: organization.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Step 2: Subscription Plan - Trivikratech POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 540px; width: 100%; padding: 36px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <span style="font-size: 12px; font-weight: 800; color: var(--color-primary); text-transform: uppercase;">Step 2 of 5</span>
    <span style="font-size: 12px; color: #6B7280;">Choose Plan</span>
  </div>

  <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 6px;">Select Your Subscription Tier</h2>
  <p style="font-size: 13.5px; color: #6B7280; margin: 0 0 24px;">All plans include 14 days free trial with zero upfront charge</p>

  <form method="POST" action="subscription.php">
    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
      <label style="border: 2px solid var(--color-border); border-radius: 14px; padding: 16px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <input type="radio" name="plan" value="starter">
          <div>
            <div style="font-weight: 800; font-size: 15px;">Starter Plan</div>
            <div style="font-size: 12px; color: #6B7280;">Single boutique counter · 1,000 SKUs</div>
          </div>
        </div>
        <div style="font-weight: 800; font-size: 16px;">₹1,499/mo</div>
      </label>

      <label style="border: 2px solid var(--color-primary); background: #F0FDFA; border-radius: 14px; padding: 16px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <input type="radio" name="plan" value="professional" checked>
          <div>
            <div style="font-weight: 800; font-size: 15px; color: var(--color-primary);">Professional (Recommended)</div>
            <div style="font-size: 12px; color: #6B7280;">3 Showrooms · Unlimited inventory · Loyalty CRM</div>
          </div>
        </div>
        <div style="font-weight: 800; font-size: 16px; color: var(--color-primary);">₹2,999/mo</div>
      </label>

      <label style="border: 2px solid var(--color-border); border-radius: 14px; padding: 16px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <input type="radio" name="plan" value="enterprise">
          <div>
            <div style="font-weight: 800; font-size: 15px;">Enterprise Chain</div>
            <div style="font-size: 12px; color: #6B7280;">Unlimited multi-city showrooms & ERP sync</div>
          </div>
        </div>
        <div style="font-weight: 800; font-size: 16px;">₹5,999/mo</div>
      </label>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; border-radius: 10px;">
      <span>Continue to Organization Setup</span>
      <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
    </button>
  </form>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
