<?php
/**
 * 403 Unauthorized Access Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$user = current_user();
$dashboardUrl = BASE_URL . '/login.php';
if ($user) {
    switch ($user['role']) {
        case 'admin': $dashboardUrl = BASE_URL . '/admin/dashboard.php'; break;
        case 'teacher': $dashboardUrl = BASE_URL . '/teacher/dashboard.php'; break;
        case 'student': $dashboardUrl = BASE_URL . '/student/dashboard.php'; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page text-center">

    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-header" style="background: linear-gradient(135deg, #1e1b4b 0%, #450a0a 100%);">
            <div class="auth-brand-icon" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 2px solid #ef4444;">
                <i class="bi bi-shield-x"></i>
            </div>
            <h2 class="fw-bold mb-1 text-white">403 Access Denied</h2>
            <p class="text-white-50 small mb-0">Role-Based Access Control Violation</p>
        </div>

        <div class="auth-body p-4">
            <p class="text-secondary mb-4">
                You do not have the necessary security privileges to access this requested resource.
                <?php if ($user): ?>
                    Your current role is <strong><?= ucfirst($user['role']) ?></strong>.
                <?php endif; ?>
            </p>

            <div class="d-flex flex-column gap-2">
                <a href="<?= $dashboardUrl ?>" class="btn btn-primary py-2 rounded-pill fw-bold">
                    <i class="bi bi-arrow-left me-2"></i> Return to Authorized Dashboard
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-secondary py-2 rounded-pill">
                    <i class="bi bi-box-arrow-right me-2"></i> Sign In With Another Account
                </a>
            </div>
        </div>
    </div>

</body>
</html>
