<?php
/**
 * Teacher - Attendance Sessions History Module
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Class.php';

require_role(['teacher', 'admin']);

$user = current_user();
$teacherModel = new Teacher();
$attendanceModel = new Attendance();
$classModel = new ClassModel();

$teacher = $teacherModel->getByUserId($user['id']);
$teacherId = $teacher['id'] ?? 0;

$assignedClasses = $teacherModel->getAssignedClasses($teacherId);

$filters = [
    'class_id'  => $_GET['class_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to'] ?? ''
];

$sessions = $attendanceModel->getAttendanceSessionsList($teacherId, $filters);

$pageTitle = 'Attendance History';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Attendance Session History</h3>
        <p class="text-secondary small mb-0">Browse past classroom attendance logs, verify headcounts, and inspect session records.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/teacher/take_attendance.php" class="btn btn-primary">
            <i class="bi bi-calendar2-plus-fill me-1"></i> Record New Attendance
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card-modern p-3 mb-4">
    <form method="GET" action="<?= BASE_URL ?>/teacher/attendance_history.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <select name="class_id" class="form-select form-select-sm">
                <option value="">All My Classes</option>
                <?php foreach ($assignedClasses as $ac): ?>
                    <option value="<?= $ac['id'] ?>" <?= $filters['class_id'] == $ac['id'] ? 'selected' : '' ?>>
                        <?= e($ac['class_code']) ?>: <?= e($ac['subject_name']) ?> (<?= e($ac['section_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>" placeholder="From Date">
        </div>
        <div class="col-md-3">
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>" placeholder="To Date">
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter"><i class="bi bi-funnel"></i></button>
            <a href="<?= BASE_URL ?>/teacher/attendance_history.php" class="btn btn-sm btn-light border" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<!-- Sessions Table -->
<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Date & Type</th>
                        <th>Class & Subject</th>
                        <th>Section</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Excused</th>
                        <th>Total Marked</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= format_date($s['session_date']) ?></div>
                                <span class="badge bg-light text-secondary border font-monospace"><?= e($s['session_type']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($s['subject_name']) ?></div>
                                <small class="text-muted"><?= e($s['class_code']) ?> (<?= e($s['subject_code']) ?>)</small>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?= e($s['section_name']) ?></span></td>
                            <td><span class="badge bg-success-subtle text-success fs-6"><?= (int)$s['present_count'] ?></span></td>
                            <td><span class="badge bg-danger-subtle text-danger fs-6"><?= (int)$s['absent_count'] ?></span></td>
                            <td><span class="badge bg-warning-subtle text-warning fs-6"><?= (int)$s['late_count'] ?></span></td>
                            <td><span class="badge bg-info-subtle text-info fs-6"><?= (int)$s['excused_count'] ?></span></td>
                            <td><strong><?= (int)$s['total_marked'] ?></strong></td>
                            <td>
                                <?php if ($s['status'] === 'closed'): ?>
                                    <span class="badge bg-secondary">Closed</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active / Open</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/teacher/take_attendance.php?class_id=<?= $s['class_id'] ?>&session_date=<?= $s['session_date'] ?>&session_type=<?= urlencode($s['session_type']) ?>" class="btn btn-sm btn-outline-primary" title="Review or Edit Attendance">
                                    <i class="bi bi-pencil-square me-1"></i> Edit Sheet
                                </a>
                            </td>
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
