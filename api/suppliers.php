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
    $contact = trim($input['contact'] ?? '');
    $city = trim($input['city'] ?? 'Mumbai');
    $metalType = $input['metal_type'] ?? 'Gold & Bullion';
    $rating = (float)($input['rating'] ?? 5.0);

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Supplier name is required']);
        exit;
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO suppliers (name, contact, city, metal_type, rating, last_order) 
            VALUES (?, ?, ?, ?, ?, CURRENT_DATE)
        ");
        $stmt->execute([$name, $contact, $city, $metalType, $rating]);
        $supplierId = $db->lastInsertId();

        echo json_encode([
            'success' => true, 
            'id' => $supplierId,
            'name' => $name,
            'contact' => $contact,
            'city' => $city,
            'metal_type' => $metalType
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'list') {
    try {
        $stmt = $db->query("SELECT * FROM suppliers ORDER BY name ASC");
        echo json_encode(['success' => true, 'suppliers' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
