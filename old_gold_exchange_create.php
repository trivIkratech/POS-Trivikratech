<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "New Old Gold Assessment & Exchange - Trivikratech POS";
$db = getDBConnection();
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'Employee';
$isManagerOrAdmin = in_array($userRole, ['Super Admin', 'Store Manager']);

// Fetch active 24K gold rate from settings
$rate24k = (float)getSetting('rate_gold_24k', '7250');
if ($rate24k <= 0) $rate24k = 7250;

// Fetch customers for autocomplete dropdown
$customers = [];
try {
    $custStmt = $db->query("SELECT id, name, mobile, city, loyalty_tier FROM customers ORDER BY name ASC");
    $customers = $custStmt->fetchAll();
} catch (Exception $e) {}

// Pre-selected customer from query param if any
$preSelectedCustId = !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="padding: 24px 28px; max-width: 1240px; margin: 0 auto;">
  <!-- Breadcrumb & Header -->
  <div style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6B7280; margin-bottom: 6px;">
      <a href="old_gold_exchange.php" style="color: var(--color-primary); font-weight: 600;">Old Gold Exchange</a>
      <span>/</span>
      <span style="color: #111827; font-weight: 600;">New Assessment</span>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
      <div>
        <h1 style="font-size: 24px; font-weight: 900; color: #111827; margin: 0;">
          Old Gold Assessment & Valuation
        </h1>
        <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">
          Standardized purity assessment and exchange credit calculation calibrated to 24K pure gold equivalent.
        </p>
      </div>

      <div style="display: flex; align-items: center; gap: 10px;">
        <span class="badge" style="background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A; font-size: 13px; padding: 6px 14px;">
          🟡 Live Rate: ₹<span id="headerRateBadge"><?= number_format($rate24k) ?></span>/g (24K)
        </span>
        <a href="old_gold_exchange.php" class="btn btn-ghost btn-sm">Cancel</a>
      </div>
    </div>
  </div>

  <form id="exchangeForm" onsubmit="handleReviewExchange(event)">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
      
      <!-- Left Column: Step 1 (Customer) & Step 2 (Items Assessment) -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- STEP 1: Customer Information Card -->
        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px 24px; box-shadow: var(--shadow-sm);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="width: 24px; height: 24px; border-radius: 50%; background: #0F766E; color: #fff; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center;">1</span>
              <h2 style="font-size: 15px; font-weight: 800; color: #111827; margin: 0;">Customer Information</h2>
            </div>
            <button type="button" onclick="openNewCustomerModal()" class="btn btn-ghost btn-sm" style="color: var(--color-primary); font-weight: 700;">
              + Register New Customer
            </button>
          </div>

          <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
              <label class="form-label" style="font-weight: 700; font-size: 12px;">Select Existing Customer</label>
              <select id="custSelect" class="form-control" onchange="onSelectCustomer(this)">
                <option value="">-- Choose Existing Customer or Type Below --</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>" 
                          data-name="<?= htmlspecialchars($c['name']) ?>" 
                          data-mobile="<?= htmlspecialchars($c['mobile']) ?>" 
                          data-city="<?= htmlspecialchars($c['city'] ?? '') ?>"
                          data-tier="<?= htmlspecialchars($c['loyalty_tier'] ?? 'Bronze') ?>"
                          <?= $preSelectedCustId === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['mobile']) ?>) · <?= $c['loyalty_tier'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="form-label" style="font-weight: 700; font-size: 12px;">Exchange Date *</label>
              <input type="date" id="exchangeDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
              <label class="form-label" style="font-weight: 700; font-size: 12px;">Customer Full Name *</label>
              <input type="text" id="custName" class="form-control" placeholder="e.g. Rajesh Soni" required>
            </div>
            <div>
              <label class="form-label" style="font-weight: 700; font-size: 12px;">Mobile Number *</label>
              <input type="tel" id="custMobile" class="form-control" placeholder="e.g. 9876543210" pattern="[0-9+\-\s]{8,15}" required>
            </div>
          </div>
          <input type="hidden" id="custId" value="<?= $preSelectedCustId ?>">
        </div>

        <!-- STEP 2: Gold Valuation Rate Configuration -->
        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px 24px; box-shadow: var(--shadow-sm);">
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px;">
            <span style="width: 24px; height: 24px; border-radius: 50%; background: #D97706; color: #fff; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center;">2</span>
            <h2 style="font-size: 15px; font-weight: 800; color: #111827; margin: 0;">Valuation Basis & Applicable 24K Gold Rate</h2>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
              <label class="form-label" style="font-weight: 700; font-size: 12px;">Applicable 24K Pure Gold Rate (₹ / gram) *</label>
              <div style="position: relative;">
                <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #9CA3AF;">₹</span>
                <input type="number" id="goldRate24k" class="form-control" style="padding-left: 28px; font-weight: 800; font-size: 15px; color: #B45309;" step="0.01" min="1" value="<?= $rate24k ?>" required oninput="recalculateAll()">
              </div>
              <span style="font-size: 11px; color: #6B7280; margin-top: 3px; display: block;">
                Defaults to live store rate. Authorized staff can adjust for daily market fluctuations.
              </span>
            </div>

            <div>
              <label class="form-label" style="font-weight: 700; font-size: 12px;">Valuation Formula Basis</label>
              <input type="text" id="rateBasis" class="form-control" value="24K Pure Gold Equivalent (Fine Wt = Net Wt × Karat / 24)" readonly style="background: var(--color-surface-2); font-size: 12px; color: #4B5563;">
              <span style="font-size: 11px; color: #059669; font-weight: 600; margin-top: 3px; display: block;">
                ✓ Strict mathematical purity conversion applied uniformly.
              </span>
            </div>
          </div>
        </div>

        <!-- STEP 3: Old Gold Assessment (Items Breakdown) -->
        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px 24px; box-shadow: var(--shadow-sm);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="width: 24px; height: 24px; border-radius: 50%; background: #D97706; color: #fff; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center;">3</span>
              <h2 style="font-size: 15px; font-weight: 800; color: #111827; margin: 0;">Old Gold Item Assessment</h2>
            </div>
            <button type="button" onclick="addNewItemRow()" class="btn btn-sm btn-primary" style="background: #D97706; border-color: #D97706; font-size: 12px;">
              <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
              <span>+ Add Another Item</span>
            </button>
          </div>

          <div id="itemsContainer" style="display: flex; flex-direction: column; gap: 16px;">
            <!-- Dynamic Item Rows Injected Here -->
          </div>

          <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
            <button type="button" onclick="addNewItemRow()" class="btn btn-ghost btn-sm" style="color: #D97706; font-weight: 700;">
              + Add Item Row
            </button>
          </div>
        </div>

        <!-- Remarks Card -->
        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px 24px; box-shadow: var(--shadow-sm);">
          <label class="form-label" style="font-weight: 700; font-size: 12px;">Overall Assessment Remarks / Acid Test Notes</label>
          <textarea id="overallRemarks" class="form-control" rows="2" placeholder="e.g. Acid test conducted on stone-set ring; purity confirmed 916 hallmarked; customer accepted valuation."></textarea>
        </div>

      </div>

      <!-- Right Column: Real-time Valuation Summary & Approval Action -->
      <div style="position: sticky; top: 88px; display: flex; flex-direction: column; gap: 18px;">
        
        <!-- Live Valuation Summary Card -->
        <div style="background: #FFFFFF; border: 2px solid #FDE68A; border-radius: var(--radius-lg); padding: 22px; box-shadow: var(--shadow-md);">
          <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--color-border); padding-bottom: 12px; margin-bottom: 16px;">
            <div style="font-size: 14px; font-weight: 800; color: #111827; display: flex; align-items: center; gap: 6px;">
              <span>📊 Valuation Summary</span>
            </div>
            <span class="badge" style="background: #FEF3C7; color: #B45309; font-size: 11px; font-weight: 700;">
              <span id="summaryItemCount">1</span> Item(s)
            </span>
          </div>

          <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; color: #4B5563;">
              <span>Total Gross Weight:</span>
              <span id="sumGrossWeight" style="font-weight: 700; color: #111827;">0.000 g</span>
            </div>

            <div style="display: flex; justify-content: space-between; color: #DC2626;">
              <span>Total Weight Deductions:</span>
              <span id="sumDeductionsWeight" style="font-weight: 700;">-0.000 g</span>
            </div>

            <div style="display: flex; justify-content: space-between; color: #111827; background: #F9FAFB; padding: 6px 10px; border-radius: 8px; font-weight: 700;">
              <span>Net Gold Weight:</span>
              <span id="sumNetWeight">0.000 g</span>
            </div>

            <div style="display: flex; justify-content: space-between; color: #B45309; font-weight: 700;">
              <span>Fine Gold Equivalent (24K):</span>
              <span id="sumFineWeight">0.000 g</span>
            </div>

            <hr style="border: none; border-top: 1px dashed var(--color-border); margin: 4px 0;">

            <div style="display: flex; justify-content: space-between; color: #4B5563;">
              <span>Gross Gold Value:</span>
              <span id="sumGrossValuation" style="font-weight: 700; color: #111827;">₹0.00</span>
            </div>

            <div style="display: flex; justify-content: space-between; color: #DC2626;">
              <span>Melting/Refining Deductions:</span>
              <span id="sumMonetaryDeductions" style="font-weight: 700;">-₹0.00</span>
            </div>

            <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FFFBEB 100%); border: 1px solid #FDE68A; border-radius: 12px; padding: 14px 16px; margin-top: 8px;">
              <div style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #B45309; letter-spacing: 0.05em; margin-bottom: 2px;">
                Final Exchange Credit Value
              </div>
              <div id="sumFinalValue" style="font-size: 26px; font-weight: 900; color: #B45309;">
                ₹0.00
              </div>
              <div style="font-size: 11px; color: #92400E; margin-top: 3px;">
                Eligible for instant deduction on POS billing counter
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; font-weight: 800; background: #D97706; border-color: #D97706; border-radius: 10px; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);">
              <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
              <span>Review Valuation & Save</span>
            </button>
            <button type="button" onclick="submitAsDraft()" class="btn btn-ghost" style="width: 100%; font-size: 13px; font-weight: 600;">
              Save as Draft Assessment
            </button>
          </div>
        </div>

        <!-- Info Card: Calculation Policy -->
        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: var(--radius-md); padding: 14px 16px; font-size: 12px; color: #166534; line-height: 1.5;">
          <strong>🛡️ Exchange Compliance & Precision:</strong>
          <ul style="margin: 6px 0 0 16px; padding: 0;">
            <li>Net Wt = Gross Wt − Deductions</li>
            <li>Fine Gold = Net Wt × Karat ÷ 24</li>
            <li>Gross Value = Fine Gold × 24K Rate</li>
            <li>Net Credit = Gross Value − Deductions</li>
          </ul>
        </div>

      </div>
    </div>
  </form>
</div>

<!-- Quick Register Customer Modal -->
<div id="newCustomerModal" class="modal-backdrop">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Register New Customer</h3>
      <button type="button" onclick="closeNewCustomerModal()" style="background: none; border: none; cursor: pointer;">
        <i data-lucide="x" style="width: 18px; height: 18px;"></i>
      </button>
    </div>
    <form id="newCustomerForm" onsubmit="handleQuickCreateCustomer(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" id="quickCustName" class="form-control" required placeholder="e.g. Ramesh Shah">
        </div>
        <div class="form-group">
          <label class="form-label">Mobile Number *</label>
          <input type="tel" id="quickCustMobile" class="form-control" required placeholder="e.g. 9876543210">
        </div>
        <div class="form-group">
          <label class="form-label">Email (Optional)</label>
          <input type="email" id="quickCustEmail" class="form-control" placeholder="name@domain.com">
        </div>
        <div class="form-group">
          <label class="form-label">City</label>
          <input type="text" id="quickCustCity" class="form-control" value="Mumbai">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeNewCustomerModal()" class="btn btn-ghost btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" id="btnQuickCust">Save Customer</button>
      </div>
    </form>
  </div>
</div>

<!-- Review & Confirmation Modal -->
<div id="reviewModal" class="modal-backdrop">
  <div class="modal-dialog" style="max-width: 680px;">
    <div class="modal-header" style="background: #FEF3C7; border-bottom: 1px solid #FDE68A;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">⚖️</span>
        <h3 style="font-size: 17px; font-weight: 800; color: #92400E; margin: 0;">Confirm Old Gold Valuation & Approval</h3>
      </div>
      <button type="button" onclick="closeReviewModal()" style="background: none; border: none; cursor: pointer;">
        <i data-lucide="x" style="width: 18px; height: 18px;"></i>
      </button>
    </div>
    <div class="modal-body" style="padding: 20px 24px;">
      <div style="background: #FAF5FF; border: 1px solid #E9D5FF; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; justify-content: space-between;">
        <div>
          <div style="font-size: 11px; text-transform: uppercase; color: #6B21A8; font-weight: 800;">Customer</div>
          <div style="font-size: 15px; font-weight: 800; color: #111827;" id="revCustomer"></div>
          <div style="font-size: 12px; color: #6B7280;" id="revMobile"></div>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 11px; text-transform: uppercase; color: #6B21A8; font-weight: 800;">Valuation Date</div>
          <div style="font-size: 13px; font-weight: 700; color: #111827;" id="revDate"></div>
          <div style="font-size: 12px; color: #D97706; font-weight: 700;" id="revRate"></div>
        </div>
      </div>

      <!-- Items Table in Modal -->
      <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; margin-bottom: 16px;">
        <thead>
          <tr style="background: #F3F4F6; border-bottom: 1px solid #E5E7EB; color: #374151;">
            <th style="padding: 6px 10px; text-align: left;">Item</th>
            <th style="padding: 6px 10px; text-align: center;">Purity</th>
            <th style="padding: 6px 10px; text-align: right;">Gross (g)</th>
            <th style="padding: 6px 10px; text-align: right;">Deductions</th>
            <th style="padding: 6px 10px; text-align: right;">Net (g)</th>
            <th style="padding: 6px 10px; text-align: right;">Assessed Value</th>
          </tr>
        </thead>
        <tbody id="revItemsList"></tbody>
      </table>

      <!-- Total Box -->
      <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div style="font-size: 12px; color: #166534; font-weight: 700;">Total Net Gold Weight: <span id="revTotalNet"></span></div>
          <div style="font-size: 11px; color: #6B7280;">Fine Pure Gold Equivalent: <span id="revTotalFine"></span></div>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 11px; text-transform: uppercase; color: #047857; font-weight: 800;">Total Exchange Credit</div>
          <div style="font-size: 22px; font-weight: 900; color: #059669;" id="revFinalCredit"></div>
        </div>
      </div>

      <?php if (!$isManagerOrAdmin): ?>
        <div style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #92400E; margin-top: 14px;">
          ⚠️ As an Employee, this exchange will be saved as <strong>Pending Approval</strong>. A Store Manager or Super Admin must approve it before credit can be redeemed in POS.
        </div>
      <?php endif; ?>
    </div>
    <div class="modal-footer" style="background: #FAFBFB;">
      <button type="button" onclick="closeReviewModal()" class="btn btn-ghost btn-sm">Edit Details</button>
      <button type="button" onclick="executeSaveExchange('Approved')" class="btn btn-primary btn-sm" id="btnConfirmSave" style="background: #059669; border-color: #059669; padding: 8px 18px; font-weight: 800;">
        <?= $isManagerOrAdmin ? '✓ Confirm & Approve Valuation' : '✓ Submit for Approval' ?>
      </button>
    </div>
  </div>
</div>

<script>
let itemRowCounter = 0;

function createItemRowHtml(id) {
  return `
    <div class="item-assessment-row" id="itemRow_${id}" style="border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px; background: #FAFBFB; position: relative;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <span style="font-weight: 800; font-size: 12.5px; color: #374151;">Gold Item #${id}</span>
        <button type="button" onclick="removeItemRow(${id})" class="btn-remove-item" style="background: none; border: none; color: #DC2626; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px;">
          <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i> Remove
        </button>
      </div>

      <div style="display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
          <label class="form-label" style="font-size: 11px; font-weight: 700;">Item Type *</label>
          <select class="form-control item-type" style="font-size: 12.5px;">
            <option value="ring">Ring</option>
            <option value="chain" selected>Chain</option>
            <option value="necklace">Necklace</option>
            <option value="bracelet">Bracelet</option>
            <option value="earrings">Earrings</option>
            <option value="bangles">Bangles</option>
            <option value="coin">Gold Coin / Bar</option>
            <option value="other">Other Ornament</option>
          </select>
        </div>

        <div>
          <label class="form-label" style="font-size: 11px; font-weight: 700;">Purity Karat *</label>
          <select class="form-control item-purity-select" onchange="onPurityChange(${id}, this)" style="font-size: 12.5px;">
            <option value="24" data-label="24K (99.9%)">24K (99.9% Pure)</option>
            <option value="22" data-label="22K (91.6%)" selected>22K (91.6% Hallmarked)</option>
            <option value="20" data-label="20K (83.3%)">20K (83.3%)</option>
            <option value="18" data-label="18K (75.0%)">18K (75.0%)</option>
            <option value="14" data-label="14K (58.3%)">14K (58.3%)</option>
            <option value="custom" data-label="Custom">Custom Karat...</option>
          </select>
          <input type="number" step="0.1" min="1" max="24" class="form-control item-purity-custom" style="display: none; margin-top: 6px; font-size: 12px;" placeholder="Enter Karat e.g. 21.6" oninput="recalculateAll()">
        </div>

        <div>
          <label class="form-label" style="font-size: 11px; font-weight: 700;">Item Description / Hallmark</label>
          <input type="text" class="form-control item-desc" placeholder="e.g. 916 BIS Hallmark Chain" style="font-size: 12px;">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
        <div>
          <label class="form-label" style="font-size: 11px; font-weight: 700; color: #111827;">Gross Wt (g) *</label>
          <input type="number" step="0.001" min="0.001" class="form-control item-gross-wt" placeholder="10.000" style="font-weight: 700; font-size: 13px;" required oninput="recalculateAll()">
        </div>

        <div>
          <label class="form-label" style="font-size: 11px; font-weight: 700; color: #6B7280;">Stone/Enamel Wt (g)</label>
          <input type="number" step="0.001" min="0" value="0.000" class="form-control item-stone-wt" placeholder="0.000" style="font-size: 13px;" oninput="recalculateAll()">
        </div>

        <div>
          <label class="form-label" style="font-size: 11px; font-weight: 700; color: #6B7280;">Other Deductions (g)</label>
          <input type="number" step="0.001" min="0" value="0.000" class="form-control item-other-wt" placeholder="0.000" style="font-size: 13px;" oninput="recalculateAll()">
        </div>

        <div>
          <label class="form-label" style="font-size: 11px; font-weight: 700; color: #B45309;">Melting/Refining (₹)</label>
          <input type="number" step="0.01" min="0" value="0" class="form-control item-melting-ded" placeholder="₹0" style="font-size: 13px;" oninput="recalculateAll()">
        </div>
      </div>

      <!-- Real-time Row Calculation Bar -->
      <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; font-size: 12px;">
        <div>
          <span style="color: #6B7280;">Net Gold Wt: </span>
          <strong class="row-net-wt" style="color: #111827;">0.000 g</strong>
          <span style="color: #9CA3AF; margin: 0 6px;">|</span>
          <span style="color: #6B7280;">Fine Gold (24K eq): </span>
          <strong class="row-fine-wt" style="color: #B45309;">0.000 g</strong>
        </div>
        <div>
          <span style="color: #6B7280;">Assessed Item Value: </span>
          <strong class="row-assessed-val" style="color: #0F766E; font-size: 14px;">₹0.00</strong>
        </div>
      </div>
    </div>
  `;
}

function addNewItemRow() {
  itemRowCounter++;
  const container = document.getElementById('itemsContainer');
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = createItemRowHtml(itemRowCounter);
  container.appendChild(tempDiv.firstElementChild);
  if (window.lucide) lucide.createIcons();
  recalculateAll();
}

function removeItemRow(id) {
  const allRows = document.querySelectorAll('.item-assessment-row');
  if (allRows.length <= 1) {
    alert('At least one gold item is required.');
    return;
  }
  const el = document.getElementById(`itemRow_${id}`);
  if (el) el.remove();
  recalculateAll();
}

function onPurityChange(id, select) {
  const row = document.getElementById(`itemRow_${id}`);
  const customInput = row.querySelector('.item-purity-custom');
  if (select.value === 'custom') {
    customInput.style.display = 'block';
    customInput.required = true;
  } else {
    customInput.style.display = 'none';
    customInput.required = false;
  }
  recalculateAll();
}

function onSelectCustomer(select) {
  const opt = select.options[select.selectedIndex];
  if (!opt.value) return;
  document.getElementById('custId').value = opt.value;
  document.getElementById('custName').value = opt.getAttribute('data-name') || '';
  document.getElementById('custMobile').value = opt.getAttribute('data-mobile') || '';
}

function recalculateAll() {
  const goldRate24k = parseFloat(document.getElementById('goldRate24k').value) || 0;
  const headerBadge = document.getElementById('headerRateBadge');
  if (headerBadge) headerBadge.innerText = Number(goldRate24k).toLocaleString('en-IN');

  const rows = document.querySelectorAll('.item-assessment-row');
  let totalGross = 0;
  let totalDeductions = 0;
  let totalNet = 0;
  let totalFine = 0;
  let totalGrossVal = 0;
  let totalMonetary = 0;
  let totalFinalVal = 0;

  rows.forEach(row => {
    const grossWt = parseFloat(row.querySelector('.item-gross-wt').value) || 0;
    const stoneWt = parseFloat(row.querySelector('.item-stone-wt').value) || 0;
    const otherWt = parseFloat(row.querySelector('.item-other-wt').value) || 0;
    const meltingDed = parseFloat(row.querySelector('.item-melting-ded').value) || 0;

    const puritySelect = row.querySelector('.item-purity-select');
    let karat = parseFloat(puritySelect.value);
    if (puritySelect.value === 'custom') {
      karat = parseFloat(row.querySelector('.item-purity-custom').value) || 0;
    }

    const wtDeductions = stoneWt + otherWt;
    const netWt = Math.max(0, grossWt - wtDeductions);
    const fineWt = (netWt * karat) / 24;
    const grossVal = fineWt * goldRate24k;
    const assessedVal = Math.max(0, grossVal - meltingDed);

    // Update row display
    row.querySelector('.row-net-wt').innerText = `${netWt.toFixed(3)} g`;
    row.querySelector('.row-fine-wt').innerText = `${fineWt.toFixed(3)} g`;
    row.querySelector('.row-assessed-val').innerText = `₹${assessedVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    totalGross += grossWt;
    totalDeductions += wtDeductions;
    totalNet += netWt;
    totalFine += fineWt;
    totalGrossVal += grossVal;
    totalMonetary += meltingDed;
    totalFinalVal += assessedVal;
  });

  document.getElementById('summaryItemCount').innerText = rows.length;
  document.getElementById('sumGrossWeight').innerText = `${totalGross.toFixed(3)} g`;
  document.getElementById('sumDeductionsWeight').innerText = `-${totalDeductions.toFixed(3)} g`;
  document.getElementById('sumNetWeight').innerText = `${totalNet.toFixed(3)} g`;
  document.getElementById('sumFineWeight').innerText = `${totalFine.toFixed(3)} g`;
  document.getElementById('sumGrossValuation').innerText = `₹${totalGrossVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  document.getElementById('sumMonetaryDeductions').innerText = `-₹${totalMonetary.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  document.getElementById('sumFinalValue').innerText = `₹${totalFinalVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// Quick Add Customer Modal
function openNewCustomerModal() {
  document.getElementById('newCustomerModal').classList.add('active');
}
function closeNewCustomerModal() {
  document.getElementById('newCustomerModal').classList.remove('active');
}

async function handleQuickCreateCustomer(e) {
  e.preventDefault();
  const name = document.getElementById('quickCustName').value.trim();
  const mobile = document.getElementById('quickCustMobile').value.trim();
  const email = document.getElementById('quickCustEmail').value.trim();
  const city = document.getElementById('quickCustCity').value.trim();

  try {
    const res = await fetch('api/customers.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, mobile, email, city })
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('custId').value = data.customer_id;
      document.getElementById('custName').value = name;
      document.getElementById('custMobile').value = mobile;

      // Add to select
      const select = document.getElementById('custSelect');
      const opt = document.createElement('option');
      opt.value = data.customer_id;
      opt.text = `${name} (${mobile}) · Bronze`;
      opt.setAttribute('data-name', name);
      opt.setAttribute('data-mobile', mobile);
      opt.selected = true;
      select.add(opt);

      closeNewCustomerModal();
    } else {
      alert(data.error || 'Failed to create customer');
    }
  } catch (err) {
    alert('Network error while saving customer');
  }
}

// Review and Submission
let pendingPayload = null;

function handleReviewExchange(e) {
  e.preventDefault();
  prepareReview('Approved');
}

function submitAsDraft() {
  prepareReview('Draft');
}

function prepareReview(status) {
  const custName = document.getElementById('custName').value.trim();
  const custMobile = document.getElementById('custMobile').value.trim();
  const custId = document.getElementById('custId').value;
  const exchangeDate = document.getElementById('exchangeDate').value;
  const goldRate24k = parseFloat(document.getElementById('goldRate24k').value) || 0;
  const rateBasis = document.getElementById('rateBasis').value;
  const remarks = document.getElementById('overallRemarks').value.trim();

  if (!custName || !custMobile) {
    alert('Please enter customer name and valid mobile number.');
    return;
  }
  if (goldRate24k <= 0) {
    alert('Gold rate must be greater than zero.');
    return;
  }

  const rows = document.querySelectorAll('.item-assessment-row');
  if (rows.length === 0) {
    alert('Please add at least one gold item.');
    return;
  }

  const items = [];
  let modalItemsHtml = '';
  let totalNet = 0;
  let totalFine = 0;
  let totalCredit = 0;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const type = row.querySelector('.item-type').value;
    const desc = row.querySelector('.item-desc').value.trim();
    const puritySelect = row.querySelector('.item-purity-select');
    let karat = parseFloat(puritySelect.value);
    let purityLabel = puritySelect.options[puritySelect.selectedIndex].getAttribute('data-label') || `${karat}K`;

    if (puritySelect.value === 'custom') {
      karat = parseFloat(row.querySelector('.item-purity-custom').value) || 0;
      purityLabel = `${karat}K Custom`;
      if (karat <= 0 || karat > 24) {
        alert(`Item #${i+1}: Custom Karat must be between 1 and 24.`);
        return;
      }
    }

    const gross = parseFloat(row.querySelector('.item-gross-wt').value) || 0;
    const stone = parseFloat(row.querySelector('.item-stone-wt').value) || 0;
    const other = parseFloat(row.querySelector('.item-other-wt').value) || 0;
    const melting = parseFloat(row.querySelector('.item-melting-ded').value) || 0;

    if (gross <= 0) {
      alert(`Item #${i+1}: Gross weight must be greater than 0.`);
      return;
    }
    if ((stone + other) >= gross) {
      alert(`Item #${i+1}: Weight deductions cannot exceed gross weight.`);
      return;
    }

    const net = gross - (stone + other);
    const fine = (net * karat) / 24;
    const assessed = (fine * goldRate24k) - melting;

    totalNet += net;
    totalFine += fine;
    totalCredit += assessed;

    items.push({
      item_type: type,
      item_description: desc,
      purity_karat: karat,
      purity_label: purityLabel,
      gross_weight: gross,
      stone_deduction: stone,
      other_deduction: other,
      melting_charge: melting,
      remarks: desc
    });

    modalItemsHtml += `
      <tr style="border-bottom: 1px solid #E5E7EB;">
        <td style="padding: 6px 10px; font-weight: 600;">${type.toUpperCase()}${desc ? ` (${desc})` : ''}</td>
        <td style="padding: 6px 10px; text-align: center;"><span class="badge" style="background: #FEF3C7; color: #B45309;">${purityLabel}</span></td>
        <td style="padding: 6px 10px; text-align: right;">${gross.toFixed(3)}g</td>
        <td style="padding: 6px 10px; text-align: right; color: #DC2626;">-${(stone + other).toFixed(3)}g</td>
        <td style="padding: 6px 10px; text-align: right; font-weight: 700;">${net.toFixed(3)}g</td>
        <td style="padding: 6px 10px; text-align: right; font-weight: 700; color: #0F766E;">₹${assessed.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
      </tr>
    `;
  }

  pendingPayload = {
    customer_id: custId ? parseInt(custId) : null,
    customer_name: custName,
    customer_mobile: custMobile,
    exchange_date: exchangeDate,
    gold_rate_24k: goldRate24k,
    rate_basis: rateBasis,
    remarks: remarks,
    status: status,
    items: items,
    auto_register_customer: !custId ? true : false
  };

  // Populate Modal
  document.getElementById('revCustomer').innerText = custName;
  document.getElementById('revMobile').innerText = custMobile;
  document.getElementById('revDate').innerText = exchangeDate;
  document.getElementById('revRate').innerText = `Rate: ₹${goldRate24k.toLocaleString('en-IN')}/g (24K)`;
  document.getElementById('revItemsList').innerHTML = modalItemsHtml;
  document.getElementById('revTotalNet').innerText = `${totalNet.toFixed(3)} g`;
  document.getElementById('revTotalFine').innerText = `${totalFine.toFixed(3)} g`;
  document.getElementById('revFinalCredit').innerText = `₹${totalCredit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

  document.getElementById('reviewModal').classList.add('active');
}

function closeReviewModal() {
  document.getElementById('reviewModal').classList.remove('active');
}

async function executeSaveExchange(finalStatus) {
  if (!pendingPayload) return;
  pendingPayload.status = finalStatus;

  const btn = document.getElementById('btnConfirmSave');
  btn.disabled = true;
  btn.innerText = 'Processing & Generating Voucher...';

  try {
    const res = await fetch('api/old_gold_exchange.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(pendingPayload)
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = data.redirect_url;
    } else {
      alert('Error: ' + (data.error || 'Failed to save valuation.'));
      btn.disabled = false;
      btn.innerText = '✓ Confirm & Approve Valuation';
    }
  } catch (err) {
    alert('Network error. Please try again.');
    btn.disabled = false;
    btn.innerText = '✓ Confirm & Approve Valuation';
  }
}

// Initialize with 1 item row
window.addEventListener('DOMContentLoaded', () => {
  addNewItemRow();
  // If customer pre-selected via query param
  const custSelect = document.getElementById('custSelect');
  if (custSelect && custSelect.value) {
    onSelectCustomer(custSelect);
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
