<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = getDBConnection();

if ($action === 'create' || $action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? (int)$input['id'] : null;
    $sku = trim($input['sku'] ?? 'SKU-' . rand(100, 999));
    $name = trim($input['name'] ?? '');
    $metalType = $input['metal_type'] ?? 'Gold';
    $purity = $input['purity'] ?? '22K';
    $grossWeight = (float)($input['gross_weight'] ?? 0);
    $stoneWeight = (float)($input['stone_weight'] ?? 0);
    $netWeight = (float)($input['net_weight'] ?? ($grossWeight - $stoneWeight));
    $makingCharges = (float)($input['making_charges'] ?? 0);
    $makingChargeType = $input['making_charge_type'] ?? 'per_gram';
    $makingChargeValue = (float)($input['making_charge_value'] ?? $makingCharges);
    $purchasePrice = (float)($input['purchase_price'] ?? 0);
    $sellingPrice = (float)($input['selling_price'] ?? 0);
    $category = $input['category'] ?? 'General';
    $barcode = trim($input['barcode'] ?? '890' . rand(1000000000, 9999999999));
    $stock = (int)($input['stock'] ?? 1);
    $minStock = (int)($input['min_stock'] ?? 2);
    $emoji = $input['image_emoji'] ?? ($metalType === 'Gold' ? '📿' : ($metalType === 'Diamond' ? '💍' : '🔗'));
    $color = $input['image_color'] ?? '#FEF3C7';
    $stoneDetails = trim($input['stone_details'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Product name is required.']);
        exit;
    }

    try {
        if ($action === 'create' || empty($id)) {
            $stmt = $db->prepare("
                INSERT INTO products (sku, name, metal_type, purity, gross_weight, stone_weight, net_weight, weight_unit, making_charges, making_charge_type, making_charge_value, purchase_price, selling_price, category, stone_details, barcode, stock, min_stock, image_emoji, image_color)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'g', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$sku, $name, $metalType, $purity, $grossWeight, $stoneWeight, $netWeight, $makingCharges, $makingChargeType, $makingChargeValue, $purchasePrice, $sellingPrice, $category, $stoneDetails, $barcode, $stock, $minStock, $emoji, $color]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        } else {
            $stmt = $db->prepare("
                UPDATE products 
                SET name = ?, metal_type = ?, purity = ?, gross_weight = ?, stone_weight = ?, net_weight = ?, making_charges = ?, making_charge_type = ?, making_charge_value = ?, purchase_price = ?, selling_price = ?, category = ?, stone_details = ?, stock = ?, min_stock = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $metalType, $purity, $grossWeight, $stoneWeight, $netWeight, $makingCharges, $makingChargeType, $makingChargeValue, $purchasePrice, $sellingPrice, $category, $stoneDetails, $stock, $minStock, $id]);
            echo json_encode(['success' => true]);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
