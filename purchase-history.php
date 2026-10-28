<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "Customer Purchase History - Trivikratech POS";
$db = getDBConnection();

$orders = [];
try {
    $stmt = $db->query("
        SELECT o.*, c.mobile, c.loyalty_tier 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        ORDER BY o.order_date DESC
    ");
    $orders = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Customer Purchase Ledger</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Historical transaction log grouped by customer visits</p>
  </div>
</div>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Date & Time</th>
        <th>Customer Name</th>
        <th>Tier</th>
        <th>Order Ref</th>
        <th>Items</th>
        <th>Amount Billed</th>
        <th>Payment Mode</th>
        <th>Salesperson</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="8" style="text-align: center; color: #9CA3AF; padding: 24px;">No purchase history found.</td></tr>
      <?php else: ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td style="font-size: 12.5px; color: #6B7280;"><?= date('d M Y, h:i A', strtotime($o['order_date'])) ?></td>
            <td>
              <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($o['customer_name']) ?></div>
              <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($o['mobile'] ?? 'N/A') ?></div>
            </td>
            <td>
              <span class="badge <?= getTierBadgeClass($o['loyalty_tier'] ?? 'Bronze') ?>">
                <?= htmlspecialchars($o['loyalty_tier'] ?? 'Bronze') ?>
              </span>
            </td>
            <td style="font-weight: 700; color: var(--color-primary);"><?= htmlspecialchars($o['order_no']) ?></td>
            <td><?= (int)$o['total_items'] ?> pcs</td>
            <td style="font-weight: 800; color: var(--color-primary-dark);"><?= formatFullINR($o['total']) ?></td>
            <td><span class="badge badge-info"><?= htmlspecialchars($o['payment_method']) ?></span></td>
            <td><?= htmlspecialchars($o['sales_person'] ?? 'Staff') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
