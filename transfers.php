<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Inter-Branch Stock Transfers - Trivikratech POS";
$db = getDBConnection();

$transfers = [];
$products = [];
$branches = [];

try {
    $stmt = $db->query("
        SELECT st.*, p.sku, p.metal_type, p.purity, p.net_weight, p.selling_price
        FROM stock_transfers st
        LEFT JOIN products p ON st.product_id = p.id
        ORDER BY st.transfer_date DESC, st.id DESC
    ");
    $transfers = $stmt->fetchAll();

    $stmtP = $db->query("SELECT id, name, sku, metal_type, purity, stock, net_weight, selling_price FROM products WHERE is_active = 1 ORDER BY name ASC");
    $products = $stmtP->fetchAll();

    $stmtB = $db->query("SELECT * FROM branches ORDER BY is_active DESC, name ASC");
    $branches = $stmtB->fetchAll();
} catch (Exception $e) {}

// Stats calculation
$totalCount = count($transfers);
$completedCount = 0;
$inTransitCount = 0;
$totalQty = 0;

foreach ($transfers as $t) {
    if ($t['status'] === 'Completed') {
        $completedCount++;
    } elseif ($t['status'] === 'In Transit' || $t['status'] === 'Pending') {
        $inTransitCount++;
    }
    $totalQty += (int)$t['qty'];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0; display: flex; align-items: center; gap: 10px;">
      <span>Inter-Branch Stock Transfers</span>
      <span class="badge badge-gold" style="font-size: 12px; font-weight: 700; padding: 4px 10px;">Vault & Branch Logistics</span>
    </h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Move jewellery, bullion, and ornaments securely between showroom branches and vault locations</p>
  </div>
  
  <div style="display: flex; gap: 10px;">
    <button onclick="openNewTransferModal()" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px; font-weight: 700; padding: 10px 18px; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25);">
      <i data-lucide="arrow-right-left" style="width: 17px; height: 17px;"></i>
      <span>+ New Branch Transfer</span>
    </button>
  </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="card" style="padding: 16px 20px; border-left: 4px solid var(--color-primary);">
    <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Total Transfers</div>
    <div style="font-size: 24px; font-weight: 800; color: #111827; margin-top: 4px;"><?= $totalCount ?></div>
    <div style="font-size: 11.5px; color: #0F766E; margin-top: 4px; font-weight: 600;">Inter-branch challans generated</div>
  </div>

  <div class="card" style="padding: 16px 20px; border-left: 4px solid #059669;">
    <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Completed / Vaulted</div>
    <div style="font-size: 24px; font-weight: 800; color: #059669; margin-top: 4px;"><?= $completedCount ?></div>
    <div style="font-size: 11.5px; color: #059669; margin-top: 4px; font-weight: 600;">Stock securely received & logged</div>
  </div>

  <div class="card" style="padding: 16px 20px; border-left: 4px solid #D97706;">
    <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">In Transit / Pending</div>
    <div style="font-size: 24px; font-weight: 800; color: #D97706; margin-top: 4px;"><?= $inTransitCount ?></div>
    <div style="font-size: 11.5px; color: #D97706; margin-top: 4px; font-weight: 600;">Out for courier / karigar transit</div>
  </div>

  <div class="card" style="padding: 16px 20px; border-left: 4px solid #3B82F6;">
    <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Total Pieces Moved</div>
    <div style="font-size: 24px; font-weight: 800; color: #3B82F6; margin-top: 4px;"><?= $totalQty ?> pcs</div>
    <div style="font-size: 11.5px; color: #3B82F6; margin-top: 4px; font-weight: 600;">Ornaments & bullion inventory</div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 20px; padding: 14px 18px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <!-- Search Bar -->
    <div style="position: relative; flex: 1; min-width: 260px;">
      <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9CA3AF;"></i>
      <input type="text" id="transferSearch" class="form-control" placeholder="Search Transfer No, Product, or Branch..." style="padding-left: 38px;" oninput="filterTransfers()">
    </div>

    <!-- Status Filter Buttons -->
    <div style="display: flex; gap: 8px;" id="statusFilterGroup">
      <button type="button" class="btn btn-secondary filter-tab active" onclick="filterByStatus('all', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700;">
        All (<?= $totalCount ?>)
      </button>
      <button type="button" class="btn btn-secondary filter-tab" onclick="filterByStatus('In Transit', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700;">
        🚚 In Transit (<?= $inTransitCount ?>)
      </button>
      <button type="button" class="btn btn-secondary filter-tab" onclick="filterByStatus('Completed', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700;">
        ✓ Completed (<?= $completedCount ?>)
      </button>
    </div>
  </div>
</div>

<!-- Table -->
<div class="table-container">
  <table class="data-table" id="transfersTable">
    <thead>
      <tr>
        <th>Transfer No</th>
        <th>Product Name & SKU</th>
        <th>From Branch</th>
        <th style="width: 30px; text-align: center;"></th>
        <th>To Branch</th>
        <th>Quantity</th>
        <th>Status</th>
        <th>Date</th>
        <th style="text-align: right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($transfers)): ?>
        <tr><td colspan="9" style="text-align: center; color: #9CA3AF; padding: 36px;">No inter-branch transfers recorded yet. Click "+ New Branch Transfer" to dispatch stock.</td></tr>
      <?php else: ?>
        <?php foreach ($transfers as $t): 
          $isCompleted = $t['status'] === 'Completed';
          $isInTransit = $t['status'] === 'In Transit';
        ?>
          <tr class="transfer-row" data-status="<?= htmlspecialchars($t['status']) ?>">
            <td>
              <div style="font-weight: 800; color: var(--color-primary); font-size: 13.5px;"><?= htmlspecialchars($t['transfer_no']) ?></div>
              <div style="font-size: 11px; color: #6B7280;"><?= date('d M Y', strtotime($t['transfer_date'])) ?></div>
            </td>
            <td>
              <div style="font-weight: 700; color: #111827; font-size: 13px;"><?= htmlspecialchars($t['product_name']) ?></div>
              <?php if (!empty($t['sku'])): ?>
                <div style="font-size: 11px; color: #0F766E; font-weight: 600;">SKU: <?= htmlspecialchars($t['sku']) ?> · <?= htmlspecialchars($t['metal_type'] ?? '') ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge" style="background: #F1F5F9; color: #475569; font-weight: 700;">
                🏢 <?= htmlspecialchars($t['from_branch']) ?>
              </span>
            </td>
            <td style="text-align: center; color: #94A3B8;">
              <i data-lucide="arrow-right" style="width: 16px; height: 16px; display: inline-block;"></i>
            </td>
            <td>
              <span class="badge" style="background: #ECFDF5; color: #065F46; font-weight: 800; border: 1px solid #A7F3D0;">
                📍 <?= htmlspecialchars($t['to_branch']) ?>
              </span>
            </td>
            <td>
              <div style="font-weight: 800; font-size: 13.5px;"><?= (int)$t['qty'] ?> pcs</div>
            </td>
            <td>
              <?php if ($isCompleted): ?>
                <span class="badge badge-success" style="padding: 4px 10px; font-weight: 700;">
                  ✓ Completed
                </span>
              <?php elseif ($isInTransit): ?>
                <span class="badge badge-info" style="padding: 4px 10px; font-weight: 700; background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;">
                  🚚 In Transit
                </span>
              <?php else: ?>
                <span class="badge badge-warning" style="padding: 4px 10px; font-weight: 700;">
                  ⏳ Pending
                </span>
              <?php endif; ?>
            </td>
            <td style="color: #6B7280; font-size: 12.5px; font-weight: 600;">
              <?= date('d/m/Y', strtotime($t['transfer_date'])) ?>
            </td>
            <td style="text-align: right; white-space: nowrap;">
              <button type="button" class="btn btn-ghost btn-sm" onclick='viewChallan(<?= json_encode($t) ?>)' style="color: var(--color-primary); font-weight: 700; padding: 4px 8px; font-size: 12px;" title="View Transfer Challan">
                <i data-lucide="file-text" style="width: 14px; height: 14px;"></i> Challan
              </button>
              <?php if (!$isCompleted): ?>
                <button type="button" class="btn btn-ghost btn-sm" onclick="markCompleted(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['transfer_no'])) ?>')" style="color: #059669; font-weight: 700; padding: 4px 8px; font-size: 12px;" title="Mark as Received & Completed">
                  <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> Receive
                </button>
              <?php endif; ?>
              <button type="button" class="btn btn-ghost btn-sm" onclick="deleteTransfer(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['transfer_no'])) ?>')" style="color: #EF4444; padding: 4px 8px;" title="Delete Transfer">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- New Branch Transfer Modal -->
<div id="newTransferModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 540px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">🚚</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Create Branch Stock Transfer</h3>
      </div>
      <button type="button" onclick="closeNewTransferModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleSaveTransfer(event)">
      <div class="modal-body">
        <!-- Product Select -->
        <div class="form-group">
          <label class="form-label">Select Product from Vault / Inventory *</label>
          <select id="transProduct" class="form-control" required onchange="handleProductSelect(this)" style="font-weight: 600;">
            <option value="">-- Choose Product to Transfer --</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= $p['id'] ?>" 
                      data-name="<?= htmlspecialchars($p['name']) ?>" 
                      data-stock="<?= (int)$p['stock'] ?>"
                      data-metal="<?= htmlspecialchars($p['metal_type']) ?>"
                      data-sku="<?= htmlspecialchars($p['sku']) ?>">
                <?= htmlspecialchars($p['name']) ?> (SKU: <?= htmlspecialchars($p['sku']) ?> · In Stock: <?= (int)$p['stock'] ?> pcs)
              </option>
            <?php endforeach; ?>
            <option value="custom">-- Other / Unlisted Commodity / Bulk Lot --</option>
          </select>
        </div>

        <div class="form-group" id="customProductGroup" style="display: none;">
          <label class="form-label">Custom Commodity / Item Description *</label>
          <input type="text" id="customProductName" class="form-control" placeholder="e.g. 24K Pure Gold Casting Bars">
        </div>

        <!-- Stock Indicator Banner -->
        <div id="stockNotice" style="display: none; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 12.5px; color: #166534;">
          <strong>Available In Vault:</strong> <span id="vaultStockCount">0</span> pcs available for transfer.
        </div>

        <!-- From and To Branches -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label">Origin Branch (From) *</label>
            <select id="fromBranch" class="form-control" required>
              <option value="Bandra West" selected>Bandra West (Flagship)</option>
              <option value="Andheri East">Andheri East</option>
              <option value="Powai">Powai Showroom</option>
              <option value="South Mumbai Vault">South Mumbai Vault</option>
              <option value="Borivali">Borivali Branch</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Destination Branch (To) *</label>
            <select id="toBranch" class="form-control" required>
              <option value="Andheri East" selected>Andheri East</option>
              <option value="Powai">Powai Showroom</option>
              <option value="Bandra West">Bandra West (Flagship)</option>
              <option value="South Mumbai Vault">South Mumbai Vault</option>
              <option value="Borivali">Borivali Branch</option>
            </select>
          </div>
        </div>

        <!-- Quantity & Date -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label">Transfer Quantity (pcs) *</label>
            <input type="number" id="transQty" class="form-control" value="1" min="1" required style="font-weight: 700;">
          </div>

          <div class="form-group">
            <label class="form-label">Transfer Date *</label>
            <input type="date" id="transDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <!-- Initial Status -->
        <div class="form-group">
          <label class="form-label">Transfer Status *</label>
          <select id="transStatus" class="form-control">
            <option value="Completed" selected>Completed (Immediately Transferred & Logged)</option>
            <option value="In Transit">In Transit (Dispatched via Security Courier)</option>
            <option value="Pending">Pending (Awaiting Dispatch Approval)</option>
          </select>
        </div>

        <!-- Notes -->
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">Gate Pass / Karigar Transit Memo (Optional)</label>
          <textarea id="transNotes" class="form-control" rows="2" placeholder="e.g. Dispatched with Karigar Suresh, Security Seal #8821"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeNewTransferModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
          <i data-lucide="arrow-right-left" style="width: 15px; height: 15px;"></i>
          <span>Confirm & Dispatch Transfer</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- View Challan / Memo Modal -->
<div id="viewChallanModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 520px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">📄</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;" id="challanModalTitle">Stock Transfer Challan</h3>
      </div>
      <button type="button" onclick="closeChallanModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <div class="modal-body" id="challanModalContent">
      <!-- Dynamic via JS -->
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeChallanModal()" class="btn btn-ghost">Close</button>
      <button type="button" onclick="printChallan()" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
        <i data-lucide="printer" style="width: 15px; height: 15px;"></i>
        <span>Print Delivery Challan</span>
      </button>
    </div>
  </div>
</div>

<script>
let currentChallanData = null;

function openNewTransferModal() {
  document.getElementById('newTransferModal').classList.add('active');
  document.getElementById('transProduct').focus();
}

function closeNewTransferModal() {
  document.getElementById('newTransferModal').classList.remove('active');
}

function handleProductSelect(sel) {
  const isCustom = sel.value === 'custom';
  document.getElementById('customProductGroup').style.display = isCustom ? 'block' : 'none';
  
  const notice = document.getElementById('stockNotice');
  if (!isCustom && sel.value) {
    const opt = sel.options[sel.selectedIndex];
    const stock = opt.getAttribute('data-stock') || '0';
    document.getElementById('vaultStockCount').innerText = stock;
    notice.style.display = 'block';
  } else {
    notice.style.display = 'none';
  }
}

function filterTransfers() {
  const q = document.getElementById('transferSearch').value.toLowerCase();
  const activeStatusBtn = document.querySelector('#statusFilterGroup .filter-tab.active');
  const statusFilter = activeStatusBtn ? activeStatusBtn.innerText.toLowerCase() : 'all';

  const rows = document.querySelectorAll('.transfer-row');
  rows.forEach(r => {
    const text = r.innerText.toLowerCase();
    const rowStatus = (r.getAttribute('data-status') || '').toLowerCase();
    
    let matchesStatus = true;
    if (statusFilter.includes('in transit')) {
      matchesStatus = rowStatus === 'in transit' || rowStatus === 'pending';
    } else if (statusFilter.includes('completed')) {
      matchesStatus = rowStatus === 'completed';
    }

    const matchesSearch = text.includes(q);
    r.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
  });
}

function filterByStatus(status, btnEl) {
  document.querySelectorAll('#statusFilterGroup .filter-tab').forEach(b => {
    b.classList.remove('active');
    b.style.background = '';
    b.style.color = '';
  });
  btnEl.classList.add('active');
  btnEl.style.background = 'var(--color-primary)';
  btnEl.style.color = '#FFFFFF';
  filterTransfers();
}

async function handleSaveTransfer(e) {
  e.preventDefault();
  const productSelect = document.getElementById('transProduct');
  const isCustom = productSelect.value === 'custom';
  
  let productId = null;
  let productName = '';
  
  if (isCustom) {
    productName = document.getElementById('customProductName').value.trim();
    if (!productName) {
      alert('Please enter the custom item description');
      return;
    }
  } else {
    productId = productSelect.value;
    const opt = productSelect.options[productSelect.selectedIndex];
    productName = opt.getAttribute('data-name');
  }

  const fromBranch = document.getElementById('fromBranch').value;
  const toBranch = document.getElementById('toBranch').value;

  if (fromBranch === toBranch) {
    alert('Origin (From) and Destination (To) branch cannot be the same!');
    return;
  }

  const payload = {
    product_id: productId,
    product_name: productName,
    from_branch: fromBranch,
    to_branch: toBranch,
    qty: parseInt(document.getElementById('transQty').value) || 1,
    transfer_date: document.getElementById('transDate').value,
    status: document.getElementById('transStatus').value,
    notes: document.getElementById('transNotes').value.trim()
  };

  try {
    const res = await fetch('api/transfers.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert(`✓ Stock transfer ${data.transfer_no} created successfully!`);
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to create transfer'));
    }
  } catch(err) {
    alert('Server connection error');
  }
}

async function markCompleted(id, transferNo) {
  if (!confirm(`Mark Transfer "${transferNo}" as Received & Completed?`)) return;
  try {
    const res = await fetch('api/transfers.php?action=update_status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status: 'Completed' })
    });
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.error || 'Failed to update status');
    }
  } catch (e) {
    alert('Server error');
  }
}

async function deleteTransfer(id, transferNo) {
  if (!confirm(`Are you sure you want to delete transfer "${transferNo}"?`)) return;
  try {
    const res = await fetch(`api/transfers.php?action=delete&id=${id}`);
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.error || 'Failed to delete');
    }
  } catch(e) {
    alert('Server error');
  }
}

function viewChallan(t) {
  currentChallanData = t;
  document.getElementById('challanModalTitle').innerText = `Delivery Challan ${t.transfer_no}`;
  const content = document.getElementById('challanModalContent');
  content.innerHTML = `
    <div style="border: 2px solid #E2E8F0; border-radius: 8px; padding: 18px; background: #FFFFFF;">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #F1F5F9; padding-bottom: 12px; margin-bottom: 14px;">
        <div>
          <div style="font-weight: 800; font-size: 16px; color: #0F766E;">Kulthe Suvarnkar</div>
          <div style="font-size: 11.5px; color: #64748B;">Inter-Branch Stock Delivery Memo</div>
        </div>
        <div style="text-align: right;">
          <div style="font-weight: 800; font-size: 14px; color: #111827;">${t.transfer_no}</div>
          <div style="font-size: 11.5px; color: #64748B;">Date: ${t.transfer_date}</div>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; background: #F8FAFC; padding: 12px; border-radius: 6px;">
        <div>
          <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Dispatched From:</div>
          <div style="font-weight: 700; color: #111827; font-size: 13.5px;">🏢 ${t.from_branch}</div>
        </div>
        <div>
          <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Destination (To):</div>
          <div style="font-weight: 800; color: #059669; font-size: 13.5px;">📍 ${t.to_branch}</div>
        </div>
      </div>

      <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 12.5px;">
        <thead>
          <tr style="background: #F1F5F9; text-align: left;">
            <th style="padding: 8px 10px; border-radius: 4px 0 0 4px;">Item Description</th>
            <th style="padding: 8px 10px; text-align: center;">SKU</th>
            <th style="padding: 8px 10px; text-align: right; border-radius: 0 4px 4px 0;">Qty</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="padding: 10px; border-bottom: 1px solid #E2E8F0; font-weight: 700;">
              ${t.product_name}
              ${t.metal_type ? `<span style="font-size: 11px; color: #D97706; display: block;">${t.metal_type} ${t.purity || ''}</span>` : ''}
            </td>
            <td style="padding: 10px; border-bottom: 1px solid #E2E8F0; text-align: center; color: #64748B; font-weight: 600;">
              ${t.sku || 'N/A'}
            </td>
            <td style="padding: 10px; border-bottom: 1px solid #E2E8F0; text-align: right; font-weight: 800; font-size: 14px;">
              ${t.qty} pcs
            </td>
          </tr>
        </tbody>
      </table>

      <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px dashed #CBD5E1;">
        <div style="text-align: center;">
          <div style="height: 32px; border-bottom: 1px solid #94A3B8; width: 110px; margin-bottom: 4px;"></div>
          <span style="font-size: 10.5px; color: #64748B;">Dispatched By</span>
        </div>
        <div style="text-align: center;">
          <div style="height: 32px; border-bottom: 1px solid #94A3B8; width: 110px; margin-bottom: 4px;"></div>
          <span style="font-size: 10.5px; color: #64748B;">Security Transit Sign</span>
        </div>
        <div style="text-align: center;">
          <div style="height: 32px; border-bottom: 1px solid #94A3B8; width: 110px; margin-bottom: 4px;"></div>
          <span style="font-size: 10.5px; color: #64748B;">Received By (Store)</span>
        </div>
      </div>
    </div>
  `;
  document.getElementById('viewChallanModal').classList.add('active');
  if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeChallanModal() {
  document.getElementById('viewChallanModal').classList.remove('active');
}

function printChallan() {
  if (!currentChallanData) return;
  const t = currentChallanData;
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Transfer Challan ${t.transfer_no}</title>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px; color: #111827; }
        .challan-box { border: 2px solid #000; padding: 24px; max-width: 650px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 20px; }
        .grid { display: flex; justify-content: space-between; margin-bottom: 20px; background: #f3f4f6; padding: 12px; border: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #999; padding: 10px; text-align: left; }
        th { background: #eee; }
        .signs { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 20px; }
        .sign-line { border-top: 1px solid #000; width: 140px; text-align: center; font-size: 12px; padding-top: 4px; }
      </style>
    </head>
    <body>
      <div class="challan-box">
        <div class="header">
          <div>
            <h2 style="margin:0; font-size: 20px;">Kulthe Suvarnkar</h2>
            <p style="margin:4px 0 0; font-size: 13px;">Branch Stock Delivery Challan</p>
          </div>
          <div style="text-align: right;">
            <h3 style="margin:0;">${t.transfer_no}</h3>
            <p style="margin:4px 0 0; font-size: 13px;">Date: ${t.transfer_date}</p>
          </div>
        </div>
        <div class="grid">
          <div><strong>Dispatched From:</strong> ${t.from_branch}</div>
          <div><strong>Destination To:</strong> ${t.to_branch}</div>
        </div>
        <table>
          <thead>
            <tr><th>Item Description</th><th>SKU</th><th style="text-align:right;">Qty</th></tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>${t.product_name}</strong> (${t.metal_type || 'Jewellery'})</td>
              <td>${t.sku || 'N/A'}</td>
              <td style="text-align:right; font-weight: bold;">${t.qty} pcs</td>
            </tr>
          </tbody>
        </table>
        <div class="signs">
          <div class="sign-line">Dispatched By</div>
          <div class="sign-line">Security / Transit</div>
          <div class="sign-line">Received By (Branch)</div>
        </div>
      </div>
      <script>window.onload = function() { window.print(); }<\/script>
    </body>
    </html>
  `);
  printWindow.document.close();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
