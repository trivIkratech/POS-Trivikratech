# Trivikratech Jewellery POS & Inventory Management System (PHP & MySQL)

A complete, production-ready Jewellery Showroom Point of Sale (POS), Vault Inventory, GST Invoicing, and Customer CRM software built with **PHP 8+** and **MySQL / PDO**.

---

## 🚀 Quick Start / How to Run

### Option 1: PHP Built-in Server (Zero Configuration Instant Test)
You can run this system immediately without installing any external server software:
```bash
php -S localhost:8000
```
Open your browser and navigate to:
👉 **[http://localhost:8000](http://localhost:8000)**

---

### Option 2: Using XAMPP / WAMP / MAMP / LAMP
1. Copy this project folder into your web root directory:
   - **XAMPP**: `htdocs/trivikratech-pos/`
   - **WAMP**: `www/trivikratech-pos/`
   - **MAMP**: `htdocs/trivikratech-pos/`
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and import [`database.sql`](file:///Users/ajinkya/Desktop/POS%20BY%20Ajinkya/database.sql).
3. Open `http://localhost/trivikratech-pos/` in your browser.

---

## 🔑 Demo Login Credentials

| Role | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- |
| **👑 Super Admin** | `admin@jeweller.com` | `admin` | Full Access: POS, Inventory Vaults, Rates, Settings, GST, Payroll |
| **👤 Employee** | `employee@jeweller.com` | `employee` | Sales Counter, POS Billing, Invoices, Customer Lookup |

*(There are also 1-Click test login buttons on the login screen for instant access!)*

---

## 📁 Project Structure

```
├── database.sql               # Complete MySQL database schema & seed data
├── config/
│   ├── db.php                 # PDO database connection
│   ├── auth.php               # Session auth & role permission guards
│   └── helpers.php            # INR currency (₹), GST tax, flash messages
├── includes/
│   ├── header.php             # HTML head, typography, Chart.js & Lucide CDN
│   ├── sidebar.php            # Dynamic role-based responsive navigation
│   ├── topbar.php             # Branch selector, live rate ticker, role switch
│   └── footer.php             # Layout footer & toast notification engine
├── assets/
│   ├── css/style.css          # Design system matching luxury emerald & gold UI
│   └── js/
│       ├── app.js             # Global utilities & toast engine
│       └── pos.js             # Real-time POS billing cart, calculations & print
├── api/
│   ├── pos.php                # Complete checkout & inventory decrement API
│   ├── products.php           # CRUD product inventory
│   ├── customers.php          # Quick customer create & lookup
│   ├── orders.php             # Process returns & refund tracking
│   ├── expenses.php           # Add & delete operating expenses
│   ├── staff.php              # Staff & role assignments
│   └── settings.php           # Live bullion rates & store metadata
├── onboarding/
│   ├── business.php           # Step 1: Business profile
│   ├── subscription.php       # Step 2: Choose plan
│   ├── organization.php       # Step 3: Showroom branches
│   ├── admin.php              # Step 4: Admin account
│   └── welcome.php            # Step 5: Setup complete
├── index.php                  # Luxury landing page with live demo links
├── login.php                  # Authentication screen with 1-click test buttons
├── signup.php                 # Store owner registration
├── verify.php                 # OTP verification
├── forgot-password.php        # Password reset
├── logout.php                 # Session destroyer
├── dashboard.php              # KPI overview, sales trend & metal share charts
├── pos.php                    # Real-time POS Billing counter terminal
├── inventory-gold.php         # 24K, 22K & 18K Hallmarked gold stock
├── inventory-silver.php       # 925 Sterling Silver inventory
├── inventory-diamond.php      # Certified VVS/VS Solitaire & diamond vault
├── stock.php                  # Stock overview & low stock reorder alerts
├── categories.php             # HSN tax code masters & making defaults
├── transfers.php              # Inter-branch stock transfers
├── invoices.php               # GST Tax Invoices list
├── view-invoice.php           # Full printable GST Tax Invoice (A4 & Thermal)
├── orders.php                 # Sales orders ledger & return processor
├── returns.php                # Returns & refunds log
├── customers.php              # Customer directory & CRM
├── loyalty.php                # Royal Loyalty Club tiers & perks
├── purchase-history.php       # Customer purchase history ledger
├── purchase-orders.php        # Supplier purchase orders & bullion intake
├── suppliers.php              # Bullion merchants directory
├── gst.php                    # GSTR-1 & GSTR-3B tax calculations
├── expenses.php               # Showroom expenses & payroll tracker
├── analytics.php              # Revenue trend & gross margin analytics
├── product-reports.php        # Top selling jewellery rankings
├── customer-reports.php       # High Net Worth VIP patrons report
└── settings.php               # Live gold/silver rates & showroom profile
```
