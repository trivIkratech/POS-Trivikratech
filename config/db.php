<?php
/**
 * Trivikratech Jewellery POS - Robust Database Connection
 * Supports MySQL (auto-creates db and auto-imports schema with common default passwords)
 * and falls back to SQLite if MySQL service is inactive.
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'trivikratech_pos');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $passwordsToTry = [
        DB_PASS,   // Empty password (default XAMPP/WAMP/Homebrew)
        'root',    // Common MAMP/Docker password
        'password',
        '123456',
        'admin'
    ];
    $passwordsToTry = array_unique($passwordsToTry);

    // Try connecting to MySQL
    foreach ($passwordsToTry as $pass) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, $pass, $options);
            
            // Check if tables exist
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($tableCheck->rowCount() === 0) {
                $sqlPath = __DIR__ . '/../database.sql';
                if (file_exists($sqlPath)) {
                    $sql = file_get_contents($sqlPath);
                    $pdo->exec($sql);
                }
            }

            // Ensure Old Gold Exchange tables exist
            $ogeCheck = $pdo->query("SHOW TABLES LIKE 'old_gold_exchanges'");
            if ($ogeCheck->rowCount() === 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `old_gold_exchanges` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `exchange_no` VARCHAR(50) NOT NULL UNIQUE,
                      `customer_id` INT DEFAULT NULL,
                      `customer_name` VARCHAR(150) NOT NULL,
                      `customer_mobile` VARCHAR(20) DEFAULT NULL,
                      `exchange_date` DATE NOT NULL,
                      `rate_basis` VARCHAR(191) DEFAULT '24K Gold Rate / gram',
                      `gold_rate_24k` DECIMAL(12,2) NOT NULL,
                      `total_gross_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
                      `total_weight_deduction` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
                      `total_net_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
                      `total_fine_gold_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
                      `gross_valuation` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                      `total_monetary_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                      `final_exchange_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                      `credit_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                      `status` ENUM('Draft', 'Pending Approval', 'Approved', 'Partially Redeemed', 'Redeemed', 'Cancelled') NOT NULL DEFAULT 'Approved',
                      `branch` VARCHAR(100) DEFAULT 'Bandra West',
                      `created_by` VARCHAR(150) DEFAULT 'Super Admin',
                      `approved_by` VARCHAR(150) DEFAULT NULL,
                      `approved_at` DATETIME DEFAULT NULL,
                      `cancelled_by` VARCHAR(150) DEFAULT NULL,
                      `cancelled_at` DATETIME DEFAULT NULL,
                      `cancel_reason` TEXT DEFAULT NULL,
                      `redeemed_order_id` INT DEFAULT NULL,
                      `redeemed_invoice_id` INT DEFAULT NULL,
                      `redeemed_at` DATETIME DEFAULT NULL,
                      `redeemed_amount` DECIMAL(12,2) DEFAULT 0.00,
                      `remarks` TEXT DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      INDEX idx_exchange_cust (`customer_id`),
                      INDEX idx_exchange_status (`status`),
                      INDEX idx_exchange_date (`exchange_date`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                    CREATE TABLE IF NOT EXISTS `old_gold_exchange_items` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `exchange_id` INT NOT NULL,
                      `item_type` ENUM('ring', 'chain', 'necklace', 'bracelet', 'earrings', 'bangles', 'coin', 'other') NOT NULL DEFAULT 'other',
                      `item_description` VARCHAR(255) DEFAULT NULL,
                      `purity_karat` DECIMAL(5,2) NOT NULL,
                      `purity_label` VARCHAR(50) DEFAULT '22K',
                      `gross_weight` DECIMAL(10,3) NOT NULL,
                      `stone_deduction` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
                      `other_deduction` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
                      `net_weight` DECIMAL(10,3) NOT NULL,
                      `fine_gold_weight` DECIMAL(10,3) NOT NULL,
                      `gold_rate` DECIMAL(12,2) NOT NULL,
                      `gross_value` DECIMAL(12,2) NOT NULL,
                      `melting_charge` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                      `assessed_value` DECIMAL(12,2) NOT NULL,
                      `remarks` VARCHAR(255) DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      FOREIGN KEY (`exchange_id`) REFERENCES `old_gold_exchanges`(`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                // Columns for orders and invoices
                try {
                    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `old_gold_exchange_id` INT DEFAULT NULL, ADD COLUMN `old_gold_credit` DECIMAL(12,2) DEFAULT 0.00");
                } catch (Exception $e) {}
                try {
                    $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `old_gold_exchange_id` INT DEFAULT NULL, ADD COLUMN `old_gold_credit` DECIMAL(12,2) DEFAULT 0.00");
                } catch (Exception $e) {}
            }
            return $pdo;
        } catch (PDOException $e) {
            // If database does not exist, try creating it
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $tempPdo = new PDO($rootDsn, DB_USER, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                $sqlPath = __DIR__ . '/../database.sql';
                if (file_exists($sqlPath)) {
                    $sqlContent = file_get_contents($sqlPath);
                    $tempPdo->exec("USE `" . DB_NAME . "`;");
                    $tempPdo->exec($sqlContent);
                }

                $pdo = new PDO($dsn, DB_USER, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                return $pdo;
            } catch (Exception $fallbackErr) {
                // Try next password
                continue;
            }
        }
    }

    // If MySQL server is not running on host at all, use SQLite
    return getSQLiteConnection();
}

function getSQLiteConnection() {
    static $sqlitePdo = null;
    if ($sqlitePdo !== null) {
        return $sqlitePdo;
    }

    $dbPath = __DIR__ . '/../data/pos_database.sqlite';
    $isNew = !file_exists($dbPath);
    
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0777, true);
    }

    $sqlitePdo = new PDO("sqlite:" . $dbPath);
    $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlitePdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($isNew || filesize($dbPath) === 0) {
        initSQLiteSchema($sqlitePdo);
    }

    return $sqlitePdo;
}

function initSQLiteSchema($pdo) {
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        mobile TEXT,
        business_name TEXT DEFAULT 'Trivikratech Jewellers',
        role TEXT DEFAULT 'Employee',
        branch TEXT DEFAULT 'Bandra West',
        profile_photo TEXT,
        organization_id TEXT DEFAULT 'ORG-TRV01',
        store_id TEXT DEFAULT 'STR-BDW01',
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS branches (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE,
        name TEXT NOT NULL,
        city TEXT NOT NULL,
        address TEXT,
        phone TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        metal_type TEXT NOT NULL,
        hsn_code TEXT DEFAULT '7113',
        default_making_charge REAL DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sku TEXT UNIQUE,
        name TEXT NOT NULL,
        metal_type TEXT NOT NULL,
        purity TEXT NOT NULL,
        gross_weight REAL NOT NULL,
        stone_weight REAL DEFAULT 0,
        net_weight REAL NOT NULL,
        weight_unit TEXT DEFAULT 'g',
        making_charges REAL DEFAULT 0,
        purchase_price REAL NOT NULL,
        selling_price REAL NOT NULL,
        category TEXT NOT NULL,
        stone_details TEXT,
        barcode TEXT UNIQUE,
        stock INTEGER DEFAULT 0,
        min_stock INTEGER DEFAULT 5,
        image_emoji TEXT DEFAULT '💍',
        image_color TEXT DEFAULT '#FEF3C7',
        branch TEXT DEFAULT 'Bandra West',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        mobile TEXT UNIQUE,
        email TEXT,
        city TEXT DEFAULT 'Mumbai',
        address TEXT,
        birthday TEXT,
        anniversary TEXT,
        gstin TEXT,
        loyalty_points INTEGER DEFAULT 0,
        loyalty_tier TEXT DEFAULT 'Bronze',
        lifetime_value REAL DEFAULT 0,
        total_orders INTEGER DEFAULT 0,
        last_visit TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_no TEXT UNIQUE,
        customer_id INTEGER,
        customer_name TEXT NOT NULL,
        total_items INTEGER DEFAULT 1,
        subtotal REAL NOT NULL,
        making_charges_total REAL DEFAULT 0,
        discount REAL DEFAULT 0,
        gst REAL NOT NULL,
        total REAL NOT NULL,
        payment_method TEXT DEFAULT 'Cash',
        status TEXT DEFAULT 'Completed',
        branch TEXT DEFAULT 'Bandra West',
        sales_person TEXT DEFAULT 'Super Admin',
        order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER,
        product_name TEXT NOT NULL,
        sku TEXT,
        purity TEXT,
        weight REAL DEFAULT 0,
        qty INTEGER DEFAULT 1,
        unit_price REAL NOT NULL,
        making_charges REAL DEFAULT 0,
        discount REAL DEFAULT 0,
        line_total REAL NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_no TEXT UNIQUE,
        order_id INTEGER,
        customer_id INTEGER,
        customer_name TEXT NOT NULL,
        amount REAL NOT NULL,
        gst REAL NOT NULL,
        payment_method TEXT DEFAULT 'Cash',
        status TEXT DEFAULT 'Paid',
        invoice_date TEXT NOT NULL,
        due_date TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS suppliers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        contact TEXT NOT NULL,
        city TEXT NOT NULL,
        metal_type TEXT NOT NULL,
        rating REAL DEFAULT 4.5,
        last_order TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS purchase_orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        po_no TEXT UNIQUE,
        supplier_name TEXT NOT NULL,
        product_id INTEGER,
        metal_type TEXT NOT NULL,
        weight TEXT NOT NULL,
        quantity INTEGER DEFAULT 1,
        amount REAL NOT NULL,
        status TEXT DEFAULT 'Pending',
        ordered_on TEXT NOT NULL,
        expected_on TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS expenses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category TEXT NOT NULL,
        description TEXT NOT NULL,
        amount REAL NOT NULL,
        expense_date TEXT NOT NULL,
        branch TEXT DEFAULT 'Bandra West',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS stock_transfers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        transfer_no TEXT UNIQUE,
        from_branch TEXT NOT NULL,
        to_branch TEXT NOT NULL,
        product_id INTEGER,
        product_name TEXT NOT NULL,
        qty INTEGER NOT NULL,
        status TEXT DEFAULT 'Completed',
        transfer_date TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT DEFAULT 'info',
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE,
        setting_value TEXT NOT NULL
    );
    ";

    $pdo->exec($schema);

    // Seed Users
    $seedUsers = "
    INSERT INTO users (name, email, password, mobile, business_name, role, branch) VALUES
    ('Ajinkya (Super Admin)', 'admin@jeweller.com', '" . password_hash('admin', PASSWORD_DEFAULT) . "', '9876543210', 'Trivikratech Jewellers', 'Super Admin', 'All Branches'),
    ('Rahul Sharma (Employee)', 'employee@jeweller.com', '" . password_hash('employee', PASSWORD_DEFAULT) . "', '9822012345', 'Trivikratech Jewellers', 'Employee', 'Bandra West'),
    ('Preethi S', 'preethi@trivikratech.com', '" . password_hash('admin', PASSWORD_DEFAULT) . "', '8765411111', 'Trivikratech Jewellers', 'Store Manager', 'Bandra West'),
    ('Ravi Kumar', 'ravi@trivikratech.com', '" . password_hash('admin', PASSWORD_DEFAULT) . "', '7654322222', 'Trivikratech Jewellers', 'Employee', 'Bandra West'),
    ('Amita Roy', 'amita@trivikratech.com', '" . password_hash('admin', PASSWORD_DEFAULT) . "', '6543233333', 'Trivikratech Jewellers', 'Employee', 'Andheri East'),
    ('Suresh P', 'suresh@trivikratech.com', '" . password_hash('admin', PASSWORD_DEFAULT) . "', '5432144444', 'Trivikratech Jewellers', 'Jewellery Repairing Assistant', 'Bandra West');
    ";
    $pdo->exec($seedUsers);

    // Seed Branches
    $seedBranches = "
    INSERT INTO branches (code, name, city, address, phone, is_active) VALUES
    ('BR-01', 'Bandra West', 'Mumbai', 'Shop 12, Gold Souk Market, Bandra West, Mumbai 400050', '+91 98765 00001', 1),
    ('BR-02', 'Andheri East', 'Mumbai', 'Unit 4, Diamond Plaza, Andheri East, Mumbai 400069', '+91 98765 00002', 1),
    ('BR-03', 'Powai', 'Mumbai', 'Galleria Mall, Hiranandani Gardens, Powai, Mumbai 400076', '+91 98765 00003', 0);
    ";
    $pdo->exec($seedBranches);

    // Seed Products
    $seedProducts = "
    INSERT INTO products (sku, name, metal_type, purity, gross_weight, stone_weight, net_weight, weight_unit, making_charges, purchase_price, selling_price, category, stone_details, barcode, stock, min_stock, image_emoji, image_color, branch) VALUES
    ('GN-001', 'Antique Gold Necklace', 'Gold', '22K', 18.5, 0, 18.5, 'g', 8500, 92000, 112000, 'Necklace', NULL, '8901234567890', 5, 2, '📿', '#FEF3C7', 'Bandra West'),
    ('GR-002', 'Solitaire Diamond Ring', 'Diamond', '18K + VVS1', 4.2, 0.15, 4.05, 'g', 6200, 145000, 185000, 'Ring', '0.75ct VVS1 Diamond', '8901234567891', 3, 2, '💍', '#E0F2FE', 'Bandra West'),
    ('GB-003', 'Gold Kada Bangle', 'Gold', '22K', 32.0, 0, 32.0, 'g', 12000, 165000, 192000, 'Bangle', NULL, '8901234567892', 8, 3, '🔶', '#FEF3C7', 'Bandra West'),
    ('SE-004', 'Pearl Drop Earrings', 'Gold', '18K', 6.8, 1.2, 5.6, 'g', 3200, 34000, 42000, 'Earring', 'South Sea Pearl', '8901234567893', 12, 4, '✨', '#F0FDF4', 'Bandra West'),
    ('SB-005', 'Silver Oxidised Bracelet', 'Silver', '925', 28.5, 0, 28.5, 'g', 1800, 4500, 7200, 'Bracelet', NULL, '8901234567894', 20, 5, '🔗', '#F1F5F9', 'Bandra West'),
    ('GP-006', 'Gold Mangalsutra', 'Gold', '22K', 12.3, 0.5, 11.8, 'g', 4500, 62000, 74500, 'Pendant', 'Black beads + Gold', '8901234567895', 6, 2, '🪙', '#FEF3C7', 'Bandra West'),
    ('DP-007', 'Diamond Solitaire Pendant', 'Diamond', '18K + SI1', 2.8, 0.1, 2.7, 'g', 8000, 95000, 125000, 'Pendant', '0.5ct SI1 Diamond', '8901234567896', 4, 2, '💎', '#EFF6FF', 'Bandra West'),
    ('GC-008', 'Gold Coin 8gm', 'Gold', '24K', 8.0, 0, 8.0, 'g', 500, 50400, 54000, 'Coin', '99.9% Pure Gold Hallmarked', '8901234567897', 50, 10, '🪙', '#FFFBEB', 'Bandra West'),
    ('SR-009', 'Silver Filigree Ring', 'Silver', '925', 5.2, 0, 5.2, 'g', 800, 1200, 2800, 'Ring', NULL, '8901234567898', 18, 5, '💍', '#F8FAFC', 'Bandra West'),
    ('GN-010', 'Polki Necklace Set', 'Gold', '22K', 45.0, 5.0, 40.0, 'g', 25000, 248000, 310000, 'Necklace Set', 'Uncut Diamond Polki', '8901234567899', 2, 2, '📿', '#FEF3C7', 'Bandra West'),
    ('PT-011', 'Platinum Band Ring', 'Platinum', 'Pt950', 8.5, 0, 8.5, 'g', 5500, 48000, 62000, 'Ring', '950 Pure Platinum', '8901234567900', 7, 2, '💍', '#F9FAFB', 'Bandra West'),
    ('DE-012', 'Diamond Hoop Earrings', 'Diamond', '18K + VVS2', 5.6, 0.24, 5.36, 'g', 12000, 185000, 225000, 'Earring', '1.2ct VVS2 Diamonds', '8901234567901', 3, 2, '✨', '#EFF6FF', 'Bandra West');
    ";
    $pdo->exec($seedProducts);

    // Seed Customers
    $seedCustomers = "
    INSERT INTO customers (name, mobile, email, city, birthday, anniversary, gstin, loyalty_points, loyalty_tier, lifetime_value, total_orders, last_visit) VALUES
    ('Priya Sharma', '+91 98765 43210', 'priya.sharma@gmail.com', 'Mumbai', '1988-03-15', '2015-11-20', NULL, 8450, 'Platinum', 845000, 24, '2026-05-28'),
    ('Rajesh Mehta', '+91 87654 32109', 'rajesh.m@business.in', 'Mumbai', '1975-07-22', '2002-02-14', '27AAACR5055K1ZG', 12300, 'Diamond', 2340000, 58, '2026-06-01'),
    ('Anita Desai', '+91 76543 21098', 'anita.desai@gmail.com', 'Thane', '1992-12-05', NULL, NULL, 3200, 'Gold', 320000, 12, '2026-04-15'),
    ('Vikram Patel', '+91 65432 10987', 'vpatel@corp.com', 'Navi Mumbai', '1985-09-18', '2012-05-08', '27AABFV8899H1ZK', 5800, 'Platinum', 580000, 19, '2026-05-20'),
    ('Sunita Joshi', '+91 54321 09876', 'sunita.j@yahoo.com', 'Pune', '1995-01-30', NULL, NULL, 1200, 'Silver', 95000, 5, '2026-03-08'),
    ('Arjun Kapoor', '+91 93847 56281', 'arjun.k@outlook.com', 'Mumbai', '1990-06-12', '2018-12-25', NULL, 6900, 'Platinum', 690000, 28, '2026-06-02'),
    ('Kavita Singh', '+91 82736 45190', 'kavita.singh@gmail.com', 'Mumbai', '1982-04-08', '2008-11-11', NULL, 2100, 'Gold', 210000, 9, '2026-05-10'),
    ('Deepak Nair', '+91 74625 83910', 'deepak.n@gmail.com', 'Andheri', '1978-08-25', NULL, NULL, 450, 'Bronze', 28000, 3, '2026-01-22');
    ";
    $pdo->exec($seedCustomers);

    // Seed Orders
    $seedOrders = "
    INSERT INTO orders (order_no, customer_id, customer_name, total_items, subtotal, making_charges_total, discount, gst, total, payment_method, status, branch, sales_person, order_date) VALUES
    ('AG-2026-0892', 1, 'Priya Sharma', 2, 154000, 11700, 5000, 4620, 153620, 'Card', 'Completed', 'Bandra West', 'Ravi Kumar', '2026-06-04 10:23:00'),
    ('AG-2026-0891', 2, 'Rajesh Mehta', 1, 225000, 12000, 10000, 6750, 221750, 'UPI', 'Completed', 'Bandra West', 'Preethi S', '2026-06-04 09:45:00'),
    ('AG-2026-0890', 4, 'Vikram Patel', 3, 82000, 5000, 2000, 2460, 82460, 'Split', 'Pending', 'Andheri East', 'Amita Roy', '2026-06-03 16:12:00'),
    ('AG-2026-0889', 3, 'Anita Desai', 1, 42000, 3200, 0, 1260, 43260, 'Cash', 'Completed', 'Bandra West', 'Ravi Kumar', '2026-06-03 14:55:00'),
    ('AG-2026-0888', 5, 'Sunita Joshi', 2, 9800, 2600, 300, 294, 9794, 'UPI', 'Completed', 'Andheri East', 'Amita Roy', '2026-06-03 12:30:00'),
    ('AG-2026-0887', 6, 'Arjun Kapoor', 1, 310000, 25000, 15000, 9300, 304300, 'Card', 'Completed', 'Bandra West', 'Preethi S', '2026-06-02 11:20:00');
    ";
    $pdo->exec($seedOrders);

    // Seed Invoices
    $seedInvoices = "
    INSERT INTO invoices (invoice_no, order_id, customer_id, customer_name, amount, gst, payment_method, status, invoice_date, due_date) VALUES
    ('INV-2026-0892', 1, 1, 'Priya Sharma', 153620, 4620, 'Card', 'Paid', '2026-06-04', '2026-06-04'),
    ('INV-2026-0891', 2, 2, 'Rajesh Mehta', 221750, 6750, 'UPI', 'Paid', '2026-06-04', '2026-06-04'),
    ('INV-2026-0890', 3, 4, 'Vikram Patel', 82460, 2460, 'Split', 'Pending', '2026-06-03', '2026-06-10'),
    ('INV-2026-0889', 4, 3, 'Anita Desai', 43260, 1260, 'Cash', 'Paid', '2026-06-03', '2026-06-03'),
    ('INV-2026-0888', 5, 5, 'Sunita Joshi', 9794, 294, 'UPI', 'Paid', '2026-06-03', '2026-06-03'),
    ('INV-2026-0887', 6, 6, 'Arjun Kapoor', 304300, 9300, 'Card', 'Paid', '2026-06-02', '2026-06-02');
    ";
    $pdo->exec($seedInvoices);

    // Seed Suppliers
    $seedSuppliers = "
    INSERT INTO suppliers (name, contact, city, metal_type, rating, last_order) VALUES
    ('Rajkot Gold House', '+91 98765 11111', 'Rajkot', 'Gold', 4.8, '2026-05-20'),
    ('Surat Diamond Corp', '+91 87654 22222', 'Surat', 'Diamond', 4.9, '2026-04-15'),
    ('Jaipur Silver Works', '+91 76543 33333', 'Jaipur', 'Silver', 4.6, '2026-05-30'),
    ('Mumbai Platinum Co.', '+91 65432 44444', 'Mumbai', 'Platinum', 4.7, '2026-03-22');
    ";
    $pdo->exec($seedSuppliers);

    // Seed Purchase Orders
    $seedPOs = "
    INSERT INTO purchase_orders (po_no, supplier_name, metal_type, weight, amount, status, ordered_on, expected_on) VALUES
    ('PO-2026-042', 'Rajkot Gold House', 'Gold', '500g', 3150000, 'Pending', '2026-06-01', '2026-06-08'),
    ('PO-2026-041', 'Surat Diamond Corp', 'Diamond', '50ct', 4200000, 'In Transit', '2026-05-25', '2026-06-05'),
    ('PO-2026-040', 'Jaipur Silver Works', 'Silver', '2kg', 175000, 'Received', '2026-05-20', '2026-05-28');
    ";
    $pdo->exec($seedPOs);

    // Seed Expenses
    $seedExpenses = "
    INSERT INTO expenses (category, description, amount, expense_date, branch) VALUES
    ('Rent', 'Monthly rent - Bandra West', 85000, '2026-06-01', 'Bandra West'),
    ('Salaries', 'Staff salaries - June', 268000, '2026-06-01', 'All Branches'),
    ('Utilities', 'Electricity & water', 20000, '2026-06-02', 'Bandra West'),
    ('Marketing', 'Google Ads & Social Media campaigns', 48000, '2026-06-03', 'All Branches'),
    ('Other', 'Showroom maintenance & security', 23000, '2026-06-03', 'Bandra West');
    ";
    $pdo->exec($seedExpenses);

    // Seed Settings
    $seedSettings = "
    INSERT INTO settings (setting_key, setting_value) VALUES
    ('store_name', 'Trivikratech Jewellers Pvt. Ltd.'),
    ('address', 'Shop 12, Gold Souk Market, Bandra West, Mumbai 400050'),
    ('gstin', '27AABFA1234Z1ZX'),
    ('phone', '+91 98765 00000'),
    ('email', 'contact@trivikratech.com'),
    ('gst_rate', '3'),
    ('currency', 'INR'),
    ('rate_gold_24k', '7250'),
    ('rate_gold_22k', '6650'),
    ('rate_gold_18k', '5450'),
    ('rate_silver', '88'),
    ('rate_platinum', '3800'),
    ('rate_diamond_carat', '65000'),
    ('header_text', 'Trivikratech Jewellers'),
    ('footer_text', 'Thank you for your purchase! Jewellery once sold can be exchanged within 7 days with original invoice.'),
    ('paper_size', 'A4'),
    ('sms_notifications', '1'),
    ('email_notifications', '1'),
    ('whatsapp_notifications', '0'),
    ('low_stock_alerts', '1'),
    ('new_order_alerts', '1');
    ";
    $pdo->exec($seedSettings);
}
