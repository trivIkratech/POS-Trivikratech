<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Diamond Inventory - Trivikratech POS";
$db = getDBConnection();

$products = [];
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM products WHERE metal_type = 'Diamond' ORDER BY name ASC");
    $products = $stmt->fetchAll();

    $catStmt = $db->query("SELECT * FROM categories WHERE metal_type = 'Diamond' ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (Exception $e) {}

$gold18kRate = (float)getSetting('rate_gold_18k', '5437');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 24px;">💎</span>
      <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Certified Solitaires & Diamond Jewellery</h1>
    </div>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Manage VVS/VS diamond rings, pendants, earrings & certified solitaires</p>
  </div>

  <button type="button" onclick="openAddDiamondModal()" class="btn btn-primary" style="background: #0284C7; border-color: #0284C7; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);">
    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
    <span>+ Add Diamond Product</span>
  </button>
</div>

<!-- Quick Diamond Metrics -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr);">
  <div class="kpi-card">
    <div class="kpi-value" style="color: #0284C7;"><?= count($products) ?></div>
    <div class="kpi-label">Diamond SKUs</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= number_format(array_reduce($products, fn($s, $p) => $s + (int)$p['stock'], 0)) ?> pcs</div>
    <div class="kpi-label">Units in Vault</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= number_format(array_reduce($products, fn($s, $p) => $s + ((float)$p['net_weight'] * (int)$p['stock']), 0), 2) ?> g</div>
    <div class="kpi-label">Total Gold Mount Weight</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= formatFullINR(array_reduce($products, fn($s, $p) => $s + ((float)$p['selling_price'] * (int)$p['stock']), 0)) ?></div>
    <div class="kpi-label">Diamond Inventory Valuation</div>
  </div>
</div>

<!-- Product Table -->
<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>SKU / Cert</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Diamond Spec</th>
        <th>Gold Mount</th>
        <th>Making Charges</th>
        <th>Selling Price</th>
        <th>Stock Qty</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($products)): ?>
        <tr><td colspan="9" style="text-align: center; color: #9CA3AF; padding: 36px;">No diamond items in vault. Click "+ Add Diamond Product" to add stock.</td></tr>
      <?php else: ?>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($p['sku']) ?></div>
              <div style="font-size: 11px; color: #0284C7; font-weight: 600;"><?= htmlspecialchars($p['purity']) ?></div>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;">💍</span>
                <div>
                  <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($p['name']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge" style="background: #F3F4F6; color: #374151; font-weight: 600;">
                <?= htmlspecialchars($p['category']) ?>
              </span>
            </td>
            <td><span class="badge badge-diamond"><?= htmlspecialchars($p['stone_details'] ?? 'Certified Diamond') ?></span></td>
            <td style="font-weight: 600;"><?= $p['net_weight'] ?> g</td>
            <td>
              <div style="color: #0F766E; font-weight: 700;"><?= formatFullINR($p['making_charges']) ?></div>
              <?php if (!empty($p['making_charge_type'])): ?>
                <div style="font-size: 10.5px; color: #6B7280;">
                  <?= $p['making_charge_type'] === 'percentage' ? ((float)$p['making_charge_value'] . '% on Mount') : ($p['making_charge_type'] === 'fixed' ? 'Fixed Price' : '₹' . (float)$p['making_charge_value'] . '/g') ?>
                </div>
              <?php endif; ?>
            </td>
            <td style="font-weight: 800; color: var(--color-primary-dark);"><?= formatFullINR($p['selling_price']) ?></td>
            <td>
              <span class="badge <?= (int)$p['stock'] <= (int)$p['min_stock'] ? 'badge-danger' : 'badge-success' ?>">
                <?= (int)$p['stock'] ?> pcs
              </span>
            </td>
            <td>
              <div style="display: flex; gap: 6px;">
                <button type="button" onclick="deleteProduct(<?= $p['id'] ?>)" class="btn btn-ghost btn-sm" style="color: #DC2626; padding: 4px 8px;" title="Delete Item">
                  <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Add Diamond Product Modal -->
<div id="addDiamondModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 530px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">💎</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Add New Diamond Product</h3>
      </div>
      <button type="button" onclick="closeAddDiamondModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleSaveDiamondProduct(event)">
      <input type="hidden" id="diaMetalType" value="Diamond">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Diamond Ornament Name *</label>
          <input type="text" id="diaName" class="form-control" required placeholder="e.g. 18K Solitaire Diamond Ring (0.75ct)" oninput="autoSuggestDiamondSKU(this.value)">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <!-- Category Select (Auto-applies default making charges) -->
          <div class="form-group">
            <label class="form-label">Category * <span style="font-size: 11px; color: #0F766E; font-weight: 700;">(Auto-Loads)</span></label>
            <select id="diaCategory" class="form-control" required onchange="handleDiaCategoryChange(this)" style="font-weight: 600;">
              <?php if (empty($categories)): ?>
                <option value="Ring" data-making="850" data-type="per_gram">Ring (₹850/g)</option>
                <option value="Pendant" data-making="950" data-type="per_gram">Pendant (₹950/g)</option>
                <option value="Earring" data-making="1200" data-type="per_gram">Earring (₹1,200/g)</option>
              <?php else: ?>
                <?php foreach ($categories as $c): 
                  $cType = $c['making_charge_type'] ?? 'per_gram';
                  $cVal = (float)$c['default_making_charge'];
                  $displayLabel = $c['name'] . ' (' . ($cType === 'percentage' ? $cVal . '% on Gold' : ($cType === 'fixed' ? '₹' . number_format($cVal) . ' Flat' : '₹' . number_format($cVal) . '/g')) . ')';
                ?>
                  <option value="<?= htmlspecialchars($c['name']) ?>" data-making="<?= $cVal ?>" data-type="<?= $cType ?>">
                    <?= htmlspecialchars($displayLabel) ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Diamond Clarity & Cut *</label>
            <select id="diaClarity" class="form-control" style="font-weight: 600;">
              <option value="18K + VVS1 (EF Color)" selected>18K + VVS1 (EF Color)</option>
              <option value="18K + VS-SI (GH Color)">18K + VS-SI (GH Color)</option>
              <option value="14K + SI (IJ Color)">14K + SI (IJ Color)</option>
              <option value="Pt950 + VVS Solitaire">Pt950 + VVS Solitaire</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Stone Details / Carat Weight</label>
            <input type="text" id="diaStone" class="form-control" placeholder="e.g. 0.85ct VVS1 Solitaire" style="font-weight: 600;">
          </div>
          <div class="form-group">
            <label class="form-label">Gold Mount (g) *</label>
            <input type="number" step="0.001" id="diaWeight" class="form-control" required placeholder="3.850" oninput="autoCalculateDiamondPrice()" style="font-weight: 700;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">SKU</label>
            <input type="text" id="diaSku" class="form-control" placeholder="DR-088" style="font-weight: 600;">
          </div>
          <div class="form-group">
            <label class="form-label">Stock Quantity</label>
            <input type="number" id="diaStock" class="form-control" value="1" min="1" style="font-weight: 600;">
          </div>
        </div>

        <!-- Making Charge Mode & Input -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Making Calculation Mode</label>
            <select id="diaMakingType" class="form-control" onchange="handleDiaMakingTypeChange(this.value)" style="font-weight: 700; color: #0F766E;">
              <option value="per_gram">Per Gram (₹ / g)</option>
              <option value="percentage">Percentage (% on Mount)</option>
              <option value="fixed">Fixed Price (₹ Flat)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" id="diaMakingLabel">Making Charge Rate (₹/g) *</label>
            <div style="position: relative;">
              <span id="diaMakingPrefix" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #6B7280;">₹</span>
              <input type="number" step="0.01" id="diaMaking" class="form-control" placeholder="850.00" oninput="autoCalculateDiamondPrice()" style="padding-left: 28px; padding-right: 36px; font-weight: 700; color: #0F766E;">
              <span id="diaMakingSuffix" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 11.5px; font-weight: 700; color: #6B7280;">/g</span>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Estimated Selling Price (₹) *</label>
          <input type="number" step="0.01" id="diaPrice" class="form-control" required placeholder="e.g. 145000" style="font-weight: 800; color: var(--color-primary-dark); font-size: 16px; background: #F0FDFA;">
        </div>

        <div id="diaPriceBadge" style="display: none; font-size: 12px; color: #065F46; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; font-weight: 600;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeAddDiamondModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: #0284C7; border-color: #0284C7;">Save to Diamond Vault</button>
      </div>
    </form>
  </div>
</div>

<script>
const GOLD_18K_RATE = <?= $gold18kRate ?>;

function autoSuggestDiamondSKU(name) {
  const sku = document.getElementById('diaSku');
  if (!sku.value) {
    sku.value = 'DR-' + Math.floor(Math.random() * 9000 + 1000);
  }
}

function handleDiaMakingTypeChange(type) {
  const label = document.getElementById('diaMakingLabel');
  const prefix = document.getElementById('diaMakingPrefix');
  const suffix = document.getElementById('diaMakingSuffix');
  const input = document.getElementById('diaMaking');

  if (type === 'percentage') {
    label.innerText = 'Making Percent (% on Mount) *';
    prefix.style.display = 'none';
    input.style.paddingLeft = '12px';
    suffix.innerText = '%';
    suffix.style.display = 'block';
  } else if (type === 'fixed') {
    label.innerText = 'Fixed Making Charge (₹ Flat) *';
    prefix.style.display = 'block';
    prefix.innerText = '₹';
    input.style.paddingLeft = '28px';
    suffix.style.display = 'none';
  } else {
    // per_gram
    label.innerText = 'Making Charge Rate (₹ / gram) *';
    prefix.style.display = 'block';
    prefix.innerText = '₹';
    input.style.paddingLeft = '28px';
    suffix.innerText = '/g';
    suffix.style.display = 'block';
  }
  autoCalculateDiamondPrice();
}

function handleDiaCategoryChange(sel) {
  const selectedOpt = sel.options[sel.selectedIndex];
  if (selectedOpt) {
    const defaultMaking = selectedOpt.getAttribute('data-making');
    const defaultType = selectedOpt.getAttribute('data-type') || 'per_gram';

    document.getElementById('diaMakingType').value = defaultType;
    handleDiaMakingTypeChange(defaultType);

    if (defaultMaking !== null && defaultMaking !== undefined) {
      document.getElementById('diaMaking').value = parseFloat(defaultMaking) || 0;
      autoCalculateDiamondPrice();
    }
  }
}

function autoCalculateDiamondPrice() {
  const weight = parseFloat(document.getElementById('diaWeight').value) || 0;
  const makingType = document.getElementById('diaMakingType').value || 'per_gram';
  const makingVal = parseFloat(document.getElementById('diaMaking').value) || 0;
  
  if (weight > 0 && makingVal > 0) {
    let totalMaking = 0;
    let breakdownText = '';
    const goldMountVal = weight * GOLD_18K_RATE;

    if (makingType === 'percentage') {
      totalMaking = Math.round(goldMountVal * (makingVal / 100));
      breakdownText = `⚡ <strong>Percentage Making:</strong> Gold Mount (${weight}g) + Making (${makingVal}% on Mount) = <strong>₹${totalMaking.toLocaleString('en-IN')}</strong>`;
    } else if (makingType === 'fixed') {
      totalMaking = Math.round(makingVal);
      breakdownText = `⚡ <strong>Fixed Making:</strong> Flat <strong>₹${totalMaking.toLocaleString('en-IN')}</strong> per piece`;
    } else {
      totalMaking = Math.round(weight * makingVal);
      breakdownText = `⚡ <strong>Per-Gram Making:</strong> Gold Mount (${weight}g × ₹${makingVal.toLocaleString('en-IN')}/g) = <strong>₹${totalMaking.toLocaleString('en-IN')}</strong>`;
    }

    const badge = document.getElementById('diaPriceBadge');
    if (badge) {
      badge.style.display = 'block';
      badge.innerHTML = `${breakdownText} total making charges.`;
    }
  }
}

function openAddDiamondModal() {
  document.getElementById('addDiamondModal').classList.add('active');
  
  // Apply category default making charge immediately
  const catSelect = document.getElementById('diaCategory');
  if (catSelect) {
    handleDiaCategoryChange(catSelect);
  }

  setTimeout(() => {
    const nameInput = document.getElementById('diaName');
    if (nameInput) nameInput.focus();
  }, 100);
}

function closeAddDiamondModal() {
  document.getElementById('addDiamondModal').classList.remove('active');
}

async function handleSaveDiamondProduct(e) {
  e.preventDefault();
  const weight = parseFloat(document.getElementById('diaWeight').value) || 0;
  const makingType = document.getElementById('diaMakingType').value || 'per_gram';
  const makingVal = parseFloat(document.getElementById('diaMaking').value) || 0;
  const goldMountVal = weight * GOLD_18K_RATE;

  let totalMaking = 0;
  if (makingType === 'percentage') {
    totalMaking = Math.round(goldMountVal * (makingVal / 100));
  } else if (makingType === 'fixed') {
    totalMaking = Math.round(makingVal);
  } else {
    totalMaking = Math.round(weight * makingVal);
  }

  const payload = {
    name: document.getElementById('diaName').value.trim(),
    sku: document.getElementById('diaSku').value.trim() || ('DR-' + Math.floor(Math.random()*9000 + 1000)),
    metal_type: 'Diamond',
    purity: document.getElementById('diaClarity').value,
    gross_weight: weight,
    net_weight: weight,
    stone_details: document.getElementById('diaStone').value.trim() || 'Certified Diamond',
    making_charges: totalMaking,
    making_charge_type: makingType,
    making_charge_value: makingVal,
    selling_price: parseFloat(document.getElementById('diaPrice').value) || 0,
    purchase_price: (parseFloat(document.getElementById('diaPrice').value) || 0) * 0.75,
    stock: parseInt(document.getElementById('diaStock').value) || 1,
    category: document.getElementById('diaCategory').value || 'Ring'
  };

  try {
    const res = await fetch('api/products.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert('✓ Diamond product added successfully!');
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to add item'));
    }
  } catch(err) {
    alert('Server error adding diamond product');
  }
}

async function deleteProduct(id) {
  if (!confirm('Are you sure you want to delete this diamond product?')) return;
  try {
    const res = await fetch(`api/products.php?action=delete&id=${id}`);
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert('Error deleting product');
    }
  } catch(err) {
    alert('Server error');
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
