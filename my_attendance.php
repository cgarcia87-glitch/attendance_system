<?php
/**
 * Student - My Attendance Module
 * Individual student attendance profile, history, and subject breakdown.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Attendance.php';

require_role(['student', 'admin']);

$user = current_user();
$studentModel = new Student();
$attendanceModel = new Attendance();
$db = Database::getInstance()->getConnection();

$student = $studentModel->getByUserId($user['id']);
$studentId = $student['id'] ?? 1;

$periodFilter = $_GET['period'] ?? '';
$filters = [
    'period'     => $periodFilter,
    'date_from'  => $_GET['date_from'] ?? '',
    'date_to'    => $_GET['date_to'] ?? '',
    'subject_id' => $_GET['subject_id'] ?? '',
    'status'     => $_GET['status'] ?? ''
];

$records = $studentModel->getAttendanceHistory($studentId, $filters);
$overallStats = $studentModel->getAttendanceStats($studentId, $filters);
$enrolledClasses = $studentModel->getEnrolledClasses($studentId);

// Calculate subject-specific attendance statistics
$subjectStats = [];
foreach ($enrolledClasses as $ec) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN ar.status = 'PRESENT' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN ar.status = 'ABSENT' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN ar.status = 'LATE' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN ar.status = 'EXCUSED' THEN 1 ELSE 0 END) as excused
        FROM attendance_records ar
        WHERE ar.student_id = ? AND ar.class_id = ?
    ");
    $stmt->execute([$studentId, $ec['id']]);
    $st = $stmt->fetch();

    $tot = (int)($st['total'] ?? 0);
    $prs = (int)($st['present'] ?? 0);
    $lat = (int)($st['late'] ?? 0);
    $exc = (int)($st['excused'] ?? 0);
    $rate = calculate_attendance_percentage($prs, $lat, $exc, $tot);

    $subjectStats[] = [
        'subject_code' => $ec['subject_code'],
        'subject_name' => $ec['subject_name'],
        'teacher_name' => $ec['teacher_name'],
        'total'        => $tot,
        'present'      => $prs,
        'absent'       => (int)($st['absent'] ?? 0),
        'late'         => $lat,
        'excused'      => $exc,
        'rate'         => $rate
    ];
}

$pageTitle = 'My Attendance Records';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">My Attendance Records</h3>
        <p class="text-secondary small mb-0">Track your attendance compliance percentage, punch-in/out times, and detailed session logs.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/student/reports.php" class="btn btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Print Attendance Report
        </a>
    </div>
</div>

<!-- Student Information Banner -->
<div class="card-modern p-4 mb-4 border-primary" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #1e293b 100%); color: #ffffff;">
    <div class="row align-items-center g-3">
        <div class="col-auto">
            <div class="rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center shadow" style="width: 64px; height: 64px; font-size: 1.6rem;">
                <?= strtoupper(substr($student['first_name'] ?? 'S', 0, 1)) ?>
            </div>
        </div>
        <div class="col">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h4 class="fw-bold text-white mb-0"><?= e($student['first_name'] ?? '') ?> <?= e($student['last_name'] ?? '') ?></h4>
                <span class="badge bg-light text-dark font-monospace"><?= e($student['student_id'] ?? '') ?></span>
                <span class="badge bg-success">Active Student</span>
            </div>
            <div class="row g-2 text-white-50 small">
                <div class="col-sm-auto">
                    <i class="bi bi-mortarboard me-1 text-info"></i>
                    <strong>Grade Level:</strong> <?= e($student['year_level_name'] ?? 'N/A') ?>
                </div>
                <div class="col-sm-auto">
                    <i class="bi bi-diagram-3 me-1 text-info"></i>
                    <strong>Section:</strong> <?= e($student['section_name'] ?? 'N/A') ?>
                </div>
                <div class="col-sm-auto">
                    <i class="bi bi-book me-1 text-info"></i>
                    <strong>Course:</strong> <?= e($student['course_code'] ?? 'N/A') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5 Stat Cards (Individual Attendance Summary) -->
<div class="row g-3 mb-4">
    <!-- Attendance Percentage -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card border-primary bg-primary-subtle">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title text-primary">Attendance Rate</div>
                    <div class="stat-value text-primary"><?= $overallStats['percentage'] ?>%</div>
                    <small class="text-primary fw-semibold">
                        <?= $overallStats['percentage'] >= 80 ? 'Good Standing' : ($overallStats['percentage'] >= 75 ? 'Satisfactory' : 'Needs Attention') ?>
                    </small>
                </div>
                <div class="stat-icon primary">
                    <i class="bi bi-percent"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Present -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card border-success">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title text-success">Total Days Present</div>
                    <div class="stat-value text-success"><?= number_format($overallStats['present']) ?></div>
                    <small class="text-success"><i class="bi bi-check-circle me-1"></i>Full attendance</small>
                </div>
                <div class="stat-icon success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Absent -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card border-danger">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title text-danger">Total Days Absent</div>
                    <div class="stat-value text-danger"><?= number_format($overallStats['absent']) ?></div>
                    <small class="text-danger"><i class="bi bi-x-circle me-1"></i>Missed classes</small>
                </div>
                <div class="stat-icon danger">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Late -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card border-warning">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title text-warning">Total Days Late</div>
                    <div class="stat-value text-warning"><?= number_format($overallStats['late']) ?></div>
                    <small class="text-warning"><i class="bi bi-clock-history me-1"></i>Tardy arrivals</small>
                </div>
                <div class="stat-icon warning">
                    <i class="bi bi-clock-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Excused -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card border-info">
            <div class="stat-card-inner">
                <div>
                    <div class="stat-title text-info">Total Days Excused</div>
                    <div class="stat-value text-info"><?= number_format($overallStats['excused']) ?></div>
                    <small class="text-info"><i class="bi bi-shield-check me-1"></i>Approved leaves</small>
                </div>
                <div class="stat-icon info">
                    <i class="bi bi-shield-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Subject Attendance Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($subjectStats as $ss): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card-modern p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                        <?= e($ss['subject_code']) ?>
                    </span>
                    <span class="badge <?= $ss['rate'] >= 80 ? 'bg-success' : 'bg-warning text-dark' ?> fs-6 fw-bold">
                        <?= $ss['rate'] ?>%
                    </span>
                </div>
                <h6 class="fw-bold text-dark mb-1 text-truncate"><?= e($ss['subject_name']) ?></h6>
                <small class="text-muted d-block mb-3"><?= e($ss['teacher_name']) ?></small>

                <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar <?= $ss['rate'] >= 80 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= $ss['rate'] ?>%;"></div>
                </div>

                <div class="d-flex justify-content-between text-center small text-secondary">
                    <div><strong><?= $ss['present'] ?></strong> Present</div>
                    <div><strong><?= $ss['absent'] ?></strong> Absent</div>
                    <div><strong><?= $ss['late'] ?></strong> Late</div>
                    <div><strong><?= $ss['total'] ?></strong> Total</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Filters (Daily, Weekly, Monthly, and Date Range) -->
<div class="card-modern p-3 mb-4">
    <form method="GET" action="<?= BASE_URL ?>/student/my_attendance.php" class="row g-2 align-items-center">
        <!-- Quick Period Filter Buttons -->
        <div class="col-auto">
            <div class="btn-group btn-group-sm" role="group">
                <a href="<?= BASE_URL ?>/student/my_attendance.php" 
                   class="btn <?= empty($periodFilter) && empty($filters['date_from']) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    All Time
                </a>
                <a href="<?= BASE_URL ?>/student/my_attendance.php?period=daily" 
                   class="btn <?= $periodFilter === 'daily' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Today
                </a>
                <a href="<?= BASE_URL ?>/student/my_attendance.php?period=weekly" 
                   class="btn <?= $periodFilter === 'weekly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    This Week
                </a>
                <a href="<?= BASE_URL ?>/student/my_attendance.php?period=monthly" 
                   class="btn <?= $periodFilter === 'monthly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    This Month
                </a>
            </div>
        </div>

        <div class="col-md-3">
            <select name="subject_id" class="form-select form-select-sm">
                <option value="">All My Subjects</option>
                <?php foreach ($enrolledClasses as $ec): ?>
                    <option value="<?= $ec['subject_id'] ?>" <?= $filters['subject_id'] == $ec['subject_id'] ? 'selected' : '' ?>>
                        <?= e($ec['subject_code']) ?>: <?= e($ec['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>" placeholder="From Date">
        </div>
        <div class="col-md-2">
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>" placeholder="To Date">
        </div>

        <div class="col-auto d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            <a href="<?= BASE_URL ?>/student/my_attendance.php" class="btn btn-sm btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<!-- All Attendance Table with Time In & Time Out -->
<div class="card-modern">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-calendar-check text-primary me-2"></i> Attendance Record Audit Log
        </h5>
        <span class="badge bg-light text-dark border px-3 py-2">
            <?= count($records) ?> Attendance Sessions Recorded
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Instructor Remarks</th>
                        <th>Time Marked</th>
                        <th>Instructor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No attendance records found for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><span class="fw-semibold text-dark"><?= format_date($r['session_date']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($r['subject_name']) ?></div>
                                    <small class="text-muted"><?= e($r['subject_code']) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($r['time_in'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            <i class="bi bi-box-arrow-in-right text-success me-1"></i>
                                            <?= date('h:i A', strtotime($r['time_in'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['time_out'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            <i class="bi bi-box-arrow-left text-danger me-1"></i>
                                            <?= date('h:i A', strtotime($r['time_out'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= get_status_badge($r['status']) ?></td>
                                <td><small class="text-muted"><?= e($r['remarks'] ?: '—') ?></small></td>
                                <td><small class="text-muted"><?= format_time($r['marked_at']) ?></small></td>
                                <td><small class="text-secondary"><?= e($r['teacher_name']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../views/layouts/footer.php';
?>
