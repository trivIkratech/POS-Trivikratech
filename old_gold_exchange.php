<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "Old Gold Exchange & Valuation Vault - Trivikratech POS";
$db = getDBConnection();
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'Employee';
$isManagerOrAdmin = in_array($userRole, ['Super Admin', 'Store Manager']);

// Filters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(e.exchange_no LIKE ? OR e.customer_name LIKE ? OR e.customer_mobile LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($statusFilter)) {
    $where[] = "e.status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFrom)) {
    $where[] = "e.exchange_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $where[] = "e.exchange_date <= ?";
    $params[] = $dateTo;
}

$whereClause = implode(" AND ", $where);

// Metrics
$totalExchanges = 0;
$totalNetWeight = 0.0;
$totalValuation = 0.0;
$totalAvailableCredit = 0.0;

try {
    $metricsStmt = $db->query("
        SELECT 
            COUNT(*) as total_count,
            COALESCE(SUM(total_net_weight), 0) as sum_net_weight,
            COALESCE(SUM(final_exchange_value), 0) as sum_valuation,
            COALESCE(SUM(CASE WHEN status IN ('Approved', 'Partially Redeemed') THEN credit_balance ELSE 0 END), 0) as sum_credit_balance
        FROM old_gold_exchanges
        WHERE status != 'Cancelled'
    ");
    $metrics = $metricsStmt->fetch();
    if ($metrics) {
        $totalExchanges = (int)$metrics['total_count'];
        $totalNetWeight = (float)$metrics['sum_net_weight'];
        $totalValuation = (float)$metrics['sum_valuation'];
        $totalAvailableCredit = (float)$metrics['sum_credit_balance'];
    }
} catch (Exception $e) {}

// Fetch List
$exchanges = [];
try {
    $sql = "
        SELECT e.*, 
               (SELECT COUNT(*) FROM old_gold_exchange_items WHERE exchange_id = e.id) as item_count,
               i.invoice_no as linked_invoice_no,
               o.order_no as linked_order_no
        FROM old_gold_exchanges e
        LEFT JOIN invoices i ON e.redeemed_invoice_id = i.id
        LEFT JOIN orders o ON e.redeemed_order_id = o.id
        WHERE {$whereClause}
        ORDER BY e.id DESC
        LIMIT 100
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $exchanges = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="padding: 24px 28px;">
  <!-- Page Header -->
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <div style="width: 40px; height: 40px; border-radius: 12px; background: #FEF3C7; border: 1px solid #FDE68A; display: flex; align-items: center; justify-content: center; color: #D97706; font-size: 20px;">
          ⚖️
        </div>
        <div>
          <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0; line-height: 1.2;">
            Old Gold Exchange & Valuation Vault
          </h1>
          <p style="font-size: 13.5px; color: #6B7280; margin: 3px 0 0;">
            Accept customer old gold ornaments, assess purity & weight, generate valuation vouchers & redeem against POS purchases
          </p>
        </div>
      </div>
    </div>

    <div style="display: flex; gap: 10px; align-items: center;">
      <a href="pos.php" class="btn btn-ghost" style="background: #FFFFFF;">
        <i data-lucide="shopping-cart" style="width: 16px; height: 16px; color: var(--color-primary);"></i>
        <span>Go to POS Billing</span>
      </a>
      <a href="old_gold_exchange_create.php" class="btn btn-primary" style="background: #D97706; border-color: #D97706; box-shadow: 0 4px 14px rgba(217, 119, 6, 0.3);">
        <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
        <span>+ New Gold Exchange</span>
      </a>
    </div>
  </div>

  <!-- KPI Metrics Grid -->
  <div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
    <div class="kpi-card" style="border-left: 4px solid #D97706;">
      <div class="kpi-value" style="color: #D97706;"><?= number_format($totalExchanges) ?></div>
      <div class="kpi-label">Total Gold Exchanges</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #B45309;">
      <div class="kpi-value"><?= number_format($totalNetWeight, 3) ?> g</div>
      <div class="kpi-label">Total Net Gold Deposited</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #0F766E;">
      <div class="kpi-value"><?= formatFullINR($totalValuation) ?></div>
      <div class="kpi-label">Gross Gold Valuation</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #059669; background: #F0FDF4;">
      <div class="kpi-value" style="color: #059669;"><?= formatFullINR($totalAvailableCredit) ?></div>
      <div class="kpi-label">Active Unredeemed Credit</div>
    </div>
  </div>

  <!-- Filters & Search Toolbar -->
  <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 16px 20px; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
    <form method="GET" action="old_gold_exchange.php" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 220px;">
        <label class="form-label" style="font-size: 11.5px; text-transform: uppercase; color: #6B7280;">Search Records</label>
        <div style="position: relative;">
          <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9CA3AF;"></i>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Exchange #, customer name, mobile..." class="form-control" style="padding-left: 36px; font-size: 13px;">
        </div>
      </div>

      <div style="width: 170px;">
        <label class="form-label" style="font-size: 11.5px; text-transform: uppercase; color: #6B7280;">Status</label>
        <select name="status" class="form-control" style="font-size: 13px;">
          <option value="">All Statuses</option>
          <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved (Ready)</option>
          <option value="Pending Approval" <?= $statusFilter === 'Pending Approval' ? 'selected' : '' ?>>Pending Approval</option>
          <option value="Partially Redeemed" <?= $statusFilter === 'Partially Redeemed' ? 'selected' : '' ?>>Partially Redeemed</option>
          <option value="Redeemed" <?= $statusFilter === 'Redeemed' ? 'selected' : '' ?>>Redeemed (Billed)</option>
          <option value="Draft" <?= $statusFilter === 'Draft' ? 'selected' : '' ?>>Draft</option>
          <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div style="width: 140px;">
        <label class="form-label" style="font-size: 11.5px; text-transform: uppercase; color: #6B7280;">From Date</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="form-control" style="font-size: 13px;">
      </div>

      <div style="width: 140px;">
        <label class="form-label" style="font-size: 11.5px; text-transform: uppercase; color: #6B7280;">To Date</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="form-control" style="font-size: 13px;">
      </div>

      <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary btn-sm" style="padding: 9px 16px; font-size: 13px;">
          <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
          <span>Filter</span>
        </button>
        <?php if (!empty($search) || !empty($statusFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
          <a href="old_gold_exchange.php" class="btn btn-ghost btn-sm" style="padding: 9px 12px; font-size: 13px;">
            Clear
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Exchanges Data Table -->
  <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
    <div style="padding: 14px 20px; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; background: #FAFBFB;">
      <div style="font-weight: 700; font-size: 14px; color: #111827;">
        Exchange Vouchers (<?= count($exchanges) ?> records)
      </div>
      <div style="font-size: 12px; color: #6B7280;">
        Calibrated to 24K pure gold equivalent formula
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
        <thead>
          <tr style="background: #F3F4F6; border-bottom: 2px solid #E5E7EB; color: #374151;">
            <th style="padding: 12px 16px; font-weight: 700;">Exchange #</th>
            <th style="padding: 12px 16px; font-weight: 700;">Customer</th>
            <th style="padding: 12px 16px; font-weight: 700;">Date & Branch</th>
            <th style="padding: 12px 16px; font-weight: 700; text-align: center;">Items</th>
            <th style="padding: 12px 16px; font-weight: 700; text-align: right;">Net Wt (g)</th>
            <th style="padding: 12px 16px; font-weight: 700; text-align: right;">Fine Gold (g)</th>
            <th style="padding: 12px 16px; font-weight: 700; text-align: right;">24K Rate</th>
            <th style="padding: 12px 16px; font-weight: 700; text-align: right;">Credit Value</th>
            <th style="padding: 12px 16px; font-weight: 700; text-align: center;">Status</th>
            <th style="padding: 12px 16px; font-weight: 700; text-align: center;">Linked Bill</th>
            <th style="padding: 12px 16px; font-weight: 700; text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($exchanges)): ?>
            <tr>
              <td colspan="11" style="padding: 48px; text-align: center; color: #9CA3AF;">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                  <span style="font-size: 40px; margin-bottom: 12px;">⚖️</span>
                  <div style="font-weight: 700; font-size: 16px; color: #374151; margin-bottom: 4px;">No Gold Exchanges Found</div>
                  <div style="font-size: 13px; color: #6B7280; max-width: 400px; margin-bottom: 16px;">
                    Start a new gold assessment to calculate exchange value and issue credit vouchers to customers.
                  </div>
                  <a href="old_gold_exchange_create.php" class="btn btn-primary btn-sm" style="background: #D97706; border-color: #D97706;">
                    + Create First Exchange Voucher
                  </a>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($exchanges as $e): 
              $st = $e['status'];
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
              <tr style="border-bottom: 1px solid #E5E7EB; transition: background 0.15s ease;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#FFFFFF'">
                <td style="padding: 12px 16px;">
                  <a href="old_gold_exchange_view.php?id=<?= $e['id'] ?>" style="font-weight: 800; color: #D97706; font-family: monospace; font-size: 13px;">
                    <?= htmlspecialchars($e['exchange_no']) ?>
                  </a>
                </td>
                <td style="padding: 12px 16px;">
                  <div style="font-weight: 700; color: #111827;"><?= htmlspecialchars($e['customer_name']) ?></div>
                  <div style="font-size: 11.5px; color: #6B7280;"><?= htmlspecialchars($e['customer_mobile'] ?? 'N/A') ?></div>
                </td>
                <td style="padding: 12px 16px;">
                  <div style="font-weight: 600; color: #374151;"><?= date('d M Y', strtotime($e['exchange_date'])) ?></div>
                  <div style="font-size: 11px; color: #9CA3AF;"><?= htmlspecialchars($e['branch'] ?? 'Bandra West') ?></div>
                </td>
                <td style="padding: 12px 16px; text-align: center;">
                  <span class="badge" style="background: #F3F4F6; color: #374151; font-weight: 600;">
                    <?= (int)$e['item_count'] ?> item<?= (int)$e['item_count'] === 1 ? '' : 's' ?>
                  </span>
                </td>
                <td style="padding: 12px 16px; text-align: right; font-weight: 600; color: #111827;">
                  <?= number_format((float)$e['total_net_weight'], 3) ?> g
                </td>
                <td style="padding: 12px 16px; text-align: right; font-weight: 600; color: #B45309;">
                  <?= number_format((float)$e['total_fine_gold_weight'], 3) ?> g
                </td>
                <td style="padding: 12px 16px; text-align: right; color: #4B5563;">
                  ₹<?= number_format((float)$e['gold_rate_24k']) ?>/g
                </td>
                <td style="padding: 12px 16px; text-align: right;">
                  <div style="font-weight: 800; color: #0F766E; font-size: 14px;">
                    <?= formatFullINR($e['final_exchange_value']) ?>
                  </div>
                  <?php if ($st === 'Partially Redeemed'): ?>
                    <div style="font-size: 10.5px; color: #2563EB; font-weight: 600;">
                      Bal: <?= formatFullINR($e['credit_balance']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td style="padding: 12px 16px; text-align: center;">
                  <span class="badge" style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border: 1px solid <?= $badgeBorder ?>; font-weight: 700; font-size: 11px;">
                    <?= htmlspecialchars($st) ?>
                  </span>
                </td>
                <td style="padding: 12px 16px; text-align: center;">
                  <?php if (!empty($e['linked_invoice_no'])): ?>
                    <a href="view-invoice.php?invoice_no=<?= urlencode($e['linked_invoice_no']) ?>" style="font-weight: 700; color: #2563EB; font-size: 12px; display: inline-flex; align-items: center; gap: 3px;">
                      <i data-lucide="file-text" style="width: 12px; height: 12px;"></i>
                      <span><?= htmlspecialchars($e['linked_invoice_no']) ?></span>
                    </a>
                  <?php elseif ($st === 'Approved' && (float)$e['credit_balance'] > 0): ?>
                    <span style="font-size: 11px; color: #059669; font-weight: 700; background: #ECFDF5; padding: 2px 8px; border-radius: 9999px;">
                      Ready to Redeem
                    </span>
                  <?php else: ?>
                    <span style="color: #9CA3AF; font-size: 11.5px;">—</span>
                  <?php endif; ?>
                </td>
                <td style="padding: 12px 16px; text-align: right;">
                  <div style="display: flex; gap: 6px; justify-content: flex-end;">
                    <a href="old_gold_exchange_view.php?id=<?= $e['id'] ?>" class="btn btn-ghost btn-sm" style="padding: 5px 8px;" title="View Details & Receipt">
                      <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                    </a>
                    <a href="old_gold_exchange_view.php?id=<?= $e['id'] ?>&print=1" class="btn btn-ghost btn-sm" style="padding: 5px 8px;" title="Print Voucher Receipt">
                      <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                    </a>
                    <?php if ($st === 'Approved' && (float)$e['credit_balance'] > 0): ?>
                      <a href="pos.php?exchange_id=<?= $e['id'] ?>" class="btn btn-sm btn-primary" style="padding: 4px 10px; font-size: 11.5px; background: #059669; border-color: #059669;" title="Apply to POS Bill">
                        <i data-lucide="shopping-cart" style="width: 12px; height: 12px;"></i>
                        <span>Bill</span>
                      </a>
                    <?php elseif ($st === 'Pending Approval' && $isManagerOrAdmin): ?>
                      <button type="button" onclick="quickApprove(<?= $e['id'] ?>)" class="btn btn-sm btn-primary" style="padding: 4px 8px; font-size: 11px; background: #D97706; border-color: #D97706;">
                        Approve
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
async function quickApprove(exchangeId) {
  if (!confirm('Are you sure you want to approve this Old Gold Exchange valuation? This will enable credit redemption at the POS counter.')) {
    return;
  }
  try {
    const res = await fetch('api/old_gold_exchange.php?action=approve', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ exchange_id: exchangeId })
    });
    const data = await res.json();
    if (data.success) {
      alert('Exchange approved successfully!');
      location.reload();
    } else {
      alert(data.error || 'Failed to approve exchange.');
    }
  } catch (err) {
    alert('Network error. Please try again.');
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
