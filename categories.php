<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Jewellery Categories & HSN Masters - Trivikratech POS";
$db = getDBConnection();

$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY id DESC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {}

// Stats calculation
$totalCats = count($categories);
$goldCats = 0;
$silverCats = 0;
$diamondCats = 0;
$platinumCats = 0;

foreach ($categories as $c) {
    if ($c['metal_type'] === 'Gold') $goldCats++;
    elseif ($c['metal_type'] === 'Silver') $silverCats++;
    elseif ($c['metal_type'] === 'Diamond') $diamondCats++;
    elseif ($c['metal_type'] === 'Platinum') $platinumCats++;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0; display: flex; align-items: center; gap: 10px;">
      <span>Jewellery Categories & HSN Tax Masters</span>
      <span class="badge badge-gold" style="font-size: 12px; font-weight: 700; padding: 4px 10px;">HSN 7113</span>
    </h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Manage category classification, GST HSN codes, and standard making charge defaults (Per Gram, %, or Fixed)</p>
  </div>
  
  <div style="display: flex; gap: 10px;">
    <button onclick="openAddCategoryModal()" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px; font-weight: 700; padding: 10px 18px; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25);">
      <i data-lucide="plus-circle" style="width: 17px; height: 17px;"></i>
      <span>+ Add New Category</span>
    </button>
  </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="card" style="padding: 16px 20px; border-left: 4px solid var(--color-primary);">
    <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Total Categories</div>
    <div style="font-size: 24px; font-weight: 800; color: #111827; margin-top: 4px;"><?= $totalCats ?></div>
    <div style="font-size: 11.5px; color: #0F766E; margin-top: 4px; font-weight: 600;">Active catalogue types</div>
  </div>

  <div class="card" style="padding: 16px 20px; border-left: 4px solid #D97706;">
    <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Gold Categories</div>
    <div style="font-size: 24px; font-weight: 800; color: #D97706; margin-top: 4px;"><?= $goldCats ?></div>
    <div style="font-size: 11.5px; color: #D97706; margin-top: 4px; font-weight: 600;">24K / 22K / 18K Ornaments</div>
  </div>

  <div class="card" style="padding: 16px 20px; border-left: 4px solid #64748B;">
    <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Silver Categories</div>
    <div style="font-size: 24px; font-weight: 800; color: #475569; margin-top: 4px;"><?= $silverCats ?></div>
    <div style="font-size: 11.5px; color: #475569; margin-top: 4px; font-weight: 600;">925 Sterling & Bullion</div>
  </div>

  <div class="card" style="padding: 16px 20px; border-left: 4px solid #3B82F6;">
    <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Diamond & Platinum</div>
    <div style="font-size: 24px; font-weight: 800; color: #2563EB; margin-top: 4px;"><?= ($diamondCats + $platinumCats) ?></div>
    <div style="font-size: 11.5px; color: #2563EB; margin-top: 4px; font-weight: 600;">Precious Gemstones & Pt950</div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 20px; padding: 14px 18px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <!-- Search Bar -->
    <div style="position: relative; flex: 1; min-width: 260px;">
      <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9CA3AF;"></i>
      <input type="text" id="categorySearch" class="form-control" placeholder="Search category name, metal, or HSN code..." style="padding-left: 38px;" oninput="filterCategories()">
    </div>

    <!-- Metal Filter Tabs -->
    <div style="display: flex; gap: 8px;" id="metalFilterGroup">
      <button type="button" class="btn btn-secondary filter-tab active" onclick="filterByMetal('all', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700; background: var(--color-primary); color: #FFFFFF;">
        All (<?= $totalCats ?>)
      </button>
      <button type="button" class="btn btn-secondary filter-tab" onclick="filterByMetal('Gold', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700;">
        🪙 Gold (<?= $goldCats ?>)
      </button>
      <button type="button" class="btn btn-secondary filter-tab" onclick="filterByMetal('Silver', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700;">
        ⚪ Silver (<?= $silverCats ?>)
      </button>
      <button type="button" class="btn btn-secondary filter-tab" onclick="filterByMetal('Diamond', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700;">
        💎 Diamond (<?= $diamondCats ?>)
      </button>
      <button type="button" class="btn btn-secondary filter-tab" onclick="filterByMetal('Platinum', this)" style="padding: 6px 14px; font-size: 12.5px; font-weight: 700;">
        🛡️ Platinum (<?= $platinumCats ?>)
      </button>
    </div>
  </div>
</div>

<!-- Table -->
<div class="table-container">
  <table class="data-table" id="categoriesTable">
    <thead>
      <tr>
        <th>Category Name</th>
        <th>Metal Classification</th>
        <th>HSN Code</th>
        <th>Default Making Charges</th>
        <th style="text-align: right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($categories)): ?>
        <tr><td colspan="5" style="text-align: center; color: #9CA3AF; padding: 36px;">No categories found. Click "+ Add New Category" to create your first category master.</td></tr>
      <?php else: ?>
        <?php foreach ($categories as $c): 
          $mType = $c['making_charge_type'] ?? 'per_gram';
          $val = (float)$c['default_making_charge'];
        ?>
          <tr class="cat-row" data-metal="<?= htmlspecialchars($c['metal_type']) ?>">
            <td>
              <div style="font-weight: 800; color: #111827; font-size: 14px;"><?= htmlspecialchars($c['name']) ?></div>
              <div style="font-size: 11px; color: #6B7280;">ID: #CAT-<?= str_pad($c['id'], 3, '0', STR_PAD_LEFT) ?></div>
            </td>
            <td>
              <span class="badge <?= $c['metal_type'] === 'Gold' ? 'badge-gold' : ($c['metal_type'] === 'Diamond' ? 'badge-diamond' : ($c['metal_type'] === 'Silver' ? 'badge-silver' : 'badge-info')) ?>" style="font-weight: 700;">
                <?= htmlspecialchars($c['metal_type']) ?>
              </span>
            </td>
            <td>
              <span style="font-family: monospace; font-weight: 700; background: #F3F4F6; padding: 3px 8px; border-radius: 4px; color: #374151; font-size: 12.5px;">
                <?= htmlspecialchars($c['hsn_code'] ?: '7113') ?>
              </span>
            </td>
            <td>
              <?php if ($mType === 'percentage'): ?>
                <span class="badge" style="background: #EFF6FF; color: #1D4ED8; font-weight: 800; font-size: 13.5px; border: 1px solid #BFDBFE;">
                  <?= number_format($val, 1) ?>% <span style="font-size: 11px; font-weight: 600; color: #60A5FA;">of Metal Value</span>
                </span>
              <?php elseif ($mType === 'fixed'): ?>
                <span class="badge" style="background: #FEF3C7; color: #B45309; font-weight: 800; font-size: 13.5px; border: 1px solid #FDE68A;">
                  <?= formatFullINR($val) ?> <span style="font-size: 11px; font-weight: 600; color: #D97706;">Fixed / Piece</span>
                </span>
              <?php else: ?>
                <span style="font-weight: 800; color: var(--color-primary-dark); font-size: 14px;">
                  <?= formatFullINR($val) ?> <span style="font-size: 11.5px; color: #6B7280; font-weight: 600;">/ gram</span>
                </span>
              <?php endif; ?>
            </td>
            <td style="text-align: right; white-space: nowrap;">
              <button type="button" class="btn btn-ghost btn-sm" onclick='openEditCategoryModal(<?= json_encode($c) ?>)' style="color: var(--color-primary); font-weight: 700; padding: 4px 8px;" title="Edit Category">
                <i data-lucide="edit" style="width: 15px; height: 15px;"></i> Edit
              </button>
              <button type="button" class="btn btn-ghost btn-sm" onclick="deleteCategory(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')" style="color: #EF4444; padding: 4px 8px;" title="Delete Category">
                <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Add / Edit Category Modal -->
<div id="categoryModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 500px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;" id="catModalIcon">🏷️</span>
        <h3 style="font-size: 16px; font-weight: 800; margin: 0;" id="catModalTitle">Add New Jewellery Category</h3>
      </div>
      <button type="button" onclick="closeCategoryModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form onsubmit="handleSaveCategory(event)">
      <input type="hidden" id="catId" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Category Name *</label>
          <input type="text" id="catName" class="form-control" required placeholder="e.g. Bridal Necklace Set, Solitaire Ring, Kada" style="font-weight: 600;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Metal Classification *</label>
            <select id="catMetal" class="form-control" required style="font-weight: 600;">
              <option value="Gold">Gold</option>
              <option value="Silver">Silver</option>
              <option value="Diamond">Diamond</option>
              <option value="Platinum">Platinum</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">HSN / SAC Code *</label>
            <input type="text" id="catHsn" class="form-control" value="7113" required placeholder="7113" style="font-family: monospace; font-weight: 700;">
          </div>
        </div>

        <!-- Making Charge Type & Default Rate -->
        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Making Charge Type *</label>
            <select id="catMakingType" class="form-control" required onchange="handleCatMakingTypeChange(this.value)" style="font-weight: 700; color: #0F766E;">
              <option value="per_gram">Per Gram (₹ / gram)</option>
              <option value="percentage">Percentage (% of Metal)</option>
              <option value="fixed">Fixed Price (₹ Flat / Piece)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" id="catMakingValLabel">Making Rate (₹/g) *</label>
            <div style="position: relative;">
              <span id="catMakingPrefix" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #6B7280;">₹</span>
              <input type="number" step="0.01" min="0" id="catMaking" class="form-control" value="650.00" required placeholder="650.00" style="padding-left: 28px; padding-right: 32px; font-weight: 700;">
              <span id="catMakingSuffix" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 11.5px; font-weight: 700; color: #6B7280;">/g</span>
            </div>
          </div>
        </div>

        <div style="background: #F0FDFA; border: 1px solid #CCFBF1; border-radius: 6px; padding: 10px 12px; font-size: 12px; color: #0F766E; margin-bottom: 8px;">
          💡 <span id="catMakingHelp">Per-Gram making charge auto-multiplies with product weight in inventory & billing.</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeCategoryModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary" id="catSubmitBtn">Save Category Master</button>
      </div>
    </form>
  </div>
</div>

<script>
function handleCatMakingTypeChange(type) {
  const label = document.getElementById('catMakingValLabel');
  const prefix = document.getElementById('catMakingPrefix');
  const suffix = document.getElementById('catMakingSuffix');
  const help = document.getElementById('catMakingHelp');
  const input = document.getElementById('catMaking');

  if (type === 'percentage') {
    label.innerText = 'Making Percent (%) *';
    prefix.style.display = 'none';
    input.style.paddingLeft = '12px';
    suffix.innerText = '%';
    suffix.style.display = 'block';
    help.innerText = 'Percentage making charge calculates dynamically on the total metal value (Weight × Rate).';
    if (parseFloat(input.value) > 100 || parseFloat(input.value) === 650) {
      input.value = '14.00';
    }
  } else if (type === 'fixed') {
    label.innerText = 'Fixed Making Price (₹) *';
    prefix.style.display = 'block';
    prefix.innerText = '₹';
    input.style.paddingLeft = '28px';
    suffix.style.display = 'none';
    help.innerText = 'Fixed making charge adds an exact flat amount per piece regardless of weight.';
    if (parseFloat(input.value) <= 100) {
      input.value = '3500.00';
    }
  } else {
    // per_gram
    label.innerText = 'Making Rate (₹/g) *';
    prefix.style.display = 'block';
    prefix.innerText = '₹';
    input.style.paddingLeft = '28px';
    suffix.innerText = '/g';
    suffix.style.display = 'block';
    help.innerText = 'Per-Gram making charge auto-multiplies with product weight in inventory & billing.';
    if (parseFloat(input.value) < 50 || parseFloat(input.value) > 5000) {
      input.value = '650.00';
    }
  }
}

function openAddCategoryModal() {
  document.getElementById('catId').value = '';
  document.getElementById('catModalTitle').innerText = 'Add New Jewellery Category';
  document.getElementById('catModalIcon').innerText = '🏷️';
  document.getElementById('catSubmitBtn').innerText = 'Save Category Master';
  document.getElementById('catName').value = '';
  document.getElementById('catMetal').value = 'Gold';
  document.getElementById('catHsn').value = '7113';
  document.getElementById('catMakingType').value = 'per_gram';
  document.getElementById('catMaking').value = '650.00';
  handleCatMakingTypeChange('per_gram');
  document.getElementById('categoryModal').classList.add('active');
  document.getElementById('catName').focus();
}

function openEditCategoryModal(cat) {
  document.getElementById('catId').value = cat.id;
  document.getElementById('catModalTitle').innerText = `Edit Category: ${cat.name}`;
  document.getElementById('catModalIcon').innerText = '✏️';
  document.getElementById('catSubmitBtn').innerText = 'Update Category';
  document.getElementById('catName').value = cat.name;
  document.getElementById('catMetal').value = cat.metal_type;
  document.getElementById('catHsn').value = cat.hsn_code || '7113';
  
  const mType = cat.making_charge_type || 'per_gram';
  document.getElementById('catMakingType').value = mType;
  document.getElementById('catMaking').value = parseFloat(cat.default_making_charge) || 0;
  handleCatMakingTypeChange(mType);
  
  document.getElementById('categoryModal').classList.add('active');
  document.getElementById('catName').focus();
}

function closeCategoryModal() {
  document.getElementById('categoryModal').classList.remove('active');
}

function filterCategories() {
  const q = document.getElementById('categorySearch').value.toLowerCase();
  const activeMetalBtn = document.querySelector('#metalFilterGroup .filter-tab.active');
  const metalFilter = activeMetalBtn ? activeMetalBtn.innerText.toLowerCase() : 'all';

  const rows = document.querySelectorAll('.cat-row');
  rows.forEach(r => {
    const text = r.innerText.toLowerCase();
    const rowMetal = (r.getAttribute('data-metal') || '').toLowerCase();
    
    let matchesMetal = true;
    if (!metalFilter.includes('all')) {
      matchesMetal = metalFilter.includes(rowMetal);
    }

    const matchesSearch = text.includes(q);
    r.style.display = (matchesMetal && matchesSearch) ? '' : 'none';
  });
}

function filterByMetal(metal, btnEl) {
  document.querySelectorAll('#metalFilterGroup .filter-tab').forEach(b => {
    b.classList.remove('active');
    b.style.background = '';
    b.style.color = '';
  });
  btnEl.classList.add('active');
  btnEl.style.background = 'var(--color-primary)';
  btnEl.style.color = '#FFFFFF';
  filterCategories();
}

async function handleSaveCategory(e) {
  e.preventDefault();
  const id = document.getElementById('catId').value;
  const isEdit = !!id;

  const payload = {
    id: id ? parseInt(id) : undefined,
    name: document.getElementById('catName').value.trim(),
    metal_type: document.getElementById('catMetal').value,
    hsn_code: document.getElementById('catHsn').value.trim() || '7113',
    making_charge_type: document.getElementById('catMakingType').value,
    default_making_charge: parseFloat(document.getElementById('catMaking').value) || 0
  };

  const action = isEdit ? 'update' : 'create';

  try {
    const res = await fetch(`api/categories.php?action=${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert(`✓ Category ${isEdit ? 'updated' : 'created'} successfully!`);
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to save category'));
    }
  } catch(err) {
    alert('Server error saving category');
  }
}

async function deleteCategory(id, name) {
  if (!confirm(`Are you sure you want to delete category "${name}"?`)) return;
  try {
    const res = await fetch(`api/categories.php?action=delete&id=${id}`);
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.error || 'Failed to delete category');
    }
  } catch(e) {
    alert('Server error');
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
