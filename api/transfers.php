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
    
    $productId = !empty($input['product_id']) ? (int)$input['product_id'] : null;
    $productName = trim($input['product_name'] ?? '');
    $fromBranch = trim($input['from_branch'] ?? 'Bandra West');
    $toBranch = trim($input['to_branch'] ?? '');
    $qty = (int)($input['qty'] ?? 1);
    $status = $input['status'] ?? 'Completed';
    $transferDate = $input['transfer_date'] ?? date('Y-m-d');
    $notes = trim($input['notes'] ?? '');

    if (empty($toBranch)) {
        echo json_encode(['success' => false, 'error' => 'Destination branch is required']);
        exit;
    }
    if ($fromBranch === $toBranch) {
        echo json_encode(['success' => false, 'error' => 'Origin and Destination branch cannot be the same']);
        exit;
    }
    if ($qty < 1) {
        echo json_encode(['success' => false, 'error' => 'Transfer quantity must be at least 1']);
        exit;
    }

    try {
        // If product ID provided, fetch product name if empty and check stock
        if ($productId) {
            $stmtP = $db->prepare("SELECT id, name, sku, stock FROM products WHERE id = ?");
            $stmtP->execute([$productId]);
            $prod = $stmtP->fetch();
            if ($prod) {
                if (empty($productName)) {
                    $productName = $prod['name'];
                }
                // Check if sufficient stock is available
                if ($prod['stock'] < $qty && $status === 'Completed') {
                    // We still allow transfer but warn or deduct stock
                }
            }
        }

        if (empty($productName)) {
            echo json_encode(['success' => false, 'error' => 'Product name is required']);
            exit;
        }

        // Generate unique transfer number TR-YYYY-XX
        $year = date('Y');
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM stock_transfers WHERE transfer_no LIKE ?");
        $stmtCount->execute(["TR-{$year}-%"]);
        $count = (int)$stmtCount->fetchColumn() + 1;
        $transferNo = "TR-{$year}-" . str_pad($count, 2, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        $stmtCheck = $db->prepare("SELECT id FROM stock_transfers WHERE transfer_no = ?");
        $stmtCheck->execute([$transferNo]);
        if ($stmtCheck->fetch()) {
            $transferNo = "TR-{$year}-" . str_pad($count + mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
        }

        $stmt = $db->prepare("
            INSERT INTO stock_transfers (transfer_no, from_branch, to_branch, product_id, product_name, qty, status, transfer_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $transferNo,
            $fromBranch,
            $toBranch,
            $productId,
            $productName,
            $qty,
            $status,
            $transferDate
        ]);

        $transferId = $db->lastInsertId();

        // Add system notification
        try {
            $notifMsg = "Stock Transfer {$transferNo}: {$qty} pcs of '{$productName}' dispatched from {$fromBranch} to {$toBranch}.";
            $db->prepare("INSERT INTO notifications (type, message, is_read) VALUES ('info', ?, 0)")
               ->execute([$notifMsg]);
        } catch (Exception $e) {}

        echo json_encode([
            'success' => true,
            'message' => 'Stock transfer recorded successfully',
            'transfer_id' => $transferId,
            'transfer_no' => $transferNo
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'update_status') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? 'Completed';

    try {
        $stmt = $db->prepare("UPDATE stock_transfers SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true, 'message' => 'Status updated']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("DELETE FROM stock_transfers WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'list') {
    try {
        $stmt = $db->query("
            SELECT st.*, p.sku, p.metal_type, p.purity 
            FROM stock_transfers st
            LEFT JOIN products p ON st.product_id = p.id
            ORDER BY st.transfer_date DESC, st.id DESC
        ");
        echo json_encode(['success' => true, 'transfers' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
