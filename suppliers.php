<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Suppliers Directory - Trivikratech POS";
$db = getDBConnection();

$suppliers = [];
try {
    $stmt = $db->query("SELECT * FROM suppliers ORDER BY id DESC");
    $suppliers = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Bullion & Diamond Suppliers Directory</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Certified bullion merchants, loose diamond cutters, and casting karigars</p>
  </div>
  <div style="display: flex; gap: 10px;">
    <a href="purchase-orders.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 6px;">
      <i data-lucide="file-text" style="width: 16px; height: 16px;"></i>
      <span>Purchase Orders & Inward</span>
    </a>
    <button onclick="openAddSupplierModal()" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
      <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
      <span>+ Add New Supplier</span>
    </button>
  </div>
</div>

<div class="card" style="margin-bottom: 20px; padding: 14px 18px;">
  <div style="display: flex; gap: 12px; align-items: center;">
    <div style="position: relative; flex: 1;">
      <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9CA3AF;"></i>
      <input type="text" id="supplierSearch" class="form-control" placeholder="Search supplier name, city, contact or metal speciality..." style="padding-left: 38px;" oninput="filterSuppliers()">
    </div>
  </div>
</div>

<div class="table-container">
  <table class="data-table" id="suppliersTable">
    <thead>
      <tr>
        <th>Supplier Name</th>
        <th>Metal Speciality</th>
        <th>City Hub</th>
        <th>Contact Number</th>
        <th>Vendor Rating</th>
        <th>Last Consignment</th>
        <th style="text-align: right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($suppliers)): ?>
        <tr><td colspan="7" style="text-align: center; color: #9CA3AF; padding: 36px;">No suppliers found. Click "+ Add New Supplier" to create your first vendor.</td></tr>
      <?php else: ?>
        <?php foreach ($suppliers as $s): ?>
          <tr class="supplier-row">
            <td>
              <div style="font-weight: 800; color: #111827; font-size: 14px;"><?= htmlspecialchars($s['name']) ?></div>
              <div style="font-size: 11px; color: #6B7280;">ID: #SUP-<?= str_pad($s['id'], 3, '0', STR_PAD_LEFT) ?></div>
            </td>
            <td><span class="badge badge-gold" style="font-weight: 700;"><?= htmlspecialchars($s['metal_type']) ?></span></td>
            <td style="font-weight: 600; color: #374151;"><?= htmlspecialchars($s['city']) ?></td>
            <td>
              <a href="tel:<?= htmlspecialchars($s['contact']) ?>" style="font-weight: 700; color: var(--color-primary); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                <i data-lucide="phone" style="width: 13px; height: 13px;"></i>
                <?= htmlspecialchars($s['contact']) ?>
              </a>
            </td>
            <td><span class="badge badge-warning" style="font-weight: 700;">★ <?= number_format((float)$s['rating'], 1) ?></span></td>
            <td style="color: #6B7280; font-size: 12.5px;"><?= htmlspecialchars($s['last_order'] ?? 'Recent') ?></td>
            <td style="text-align: right;">
              <a href="purchase-orders.php?supplier=<?= urlencode($s['name']) ?>" class="btn btn-ghost btn-sm" style="color: var(--color-primary); font-weight: 700; padding: 4px 8px; font-size: 12px;">
                Create PO
              </a>
              <button onclick="deleteSupplier(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')" class="btn btn-ghost btn-sm" style="color: #EF4444; padding: 4px 8px;" title="Delete Supplier">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 480px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">🏢</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Add New Supplier</h3>
      </div>
      <button type="button" onclick="closeAddSupplierModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleSaveSupplier(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Supplier / Merchant Name *</label>
          <input type="text" id="suppName" class="form-control" required placeholder="e.g. Shree Ganesh Bullion House">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Contact Mobile *</label>
            <input type="tel" id="suppContact" class="form-control" placeholder="9820011223" required>
          </div>
          <div class="form-group">
            <label class="form-label">City Hub *</label>
            <input type="text" id="suppCity" class="form-control" value="Mumbai" required>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Metal Speciality *</label>
            <select id="suppMetal" class="form-control" required>
              <option value="Gold 24K/22K Bullion">Gold 24K/22K Bullion</option>
              <option value="Certified Solitaires & Diamonds">Certified Solitaires & Diamonds</option>
              <option value="925 Sterling Silver & Utensils">925 Sterling Silver & Utensils</option>
              <option value="Hallmark Casting & Ornaments">Hallmark Casting & Ornaments</option>
              <option value="Platinum & Gemstones">Platinum & Gemstones</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Vendor Rating</label>
            <select id="suppRating" class="form-control">
              <option value="5.0">5.0 ★ (Top Tier)</option>
              <option value="4.8" selected>4.8 ★ (Reliable)</option>
              <option value="4.5">4.5 ★ (Good)</option>
              <option value="4.0">4.0 ★ (Standard)</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeAddSupplierModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Supplier</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddSupplierModal() {
  document.getElementById('addSupplierModal').classList.add('active');
  document.getElementById('suppName').focus();
}

function closeAddSupplierModal() {
  document.getElementById('addSupplierModal').classList.remove('active');
}

function filterSuppliers() {
  const q = document.getElementById('supplierSearch').value.toLowerCase();
  const rows = document.querySelectorAll('.supplier-row');
  rows.forEach(r => {
    const text = r.innerText.toLowerCase();
    r.style.display = text.includes(q) ? '' : 'none';
  });
}

async function handleSaveSupplier(e) {
  e.preventDefault();
  const payload = {
    name: document.getElementById('suppName').value.trim(),
    contact: document.getElementById('suppContact').value.trim(),
    city: document.getElementById('suppCity').value.trim(),
    metal_type: document.getElementById('suppMetal').value,
    rating: parseFloat(document.getElementById('suppRating').value) || 5.0
  };

  try {
    const res = await fetch('api/suppliers.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert('✓ Supplier added successfully!');
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to create supplier'));
    }
  } catch (err) {
    alert('Server connection error');
  }
}

async function deleteSupplier(id, name) {
  if (!confirm(`Are you sure you want to delete supplier "${name}"?`)) return;
  try {
    const res = await fetch(`api/suppliers.php?action=delete&id=${id}`);
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert('Error deleting supplier');
    }
  } catch (e) {
    alert('Server error');
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

