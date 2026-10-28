<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$currentUser = getCurrentUser();
$settings = getSettings();
$currentScript = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Trivikratech POS - Luxury Jewellery POS') ?></title>
  
  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Lucide Icons via unpkg -->
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <!-- Chart.js for Dashboards & Analytics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- App Stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-container">
