<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "Customers & Loyalty CRM - Trivikratech POS";
$db = getDBConnection();

$customers = [];
try {
    $stmt = $db->query("SELECT * FROM customers ORDER BY total_orders DESC, name ASC");
    $customers = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Customer Directory & CRM</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Manage customer profiles, purchase history, and tier-based loyalty points</p>
  </div>

  <button type="button" onclick="openAddCustomerModal()" class="btn btn-primary">
    <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
    <span>Add New Customer</span>
  </button>
</div>

<!-- CRM KPI Cards -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr);">
  <div class="kpi-card">
    <div class="kpi-value" style="color: var(--color-primary);"><?= count($customers) ?></div>
    <div class="kpi-label">Total Registered Profiles</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= number_format(array_reduce($customers, fn($s, $c) => $s + (int)$c['loyalty_points'], 0)) ?> pts</div>
    <div class="kpi-label">Active Loyalty Points</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= formatINR(array_reduce($customers, fn($s, $c) => $s + (float)$c['lifetime_value'], 0)) ?></div>
    <div class="kpi-label">Cumulative Lifetime Value</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value" style="color: #D97706;"><?= count(array_filter($customers, fn($c) => in_array($c['loyalty_tier'], ['Platinum', 'Diamond']))) ?></div>
    <div class="kpi-label">VIP Platinum/Diamond Patrons</div>
  </div>
</div>

<!-- Search Input -->
<div style="margin-bottom: 20px; max-width: 380px; position: relative;">
  <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9CA3AF;"></i>
  <input type="text" id="custSearch" class="form-control" placeholder="Search by name, mobile, or city..." style="padding-left: 36px;">
</div>

<div class="table-container">
  <table class="data-table" id="custTable">
    <thead>
      <tr>
        <th>Customer Name</th>
        <th>Mobile / Email</th>
        <th>City</th>
        <th>Loyalty Tier</th>
        <th>Points</th>
        <th>Lifetime Value</th>
        <th>Orders</th>
        <th>Last Visit</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($customers)): ?>
        <tr><td colspan="9" style="text-align: center; color: #9CA3AF; padding: 24px;">No customers found.</td></tr>
      <?php else: ?>
        <?php foreach ($customers as $c): ?>
          <tr data-name="<?= strtolower($c['name']) ?>" data-mob="<?= $c['mobile'] ?>" data-city="<?= strtolower($c['city']) ?>">
            <td>
              <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($c['name']) ?></div>
              <?php if (!empty($c['gstin'])): ?>
                <div style="font-size: 11px; color: #0F766E;">GSTIN: <?= htmlspecialchars($c['gstin']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-weight: 600;"><?= htmlspecialchars($c['mobile']) ?></div>
              <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($c['email'] ?? 'N/A') ?></div>
            </td>
            <td><?= htmlspecialchars($c['city']) ?></td>
            <td>
              <span class="badge <?= getTierBadgeClass($c['loyalty_tier']) ?>">
                <?= htmlspecialchars($c['loyalty_tier']) ?>
              </span>
            </td>
            <td style="font-weight: 700; color: #D97706;"><?= number_format($c['loyalty_points']) ?> pts</td>
            <td style="font-weight: 800; color: var(--color-primary-dark);"><?= formatFullINR($c['lifetime_value']) ?></td>
            <td><?= (int)$c['total_orders'] ?> bills</td>
            <td style="font-size: 12px; color: #6B7280;"><?= htmlspecialchars($c['last_visit'] ?? 'Never') ?></td>
            <td>
              <a href="pos.php" class="btn btn-primary btn-sm" style="padding: 4px 10px; font-size: 11px;" title="Start Bill">
                <i data-lucide="shopping-cart" style="width: 12px; height: 12px;"></i>
                <span>Bill</span>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Add Customer Modal -->
<div id="addCustomerModal" class="modal-backdrop">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Register New Customer</h3>
      <button type="button" onclick="closeAddCustomerModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleCustomerSubmit(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" id="cName" class="form-control" required placeholder="e.g. Anand Mahindra">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Mobile Number *</label>
            <input type="tel" id="cMobile" class="form-control" required placeholder="9876543210">
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" id="cEmail" class="form-control" placeholder="anand@corp.in">
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">City</label>
            <input type="text" id="cCity" class="form-control" value="Mumbai">
          </div>
          <div class="form-group">
            <label class="form-label">GSTIN (Optional)</label>
            <input type="text" id="cGstin" class="form-control" placeholder="27AAAAA0000A1Z5">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeAddCustomerModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Customer</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('custSearch')?.addEventListener('input', function(e) {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('#custTable tbody tr').forEach(row => {
    const name = row.getAttribute('data-name') || '';
    const mob = row.getAttribute('data-mob') || '';
    const city = row.getAttribute('data-city') || '';
    if (!q || name.includes(q) || mob.includes(q) || city.includes(q)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});

function openAddCustomerModal() {
  document.getElementById('addCustomerModal').classList.add('show');
}
function closeAddCustomerModal() {
  document.getElementById('addCustomerModal').classList.remove('show');
}

async function handleCustomerSubmit(e) {
  e.preventDefault();
  const payload = {
    name: document.getElementById('cName').value,
    mobile: document.getElementById('cMobile').value,
    email: document.getElementById('cEmail').value,
    city: document.getElementById('cCity').value,
    gstin: document.getElementById('cGstin').value
  };

  const res = await fetch('api/customers.php?action=create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.success) {
    location.reload();
  } else {
    alert(data.error || 'Failed to add customer');
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
