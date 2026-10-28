<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "Dashboard - Trivikratech Jewellery POS";
$db = getDBConnection();

// Fetch pure database metrics
$totalRevenue = 0;
$todaySales = 0;
$monthlySales = 0;
$activeCustomers = 0;
$pendingOrders = 0;
$goldStockValue = 0;
$silverStockValue = 0;
$diamondStockValue = 0;
$platinumStockValue = 0;
$totalInventoryValue = 0;

$monthlyGoldSales = array_fill(1, 12, 0);
$monthlyDiamondSales = array_fill(1, 12, 0);
$metalDistribution = ['Gold' => 0, 'Diamond' => 0, 'Silver' => 0, 'Platinum' => 0];

try {
    // Total Revenue from completed orders
    $revStmt = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'Completed'");
    $totalRevenue = (float)$revStmt->fetchColumn();

    // Today's Sales
    $todayStmt = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'Completed' AND DATE(order_date) = CURRENT_DATE");
    $todaySales = (float)$todayStmt->fetchColumn();

    // Monthly Sales
    $monthStmt = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'Completed' AND MONTH(order_date) = MONTH(CURRENT_DATE) AND YEAR(order_date) = YEAR(CURRENT_DATE)");
    $monthlySales = (float)$monthStmt->fetchColumn();

    // Active Customers
    $custStmt = $db->query("SELECT COUNT(*) FROM customers");
    $activeCustomers = (int)$custStmt->fetchColumn();

    // Pending Orders
    $pendStmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
    $pendingOrders = (int)$pendStmt->fetchColumn();

    // Inventory Values per metal
    $invStmt = $db->query("
        SELECT metal_type, COALESCE(SUM(selling_price * stock), 0) as total_val 
        FROM products 
        GROUP BY metal_type
    ");
    while ($row = $invStmt->fetch()) {
        $m = $row['metal_type'];
        $val = (float)$row['total_val'];
        if ($m === 'Gold') $goldStockValue = $val;
        elseif ($m === 'Silver') $silverStockValue = $val;
        elseif ($m === 'Diamond') $diamondStockValue = $val;
        elseif ($m === 'Platinum') $platinumStockValue = $val;
        $totalInventoryValue += $val;
        if (isset($metalDistribution[$m])) {
            $metalDistribution[$m] = $val;
        }
    }

    // Monthly sales breakdown for chart (current year)
    $currYear = date('Y');
    $chartStmt = $db->prepare("
        SELECT 
            MONTH(o.order_date) as ord_month,
            oi.purity,
            SUM(oi.line_total) as month_total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'Completed' AND YEAR(o.order_date) = ?
        GROUP BY MONTH(o.order_date), oi.purity
    ");
    $chartStmt->execute([$currYear]);
    while ($r = $chartStmt->fetch()) {
        $m = (int)$r['ord_month'];
        $pur = $r['purity'];
        $tot = (float)$r['month_total'] / 100000; // in Lakhs
        if (stripos($pur, 'Diamond') !== false) {
            $monthlyDiamondSales[$m] += $tot;
        } else {
            $monthlyGoldSales[$m] += $tot;
        }
    }

    // Recent 6 Orders
    $recentStmt = $db->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT 6");
    $recentOrders = $recentStmt->fetchAll();

    // Low stock items
    $lowStockStmt = $db->query("SELECT * FROM products WHERE stock <= min_stock LIMIT 4");
    $lowStockItems = $lowStockStmt->fetchAll();
} catch (Exception $e) {
    $recentOrders = [];
    $lowStockItems = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #111827; margin: 0 0 4px;">
      Showroom Dashboard
    </h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 0;">
      Real-time business performance & counter overview for <strong><?= htmlspecialchars($_SESSION['active_branch'] ?? 'Bandra West') ?></strong>
    </p>
  </div>

  <div style="display: flex; gap: 10px;">
    <a href="pos.php" class="btn btn-primary" style="box-shadow: 0 4px 14px rgba(15,118,110,0.25);">
      <i data-lucide="shopping-cart" style="width: 16px; height: 16px;"></i>
      <span>New POS Sale</span>
    </a>
    <a href="customers.php" class="btn btn-secondary">
      <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
      <span>Add Customer</span>
    </a>
  </div>
</div>

<!-- KPI Cards Grid -->
<div class="kpi-grid">
  <div class="kpi-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
      <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(15,118,110,0.1); color: var(--color-primary); display: flex; align-items: center; justify-content: center;">
        <i data-lucide="indian-rupee" style="width: 20px; height: 20px;"></i>
      </div>
      <span class="badge badge-success" style="font-size: 11px;">Live Sales</span>
    </div>
    <div class="kpi-value"><?= formatFullINR($totalRevenue) ?></div>
    <div class="kpi-label">Total Revenue</div>
  </div>

  <div class="kpi-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
      <div style="width: 40px; height: 40px; border-radius: 10px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center;">
        <i data-lucide="zap" style="width: 20px; height: 20px;"></i>
      </div>
      <span class="badge badge-warning" style="font-size: 11px;">Today</span>
    </div>
    <div class="kpi-value" style="color: #D97706;"><?= formatFullINR($todaySales) ?></div>
    <div class="kpi-label">Today's Counter Sales</div>
  </div>

  <div class="kpi-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
      <div style="width: 40px; height: 40px; border-radius: 10px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center;">
        <i data-lucide="users" style="width: 20px; height: 20px;"></i>
      </div>
      <span class="badge badge-info" style="font-size: 11px;">Customers</span>
    </div>
    <div class="kpi-value"><?= number_format($activeCustomers) ?></div>
    <div class="kpi-label">Registered Customers</div>
  </div>

  <?php if ($currentUser['role'] === 'Super Admin' || $currentUser['role'] === 'Store Manager'): ?>
  <div class="kpi-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
      <div style="width: 40px; height: 40px; border-radius: 10px; background: #FAF5FF; color: #7E22CE; display: flex; align-items: center; justify-content: center;">
        <i data-lucide="gem" style="width: 20px; height: 20px;"></i>
      </div>
      <span class="badge badge-gold" style="font-size: 11px;">Vault Stock</span>
    </div>
    <div class="kpi-value"><?= formatFullINR($totalInventoryValue) ?></div>
    <div class="kpi-label">Total Vault Inventory</div>
  </div>
  <?php endif; ?>
</div>

<!-- Charts Row -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
  <div class="card" style="margin-bottom: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <div>
        <h3 style="font-size: 16px; font-weight: 800; color: #111827; margin: 0 0 2px;">Monthly Sales Revenue Trend</h3>
        <p style="font-size: 12px; color: #6B7280; margin: 0;">Comparison across Gold, Diamond, and Silver jewellery</p>
      </div>
      <span class="badge badge-success">FY <?= date('Y') ?></span>
    </div>
    <div style="height: 280px; position: relative;">
      <canvas id="salesTrendChart"></canvas>
    </div>
  </div>

  <div class="card" style="margin-bottom: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <div>
        <h3 style="font-size: 16px; font-weight: 800; color: #111827; margin: 0 0 2px;">Stock Valuation by Metal</h3>
        <p style="font-size: 12px; color: #6B7280; margin: 0;">Vault inventory value breakdown</p>
      </div>
    </div>
    <div style="height: 220px; position: relative; display: flex; align-items: center; justify-content: center;">
      <canvas id="metalCategoryChart"></canvas>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 14px; font-size: 12px;">
      <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #D4AF37;"></span> Gold: <?= formatINR($goldStockValue) ?></div>
      <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #0F766E;"></span> Diamond: <?= formatINR($diamondStockValue) ?></div>
      <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #9CA3AF;"></span> Silver: <?= formatINR($silverStockValue) ?></div>
      <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #6B7280;"></span> Platinum: <?= formatINR($platinumStockValue) ?></div>
    </div>
  </div>
</div>

<!-- Recent Orders & Alerts Table -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
  <!-- Recent Sales Orders -->
  <div class="card" style="margin-bottom: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <div>
        <h3 style="font-size: 16px; font-weight: 800; color: #111827; margin: 0 0 2px;">Recent Counter Transactions</h3>
        <p style="font-size: 12px; color: #6B7280; margin: 0;">Latest sales orders and invoices</p>
      </div>
      <a href="orders.php" class="btn btn-ghost btn-sm">View All</a>
    </div>

    <div class="table-container" style="border: none; box-shadow: none;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order No</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total Amount</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="7" style="text-align: center; color: #9CA3AF; padding: 28px;">No sales orders recorded yet. Create a bill via POS to see transactions.</td></tr>
          <?php else: ?>
            <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td style="font-weight: 700; color: var(--color-primary);"><?= htmlspecialchars($o['order_no']) ?></td>
                <td>
                  <div style="font-weight: 600;"><?= htmlspecialchars($o['customer_name']) ?></div>
                  <div style="font-size: 11px; color: #6B7280;"><?= date('d M, h:i A', strtotime($o['order_date'])) ?></div>
                </td>
                <td><?= (int)$o['total_items'] ?> pcs</td>
                <td style="font-weight: 700;"><?= formatFullINR($o['total']) ?></td>
                <td><span class="badge badge-info"><?= htmlspecialchars($o['payment_method']) ?></span></td>
                <td>
                  <span class="badge <?= $o['status'] === 'Completed' ? 'badge-success' : ($o['status'] === 'Pending' ? 'badge-warning' : 'badge-danger') ?>">
                    <?= htmlspecialchars($o['status']) ?>
                  </span>
                </td>
                <td>
                  <a href="view-invoice.php?order_no=<?= urlencode($o['order_no']) ?>" class="btn btn-ghost btn-sm" style="padding: 4px 8px;" title="Print Tax Invoice">
                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Low Stock Alerts -->
  <div class="card" style="margin-bottom: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #111827; margin: 0;">Stock Watchlist</h3>
      <a href="stock.php" class="btn btn-ghost btn-sm">Stock Hub</a>
    </div>

    <div style="display: flex; flex-direction: column; gap: 12px;">
      <?php if (empty($lowStockItems)): ?>
        <div style="padding: 28px 16px; text-align: center; color: #6B7280; font-size: 13px;">
          <div style="font-size: 28px; margin-bottom: 6px;">📦</div>
          No low stock items. All inventory levels are healthy.
        </div>
      <?php else: ?>
        <?php foreach ($lowStockItems as $item): ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: var(--color-surface-2); border-radius: 12px; border: 1px solid var(--color-border);">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="font-size: 24px;"><?= $item['image_emoji'] ?? '💎' ?></div>
              <div>
                <div style="font-weight: 700; font-size: 13px;"><?= htmlspecialchars($item['name']) ?></div>
                <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($item['sku']) ?> · <?= htmlspecialchars($item['purity']) ?></div>
              </div>
            </div>
            <div style="text-align: right;">
              <span class="badge badge-danger"><?= (int)$item['stock'] ?> left</span>
              <div style="font-size: 10px; color: #6B7280; margin-top: 2px;">Min: <?= (int)$item['min_stock'] ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const goldMonthly = <?= json_encode(array_values($monthlyGoldSales)) ?>;
  const diamondMonthly = <?= json_encode(array_values($monthlyDiamondSales)) ?>;
  
  const distGold = <?= (float)$goldStockValue ?>;
  const distDiamond = <?= (float)$diamondStockValue ?>;
  const distSilver = <?= (float)$silverStockValue ?>;
  const distPlat = <?= (float)$platinumStockValue ?>;

  const totalDist = distGold + distDiamond + distSilver + distPlat;
  const chartDistData = totalDist > 0 ? [distGold, distDiamond, distSilver, distPlat] : [0, 0, 0, 0];

  // Sales Trend Chart
  const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
  new Chart(trendCtx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [
        {
          label: 'Gold Sales (₹ Lakhs)',
          data: goldMonthly,
          borderColor: '#D4AF37',
          backgroundColor: 'rgba(212, 175, 55, 0.1)',
          fill: true,
          tension: 0.35
        },
        {
          label: 'Diamond Sales (₹ Lakhs)',
          data: diamondMonthly,
          borderColor: '#0F766E',
          backgroundColor: 'rgba(15, 118, 110, 0.08)',
          fill: true,
          tension: 0.35
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: {
        y: { beginAtZero: true, grid: { color: '#F3F4F6' } },
        x: { grid: { display: false } }
      }
    }
  });

  // Metal Category Chart
  const pieCtx = document.getElementById('metalCategoryChart').getContext('2d');
  new Chart(pieCtx, {
    type: 'doughnut',
    data: {
      labels: ['Gold', 'Diamond', 'Silver', 'Platinum'],
      datasets: [{
        data: chartDistData,
        backgroundColor: ['#D4AF37', '#0F766E', '#9CA3AF', '#6B7280'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      cutout: '68%'
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
