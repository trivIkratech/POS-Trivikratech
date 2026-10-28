<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "Sales Returns & Exchanges - Trivikratech POS";
$db = getDBConnection();

$returns = [];
try {
    $stmt = $db->query("SELECT * FROM orders WHERE status = 'Returned' ORDER BY order_date DESC");
    $returns = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Returns & Refund Management</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Review returned jewellery ornaments, credit notes, and exchanges</p>
  </div>
</div>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Order No</th>
        <th>Customer</th>
        <th>Items Returned</th>
        <th>Refund Amount</th>
        <th>Branch</th>
        <th>Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($returns)): ?>
        <tr><td colspan="7" style="text-align: center; color: #9CA3AF; padding: 24px;">No returned orders found.</td></tr>
      <?php else: ?>
        <?php foreach ($returns as $r): ?>
          <tr>
            <td style="font-weight: 700; color: var(--color-primary);"><?= htmlspecialchars($r['order_no']) ?></td>
            <td style="font-weight: 600;"><?= htmlspecialchars($r['customer_name']) ?></td>
            <td><?= (int)$r['total_items'] ?> pcs</td>
            <td style="font-weight: 800; color: #DC2626;"><?= formatFullINR($r['total']) ?></td>
            <td><?= htmlspecialchars($r['branch']) ?></td>
            <td style="color: #6B7280; font-size: 12px;"><?= htmlspecialchars($r['order_date']) ?></td>
            <td><span class="badge badge-danger">Returned</span></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
