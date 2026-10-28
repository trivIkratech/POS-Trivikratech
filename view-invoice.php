<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$db = getDBConnection();
$invoiceNo = $_GET['invoice_no'] ?? '';
$orderNo = $_GET['order_no'] ?? '';

$invoice = null;
$order = null;
$items = [];
$customer = null;

try {
    if (!empty($invoiceNo)) {
        $stmt = $db->prepare("SELECT * FROM invoices WHERE invoice_no = ? LIMIT 1");
        $stmt->execute([$invoiceNo]);
        $invoice = $stmt->fetch();
        if ($invoice && !empty($invoice['order_id'])) {
            $ordStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
            $ordStmt->execute([$invoice['order_id']]);
            $order = $ordStmt->fetch();
        }
    } else if (!empty($orderNo)) {
        $ordStmt = $db->prepare("SELECT * FROM orders WHERE order_no = ? LIMIT 1");
        $ordStmt->execute([$orderNo]);
        $order = $ordStmt->fetch();
        if ($order) {
            $stmt = $db->prepare("SELECT * FROM invoices WHERE order_id = ? LIMIT 1");
            $stmt->execute([$order['id']]);
            $invoice = $stmt->fetch();
        }
    }

    if ($order) {
        $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $items = $itemStmt->fetchAll();

        if ($order['customer_id']) {
            $custStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
            $custStmt->execute([$order['customer_id']]);
            $customer = $custStmt->fetch();
        }
    }
} catch (Exception $e) {}

$pageTitle = "Tax Invoice " . ($invoice['invoice_no'] ?? 'INV') . " - Trivikratech POS";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <a href="invoices.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to Invoices</span>
  </a>

  <div style="display: flex; gap: 10px;">
    <button type="button" onclick="window.print()" class="btn btn-primary">
      <i data-lucide="printer" style="width: 16px; height: 16px;"></i>
      <span>Print GST Invoice</span>
    </button>
  </div>
</div>

<!-- Printable Tax Invoice Document Container -->
<div id="print-area" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 36px 40px; max-width: 800px; margin: 0 auto; box-shadow: var(--shadow-md);">
  <!-- Header -->
  <div style="display: flex; justify-content: space-between; border-bottom: 2px solid var(--color-primary); padding-bottom: 20px; margin-bottom: 24px;">
    <div>
      <h1 style="font-size: 24px; font-weight: 900; color: var(--color-primary-dark); margin: 0 0 4px;">
        <?= htmlspecialchars(getSetting('store_name', 'Kulthe Suvarnkar')) ?>
      </h1>
      <div style="font-size: 13px; color: #4B5563;"><?= htmlspecialchars(getSetting('address', 'Bandra West, Mumbai 400050')) ?></div>
      <div style="font-size: 13px; color: #4B5563; font-weight: 600; margin-top: 2px;">
        GSTIN: <span style="font-family: monospace;"><?= htmlspecialchars(getSetting('gstin', '27AABFA1234Z1ZX')) ?></span> | Phone: <?= htmlspecialchars(getSetting('phone', '+91 98765 00000')) ?>
      </div>
    </div>
    <div style="text-align: right;">
      <span class="badge" style="background: #0F766E; color: #fff; font-size: 12px; padding: 4px 12px; margin-bottom: 8px;">
        TAX INVOICE
      </span>
      <div style="font-size: 14px; font-weight: 800; color: #111827;">Invoice: <?= htmlspecialchars($invoice['invoice_no'] ?? ($order['order_no'] ?? 'INV-001')) ?></div>
      <div style="font-size: 12px; color: #6B7280;">Date: <?= htmlspecialchars($invoice['invoice_date'] ?? date('Y-m-d')) ?></div>
      <div style="font-size: 12px; color: #6B7280;">Branch: <?= htmlspecialchars($order['branch'] ?? 'Bandra West') ?></div>
    </div>
  </div>

  <!-- Bill To Info -->
  <div style="display: flex; justify-content: space-between; background: var(--color-surface-2); padding: 14px 18px; border-radius: 12px; margin-bottom: 24px;">
    <div>
      <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #6B7280; margin-bottom: 4px;">Billed To:</div>
      <div style="font-size: 15px; font-weight: 800; color: #111827;"><?= htmlspecialchars($customer['name'] ?? ($order['customer_name'] ?? 'Walk-in Customer')) ?></div>
      <?php if (!empty($customer['mobile'])): ?>
        <div style="font-size: 13px; color: #4B5563;">Mobile: <?= htmlspecialchars($customer['mobile']) ?></div>
      <?php endif; ?>
      <?php if (!empty($customer['gstin'])): ?>
        <div style="font-size: 12px; font-weight: 700; color: #0F766E;">Customer GSTIN: <?= htmlspecialchars($customer['gstin']) ?></div>
      <?php endif; ?>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #6B7280; margin-bottom: 4px;">Payment Info:</div>
      <div style="font-size: 14px; font-weight: 700; color: #111827;">Mode: <?= htmlspecialchars($order['payment_method'] ?? 'Cash') ?></div>
      <div style="font-size: 12px; color: #059669; font-weight: 700;">Status: Paid in Full</div>
    </div>
  </div>

  <!-- Items Table -->
  <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
    <thead>
      <tr style="background: #F3F4F6; border-bottom: 2px solid #E5E7EB;">
        <th style="padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 800; color: #374151;">Description</th>
        <th style="padding: 10px 14px; text-align: center; font-size: 12px; font-weight: 800; color: #374151;">Purity</th>
        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 800; color: #374151;">Weight (g)</th>
        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 800; color: #374151;">Qty</th>
        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 800; color: #374151;">Rate (₹)</th>
        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 800; color: #374151;">Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr style="border-bottom: 1px solid #E5E7EB;">
          <td style="padding: 12px 14px; font-weight: 600;">Gold & Diamond Jewellery Assortment</td>
          <td style="padding: 12px 14px; text-align: center;">22K Hallmarked</td>
          <td style="padding: 12px 14px; text-align: right;">18.500</td>
          <td style="padding: 12px 14px; text-align: right;">1</td>
          <td style="padding: 12px 14px; text-align: right;"><?= formatFullINR($order['subtotal'] ?? 154000) ?></td>
          <td style="padding: 12px 14px; text-align: right; font-weight: 700;"><?= formatFullINR($order['subtotal'] ?? 154000) ?></td>
        </tr>
      <?php else: ?>
        <?php foreach ($items as $it): ?>
          <tr style="border-bottom: 1px solid #E5E7EB;">
            <td style="padding: 12px 14px;">
              <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($it['product_name']) ?></div>
              <div style="font-size: 11px; color: #6B7280;">SKU: <?= htmlspecialchars($it['sku'] ?? 'N/A') ?> · HSN: 7113</div>
            </td>
            <td style="padding: 12px 14px; text-align: center;"><span class="badge badge-gold"><?= htmlspecialchars($it['purity'] ?? '22K') ?></span></td>
            <td style="padding: 12px 14px; text-align: right; font-weight: 600;"><?= $it['weight'] ?> g</td>
            <td style="padding: 12px 14px; text-align: right;"><?= (int)$it['qty'] ?></td>
            <td style="padding: 12px 14px; text-align: right;"><?= formatFullINR($it['unit_price']) ?></td>
            <td style="padding: 12px 14px; text-align: right; font-weight: 700;"><?= formatFullINR($it['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Calculation Summary -->
  <div style="display: flex; justify-content: flex-end;">
    <div style="width: 300px;">
      <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; color: #4B5563;">
        <span>Taxable Subtotal:</span>
        <span style="font-weight: 600;"><?= formatFullINR($order['subtotal'] ?? ($invoice['amount'] ?? 154000)) ?></span>
      </div>
      <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; color: #4B5563;">
        <span>CGST (1.5%):</span>
        <span><?= formatFullINR(($order['gst'] ?? ($invoice['gst'] ?? 4620)) / 2) ?></span>
      </div>
      <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; color: #4B5563;">
        <span>SGST (1.5%):</span>
        <span><?= formatFullINR(($order['gst'] ?? ($invoice['gst'] ?? 4620)) / 2) ?></span>
      </div>
      <?php if (!empty($order['discount']) && $order['discount'] > 0): ?>
        <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; color: #059669; font-weight: 600;">
          <span>Discount Applied:</span>
          <span>-<?= formatFullINR($order['discount']) ?></span>
        </div>
      <?php endif; ?>
      <?php if (!empty($order['old_gold_credit']) && $order['old_gold_credit'] > 0): 
        $ogeNo = 'Voucher';
        if (!empty($order['old_gold_exchange_id'])) {
          try {
            $ogeLookup = $db->prepare("SELECT exchange_no FROM old_gold_exchanges WHERE id = ?");
            $ogeLookup->execute([$order['old_gold_exchange_id']]);
            $ogeNo = $ogeLookup->fetchColumn() ?: 'Voucher';
          } catch(Exception $e){}
        }
      ?>
        <div style="display: flex; justify-content: space-between; padding: 4px 8px; font-size: 12.5px; color: #B45309; font-weight: 700; background: #FEF3C7; border: 1px solid #FDE68A; border-radius: 6px; margin: 6px 0;">
          <span>− Old Gold Credit (<?= htmlspecialchars($ogeNo) ?>):</span>
          <span>-<?= formatFullINR($order['old_gold_credit']) ?></span>
        </div>
      <?php endif; ?>

      <div style="display: flex; justify-content: space-between; padding: 10px 0; border-top: 2px solid var(--color-primary); margin-top: 8px; font-size: 18px; font-weight: 900; color: var(--color-primary-dark);">
        <span>Total Net Payable:</span>
        <span><?= formatFullINR($order['total'] ?? ($invoice['amount'] ?? 153620)) ?></span>
      </div>
    </div>
  </div>

  <!-- Terms & Signature Footer -->
  <div style="margin-top: 40px; border-top: 1px solid var(--color-border); padding-top: 20px; display: flex; justify-content: space-between; align-items: flex-end;">
    <div style="font-size: 11.5px; color: #6B7280; max-width: 440px; line-height: 1.5;">
      <strong>Terms & Conditions:</strong><br>
      1. All gold jewellery is 100% BIS Hallmarked.<br>
      2. Diamonds certified by authorized international gemological labs.<br>
      3. Exchange allowable within 7 days against original tax invoice.
    </div>
    <div style="text-align: center;">
      <div style="border-top: 1px solid #111827; width: 180px; padding-top: 6px; font-size: 12px; font-weight: 700;">
        Authorized Signatory
      </div>
      <div style="font-size: 11px; color: #6B7280; margin-top: 2px;"><?= htmlspecialchars(getSetting('store_name', 'Trivikratech Jewellers')) ?></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
