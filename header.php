<?php
/**
 * Master Header Layout
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
    
    <!-- Custom Application CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<?php
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isDashboard = (isset($isDashboard) && $isDashboard) || ($currentScript === 'dashboard.php');
$bodyClasses = ['theme-gold-black'];
if ($isDashboard) {
    $bodyClasses[] = 'page-dashboard';
}
if (isset($extraBodyClass)) {
    $bodyClasses[] = $extraBodyClass;
}
?>
<body class="<?= implode(' ', $bodyClasses) ?>">
<?php
// Render flash notification element if present
$flash = flash();
if ($flash): ?>
    <div id="flash-message" data-type="<?= e($flash['type']) ?>" data-message="<?= e($flash['message']) ?>" style="display:none;"></div>
<?php endif; ?>

<div class="app-wrapper">
