<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "Sales Orders - Trivikratech POS";
$db = getDBConnection();

$orders = [];
try {
    $stmt = $db->query("SELECT * FROM orders ORDER BY order_date DESC");
    $orders = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Point-of-Sale Orders</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Review customer transactions, invoice history, and returns</p>
  </div>

  <a href="pos.php" class="btn btn-primary">
    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
    <span>New Counter Sale</span>
  </a>
</div>

<!-- Search & Filters -->
<div style="display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
  <div style="position: relative; flex: 1; min-width: 240px;">
    <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9CA3AF;"></i>
    <input type="text" id="orderSearch" class="form-control" placeholder="Search by Order No or Customer Name..." style="padding-left: 36px;">
  </div>
</div>

<div class="table-container">
  <table class="data-table" id="ordersTable">
    <thead>
      <tr>
        <th>Order No</th>
        <th>Customer</th>
        <th>Items</th>
        <th>Subtotal</th>
        <th>GST (3%)</th>
        <th>Grand Total</th>
        <th>Payment</th>
        <th>Status</th>
        <th>Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="10" style="text-align: center; color: #9CA3AF; padding: 24px;">No orders found.</td></tr>
      <?php else: ?>
        <?php foreach ($orders as $o): ?>
          <tr data-order-no="<?= strtolower($o['order_no']) ?>" data-cust="<?= strtolower($o['customer_name']) ?>">
            <td style="font-weight: 800; color: var(--color-primary);"><?= htmlspecialchars($o['order_no']) ?></td>
            <td>
              <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($o['customer_name']) ?></div>
              <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($o['branch']) ?></div>
            </td>
            <td><?= (int)$o['total_items'] ?> pcs</td>
            <td><?= formatFullINR($o['subtotal']) ?></td>
            <td style="color: #6B7280;"><?= formatFullINR($o['gst']) ?></td>
            <td style="font-weight: 800; color: var(--color-primary-dark);"><?= formatFullINR($o['total']) ?></td>
            <td><span class="badge badge-info"><?= htmlspecialchars($o['payment_method']) ?></span></td>
            <td>
              <span class="badge <?= $o['status'] === 'Completed' ? 'badge-success' : ($o['status'] === 'Pending' ? 'badge-warning' : 'badge-danger') ?>">
                <?= htmlspecialchars($o['status']) ?>
              </span>
            </td>
            <td style="font-size: 12px; color: #6B7280;"><?= date('d M Y, h:i A', strtotime($o['order_date'])) ?></td>
            <td>
              <div style="display: flex; gap: 6px;">
                <a href="view-invoice.php?order_no=<?= urlencode($o['order_no']) ?>" class="btn btn-ghost btn-sm" style="padding: 4px 8px;" title="Print Invoice">
                  <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                </a>
                <?php if ($o['status'] === 'Completed'): ?>
                  <button type="button" onclick="openReturnModal(<?= $o['id'] ?>, '<?= $o['order_no'] ?>')" class="btn btn-ghost btn-sm" style="color: #DC2626; padding: 4px 8px;" title="Process Return">
                    <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Return Modal -->
<div id="returnModal" class="modal-backdrop">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Process Order Return</h3>
      <button type="button" onclick="closeReturnModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleReturnSubmit(event)">
      <input type="hidden" id="returnOrderId">
      <div class="modal-body">
        <div style="font-size: 13.5px; margin-bottom: 14px;">
          Returning Order: <strong id="returnOrderNoLabel" style="color: var(--color-primary);"></strong>
        </div>
        <div class="form-group">
          <label class="form-label">Reason for Return *</label>
          <select id="returnReason" class="form-control" required>
            <option value="Customer Size Issue">Customer Size Issue</option>
            <option value="Exchange for Different Ornament">Exchange for Different Ornament</option>
            <option value="Design Preference">Design Preference</option>
            <option value="Defect / Polish Request">Defect / Polish Request</option>
            <option value="Other">Other Reason</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeReturnModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-danger">Confirm Return & Cancel Invoice</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('orderSearch')?.addEventListener('input', function(e) {
  const query = e.target.value.toLowerCase();
  document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
    const no = row.getAttribute('data-order-no') || '';
    const cust = row.getAttribute('data-cust') || '';
    if (!query || no.includes(query) || cust.includes(query)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});

function openReturnModal(id, orderNo) {
  document.getElementById('returnOrderId').value = id;
  document.getElementById('returnOrderNoLabel').innerText = orderNo;
  document.getElementById('returnModal').classList.add('show');
}

function closeReturnModal() {
  document.getElementById('returnModal').classList.remove('show');
}

async function handleReturnSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('returnOrderId').value;
  const reason = document.getElementById('returnReason').value;

  const res = await fetch('api/orders.php?action=process_return', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order_id: id, reason })
  });
  const data = await res.json();
  if (data.success) {
    location.reload();
  } else {
    alert(data.error || 'Failed to process return');
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
