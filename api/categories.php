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

if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $metalType = $input['metal_type'] ?? 'Gold';
    $hsnCode = trim($input['hsn_code'] ?? '7113');
    $defaultMaking = (float)($input['default_making_charge'] ?? 0);
    $makingType = $input['making_charge_type'] ?? 'per_gram';

    if (!in_array($makingType, ['per_gram', 'percentage', 'fixed'])) {
        $makingType = 'per_gram';
    }

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Category name is required']);
        exit;
    }

    $validMetals = ['Gold', 'Silver', 'Diamond', 'Platinum'];
    if (!in_array($metalType, $validMetals)) {
        $metalType = 'Gold';
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO categories (name, metal_type, hsn_code, default_making_charge, making_charge_type) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $metalType, $hsnCode, $defaultMaking, $makingType]);
        $catId = $db->lastInsertId();

        // Add notification
        try {
            $db->prepare("INSERT INTO notifications (type, message, is_read) VALUES ('info', ?, 0)")
               ->execute(["New jewellery category added: '{$name}' ({$metalType})"]);
        } catch (Exception $e) {}

        echo json_encode([
            'success' => true,
            'message' => 'Category created successfully',
            'id' => $catId,
            'name' => $name,
            'metal_type' => $metalType,
            'hsn_code' => $hsnCode,
            'default_making_charge' => $defaultMaking,
            'making_charge_type' => $makingType
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $metalType = $input['metal_type'] ?? 'Gold';
    $hsnCode = trim($input['hsn_code'] ?? '7113');
    $defaultMaking = (float)($input['default_making_charge'] ?? 0);
    $makingType = $input['making_charge_type'] ?? 'per_gram';

    if (!in_array($makingType, ['per_gram', 'percentage', 'fixed'])) {
        $makingType = 'per_gram';
    }

    if ($id <= 0 || empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Valid ID and category name are required']);
        exit;
    }

    try {
        $stmt = $db->prepare("
            UPDATE categories 
            SET name = ?, metal_type = ?, hsn_code = ?, default_making_charge = ?, making_charge_type = ? 
            WHERE id = ?
        ");
        $stmt->execute([$name, $metalType, $hsnCode, $defaultMaking, $makingType, $id]);
        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
        exit;
    }

    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'list') {
    try {
        $stmt = $db->query("SELECT * FROM categories ORDER BY metal_type ASC, name ASC");
        echo json_encode(['success' => true, 'categories' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
