<?php
$userRole = $currentUser['role'] ?? 'Employee';
$isSuperAdmin = ($userRole === 'Super Admin' || $userRole === 'Store Manager');

// Helper to check active nav
function isActiveNav($pages) {
    global $currentScript;
    if (is_array($pages)) {
        return in_array($currentScript, $pages) ? 'active' : '';
    }
    return $currentScript === $pages ? 'active' : '';
}
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo-icon">T</div>
    <div>
      <div style="font-weight: 800; font-size: 15px; color: var(--color-primary-dark); line-height: 1.2;">
        <?= htmlspecialchars(getSetting('store_name', 'Trivikratech POS')) ?>
      </div>
      <div style="font-size: 11px; color: var(--color-text-2); font-weight: 500;">
        Jewellery POS System
      </div>
    </div>
  </div>

  <div class="sidebar-nav">
    <div class="nav-section-title">Sales & Billing</div>
    <a href="dashboard.php" class="nav-item <?= isActiveNav('dashboard') ?>">
      <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
      <span>Dashboard</span>
    </a>
    <a href="pos.php" class="nav-item <?= isActiveNav('pos') ?>">
      <i data-lucide="shopping-cart" style="width: 18px; height: 18px;"></i>
      <span>POS Billing</span>
      <span class="nav-badge" style="background: #ECFDF5; color: #059669;">Live</span>
    </a>
    <a href="orders.php" class="nav-item <?= isActiveNav(['orders', 'returns']) ?>">
      <i data-lucide="receipt" style="width: 18px; height: 18px;"></i>
      <span>Orders</span>
    </a>
    <a href="invoices.php" class="nav-item <?= isActiveNav(['invoices', 'view-invoice']) ?>">
      <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
      <span>Invoices</span>
    </a>
    <a href="old_gold_exchange.php" class="nav-item <?= isActiveNav(['old_gold_exchange', 'old_gold_exchange_create', 'old_gold_exchange_view']) ?>">
      <i data-lucide="scale" style="width: 18px; height: 18px; color: #D97706;"></i>
      <span>Old Gold Exchange</span>
      <span class="nav-badge" style="background: #FEF3C7; color: #B45309; font-weight: 700;">Rate 24K</span>
    </a>

    <div class="nav-section-title">Customers & CRM</div>
    <a href="customers.php" class="nav-item <?= isActiveNav(['customers', 'customer-detail']) ?>">
      <i data-lucide="users" style="width: 18px; height: 18px;"></i>
      <span>Customers</span>
    </a>
    <a href="loyalty.php" class="nav-item <?= isActiveNav('loyalty') ?>">
      <i data-lucide="award" style="width: 18px; height: 18px;"></i>
      <span>Loyalty Program</span>
    </a>
    <a href="purchase-history.php" class="nav-item <?= isActiveNav('purchase-history') ?>">
      <i data-lucide="history" style="width: 18px; height: 18px;"></i>
      <span>Purchase History</span>
    </a>

    <?php if ($isSuperAdmin): ?>
    <div class="nav-section-title">Inventory Management</div>
    <a href="inventory-gold.php" class="nav-item <?= isActiveNav('inventory-gold') ?>">
      <i data-lucide="circle-dot" style="width: 18px; height: 18px; color: #D97706;"></i>
      <span>Gold Inventory</span>
    </a>
    <a href="inventory-silver.php" class="nav-item <?= isActiveNav('inventory-silver') ?>">
      <i data-lucide="circle-dot" style="width: 18px; height: 18px; color: #6B7280;"></i>
      <span>Silver Inventory</span>
    </a>
    <a href="inventory-diamond.php" class="nav-item <?= isActiveNav('inventory-diamond') ?>">
      <i data-lucide="gem" style="width: 18px; height: 18px; color: #0284C7;"></i>
      <span>Diamond Inventory</span>
    </a>
    <a href="stock.php" class="nav-item <?= isActiveNav('stock') ?>">
      <i data-lucide="boxes" style="width: 18px; height: 18px;"></i>
      <span>Stock Overview</span>
    </a>
    <a href="categories.php" class="nav-item <?= isActiveNav('categories') ?>">
      <i data-lucide="tag" style="width: 18px; height: 18px;"></i>
      <span>Categories</span>
    </a>
    <a href="transfers.php" class="nav-item <?= isActiveNav('transfers') ?>">
      <i data-lucide="arrow-right-left" style="width: 18px; height: 18px;"></i>
      <span>Stock Transfers</span>
    </a>

    <div class="nav-section-title">Operations & Finance</div>
    <a href="purchase-orders.php" class="nav-item <?= isActiveNav('purchase-orders') ?>">
      <i data-lucide="truck" style="width: 18px; height: 18px;"></i>
      <span>Purchase Orders</span>
    </a>
    <a href="suppliers.php" class="nav-item <?= isActiveNav('suppliers') ?>">
      <i data-lucide="building-2" style="width: 18px; height: 18px;"></i>
      <span>Suppliers</span>
    </a>
    <a href="gst.php" class="nav-item <?= isActiveNav('gst') ?>">
      <i data-lucide="file-check" style="width: 18px; height: 18px;"></i>
      <span>GST Tax Reports</span>
    </a>
    <a href="expenses.php" class="nav-item <?= isActiveNav('expenses') ?>">
      <i data-lucide="credit-card" style="width: 18px; height: 18px;"></i>
      <span>Store Expenses</span>
    </a>

    <div class="nav-section-title">Reports & Analytics</div>
    <a href="analytics.php" class="nav-item <?= isActiveNav('analytics') ?>">
      <i data-lucide="trending-up" style="width: 18px; height: 18px;"></i>
      <span>Analytics</span>
    </a>
    <a href="product-reports.php" class="nav-item <?= isActiveNav('product-reports') ?>">
      <i data-lucide="bar-chart-2" style="width: 18px; height: 18px;"></i>
      <span>Product Reports</span>
    </a>
    <a href="customer-reports.php" class="nav-item <?= isActiveNav('customer-reports') ?>">
      <i data-lucide="pie-chart" style="width: 18px; height: 18px;"></i>
      <span>Customer Reports</span>
    </a>

    <div class="nav-section-title">System</div>
    <a href="settings.php" class="nav-item <?= isActiveNav('settings') ?>">
      <i data-lucide="settings" style="width: 18px; height: 18px;"></i>
      <span>Settings</span>
    </a>
    <?php endif; ?>
  </div>

  <div style="padding: 14px; border-top: 1px solid var(--color-border); background: var(--color-surface-2);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
      <div style="font-weight: 700; font-size: 12px; color: var(--color-text-1);">
        <?= htmlspecialchars($currentUser['name'] ?? 'Staff') ?>
      </div>
      <?php $roleBadge = getRoleBadgeInfo($currentUser['role'] ?? 'Employee'); ?>
      <span class="badge" style="background: <?= $roleBadge['bg'] ?>; color: <?= $roleBadge['color'] ?>; border: 1px solid <?= $roleBadge['border'] ?>; font-size: 10px;">
        <?= $currentUser['role'] ?? 'Employee' ?>
      </span>
    </div>
    <a href="logout.php" class="btn btn-ghost btn-sm" style="width: 100%; justify-content: flex-start; color: var(--color-danger); border-color: rgba(220,38,38,0.2);">
      <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
      <span>Sign Out</span>
    </a>
  </div>
</aside>
<div class="main-content">
