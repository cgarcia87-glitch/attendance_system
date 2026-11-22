<?php
/**
 * Teacher Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Class.php';

require_role(['teacher', 'admin']);

$user = current_user();
$teacherModel = new Teacher();
$attendanceModel = new Attendance();
$classModel = new ClassModel();

// Resolve Teacher Record
$teacher = $teacherModel->getByUserId($user['id']);
$teacherId = $teacher['id'] ?? 0;

$stats = $attendanceModel->getTeacherStats($teacherId);
$assignedClasses = $teacherModel->getAssignedClasses($teacherId);
$dailyTrends = $attendanceModel->getDailyTrends(7, $teacherId);
$statusBreakdown = $attendanceModel->getAttendanceByStatus($teacherId);
$recentRecords = $attendanceModel->getRecentRecords(6, $teacherId);

$pageTitle = 'Faculty Teacher Dashboard';
$isDashboard = true;
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<!-- Welcome Banner -->
<div class="card-modern p-4 mb-4 dashboard-welcome-banner">
    <div class="row align-items-center">
        <div class="col-md-8">
            <span class="badge px-3 py-1 rounded-pill mb-2 border" style="background: rgba(212, 175, 55, 0.2); color: var(--text-gold-light); border-color: rgba(212, 175, 55, 0.4) !important;">
                <i class="bi bi-person-video3 me-1"></i> Faculty Instructor Portal
            </span>
            <h3 class="fw-bold text-white mb-1">Welcome back, Prof. <?= e($teacher['first_name'] ?? $user['full_name']) ?>!</h3>
            <p class="mb-0" style="color: var(--text-gold-light);">Department of <?= e($teacher['department'] ?? 'Information Technology') ?> &bull; Active Academic Term <?= ACADEMIC_YEAR ?></p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?= BASE_URL ?>/teacher/take_attendance.php" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                <i class="bi bi-calendar2-plus-fill me-1"></i> Take Attendance Now
            </a>
        </div>
    </div>
</div>

<!-- 5 Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Assigned Classes -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title">Assigned Classes</div>
                    <div class="stat-value"><?= number_format($stats['assigned_classes']) ?></div>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/teacher/my_classes.php" class="text-decoration-none">View classes &rarr;</a></small>
                </div>
                <div class="stat-icon primary">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Students -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title">Total Enrolled</div>
                    <div class="stat-value"><?= number_format($stats['total_students']) ?></div>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/teacher/students.php" class="text-decoration-none">View rosters &rarr;</a></small>
                </div>
                <div class="stat-icon purple">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Present Today -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title text-success">Present Today</div>
                    <div class="stat-value text-success"><?= number_format($stats['present_today']) ?></div>
                    <small class="text-success"><i class="bi bi-check-circle me-1"></i>Logged present</small>
                </div>
                <div class="stat-icon success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Absent Today -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title text-danger">Absent Today</div>
                    <div class="stat-value text-danger"><?= number_format($stats['absent_today']) ?></div>
                    <small class="text-danger"><i class="bi bi-x-circle me-1"></i>Unexcused</small>
                </div>
                <div class="stat-icon danger">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Late Today -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title text-warning">Late Today</div>
                    <div class="stat-value text-warning"><?= number_format($stats['late_today']) ?></div>
                    <small class="text-warning"><i class="bi bi-clock-history me-1"></i>Tardy</small>
                </div>
                <div class="stat-icon warning">
                    <i class="bi bi-clock-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Classes Quick Launch -->
<div class="card-modern mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-calendar-check text-primary"></i> My Teaching Classes & Quick Attendance
        </h5>
        <a href="<?= BASE_URL ?>/teacher/take_attendance.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Session
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Class Code</th>
                        <th>Subject</th>
                        <th>Section & Degree</th>
                        <th>Classroom</th>
                        <th>Enrolled Students</th>
                        <th class="text-end">Quick Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignedClasses)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No classes currently assigned.</td></tr>
                    <?php else: ?>
                        <?php foreach ($assignedClasses as $ac): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                        <?= e($ac['class_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($ac['subject_name']) ?></div>
                                    <small class="text-muted"><?= e($ac['subject_code']) ?> (<?= (int)$ac['units'] ?> units)</small>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary"><?= e($ac['section_name']) ?></span>
                                    <div class="small text-muted"><?= e($ac['course_code']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-door-open me-1"></i><?= e($ac['room']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <?= (int)$ac['enrolled_count'] ?> Students
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/teacher/students.php?class_id=<?= $ac['id'] ?>" class="btn btn-outline-secondary" title="View Class Students Attendance">
                                            <i class="bi bi-person-lines-fill me-1"></i> Students
                                        </a>
                                        <a href="<?= BASE_URL ?>/teacher/take_attendance.php?class_id=<?= $ac['id'] ?>" class="btn btn-primary" title="Record Attendance Session">
                                            <i class="bi bi-check2-circle me-1"></i> Take Attendance
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card-modern h-100 mb-0">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-bar-chart-fill text-primary"></i> 7-Day Class Attendance Trends
                </h5>
            </div>
            <div class="card-body">
                <canvas id="teacherTrendChart" style="min-height: 260px; max-height: 280px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-modern h-100 mb-0">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-pie-chart-fill text-purple"></i> Status Ratios
                </h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <canvas id="teacherDoughnutChart" style="min-height: 230px; max-height: 240px;"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
$tDates = [];
$tPresent = [];
$tAbsent = [];
$tLate = [];
foreach ($dailyTrends as $d) {
    $tDates[] = date('M d', strtotime($d['session_date']));
    $tPresent[] = (int)$d['present_count'];
    $tAbsent[] = (int)$d['absent_count'];
    $tLate[] = (int)$d['late_count'];
}

$dLabels = [];
$dCounts = [];
$dColors = [];
$mapColors = ['PRESENT' => '#D4AF37', 'ABSENT' => '#ef4444', 'LATE' => '#B8860B', 'EXCUSED' => '#F5D76E'];
foreach ($statusBreakdown as $sb) {
    $dLabels[] = ucfirst(strtolower($sb['status']));
    $dCounts[] = (int)$sb['count'];
    $dColors[] = $mapColors[$sb['status']] ?? '#B8860B';
}

$jsonDates = json_encode($tDates);
$jsonPresent = json_encode($tPresent);
$jsonAbsent = json_encode($tAbsent);
$jsonLate = json_encode($tLate);
$jsonLabels = json_encode($dLabels);
$jsonCounts = json_encode($dCounts);
$jsonColors = json_encode($dColors);

$extraScripts = <<<HTML
<script>
    new Chart(document.getElementById('teacherTrendChart'), {
        type: 'bar',
        data: {
            labels: {$jsonDates},
            datasets: [
                { label: 'Present', data: {$jsonPresent}, backgroundColor: '#D4AF37', borderRadius: 4 },
                { label: 'Absent', data: {$jsonAbsent}, backgroundColor: '#ef4444', borderRadius: 4 },
                { label: 'Late', data: {$jsonLate}, backgroundColor: '#B8860B', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('teacherDoughnutChart'), {
        type: 'doughnut',
        data: {
            labels: {$jsonLabels},
            datasets: [{
                data: {$jsonCounts},
                backgroundColor: {$jsonColors}
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%'
        }
    });
</script>
HTML;

require_once __DIR__ . '/../views/layouts/footer.php';
?>
