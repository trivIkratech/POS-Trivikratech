<?php
/**
 * Trivikratech Jewellery POS - Helper Utilities
 */

require_once __DIR__ . '/db.php';

function formatINR($amount) {
    $amount = (float)$amount;
    if ($amount >= 10000000) {
        return '₹' . number_format($amount / 10000000, 2) . 'Cr';
    }
    if ($amount >= 100000) {
        return '₹' . number_format($amount / 100000, 2) . 'L';
    }
    if ($amount >= 1000) {
        return '₹' . number_format($amount / 1000, 1) . 'K';
    }
    return '₹' . number_format($amount, 0);
}

function formatFullINR($amount) {
    return '₹' . number_format((float)$amount, 2, '.', ',');
}

function getSettings() {
    static $settingsCache = null;
    if ($settingsCache !== null) {
        return $settingsCache;
    }
    try {
        $db = getDBConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return $settingsCache;
    } catch (Exception $e) {
        return [];
    }
}

function getSetting($key, $default = '') {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getTierBadgeClass($tier) {
    switch ($tier) {
        case 'Diamond': return 'badge-diamond';
        case 'Platinum': return 'badge-platinum';
        case 'Gold': return 'badge-gold';
        case 'Silver': return 'badge-silver';
        case 'Bronze': default: return 'badge-bronze';
    }
}

function getRoleBadgeInfo($role) {
    switch ($role) {
        case 'Store Manager':
            return ['bg' => '#F0FDFA', 'border' => '#CCFBF1', 'color' => '#0F766E', 'label' => '🏢 Store Manager'];
        case 'Employee':
            return ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'color' => '#1D4ED8', 'label' => '👤 Employee'];
        case 'Jewellery Repairing Assistant':
            return ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'color' => '#B45309', 'label' => '💍 🔧 Jewellery Repairing Assistant'];
        case 'Super Admin':
        default:
            return ['bg' => '#FAF5FF', 'border' => '#E9D5FF', 'color' => '#7E22CE', 'label' => '👑 Super Admin'];
    }
}

function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}
