<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$action = $_GET['action'] ?? '';
$db = getDBConnection();

// Switch Branch
if ($action === 'switch_branch') {
    $branch = $_POST['branch'] ?? 'Bandra West';
    switchBranch($branch);
    $referer = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';
    header('Location: ' . $referer);
    exit;
}

// Save Settings Form
if ($action === 'save_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole(['Super Admin', 'Store Manager']);
    
    $keys = [
        'store_name', 'address', 'gstin', 'phone', 'email', 'gst_rate',
        'rate_gold_24k', 'rate_gold_22k', 'rate_gold_18k', 'rate_silver', 'rate_platinum', 'rate_diamond_carat',
        'header_text', 'footer_text', 'paper_size'
    ];

    try {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        // Check if using SQLite (which uses INSERT OR REPLACE)
        $isSQLite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        if ($isSQLite) {
            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        }

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                if ($isSQLite) {
                    $stmt->execute([$key, $val]);
                } else {
                    $stmt->execute([$key, $val, $val]);
                }
            }
        }

        setFlash('success', 'Store settings and metal rates updated successfully!');
    } catch (Exception $e) {
        setFlash('error', 'Error saving settings: ' . $e->getMessage());
    }

    header('Location: ../settings.php');
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid action']);
