<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "POS Counter Billing - Trivikratech POS";
$db = getDBConnection();

// Fetch all active products
$products = [];
try {
    $stmt = $db->query("SELECT * FROM products ORDER BY name ASC");
    $products = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch customers for fast autocomplete
$customers = [];
try {
    $custStmt = $db->query("SELECT id, name, mobile, loyalty_points, loyalty_tier FROM customers ORDER BY name ASC");
    $customers = $custStmt->fetchAll();
} catch (Exception $e) {}

// Pre-selected Old Gold Exchange if coming from exchange view/list
$preExchange = null;
if (!empty($_GET['exchange_id'])) {
    try {
        $ogeStmt = $db->prepare("SELECT * FROM old_gold_exchanges WHERE id = ? AND status IN ('Approved', 'Partially Redeemed') AND credit_balance > 0");
        $ogeStmt->execute([(int)$_GET['exchange_id']]);
        $preExchange = $ogeStmt->fetch();
    } catch(Exception $e) {}
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="pos-layout">
  <!-- Left Side: Product Catalog & Fast Lookup -->
  <div class="pos-catalog">
    <!-- Search & Filter Bar -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 14px 18px; display: flex; gap: 14px; align-items: center; box-shadow: var(--shadow-sm); flex-wrap: wrap;">
      <div style="position: relative; flex: 1; min-width: 220px;">
        <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9CA3AF;"></i>
        <input type="text" id="posSearch" class="form-control" placeholder="Search by jewellery name, SKU, or purity..." style="padding-left: 36px; font-size: 13.5px;">
      </div>

      <div style="position: relative; width: 200px;">
        <i data-lucide="scan" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9CA3AF;"></i>
        <input type="text" id="barcodeScanInput" class="form-control" placeholder="Scan Barcode..." style="padding-left: 36px; font-size: 13px;">
      </div>

      <!-- Metal Tabs -->
      <div style="display: flex; gap: 6px; background: var(--color-surface-2); padding: 4px; border-radius: 10px;">
        <button type="button" class="btn btn-sm metal-tab active" data-filter="All" style="padding: 5px 12px; font-size: 12px; background: #fff; color: var(--color-primary); font-weight: 700;">All</button>
        <button type="button" class="btn btn-sm metal-tab" data-filter="Gold" style="padding: 5px 12px; font-size: 12px; background: transparent; color: var(--color-text-2);">Gold</button>
        <button type="button" class="btn btn-sm metal-tab" data-filter="Silver" style="padding: 5px 12px; font-size: 12px; background: transparent; color: var(--color-text-2);">Silver</button>
        <button type="button" class="btn btn-sm metal-tab" data-filter="Diamond" style="padding: 5px 12px; font-size: 12px; background: transparent; color: var(--color-text-2);">Diamond</button>
        <button type="button" class="btn btn-sm metal-tab" data-filter="Platinum" style="padding: 5px 12px; font-size: 12px; background: transparent; color: var(--color-text-2);">Platinum</button>
      </div>
    </div>

    <!-- Product Grid Container -->
    <div class="product-grid" id="productGrid">
      <?php foreach ($products as $p): 
        $metal = strtolower($p['metal_type'] ?? 'gold');
        $badgeStyle = 'background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A;';
        if (str_contains($metal, 'diamond')) {
            $badgeStyle = 'background: #E0F2FE; color: #0284C7; border: 1px solid #BAE6FD;';
        } elseif (str_contains($metal, 'silver')) {
            $badgeStyle = 'background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0;';
        } elseif (str_contains($metal, 'platinum')) {
            $badgeStyle = 'background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;';
        }
        $isLowStock = (int)$p['stock'] <= (int)$p['min_stock'];
      ?>
        <div class="product-card" 
             data-id="<?= $p['id'] ?>"
             data-sku="<?= htmlspecialchars($p['sku']) ?>"
             data-name="<?= htmlspecialchars($p['name']) ?>"
             data-metal="<?= htmlspecialchars($p['metal_type']) ?>"
             data-purity="<?= htmlspecialchars($p['purity']) ?>"
             data-price="<?= (float)$p['selling_price'] ?>"
             data-weight="<?= (float)$p['net_weight'] ?>"
             data-making="<?= (float)$p['making_charges'] ?>"
             data-making-type="<?= htmlspecialchars($p['making_charge_type'] ?? 'per_gram') ?>"
             data-making-val="<?= (float)($p['making_charge_value'] ?? $p['making_charges']) ?>"
             data-stock="<?= (int)$p['stock'] ?>"
             data-barcode="<?= htmlspecialchars($p['barcode']) ?>"
             onclick="addToCart(this)">
          <div class="product-thumb" style="background: <?= htmlspecialchars($p['image_color'] ?? '#FEF3C7') ?>;">
            <?= $p['image_emoji'] ?? '💍' ?>
          </div>
          <div class="product-info">
            <div>
              <div class="product-badge-row">
                <span class="product-purity-pill" style="<?= $badgeStyle ?>"><?= htmlspecialchars($p['purity']) ?></span>
                <span class="product-stock-pill" style="color: <?= $isLowStock ? '#DC2626' : '#059669' ?>;">
                  <?= (int)$p['stock'] ?> in stock
                </span>
              </div>
              <div class="product-title" title="<?= htmlspecialchars($p['name']) ?>">
                <?= htmlspecialchars($p['name']) ?>
              </div>
              <div class="product-subtitle">
                <span><?= $p['net_weight'] ?>g</span>
                <span>•</span>
                <span>SKU: <?= htmlspecialchars($p['sku']) ?></span>
              </div>
              <div style="margin-top: 4px;">
                <?php if (($p['making_charge_type'] ?? '') === 'percentage'): ?>
                  <span style="font-size: 10px; font-weight: 700; color: #1D4ED8; background: #EFF6FF; border: 1px solid #BFDBFE; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px;">
                    ⚡ Making: <?= (float)$p['making_charge_value'] ?>%
                  </span>
                <?php elseif (($p['making_charge_type'] ?? '') === 'fixed'): ?>
                  <span style="font-size: 10px; font-weight: 700; color: #B45309; background: #FEF3C7; border: 1px solid #FDE68A; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px;">
                    ⚡ Making: Fixed ₹<?= number_format($p['making_charges']) ?>
                  </span>
                <?php else: ?>
                  <span style="font-size: 10px; font-weight: 700; color: #0F766E; background: #F0FDFA; border: 1px solid #CCFBF1; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px;">
                    ⚡ Making: ₹<?= (float)($p['making_charge_value'] > 0 ? $p['making_charge_value'] : ($p['net_weight'] > 0 ? round($p['making_charges'] / $p['net_weight']) : $p['making_charges'])) ?>/g
                  </span>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="product-footer-row">
              <div class="product-price-section">
                <span class="product-price-label">Price</span>
                <span class="product-price-text"><?= formatFullINR($p['selling_price']) ?></span>
              </div>
              <button type="button" class="product-add-btn" onclick="event.stopPropagation(); addToCart(this.closest('.product-card'));">
                <i data-lucide="shopping-bag" style="width: 13px; height: 13px;"></i>
                <span>Add</span>
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right Side: Interactive POS Cart & Billing Terminal -->
  <div class="pos-cart">
    <!-- Customer Selector -->
    <div style="padding: 14px 18px; border-bottom: 1px solid var(--color-border); background: var(--color-surface-2);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-text-2); letter-spacing: 0.05em;">Customer Details</span>
        <button type="button" onclick="openNewCustomerModal()" style="background: none; border: none; font-size: 12px; color: var(--color-primary); font-weight: 700; cursor: pointer;">
          + New Customer
        </button>
      </div>
      <div style="position: relative; margin-bottom: 10px;">
        <select id="posCustomerSelect" class="form-control" style="font-size: 13px; padding: 7px 10px; font-weight: 600;" onchange="onCustomerChanged(this)">
          <option value="">Walk-in Customer</option>
          <?php foreach ($customers as $c): 
            $isCustSelected = $preExchange && ((int)$preExchange['customer_id'] === (int)$c['id'] || $preExchange['customer_mobile'] === $c['mobile']);
          ?>
            <option value="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" data-mobile="<?= htmlspecialchars($c['mobile']) ?>" data-points="<?= (int)$c['loyalty_points'] ?>" <?= $isCustSelected ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['mobile']) ?>) · <?= $c['loyalty_tier'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Old Gold Exchange Credit Voucher Selector -->
      <div style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 10px 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
          <span style="font-size: 11px; font-weight: 800; color: #B45309; text-transform: uppercase; display: flex; align-items: center; gap: 4px;">
            <span>⚖️</span> Old Gold Credit Voucher
          </span>
          <a href="old_gold_exchange_create.php" target="_blank" style="font-size: 11px; color: #D97706; font-weight: 700; text-decoration: underline;">
            + Assess Gold
          </a>
        </div>
        <select id="posExchangeSelect" class="form-control" style="font-size: 12px; padding: 5px 8px; font-weight: 600;" onchange="onExchangeVoucherSelected(this)">
          <option value="">-- No Exchange Credit Applied --</option>
          <?php if ($preExchange): ?>
            <option value="<?= $preExchange['id'] ?>" 
                    data-no="<?= htmlspecialchars($preExchange['exchange_no']) ?>"
                    data-val="<?= (float)$preExchange['credit_balance'] ?>" 
                    data-wt="<?= (float)$preExchange['total_net_weight'] ?>"
                    selected>
              ⭐ <?= htmlspecialchars($preExchange['exchange_no']) ?> (₹<?= number_format((float)$preExchange['credit_balance'], 2) ?> · <?= $preExchange['total_net_weight'] ?>g)
            </option>
          <?php endif; ?>
        </select>
        <div id="exchangeCreditNotice" style="<?= $preExchange ? 'display: block;' : 'display: none;' ?> font-size: 11px; color: #047857; margin-top: 5px; font-weight: 700;">
          <?php if ($preExchange): ?>
            ✓ Voucher <?= htmlspecialchars($preExchange['exchange_no']) ?> active: ₹<?= number_format((float)$preExchange['credit_balance'], 2) ?> available credit
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Cart Items Scroll Area -->
    <div class="cart-items-scroll" id="cartItemsList">
      <div id="emptyCartMessage" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; text-align: center; color: #9CA3AF;">
        <i data-lucide="shopping-bag" style="width: 44px; height: 44px; stroke-width: 1.5; margin-bottom: 10px;"></i>
        <div style="font-weight: 600; font-size: 13.5px;">Cart is empty</div>
        <div style="font-size: 11.5px;">Click items on the left to add</div>
      </div>
    </div>

    <!-- Order Summary & Calculations -->
    <div style="padding: 16px 20px; border-top: 1px solid var(--color-border); background: #FFFFFF;">
      <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: #6B7280; margin-bottom: 4px;">
        <span>Items Subtotal:</span>
        <span id="posSubtotal" style="font-weight: 600; color: #111827;">₹0.00</span>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; color: #6B7280; margin-bottom: 4px;">
        <span id="posMakingLabel">Total Making Charges:</span>
        <span id="posMakingCharges" style="font-weight: 600; color: #111827;">₹0.00</span>
      </div>
      <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: #6B7280; margin-bottom: 4px;">
        <span>GST (3% Indian Bullion):</span>
        <span id="posGst" style="font-weight: 600; color: #111827;">₹0.00</span>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; color: #6B7280; margin-bottom: 6px;">
        <span>Special Discount:</span>
        <input type="number" id="posDiscount" min="0" value="0" style="width: 90px; text-align: right; padding: 3px 6px; font-size: 12px; border: 1px solid var(--color-border); border-radius: 6px;" oninput="recalculateCart()">
      </div>

      <!-- Old Gold Exchange Credit Deduction Row -->
      <div id="posExchangeCreditRow" style="display: none; justify-content: space-between; align-items: center; font-size: 12.5px; color: #B45309; font-weight: 700; background: #FEF3C7; border: 1px solid #FDE68A; padding: 5px 8px; border-radius: 6px; margin-bottom: 8px;">
        <span id="posExchangeCreditLabel">− Old Gold Credit:</span>
        <span id="posExchangeCreditText">-₹0.00</span>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: baseline; border-top: 2px solid var(--color-primary); padding-top: 10px; margin-bottom: 14px;">
        <span style="font-size: 15px; font-weight: 800; color: #111827;">Net Payable:</span>
        <span id="posGrandTotal" style="font-size: 20px; font-weight: 900; color: var(--color-primary);">₹0.00</span>
      </div>

      <!-- Payment Method Selection -->
      <div style="margin-bottom: 14px;">
        <label class="form-label" style="font-size: 11px; text-transform: uppercase; color: #6B7280;">Payment Mode</label>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
          <button type="button" class="btn btn-sm pay-btn active" data-method="Cash" onclick="selectPaymentMethod('Cash', this)" style="background: var(--color-primary); color: #fff; font-size: 11px; padding: 6px 4px;">💵 Cash</button>
          <button type="button" class="btn btn-sm pay-btn" data-method="Card" onclick="selectPaymentMethod('Card', this)" style="background: var(--color-surface-2); color: var(--color-text-1); font-size: 11px; padding: 6px 4px;">💳 Card</button>
          <button type="button" class="btn btn-sm pay-btn" data-method="UPI" onclick="selectPaymentMethod('UPI', this)" style="background: var(--color-surface-2); color: var(--color-text-1); font-size: 11px; padding: 6px 4px;">📱 UPI</button>
          <button type="button" class="btn btn-sm pay-btn" data-method="Gold Exchange" onclick="selectPaymentMethod('Gold Exchange', this)" style="background: var(--color-surface-2); color: var(--color-text-1); font-size: 11px; padding: 6px 4px;">⚖️ Exchange</button>
        </div>
      </div>

      <!-- Checkout Button -->
      <button type="button" id="btnCheckout" onclick="submitPOSSale()" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 15px; border-radius: 12px; font-weight: 800;" disabled>
        <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
        <span>Complete Sale & Print Bill</span>
      </button>
    </div>
  </div>
</div>

<!-- Quick Add Customer Modal -->
<div id="newCustomerModal" class="modal-backdrop">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Add New Customer</h3>
      <button type="button" onclick="closeNewCustomerModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>
    <form id="newCustomerForm" onsubmit="handleCreateCustomer(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" id="newCustName" class="form-control" required placeholder="e.g. Ramesh Shah">
        </div>
        <div class="form-group">
          <label class="form-label">Mobile Number *</label>
          <input type="tel" id="newCustMobile" class="form-control" required placeholder="e.g. 9876543210">
        </div>
        <div class="form-group">
          <label class="form-label">Email (Optional)</label>
          <input type="email" id="newCustEmail" class="form-control" placeholder="name@domain.com">
        </div>
        <div class="form-group">
          <label class="form-label">City</label>
          <input type="text" id="newCustCity" class="form-control" value="Mumbai">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeNewCustomerModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">Save & Select</button>
      </div>
    </form>
  </div>
</div>

<!-- Sale Receipt / Invoice Print Modal -->
<div id="saleSuccessModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 580px;">
    <div class="modal-header" style="background: #F0FDFA; border-bottom: 1px solid #CCFBF1;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <div style="width: 28px; height: 28px; border-radius: 50%; background: #0F766E; color: #fff; display: flex; align-items: center; justify-content: center;">
          <i data-lucide="check" style="width: 16px; height: 16px;"></i>
        </div>
        <h3 style="font-size: 16px; font-weight: 800; color: #0F766E; margin: 0;">Sale Successfully Completed!</h3>
      </div>
      <button type="button" onclick="closeSaleSuccessModal()" style="background: none; border: none; cursor: pointer;"><i data-lucide="x" style="width: 18px; height: 18px;"></i></button>
    </div>

    <div class="modal-body" id="printReceiptContent" style="padding: 24px; font-family: 'Plus Jakarta Sans', sans-serif;">
      <!-- Generated Dynamically via JS -->
    </div>

    <div class="modal-footer" style="background: #F9FAFB; display: flex; flex-direction: column; gap: 10px;">
      <div style="display: flex; justify-content: space-between; width: 100%; gap: 10px;">
        <button type="button" onclick="printReceipt()" class="btn btn-primary" style="flex: 1; padding: 10px 16px; font-size: 14px; font-weight: 800;">
          <i data-lucide="printer" style="width: 17px; height: 17px;"></i>
          <span>Print Tax Invoice</span>
        </button>
      </div>

      <div style="display: flex; gap: 8px; width: 100%; border-top: 1px solid #E5E7EB; padding-top: 10px;">
        <button type="button" onclick="startNextSale('new_customer')" class="btn btn-sm" style="flex: 1; background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; font-weight: 700; padding: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 5px;">
          <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
          <span>+ Add New Customer</span>
        </button>
        <button type="button" onclick="startNextSale('select_customer')" class="btn btn-sm" style="flex: 1; background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; font-weight: 700; padding: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 5px;">
          <i data-lucide="users" style="width: 14px; height: 14px;"></i>
          <span>Select Customer</span>
        </button>
        <button type="button" onclick="startNextSale('walk_in')" class="btn btn-ghost btn-sm" style="flex: 1; font-weight: 600; padding: 8px;">
          <span>Walk-in Sale</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Pass dynamic Store Settings from Database to POS Frontend JS -->
<script>
  window.STORE_INFO = {
    name: <?= json_encode(getSetting('store_name', 'Kulthe Suvarnkar')) ?>,
    address: <?= json_encode(getSetting('address', 'Bandra West, Mumbai')) ?>,
    gstin: <?= json_encode(getSetting('gstin', '27AABFA1234Z1ZX')) ?>,
    phone: <?= json_encode(getSetting('phone', '+91 98765 00000')) ?>,
    email: <?= json_encode(getSetting('email', 'contact@jeweller.com')) ?>
  };
</script>

<!-- Include Interactive POS JS -->
<script src="assets/js/pos.js?v=<?= time() ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
