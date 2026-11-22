<?php
/**
 * Role-Aware Master Sidebar
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';

$role = current_role();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="app-sidebar" id="app-sidebar">
    <a href="<?= BASE_URL ?>" class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="bi bi-calendar2-check-fill"></i>
        </div>
        <div class="sidebar-brand-text">
            <span>AMS Core</span>
            <small class="d-block text-white-50" style="font-size: 0.65rem; font-weight: 500;">Attendance System</small>
        </div>
    </a>

    <div class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <div class="sidebar-heading">Core</div>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-item-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Admin Dashboard</span>
            </a>

            <div class="sidebar-heading">Academic Structure</div>
            <a href="<?= BASE_URL ?>/admin/courses.php" class="nav-item-link <?= $currentScript === 'courses.php' ? 'active' : '' ?>">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Courses</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/sections.php" class="nav-item-link <?= $currentScript === 'sections.php' ? 'active' : '' ?>">
                <i class="bi bi-diagram-3-fill"></i>
                <span>Sections</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/subjects.php" class="nav-item-link <?= $currentScript === 'subjects.php' ? 'active' : '' ?>">
                <i class="bi bi-book-half"></i>
                <span>Subjects</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/classes.php" class="nav-item-link <?= $currentScript === 'classes.php' ? 'active' : '' ?>">
                <i class="bi bi-building"></i>
                <span>Classes</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/schedules.php" class="nav-item-link <?= $currentScript === 'schedules.php' ? 'active' : '' ?>">
                <i class="bi bi-clock-fill"></i>
                <span>Schedules</span>
            </a>

            <div class="sidebar-heading">Users Management</div>
            <a href="<?= BASE_URL ?>/admin/students.php" class="nav-item-link <?= $currentScript === 'students.php' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/teachers.php" class="nav-item-link <?= $currentScript === 'teachers.php' ? 'active' : '' ?>">
                <i class="bi bi-person-badge-fill"></i>
                <span>Teachers</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="nav-item-link <?= $currentScript === 'users.php' ? 'active' : '' ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>User Accounts</span>
            </a>

            <div class="sidebar-heading">Records & Analytics</div>
            <a href="<?= BASE_URL ?>/admin/attendance.php" class="nav-item-link <?= $currentScript === 'attendance.php' ? 'active' : '' ?>">
                <i class="bi bi-check-all"></i>
                <span>All Attendance</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="nav-item-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                <span>Attendance Reports</span>
            </a>

            <div class="sidebar-heading">System</div>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="nav-item-link <?= $currentScript === 'settings.php' ? 'active' : '' ?>">
                <i class="bi bi-gear-fill"></i>
                <span>System Settings</span>
            </a>

        <?php elseif ($role === 'teacher'): ?>
            <div class="sidebar-heading">Overview</div>
            <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="nav-item-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Teacher Dashboard</span>
            </a>

            <div class="sidebar-heading">Attendance Module</div>
            <a href="<?= BASE_URL ?>/teacher/take_attendance.php" class="nav-item-link <?= $currentScript === 'take_attendance.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar2-plus-fill"></i>
                <span>Take Attendance</span>
            </a>
            <a href="<?= BASE_URL ?>/teacher/attendance_history.php" class="nav-item-link <?= $currentScript === 'attendance_history.php' ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>Attendance History</span>
            </a>
            <a href="<?= BASE_URL ?>/teacher/reports.php" class="nav-item-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                <span>Class Reports</span>
            </a>

            <div class="sidebar-heading">Academic Management</div>
            <a href="<?= BASE_URL ?>/teacher/my_classes.php" class="nav-item-link <?= $currentScript === 'my_classes.php' ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>My Classes</span>
            </a>
            <a href="<?= BASE_URL ?>/teacher/students.php" class="nav-item-link <?= $currentScript === 'students.php' ? 'active' : '' ?>">
                <i class="bi bi-person-lines-fill"></i>
                <span>Enrolled Students</span>
            </a>

        <?php elseif ($role === 'student'): ?>
            <div class="sidebar-heading">Overview</div>
            <a href="<?= BASE_URL ?>/student/dashboard.php" class="nav-item-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Student Dashboard</span>
            </a>

            <div class="sidebar-heading">My Attendance</div>
            <a href="<?= BASE_URL ?>/student/my_attendance.php" class="nav-item-link <?= $currentScript === 'my_attendance.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check-fill"></i>
                <span>My Attendance</span>
            </a>
            <a href="<?= BASE_URL ?>/student/attendance_history.php" class="nav-item-link <?= $currentScript === 'attendance_history.php' ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>Attendance History</span>
            </a>
            <a href="<?= BASE_URL ?>/student/reports.php" class="nav-item-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span>Printable Reports</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-heading">My Account</div>
        <a href="<?= BASE_URL ?>/profile.php" class="nav-item-link <?= $currentScript === 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profile & Security</span>
        </a>
        <a href="<?= BASE_URL ?>/logout.php" class="nav-item-link text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Sign Out</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between text-white-50 small">
            <div>
                <i class="bi bi-shield-check text-success me-1"></i>
                <span><?= ucfirst($role ?? 'Guest') ?> Mode</span>
            </div>
            <span class="badge bg-secondary-subtle text-light px-2"><?= ACADEMIC_YEAR ?></span>
        </div>
    </div>
</aside>
