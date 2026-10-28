<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "VIP Customer Analytics - Trivikratech POS";
$db = getDBConnection();

$vipCustomers = [];
try {
    $stmt = $db->query("SELECT * FROM customers ORDER BY lifetime_value DESC LIMIT 10");
    $vipCustomers = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">VIP Patrons & High Net Worth Customers</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Top spenders ranking, purchase frequency & customer lifetime value (LTV)</p>
  </div>
</div>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Customer Name</th>
        <th>Mobile / Email</th>
        <th>Tier</th>
        <th>Total Orders</th>
        <th>Cumulative Spend (LTV)</th>
        <th>Loyalty Points</th>
        <th>Last Showroom Visit</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($vipCustomers as $vip): ?>
        <tr>
          <td style="font-weight: 800; color: #111827;"><?= htmlspecialchars($vip['name']) ?></td>
          <td>
            <div style="font-weight: 600;"><?= htmlspecialchars($vip['mobile']) ?></div>
            <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($vip['email'] ?? '') ?></div>
          </td>
          <td>
            <span class="badge <?= getTierBadgeClass($vip['loyalty_tier']) ?>">
              <?= htmlspecialchars($vip['loyalty_tier']) ?>
            </span>
          </td>
          <td style="font-weight: 700;"><?= (int)$vip['total_orders'] ?> transactions</td>
          <td style="font-weight: 900; color: var(--color-primary); font-size: 15px;"><?= formatFullINR($vip['lifetime_value']) ?></td>
          <td style="font-weight: 700; color: #D97706;"><?= number_format($vip['loyalty_points']) ?> pts</td>
          <td style="color: #6B7280; font-size: 12.5px;"><?= htmlspecialchars($vip['last_visit'] ?? 'Recent') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
