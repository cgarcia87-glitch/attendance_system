<?php
/**
 * Login Authentication Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Redirect if already logged in
if (is_logged_in()) {
    switch (current_role()) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        case 'teacher':
            header('Location: ' . BASE_URL . '/teacher/dashboard.php');
            exit;
        case 'student':
            header('Location: ' . BASE_URL . '/student/dashboard.php');
            exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $authController->handleLogin();
}

$pageRoleHint = $_GET['role'] ?? '';
$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">

    <?php if ($flash): ?>
        <div id="flash-message" data-type="<?= e($flash['type']) ?>" data-message="<?= e($flash['message']) ?>" style="display:none;"></div>
    <?php endif; ?>

    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-brand-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h3 class="fw-bold mb-1 text-white"><?= e(APP_NAME) ?></h3>
            <p class="text-white-50 small mb-0"><?= e(INSTITUTION_NAME) ?></p>
        </div>

        <div class="auth-body">
            <!-- Demo Quick Fill Buttons -->
            <div class="mb-4 p-3 bg-light rounded-3 border">
                <div class="small fw-bold text-secondary mb-2 text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Demo Login
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="fillCredentials('admin', 'admin123')">
                        <i class="bi bi-shield-fill me-1"></i> Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success flex-fill" onclick="fillCredentials('teacher', 'teacher123')">
                        <i class="bi bi-person-video3 me-1"></i> Teacher
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info flex-fill" onclick="fillCredentials('student', 'student123')">
                        <i class="bi bi-mortarboard-fill me-1"></i> Student
                    </button>
                </div>
            </div>

            <!-- Login Form -->
            <form action="<?= BASE_URL ?>/login.php" method="POST" id="loginForm">
                <?= csrf_field() ?>

                <!-- Username or Email -->
                <div class="mb-3">
                    <label for="login" class="form-label small fw-bold text-secondary">Username or Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="login" name="login" required placeholder="e.g. admin or admin@attendance.com" autofocus>
                    </div>
                </div>

                <!-- Password with Eye Icon Toggle -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label small fw-bold text-secondary mb-0">Password</label>
                    </div>
                    <div class="password-group">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" required placeholder="Enter your password">
                            <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="Show password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" checked>
                        <label class="form-check-label small text-muted" for="remember">Remember me</label>
                    </div>
                    <span class="small text-muted">Protected with PDO & CSRF</span>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold fs-6">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Portal
                </button>
            </form>

            <?php if (ALLOW_REGISTRATION): ?>
                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        Are you a student without an account?
                        <a href="<?= BASE_URL ?>/register.php" class="text-primary fw-bold text-decoration-none">Register here</a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>/index.php" class="text-secondary small text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Back to Homepage
                </a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
    <script>
        function fillCredentials(user, pass) {
            document.getElementById('login').value = user;
            document.getElementById('password').value = pass;
        }

        // Auto-fill if query parameter specifies role
        const urlParams = new URLSearchParams(window.location.search);
        const role = urlParams.get('role');
        if (role === 'admin') fillCredentials('admin', 'admin123');
        else if (role === 'teacher') fillCredentials('teacher', 'teacher123');
        else if (role === 'student') fillCredentials('student', 'student123');
    </script>
</body>
</html>
