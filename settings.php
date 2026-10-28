<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Settings & Configuration - Trivikratech POS";
$db = getDBConnection();
$settings = getSettings();

// Fetch staff list
$staff = [];
try {
    $stmt = $db->query("SELECT * FROM users ORDER BY role DESC, name ASC");
    $staff = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch branches
$branches = [];
try {
    $bStmt = $db->query("SELECT * FROM branches");
    $branches = $bStmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Store Settings & Master Rates</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Configure daily live gold/silver bullion rates, GST taxes, staff permissions & invoice design</p>
  </div>
</div>

<form action="api/settings.php?action=save_all" method="POST">
  <!-- Live Metal Bullion Rates -->
  <div class="card">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px;">
      <i data-lucide="coins" style="width: 20px; height: 20px; color: #D97706;"></i>
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Daily Bullion Market Rates (Auto-Calculates POS Prices)</h3>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
      <div class="form-group">
        <label class="form-label" style="color: #D97706;">🟡 24K Gold Rate (₹/gram)</label>
        <input type="number" step="1" name="rate_gold_24k" class="form-control" value="<?= htmlspecialchars($settings['rate_gold_24k'] ?? '7250') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" style="color: #B45309;">🟠 22K Gold Rate (₹/gram)</label>
        <input type="number" step="1" name="rate_gold_22k" class="form-control" value="<?= htmlspecialchars($settings['rate_gold_22k'] ?? '6650') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Gold 18K Rate (₹/gram)</label>
        <input type="number" step="1" name="rate_gold_18k" class="form-control" value="<?= htmlspecialchars($settings['rate_gold_18k'] ?? '5450') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" style="color: #4B5563;">⚪ 925 Silver Rate (₹/gram)</label>
        <input type="number" step="1" name="rate_silver" class="form-control" value="<?= htmlspecialchars($settings['rate_silver'] ?? '88') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Platinum Pt950 (₹/gram)</label>
        <input type="number" step="1" name="rate_platinum" class="form-control" value="<?= htmlspecialchars($settings['rate_platinum'] ?? '3800') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" style="color: #0284C7;">💎 Diamond VVS/VS (₹/carat)</label>
        <input type="number" step="1" name="rate_diamond_carat" class="form-control" value="<?= htmlspecialchars($settings['rate_diamond_carat'] ?? '65000') ?>" required>
      </div>
    </div>
  </div>

  <!-- Store Profile & GST -->
  <div class="card">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px;">
      <i data-lucide="building" style="width: 20px; height: 20px; color: var(--color-primary);"></i>
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Showroom Profile & GST Tax Rules</h3>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label class="form-label">Store Legal Entity Name</label>
        <input type="text" name="store_name" class="form-control" value="<?= htmlspecialchars($settings['store_name'] ?? 'Trivikratech Jewellers Pvt. Ltd.') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">GSTIN (Goods & Services Tax Identification Number)</label>
        <input type="text" name="gstin" class="form-control" value="<?= htmlspecialchars($settings['gstin'] ?? '27AABFA1234Z1ZX') ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Showroom Address</label>
      <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($settings['address'] ?? 'Shop 12, Gold Souk Market, Bandra West, Mumbai 400050') ?>" required>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label class="form-label">Contact Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone'] ?? '+91 98765 00000') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Official Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email'] ?? 'contact@trivikratech.com') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Standard GST Rate (%)</label>
        <input type="number" step="0.1" name="gst_rate" class="form-control" value="<?= htmlspecialchars($settings['gst_rate'] ?? '3') ?>" required>
      </div>
    </div>
  </div>

  <!-- Invoice & Receipt Customization -->
  <div class="card">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px;">
      <i data-lucide="printer" style="width: 20px; height: 20px; color: #2563EB;"></i>
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Tax Invoice & Thermal Print Receipt Settings</h3>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label class="form-label">Invoice Header Title</label>
        <input type="text" name="header_text" class="form-control" value="<?= htmlspecialchars($settings['header_text'] ?? 'Trivikratech Jewellers') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Default Printing Paper Size</label>
        <select name="paper_size" class="form-control">
          <option value="A4" <?= ($settings['paper_size'] ?? 'A4') === 'A4' ? 'selected' : '' ?>>A4 Full Page Laser Invoice</option>
          <option value="A5" <?= ($settings['paper_size'] ?? '') === 'A5' ? 'selected' : '' ?>>A5 Half Page Format</option>
          <option value="Thermal" <?= ($settings['paper_size'] ?? '') === 'Thermal' ? 'selected' : '' ?>>80mm Thermal Receipt Slip</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Invoice Footer / Return Policy Note</label>
      <textarea name="footer_text" class="form-control" rows="2"><?= htmlspecialchars($settings['footer_text'] ?? 'Thank you for your purchase! Jewellery once sold can be exchanged within 7 days with original invoice.') ?></textarea>
    </div>
  </div>

  <!-- Save Changes Button -->
  <div style="margin-bottom: 30px;">
    <button type="submit" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 16px rgba(15,118,110,0.3);">
      <i data-lucide="save" style="width: 18px; height: 18px;"></i>
      <span>Save All Store Settings & Rates</span>
    </button>
  </div>
</form>

<!-- Staff Members & Role Access Control -->
<div class="card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px;">
    <div style="display: flex; align-items: center; gap: 8px;">
      <i data-lucide="users" style="width: 20px; height: 20px; color: #7E22CE;"></i>
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Showroom Staff & Role Permissions</h3>
    </div>
    <button type="button" onclick="openAddStaffModal()" class="btn btn-primary btn-sm">
      <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
      <span>Add Staff Member</span>
    </button>
  </div>

  <div class="table-container" style="border: none; box-shadow: none;">
    <table class="data-table">
      <thead>
        <tr>
          <th>Staff Name</th>
          <th>Designation / Role</th>
          <th>Email</th>
          <th>Mobile</th>
          <th>Branch Assigned</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staff as $st): ?>
          <?php $roleBadge = getRoleBadgeInfo($st['role']); ?>
          <tr>
            <td style="font-weight: 700; color: #111827;"><?= htmlspecialchars($st['name']) ?></td>
            <td>
              <span class="badge" style="background: <?= $roleBadge['bg'] ?>; color: <?= $roleBadge['color'] ?>; border: 1px solid <?= $roleBadge['border'] ?>;">
                <?= $roleBadge['label'] ?>
              </span>
            </td>
            <td><?= htmlspecialchars($st['email']) ?></td>
            <td><?= htmlspecialchars($st['mobile'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($st['branch'] ?? 'All Branches') ?></td>
            <td>
              <?php if ($st['email'] !== 'admin@jeweller.com'): ?>
                <button type="button" onclick="deleteStaff(<?= $st['id'] ?>)" class="btn btn-ghost btn-sm" style="color: #DC2626; padding: 4px 8px;">
                  <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                </button>
              <?php else: ?>
                <span style="font-size: 11px; color: #9CA3AF; font-weight: 600;">Primary Owner</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function openAddStaffModal() {
  const name = prompt("Enter Staff Full Name:");
  if (!name) return;
  const email = prompt("Enter Staff Email Address:");
  if (!email) return;
  const role = prompt("Enter Role (Employee / Store Manager / Jewellery Repairing Assistant / Super Admin):", "Employee");
  const branch = prompt("Assigned Branch (Bandra West / Andheri East / Powai):", "Bandra West");

  fetch('api/staff.php?action=create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name, email, role, branch, mobile: '9876540000' })
  }).then(() => location.reload());
}

function deleteStaff(id) {
  if (confirm('Remove this staff member from POS system?')) {
    fetch('api/staff.php?action=delete&id=' + id).then(() => location.reload());
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
