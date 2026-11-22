<?php
/**
 * Public Landing Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to appropriate role dashboard
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

// Fetch stats for landing page metrics
$db = Database::getInstance()->getConnection();
$studentCount = $db->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn();
$teacherCount = $db->query("SELECT COUNT(*) FROM teachers WHERE status = 'active'")->fetchColumn();
$classCount = $db->query("SELECT COUNT(*) FROM classes WHERE status = 'active'")->fetchColumn();
$recordCount = $db->query("SELECT COUNT(*) FROM attendance_records")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - Modern Attendance Tracking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #31104b 100%);
            color: #ffffff;
            padding: 5rem 1rem 4rem;
            position: relative;
            overflow: hidden;
        }
        .hero-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #e0e7ff;
            border-radius: 9999px;
            padding: 0.4rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .feature-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-lg);
            padding: 2rem;
            transition: all 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(15, 23, 42, 0.12);
            border-color: #cbd5e1;
        }
        .role-portal-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .role-portal-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px -8px rgba(79, 70, 229, 0.15);
            border-color: #818cf8;
        }
        .role-portal-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.25rem;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Public Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 sticky-top" style="background: #0f172a !important;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>">
                <div class="d-inline-flex p-2 rounded-3 text-white" style="background: var(--primary-gradient);">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <span><?= e(APP_NAME) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li class="nav-item">
                        <span class="text-white-50 small d-none d-lg-inline">
                            <i class="bi bi-geo-alt me-1"></i><?= e(INSTITUTION_NAME) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-light px-4 rounded-pill">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                    </li>
                    <?php if (ALLOW_REGISTRATION): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary px-4 rounded-pill">
                                <i class="bi bi-person-plus me-1"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container position-relative" style="z-index: 2;">
            <div class="hero-badge mb-4">
                <i class="bi bi-stars text-warning"></i> Next-Generation Attendance Tracking Platform
            </div>
            <h1 class="display-4 fw-extrabold mb-3 text-white" style="letter-spacing: -0.03em;">
                Effortless Attendance Management<br>
                <span style="background: linear-gradient(135deg, #a5b4fc 0%, #c084fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    For Schools & Universities
                </span>
            </h1>
            <p class="lead text-white-50 mx-auto mb-4" style="max-width: 680px;">
                Complete role-based platform for Administrators, Teachers, and Students. Take attendance in seconds, scan student QR codes, analyze trends, and export instant reports.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-bold">
                    <i class="bi bi-shield-lock-fill me-2"></i> Access Portal
                </a>
                <a href="#portals" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill">
                    <i class="bi bi-arrow-down me-1"></i> Explore Portals
                </a>
            </div>

            <!-- Metric Stats Bar -->
            <div class="row g-3 justify-content-center pt-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-4" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div class="fs-2 fw-bold text-white"><?= number_format($studentCount) ?></div>
                        <div class="text-white-50 small text-uppercase fw-semibold">Active Students</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-4" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div class="fs-2 fw-bold text-white"><?= number_format($teacherCount) ?></div>
                        <div class="text-white-50 small text-uppercase fw-semibold">Faculty Teachers</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-4" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div class="fs-2 fw-bold text-white"><?= number_format($classCount) ?></div>
                        <div class="text-white-50 small text-uppercase fw-semibold">Active Classes</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-4" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div class="fs-2 fw-bold text-white"><?= number_format($recordCount) ?></div>
                        <div class="text-white-50 small text-uppercase fw-semibold">Attendance Logs</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Portals Access -->
    <section class="py-5" id="portals">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h6 class="text-primary text-uppercase fw-bold letter-spacing-1">Role-Based Gateways</h6>
                <h2 class="fw-bold text-dark">Dedicated Portals For Every Stakeholder</h2>
                <p class="text-muted">Direct authentication and specialized modules tailored to each institution role.</p>
            </div>

            <div class="row g-4">
                <!-- Admin Card -->
                <div class="col-md-4">
                    <div class="role-portal-card h-100">
                        <div class="role-portal-icon" style="background: rgba(79, 70, 229, 0.12); color: #4f46e5;">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Administrator</h4>
                        <p class="text-muted small mb-4">Complete administrative oversight: student & faculty CRUD, course and class scheduling, activity monitoring, and global report exports.</p>
                        <a href="<?= BASE_URL ?>/login.php?role=admin" class="btn btn-outline-primary w-100 rounded-pill">
                            Admin Login <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Teacher Card -->
                <div class="col-md-4">
                    <div class="role-portal-card h-100">
                        <div class="role-portal-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                            <i class="bi bi-person-video3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Teacher & Instructor</h4>
                        <p class="text-muted small mb-4">Quick daily attendance recording, 1-click batch marking, QR code camera scanner, class roster filters, and real-time attendance stats.</p>
                        <a href="<?= BASE_URL ?>/login.php?role=teacher" class="btn btn-outline-success w-100 rounded-pill">
                            Teacher Login <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Student Card -->
                <div class="col-md-4">
                    <div class="role-portal-card h-100">
                        <div class="role-portal-icon" style="background: rgba(14, 165, 233, 0.12); color: #0ea5e9;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Student</h4>
                        <p class="text-muted small mb-4">Personal attendance tracking, real-time presence rate percentage gauge, digital student QR ID card, and printable official attendance summary.</p>
                        <a href="<?= BASE_URL ?>/login.php?role=student" class="btn btn-outline-info w-100 rounded-pill">
                            Student Login <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Features Section -->
    <section class="py-5 bg-white border-top border-bottom">
        <div class="container py-3">
            <div class="text-center mb-5">
                <h6 class="text-primary text-uppercase fw-bold letter-spacing-1">Why Choose AMS</h6>
                <h2 class="fw-bold text-dark">Enterprise-Grade Architecture & Design</h2>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="stat-icon primary mb-3">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h5 class="fw-bold text-dark">QR Code Attendance</h5>
                        <p class="text-muted small mb-0">Every student receives an encrypted digital QR badge. Instructors can instantly record presence in under a second using any camera.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="stat-icon success mb-3">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Multi-Format Exports</h5>
                        <p class="text-muted small mb-0">Generate comprehensive attendance reports filtered by course, date range, section, or student. Export to CSV, Excel, or print-ready PDF format.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="stat-icon warning mb-3">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Interactive Analytics</h5>
                        <p class="text-muted small mb-0">Integrated Chart.js visual analytics render 7-day attendance trends, status distribution ratios, and departmental rate summaries in real time.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted small bg-dark text-white-50" style="background: #0f172a !important;">
        <div class="container">
            <p class="mb-1 text-white">&copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong> &bull; <?= e(INSTITUTION_NAME) ?>. All rights reserved.</p>
            <p class="mb-0">Powered by Native PHP 8+, PDO MySQL & Bootstrap 5.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
