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
    $category = $input['category'] ?? 'Other';
    $description = trim($input['description'] ?? '');
    $amount = (float)($input['amount'] ?? 0);
    $branch = $_SESSION['active_branch'] ?? 'Bandra West';
    $date = $input['expense_date'] ?? date('Y-m-d');

    if (empty($description) || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Valid description and amount required.']);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO expenses (category, description, amount, expense_date, branch) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$category, $description, $amount, $date, $branch]);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
