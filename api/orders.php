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

if ($action === 'process_return') {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = (int)($input['order_id'] ?? 0);
    $reason = trim($input['reason'] ?? 'Customer Request');

    try {
        $stmt = $db->prepare("UPDATE orders SET status = 'Returned' WHERE id = ?");
        $stmt->execute([$orderId]);

        // Also update invoice status
        $invStmt = $db->prepare("UPDATE invoices SET status = 'Cancelled' WHERE order_id = ?");
        $invStmt->execute([$orderId]);

        // Log notification
        $notif = $db->prepare("INSERT INTO notifications (type, message) VALUES ('info', ?)");
        $notif->execute(["Return processed for Order #{$orderId}. Reason: {$reason}"]);

        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
