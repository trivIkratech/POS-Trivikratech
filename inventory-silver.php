<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Silver Jewellery & Articles - Trivikratech POS";
$db = getDBConnection();

$products = [];
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM products WHERE metal_type = 'Silver' ORDER BY name ASC");
    $products = $stmt->fetchAll();

    $catStmt = $db->query("SELECT * FROM categories WHERE metal_type = 'Silver' ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (Exception $e) {}

$silverRate = (float)getSetting('rate_silver_1g', '88.50');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 24px;">⚪</span>
      <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Silver Jewellery & Utensils Vault</h1>
    </div>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Manage 925 Sterling Silver ornaments, puja utensils & silver bullion bars</p>
  </div>

  <button type="button" onclick="openAddSilverModal()" class="btn btn-primary" style="background: #4B5563; border-color: #4B5563; box-shadow: 0 4px 12px rgba(75, 85, 99, 0.25);">
    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
    <span>+ Add Silver Product</span>
  </button>
</div>

<!-- Quick Silver Metrics -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr);">
  <div class="kpi-card">
    <div class="kpi-value" style="color: #4B5563;"><?= count($products) ?></div>
    <div class="kpi-label">Active Silver SKUs</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= number_format(array_reduce($products, fn($s, $p) => $s + (int)$p['stock'], 0)) ?> pcs</div>
    <div class="kpi-label">Total Units in Stock</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= number_format(array_reduce($products, fn($s, $p) => $s + ((float)$p['net_weight'] * (int)$p['stock']), 0), 2) ?> g</div>
    <div class="kpi-label">Total Silver Weight</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value"><?= formatFullINR(array_reduce($products, fn($s, $p) => $s + ((float)$p['selling_price'] * (int)$p['stock']), 0)) ?></div>
    <div class="kpi-label">Silver Vault Valuation</div>
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
        <tr><td colspan="9" style="text-align: center; color: #9CA3AF; padding: 36px;">No silver items found. Click "+ Add Silver Product" to add stock.</td></tr>
      <?php else: ?>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($p['sku']) ?></div>
              <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($p['barcode'] ?? '') ?></div>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;"><?= $p['image_emoji'] ?? '🥈' ?></span>
                <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($p['name']) ?></div>
              </div>
            </td>
            <td>
              <span class="badge" style="background: #F3F4F6; color: #374151; font-weight: 600;">
                <?= htmlspecialchars($p['category']) ?>
              </span>
            </td>
            <td><span class="badge badge-silver"><?= htmlspecialchars($p['purity']) ?></span></td>
            <td style="font-weight: 600;"><?= $p['net_weight'] ?> g</td>
            <td>
              <div style="color: #0F766E; font-weight: 700;"><?= formatFullINR($p['making_charges']) ?></div>
              <?php if (!empty($p['making_charge_type'])): ?>
                <div style="font-size: 10.5px; color: #6B7280;">
                  <?= $p['making_charge_type'] === 'percentage' ? ((float)$p['making_charge_value'] . '% on Silver') : ($p['making_charge_type'] === 'fixed' ? 'Fixed Price' : '₹' . (float)$p['making_charge_value'] . '/g') ?>
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

<!-- Add Silver Product Modal -->
<div id="addSilverModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 530px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">⚪</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Add New Silver Product</h3>
      </div>
      <button type="button" onclick="closeAddSilverModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleSaveSilverProduct(event)">
      <input type="hidden" id="silverMetalType" value="Silver">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Ornament / Utensil Name *</label>
          <input type="text" id="silverName" class="form-control" required placeholder="e.g. 92.5 Sterling Silver Bracelet" oninput="autoSuggestSilverSKU(this.value)">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <!-- Category Select (Auto-applies default making charges) -->
          <div class="form-group">
            <label class="form-label">Category * <span style="font-size: 11px; color: #0F766E; font-weight: 700;">(Auto-Loads)</span></label>
            <select id="silverCategory" class="form-control" required onchange="handleSilverCategoryChange(this)" style="font-weight: 600;">
              <?php if (empty($categories)): ?>
                <option value="Bracelet" data-making="35" data-type="per_gram">Bracelet (₹35/g)</option>
                <option value="Ring" data-making="45" data-type="per_gram">Ring (₹45/g)</option>
                <option value="Pooja Utensils" data-making="25" data-type="per_gram">Pooja Utensils (₹25/g)</option>
                <option value="Silver Coin" data-making="10" data-type="per_gram">Silver Coin (₹10/g)</option>
              <?php else: ?>
                <?php foreach ($categories as $c): 
                  $cType = $c['making_charge_type'] ?? 'per_gram';
                  $cVal = (float)$c['default_making_charge'];
                  $displayLabel = $c['name'] . ' (' . ($cType === 'percentage' ? $cVal . '% on Silver' : ($cType === 'fixed' ? '₹' . number_format($cVal) . ' Flat' : '₹' . number_format($cVal) . '/g')) . ')';
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
            <select id="silverPurity" class="form-control" onchange="autoCalculateSilverPrice()" style="font-weight: 600;">
              <option value="925" selected>92.5% (Sterling Silver)</option>
              <option value="999">99.9% (Fine Silver Coin / Bar)</option>
              <option value="800">80.0% (Silver Utensils)</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">SKU</label>
            <input type="text" id="silverSku" class="form-control" placeholder="SB-024" style="font-weight: 600;">
          </div>
          <div class="form-group">
            <label class="form-label">Stock Quantity</label>
            <input type="number" id="silverStock" class="form-control" value="1" min="1" style="font-weight: 600;">
          </div>
        </div>

        <!-- Weight & Making Charge Mode -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Gross Weight (g) *</label>
            <input type="number" step="0.001" id="silverWeight" class="form-control" required placeholder="e.g. 25.000" oninput="autoCalculateSilverPrice()" style="font-weight: 700; color: #111827;">
          </div>

          <div class="form-group">
            <label class="form-label">Making Calculation Mode</label>
            <select id="silverMakingType" class="form-control" onchange="handleSilverMakingTypeChange(this.value)" style="font-weight: 700; color: #0F766E;">
              <option value="per_gram">Per Gram (₹ / g)</option>
              <option value="percentage">Percentage (% on Silver)</option>
              <option value="fixed">Fixed Price (₹ Flat)</option>
            </select>
          </div>
        </div>

        <!-- Making Charge Input -->
        <div class="form-group">
          <label class="form-label" id="silverMakingLabel">Making Charge Rate (₹/g) *</label>
          <div style="position: relative;">
            <span id="silverMakingPrefix" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #6B7280;">₹</span>
            <input type="number" step="0.01" id="silverMaking" class="form-control" placeholder="35.00" oninput="autoCalculateSilverPrice()" style="padding-left: 28px; padding-right: 36px; font-weight: 700; color: #0F766E;">
            <span id="silverMakingSuffix" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 11.5px; font-weight: 700; color: #6B7280;">/g</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Calculated Selling Price (₹) *</label>
          <input type="number" step="0.01" id="silverPrice" class="form-control" required placeholder="0.00" style="font-weight: 800; color: var(--color-primary-dark); font-size: 16px; background: #F0FDFA;">
        </div>

        <div id="silverPriceBadge" style="display: none; font-size: 12px; color: #065F46; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; font-weight: 600;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeAddSilverModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: #4B5563; border-color: #4B5563;">Save to Silver Vault</button>
      </div>
    </form>
  </div>
</div>

<script>
const SILVER_RATE = <?= $silverRate ?>;

function autoSuggestSilverSKU(name) {
  const sku = document.getElementById('silverSku');
  if (!sku.value) {
    sku.value = 'SB-' + Math.floor(Math.random() * 9000 + 1000);
  }
}

function handleSilverMakingTypeChange(type) {
  const label = document.getElementById('silverMakingLabel');
  const prefix = document.getElementById('silverMakingPrefix');
  const suffix = document.getElementById('silverMakingSuffix');
  const input = document.getElementById('silverMaking');

  if (type === 'percentage') {
    label.innerText = 'Making Percentage (% of Silver Value) *';
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
  autoCalculateSilverPrice();
}

function handleSilverCategoryChange(sel) {
  const selectedOpt = sel.options[sel.selectedIndex];
  if (selectedOpt) {
    const defaultMaking = selectedOpt.getAttribute('data-making');
    const defaultType = selectedOpt.getAttribute('data-type') || 'per_gram';

    document.getElementById('silverMakingType').value = defaultType;
    handleSilverMakingTypeChange(defaultType);

    if (defaultMaking !== null && defaultMaking !== undefined) {
      document.getElementById('silverMaking').value = parseFloat(defaultMaking) || 0;
      autoCalculateSilverPrice();
    }
  }
}

function autoCalculateSilverPrice() {
  const weight = parseFloat(document.getElementById('silverWeight').value) || 0;
  const purity = document.getElementById('silverPurity').value || '925';
  const makingType = document.getElementById('silverMakingType').value || 'per_gram';
  const makingVal = parseFloat(document.getElementById('silverMaking').value) || 0;

  let purityFactor = 0.925;
  if (purity === '999') purityFactor = 0.999;
  if (purity === '800') purityFactor = 0.800;

  const effectiveRate = SILVER_RATE * purityFactor;

  if (weight > 0) {
    const silverValue = weight * effectiveRate;
    let totalMaking = 0;
    let breakdownText = '';

    if (makingType === 'percentage') {
      totalMaking = Math.round(silverValue * (makingVal / 100));
      breakdownText = `⚡ <strong>Percentage Calculation:</strong> Silver (${weight}g × ₹${effectiveRate.toFixed(1)}/g) = ₹${Math.round(silverValue).toLocaleString('en-IN')} + Making (${makingVal}% on Silver) = ₹${totalMaking.toLocaleString('en-IN')}`;
    } else if (makingType === 'fixed') {
      totalMaking = Math.round(makingVal);
      breakdownText = `⚡ <strong>Fixed Price Calculation:</strong> Silver (${weight}g × ₹${effectiveRate.toFixed(1)}/g) = ₹${Math.round(silverValue).toLocaleString('en-IN')} + Flat Making = ₹${totalMaking.toLocaleString('en-IN')}`;
    } else {
      // per_gram
      totalMaking = Math.round(weight * makingVal);
      breakdownText = `⚡ <strong>Per-Gram Calculation:</strong> Silver (${weight}g × ₹${effectiveRate.toFixed(1)}/g) = ₹${Math.round(silverValue).toLocaleString('en-IN')} + Making (${weight}g × ₹${makingVal.toFixed(1)}/g) = ₹${totalMaking.toLocaleString('en-IN')}`;
    }

    const total = Math.round(silverValue + totalMaking);
    document.getElementById('silverPrice').value = total;

    const badge = document.getElementById('silverPriceBadge');
    if (badge) {
      badge.style.display = 'block';
      badge.innerHTML = `${breakdownText} ➔ <strong>Total Selling Price: ₹${total.toLocaleString('en-IN')}</strong>`;
    }
  } else if (makingVal > 0) {
    document.getElementById('silverPrice').value = 0;
  }
}

function openAddSilverModal() {
  document.getElementById('addSilverModal').classList.add('active');
  
  // Apply category default making charge immediately
  const catSelect = document.getElementById('silverCategory');
  if (catSelect) {
    handleSilverCategoryChange(catSelect);
  }

  setTimeout(() => {
    const nameInput = document.getElementById('silverName');
    if (nameInput) nameInput.focus();
  }, 100);
}

function closeAddSilverModal() {
  document.getElementById('addSilverModal').classList.remove('active');
}

async function handleSaveSilverProduct(e) {
  e.preventDefault();
  const weight = parseFloat(document.getElementById('silverWeight').value) || 0;
  const purity = document.getElementById('silverPurity').value || '925';
  const makingType = document.getElementById('silverMakingType').value || 'per_gram';
  const makingVal = parseFloat(document.getElementById('silverMaking').value) || 0;

  let purityFactor = 0.925;
  if (purity === '999') purityFactor = 0.999;
  if (purity === '800') purityFactor = 0.800;
  const effectiveRate = SILVER_RATE * purityFactor;
  const silverValue = weight * effectiveRate;

  let totalMaking = 0;
  if (makingType === 'percentage') {
    totalMaking = Math.round(silverValue * (makingVal / 100));
  } else if (makingType === 'fixed') {
    totalMaking = Math.round(makingVal);
  } else {
    totalMaking = Math.round(weight * makingVal);
  }

  const payload = {
    name: document.getElementById('silverName').value.trim(),
    sku: document.getElementById('silverSku').value.trim() || ('SB-' + Math.floor(Math.random()*9000 + 1000)),
    metal_type: 'Silver',
    purity: purity,
    gross_weight: weight,
    net_weight: weight,
    making_charges: totalMaking,
    making_charge_type: makingType,
    making_charge_value: makingVal,
    selling_price: parseFloat(document.getElementById('silverPrice').value) || 0,
    purchase_price: (parseFloat(document.getElementById('silverPrice').value) || 0) * 0.80,
    stock: parseInt(document.getElementById('silverStock').value) || 1,
    category: document.getElementById('silverCategory').value || 'Silver Jewellery'
  };

  try {
    const res = await fetch('api/products.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert('✓ Silver product added successfully!');
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to add item'));
    }
  } catch(err) {
    alert('Server error adding silver product');
  }
}

async function deleteProduct(id) {
  if (!confirm('Are you sure you want to delete this silver product?')) return;
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
