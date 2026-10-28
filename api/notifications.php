<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = getDBConnection();
$action = $_GET['action'] ?? '';

if ($action === 'list') {
    try {
        $stmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 15");
        $notifications = $stmt->fetchAll();
        
        $countStmt = $db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
        $unreadCount = (int)$countStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'mark_all_read') {
    try {
        $db->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'clear_all') {
    try {
        $db->exec("DELETE FROM notifications");
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
