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
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'Employee';
$isManagerOrAdmin = in_array($userRole, ['Super Admin', 'Store Manager']);

/**
 * 1. CREATE NEW OLD GOLD EXCHANGE
 */
if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON payload.']);
        exit;
    }

    $customerName = trim($input['customer_name'] ?? '');
    $customerMobile = trim($input['customer_mobile'] ?? '');
    $customerId = !empty($input['customer_id']) ? (int)$input['customer_id'] : null;
    $exchangeDate = !empty($input['exchange_date']) ? $input['exchange_date'] : date('Y-m-d');
    $goldRate24k = (float)($input['gold_rate_24k'] ?? 0);
    $rateBasis = mb_substr(trim($input['rate_basis'] ?? '24K Gold Rate / gram'), 0, 180);
    $branch = $_SESSION['active_branch'] ?? 'Bandra West';
    $remarks = trim($input['remarks'] ?? '');
    $rawItems = $input['items'] ?? [];
    $requestedStatus = trim($input['status'] ?? 'Approved');

    // Validation
    if (empty($customerName)) {
        echo json_encode(['success' => false, 'error' => 'Customer name is required.']);
        exit;
    }
    if (empty($customerMobile) || !preg_match('/^[0-9+\-\s]{8,15}$/', $customerMobile)) {
        echo json_encode(['success' => false, 'error' => 'A valid customer mobile number is required.']);
        exit;
    }
    if ($goldRate24k <= 0) {
        echo json_encode(['success' => false, 'error' => 'Applicable 24K gold rate per gram must be greater than zero.']);
        exit;
    }
    if (empty($rawItems) || !is_array($rawItems)) {
        echo json_encode(['success' => false, 'error' => 'At least one old gold item must be assessed.']);
        exit;
    }

    // Role-based approval policy
    if (!$isManagerOrAdmin && $requestedStatus === 'Approved') {
        $status = 'Pending Approval';
    } elseif ($requestedStatus === 'Draft') {
        $status = 'Draft';
    } else {
        $status = 'Approved';
    }

    try {
        $db->beginTransaction();

        // If customer_id is empty, check if mobile exists in customers table
        if (!$customerId && !empty($customerMobile)) {
            $findCust = $db->prepare("SELECT id FROM customers WHERE mobile = ? LIMIT 1");
            $findCust->execute([$customerMobile]);
            $existingCustId = $findCust->fetchColumn();
            if ($existingCustId) {
                $customerId = (int)$existingCustId;
            } else {
                // Auto-register new customer if requested
                if (!empty($input['auto_register_customer'])) {
                    $insertCust = $db->prepare("INSERT INTO customers (name, mobile, city, loyalty_tier) VALUES (?, ?, ?, 'Bronze')");
                    $insertCust->execute([$customerName, $customerMobile, $branch]);
                    $customerId = (int)$db->lastInsertId();
                }
            }
        }

        // Server-Side Calculation & Validation for each Item
        $calculatedItems = [];
        $totalGrossWeight = 0.0;
        $totalWeightDeduction = 0.0;
        $totalNetWeight = 0.0;
        $totalFineGoldWeight = 0.0;
        $totalGrossValuation = 0.0;
        $totalMonetaryDeductions = 0.0;
        $totalFinalValue = 0.0;

        $validItemTypes = ['ring', 'chain', 'necklace', 'bracelet', 'earrings', 'bangles', 'coin', 'other'];

        foreach ($rawItems as $idx => $item) {
            $itemNum = $idx + 1;
            $itemType = in_array(strtolower($item['item_type'] ?? ''), $validItemTypes) ? strtolower($item['item_type']) : 'other';
            $description = trim($item['item_description'] ?? '');
            $purityKarat = (float)($item['purity_karat'] ?? 22.0);
            $purityLabel = trim($item['purity_label'] ?? ($purityKarat . 'K'));

            if ($purityKarat <= 0 || $purityKarat > 24) {
                throw new Exception("Item #{$itemNum}: Gold purity karat must be between 1K and 24K.");
            }

            $grossWeight = round((float)($item['gross_weight'] ?? 0), 3);
            if ($grossWeight <= 0) {
                throw new Exception("Item #{$itemNum}: Gross weight must be strictly greater than 0 grams.");
            }

            $stoneDeduction = round((float)($item['stone_deduction'] ?? 0), 3);
            $otherDeduction = round((float)($item['other_deduction'] ?? 0), 3);

            if ($stoneDeduction < 0 || $otherDeduction < 0) {
                throw new Exception("Item #{$itemNum}: Weight deductions cannot be negative.");
            }

            $itemWeightDeductions = round($stoneDeduction + $otherDeduction, 3);
            if ($itemWeightDeductions >= $grossWeight) {
                throw new Exception("Item #{$itemNum}: Total deductions ({$itemWeightDeductions}g) must be less than gross weight ({$grossWeight}g). Net weight cannot be zero or negative.");
            }

            // Net Gold Weight = Gross Weight − Total Weight Deductions
            $netWeight = round($grossWeight - $itemWeightDeductions, 3);

            // Fine Gold Equivalent Weight = Net Gold Weight × Purity Karat ÷ 24
            $fineGoldRaw = ($netWeight * $purityKarat) / 24;
            $fineGoldWeight = round($fineGoldRaw, 4);

            // Gross Gold Value = Fine Gold Equivalent Weight × Applicable 24K Gold Rate per Gram
            $grossValue = round($fineGoldRaw * $goldRate24k, 2);

            // Monetary Deductions (Melting / Refining charges)
            $meltingCharge = round((float)($item['melting_charge'] ?? 0), 2);
            if ($meltingCharge < 0) {
                throw new Exception("Item #{$itemNum}: Melting/refining charges cannot be negative.");
            }
            if ($meltingCharge >= $grossValue) {
                throw new Exception("Item #{$itemNum}: Valuation deductions (₹" . number_format($meltingCharge, 2) . ") cannot exceed gross gold value (₹" . number_format($grossValue, 2) . ").");
            }

            // Final Assessed Item Value = Gross Gold Value − Approved Monetary Deductions
            $assessedValue = round($grossValue - $meltingCharge, 2);
            $itemRemarks = trim($item['remarks'] ?? '');

            $calculatedItems[] = [
                'item_type' => $itemType,
                'item_description' => $description,
                'purity_karat' => $purityKarat,
                'purity_label' => $purityLabel,
                'gross_weight' => $grossWeight,
                'stone_deduction' => $stoneDeduction,
                'other_deduction' => $otherDeduction,
                'net_weight' => $netWeight,
                'fine_gold_weight' => $fineGoldWeight,
                'gold_rate' => $goldRate24k,
                'gross_value' => $grossValue,
                'melting_charge' => $meltingCharge,
                'assessed_value' => $assessedValue,
                'remarks' => $itemRemarks,
            ];

            $totalGrossWeight += $grossWeight;
            $totalWeightDeduction += $itemWeightDeductions;
            $totalNetWeight += $netWeight;
            $totalFineGoldWeight += $fineGoldWeight;
            $totalGrossValuation += $grossValue;
            $totalMonetaryDeductions += $meltingCharge;
            $totalFinalValue += $assessedValue;
        }

        $totalGrossWeight = round($totalGrossWeight, 3);
        $totalWeightDeduction = round($totalWeightDeduction, 3);
        $totalNetWeight = round($totalNetWeight, 3);
        $totalFineGoldWeight = round($totalFineGoldWeight, 3);
        $totalGrossValuation = round($totalGrossValuation, 2);
        $totalMonetaryDeductions = round($totalMonetaryDeductions, 2);
        $totalFinalValue = round($totalFinalValue, 2);

        // Generate unique exchange number: OGE-YYYY-XXXX
        $year = date('Y');
        $countStmt = $db->query("SELECT COUNT(*) FROM old_gold_exchanges WHERE exchange_no LIKE 'OGE-{$year}-%'");
        $nextSeq = (int)$countStmt->fetchColumn() + 1;
        $exchangeNo = sprintf("OGE-%s-%04d", $year, $nextSeq);

        // Ensure uniqueness
        $checkUnique = $db->prepare("SELECT id FROM old_gold_exchanges WHERE exchange_no = ?");
        $checkUnique->execute([$exchangeNo]);
        if ($checkUnique->fetch()) {
            $exchangeNo = sprintf("OGE-%s-%04d", $year, $nextSeq + rand(10, 99));
        }

        $createdBy = $currentUser['name'] ?? 'Super Admin';
        $approvedBy = ($status === 'Approved') ? $createdBy : null;
        $approvedAt = ($status === 'Approved') ? date('Y-m-d H:i:s') : null;

        // Insert Header
        $insertHeader = $db->prepare("
            INSERT INTO old_gold_exchanges (
                exchange_no, customer_id, customer_name, customer_mobile, exchange_date,
                rate_basis, gold_rate_24k, total_gross_weight, total_weight_deduction,
                total_net_weight, total_fine_gold_weight, gross_valuation, total_monetary_deductions,
                final_exchange_value, credit_balance, status, branch, created_by, approved_by, approved_at, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertHeader->execute([
            $exchangeNo, $customerId, $customerName, $customerMobile, $exchangeDate,
            $rateBasis, $goldRate24k, $totalGrossWeight, $totalWeightDeduction,
            $totalNetWeight, $totalFineGoldWeight, $totalGrossValuation, $totalMonetaryDeductions,
            $totalFinalValue, $totalFinalValue, $status, $branch, $createdBy, $approvedBy, $approvedAt, $remarks
        ]);
        $exchangeId = (int)$db->lastInsertId();

        // Insert Items
        $insertItem = $db->prepare("
            INSERT INTO old_gold_exchange_items (
                exchange_id, item_type, item_description, purity_karat, purity_label,
                gross_weight, stone_deduction, other_deduction, net_weight, fine_gold_weight,
                gold_rate, gross_value, melting_charge, assessed_value, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($calculatedItems as $it) {
            $insertItem->execute([
                $exchangeId,
                $it['item_type'],
                $it['item_description'],
                $it['purity_karat'],
                $it['purity_label'],
                $it['gross_weight'],
                $it['stone_deduction'],
                $it['other_deduction'],
                $it['net_weight'],
                $it['fine_gold_weight'],
                $it['gold_rate'],
                $it['gross_value'],
                $it['melting_charge'],
                $it['assessed_value'],
                $it['remarks']
            ]);
        }

        // Notification
        $statusNotice = ($status === 'Approved') ? 'Approved & Ready for POS' : 'Pending Manager Approval';
        $notifStmt = $db->prepare("INSERT INTO notifications (type, message) VALUES ('info', ?)");
        $notifStmt->execute(["Old Gold Exchange {$exchangeNo} created for {$customerName} ({$totalNetWeight}g Net) — ₹" . number_format($totalFinalValue, 2) . " [{$statusNotice}]"]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'exchange_id' => $exchangeId,
            'exchange_no' => $exchangeNo,
            'status' => $status,
            'total_gross_weight' => $totalGrossWeight,
            'total_net_weight' => $totalNetWeight,
            'total_fine_gold_weight' => $totalFineGoldWeight,
            'final_exchange_value' => $totalFinalValue,
            'redirect_url' => "old_gold_exchange_view.php?id={$exchangeId}"
        ]);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/**
 * 2. APPROVE AN EXCHANGE RECORD (Authorized Users)
 */
if ($action === 'approve') {
    if (!$isManagerOrAdmin) {
        echo json_encode(['success' => false, 'error' => 'Permission denied. Only Super Admin and Store Managers can approve gold exchanges.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $exchangeId = (int)($input['exchange_id'] ?? 0);

    try {
        $stmt = $db->prepare("SELECT * FROM old_gold_exchanges WHERE id = ?");
        $stmt->execute([$exchangeId]);
        $exchange = $stmt->fetch();

        if (!$exchange) {
            echo json_encode(['success' => false, 'error' => 'Exchange record not found.']);
            exit;
        }

        if ($exchange['status'] === 'Approved') {
            echo json_encode(['success' => true, 'message' => 'Already approved.']);
            exit;
        }

        if ($exchange['status'] === 'Redeemed' || $exchange['status'] === 'Cancelled') {
            echo json_encode(['success' => false, 'error' => "Cannot approve an exchange with status '{$exchange['status']}'."]);
            exit;
        }

        $approverName = $currentUser['name'] ?? 'Super Admin';
        $update = $db->prepare("UPDATE old_gold_exchanges SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $update->execute([$approverName, $exchangeId]);

        $notif = $db->prepare("INSERT INTO notifications (type, message) VALUES ('success', ?)");
        $notif->execute(["Old Gold Exchange {$exchange['exchange_no']} has been approved by {$approverName}."]);

        echo json_encode(['success' => true, 'message' => 'Exchange approved successfully.']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/**
 * 3. CANCEL AN EXCHANGE RECORD (Authorized Users)
 */
if ($action === 'cancel') {
    if (!$isManagerOrAdmin) {
        echo json_encode(['success' => false, 'error' => 'Permission denied. Only Super Admin and Store Managers can cancel gold exchanges.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $exchangeId = (int)($input['exchange_id'] ?? 0);
    $reason = trim($input['reason'] ?? 'Cancelled by management');

    try {
        $stmt = $db->prepare("SELECT * FROM old_gold_exchanges WHERE id = ?");
        $stmt->execute([$exchangeId]);
        $exchange = $stmt->fetch();

        if (!$exchange) {
            echo json_encode(['success' => false, 'error' => 'Exchange record not found.']);
            exit;
        }

        if ($exchange['status'] === 'Redeemed') {
            echo json_encode(['success' => false, 'error' => 'Cannot cancel an exchange that has already been redeemed against a sales invoice.']);
            exit;
        }

        $cancellerName = $currentUser['name'] ?? 'Super Admin';
        $update = $db->prepare("UPDATE old_gold_exchanges SET status = 'Cancelled', cancelled_by = ?, cancelled_at = NOW(), cancel_reason = ?, credit_balance = 0 WHERE id = ?");
        $update->execute([$cancellerName, $reason, $exchangeId]);

        $notif = $db->prepare("INSERT INTO notifications (type, message) VALUES ('alert', ?)");
        $notif->execute(["Old Gold Exchange {$exchange['exchange_no']} cancelled by {$cancellerName}. Reason: {$reason}"]);

        echo json_encode(['success' => true, 'message' => 'Exchange cancelled successfully.']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/**
 * 4. GET AVAILABLE CREDITS FOR POS BILLING
 */
if ($action === 'get_available') {
    $customerId = !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
    $customerMobile = !empty($_GET['mobile']) ? trim($_GET['mobile']) : null;
    $exchangeNo = !empty($_GET['exchange_no']) ? trim($_GET['exchange_no']) : null;

    try {
        $whereClauses = ["status = 'Approved'", "credit_balance > 0"];
        $params = [];

        if ($exchangeNo) {
            $whereClauses[] = "exchange_no = ?";
            $params[] = $exchangeNo;
        } elseif ($customerId) {
            $whereClauses[] = "(customer_id = ? OR customer_mobile = (SELECT mobile FROM customers WHERE id = ?))";
            $params[] = $customerId;
            $params[] = $customerId;
        } elseif ($customerMobile) {
            $whereClauses[] = "customer_mobile = ?";
            $params[] = $customerMobile;
        }

        $sql = "SELECT id, exchange_no, customer_id, customer_name, customer_mobile, exchange_date, total_net_weight, total_fine_gold_weight, final_exchange_value, credit_balance, status FROM old_gold_exchanges WHERE " . implode(' AND ', $whereClauses) . " ORDER BY id DESC LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        echo json_encode(['success' => true, 'exchanges' => $records]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/**
 * 5. SEARCH CUSTOMERS WITH STATS
 */
if ($action === 'search_customer') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'customers' => []]);
        exit;
    }

    try {
        $stmt = $db->prepare("
            SELECT c.id, c.name, c.mobile, c.city, c.loyalty_tier, c.loyalty_points,
                   COALESCE((SELECT SUM(credit_balance) FROM old_gold_exchanges WHERE customer_id = c.id AND status = 'Approved'), 0) as unredeemed_gold_credit
            FROM customers c
            WHERE c.name LIKE ? OR c.mobile LIKE ?
            ORDER BY c.name ASC
            LIMIT 10
        ");
        $term = "%{$q}%";
        $stmt->execute([$term, $term]);
        $results = $stmt->fetchAll();

        echo json_encode(['success' => true, 'customers' => $results]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
