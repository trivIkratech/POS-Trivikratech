<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireRole(['Super Admin', 'Store Manager']);

$pageTitle = "Showroom Expenses - Trivikratech POS";
$db = getDBConnection();

$expenses = [];
try {
    $stmt = $db->query("SELECT * FROM expenses ORDER BY expense_date DESC");
    $expenses = $stmt->fetchAll();
} catch (Exception $e) {}

$totalExpenses = array_reduce($expenses, fn($s, $e) => $s + (float)$e['amount'], 0);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Showroom Operating Expenses</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Track showroom rent, staff salaries, electricity & marketing expenses</p>
  </div>

  <button type="button" onclick="openAddExpenseModal()" class="btn btn-primary">
    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
    <span>Record Expense</span>
  </button>
</div>

<!-- Quick Expense Summary -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr);">
  <div class="kpi-card">
    <div class="kpi-value" style="color: #DC2626;"><?= formatFullINR($totalExpenses) ?></div>
    <div class="kpi-label">Total Monthly Expenses</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value">₹2,68,000</div>
    <div class="kpi-label">Staff Payroll</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value">₹85,000</div>
    <div class="kpi-label">Showroom Rent</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-value">₹48,000</div>
    <div class="kpi-label">Marketing & Ads</div>
  </div>
</div>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Category</th>
        <th>Description</th>
        <th>Amount</th>
        <th>Branch</th>
        <th>Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($expenses)): ?>
        <tr><td colspan="6" style="text-align: center; color: #9CA3AF; padding: 24px;">No expenses logged.</td></tr>
      <?php else: ?>
        <?php foreach ($expenses as $e): ?>
          <tr>
            <td><span class="badge badge-info"><?= htmlspecialchars($e['category']) ?></span></td>
            <td style="font-weight: 600;"><?= htmlspecialchars($e['description']) ?></td>
            <td style="font-weight: 800; color: #DC2626;"><?= formatFullINR($e['amount']) ?></td>
            <td><?= htmlspecialchars($e['branch']) ?></td>
            <td style="color: #6B7280; font-size: 12.5px;"><?= htmlspecialchars($e['expense_date']) ?></td>
            <td>
              <button type="button" onclick="deleteExpense(<?= $e['id'] ?>)" class="btn btn-ghost btn-sm" style="color: #DC2626; padding: 4px 8px;">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function openAddExpenseModal() {
  const cat = prompt("Enter Category (Rent / Salaries / Utilities / Marketing / Other):", "Utilities");
  if (!cat) return;
  const desc = prompt("Enter Description:", "Showroom AC electricity bill");
  if (!desc) return;
  const amt = prompt("Enter Amount (₹):", "18000");

  fetch('api/expenses.php?action=create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      category: cat,
      description: desc,
      amount: parseFloat(amt) || 0,
      expense_date: new Date().toISOString().slice(0, 10)
    })
  }).then(() => location.reload());
}

function deleteExpense(id) {
  if (confirm('Delete this expense entry?')) {
    fetch('api/expenses.php?action=delete&id=' + id).then(() => location.reload());
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
