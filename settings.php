<?php
/**
 * Administrator - System Settings Module
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../controllers/UserController.php';

require_role(['admin']);

$userController = new UserController();
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userController->handleUpdateSettings();
}

$stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'System Settings';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">System Settings & Configurations</h3>
        <p class="text-secondary small mb-0">Control institution branding, academic calendar parameters, and security policies.</p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-sliders text-primary"></i> Global System Configuration
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/admin/settings.php" method="POST">
                    <?= csrf_field() ?>

                    <h6 class="fw-bold text-primary mb-3 text-uppercase small" style="letter-spacing: 0.05em;">
                        <i class="bi bi-building me-1"></i> Institutional Branding
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Application Name *</label>
                            <input type="text" name="system_name" class="form-control" value="<?= e($currentSettings['system_name'] ?? 'Attendance Management System') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Institution / University Name *</label>
                            <input type="text" name="institution_name" class="form-control" value="<?= e($currentSettings['institution_name'] ?? 'Apex Institute of Technology') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Campus Address</label>
                        <input type="text" name="institution_address" class="form-control" value="<?= e($currentSettings['institution_address'] ?? '') ?>">
                    </div>

                    <h6 class="fw-bold text-primary mb-3 mt-4 text-uppercase small" style="letter-spacing: 0.05em;">
                        <i class="bi bi-calendar3 me-1"></i> Academic Term & Timetable
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Active Academic Year *</label>
                            <input type="text" name="academic_year" class="form-control" value="<?= e($currentSettings['academic_year'] ?? '2025-2026') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Active Semester *</label>
                            <select name="current_semester" class="form-select" required>
                                <option value="1st Semester" <?= ($currentSettings['current_semester'] ?? '') === '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                                <option value="2nd Semester" <?= ($currentSettings['current_semester'] ?? '') === '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                                <option value="Summer Term" <?= ($currentSettings['current_semester'] ?? '') === 'Summer Term' ? 'selected' : '' ?>>Summer Term</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Late Threshold Grace Period (Minutes)</label>
                        <input type="number" name="late_threshold_minutes" class="form-control" value="<?= e($currentSettings['late_threshold_minutes'] ?? '15') ?>" min="1" max="60">
                        <small class="text-muted">Grace period in minutes before student arrival is flagged as LATE.</small>
                    </div>

                    <h6 class="fw-bold text-primary mb-3 mt-4 text-uppercase small" style="letter-spacing: 0.05em;">
                        <i class="bi bi-shield-check me-1"></i> Security & Access Policies
                    </h6>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="allow_registration" id="allowRegSwitch" <?= ($currentSettings['allow_registration'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-dark small" for="allowRegSwitch">
                                Enable Public Student Self-Registration
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">When turned on, students can register directly from the public landing or login page.</small>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Help Desk Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" value="<?= e($currentSettings['contact_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Help Desk Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control" value="<?= e($currentSettings['contact_phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../views/layouts/footer.php';
?>
