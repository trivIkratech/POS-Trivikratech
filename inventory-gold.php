<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Gold Jewellery Inventory - Trivikratech POS";
$db = getDBConnection();

// Fetch all Gold Products & Categories
$products = [];
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM products WHERE metal_type = 'Gold' ORDER BY name ASC");
    $products = $stmt->fetchAll();

    $catStmt = $db->query("SELECT * FROM categories WHERE metal_type = 'Gold' ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 24px;">🟡</span>
      <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Gold Jewellery Vault & Inventory</h1>
    </div>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Manage 24K, 22K & 18K Hallmarked gold ornaments, purity, weight & stock</p>
  </div>

  <button type="button" onclick="openAddProductModal('Gold')" class="btn btn-primary" style="background: #D97706; border-color: #D97706; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);">
    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
    <span>+ Add Gold Product</span>
  </button>
</div>

<!-- Quick Gold Metrics -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr);">
  <div class="kpi-card">
    <div class="kpi-value" style="color: #D97706;"><?= count($products) ?></div>
    <div class="kpi-label">Active Gold SKUs</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= number_format(array_reduce($products, fn($s, $p) => $s + (int)$p['stock'], 0)) ?> pcs</div>
    <div class="kpi-label">Total Units in Stock</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= number_format(array_reduce($products, fn($s, $p) => $s + ((float)$p['net_weight'] * (int)$p['stock']), 0), 2) ?> g</div>
    <div class="kpi-label">Total Gold Vault Weight</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= formatFullINR(array_reduce($products, fn($s, $p) => $s + ((float)$p['selling_price'] * (int)$p['stock']), 0)) ?></div>
    <div class="kpi-label">Total Inventory Valuation</div>
  </div>
</div>

<!-- Product Table -->
<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>SKU / Barcode</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Purity</th>
        <th>Net Weight</th>
        <th>Making Charges</th>
        <th>Selling Price</th>
        <th>Stock Qty</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($products)): ?>
        <tr><td colspan="9" style="text-align: center; color: #9CA3AF; padding: 36px;">No gold items found in inventory. Click "+ Add Gold Product" to add your first piece.</td></tr>
      <?php else: ?>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($p['sku']) ?></div>
              <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($p['barcode'] ?? '') ?></div>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;"><?= $p['image_emoji'] ?? '📿' ?></span>
                <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($p['name']) ?></div>
              </div>
            </td>
            <td>
              <span class="badge" style="background: #F3F4F6; color: #374151; font-weight: 600;">
                <?= htmlspecialchars($p['category']) ?>
              </span>
            </td>
            <td><span class="badge badge-gold"><?= htmlspecialchars($p['purity']) ?></span></td>
            <td style="font-weight: 600;"><?= $p['net_weight'] ?> g</td>
            <td>
              <div style="color: #0F766E; font-weight: 700;"><?= formatFullINR($p['making_charges']) ?></div>
              <?php if (!empty($p['making_charge_type'])): ?>
                <div style="font-size: 10.5px; color: #6B7280;">
                  <?= $p['making_charge_type'] === 'percentage' ? ((float)$p['making_charge_value'] . '% on Gold') : ($p['making_charge_type'] === 'fixed' ? 'Fixed Price' : '₹' . (float)$p['making_charge_value'] . '/g') ?>
                </div>
              <?php endif; ?>
            </td>
            <td style="font-weight: 800; color: var(--color-primary-dark); font-size: 14px;"><?= formatFullINR($p['selling_price']) ?></td>
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

<?php
$gold24k = (float)getSetting('rate_gold_24k', '7250');
$gold22k = (float)getSetting('rate_gold_22k', '6650');
$gold18k = (float)getSetting('rate_gold_18k', round($gold24k * 0.75));
?>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 530px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">🟡</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Add New Gold Product</h3>
      </div>
      <button type="button" onclick="closeAddProductModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleSaveProduct(event)">
      <input type="hidden" id="prodMetalType" value="Gold">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Ornament Name *</label>
          <input type="text" id="prodName" class="form-control" required placeholder="e.g. 22K Bridal Gold Choker Set" oninput="autoSuggestSKU(this.value)">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <!-- Category Select (Auto-applies default making charges) -->
          <div class="form-group">
            <label class="form-label">Category * <span style="font-size: 11px; color: #0F766E; font-weight: 700;">(Auto-Loads)</span></label>
            <select id="prodCategory" class="form-control" required onchange="handleCategoryChange(this)" style="font-weight: 600;">
              <?php if (empty($categories)): ?>
                <option value="Necklace" data-making="650" data-type="per_gram">Necklace (₹650/g)</option>
                <option value="Ring" data-making="550" data-type="per_gram">Ring (₹550/g)</option>
                <option value="Bangle" data-making="750" data-type="per_gram">Bangle (₹750/g)</option>
                <option value="Earring" data-making="600" data-type="per_gram">Earring (₹600/g)</option>
                <option value="Pendant" data-making="550" data-type="per_gram">Pendant (₹550/g)</option>
                <option value="Coin" data-making="150" data-type="per_gram">Coin (₹150/g)</option>
                <option value="Necklace Set" data-making="14" data-type="percentage">Necklace Set (14% of Gold)</option>
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
            <label class="form-label">Purity *</label>
            <select id="prodPurity" class="form-control" onchange="autoCalculateGoldPrice()" style="font-weight: 600;">
              <option value="24K">24K (99.9%) — ₹<?= number_format($gold24k) ?>/g</option>
              <option value="22K" selected>22K (91.6% Hallmark) — ₹<?= number_format($gold22k) ?>/g</option>
              <option value="18K">18K (75.0%) — ₹<?= number_format($gold18k) ?>/g</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">SKU / Code</label>
            <input type="text" id="prodSku" class="form-control" placeholder="GN-015" style="font-weight: 600;">
          </div>
          <div class="form-group">
            <label class="form-label">Stock Quantity</label>
            <input type="number" id="prodStock" class="form-control" value="1" min="1" style="font-weight: 600;">
          </div>
        </div>

        <!-- Weight & Making Charge Mode -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Gross Weight (g) *</label>
            <input type="number" step="0.001" id="prodGrossWeight" class="form-control" required placeholder="e.g. 15.500" oninput="autoCalculateGoldPrice()" style="font-weight: 700; color: #111827;">
          </div>

          <div class="form-group">
            <label class="form-label">Making Calculation Mode</label>
            <select id="prodMakingType" class="form-control" onchange="handleProductMakingTypeChange(this.value)" style="font-weight: 700; color: #0F766E;">
              <option value="per_gram">Per Gram (₹ / g)</option>
              <option value="percentage">Percentage (% on Gold)</option>
              <option value="fixed">Fixed Price (₹ Flat)</option>
            </select>
          </div>
        </div>

        <!-- Making Charge Input -->
        <div class="form-group">
          <label class="form-label" id="prodMakingLabel">Making Charge Rate (₹/g) *</label>
          <div style="position: relative;">
            <span id="prodMakingPrefix" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #6B7280;">₹</span>
            <input type="number" step="0.01" id="prodMaking" class="form-control" placeholder="650.00" oninput="autoCalculateGoldPrice()" style="padding-left: 28px; padding-right: 36px; font-weight: 700; color: #0F766E;">
            <span id="prodMakingSuffix" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 11.5px; font-weight: 700; color: #6B7280;">/g</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Calculated Selling Price (₹) *</label>
          <input type="number" step="0.01" id="prodPrice" class="form-control" required placeholder="0.00" style="font-weight: 800; color: var(--color-primary-dark); font-size: 16px; background: #F0FDFA;">
        </div>

        <div id="priceBreakdownBadge" style="display: none; font-size: 12px; color: #065F46; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; font-weight: 600;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeAddProductModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: #D97706; border-color: #D97706;">Save to Gold Vault</button>
      </div>
    </form>
  </div>
</div>

<script>
const GOLD_RATES = {
  '24K': <?= $gold24k ?>,
  '22K': <?= $gold22k ?>,
  '18K': <?= $gold18k ?>
};

function autoSuggestSKU(name) {
  const skuInput = document.getElementById('prodSku');
  if (!skuInput.value) {
    skuInput.value = 'GN-' + Math.floor(Math.random() * 9000 + 1000);
  }
}

function handleProductMakingTypeChange(type) {
  const label = document.getElementById('prodMakingLabel');
  const prefix = document.getElementById('prodMakingPrefix');
  const suffix = document.getElementById('prodMakingSuffix');
  const input = document.getElementById('prodMaking');

  if (type === 'percentage') {
    label.innerText = 'Making Percentage (% of Gold Value) *';
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
  autoCalculateGoldPrice();
}

function handleCategoryChange(sel) {
  const selectedOpt = sel.options[sel.selectedIndex];
  if (selectedOpt) {
    const defaultMaking = selectedOpt.getAttribute('data-making');
    const defaultType = selectedOpt.getAttribute('data-type') || 'per_gram';
    
    document.getElementById('prodMakingType').value = defaultType;
    handleProductMakingTypeChange(defaultType);

    if (defaultMaking !== null && defaultMaking !== undefined) {
      document.getElementById('prodMaking').value = parseFloat(defaultMaking) || 0;
      autoCalculateGoldPrice();
    }
  }
}

function autoCalculateGoldPrice() {
  const weight = parseFloat(document.getElementById('prodGrossWeight').value) || 0;
  const purity = document.getElementById('prodPurity').value || '22K';
  const makingType = document.getElementById('prodMakingType').value || 'per_gram';
  const makingVal = parseFloat(document.getElementById('prodMaking').value) || 0;
  const rate = GOLD_RATES[purity] || <?= $gold22k ?>;

  if (weight > 0) {
    const goldValue = weight * rate;
    let totalMaking = 0;
    let breakdownText = '';

    if (makingType === 'percentage') {
      totalMaking = Math.round(goldValue * (makingVal / 100));
      breakdownText = `⚡ <strong>Percentage Calculation:</strong> Gold (${weight}g × ₹${rate.toLocaleString('en-IN')}) = ₹${Math.round(goldValue).toLocaleString('en-IN')} + Making (${makingVal}% on Gold) = ₹${totalMaking.toLocaleString('en-IN')}`;
    } else if (makingType === 'fixed') {
      totalMaking = Math.round(makingVal);
      breakdownText = `⚡ <strong>Fixed Price Calculation:</strong> Gold (${weight}g × ₹${rate.toLocaleString('en-IN')}) = ₹${Math.round(goldValue).toLocaleString('en-IN')} + Flat Making = ₹${totalMaking.toLocaleString('en-IN')}`;
    } else {
      // per_gram
      totalMaking = Math.round(weight * makingVal);
      breakdownText = `⚡ <strong>Per-Gram Calculation:</strong> Gold (${weight}g × ₹${rate.toLocaleString('en-IN')}) = ₹${Math.round(goldValue).toLocaleString('en-IN')} + Making (${weight}g × ₹${makingVal.toLocaleString('en-IN')}/g) = ₹${totalMaking.toLocaleString('en-IN')}`;
    }

    const total = Math.round(goldValue + totalMaking);
    document.getElementById('prodPrice').value = total;

    const badge = document.getElementById('priceBreakdownBadge');
    if (badge) {
      badge.style.display = 'block';
      badge.innerHTML = `${breakdownText} ➔ <strong>Total Selling Price: ₹${total.toLocaleString('en-IN')}</strong>`;
    }
  } else if (makingVal > 0) {
    document.getElementById('prodPrice').value = 0;
  }
}

function openAddProductModal(metal) {
  document.getElementById('prodMetalType').value = metal;
  document.getElementById('addProductModal').classList.add('active');
  
  // Apply category default making charge immediately
  const catSelect = document.getElementById('prodCategory');
  if (catSelect) {
    handleCategoryChange(catSelect);
  }

  setTimeout(() => {
    const nameInput = document.getElementById('prodName');
    if (nameInput) nameInput.focus();
  }, 100);
}

function closeAddProductModal() {
  document.getElementById('addProductModal').classList.remove('active');
}

async function handleSaveProduct(e) {
  e.preventDefault();
  const weight = parseFloat(document.getElementById('prodGrossWeight').value) || 0;
  const purity = document.getElementById('prodPurity').value || '22K';
  const makingType = document.getElementById('prodMakingType').value || 'per_gram';
  const makingVal = parseFloat(document.getElementById('prodMaking').value) || 0;
  const rate = GOLD_RATES[purity] || <?= $gold22k ?>;
  const goldValue = weight * rate;

  let totalMaking = 0;
  if (makingType === 'percentage') {
    totalMaking = Math.round(goldValue * (makingVal / 100));
  } else if (makingType === 'fixed') {
    totalMaking = Math.round(makingVal);
  } else {
    totalMaking = Math.round(weight * makingVal);
  }

  const payload = {
    name: document.getElementById('prodName').value.trim(),
    sku: document.getElementById('prodSku').value.trim() || ('GN-' + Math.floor(Math.random()*9000 + 1000)),
    metal_type: document.getElementById('prodMetalType').value,
    purity: purity,
    gross_weight: weight,
    net_weight: weight,
    making_charges: totalMaking,
    making_charge_type: makingType,
    making_charge_value: makingVal,
    selling_price: parseFloat(document.getElementById('prodPrice').value) || 0,
    purchase_price: (parseFloat(document.getElementById('prodPrice').value) || 0) * 0.85,
    stock: parseInt(document.getElementById('prodStock').value) || 1,
    category: document.getElementById('prodCategory').value || 'Necklace'
  };

  try {
    const res = await fetch('api/products.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert('✓ Gold product added successfully!');
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to add item'));
    }
  } catch(err) {
    alert('Server error adding product');
  }
}

async function deleteProduct(id) {
  if (!confirm('Are you sure you want to delete this product?')) return;
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
