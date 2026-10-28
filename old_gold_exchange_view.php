<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$db = getDBConnection();
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'Employee';
$isManagerOrAdmin = in_array($userRole, ['Super Admin', 'Store Manager']);

$exchangeId = (int)($_GET['id'] ?? 0);
$exchangeNo = trim($_GET['exchange_no'] ?? '');

$exchange = null;
$items = [];
$customer = null;

try {
    if ($exchangeId > 0) {
        $stmt = $db->prepare("SELECT * FROM old_gold_exchanges WHERE id = ? LIMIT 1");
        $stmt->execute([$exchangeId]);
        $exchange = $stmt->fetch();
    } elseif (!empty($exchangeNo)) {
        $stmt = $db->prepare("SELECT * FROM old_gold_exchanges WHERE exchange_no = ? LIMIT 1");
        $stmt->execute([$exchangeNo]);
        $exchange = $stmt->fetch();
    }

    if ($exchange) {
        $itemStmt = $db->prepare("SELECT * FROM old_gold_exchange_items WHERE exchange_id = ? ORDER BY id ASC");
        $itemStmt->execute([$exchange['id']]);
        $items = $itemStmt->fetchAll();

        if (!empty($exchange['customer_id'])) {
            $custStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
            $custStmt->execute([$exchange['customer_id']]);
            $customer = $custStmt->fetch();
        }

        // Fetch linked invoice & order if redeemed
        $linkedInvoice = null;
        if (!empty($exchange['redeemed_invoice_id'])) {
            $invStmt = $db->prepare("SELECT invoice_no, amount, status, invoice_date FROM invoices WHERE id = ?");
            $invStmt->execute([$exchange['redeemed_invoice_id']]);
            $linkedInvoice = $invStmt->fetch();
        }
    }
} catch (Exception $e) {}

if (!$exchange) {
    header('Location: old_gold_exchange.php');
    exit;
}

$pageTitle = "Exchange Voucher " . $exchange['exchange_no'] . " - Trivikratech POS";

// Auto print trigger if print=1
$autoPrint = !empty($_GET['print']);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';

$st = $exchange['status'];
$badgeBg = '#F3F4F6'; $badgeColor = '#374151'; $badgeBorder = '#E5E7EB';
if ($st === 'Approved') {
    $badgeBg = '#ECFDF5'; $badgeColor = '#059669'; $badgeBorder = '#A7F3D0';
} elseif ($st === 'Pending Approval') {
    $badgeBg = '#FFFBEB'; $badgeColor = '#D97706'; $badgeBorder = '#FDE68A';
} elseif ($st === 'Partially Redeemed') {
    $badgeBg = '#EFF6FF'; $badgeColor = '#2563EB'; $badgeBorder = '#BFDBFE';
} elseif ($st === 'Redeemed') {
    $badgeBg = '#F0FDFA'; $badgeColor = '#0F766E'; $badgeBorder = '#99F6E4';
} elseif ($st === 'Cancelled') {
    $badgeBg = '#FEF2F2'; $badgeColor = '#DC2626'; $badgeBorder = '#FECACA';
}
?>

<div style="padding: 24px 28px; max-width: 900px; margin: 0 auto;">
  
  <!-- Action Bar (Hidden on Print) -->
  <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <a href="old_gold_exchange.php" class="btn btn-ghost">
      <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
      <span>Back to All Exchanges</span>
    </a>

    <div style="display: flex; gap: 10px; align-items: center;">
      <button type="button" onclick="window.print()" class="btn btn-ghost" style="background: #FFFFFF;">
        <i data-lucide="printer" style="width: 16px; height: 16px;"></i>
        <span>Print Voucher Receipt</span>
      </button>

      <?php if ($st === 'Approved' && (float)$exchange['credit_balance'] > 0): ?>
        <a href="pos.php?exchange_id=<?= $exchange['id'] ?>" class="btn btn-primary" style="background: #059669; border-color: #059669; font-weight: 800;">
          <i data-lucide="shopping-cart" style="width: 16px; height: 16px;"></i>
          <span>Apply in POS (₹<?= number_format((float)$exchange['credit_balance'], 2) ?> Available)</span>
        </a>
      <?php elseif ($st === 'Pending Approval' && $isManagerOrAdmin): ?>
        <button type="button" onclick="approveExchange(<?= $exchange['id'] ?>)" class="btn btn-primary" style="background: #D97706; border-color: #D97706;">
          <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
          <span>Approve Valuation</span>
        </button>
      <?php endif; ?>

      <?php if ($st !== 'Redeemed' && $st !== 'Cancelled' && $isManagerOrAdmin): ?>
        <button type="button" onclick="cancelExchange(<?= $exchange['id'] ?>)" class="btn btn-ghost" style="color: #DC2626; border-color: #FECACA;">
          <i data-lucide="x-circle" style="width: 16px; height: 16px;"></i>
          <span>Cancel</span>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Status Banners (Hidden on Print) -->
  <div class="no-print">
    <?php if ($st === 'Redeemed' && $linkedInvoice): ?>
      <div style="background: #F0FDFA; border: 1px solid #99F6E4; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <i data-lucide="check-circle-2" style="width: 22px; height: 22px; color: #0F766E;"></i>
          <div>
            <div style="font-weight: 800; color: #0F766E; font-size: 14px;">Credit Fully Redeemed</div>
            <div style="font-size: 12px; color: #115E59;">
              Applied against Sales Invoice <strong><?= htmlspecialchars($linkedInvoice['invoice_no']) ?></strong> on <?= date('d M Y', strtotime($exchange['redeemed_at'])) ?>
            </div>
          </div>
        </div>
        <a href="view-invoice.php?invoice_no=<?= urlencode($linkedInvoice['invoice_no']) ?>" class="btn btn-primary btn-sm" style="background: #0F766E; border-color: #0F766E;">
          View Linked Invoice
        </a>
      </div>
    <?php elseif ($st === 'Pending Approval'): ?>
      <div style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i data-lucide="clock" style="width: 22px; height: 22px; color: #D97706;"></i>
        <div>
          <div style="font-weight: 800; color: #B45309; font-size: 14px;">Awaiting Manager Approval</div>
          <div style="font-size: 12px; color: #92400E;">
            This valuation was submitted by <?= htmlspecialchars($exchange['created_by']) ?>. An authorized manager must review and approve it before it can be applied to POS sales.
          </div>
        </div>
      </div>
    <?php elseif ($st === 'Cancelled'): ?>
      <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i data-lucide="alert-triangle" style="width: 22px; height: 22px; color: #DC2626;"></i>
        <div>
          <div style="font-weight: 800; color: #DC2626; font-size: 14px;">Voucher Cancelled</div>
          <div style="font-size: 12px; color: #991B1B;">
            Cancelled by <?= htmlspecialchars($exchange['cancelled_by'] ?? 'Staff') ?> on <?= date('d M Y', strtotime($exchange['cancelled_at'])) ?>. Reason: <?= htmlspecialchars($exchange['cancel_reason'] ?? 'N/A') ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- PRINTABLE VOUCHER DOCUMENT -->
  <div id="print-area" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 36px 40px; box-shadow: var(--shadow-md);">
    
    <!-- Voucher Top Branding -->
    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #D97706; padding-bottom: 20px; margin-bottom: 24px;">
      <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
          <span style="font-size: 26px;">👑</span>
          <h1 style="font-size: 22px; font-weight: 900; color: #111827; margin: 0;">
            <?= htmlspecialchars(getSetting('store_name', 'Trivikratech Jewellers')) ?>
          </h1>
        </div>
        <div style="font-size: 12.5px; color: #4B5563;"><?= htmlspecialchars(getSetting('address', 'Shop 12, Gold Souk Market, Bandra West, Mumbai 400050')) ?></div>
        <div style="font-size: 12px; color: #4B5563; font-weight: 600; margin-top: 2px;">
          GSTIN: <span style="font-family: monospace;"><?= htmlspecialchars(getSetting('gstin', '27AABFA1234Z1ZX')) ?></span> | Phone: <?= htmlspecialchars(getSetting('phone', '+91 98765 00000')) ?>
        </div>
      </div>

      <div style="text-align: right;">
        <span class="badge" style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border: 1px solid <?= $badgeBorder ?>; font-size: 12px; padding: 4px 12px; margin-bottom: 8px; font-weight: 800;">
          OLD GOLD EXCHANGE VOUCHER
        </span>
        <div style="font-size: 15px; font-weight: 900; color: #B45309; font-family: monospace;">
          <?= htmlspecialchars($exchange['exchange_no']) ?>
        </div>
        <div style="font-size: 12px; color: #6B7280; margin-top: 2px;">
          Date: <strong><?= date('d M Y', strtotime($exchange['exchange_date'])) ?></strong>
        </div>
        <div style="font-size: 12px; color: #6B7280;">
          Branch: <?= htmlspecialchars($exchange['branch'] ?? 'Bandra West') ?>
        </div>
      </div>
    </div>

    <!-- Customer & Valuation Basis Meta -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 16px; background: #FAFBFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px;">
      <div>
        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #6B7280; margin-bottom: 3px;">Customer Details:</div>
        <div style="font-size: 15px; font-weight: 800; color: #111827;">
          <?= htmlspecialchars($exchange['customer_name']) ?>
        </div>
        <div style="font-size: 13px; color: #4B5563;">
          Mobile: <?= htmlspecialchars($exchange['customer_mobile'] ?? 'N/A') ?>
        </div>
        <?php if (!empty($customer['city'])): ?>
          <div style="font-size: 12px; color: #6B7280;">City: <?= htmlspecialchars($customer['city']) ?></div>
        <?php endif; ?>
      </div>

      <div style="border-left: 1px solid #E5E7EB; padding-left: 16px;">
        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #6B7280; margin-bottom: 3px;">Valuation Calibration:</div>
        <div style="font-size: 13px; font-weight: 700; color: #B45309;">
          24K Gold Rate: ₹<?= number_format((float)$exchange['gold_rate_24k'], 2) ?> / gram
        </div>
        <div style="font-size: 11.5px; color: #4B5563; margin-top: 2px;">
          Formula: <span style="font-family: monospace;">Fine Wt = Net Wt × (Karat / 24)</span>
        </div>
        <div style="font-size: 11px; color: #059669; font-weight: 600; margin-top: 2px;">
          Assessed by: <?= htmlspecialchars($exchange['created_by']) ?>
          <?= !empty($exchange['approved_by']) ? ' · Approved: ' . htmlspecialchars($exchange['approved_by']) : '' ?>
        </div>
      </div>
    </div>

    <!-- Items Assessment Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
      <thead>
        <tr style="background: #F3F4F6; border-bottom: 2px solid #E5E7EB; color: #374151;">
          <th style="padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 800;">#</th>
          <th style="padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 800;">Item & Hallmark</th>
          <th style="padding: 10px 12px; text-align: center; font-size: 12px; font-weight: 800;">Purity</th>
          <th style="padding: 10px 12px; text-align: right; font-size: 12px; font-weight: 800;">Gross (g)</th>
          <th style="padding: 10px 12px; text-align: right; font-size: 12px; font-weight: 800;">Deductions (g)</th>
          <th style="padding: 10px 12px; text-align: right; font-size: 12px; font-weight: 800;">Net Wt (g)</th>
          <th style="padding: 10px 12px; text-align: right; font-size: 12px; font-weight: 800;">Fine Wt (g)</th>
          <th style="padding: 10px 12px; text-align: right; font-size: 12px; font-weight: 800;">Melting (₹)</th>
          <th style="padding: 10px 12px; text-align: right; font-size: 12px; font-weight: 800;">Assessed Value</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $it): 
          $wtDeds = (float)$it['stone_deduction'] + (float)$it['other_deduction'];
        ?>
          <tr style="border-bottom: 1px solid #E5E7EB; font-size: 12.5px;">
            <td style="padding: 10px 12px; color: #6B7280; font-weight: 600;"><?= $idx + 1 ?></td>
            <td style="padding: 10px 12px;">
              <div style="font-weight: 700; color: #111827; text-transform: uppercase;">
                <?= htmlspecialchars($it['item_type']) ?>
              </div>
              <?php if (!empty($it['item_description'])): ?>
                <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($it['item_description']) ?></div>
              <?php endif; ?>
            </td>
            <td style="padding: 10px 12px; text-align: center;">
              <span class="badge" style="background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; font-weight: 700; font-size: 11px;">
                <?= htmlspecialchars($it['purity_label']) ?>
              </span>
            </td>
            <td style="padding: 10px 12px; text-align: right;"><?= number_format((float)$it['gross_weight'], 3) ?></td>
            <td style="padding: 10px 12px; text-align: right; color: #DC2626;">-<?= number_format($wtDeds, 3) ?></td>
            <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #111827;"><?= number_format((float)$it['net_weight'], 3) ?></td>
            <td style="padding: 10px 12px; text-align: right; font-weight: 600; color: #B45309;"><?= number_format((float)$it['fine_gold_weight'], 3) ?></td>
            <td style="padding: 10px 12px; text-align: right; color: #6B7280;">₹<?= number_format((float)$it['melting_charge'], 2) ?></td>
            <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #0F766E;">
              <?= formatFullINR($it['assessed_value']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Valuation Breakdown Totals Box -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
      <div style="width: 380px; background: #FAFBFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; color: #4B5563;">
          <span>Total Gross Weight:</span>
          <span style="font-weight: 600;"><?= number_format((float)$exchange['total_gross_weight'], 3) ?> g</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; color: #DC2626;">
          <span>Weight Deductions:</span>
          <span>-<?= number_format((float)$exchange['total_weight_deduction'], 3) ?> g</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; color: #111827; font-weight: 700; border-top: 1px solid #E5E7EB; margin-top: 3px;">
          <span>Total Net Gold Weight:</span>
          <span><?= number_format((float)$exchange['total_net_weight'], 3) ?> g</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; color: #B45309; font-weight: 700;">
          <span>Total 24K Pure Gold Equivalent:</span>
          <span><?= number_format((float)$exchange['total_fine_gold_weight'], 3) ?> g</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; color: #4B5563;">
          <span>Gross Gold Valuation:</span>
          <span style="font-weight: 600;"><?= formatFullINR($exchange['gross_valuation']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; color: #DC2626;">
          <span>Melting / Refining Deductions:</span>
          <span>-<?= formatFullINR($exchange['total_monetary_deductions']) ?></span>
        </div>

        <div style="display: flex; justify-content: space-between; padding: 10px 0 4px; border-top: 2px solid #D97706; margin-top: 8px; font-size: 18px; font-weight: 900; color: #B45309;">
          <span>Final Exchange Credit:</span>
          <span><?= formatFullINR($exchange['final_exchange_value']) ?></span>
        </div>

        <?php if ($st === 'Partially Redeemed'): ?>
          <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; color: #2563EB; font-weight: 700;">
            <span>Unused Credit Balance:</span>
            <span><?= formatFullINR($exchange['credit_balance']) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Customer Acknowledgement & Terms -->
    <div style="border-top: 1px solid var(--color-border); padding-top: 20px;">
      <div style="font-size: 11.5px; color: #4B5563; line-height: 1.6; background: #FFFBEB; padding: 12px 16px; border-radius: 8px; border: 1px solid #FDE68A; margin-bottom: 24px;">
        <strong>Customer Declaration & Exchange Terms:</strong><br>
        1. I/We hereby declare that the old gold items described above are my/our bonafide personal property and are free from all encumbrances, disputes, or claims.<br>
        2. I/We have verified and agreed to the gross weight, weight deductions for stones/wax/dirt, net purity karats, and monetary valuations as computed above.<br>
        3. This voucher credit is valid for redemption against new gold, diamond, or silver jewellery purchases at <?= htmlspecialchars(getSetting('store_name', 'Trivikratech Jewellers')) ?>.<br>
        4. Old gold once melted or processed cannot be returned or re-claimed.
      </div>

      <!-- Signature Blocks -->
      <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 20px;">
        <div style="text-align: center;">
          <div style="border-top: 1px solid #111827; width: 200px; padding-top: 6px; font-size: 12px; font-weight: 700;">
            Customer Signature
          </div>
          <div style="font-size: 11px; color: #6B7280; margin-top: 2px;"><?= htmlspecialchars($exchange['customer_name']) ?></div>
        </div>

        <div style="text-align: center;">
          <div style="border-top: 1px solid #111827; width: 200px; padding-top: 6px; font-size: 12px; font-weight: 700;">
            Authorized Assessor / Store Signatory
          </div>
          <div style="font-size: 11px; color: #6B7280; margin-top: 2px;"><?= htmlspecialchars(getSetting('store_name', 'Trivikratech Jewellers')) ?></div>
        </div>
      </div>
    </div>

  </div>

</div>

<script>
async function approveExchange(id) {
  if (!confirm('Approve this exchange valuation? It will immediately be eligible for POS credit redemption.')) return;
  try {
    const res = await fetch('api/old_gold_exchange.php?action=approve', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ exchange_id: id })
    });
    const data = await res.json();
    if (data.success) {
      alert('Valuation approved successfully!');
      location.reload();
    } else {
      alert(data.error || 'Failed to approve');
    }
  } catch(e) {
    alert('Network error');
  }
}

async function cancelExchange(id) {
  const reason = prompt('Please enter the reason for cancelling this exchange voucher:');
  if (reason === null) return;
  if (!reason.trim()) {
    alert('Cancellation reason is required.');
    return;
  }
  try {
    const res = await fetch('api/old_gold_exchange.php?action=cancel', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ exchange_id: id, reason })
    });
    const data = await res.json();
    if (data.success) {
      alert('Exchange cancelled successfully.');
      location.reload();
    } else {
      alert(data.error || 'Failed to cancel');
    }
  } catch(e) {
    alert('Network error');
  }
}

<?php if ($autoPrint): ?>
window.addEventListener('load', () => {
  setTimeout(() => window.print(), 400);
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
