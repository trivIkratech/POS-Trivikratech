<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['onboarding_org'] = $_POST['org_name'] ?? 'Trivikratech Retail Group';
    $_SESSION['onboarding_branch'] = $_POST['branch_name'] ?? 'Bandra West Branch';
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Step 3: Organization Setup - Trivikratech POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0F766E 0%, #0d6360 40%, #042f2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 500px; width: 100%; padding: 36px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <span style="font-size: 12px; font-weight: 800; color: var(--color-primary); text-transform: uppercase;">Step 3 of 5</span>
    <span style="font-size: 12px; color: #6B7280;">Organization & Showroom</span>
  </div>

  <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 6px;">Showroom Branch Setup</h2>
  <p style="font-size: 13.5px; color: #6B7280; margin: 0 0 24px;">Set default billing currency and headquarters branch</p>

  <form method="POST" action="organization.php">
    <div class="form-group">
      <label class="form-label">Organization Group Name</label>
      <input type="text" name="org_name" class="form-control" value="Trivikratech Retail Group" required>
    </div>

    <div class="form-group">
      <label class="form-label">Primary Flagship Branch Name</label>
      <input type="text" name="branch_name" class="form-control" value="Bandra West Branch" required>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
      <div class="form-group">
        <label class="form-label">Currency</label>
        <input type="text" class="form-control" value="INR (₹ Indian Rupee)" readonly style="background: var(--color-surface-2);">
      </div>
      <div class="form-group">
        <label class="form-label">Timezone</label>
        <input type="text" class="form-control" value="Asia/Kolkata (IST)" readonly style="background: var(--color-surface-2);">
      </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; border-radius: 10px; margin-top: 10px;">
      <span>Continue to Admin Profile</span>
      <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
    </button>
  </form>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
