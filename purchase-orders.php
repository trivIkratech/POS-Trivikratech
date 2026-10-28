<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Supplier Purchase Orders & Stock Import - " . htmlspecialchars(getSetting('store_name', 'Kulthe Suvarnkar'));
$db = getDBConnection();

// Fetch all POs with linked product details
$pos = [];
try {
    $stmt = $db->query("
        SELECT po.*, p.name AS product_name, p.sku AS product_sku 
        FROM purchase_orders po
        LEFT JOIN products p ON po.product_id = p.id
        ORDER BY po.ordered_on DESC, po.id DESC
    ");
    $pos = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch active suppliers
$suppliers = [];
try {
    $sStmt = $db->query("SELECT * FROM suppliers ORDER BY name ASC");
    $suppliers = $sStmt->fetchAll();
} catch (Exception $e) {}

// Fetch products for linking
$products = [];
try {
    $pStmt = $db->query("SELECT id, name, sku, metal_type, purity, stock FROM products ORDER BY name ASC");
    $products = $pStmt->fetchAll();
} catch (Exception $e) {}

// Summary Metrics
$totalPOs = count($pos);
$pendingPOs = count(array_filter($pos, fn($p) => $p['status'] === 'Pending'));
$inTransitPOs = count(array_filter($pos, fn($p) => $p['status'] === 'In Transit'));
$receivedPOs = count(array_filter($pos, fn($p) => $p['status'] === 'Received'));
$totalSpend = array_reduce($pos, fn($sum, $p) => $sum + (float)$p['amount'], 0);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 24px;">📦</span>
      <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Purchase Orders & Stock Inward</h1>
    </div>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Manage bullion intake, consignments from suppliers, and import inventory stock into vault</p>
  </div>

  <div style="display: flex; gap: 10px;">
    <button type="button" onclick="openBulkImportModal()" class="btn btn-outline" style="border-color: #0F766E; color: #0F766E; font-weight: 700;">
      <i data-lucide="download" style="width: 16px; height: 16px;"></i>
      <span>Bulk Import Stock</span>
    </button>
    <button type="button" onclick="openNewPOModal()" class="btn btn-primary" style="background: var(--color-primary); font-weight: 700;">
      <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
      <span>+ Create Purchase Order</span>
    </button>
  </div>
</div>

<!-- Metrics Cards -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
  <div class="kpi-card">
    <div class="kpi-value"><?= $totalPOs ?></div>
    <div class="kpi-label">Total Purchase Orders</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value" style="color: #D97706;"><?= $pendingPOs + $inTransitPOs ?></div>
    <div class="kpi-label">Active / In-Transit Consignments</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value" style="color: #059669;"><?= $receivedPOs ?></div>
    <div class="kpi-label">Stock Received & Vaulted</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value" style="color: var(--color-primary);"><?= formatINR($totalSpend) ?></div>
    <div class="kpi-label">Total Procurement Value</div>
  </div>
</div>

<!-- Filter Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 16px; background: var(--color-surface-2); padding: 4px; border-radius: 10px; width: fit-content;">
  <button type="button" class="btn btn-sm po-filter active" data-status="All" onclick="filterPOTable('All', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700; background: #fff; color: var(--color-primary);">All (<?= $totalPOs ?>)</button>
  <button type="button" class="btn btn-sm po-filter" data-status="Pending" onclick="filterPOTable('Pending', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 600; background: transparent; color: var(--color-text-2);">Pending (<?= $pendingPOs ?>)</button>
  <button type="button" class="btn btn-sm po-filter" data-status="In Transit" onclick="filterPOTable('In Transit', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 600; background: transparent; color: var(--color-text-2);">In Transit (<?= $inTransitPOs ?>)</button>
  <button type="button" class="btn btn-sm po-filter" data-status="Received" onclick="filterPOTable('Received', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 600; background: transparent; color: var(--color-text-2);">Received & Imported (<?= $receivedPOs ?>)</button>
</div>

<!-- Purchase Orders Table -->
<div class="table-container">
  <table class="data-table" id="poTable">
    <thead>
      <tr>
        <th>PO Number</th>
        <th>Supplier</th>
        <th>Metal & Purity</th>
        <th>Linked Item / SKU</th>
        <th>Weight & Qty</th>
        <th>Procurement Cost</th>
        <th>Order Dates</th>
        <th>Status</th>
        <th style="text-align: right;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($pos)): ?>
        <tr><td colspan="9" style="text-align: center; color: #9CA3AF; padding: 36px;">No purchase orders found. Click "+ Create Purchase Order" to create one.</td></tr>
      <?php else: ?>
        <?php foreach ($pos as $p): 
          $isReceived = $p['status'] === 'Received';
        ?>
          <tr class="po-row" data-status="<?= htmlspecialchars($p['status']) ?>">
            <td>
              <div style="font-weight: 800; color: var(--color-primary); font-size: 13.5px;"><?= htmlspecialchars($p['po_no']) ?></div>
              <div style="font-size: 11px; color: #6B7280;"><?= date('d M Y', strtotime($p['ordered_on'])) ?></div>
            </td>
            <td>
              <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($p['supplier_name']) ?></div>
            </td>
            <td>
              <span class="badge" style="background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A;">
                <?= htmlspecialchars($p['metal_type']) ?>
              </span>
            </td>
            <td>
              <?php if (!empty($p['product_name'])): ?>
                <div style="font-weight: 700; font-size: 12.5px;"><?= htmlspecialchars($p['product_name']) ?></div>
                <div style="font-size: 11px; color: #0F766E; font-weight: 600;">SKU: <?= htmlspecialchars($p['product_sku']) ?></div>
              <?php else: ?>
                <span style="color: #9CA3AF; font-size: 12px;">Raw Lot Consignment</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-weight: 700;"><?= htmlspecialchars($p['weight']) ?></div>
              <div style="font-size: 11px; color: #6B7280;"><?= (int)($p['quantity'] ?? 1) ?> units</div>
            </td>
            <td>
              <div style="font-weight: 800; font-size: 14px; color: var(--color-primary-dark);"><?= formatFullINR($p['amount']) ?></div>
            </td>
            <td>
              <div style="font-size: 12px; color: #374151;">Ordered: <strong><?= date('d/m/Y', strtotime($p['ordered_on'])) ?></strong></div>
              <div style="font-size: 11.5px; color: #6B7280;">Due: <?= date('d/m/Y', strtotime($p['expected_on'])) ?></div>
            </td>
            <td>
              <?php if ($isReceived): ?>
                <span class="badge badge-success" style="padding: 4px 8px; font-size: 11.5px;">
                  ✓ Received & Vaulted
                </span>
              <?php elseif ($p['status'] === 'In Transit'): ?>
                <span class="badge badge-info" style="padding: 4px 8px; font-size: 11.5px;">
                  🚚 In Transit
                </span>
              <?php else: ?>
                <span class="badge badge-warning" style="padding: 4px 8px; font-size: 11.5px;">
                  ⏳ Pending
                </span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                <?php if (!$isReceived): ?>
                  <button type="button" onclick="receivePOStock(<?= $p['id'] ?>, '<?= htmlspecialchars($p['po_no']) ?>')" class="btn btn-sm btn-primary" style="background: #059669; border-color: #059669; padding: 4px 10px; font-size: 11.5px; font-weight: 700;" title="Receive delivery & import to live inventory">
                    📥 Receive Stock
                  </button>
                <?php endif; ?>
                
                <button type="button" onclick="viewPODetails(<?= htmlspecialchars(json_encode($p)) ?>)" class="btn btn-ghost btn-sm" style="padding: 4px 8px;" title="View PO Details">
                  <i data-lucide="eye" style="width: 15px; height: 15px; color: #0F766E;"></i>
                </button>
                <button type="button" onclick="deletePO(<?= $p['id'] ?>)" class="btn btn-ghost btn-sm" style="padding: 4px 8px;" title="Delete PO">
                  <i data-lucide="trash-2" style="width: 15px; height: 15px; color: #DC2626;"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Create Purchase Order Modal -->
<div id="newPOModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 560px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">📝</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Create Purchase Order & Inward</h3>
      </div>
      <button type="button" onclick="closeNewPOModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleSavePO(event)">
      <div class="modal-body">
        <!-- Supplier Select -->
        <div class="form-group">
          <label class="form-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span>Select Supplier *</span>
            <span style="font-size: 11.5px; color: #6B7280;">Choose existing or add new</span>
          </label>
          <div style="display: flex; gap: 8px;">
            <select id="poSupplier" class="form-control" required style="font-weight: 600; flex: 1;">
              <option value="">-- Choose / Select Supplier --</option>
              <?php foreach ($suppliers as $s): ?>
                <option value="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['metal_type'] ?? 'Bullion') ?> · <?= htmlspecialchars($s['city']) ?>)</option>
              <?php endforeach; ?>
              <option value="Rajkot Gold House">Rajkot Gold House (Rajkot)</option>
              <option value="Surat Diamond Mart">Surat Diamond Mart (Surat)</option>
              <option value="Zaveri Bullion Mumbai">Zaveri Bullion Mumbai (Mumbai)</option>
            </select>
            <button type="button" onclick="openQuickSupplierModal()" class="btn btn-secondary" style="display: flex; align-items: center; gap: 6px; white-space: nowrap; font-weight: 700; background: #ECFDF5; border: 1.5px dashed var(--color-primary); color: var(--color-primary); padding: 0 14px; cursor: pointer;">
              <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
              <span>+ Add Supplier</span>
            </button>
          </div>
        </div>

        <!-- Linked Product (Optional) -->
        <div class="form-group">
          <label class="form-label">Link to Inventory Product (Optional to auto-update stock)</label>
          <select id="poProduct" class="form-control" onchange="handleProductSelect(this)">
            <option value="">-- Raw / Non-Inventory Bulk Lot --</option>
            <?php foreach ($products as $pr): ?>
              <option value="<?= $pr['id'] ?>" data-metal="<?= htmlspecialchars($pr['metal_type']) ?>" data-purity="<?= htmlspecialchars($pr['purity']) ?>">
                <?= htmlspecialchars($pr['name']) ?> (SKU: <?= htmlspecialchars($pr['sku']) ?> · Current Stock: <?= (int)$pr['stock'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Metal Type & Purity -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Metal Spec / Commodity *</label>
            <select id="poMetal" class="form-control" required>
              <option value="Gold 24K">Gold 24K (99.9% Pure)</option>
              <option value="Gold 22K" selected>Gold 22K (91.6% Hallmark)</option>
              <option value="Gold 18K">Gold 18K (75.0%)</option>
              <option value="Silver 999">Silver 999 (Pure Bar)</option>
              <option value="Silver 925">Silver 92.5 (Sterling)</option>
              <option value="Diamond VVS">Diamond VVS Certified</option>
              <option value="Platinum Pt950">Platinum Pt950</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Quantity (Units/Pieces) *</label>
            <input type="number" id="poQty" class="form-control" value="1" min="1" required oninput="calculatePOAmount()">
          </div>
        </div>

        <!-- Weight & Amount -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Weight / Gross Lot (e.g. 50g / 2.5ct) *</label>
            <input type="text" id="poWeight" class="form-control" required placeholder="e.g. 50.000g" oninput="calculatePOAmount()">
          </div>
          <div class="form-group">
            <label class="form-label">Total Procurement Amount (₹) *</label>
            <input type="number" id="poAmount" class="form-control" required placeholder="332500" style="font-weight: 800; color: var(--color-primary-dark); background: #F0FDFA;">
          </div>
        </div>

        <!-- Dates -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Ordered Date</label>
            <input type="date" id="poOrderedDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Expected Delivery</label>
            <input type="date" id="poExpectedDate" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
          </div>
        </div>

        <!-- Status & Import to Live Stock Option -->
        <div class="form-group">
          <label class="form-label">Consignment Status</label>
          <select id="poStatus" class="form-control">
            <option value="Pending">⏳ Pending Supplier Dispatch</option>
            <option value="In Transit">🚚 In Transit / Customs</option>
            <option value="Received">✓ Received & Ready to Vault</option>
          </select>
        </div>

        <div style="background: #F0FDFA; border: 1px solid #CCFBF1; border-radius: 8px; padding: 10px 14px; margin-top: 6px;">
          <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #0F766E; cursor: pointer; margin: 0;">
            <input type="checkbox" id="poAutoImport" style="width: 16px; height: 16px;">
            <span>Automatically Import & Increase Product Stock in Vault</span>
          </label>
          <div style="font-size: 11px; color: #6B7280; margin-top: 2px; padding-left: 24px;">
            If checked, inventory stock will immediately increment upon saving.
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeNewPOModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">Generate Purchase Order</button>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Stock Import Modal -->
<div id="bulkImportModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 600px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">📥</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Bulk Import Inventory Stock</h3>
      </div>
      <button type="button" onclick="closeBulkImportModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleBulkImport(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Batch Supplier / Source Name</label>
          <input type="text" id="bulkSupplierName" class="form-control" value="Direct Vault Inward" required>
        </div>

        <div class="form-group">
          <label class="form-label">Paste Stock Items (JSON or Quick Data Format) *</label>
          <textarea id="bulkDataInput" class="form-control" rows="8" style="font-family: monospace; font-size: 12px;" required placeholder='[
  {
    "sku": "GN-101",
    "name": "22K Traditional Jhumka",
    "metal_type": "Gold",
    "purity": "22K",
    "net_weight": 14.500,
    "stock": 5,
    "purchase_price": 85000,
    "selling_price": 98500,
    "category": "Earrings"
  },
  {
    "sku": "SB-202",
    "name": "925 Silver Filigree Anklet",
    "metal_type": "Silver",
    "purity": "925",
    "net_weight": 35.000,
    "stock": 10,
    "purchase_price": 2800,
    "selling_price": 3800,
    "category": "Anklets"
  }
]'></textarea>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
          <button type="button" onclick="loadSampleBulkData()" class="btn btn-ghost btn-sm" style="font-size: 11.5px; color: var(--color-primary); padding: 0;">
            Load Sample Gold & Silver Consignment
          </button>
          <span style="font-size: 11.5px; color: #6B7280;">Auto-creates PO ledger record</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeBulkImportModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">Import into Inventory Vault</button>
      </div>
    </form>
  </div>
</div>

<!-- View PO Memo Modal -->
<div id="viewPOModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 500px;">
    <div class="modal-header">
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;" id="viewPOTitle">Purchase Order Details</h3>
      <button type="button" onclick="closeViewPOModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <div class="modal-body" id="viewPOContent" style="font-size: 13px;">
      <!-- Generated via JS -->
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeViewPOModal()" class="btn btn-ghost">Close</button>
      <button type="button" onclick="window.print()" class="btn btn-primary"><i data-lucide="printer" style="width: 15px; height: 15px;"></i> Print PO</button>
    </div>
  </div>
</div>

<!-- Quick Add Supplier Modal -->
<div id="quickSupplierModal" class="modal-backdrop" style="z-index: 1050;">
  <div class="modal-dialog" style="max-width: 480px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">🏢</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Add New Bullion / Diamond Supplier</h3>
      </div>
      <button type="button" onclick="closeQuickSupplierModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleQuickCreateSupplier(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Supplier / Merchant Name *</label>
          <input type="text" id="newSuppName" class="form-control" required placeholder="e.g. Shree Ganesh Bullion House">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Contact Mobile / Phone</label>
            <input type="tel" id="newSuppContact" class="form-control" placeholder="9820011223">
          </div>
          <div class="form-group">
            <label class="form-label">City Hub *</label>
            <input type="text" id="newSuppCity" class="form-control" value="Mumbai" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Metal Speciality / Category</label>
          <select id="newSuppMetal" class="form-control">
            <option value="Gold 24K/22K Bullion">Gold 24K/22K Bullion</option>
            <option value="Certified Solitaires & Diamonds">Certified Solitaires & Diamonds</option>
            <option value="925 Sterling Silver & Utensils">925 Sterling Silver & Utensils</option>
            <option value="Hallmark Casting & Ornaments">Hallmark Casting & Ornaments</option>
            <option value="Platinum & Gemstones">Platinum & Gemstones</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeQuickSupplierModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">Save & Select Supplier</button>
      </div>
    </form>
  </div>
</div>

<script>
function filterPOTable(status, btnEl) {
  document.querySelectorAll('.po-filter').forEach(b => {
    b.style.background = 'transparent';
    b.style.color = 'var(--color-text-2)';
    b.classList.remove('active');
  });
  btnEl.style.background = '#fff';
  btnEl.style.color = 'var(--color-primary)';
  btnEl.classList.add('active');

  document.querySelectorAll('.po-row').forEach(row => {
    const s = row.getAttribute('data-status');
    if (status === 'All' || s === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

function openNewPOModal() {
  document.getElementById('newPOModal').classList.add('show');
}
function closeNewPOModal() {
  document.getElementById('newPOModal').classList.remove('show');
}

function openBulkImportModal() {
  document.getElementById('bulkImportModal').classList.add('show');
}
function closeBulkImportModal() {
  document.getElementById('bulkImportModal').classList.remove('show');
}

function handleProductSelect(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt.value) {
    const metal = opt.getAttribute('data-metal');
    const purity = opt.getAttribute('data-purity');
    if (metal) {
      const metalSel = document.getElementById('poMetal');
      for (let i = 0; i < metalSel.options.length; i++) {
        if (metalSel.options[i].value.includes(metal) || metalSel.options[i].value.includes(purity)) {
          metalSel.selectedIndex = i;
          break;
        }
      }
    }
  }
}

function calculatePOAmount() {
  const weightStr = document.getElementById('poWeight').value || '';
  const weightNum = parseFloat(weightStr) || 0;
  const qty = parseInt(document.getElementById('poQty').value) || 1;
  const metal = document.getElementById('poMetal').value;

  let ratePerGram = 6650;
  if (metal.includes('24K')) ratePerGram = 7250;
  else if (metal.includes('18K')) ratePerGram = 5438;
  else if (metal.includes('Silver')) ratePerGram = 88;

  if (weightNum > 0 && !document.getElementById('poAmount').value) {
    document.getElementById('poAmount').value = Math.round(weightNum * ratePerGram * qty);
  }
}

async function handleSavePO(e) {
  e.preventDefault();
  const payload = {
    supplier_name: document.getElementById('poSupplier').value,
    product_id: document.getElementById('poProduct').value || null,
    metal_type: document.getElementById('poMetal').value,
    weight: document.getElementById('poWeight').value,
    quantity: parseInt(document.getElementById('poQty').value) || 1,
    amount: parseFloat(document.getElementById('poAmount').value) || 0,
    ordered_on: document.getElementById('poOrderedDate').value,
    expected_on: document.getElementById('poExpectedDate').value,
    status: document.getElementById('poStatus').value,
    import_stock: document.getElementById('poAutoImport').checked
  };

  try {
    const res = await fetch('api/purchase_orders.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      showToast('Purchase Order generated successfully!', 'success');
      setTimeout(() => location.reload(), 500);
    } else {
      alert(data.error || 'Failed to create PO');
    }
  } catch(err) {
    console.error(err);
    alert('Server error creating PO');
  }
}

async function receivePOStock(id, poNo) {
  if (!confirm(`Confirm receipt and import stock into inventory vault for PO ${poNo}?`)) return;

  try {
    const res = await fetch('api/purchase_orders.php?action=receive_stock', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || 'Stock successfully imported into vault!', 'success');
      setTimeout(() => location.reload(), 600);
    } else {
      alert(data.error || 'Failed to receive stock');
    }
  } catch(err) {
    console.error(err);
    alert('Server error processing stock receipt');
  }
}

async function deletePO(id) {
  if (!confirm('Are you sure you want to delete this purchase order?')) return;
  try {
    const res = await fetch(`api/purchase_orders.php?action=delete&id=${id}`);
    const data = await res.json();
    if (data.success) {
      showToast('PO deleted', 'info');
      setTimeout(() => location.reload(), 400);
    } else {
      alert(data.error || 'Failed to delete PO');
    }
  } catch(err) {
    console.error(err);
  }
}

function viewPODetails(po) {
  document.getElementById('viewPOTitle').innerText = `Purchase Order ${po.po_no}`;
  const content = document.getElementById('viewPOContent');
  content.innerHTML = `
    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 14px; margin-bottom: 14px;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
        <span style="color: #64748B;">Supplier:</span>
        <span style="font-weight: 700; color: #111827;">${po.supplier_name}</span>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
        <span style="color: #64748B;">Metal Specification:</span>
        <span style="font-weight: 700; color: #D97706;">${po.metal_type}</span>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
        <span style="color: #64748B;">Weight & Quantity:</span>
        <span style="font-weight: 700;">${po.weight} (${po.quantity || 1} units)</span>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
        <span style="color: #64748B;">Procurement Amount:</span>
        <span style="font-weight: 800; color: #0F766E; font-size: 15px;">₹${parseFloat(po.amount).toLocaleString('en-IN')}</span>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: #64748B;">Status:</span>
        <span style="font-weight: 700; color: ${po.status === 'Received' ? '#059669' : '#D97706'};">${po.status}</span>
      </div>
    </div>
    <div style="font-size: 11.5px; color: #64748B;">
      Ordered on: ${po.ordered_on} · Expected Delivery: ${po.expected_on}
    </div>
  `;
  document.getElementById('viewPOModal').classList.add('show');
  if (typeof lucide !== 'undefined') lucide.createIcons();
}
function closeViewPOModal() {
  document.getElementById('viewPOModal').classList.remove('show');
}

function loadSampleBulkData() {
  const sample = [
    {
      "sku": "GN-" + Math.floor(Math.random()*9000 + 1000),
      "name": "22K Designer Gold Bangle",
      "metal_type": "Gold",
      "purity": "22K",
      "net_weight": 22.500,
      "stock": 4,
      "purchase_price": 140000,
      "selling_price": 162000,
      "category": "Bangles"
    },
    {
      "sku": "SB-" + Math.floor(Math.random()*9000 + 1000),
      "name": "925 Pure Silver Pooja Diya",
      "metal_type": "Silver",
      "purity": "925",
      "net_weight": 45.000,
      "stock": 8,
      "purchase_price": 3600,
      "selling_price": 4950,
      "category": "Pooja Utensils"
    }
  ];
  document.getElementById('bulkDataInput').value = JSON.stringify(sample, null, 2);
}

async function handleBulkImport(e) {
  e.preventDefault();
  let items = [];
  try {
    items = JSON.parse(document.getElementById('bulkDataInput').value);
  } catch(err) {
    alert('Invalid JSON format. Please verify the stock items data.');
    return;
  }

  const supplier = document.getElementById('bulkSupplierName').value.trim();

  try {
    const res = await fetch('api/purchase_orders.php?action=bulk_import', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ supplier_name: supplier, items })
    });
    const data = await res.json();
    if (data.success) {
      showToast(`Successfully imported ${data.imported_count} stock units!`, 'success');
      setTimeout(() => location.reload(), 600);
    } else {
      alert(data.error || 'Failed to import stock');
    }
  } catch(err) {
    console.error(err);
    alert('Server error during bulk stock import');
  }
}

function openQuickSupplierModal() {
  document.getElementById('quickSupplierModal').classList.add('show');
  setTimeout(() => {
    const input = document.getElementById('newSuppName');
    if (input) input.focus();
  }, 100);
}
function closeQuickSupplierModal() {
  document.getElementById('quickSupplierModal').classList.remove('show');
}

async function handleQuickCreateSupplier(e) {
  e.preventDefault();
  const name = document.getElementById('newSuppName').value.trim();
  const contact = document.getElementById('newSuppContact').value.trim();
  const city = document.getElementById('newSuppCity').value.trim();
  const metalType = document.getElementById('newSuppMetal').value;

  try {
    const res = await fetch('api/suppliers.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, contact, city, metal_type: metalType })
    });
    const data = await res.json();
    if (data.success) {
      // Add new option to poSupplier select and select it
      const select = document.getElementById('poSupplier');
      const opt = document.createElement('option');
      opt.value = name;
      opt.text = `${name} (${metalType} · ${city})`;
      opt.selected = true;
      select.insertBefore(opt, select.firstChild.nextSibling);
      select.value = name;

      closeQuickSupplierModal();
      document.getElementById('newSuppName').value = '';
      document.getElementById('newSuppContact').value = '';
      showToast(`Supplier ${name} added and selected!`, 'success');
    } else {
      alert(data.error || 'Failed to add supplier.');
    }
  } catch(err) {
    console.error(err);
    alert('Server error creating supplier.');
  }
}

// Auto-open modal if URL has supplier or create parameter
window.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const supp = urlParams.get('supplier');
  const create = urlParams.get('create');
  if (supp || create) {
    openNewPOModal();
    if (supp) {
      const select = document.getElementById('poSupplier');
      if (select) {
        select.value = supp;
        // If not found in select list, create it and select
        if (!select.value) {
          const opt = document.createElement('option');
          opt.value = supp;
          opt.text = supp;
          opt.selected = true;
          select.appendChild(opt);
          select.value = supp;
        }
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
