-- ==============================================================================
-- Trivikratech Jewellery POS & Inventory Management System
-- MySQL Relational Database Schema & Initial Seed Data
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `trivikratech_pos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `trivikratech_pos`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users & Staff Table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `mobile` VARCHAR(20) DEFAULT NULL,
  `business_name` VARCHAR(200) DEFAULT 'Trivikratech Jewellers',
  `role` ENUM('Super Admin', 'Store Manager', 'Employee', 'Jewellery Repairing Assistant') NOT NULL DEFAULT 'Employee',
  `branch` VARCHAR(100) DEFAULT 'Bandra West',
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `organization_id` VARCHAR(50) DEFAULT 'ORG-TRV01',
  `store_id` VARCHAR(50) DEFAULT 'STR-BDW01',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Branches Table
DROP TABLE IF EXISTS `branches`;
CREATE TABLE `branches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories Table
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `metal_type` ENUM('Gold', 'Silver', 'Diamond', 'Platinum') NOT NULL,
  `hsn_code` VARCHAR(20) DEFAULT '7113',
  `default_making_charge` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Products / Inventory Table
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(200) NOT NULL,
  `metal_type` ENUM('Gold', 'Silver', 'Diamond', 'Platinum') NOT NULL,
  `purity` VARCHAR(50) NOT NULL,
  `gross_weight` DECIMAL(10,3) NOT NULL,
  `stone_weight` DECIMAL(10,3) DEFAULT 0.000,
  `net_weight` DECIMAL(10,3) NOT NULL,
  `weight_unit` VARCHAR(10) DEFAULT 'g',
  `making_charges` DECIMAL(10,2) DEFAULT 0.00,
  `purchase_price` DECIMAL(12,2) NOT NULL,
  `selling_price` DECIMAL(12,2) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `stone_details` TEXT DEFAULT NULL,
  `barcode` VARCHAR(100) NOT NULL UNIQUE,
  `stock` INT NOT NULL DEFAULT 0,
  `min_stock` INT NOT NULL DEFAULT 5,
  `image_emoji` VARCHAR(20) DEFAULT '💍',
  `image_color` VARCHAR(20) DEFAULT '#FEF3C7',
  `branch` VARCHAR(100) DEFAULT 'Bandra West',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Customers Table
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(20) NOT NULL UNIQUE,
  `email` VARCHAR(191) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT 'Mumbai',
  `address` TEXT DEFAULT NULL,
  `birthday` DATE DEFAULT NULL,
  `anniversary` DATE DEFAULT NULL,
  `gstin` VARCHAR(30) DEFAULT NULL,
  `loyalty_points` INT DEFAULT 0,
  `loyalty_tier` ENUM('Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond') DEFAULT 'Bronze',
  `lifetime_value` DECIMAL(14,2) DEFAULT 0.00,
  `total_orders` INT DEFAULT 0,
  `last_visit` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Orders Table
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_no` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `total_items` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `making_charges_total` DECIMAL(12,2) DEFAULT 0.00,
  `discount` DECIMAL(12,2) DEFAULT 0.00,
  `gst` DECIMAL(12,2) NOT NULL,
  `total` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('Cash', 'Card', 'UPI', 'Split', 'Gold Exchange') NOT NULL DEFAULT 'Cash',
  `status` ENUM('Completed', 'Pending', 'Cancelled', 'Returned') NOT NULL DEFAULT 'Completed',
  `branch` VARCHAR(100) DEFAULT 'Bandra West',
  `sales_person` VARCHAR(150) DEFAULT 'Super Admin',
  `order_date` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Order Items Table
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `sku` VARCHAR(50) DEFAULT NULL,
  `purity` VARCHAR(50) DEFAULT NULL,
  `weight` DECIMAL(10,3) DEFAULT 0.000,
  `qty` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `making_charges` DECIMAL(10,2) DEFAULT 0.00,
  `discount` DECIMAL(10,2) DEFAULT 0.00,
  `line_total` DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Invoices Table
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
  `order_id` INT DEFAULT NULL,
  `customer_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `gst` DECIMAL(12,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Cash',
  `status` ENUM('Paid', 'Pending', 'Cancelled') NOT NULL DEFAULT 'Paid',
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Suppliers Table
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `contact` VARCHAR(30) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `metal_type` VARCHAR(50) NOT NULL,
  `rating` DECIMAL(3,1) DEFAULT 4.5,
  `last_order` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Purchase Orders Table
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `po_no` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_name` VARCHAR(150) NOT NULL,
  `metal_type` VARCHAR(50) NOT NULL,
  `weight` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `status` ENUM('Pending', 'In Transit', 'Received') NOT NULL DEFAULT 'Pending',
  `ordered_on` DATE NOT NULL,
  `expected_on` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Expenses Table
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` ENUM('Rent', 'Salaries', 'Utilities', 'Marketing', 'Other') NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `branch` VARCHAR(100) DEFAULT 'Bandra West',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Stock Transfers Table
DROP TABLE IF EXISTS `stock_transfers`;
CREATE TABLE `stock_transfers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transfer_no` VARCHAR(50) NOT NULL UNIQUE,
  `from_branch` VARCHAR(100) NOT NULL,
  `to_branch` VARCHAR(100) NOT NULL,
  `product_id` INT DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `qty` INT NOT NULL,
  `status` ENUM('Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Completed',
  `transfer_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Notifications Table
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('alert', 'info', 'success') NOT NULL DEFAULT 'info',
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Live Metal Rates & Settings Table
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- SEED DATA
-- ==============================================================================

-- 1. Users (Passwords hashed: 'admin' and 'employee')
INSERT INTO `users` (`name`, `email`, `password`, `mobile`, `business_name`, `role`, `branch`) VALUES
('Ajinkya (Super Admin)', 'admin@jeweller.com', '$2y$10$wN9a04lJ3/QGz7e81M6bfeV4Uo0eGzQ7k1Vq1w7r8U4n3R5qHqO6K', '9876543210', 'Trivikratech Jewellers', 'Super Admin', 'All Branches'),
('Rahul Sharma (Employee)', 'employee@jeweller.com', '$2y$10$wN9a04lJ3/QGz7e81M6bfeV4Uo0eGzQ7k1Vq1w7r8U4n3R5qHqO6K', '9822012345', 'Trivikratech Jewellers', 'Employee', 'Bandra West'),
('Preethi S', 'preethi@trivikratech.com', '$2y$10$wN9a04lJ3/QGz7e81M6bfeV4Uo0eGzQ7k1Vq1w7r8U4n3R5qHqO6K', '8765411111', 'Trivikratech Jewellers', 'Store Manager', 'Bandra West'),
('Ravi Kumar', 'ravi@trivikratech.com', '$2y$10$wN9a04lJ3/QGz7e81M6bfeV4Uo0eGzQ7k1Vq1w7r8U4n3R5qHqO6K', '7654322222', 'Trivikratech Jewellers', 'Employee', 'Bandra West'),
('Amita Roy', 'amita@trivikratech.com', '$2y$10$wN9a04lJ3/QGz7e81M6bfeV4Uo0eGzQ7k1Vq1w7r8U4n3R5qHqO6K', '6543233333', 'Trivikratech Jewellers', 'Employee', 'Andheri East'),
('Suresh P', 'suresh@trivikratech.com', '$2y$10$wN9a04lJ3/QGz7e81M6bfeV4Uo0eGzQ7k1Vq1w7r8U4n3R5qHqO6K', '5432144444', 'Trivikratech Jewellers', 'Jewellery Repairing Assistant', 'Bandra West');

-- 2. Branches
INSERT INTO `branches` (`code`, `name`, `city`, `address`, `phone`, `is_active`) VALUES
('BR-01', 'Bandra West', 'Mumbai', 'Shop 12, Gold Souk Market, Bandra West, Mumbai 400050', '+91 98765 00001', 1),
('BR-02', 'Andheri East', 'Mumbai', 'Unit 4, Diamond Plaza, Andheri East, Mumbai 400069', '+91 98765 00002', 1),
('BR-03', 'Powai', 'Mumbai', 'Galleria Mall, Hiranandani Gardens, Powai, Mumbai 400076', '+91 98765 00003', 0);

-- 3. Categories
INSERT INTO `categories` (`name`, `metal_type`, `hsn_code`, `default_making_charge`) VALUES
('Necklace', 'Gold', '7113', 8500.00),
('Ring', 'Gold', '7113', 4500.00),
('Bangle', 'Gold', '7113', 12000.00),
('Earring', 'Gold', '7113', 3200.00),
('Pendant', 'Gold', '7113', 4500.00),
('Coin', 'Gold', '7113', 500.00),
('Necklace Set', 'Gold', '7113', 25000.00),
('Bracelet', 'Silver', '7113', 1800.00),
('Ring', 'Silver', '7113', 800.00),
('Ring', 'Diamond', '7113', 6200.00),
('Pendant', 'Diamond', '7113', 8000.00),
('Earring', 'Diamond', '7113', 12000.00),
('Ring', 'Platinum', '7113', 5500.00);

-- 4. Products
INSERT INTO `products` (`sku`, `name`, `metal_type`, `purity`, `gross_weight`, `stone_weight`, `net_weight`, `weight_unit`, `making_charges`, `purchase_price`, `selling_price`, `category`, `stone_details`, `barcode`, `stock`, `min_stock`, `image_emoji`, `image_color`, `branch`) VALUES
('GN-001', 'Antique Gold Necklace', 'Gold', '22K', 18.500, 0.000, 18.500, 'g', 8500.00, 92000.00, 112000.00, 'Necklace', NULL, '8901234567890', 5, 2, '📿', '#FEF3C7', 'Bandra West'),
('GR-002', 'Solitaire Diamond Ring', 'Diamond', '18K + VVS1', 4.200, 0.150, 4.050, 'g', 6200.00, 145000.00, 185000.00, 'Ring', '0.75ct VVS1 Diamond', '8901234567891', 3, 2, '💍', '#E0F2FE', 'Bandra West'),
('GB-003', 'Gold Kada Bangle', 'Gold', '22K', 32.000, 0.000, 32.000, 'g', 12000.00, 165000.00, 192000.00, 'Bangle', NULL, '8901234567892', 8, 3, '🔶', '#FEF3C7', 'Bandra West'),
('SE-004', 'Pearl Drop Earrings', 'Gold', '18K', 6.800, 1.200, 5.600, 'g', 3200.00, 34000.00, 42000.00, 'Earring', 'South Sea Pearl', '8901234567893', 12, 4, '✨', '#F0FDF4', 'Bandra West'),
('SB-005', 'Silver Oxidised Bracelet', 'Silver', '925', 28.500, 0.000, 28.500, 'g', 1800.00, 4500.00, 7200.00, 'Bracelet', NULL, '8901234567894', 20, 5, '🔗', '#F1F5F9', 'Bandra West'),
('GP-006', 'Gold Mangalsutra', 'Gold', '22K', 12.300, 0.500, 11.800, 'g', 4500.00, 62000.00, 74500.00, 'Pendant', 'Black beads + Gold', '8901234567895', 6, 2, '🪙', '#FEF3C7', 'Bandra West'),
('DP-007', 'Diamond Solitaire Pendant', 'Diamond', '18K + SI1', 2.800, 0.100, 2.700, 'g', 8000.00, 95000.00, 125000.00, 'Pendant', '0.5ct SI1 Diamond', '8901234567896', 4, 2, '💎', '#EFF6FF', 'Bandra West'),
('GC-008', 'Gold Coin 8gm', 'Gold', '24K', 8.000, 0.000, 8.000, 'g', 500.00, 50400.00, 54000.00, 'Coin', '99.9% Pure Gold Hallmarked', '8901234567897', 50, 10, '🪙', '#FFFBEB', 'Bandra West'),
('SR-009', 'Silver Filigree Ring', 'Silver', '925', 5.200, 0.000, 5.200, 'g', 8000.00, 1200.00, 2800.00, 'Ring', NULL, '8901234567898', 18, 5, '💍', '#F8FAFC', 'Bandra West'),
('GN-010', 'Polki Necklace Set', 'Gold', '22K', 45.000, 5.000, 40.000, 'g', 25000.00, 248000.00, 310000.00, 'Necklace Set', 'Uncut Diamond Polki', '8901234567899', 2, 2, '📿', '#FEF3C7', 'Bandra West'),
('PT-011', 'Platinum Band Ring', 'Platinum', 'Pt950', 8.500, 0.000, 8.500, 'g', 5500.00, 48000.00, 62000.00, 'Ring', '950 Pure Platinum', '8901234567900', 7, 2, '💍', '#F9FAFB', 'Bandra West'),
('DE-012', 'Diamond Hoop Earrings', 'Diamond', '18K + VVS2', 5.600, 0.240, 5.360, 'g', 12000.00, 185000.00, 225000.00, 'Earring', '1.2ct VVS2 Diamonds', '8901234567901', 3, 2, '✨', '#EFF6FF', 'Bandra West');

-- 5. Customers
INSERT INTO `customers` (`name`, `mobile`, `email`, `city`, `birthday`, `anniversary`, `gstin`, `loyalty_points`, `loyalty_tier`, `lifetime_value`, `total_orders`, `last_visit`, `created_at`) VALUES
('Priya Sharma', '+91 98765 43210', 'priya.sharma@gmail.com', 'Mumbai', '1988-03-15', '2015-11-20', NULL, 8450, 'Platinum', 845000.00, 24, '2026-05-28', '2019-06-14 10:00:00'),
('Rajesh Mehta', '+91 87654 32109', 'rajesh.m@business.in', 'Mumbai', '1975-07-22', '2002-02-14', '27AAACR5055K1ZG', 12300, 'Diamond', 2340000.00, 58, '2026-06-01', '2017-01-08 11:30:00'),
('Anita Desai', '+91 76543 21098', 'anita.desai@gmail.com', 'Thane', '1992-12-05', NULL, NULL, 3200, 'Gold', 320000.00, 12, '2026-04-15', '2021-08-22 14:15:00'),
('Vikram Patel', '+91 65432 10987', 'vpatel@corp.com', 'Navi Mumbai', '1985-09-18', '2012-05-08', '27AABFV8899H1ZK', 5800, 'Platinum', 580000.00, 19, '2026-05-20', '2020-03-11 16:45:00'),
('Sunita Joshi', '+91 54321 09876', 'sunita.j@yahoo.com', 'Pune', '1995-01-30', NULL, NULL, 1200, 'Silver', 95000.00, 5, '2026-03-08', '2023-11-15 12:20:00'),
('Arjun Kapoor', '+91 93847 56281', 'arjun.k@outlook.com', 'Mumbai', '1990-06-12', '2018-12-25', NULL, 6900, 'Platinum', 690000.00, 28, '2026-06-02', '2018-09-03 17:10:00'),
('Kavita Singh', '+91 82736 45190', 'kavita.singh@gmail.com', 'Mumbai', '1982-04-08', '2008-11-11', NULL, 2100, 'Gold', 210000.00, 9, '2026-05-10', '2022-02-28 13:00:00'),
('Deepak Nair', '+91 74625 83910', 'deepak.n@gmail.com', 'Andheri', '1978-08-25', NULL, NULL, 450, 'Bronze', 28000.00, 3, '2026-01-22', '2025-01-10 15:30:00');

-- 6. Orders
INSERT INTO `orders` (`order_no`, `customer_id`, `customer_name`, `total_items`, `subtotal`, `making_charges_total`, `discount`, `gst`, `total`, `payment_method`, `status`, `branch`, `sales_person`, `order_date`) VALUES
('AG-2026-0892', 1, 'Priya Sharma', 2, 154000.00, 11700.00, 5000.00, 4620.00, 153620.00, 'Card', 'Completed', 'Bandra West', 'Ravi Kumar', '2026-06-04 10:23:00'),
('AG-2026-0891', 2, 'Rajesh Mehta', 1, 225000.00, 12000.00, 10000.00, 6750.00, 221750.00, 'UPI', 'Completed', 'Bandra West', 'Preethi S', '2026-06-04 09:45:00'),
('AG-2026-0890', 4, 'Vikram Patel', 3, 82000.00, 5000.00, 2000.00, 2460.00, 82460.00, 'Split', 'Pending', 'Andheri East', 'Amita Roy', '2026-06-03 16:12:00'),
('AG-2026-0889', 3, 'Anita Desai', 1, 42000.00, 3200.00, 0.00, 1260.00, 43260.00, 'Cash', 'Completed', 'Bandra West', 'Ravi Kumar', '2026-06-03 14:55:00'),
('AG-2026-0888', 5, 'Sunita Joshi', 2, 9800.00, 2600.00, 300.00, 294.00, 9794.00, 'UPI', 'Completed', 'Andheri East', 'Amita Roy', '2026-06-03 12:30:00'),
('AG-2026-0887', 6, 'Arjun Kapoor', 1, 310000.00, 25000.00, 15000.00, 9300.00, 304300.00, 'Card', 'Completed', 'Bandra West', 'Preethi S', '2026-06-02 11:20:00');

-- 7. Order Items
INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `sku`, `purity`, `weight`, `qty`, `unit_price`, `making_charges`, `discount`, `line_total`) VALUES
(1, 1, 'Antique Gold Necklace', 'GN-001', '22K', 18.500, 1, 112000.00, 8500.00, 3000.00, 109000.00),
(1, 4, 'Pearl Drop Earrings', 'SE-004', '18K', 6.800, 1, 42000.00, 3200.00, 2000.00, 40000.00),
(2, 12, 'Diamond Hoop Earrings', 'DE-012', '18K + VVS2', 5.600, 1, 225000.00, 12000.00, 10000.00, 215000.00),
(3, 5, 'Silver Oxidised Bracelet', 'SB-005', '925', 28.500, 2, 7200.00, 1800.00, 500.00, 13900.00),
(3, 6, 'Gold Mangalsutra', 'GP-006', '22K', 12.300, 1, 74500.00, 4500.00, 1500.00, 73000.00),
(4, 4, 'Pearl Drop Earrings', 'SE-004', '18K', 6.800, 1, 42000.00, 3200.00, 0.00, 42000.00),
(5, 5, 'Silver Oxidised Bracelet', 'SB-005', '925', 28.500, 1, 7200.00, 1800.00, 200.00, 7000.00),
(5, 9, 'Silver Filigree Ring', 'SR-009', '925', 5.200, 1, 2800.00, 800.00, 100.00, 2700.00),
(6, 10, 'Polki Necklace Set', 'GN-010', '22K', 45.000, 1, 310000.00, 25000.00, 15000.00, 295000.00);

-- 8. Invoices
INSERT INTO `invoices` (`invoice_no`, `order_id`, `customer_id`, `customer_name`, `amount`, `gst`, `payment_method`, `status`, `invoice_date`, `due_date`) VALUES
('INV-2026-0892', 1, 1, 'Priya Sharma', 153620.00, 4620.00, 'Card', 'Paid', '2026-06-04', '2026-06-04'),
('INV-2026-0891', 2, 2, 'Rajesh Mehta', 221750.00, 6750.00, 'UPI', 'Paid', '2026-06-04', '2026-06-04'),
('INV-2026-0890', 3, 4, 'Vikram Patel', 82460.00, 2460.00, 'Split', 'Pending', '2026-06-03', '2026-06-10'),
('INV-2026-0889', 4, 3, 'Anita Desai', 43260.00, 1260.00, 'Cash', 'Paid', '2026-06-03', '2026-06-03'),
('INV-2026-0888', 5, 5, 'Sunita Joshi', 9794.00, 294.00, 'UPI', 'Paid', '2026-06-03', '2026-06-03'),
('INV-2026-0887', 6, 6, 'Arjun Kapoor', 304300.00, 9300.00, 'Card', 'Paid', '2026-06-02', '2026-06-02');

-- 9. Suppliers
INSERT INTO `suppliers` (`name`, `contact`, `city`, `metal_type`, `rating`, `last_order`) VALUES
('Rajkot Gold House', '+91 98765 11111', 'Rajkot', 'Gold', 4.8, '2026-05-20'),
('Surat Diamond Corp', '+91 87654 22222', 'Surat', 'Diamond', 4.9, '2026-04-15'),
('Jaipur Silver Works', '+91 76543 33333', 'Jaipur', 'Silver', 4.6, '2026-05-30'),
('Mumbai Platinum Co.', '+91 65432 44444', 'Mumbai', 'Platinum', 4.7, '2026-03-22');

-- 10. Purchase Orders
INSERT INTO `purchase_orders` (`po_no`, `supplier_name`, `metal_type`, `weight`, `amount`, `status`, `ordered_on`, `expected_on`) VALUES
('PO-2026-042', 'Rajkot Gold House', 'Gold', '500g', 3150000.00, 'Pending', '2026-06-01', '2026-06-08'),
('PO-2026-041', 'Surat Diamond Corp', 'Diamond', '50ct', 4200000.00, 'In Transit', '2026-05-25', '2026-06-05'),
('PO-2026-040', 'Jaipur Silver Works', 'Silver', '2kg', 175000.00, 'Received', '2026-05-20', '2026-05-28');

-- 11. Expenses
INSERT INTO `expenses` (`category`, `description`, `amount`, `expense_date`, `branch`) VALUES
('Rent', 'Monthly rent - Bandra West', 85000.00, '2026-06-01', 'Bandra West'),
('Salaries', 'Staff salaries - June', 268000.00, '2026-06-01', 'All Branches'),
('Utilities', 'Electricity & water', 20000.00, '2026-06-02', 'Bandra West'),
('Marketing', 'Google Ads & Social Media campaigns', 48000.00, '2026-06-03', 'All Branches'),
('Other', 'Showroom maintenance & security', 23000.00, '2026-06-03', 'Bandra West');

-- 12. Stock Transfers
INSERT INTO `stock_transfers` (`transfer_no`, `from_branch`, `to_branch`, `product_id`, `product_name`, `qty`, `status`, `transfer_date`) VALUES
('TR-2026-01', 'Bandra West', 'Andheri East', 1, 'Antique Gold Necklace', 2, 'Completed', '2026-06-01'),
('TR-2026-02', 'Bandra West', 'Andheri East', 7, 'Diamond Solitaire Pendant', 1, 'Completed', '2026-06-02');

-- 13. Notifications
INSERT INTO `notifications` (`type`, `message`, `is_read`, `created_at`) VALUES
('alert', 'Diamond stock below minimum threshold', 0, NOW() - INTERVAL 10 MINUTE),
('alert', 'Platinum stock below minimum threshold', 0, NOW() - INTERVAL 2 HOUR),
('info', 'Purchase Order PO-2026-042 pending approval', 0, NOW() - INTERVAL 4 HOUR),
('success', 'New sale AG-2026-0892 completed — ₹1,53,620', 1, NOW() - INTERVAL 1 DAY);

-- 14. Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
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

-- 15. Old Gold Exchanges Table
DROP TABLE IF EXISTS `old_gold_exchanges`;
CREATE TABLE `old_gold_exchanges` (
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
  INDEX idx_exchange_date (`exchange_date`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Old Gold Exchange Items Table
DROP TABLE IF EXISTS `old_gold_exchange_items`;
CREATE TABLE `old_gold_exchange_items` (
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

