<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trivikratech Jewellery POS & ERP - Luxury Retail Software</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .hero-section {
      background: linear-gradient(135deg, #042F2E 0%, #0F766E 50%, #115E59 100%);
      color: #fff;
      padding: 80px 24px 100px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 6px 16px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      color: #FDE68A;
      margin-bottom: 24px;
    }
    .feature-card {
      background: #FFFFFF;
      border: 1px solid var(--color-border);
      border-radius: 20px;
      padding: 32px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
      transition: all 0.25s ease;
    }
    .feature-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(0,0,0,0.1);
      border-color: var(--color-primary-light);
    }
    .pricing-card {
      background: #FFFFFF;
      border: 1px solid var(--color-border);
      border-radius: 24px;
      padding: 36px;
      display: flex;
      flex-direction: column;
      position: relative;
    }
    .pricing-card.featured {
      border: 2px solid var(--color-primary);
      box-shadow: 0 20px 40px rgba(15,118,110,0.15);
      transform: scale(1.03);
    }
  </style>
</head>
<body>

<!-- Navigation -->
<nav style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--color-border); position: sticky; top: 0; z-index: 100; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between;">
  <div style="display: flex; align-items: center; gap: 12px;">
    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #0F766E, #14B8A6); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 20px;">
      T
    </div>
    <div>
      <div style="font-weight: 800; font-size: 17px; color: var(--color-primary-dark); line-height: 1.1;">
        Trivikratech POS
      </div>
      <div style="font-size: 11px; color: #6B7280; font-weight: 600;">Jewellery ERP & Billing</div>
    </div>
  </div>

  <div style="display: flex; align-items: center; gap: 28px; font-weight: 600; font-size: 14px;">
    <a href="#features" style="color: var(--color-text-2);">Features</a>
    <a href="#pricing" style="color: var(--color-text-2);">Pricing</a>
    <a href="#testimonials" style="color: var(--color-text-2);">Testimonials</a>
  </div>

  <div style="display: flex; align-items: center; gap: 12px;">
    <?php if (isAuthenticated()): ?>
      <a href="dashboard.php" class="btn btn-primary" style="border-radius: 10px; padding: 9px 20px;">
        <i data-lucide="layout-dashboard" style="width: 16px; height: 16px;"></i>
        <span>Go to Dashboard</span>
      </a>
    <?php else: ?>
      <a href="login.php" class="btn btn-ghost" style="border-radius: 10px; padding: 8px 18px;">Sign In</a>
      <a href="login.php" class="btn btn-primary" style="border-radius: 10px; padding: 9px 20px; background: #D97706;">
        <i data-lucide="play" style="width: 16px; height: 16px;"></i>
        <span>Live POS Demo</span>
      </a>
    <?php endif; ?>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
  <div style="max-width: 900px; margin: 0 auto;">
    <div class="hero-badge">
      <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i>
      <span>Next-Gen Jewellery ERP & Point of Sale</span>
    </div>

    <h1 style="font-size: 48px; font-weight: 900; line-height: 1.15; letter-spacing: -0.02em; margin-bottom: 20px;">
      Precision Jewellery POS & Inventory Built for Indian Jewellers
    </h1>

    <p style="font-size: 18px; color: rgba(255, 255, 255, 0.85); line-height: 1.6; max-width: 720px; margin: 0 auto 36px;">
      Automate gold rates, gross vs net weights, making charges, hallmark barcodes, GST 3% tax invoices, and customer loyalty in one lightning-fast platform.
    </p>

    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
      <a href="login.php" class="btn" style="background: #F59E0B; color: #fff; font-weight: 800; font-size: 16px; padding: 14px 32px; border-radius: 12px; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4);">
        <i data-lucide="zap" style="width: 20px; height: 20px;"></i>
        <span>Launch Live Demo</span>
      </a>
      <a href="signup.php" class="btn" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; font-weight: 700; font-size: 16px; padding: 14px 28px; border-radius: 12px;">
        <span>Start Free Trial</span>
      </a>
    </div>

    <!-- Stats Ticker -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 60px; background: rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; border: 1px solid rgba(255,255,255,0.15);">
      <div>
        <div style="font-size: 28px; font-weight: 900; color: #FDE68A;">₹50Cr+</div>
        <div style="font-size: 12px; color: rgba(255,255,255,0.7); font-weight: 600;">Monthly Billing Processed</div>
      </div>
      <div>
        <div style="font-size: 28px; font-weight: 900; color: #FDE68A;">99.98%</div>
        <div style="font-size: 12px; color: rgba(255,255,255,0.7); font-weight: 600;">Inventory Accuracy</div>
      </div>
      <div>
        <div style="font-size: 28px; font-weight: 900; color: #FDE68A;">2,400+</div>
        <div style="font-size: 12px; color: rgba(255,255,255,0.7); font-weight: 600;">Active Retail Counters</div>
      </div>
      <div>
        <div style="font-size: 28px; font-weight: 900; color: #FDE68A;">100%</div>
        <div style="font-size: 12px; color: rgba(255,255,255,0.7); font-weight: 600;">GST & Hallmarking Compliant</div>
      </div>
    </div>
  </div>
</section>

<!-- Features Grid -->
<section id="features" style="max-width: 1200px; margin: 80px auto; padding: 0 24px;">
  <div style="text-align: center; margin-bottom: 50px;">
    <h2 style="font-size: 32px; font-weight: 900; color: #111827; margin-bottom: 12px;">Engineered for Jewellery Showrooms</h2>
    <p style="font-size: 16px; color: #6B7280; max-width: 600px; margin: 0 auto;">Everything you need to run gold, silver, diamond, and platinum counters with maximum precision.</p>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <div class="feature-card">
      <div style="width: 50px; height: 50px; border-radius: 14px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
        <i data-lucide="calculator" style="width: 26px; height: 26px;"></i>
      </div>
      <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 10px;">Live Metal Price Calculation</h3>
      <p style="font-size: 14px; color: #6B7280; line-height: 1.6;">Auto-calculates item pricing based on dynamic daily rates for 24K, 22K, 18K Gold, 925 Silver, and Diamond carats.</p>
    </div>

    <div class="feature-card">
      <div style="width: 50px; height: 50px; border-radius: 14px; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
        <i data-lucide="scale" style="width: 26px; height: 26px;"></i>
      </div>
      <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 10px;">Gross, Stone & Net Weight</h3>
      <p style="font-size: 14px; color: #6B7280; line-height: 1.6;">Precise weight accounting down to 3 decimal places with stone deduction, wastage %, and making charge rules.</p>
    </div>

    <div class="feature-card">
      <div style="width: 50px; height: 50px; border-radius: 14px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
        <i data-lucide="printer" style="width: 26px; height: 26px;"></i>
      </div>
      <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 10px;">Instant GST Tax Invoicing & Thermal Print</h3>
      <p style="font-size: 14px; color: #6B7280; line-height: 1.6;">Generate standard 3% GST jewellery tax invoices, A4/A5 receipts, and 80mm thermal counter slips with QR codes.</p>
    </div>

    <div class="feature-card">
      <div style="width: 50px; height: 50px; border-radius: 14px; background: #FAF5FF; color: #7E22CE; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
        <i data-lucide="award" style="width: 26px; height: 26px;"></i>
      </div>
      <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 10px;">Customer CRM & Tier Loyalty</h3>
      <p style="font-size: 14px; color: #6B7280; line-height: 1.6;">Bronze, Silver, Gold, Platinum, Diamond VIP tiers with birthday/anniversary reminders and points redemption.</p>
    </div>

    <div class="feature-card">
      <div style="width: 50px; height: 50px; border-radius: 14px; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
        <i data-lucide="building" style="width: 26px; height: 26px;"></i>
      </div>
      <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 10px;">Multi-Branch Stock Transfers</h3>
      <p style="font-size: 14px; color: #6B7280; line-height: 1.6;">Track inventory movements across showrooms, manage centralized stock audits, and transfer approval workflows.</p>
    </div>

    <div class="feature-card">
      <div style="width: 50px; height: 50px; border-radius: 14px; background: #F0FDFA; color: #0F766E; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
        <i data-lucide="shield-check" style="width: 26px; height: 26px;"></i>
      </div>
      <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 10px;">Role-Based Staff Access</h3>
      <p style="font-size: 14px; color: #6B7280; line-height: 1.6;">Granular security for Super Admin, Store Managers, Sales Executives, and Repairing Assistants.</p>
    </div>
  </div>
</section>

<!-- Pricing Section -->
<section id="pricing" style="background: #F3F4F6; padding: 80px 24px;">
  <div style="max-width: 1100px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 50px;">
      <h2 style="font-size: 32px; font-weight: 900; color: #111827; margin-bottom: 12px;">Simple, Transparent Pricing</h2>
      <p style="font-size: 16px; color: #6B7280;">Choose the plan tailored for your jewellery enterprise</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; align-items: center;">
      <div class="pricing-card">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px;">Starter Plan</h3>
        <p style="font-size: 13px; color: #6B7280; margin-bottom: 20px;">For boutique jewellery shops</p>
        <div style="font-size: 32px; font-weight: 900; color: #111827; margin-bottom: 20px;">₹1,499<span style="font-size: 14px; color: #6B7280; font-weight: 500;">/month</span></div>
        <ul style="list-style: none; font-size: 13.5px; color: #374151; display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
          <li>✓ 1 Store Location</li>
          <li>✓ Up to 1,000 Inventory Items</li>
          <li>✓ POS Billing & Tax Invoices</li>
          <li>✓ Standard Email Support</li>
        </ul>
        <a href="signup.php" class="btn btn-secondary" style="border-radius: 10px;">Choose Starter</a>
      </div>

      <div class="pricing-card featured">
        <div style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: #0F766E; color: #fff; padding: 4px 14px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase;">
          Most Popular
        </div>
        <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 8px; color: var(--color-primary);">Professional Plan</h3>
        <p style="font-size: 13px; color: #6B7280; margin-bottom: 20px;">For expanding multi-counter showrooms</p>
        <div style="font-size: 36px; font-weight: 900; color: #111827; margin-bottom: 20px;">₹2,999<span style="font-size: 14px; color: #6B7280; font-weight: 500;">/month</span></div>
        <ul style="list-style: none; font-size: 13.5px; color: #374151; display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
          <li>✓ 3 Store Branches</li>
          <li>✓ Unlimited Inventory & Barcoding</li>
          <li>✓ Loyalty Program & CRM</li>
          <li>✓ Multi-Staff Roles & Permissions</li>
          <li>✓ Dedicated Phone & WhatsApp Support</li>
        </ul>
        <a href="signup.php" class="btn btn-primary" style="border-radius: 10px; padding: 12px;">Start 14-Day Free Trial</a>
      </div>

      <div class="pricing-card">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px;">Enterprise Plan</h3>
        <p style="font-size: 13px; color: #6B7280; margin-bottom: 20px;">For large jewellery retail chains</p>
        <div style="font-size: 32px; font-weight: 900; color: #111827; margin-bottom: 20px;">₹5,999<span style="font-size: 14px; color: #6B7280; font-weight: 500;">/month</span></div>
        <ul style="list-style: none; font-size: 13.5px; color: #374151; display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
          <li>✓ Unlimited Branches</li>
          <li>✓ Advanced Stock Transfers & Audits</li>
          <li>✓ Custom API & Accounting Sync</li>
          <li>✓ 24/7 Dedicated Account Manager</li>
        </ul>
        <a href="signup.php" class="btn btn-secondary" style="border-radius: 10px;">Contact Sales</a>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer style="background: #111827; color: #9CA3AF; padding: 50px 40px 30px;">
  <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #374151; padding-bottom: 30px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="width: 36px; height: 36px; background: #0F766E; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800;">T</div>
      <span style="color: #fff; font-weight: 800; font-size: 16px;">Trivikratech Jewellers POS</span>
    </div>
    <div style="font-size: 13px;">
      &copy; <?= date('Y') ?> Trivikratech Solutions Pvt. Ltd. All rights reserved.
    </div>
  </div>
  <div style="text-align: center; font-size: 12px; color: #6B7280;">
    Built with PHP & MySQL · Modern Responsive Architecture
  </div>
</footer>

<script>lucide.createIcons();</script>
</body>
</html>
