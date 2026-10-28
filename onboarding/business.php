<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['onboarding_store_name'] = $_POST['store_name'] ?? 'Trivikratech Jewellers';
    $_SESSION['onboarding_gst'] = $_POST['gst_number'] ?? '';
    $_SESSION['onboarding_pan'] = $_POST['pan_number'] ?? '';
    $_SESSION['onboarding_city'] = $_POST['city'] ?? 'Mumbai';
    header('Location: subscription.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Step 1: Business Setup - Trivikratech POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 540px; width: 100%; padding: 36px;">
  <!-- Progress Bar -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <span style="font-size: 12px; font-weight: 800; color: var(--color-primary); text-transform: uppercase;">Step 1 of 5</span>
    <span style="font-size: 12px; color: #6B7280;">Business Setup</span>
  </div>

  <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 6px;">Tell Us About Your Jewellery Store</h2>
  <p style="font-size: 13.5px; color: #6B7280; margin: 0 0 24px;">Configure GST, Hallmarking & branch details</p>

  <form method="POST" action="business.php">
    <div class="form-group">
      <label class="form-label">Store Trade Name *</label>
      <input type="text" name="store_name" class="form-control" value="Trivikratech Jewellers" required>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
      <div class="form-group">
        <label class="form-label">GSTIN (Optional)</label>
        <input type="text" name="gst_number" class="form-control" placeholder="27AAAAA0000A1Z5">
      </div>
      <div class="form-group">
        <label class="form-label">PAN Number</label>
        <input type="text" name="pan_number" class="form-control" placeholder="ABCDE1234F">
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
      <div class="form-group">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="Mumbai">
      </div>
      <div class="form-group">
        <label class="form-label">State</label>
        <input type="text" name="state" class="form-control" value="Maharashtra">
      </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; border-radius: 10px; margin-top: 10px;">
      <span>Continue to Subscription</span>
      <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
    </button>
  </form>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
