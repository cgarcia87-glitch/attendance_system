<?php
/**
 * Teacher - Class Attendance Reports
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../controllers/ReportController.php';

require_role(['teacher', 'admin']);

$user = current_user();
$teacherModel = new Teacher();
$reportModel = new Report();

$teacher = $teacherModel->getByUserId($user['id']);
$teacherId = $teacher['id'] ?? 0;

if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $reportController = new ReportController();
    $reportController->handleExport();
}

$assignedClasses = $teacherModel->getAssignedClasses($teacherId);

$filters = [
    'period'     => $_GET['period'] ?? '',
    'date_from'  => $_GET['date_from'] ?? '',
    'date_to'    => $_GET['date_to'] ?? '',
    'teacher_id' => $teacherId,
    'class_id'   => $_GET['class_id'] ?? '',
    'status'     => $_GET['status'] ?? ''
];

$records = $reportModel->getAttendanceReport($filters);
$summary = $reportModel->getSummaryStats($records);

$pageTitle = 'Class Attendance Reports';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h3 class="fw-bold text-dark mb-1">Class Attendance Reports</h3>
        <p class="text-secondary small mb-0">Generate, review, print, and export attendance records for your assigned subjects.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="<?= BASE_URL ?>/teacher/reports.php?action=export&format=csv&<?= http_build_query($filters) ?>" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
    </div>
</div>

<!-- Filters Form -->
<div class="card-modern p-4 mb-4 no-print">
    <form method="GET" action="<?= BASE_URL ?>/teacher/reports.php">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Period Preset</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Custom Date Range</option>
                    <option value="daily" <?= $filters['period'] === 'daily' ? 'selected' : '' ?>>Today (Daily)</option>
                    <option value="weekly" <?= $filters['period'] === 'weekly' ? 'selected' : '' ?>>This Week (Weekly)</option>
                    <option value="monthly" <?= $filters['period'] === 'monthly' ? 'selected' : '' ?>>This Month (Monthly)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Filter By Class</label>
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">All My Classes</option>
                    <?php foreach ($assignedClasses as $ac): ?>
                        <option value="<?= $ac['id'] ?>" <?= $filters['class_id'] == $ac['id'] ? 'selected' : '' ?>>
                            <?= e($ac['class_code']) ?>: <?= e($ac['subject_name']) ?> (<?= e($ac['section_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= BASE_URL ?>/teacher/reports.php" class="btn btn-sm btn-light border">Reset</a>
            <button type="submit" class="btn btn-sm btn-primary px-4"><i class="bi bi-filter me-1"></i> Generate Report</button>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-2">
        <div class="stat-card p-3 text-center">
            <div class="stat-title">Total Records</div>
            <div class="stat-value fs-3"><?= number_format($summary['total']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="stat-card p-3 text-center border-success">
            <div class="stat-title text-success">Present</div>
            <div class="stat-value fs-3 text-success"><?= number_format($summary['present']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="stat-card p-3 text-center border-danger">
            <div class="stat-title text-danger">Absent</div>
            <div class="stat-value fs-3 text-danger"><?= number_format($summary['absent']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="stat-card p-3 text-center border-warning">
            <div class="stat-title text-warning">Late</div>
            <div class="stat-value fs-3 text-warning"><?= number_format($summary['late']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="stat-card p-3 text-center border-info">
            <div class="stat-title text-info">Excused</div>
            <div class="stat-value fs-3 text-info"><?= number_format($summary['excused']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="stat-card p-3 text-center border-primary bg-primary-subtle">
            <div class="stat-title text-primary">Class Average</div>
            <div class="stat-value fs-3 text-primary"><?= $summary['percentage'] ?>%</div>
        </div>
    </div>
</div>

<!-- Report Table -->
<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-bordered table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Class & Subject</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><span class="fw-semibold"><?= format_date($r['session_date']) ?></span></td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= e($r['student_number']) ?></span></td>
                            <td><span class="fw-bold text-dark"><?= e($r['student_name']) ?></span></td>
                            <td>
                                <div class="fw-medium text-dark"><?= e($r['subject_name']) ?></div>
                                <small class="text-muted"><?= e($r['class_code']) ?></small>
                            </td>
                            <td><?= e($r['section_name']) ?></td>
                            <td><?= get_status_badge($r['status']) ?></td>
                            <td><small class="text-muted"><?= e($r['remarks'] ?: '—') ?></small></td>
                            <td><small class="text-muted"><?= format_time($r['marked_at']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../views/layouts/footer.php';
?>
