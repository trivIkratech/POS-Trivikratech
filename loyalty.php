<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
requireAuth();

$pageTitle = "Loyalty Program - Trivikratech POS";
$db = getDBConnection();

$tiers = [
    ['name' => 'Bronze',   'min' => '0',       'color' => '#9A3412', 'bg' => '#FEF3E8', 'points' => '1 pt per ₹1,000', 'perk' => 'Standard birthday greetings & updates'],
    ['name' => 'Silver',   'min' => '1,000',   'color' => '#4B5563', 'bg' => '#F3F4F6', 'points' => '1.5 pts per ₹1,000', 'perk' => '5% off on making charges'],
    ['name' => 'Gold',     'min' => '3,000',   'color' => '#B45309', 'bg' => '#FFFBEB', 'points' => '2 pts per ₹1,000', 'perk' => '10% off making charges + free ultrasonic cleaning'],
    ['name' => 'Platinum', 'min' => '6,000',   'color' => '#0F766E', 'bg' => '#F0FDF4', 'points' => '3 pts per ₹1,000', 'perk' => '15% off making charges + VIP lounge access'],
    ['name' => 'Diamond',  'min' => '12,000+', 'color' => '#1D4ED8', 'bg' => '#EFF6FF', 'points' => '5 pts per ₹1,000', 'perk' => '20% off making + personal jewellery concierge & home previews'],
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Royal Jewellery Loyalty Club</h1>
    <p style="font-size: 13.5px; color: #6B7280; margin: 4px 0 0;">Automated customer tier rewards, milestone perks, and points redemptions</p>
  </div>
</div>

<!-- Tier Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <?php foreach ($tiers as $t): ?>
    <div class="card" style="margin-bottom: 0; background: <?= $t['bg'] ?>; border-color: rgba(0,0,0,0.08);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <h3 style="font-size: 17px; font-weight: 800; color: <?= $t['color'] ?>; margin: 0;"><?= $t['name'] ?> Tier</h3>
        <i data-lucide="award" style="width: 20px; height: 20px; color: <?= $t['color'] ?>;"></i>
      </div>
      <div style="font-size: 12.5px; font-weight: 700; color: <?= $t['color'] ?>; margin-bottom: 12px;">Threshold: <?= $t['min'] ?> Points</div>
      <div style="font-size: 12px; color: #374151; line-height: 1.5; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 8px;">
        <strong>Reward:</strong> <?= $t['points'] ?><br>
        <strong>Perk:</strong> <?= $t['perk'] ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 14px;">Loyalty Redemption Policy</h3>
  <p style="font-size: 13.5px; color: #4B5563; line-height: 1.6;">
    1 Loyalty Point = ₹1.00 store credit. Points are earned automatically on completing counter transactions at any showroom branch and can be redeemed directly against future invoices or making charges.
  </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
