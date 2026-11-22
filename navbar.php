<?php
/**
 * Master Top Navigation Bar - Gold & Black Theme
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = current_user();
$db = Database::getInstance()->getConnection();

// Fetch unread notifications
$notifications = [];
if (!empty($user['id'])) {
    $stmtN = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmtN->execute([$user['id']]);
    $notifications = $stmtN->fetchAll();
}
?>
<div class="app-main">
    <header class="app-topbar">
        <div class="topbar-left">
            <button class="topbar-toggle" id="sidebar-toggle" aria-label="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>
            <div class="d-none d-md-flex align-items-center gap-2">
                <span class="fw-bold fs-5 text-white"><?= e($pageTitle ?? 'Dashboard') ?></span>
                <span style="color: var(--gold);">|</span>
                <span class="small fw-semibold" style="color: var(--text-gold-light);"><?= e(INSTITUTION_NAME) ?></span>
            </div>
        </div>

        <div class="topbar-right">
            <!-- Academic Term Badge -->
            <div class="d-none d-lg-flex align-items-center rounded-pill px-3 py-1 gap-2 border" style="background: var(--bg-card); border-color: var(--gold-border) !important;">
                <i class="bi bi-mortarboard" style="color: var(--gold);"></i>
                <span class="small fw-semibold text-white"><?= e(ACADEMIC_YEAR) ?> &bull; <?= e(CURRENT_SEMESTER) ?></span>
            </div>

            <!-- Notifications Dropdown -->
            <div class="dropdown">
                <button class="btn rounded-circle position-relative p-2 border" style="background: var(--bg-card); border-color: var(--gold-border) !important;" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5" style="color: var(--text-gold-light);"></i>
                    <?php if (count($notifications) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill" style="font-size: 0.65rem; background: var(--gold); color: #000000; font-weight: 800;">
                            <?= count($notifications) ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg py-0 border" style="width: 320px; border-radius: 12px; overflow: hidden; background: var(--bg-card); border-color: var(--gold-dark) !important;">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between" style="background: #141414; border-color: var(--gold-border) !important;">
                        <span class="fw-bold text-white">Notifications</span>
                        <span class="badge" style="background: rgba(212, 175, 55, 0.2); color: var(--text-gold-light); border: 1px solid rgba(212, 175, 55, 0.4);"><?= count($notifications) ?> unread</span>
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto;">
                        <?php if (empty($notifications)): ?>
                            <div class="p-3 text-center text-muted small">No notifications at this time.</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="list-group-item list-group-item-action p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <strong class="small" style="color: var(--text-gold-light);"><?= e($n['title']) ?></strong>
                                        <small class="text-muted" style="font-size: 0.7rem;"><?= format_date($n['created_at'], 'M d') ?></small>
                                    </div>
                                    <p class="mb-0 text-white small" style="line-height: 1.3; opacity: 0.9;"><?= e($n['message']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- User Menu Dropdown -->
            <div class="dropdown">
                <a href="#" class="user-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php if (!empty($user['avatar']) && file_exists(ROOT_PATH . '/uploads/avatars/' . $user['avatar'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/avatars/<?= e($user['avatar']) ?>" alt="Avatar" class="user-avatar-img">
                    <?php else: ?>
                        <div class="user-avatar-placeholder">
                            <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="d-none d-sm-block text-start lh-1 pe-2">
                        <div class="fw-bold small text-white"><?= e($user['full_name'] ?? 'User') ?></div>
                        <small class="text-capitalize" style="font-size: 0.72rem; color: var(--text-gold-light);"><?= e($user['role'] ?? 'Guest') ?></small>
                    </div>
                    <i class="bi bi-chevron-down small" style="color: var(--gold);"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg py-2 mt-2 border" style="border-radius: 12px; min-width: 200px; background: var(--bg-card); border-color: var(--gold-dark) !important;">
                    <li class="px-3 py-2 border-bottom" style="border-color: var(--gold-border) !important;">
                        <span class="d-block small" style="color: var(--text-muted);">Signed in as</span>
                        <strong class="small text-white"><?= e($user['email'] ?? '') ?></strong>
                    </li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/profile.php"><i class="bi bi-person me-2" style="color: var(--gold);"></i>My Profile</a></li>
                    <?php if (current_role() === 'admin'): ?>
                        <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/settings.php"><i class="bi bi-gear me-2" style="color: var(--text-gold-light);"></i>System Settings</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="app-content">
