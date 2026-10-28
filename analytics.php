<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Sales & Profit Analytics - Trivikratech POS";
$db = getDBConnection();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Sales Performance & Margin Analytics</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Deep revenue trends, metal sales distribution, and profit margins</p>
  </div>
</div>

<!-- Main Trend Chart -->
<div class="card" style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h3 style="font-size: 16px; font-weight: 800; margin: 0 0 2px;">Monthly Revenue vs Cost Comparison (₹ Lakhs)</h3>
      <p style="font-size: 12px; color: #6B7280; margin: 0;">Net Revenue vs Raw Bullion Acquisition Cost</p>
    </div>
  </div>
  <div style="height: 320px;">
    <canvas id="analyticsBarChart"></canvas>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
  <div class="card">
    <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 14px;">Gross Margin by Category</h3>
    <div style="height: 240px;">
      <canvas id="marginRadarChart"></canvas>
    </div>
  </div>

  <div class="card">
    <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 14px;">Payment Method Share</h3>
    <div style="height: 240px;">
      <canvas id="paymentPieChart"></canvas>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Bar chart
  new Chart(document.getElementById('analyticsBarChart'), {
    type: 'bar',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [
        { label: 'Revenue (₹L)', data: [5.2, 4.3, 7.0, 6.1, 8.6, 7.1, 10.0, 9.2, 11.8, 15.1, 13.7, 17.8], backgroundColor: '#0F766E' },
        { label: 'Bullion Cost (₹L)', data: [4.1, 3.4, 5.5, 4.8, 6.7, 5.5, 7.8, 7.2, 9.2, 11.8, 10.7, 13.9], backgroundColor: '#D4AF37' }
      ]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  // Radar chart
  new Chart(document.getElementById('marginRadarChart'), {
    type: 'radar',
    data: {
      labels: ['Necklaces', 'Solitaire Rings', 'Bangles', 'Earrings', 'Pendants', 'Coins'],
      datasets: [{
        label: 'Gross Margin %',
        data: [18, 28, 14, 22, 20, 6],
        backgroundColor: 'rgba(15, 118, 110, 0.2)',
        borderColor: '#0F766E',
        pointBackgroundColor: '#0F766E'
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  // Pie chart
  new Chart(document.getElementById('paymentPieChart'), {
    type: 'pie',
    data: {
      labels: ['UPI / QR', 'Credit/Debit Card', 'Cash', 'Gold Exchange'],
      datasets: [{
        data: [42, 35, 15, 8],
        backgroundColor: ['#059669', '#2563EB', '#D97706', '#7E22CE']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
