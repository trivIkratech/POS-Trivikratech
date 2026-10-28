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

if ($action === 'complete_sale') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['cart_items'])) {
        echo json_encode(['success' => false, 'error' => 'Cart is empty.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Generate Order & Invoice Numbers
        $randSeq = rand(1000, 9999);
        $orderNo = "AG-" . date('Y') . "-" . str_pad($randSeq, 4, '0', STR_PAD_LEFT);
        $invoiceNo = "INV-" . date('Y') . "-" . str_pad($randSeq, 4, '0', STR_PAD_LEFT);

        $customerId = !empty($input['customer_id']) ? (int)$input['customer_id'] : null;
        $customerName = !empty($input['customer_name']) ? $input['customer_name'] : 'Walk-in Customer';
        $subtotal = (float)($input['subtotal'] ?? 0);
        $makingCharges = (float)($input['making_charges'] ?? 0);
        $discount = (float)($input['discount'] ?? 0);
        $gst = (float)($input['gst'] ?? 0);
        $total = (float)($input['grand_total'] ?? 0);
        $paymentMethod = $input['payment_method'] ?? 'Cash';
        $branch = $_SESSION['active_branch'] ?? 'Bandra West';
        $salesPerson = $_SESSION['user_name'] ?? 'Super Admin';
        $totalItems = array_reduce($input['cart_items'], fn($carry, $item) => $carry + (int)$item['qty'], 0);

        // Old Gold Exchange Credit Validation
        $exchangeId = !empty($input['exchange_id']) ? (int)$input['exchange_id'] : null;
        $exchangeCreditApplied = (float)($input['exchange_credit_applied'] ?? 0);
        $exchangeRecord = null;

        if ($exchangeId) {
            $ogeStmt = $db->prepare("SELECT * FROM old_gold_exchanges WHERE id = ? FOR UPDATE");
            $ogeStmt->execute([$exchangeId]);
            $exchangeRecord = $ogeStmt->fetch();

            if (!$exchangeRecord) {
                throw new Exception("Selected Old Gold Exchange voucher not found.");
            }

            if (!in_array($exchangeRecord['status'], ['Approved', 'Partially Redeemed'])) {
                throw new Exception("Old Gold Exchange {$exchangeRecord['exchange_no']} cannot be redeemed because its status is '{$exchangeRecord['status']}'.");
            }

            if ($exchangeCreditApplied <= 0) {
                throw new Exception("Applied exchange credit must be greater than zero.");
            }

            if ($exchangeCreditApplied > (float)$exchangeRecord['credit_balance'] + 0.01) {
                throw new Exception("Applied exchange credit (₹" . number_format($exchangeCreditApplied, 2) . ") exceeds available voucher balance (₹" . number_format($exchangeRecord['credit_balance'], 2) . ").");
            }

            // If fully or partially paid by exchange, set payment method or note
            if ($total <= 0.01) {
                $paymentMethod = 'Gold Exchange';
            } elseif ($paymentMethod === 'Cash' || empty($paymentMethod)) {
                $paymentMethod = 'Gold Exchange';
            }
        }

        // 2. Insert into orders table
        $orderStmt = $db->prepare("
            INSERT INTO orders (order_no, customer_id, customer_name, total_items, subtotal, making_charges_total, discount, gst, total, old_gold_exchange_id, old_gold_credit, payment_method, status, branch, sales_person, order_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', ?, ?, NOW())
        ");
        $orderStmt->execute([$orderNo, $customerId, $customerName, $totalItems, $subtotal, $makingCharges, $discount, $gst, $total, $exchangeId, $exchangeCreditApplied, $paymentMethod, $branch, $salesPerson]);
        $orderId = $db->lastInsertId();

        // 3. Insert order items & reduce stock
        $itemStmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, sku, purity, weight, qty, unit_price, making_charges, discount, line_total)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stockStmt = $db->prepare("UPDATE products SET stock = CASE WHEN stock >= ? THEN stock - ? ELSE 0 END WHERE id = ?");

        foreach ($input['cart_items'] as $item) {
            $prodId = (int)$item['id'];
            $qty = (int)$item['qty'];
            $lineTotal = ((float)$item['price'] * $qty) - (float)($item['discount'] ?? 0);

            $itemStmt->execute([
                $orderId,
                $prodId,
                $item['name'],
                $item['sku'] ?? '',
                $item['purity'] ?? '',
                (float)($item['weight'] ?? 0),
                $qty,
                (float)$item['price'],
                (float)($item['making'] ?? 0),
                (float)($item['discount'] ?? 0),
                $lineTotal
            ]);

            // Deduct stock in real-time
            $stockStmt->execute([$qty, $qty, $prodId]);
        }

        // 4. Create Invoice
        $invStmt = $db->prepare("
            INSERT INTO invoices (invoice_no, order_id, customer_id, customer_name, amount, gst, old_gold_exchange_id, old_gold_credit, payment_method, status, invoice_date, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', CURRENT_DATE, CURRENT_DATE)
        ");
        $invStmt->execute([$invoiceNo, $orderId, $customerId, $customerName, $total, $gst, $exchangeId, $exchangeCreditApplied, $paymentMethod]);
        $invoiceId = $db->lastInsertId();

        // 5. Update Old Gold Exchange status & redemption details atomically
        if ($exchangeRecord) {
            $remainingCredit = max(0, round((float)$exchangeRecord['credit_balance'] - $exchangeCreditApplied, 2));
            $newStatus = ($remainingCredit <= 0.01) ? 'Redeemed' : 'Partially Redeemed';

            $updateOge = $db->prepare("
                UPDATE old_gold_exchanges 
                SET credit_balance = ?, 
                    status = ?, 
                    redeemed_order_id = ?, 
                    redeemed_invoice_id = ?, 
                    redeemed_at = NOW(), 
                    redeemed_amount = redeemed_amount + ? 
                WHERE id = ?
            ");
            $updateOge->execute([$remainingCredit, $newStatus, $orderId, $invoiceId, $exchangeCreditApplied, $exchangeId]);
        }

        // 6. Update Customer loyalty points & lifetime value if registered
        if ($customerId) {
            $pointsEarned = (int)floor($total / 1000);
            $custUpdate = $db->prepare("
                UPDATE customers 
                SET loyalty_points = loyalty_points + ?,
                    lifetime_value = lifetime_value + ?,
                    total_orders = total_orders + 1,
                    last_visit = CURRENT_DATE
                WHERE id = ?
            ");
            $custUpdate->execute([$pointsEarned, $total, $customerId]);
        }

        // 7. Log notification
        $notifStmt = $db->prepare("INSERT INTO notifications (type, message) VALUES ('success', ?)");
        $notifMsg = "New POS sale {$orderNo} — {$customerName} — ₹" . number_format($total, 2);
        if ($exchangeCreditApplied > 0) {
            $notifMsg .= " (incl. ₹" . number_format($exchangeCreditApplied, 2) . " Old Gold Credit from {$exchangeRecord['exchange_no']})";
        }
        $notifStmt->execute([$notifMsg]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'order_no' => $orderNo,
            'invoice_no' => $invoiceNo,
            'order_id' => $orderId,
            'store_name' => getSetting('store_name', 'Kulthe Suvarnkar'),
            'store_address' => getSetting('address', 'Bandra West, Mumbai'),
            'store_gstin' => getSetting('gstin', '27AABFA1234Z1ZX'),
            'store_phone' => getSetting('phone', '+91 98765 00000')
        ]);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
