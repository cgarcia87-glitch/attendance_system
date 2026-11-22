<?php
/**
 * Student Self-Registration Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/models/Class.php';
require_once __DIR__ . '/controllers/AuthController.php';

if (!ALLOW_REGISTRATION) {
    flash('error', 'Student self-registration is currently closed by the administrator.');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $authController->handleRegister();
}

$classModel = new ClassModel();
$courses = $classModel->getCourses();
$yearLevels = $classModel->getYearLevels();
$sections = $classModel->getSections();

$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page py-5">

    <?php if ($flash): ?>
        <div id="flash-message" data-type="<?= e($flash['type']) ?>" data-message="<?= e($flash['message']) ?>" style="display:none;"></div>
    <?php endif; ?>

    <div class="auth-card" style="max-width: 650px;">
        <div class="auth-header">
            <div class="auth-brand-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h3 class="fw-bold mb-1 text-white">Student Registration</h3>
            <p class="text-white-50 small mb-0"><?= e(INSTITUTION_NAME) ?></p>
        </div>

        <div class="auth-body">
            <form action="<?= BASE_URL ?>/register.php" method="POST" id="registerForm">
                <?= csrf_field() ?>

                <h6 class="fw-bold text-primary mb-3 text-uppercase small" style="letter-spacing: 0.05em;">
                    <i class="bi bi-person-badge me-1"></i> Personal Information
                </h6>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label small fw-bold text-secondary">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required placeholder="e.g. John">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label small fw-bold text-secondary">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required placeholder="e.g. Doe">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="student_id" class="form-label small fw-bold text-secondary">Student ID Number *</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" required placeholder="e.g. STD-2024-099">
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label small fw-bold text-secondary">Gender</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3 mt-4 text-uppercase small" style="letter-spacing: 0.05em;">
                    <i class="bi bi-diagram-3 me-1"></i> Academic Enrollment
                </h6>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="course_id" class="form-label small fw-bold text-secondary">Course / Program *</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['course_code']) ?> - <?= e($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="year_level_id" class="form-label small fw-bold text-secondary">Year Level *</label>
                        <select class="form-select" id="year_level_id" name="year_level_id" required>
                            <option value="">Select Year</option>
                            <?php foreach ($yearLevels as $y): ?>
                                <option value="<?= $y['id'] ?>"><?= e($y['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="section_id" class="form-label small fw-bold text-secondary">Section *</label>
                        <select class="form-select" id="section_id" name="section_id" required>
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['section_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3 mt-4 text-uppercase small" style="letter-spacing: 0.05em;">
                    <i class="bi bi-shield-lock me-1"></i> Account Credentials
                </h6>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label small fw-bold text-secondary">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required placeholder="Choose a username">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-bold text-secondary">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="name@domain.com">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="reg_password" class="form-label small fw-bold text-secondary">Password *</label>
                        <div class="password-group">
                            <div class="input-group">
                                <input type="password" class="form-control border-end-0" id="reg_password" name="password" required minlength="6" placeholder="Min. 6 characters">
                                <button type="button" class="password-toggle-btn" title="Show password" aria-label="Toggle password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label small fw-bold text-secondary">Confirm Password *</label>
                        <div class="password-group">
                            <div class="input-group">
                                <input type="password" class="form-control border-end-0" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Repeat password">
                                <button type="button" class="password-toggle-btn" title="Show password" aria-label="Toggle password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="contact_number" class="form-label small fw-bold text-secondary">Contact Number (Optional)</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="+1 (555) 000-0000">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold fs-6">
                    <i class="bi bi-check-circle me-1"></i> Complete Registration
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <p class="text-muted small mb-0">
                    Already have an account?
                    <a href="<?= BASE_URL ?>/login.php" class="text-primary fw-bold text-decoration-none">Sign In here</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
