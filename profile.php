<?php
/**
 * Unified User Profile Management Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Student.php';
require_once __DIR__ . '/models/Teacher.php';

require_login();

$user = current_user();
$db = Database::getInstance()->getConnection();
$userModel = new User();
$userData = $userModel->findById($user['id']);

$extraData = [];
if ($user['role'] === 'student' && !empty($user['ref_id'])) {
    $studentModel = new Student();
    $extraData = $studentModel->getById($user['ref_id']);
} elseif ($user['role'] === 'teacher' && !empty($user['ref_id'])) {
    $teacherModel = new Teacher();
    $extraData = $teacherModel->getById($user['ref_id']);
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    if (isset($_POST['update_profile'])) {
        $authController->handleProfileUpdate();
    } elseif (isset($_POST['change_password'])) {
        $authController->handlePasswordChange();
    }
}

$pageTitle = 'My Profile & Security';
require_once __DIR__ . '/views/layouts/header.php';
require_once __DIR__ . '/views/layouts/sidebar.php';
require_once __DIR__ . '/views/layouts/navbar.php';
?>

<div class="row g-4">
    <!-- Left Profile Card -->
    <div class="col-lg-4">
        <div class="card-modern text-center p-4">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <?php if (!empty($userData['avatar']) && file_exists(ROOT_PATH . '/uploads/avatars/' . $userData['avatar'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/avatars/<?= e($userData['avatar']) ?>" alt="Avatar" class="rounded-circle border border-3 border-primary shadow" style="width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle border border-3 border-primary shadow mx-auto d-flex align-items-center justify-content-center text-white fw-bold fs-1" style="width: 120px; height: 120px; background: var(--primary-gradient);">
                        <?= strtoupper(substr($userData['full_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2 border border-2 border-white">
                    <i class="bi bi-check"></i>
                </span>
            </div>

            <h4 class="fw-bold text-dark mb-1"><?= e($userData['full_name']) ?></h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 text-uppercase fw-bold mb-3">
                <?= e($userData['role']) ?>
            </span>
            <p class="text-muted small mb-3"><?= e($userData['email']) ?></p>

            <ul class="list-group list-group-flush text-start small border-top pt-3">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span class="text-muted">Username</span>
                    <strong class="text-dark">@<?= e($userData['username']) ?></strong>
                </li>
                <?php if ($user['role'] === 'student' && !empty($extraData)): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Student ID</span>
                        <strong class="text-primary"><?= e($extraData['student_id'] ?? 'N/A') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Course</span>
                        <strong class="text-dark"><?= e($extraData['course_code'] ?? 'N/A') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Section</span>
                        <strong class="text-dark"><?= e($extraData['section_name'] ?? 'N/A') ?></strong>
                    </li>
                <?php elseif ($user['role'] === 'teacher' && !empty($extraData)): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Teacher ID</span>
                        <strong class="text-primary"><?= e($extraData['teacher_id'] ?? 'N/A') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Department</span>
                        <strong class="text-dark"><?= e($extraData['department'] ?? 'N/A') ?></strong>
                    </li>
                <?php endif; ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span class="text-muted">Member Since</span>
                    <strong class="text-dark"><?= format_date($userData['created_at']) ?></strong>
                </li>
            </ul>
        </div>
    </div>

    <!-- Right Forms -->
    <div class="col-lg-8">
        <!-- Edit Profile Details -->
        <div class="card-modern mb-4">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-person-gear text-primary"></i> Edit Profile Information
                </h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/profile.php" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_profile" value="1">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label small fw-bold text-secondary">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($userData['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-bold text-secondary">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($userData['email']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="contact_number" class="form-label small fw-bold text-secondary">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?= e($extraData['contact_number'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="col-md-6">
                            <label for="avatar" class="form-label small fw-bold text-secondary">Profile Avatar (JPG, PNG, WebP)</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>

                    <?php if ($user['role'] === 'student'): ?>
                        <div class="mb-3">
                            <label for="address" class="form-label small fw-bold text-secondary">Residential Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?= e($extraData['address'] ?? '') ?></textarea>
                        </div>
                    <?php endif; ?>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card-modern">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-shield-lock text-warning"></i> Change Password
                </h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/profile.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="change_password" value="1">

                    <div class="mb-3">
                        <label for="current_password" class="form-label small fw-bold text-secondary">Current Password</label>
                        <div class="password-group">
                            <div class="input-group">
                                <input type="password" class="form-control border-end-0" id="current_password" name="current_password" required placeholder="Enter current password">
                                <button type="button" class="password-toggle-btn" title="Show password" aria-label="Toggle password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label small fw-bold text-secondary">New Password</label>
                            <div class="password-group">
                                <div class="input-group">
                                    <input type="password" class="form-control border-end-0" id="new_password" name="new_password" required minlength="6" placeholder="Min. 6 characters">
                                    <button type="button" class="password-toggle-btn" title="Show password" aria-label="Toggle password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label small fw-bold text-secondary">Confirm New Password</label>
                            <div class="password-group">
                                <div class="input-group">
                                    <input type="password" class="form-control border-end-0" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Repeat new password">
                                    <button type="button" class="password-toggle-btn" title="Show password" aria-label="Toggle password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning fw-bold text-dark">
                            <i class="bi bi-key-fill me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/views/layouts/footer.php';
?>
