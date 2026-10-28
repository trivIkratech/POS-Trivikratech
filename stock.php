<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Stock Hub & Vault Overview - Trivikratech POS";
$db = getDBConnection();

$stockSummary = [];
try {
    $stmt = $db->query("
        SELECT 
            metal_type,
            COUNT(*) as total_skus,
            SUM(stock) as total_units,
            SUM(net_weight * stock) as total_weight,
            SUM(selling_price * stock) as total_value
        FROM products 
        GROUP BY metal_type
    ");
    $stockSummary = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch low stock items
$lowStock = [];
try {
    $stmt = $db->query("SELECT * FROM products WHERE stock <= min_stock ORDER BY stock ASC");
    $lowStock = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Stock Hub & Vault Audit</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Unified overview of showroom stock valuation across all metals</p>
  </div>

  <div style="display: flex; gap: 10px;">
    <a href="transfers.php" class="btn btn-secondary">
      <i data-lucide="arrow-right-left" style="width: 16px; height: 16px;"></i>
      <span>Stock Transfers</span>
    </a>
    <a href="purchase-orders.php" class="btn btn-primary">
      <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
      <span>New Purchase Order</span>
    </a>
  </div>
</div>

<!-- Stock Metal Group Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 24px;">
  <?php foreach ($stockSummary as $s): ?>
    <div class="card" style="margin-bottom: 0; border-top: 4px solid <?= $s['metal_type'] === 'Gold' ? '#D97706' : ($s['metal_type'] === 'Diamond' ? '#0284C7' : ($s['metal_type'] === 'Silver' ? '#4B5563' : '#7E22CE')) ?>;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="font-size: 17px; font-weight: 800; margin: 0;"><?= htmlspecialchars($s['metal_type']) ?> Jewellery</h3>
        <span class="badge badge-info"><?= $s['total_skus'] ?> SKUs</span>
      </div>
      <div style="font-size: 24px; font-weight: 900; color: var(--color-primary); margin-bottom: 8px;">
        <?= formatINR($s['total_value']) ?>
      </div>
      <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: #6B7280; border-top: 1px solid var(--color-border); padding-top: 8px;">
        <span>Total Units: <strong><?= number_format($s['total_units']) ?> pcs</strong></span>
        <span>Vault Wt: <strong><?= number_format((float)$s['total_weight'], 1) ?> g</strong></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Low Stock Warning & Reorder Panel -->
<div class="card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <div style="display: flex; align-items: center; gap: 8px;">
      <i data-lucide="alert-triangle" style="width: 20px; height: 20px; color: #DC2626;"></i>
      <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Low Stock & Reorder Alert List</h3>
    </div>
  </div>

  <div class="table-container" style="border: none; box-shadow: none;">
    <table class="data-table">
      <thead>
        <tr>
          <th>Item / SKU</th>
          <th>Metal Type</th>
          <th>Purity</th>
          <th>Available Stock</th>
          <th>Min Threshold</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($lowStock)): ?>
          <tr><td colspan="7" style="text-align: center; color: #059669; padding: 20px;">✓ All products have adequate stock!</td></tr>
        <?php else: ?>
          <?php foreach ($lowStock as $item): ?>
            <tr>
              <td>
                <div style="font-weight: 700;"><?= htmlspecialchars($item['name']) ?></div>
                <div style="font-size: 11px; color: #6B7280;"><?= htmlspecialchars($item['sku']) ?></div>
              </td>
              <td><?= htmlspecialchars($item['metal_type']) ?></td>
              <td><?= htmlspecialchars($item['purity']) ?></td>
              <td style="font-weight: 800; color: #DC2626;"><?= (int)$item['stock'] ?> pcs</td>
              <td><?= (int)$item['min_stock'] ?> pcs</td>
              <td><span class="badge badge-danger">Needs Reorder</span></td>
              <td>
                <a href="purchase-orders.php" class="btn btn-primary btn-sm" style="padding: 4px 10px; font-size: 11.5px;">
                  + PO
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
