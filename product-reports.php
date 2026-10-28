<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Product Performance Reports - Trivikratech POS";
$db = getDBConnection();

$topProducts = [
    ['rank' => 1, 'name' => 'Solitaire Diamond Ring', 'sku' => 'GR-002', 'units' => 48, 'revenue' => 8880000, 'growth' => '+22.4%'],
    ['rank' => 2, 'name' => 'Antique Gold Necklace', 'sku' => 'GN-001', 'units' => 65, 'revenue' => 7280000, 'growth' => '+18.2%'],
    ['rank' => 3, 'name' => 'Gold Mangalsutra', 'sku' => 'GP-006', 'units' => 82, 'revenue' => 6109000, 'growth' => '+14.8%'],
    ['rank' => 4, 'name' => 'Polki Necklace Set', 'sku' => 'GN-010', 'units' => 18, 'revenue' => 5580000, 'growth' => '+8.5%'],
    ['rank' => 5, 'name' => 'Diamond Hoop Earrings', 'sku' => 'DE-012', 'units' => 24, 'revenue' => 5400000, 'growth' => '+28.1%'],
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Top Selling Jewellery & Fast Moving SKUs</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Product sales volume ranking, turnover velocity & revenue yield</p>
  </div>
</div>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Rank</th>
        <th>Product Name</th>
        <th>SKU Code</th>
        <th>Units Sold</th>
        <th>Total Revenue Generated</th>
        <th>YoY Growth</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($topProducts as $tp): ?>
        <tr>
          <td><span class="badge badge-gold" style="font-size: 12px; font-weight: 800;">#<?= $tp['rank'] ?></span></td>
          <td style="font-weight: 700; color: #111827;"><?= htmlspecialchars($tp['name']) ?></td>
          <td style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($tp['sku']) ?></td>
          <td style="font-weight: 700;"><?= $tp['units'] ?> pcs</td>
          <td style="font-weight: 900; color: var(--color-primary);"><?= formatFullINR($tp['revenue']) ?></td>
          <td><span class="badge badge-success"><?= $tp['growth'] ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
