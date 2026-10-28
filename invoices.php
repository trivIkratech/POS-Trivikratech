<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "GST Tax Invoices - Trivikratech POS";
$db = getDBConnection();

$invoices = [];
try {
    $stmt = $db->query("SELECT * FROM invoices ORDER BY invoice_date DESC");
    $invoices = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">GST Invoices & Tax Billing</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Official GST registered jewellery tax invoices for customer billing</p>
  </div>
</div>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Invoice No</th>
        <th>Customer</th>
        <th>Invoice Date</th>
        <th>Due Date</th>
        <th>Total Amount</th>
        <th>GST (3%)</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($invoices)): ?>
        <tr><td colspan="8" style="text-align: center; color: #9CA3AF; padding: 24px;">No invoices found.</td></tr>
      <?php else: ?>
        <?php foreach ($invoices as $inv): ?>
          <tr>
            <td style="font-weight: 800; color: var(--color-primary);"><?= htmlspecialchars($inv['invoice_no']) ?></td>
            <td style="font-weight: 600;"><?= htmlspecialchars($inv['customer_name']) ?></td>
            <td><?= htmlspecialchars($inv['invoice_date']) ?></td>
            <td><?= htmlspecialchars($inv['due_date']) ?></td>
            <td style="font-weight: 800; color: var(--color-primary-dark);"><?= formatFullINR($inv['amount']) ?></td>
            <td style="color: #6B7280;"><?= formatFullINR($inv['gst']) ?></td>
            <td>
              <span class="badge <?= $inv['status'] === 'Paid' ? 'badge-success' : 'badge-warning' ?>">
                <?= htmlspecialchars($inv['status']) ?>
              </span>
            </td>
            <td>
              <a href="view-invoice.php?invoice_no=<?= urlencode($inv['invoice_no']) ?>" class="btn btn-primary btn-sm" style="padding: 4px 10px;">
                <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                <span>View & Print</span>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
