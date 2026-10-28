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
    $mobile = trim($input['mobile'] ?? '');
    $email = trim($input['email'] ?? '');
    $city = trim($input['city'] ?? 'Mumbai');

    if (empty($name) || empty($mobile)) {
        echo json_encode(['success' => false, 'error' => 'Name and mobile number are required.']);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO customers (name, mobile, email, city, loyalty_points, loyalty_tier, lifetime_value, total_orders, last_visit) VALUES (?, ?, ?, ?, 0, 'Bronze', 0, 0, CURRENT_DATE)");
        $stmt->execute([$name, $mobile, $email, $city]);
        $id = $db->lastInsertId();

        echo json_encode(['success' => true, 'customer_id' => $id, 'name' => $name, 'mobile' => $mobile]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Customer already exists or database error.']);
        exit;
    }
}

if ($action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $email = trim($input['email'] ?? '');
    $city = trim($input['city'] ?? 'Mumbai');
    $gstin = trim($input['gstin'] ?? '');

    try {
        $stmt = $db->prepare("UPDATE customers SET name = ?, mobile = ?, email = ?, city = ?, gstin = ? WHERE id = ?");
        $stmt->execute([$name, $mobile, $email, $city, $gstin, $id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
