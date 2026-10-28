<?php
$pageTitle = "Access Restricted - Trivikratech POS";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 65vh; text-align: center; padding: 40px 20px;">
  <div style="width: 72px; height: 72px; border-radius: 20px; background: #FEF2F2; border: 1px solid #FEE2E2; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);">
    <i data-lucide="lock" style="width: 36px; height: 36px; color: #DC2626;"></i>
  </div>

  <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 8px;">
    Access Restricted
  </h2>
  <p style="font-size: 14px; color: #6B7280; max-width: 480px; margin: 0 0 24px; line-height: 1.6;">
    You are currently logged in as <strong><?= htmlspecialchars($currentUser['role'] ?? 'Employee') ?></strong>. This section (Inventory, Operations, Accounting, or Settings) is restricted to <strong>Super Admin</strong>.
  </p>

  <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
    <a href="pos.php" class="btn btn-primary" style="padding: 10px 20px; border-radius: 10px; font-size: 14px;">
      <i data-lucide="shopping-cart" style="width: 16px; height: 16px;"></i>
      <span>Go to POS Billing</span>
    </a>
    <a href="customers.php" class="btn btn-secondary" style="padding: 10px 20px; border-radius: 10px; font-size: 14px;">
      <i data-lucide="users" style="width: 16px; height: 16px;"></i>
      <span>Manage Customers</span>
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
