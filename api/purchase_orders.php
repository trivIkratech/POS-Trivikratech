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
    $supplier = trim($input['supplier_name'] ?? '');
    $productId = !empty($input['product_id']) ? (int)$input['product_id'] : null;
    $metal = $input['metal_type'] ?? 'Gold';
    $weight = trim($input['weight'] ?? '100g');
    $quantity = max(1, (int)($input['quantity'] ?? 1));
    $amount = (float)($input['amount'] ?? 0);
    $status = $input['status'] ?? 'Pending';
    $poNo = 'PO-' . date('Y') . '-' . rand(1000, 9999);
    $orderedOn = $input['ordered_on'] ?? date('Y-m-d');
    $expectedOn = $input['expected_on'] ?? date('Y-m-d', strtotime('+7 days'));
    $importStock = !empty($input['import_stock']) || $status === 'Received';

    if (empty($supplier)) {
        echo json_encode(['success' => false, 'error' => 'Supplier name is required']);
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO purchase_orders 
            (po_no, supplier_name, product_id, metal_type, weight, quantity, amount, status, ordered_on, expected_on) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$poNo, $supplier, $productId, $metal, $weight, $quantity, $amount, $status, $orderedOn, $expectedOn]);
        $poId = $db->lastInsertId();

        // If marked Received or importStock requested, increment live inventory stock
        if ($importStock && $productId) {
            $upd = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $upd->execute([$quantity, $productId]);
        }

        // Notification log
        $notifStmt = $db->prepare("INSERT INTO notifications (type, message) VALUES ('info', ?)");
        $notifStmt->execute(["New Purchase Order {$poNo} generated for {$supplier} — ₹" . number_format($amount)]);

        $db->commit();
        echo json_encode(['success' => true, 'id' => $poId, 'po_no' => $poNo]);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'receive_stock') {
    $input = json_decode(file_get_contents('php://input'), true);
    $poId = (int)($input['id'] ?? ($_GET['id'] ?? 0));

    if (!$poId) {
        echo json_encode(['success' => false, 'error' => 'Invalid PO ID']);
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
        $stmt->execute([$poId]);
        $po = $stmt->fetch();

        if (!$po) {
            echo json_encode(['success' => false, 'error' => 'Purchase order not found']);
            exit;
        }

        if ($po['status'] === 'Received') {
            echo json_encode(['success' => false, 'error' => 'Stock already imported for this PO']);
            exit;
        }

        // Update PO status to Received
        $updPo = $db->prepare("UPDATE purchase_orders SET status = 'Received' WHERE id = ?");
        $updPo->execute([$poId]);

        // If product_id linked, increment stock
        $qty = max(1, (int)$po['quantity']);
        if (!empty($po['product_id'])) {
            $updProd = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $updProd->execute([$qty, $po['product_id']]);
        }

        // Log notification
        $notifStmt = $db->prepare("INSERT INTO notifications (type, message) VALUES ('success', ?)");
        $notifStmt->execute(["Stock Received & Imported: PO {$po['po_no']} — {$qty} units added to vault."]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => "Stock successfully imported! ({$qty} units added)"]);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("DELETE FROM purchase_orders WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'bulk_import') {
    $input = json_decode(file_get_contents('php://input'), true);
    $items = $input['items'] ?? [];
    $supplierName = $input['supplier_name'] ?? 'Direct Stock Inward';

    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'No items provided for import']);
        exit;
    }

    try {
        $db->beginTransaction();
        $importedCount = 0;
        $totalValuation = 0;

        foreach ($items as $item) {
            $sku = trim($item['sku'] ?? '');
            $name = trim($item['name'] ?? 'Imported Jewellery Item');
            $metal = $item['metal_type'] ?? 'Gold';
            $purity = $item['purity'] ?? '22K';
            $weight = (float)($item['net_weight'] ?? 10.0);
            $qty = max(1, (int)($item['stock'] ?? 1));
            $purchasePrice = (float)($item['purchase_price'] ?? 0);
            $sellingPrice = (float)($item['selling_price'] ?? ($purchasePrice * 1.15));
            $making = (float)($item['making_charges'] ?? 2000);
            $category = $item['category'] ?? 'Gold Jewellery';

            // Check if SKU exists
            $chk = $db->prepare("SELECT id FROM products WHERE sku = ?");
            $chk->execute([$sku]);
            $existing = $chk->fetch();

            if ($existing) {
                // Update stock
                $u = $db->prepare("UPDATE products SET stock = stock + ?, selling_price = ? WHERE id = ?");
                $u->execute([$qty, $sellingPrice, $existing['id']]);
            } else {
                // Insert new product
                $ins = $db->prepare("
                    INSERT INTO products (sku, name, category, metal_type, purity, gross_weight, net_weight, making_charges, purchase_price, selling_price, stock, min_stock, barcode, image_emoji, image_color)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2, ?, '💍', '#FEF3C7')
                ");
                $barcode = '890' . rand(1000000000, 9999999999);
                $ins->execute([$sku, $name, $category, $metal, $purity, $weight, $weight, $making, $purchasePrice, $sellingPrice, $qty, $barcode]);
            }

            $importedCount += $qty;
            $totalValuation += ($sellingPrice * $qty);
        }

        // Create a Purchase Order record for this bulk import batch
        $poNo = 'PO-IMP-' . date('ymd') . '-' . rand(100, 999);
        $poStmt = $db->prepare("
            INSERT INTO purchase_orders 
            (po_no, supplier_name, metal_type, weight, quantity, amount, status, ordered_on, expected_on) 
            VALUES (?, ?, 'Mixed Stock', ?, ?, ?, 'Received', CURRENT_DATE, CURRENT_DATE)
        ");
        $poStmt->execute([$poNo, $supplierName, "{$importedCount} units batch", $importedCount, $totalValuation]);

        $db->commit();
        echo json_encode([
            'success' => true, 
            'imported_count' => $importedCount,
            'total_valuation' => $totalValuation,
            'po_no' => $poNo
        ]);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);

