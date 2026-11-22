<?php
/**
 * Teacher - Enrolled Students & Individual Attendance Profiles Module
 * Provides separated, individual student attendance records, summaries, and histories.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Class.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';

require_role(['teacher', 'admin']);

$user = current_user();
$teacherModel = new Teacher();
$classModel = new ClassModel();
$studentModel = new Student();
$attendanceModel = new Attendance();
$attendanceController = new AttendanceController();
$db = Database::getInstance()->getConnection();

$teacher = $teacherModel->getByUserId($user['id']);
$teacherId = $teacher['id'] ?? 0;

// Handle Form Submissions for authorized teacher corrections
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_record') {
        $attendanceController->handleUpdateRecord();
    } elseif ($action === 'add_individual_record') {
        $attendanceController->handleAddIndividualRecord();
    }
}

// Check if viewing a specific individual student's attendance profile
$selectedStudentId = (int)($_GET['student_id'] ?? 0);
$selectedStudent = null;
$studentAttendanceRecords = [];
$studentStats = null;
$studentEnrolledClasses = [];

// Filter parameters for individual attendance
$periodFilter = $_GET['period'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$subjectFilter = (int)($_GET['subject_id'] ?? 0);

if ($selectedStudentId) {
    $selectedStudent = $studentModel->getById($selectedStudentId);
    if ($selectedStudent) {
        $filterArgs = [
            'period'     => $periodFilter,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'status'     => $statusFilter,
            'subject_id' => $subjectFilter
        ];
        $studentAttendanceRecords = $studentModel->getAttendanceHistory($selectedStudentId, $filterArgs);
        $studentStats = $studentModel->getAttendanceStats($selectedStudentId, $filterArgs);
        $studentEnrolledClasses = $studentModel->getEnrolledClasses($selectedStudentId);
    }
}

// Handle Export CSV for this individual student
if (isset($_GET['export']) && $_GET['export'] === 'student_csv' && $selectedStudent) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendance_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $selectedStudent['student_id']) . '_' . date('Ymd') . '.csv');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Date', 'Student ID', 'Student Name', 'Grade Level', 'Section', 'Subject Code', 'Subject Name', 'Time In', 'Time Out', 'Status', 'Remarks', 'Marked By']);
    foreach ($studentAttendanceRecords as $r) {
        fputcsv($out, [
            $r['session_date'],
            $selectedStudent['student_id'],
            $selectedStudent['first_name'] . ' ' . $selectedStudent['last_name'],
            $selectedStudent['year_level_name'],
            $selectedStudent['section_name'],
            $r['subject_code'],
            $r['subject_name'],
            $r['time_in'] ?: '—',
            $r['time_out'] ?: '—',
            $r['status'],
            $r['remarks'] ?: '',
            $r['teacher_name'] ?: 'N/A'
        ]);
    }
    fclose($out);
    exit;
}

// Fetch assigned classes for filter
$assignedClasses = $teacherModel->getAssignedClasses($teacherId);
$classFilter = (int)($_GET['class_id'] ?? 0);
$searchTerm = trim($_GET['search'] ?? '');

// Fetch students enrolled across teacher's classes with individual attendance summaries
$sql = "
    SELECT DISTINCT s.*, 
           c.course_code, c.course_name,
           sec.section_name,
           yl.name as year_level_name,
           cl.id as class_id, cl.class_code, sub.subject_code, sub.subject_name
    FROM class_enrollments ce
    JOIN students s ON ce.student_id = s.id
    JOIN classes cl ON ce.class_id = cl.id
    JOIN subjects sub ON cl.subject_id = sub.id
    JOIN sections sec ON s.section_id = sec.id
    JOIN courses c ON s.course_id = c.id
    JOIN year_levels yl ON s.year_level_id = yl.id
    WHERE cl.teacher_id = ?
";
$params = [$teacherId];

if ($classFilter) {
    $sql .= " AND cl.id = ?";
    $params[] = $classFilter;
}

if ($searchTerm) {
    $sql .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
    $term = "%{$searchTerm}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY s.last_name ASC, s.first_name ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rawStudents = $stmt->fetchAll();

// Group by student ID and attach individual attendance summaries
$students = [];
$totalPresentAll = 0;
$totalAbsentAll = 0;
$totalLateAll = 0;
$highComplianceCount = 0;
$atRiskCount = 0;

foreach ($rawStudents as $st) {
    if (!isset($students[$st['id']])) {
        $stStats = $studentModel->getAttendanceStats($st['id']);
        $st['stats'] = $stStats;
        $st['enrolled_classes_list'] = [];
        
        $totalPresentAll += $stStats['present'];
        $totalAbsentAll += $stStats['absent'];
        $totalLateAll += $stStats['late'];
        
        if ($stStats['percentage'] >= 80) {
            $highComplianceCount++;
        } elseif ($stStats['total'] > 0 && $stStats['percentage'] < 75) {
            $atRiskCount++;
        }
        
        $students[$st['id']] = $st;
    }
    $students[$st['id']]['enrolled_classes_list'][] = $st['subject_code'] . ' (' . $st['section_name'] . ')';
}

$pageTitle = $selectedStudent ? ('Attendance Profile - ' . e($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name'])) : 'Enrolled Students Attendance';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<?php if ($selectedStudent): ?>
    <!-- ========================================== -->
    <!-- MODE 1: INDIVIDUAL STUDENT ATTENDANCE VIEW -->
    <!-- ========================================== -->

    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= BASE_URL ?>/teacher/students.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Student Directory
                </a>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                    <i class="bi bi-person-badge me-1"></i> Individual Student Profile
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0">
                <?= e($selectedStudent['first_name']) ?> <?= e($selectedStudent['last_name']) ?>'s Attendance
            </h3>
            <p class="text-secondary small mb-0">Isolated, separate attendance audit records and performance analytics for this student.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                <i class="bi bi-plus-circle me-1"></i> Add Attendance Record
            </button>
            <a href="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $selectedStudent['id'] ?>&export=student_csv" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Student CSV
            </a>
            <button type="button" class="btn btn-light border" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> Print Profile
            </button>
        </div>
    </div>

    <!-- Student Metadata Card -->
    <div class="card-modern p-4 mb-4 border-primary" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 72px; height: 72px; font-size: 1.8rem;">
                    <?= strtoupper(substr($selectedStudent['first_name'], 0, 1)) ?>
                </div>
            </div>
            <div class="col">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h4 class="fw-bold text-dark mb-0"><?= e($selectedStudent['first_name']) ?> <?= e($selectedStudent['last_name']) ?></h4>
                    <span class="badge bg-light text-dark border font-monospace px-2 py-1"><?= e($selectedStudent['student_id']) ?></span>
                    <?= get_status_badge($selectedStudent['status']) ?>
                </div>
                <div class="row g-2 text-secondary small">
                    <div class="col-sm-auto">
                        <i class="bi bi-mortarboard me-1 text-primary"></i>
                        <strong>Grade / Year:</strong> <?= e($selectedStudent['year_level_name']) ?>
                    </div>
                    <div class="col-sm-auto">
                        <i class="bi bi-diagram-3 me-1 text-primary"></i>
                        <strong>Section:</strong> <?= e($selectedStudent['section_name']) ?>
                    </div>
                    <div class="col-sm-auto">
                        <i class="bi bi-book me-1 text-primary"></i>
                        <strong>Program:</strong> <?= e($selectedStudent['course_code']) ?> (<?= e($selectedStudent['course_name']) ?>)
                    </div>
                    <div class="col-sm-auto">
                        <i class="bi bi-envelope me-1 text-primary"></i>
                        <?= e($selectedStudent['email']) ?>
                    </div>
                    <div class="col-sm-auto">
                        <i class="bi bi-telephone me-1 text-primary"></i>
                        <?= e($selectedStudent['contact_number'] ?: 'N/A') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Individual Attendance Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Attendance Percentage -->
        <div class="col-sm-6 col-xl">
            <div class="stat-card border-primary bg-primary-subtle">
                <div class="stat-card-inner">
                    <div>
                        <div class="stat-title text-primary">Attendance Rate</div>
                        <div class="stat-value text-primary"><?= $studentStats['percentage'] ?>%</div>
                        <small class="text-primary fw-semibold">
                            <?= $studentStats['percentage'] >= 80 ? 'Good Standing' : ($studentStats['percentage'] >= 75 ? 'Satisfactory' : 'Needs Attention') ?>
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
                        <div class="stat-value text-success"><?= number_format($studentStats['present']) ?></div>
                        <small class="text-success"><i class="bi bi-check2-circle me-1"></i>Attended on time</small>
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
                        <div class="stat-value text-danger"><?= number_format($studentStats['absent']) ?></div>
                        <small class="text-danger"><i class="bi bi-x-circle me-1"></i>Missed sessions</small>
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
                        <div class="stat-value text-warning"><?= number_format($studentStats['late']) ?></div>
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
                        <div class="stat-value text-info"><?= number_format($studentStats['excused']) ?></div>
                        <small class="text-info"><i class="bi bi-info-circle me-1"></i>Authorized leave</small>
                    </div>
                    <div class="stat-icon info">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance History Filter Bar (Daily, Weekly, Monthly) -->
    <div class="card-modern p-3 mb-4">
        <form method="GET" action="<?= BASE_URL ?>/teacher/students.php" class="row g-2 align-items-center">
            <input type="hidden" name="student_id" value="<?= $selectedStudent['id'] ?>">
            
            <div class="col-auto">
                <span class="small fw-bold text-secondary text-uppercase me-2"><i class="bi bi-funnel"></i> Filters:</span>
            </div>

            <!-- Quick Period Filters -->
            <div class="col-auto">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $selectedStudent['id'] ?>" 
                       class="btn <?= empty($periodFilter) && empty($dateFrom) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        All Time
                    </a>
                    <a href="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $selectedStudent['id'] ?>&period=daily" 
                       class="btn <?= $periodFilter === 'daily' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        Today
                    </a>
                    <a href="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $selectedStudent['id'] ?>&period=weekly" 
                       class="btn <?= $periodFilter === 'weekly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        This Week
                    </a>
                    <a href="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $selectedStudent['id'] ?>&period=monthly" 
                       class="btn <?= $periodFilter === 'monthly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        This Month
                    </a>
                </div>
            </div>

            <!-- Date Range Inputs -->
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>" placeholder="From Date">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>" placeholder="To Date">
            </div>

            <!-- Status Filter -->
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="PRESENT" <?= $statusFilter === 'PRESENT' ? 'selected' : '' ?>>Present</option>
                    <option value="ABSENT" <?= $statusFilter === 'ABSENT' ? 'selected' : '' ?>>Absent</option>
                    <option value="LATE" <?= $statusFilter === 'LATE' ? 'selected' : '' ?>>Late</option>
                    <option value="EXCUSED" <?= $statusFilter === 'EXCUSED' ? 'selected' : '' ?>>Excused</option>
                </select>
            </div>

            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary" title="Apply Filter">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $selectedStudent['id'] ?>" class="btn btn-sm btn-light border" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Individual Student Attendance Log Table -->
    <div class="card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-clock-history text-primary me-2"></i> Attendance History Log
            </h5>
            <span class="badge bg-light text-dark border px-3 py-2">
                <?= count($studentAttendanceRecords) ?> Records Found
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle datatable w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Subject & Course</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Attendance Status</th>
                            <th>Remarks</th>
                            <th>Recorded By</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studentAttendanceRecords)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-calendar-x fs-3 d-block mb-2 text-secondary"></i>
                                    No attendance records found matching this student's filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($studentAttendanceRecords as $rec): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark"><?= format_date($rec['session_date']) ?></span>
                                        <div class="small text-muted font-monospace"><?= e($rec['session_type']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                                            <?= e($rec['subject_code']) ?>
                                        </span>
                                        <div class="fw-semibold text-dark small"><?= e($rec['subject_name']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($rec['time_in'])): ?>
                                            <span class="badge bg-light text-dark border font-monospace">
                                                <i class="bi bi-box-arrow-in-right text-success me-1"></i>
                                                <?= date('h:i A', strtotime($rec['time_in'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($rec['time_out'])): ?>
                                            <span class="badge bg-light text-dark border font-monospace">
                                                <i class="bi bi-box-arrow-left text-danger me-1"></i>
                                                <?= date('h:i A', strtotime($rec['time_out'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= get_status_badge($rec['status']) ?>
                                    </td>
                                    <td>
                                        <small class="text-secondary"><?= e($rec['remarks'] ?: '—') ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block"><?= e($rec['teacher_name'] ?: 'Faculty') ?></small>
                                        <small class="text-muted font-monospace" style="font-size: 0.75rem;">
                                            <?= format_datetime($rec['marked_at']) ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-rec-btn"
                                                data-id="<?= $rec['id'] ?? $rec['record_id'] ?? 0 ?>"
                                                data-student="<?= e($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']) ?>"
                                                data-date="<?= $rec['session_date'] ?>"
                                                data-status="<?= $rec['status'] ?>"
                                                data-timein="<?= e($rec['time_in'] ?? '') ?>"
                                                data-timeout="<?= e($rec['time_out'] ?? '') ?>"
                                                data-remarks="<?= e($rec['remarks'] ?? '') ?>"
                                                title="Edit Record">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- EDIT RECORD MODAL -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i> Edit Attendance Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $selectedStudent['id'] ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_record">
                    <input type="hidden" name="id" id="edit_rec_id">

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Student</label>
                            <input type="text" id="edit_rec_student" class="form-control" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Date</label>
                            <input type="text" id="edit_rec_date" class="form-control" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Attendance Status *</label>
                            <select name="status" id="edit_rec_status" class="form-select" required>
                                <option value="PRESENT">PRESENT</option>
                                <option value="ABSENT">ABSENT</option>
                                <option value="LATE">LATE</option>
                                <option value="EXCUSED">EXCUSED</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Time In</label>
                                <input type="time" name="time_in" id="edit_rec_time_in" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Time Out</label>
                                <input type="time" name="time_out" id="edit_rec_time_out" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Teacher Remarks / Reason</label>
                            <textarea name="remarks" id="edit_rec_remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD INDIVIDUAL ATTENDANCE RECORD MODAL -->
    <div class="modal fade" id="addRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i> Log Student Attendance Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $selectedStudent['id'] ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_individual_record">
                    <input type="hidden" name="student_id" value="<?= $selectedStudent['id'] ?>">

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Class / Subject *</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">Select Enrolled Class</option>
                                <?php foreach ($studentEnrolledClasses as $ec): ?>
                                    <option value="<?= $ec['id'] ?>">
                                        <?= e($ec['subject_code']) ?>: <?= e($ec['subject_name']) ?> (<?= e($ec['section_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Session Date *</label>
                                <input type="date" name="session_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Attendance Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="PRESENT" selected>PRESENT</option>
                                    <option value="ABSENT">ABSENT</option>
                                    <option value="LATE">LATE</option>
                                    <option value="EXCUSED">EXCUSED</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Time In</label>
                                <input type="time" name="time_in" class="form-control" value="<?= date('H:i') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Time Out</label>
                                <input type="time" name="time_out" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Remarks / Justification</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Regular classroom attendance logged by faculty"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ============================================== -->
    <!-- MODE 2: STUDENT DIRECTORY & ATTENDANCE OVERVIEW -->
    <!-- ============================================== -->

    <!-- Header Actions Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Student Attendance Directory</h3>
            <p class="text-secondary small mb-0">Browse your enrolled students, monitor compliance percentages, and open isolated individual attendance records.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/teacher/take_attendance.php" class="btn btn-primary">
                <i class="bi bi-calendar2-plus-fill me-1"></i> Record Class Attendance
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards for Teacher's Cohorts -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card border-primary">
                <div class="stat-card-inner">
                    <div>
                        <div class="stat-title text-primary">Enrolled Students</div>
                        <div class="stat-value text-primary"><?= count($students) ?></div>
                        <small class="text-muted"><i class="bi bi-people-fill me-1"></i>Across your classes</small>
                    </div>
                    <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card border-success">
                <div class="stat-card-inner">
                    <div>
                        <div class="stat-title text-success">Good Compliance (>80%)</div>
                        <div class="stat-value text-success"><?= $highComplianceCount ?></div>
                        <small class="text-success"><i class="bi bi-check-circle me-1"></i>High attendance</small>
                    </div>
                    <div class="stat-icon success"><i class="bi bi-award-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card border-danger">
                <div class="stat-card-inner">
                    <div>
                        <div class="stat-title text-danger">At Risk (<75%)</div>
                        <div class="stat-value text-danger"><?= $atRiskCount ?></div>
                        <small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Needs attention</small>
                    </div>
                    <div class="stat-icon danger"><i class="bi bi-exclamation-circle-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card border-warning">
                <div class="stat-card-inner">
                    <div>
                        <div class="stat-title text-warning">Total Days Present</div>
                        <div class="stat-value text-warning"><?= number_format($totalPresentAll) ?></div>
                        <small class="text-muted"><i class="bi bi-calendar-check me-1"></i>Cumulatively marked</small>
                    </div>
                    <div class="stat-icon warning"><i class="bi bi-calendar-check-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card-modern p-3 mb-4">
        <form method="GET" action="<?= BASE_URL ?>/teacher/students.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <select name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All My Classes & Cohorts</option>
                    <?php foreach ($assignedClasses as $ac): ?>
                        <option value="<?= $ac['id'] ?>" <?= $classFilter == $ac['id'] ? 'selected' : '' ?>>
                            <?= e($ac['class_code']) ?>: <?= e($ac['subject_name']) ?> (<?= e($ac['section_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by student name, ID, or email..." value="<?= e($searchTerm) ?>">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Search"><i class="bi bi-search"></i> Search</button>
                <a href="<?= BASE_URL ?>/teacher/students.php" class="btn btn-sm btn-light border" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Students Directory Table with Individual Attendance Cards/Rows -->
    <div class="card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-person-lines-fill text-primary me-2"></i> Enrolled Students Directory
            </h5>
            <span class="badge bg-light text-dark border px-3 py-2">
                <?= count($students) ?> Students Listed
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle datatable w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Grade & Section</th>
                            <th>Individual Attendance Stats</th>
                            <th>Compliance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): 
                            $stats = $st['stats'];
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <?= strtoupper(substr($st['first_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($st['first_name']) ?> <?= e($st['last_name']) ?></div>
                                            <small class="text-muted"><?= e($st['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace"><?= e($st['student_id']) ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary"><?= e($st['year_level_name']) ?></span>
                                    <div class="small text-muted">Sec: <?= e($st['section_name']) ?> (<?= e($st['course_code']) ?>)</div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" title="Total Present">
                                            <i class="bi bi-check-lg me-1"></i><?= $stats['present'] ?> Pres
                                        </span>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="Total Absent">
                                            <i class="bi bi-x-lg me-1"></i><?= $stats['absent'] ?> Abs
                                        </span>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="Total Late">
                                            <i class="bi bi-clock me-1"></i><?= $stats['late'] ?> Late
                                        </span>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" title="Total Excused">
                                            <i class="bi bi-shield-check me-1"></i><?= $stats['excused'] ?> Exc
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2" style="min-width: 110px;">
                                        <div class="progress flex-fill" style="height: 8px;">
                                            <div class="progress-bar <?= $stats['percentage'] >= 80 ? 'bg-success' : ($stats['percentage'] >= 75 ? 'bg-warning' : 'bg-danger') ?>" 
                                                 style="width: <?= $stats['percentage'] ?>%;"></div>
                                        </div>
                                        <span class="small fw-bold <?= $stats['percentage'] >= 80 ? 'text-success' : ($stats['percentage'] >= 75 ? 'text-warning' : 'text-danger') ?>">
                                            <?= $stats['percentage'] ?>%
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/teacher/students.php?student_id=<?= $st['id'] ?>" class="btn btn-primary" title="View Individual Attendance Profile">
                                            <i class="bi bi-calendar-check me-1"></i> View Attendance
                                        </a>
                                        <button type="button" class="btn btn-light border view-profile-btn" 
                                                data-student='<?= json_encode($st) ?>' 
                                                title="View Student Profile">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- STUDENT PROFILE MODAL -->
    <div class="modal fade" id="studentProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i> Student Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="rounded-circle bg-primary-gradient text-white mx-auto d-flex align-items-center justify-content-center fw-bold fs-2 mb-3 shadow" style="width: 80px; height: 80px;" id="profile_avatar">
                        S
                    </div>
                    <h4 class="fw-bold text-dark mb-1" id="profile_name">Student Name</h4>
                    <div class="badge bg-light text-dark border font-monospace mb-2" id="profile_student_id">STD-000</div>
                    <div class="text-muted small mb-3" id="profile_email">email@domain.com</div>

                    <div class="p-3 bg-light rounded-3 border text-start small mb-3">
                        <div class="row g-2">
                            <div class="col-6"><span class="text-muted">Grade / Year:</span> <strong class="text-dark" id="profile_grade">N/A</strong></div>
                            <div class="col-6"><span class="text-muted">Section:</span> <strong class="text-dark" id="profile_section">N/A</strong></div>
                            <div class="col-6"><span class="text-muted">Program:</span> <strong class="text-dark" id="profile_course">N/A</strong></div>
                            <div class="col-6"><span class="text-muted">Gender:</span> <strong class="text-dark" id="profile_gender">N/A</strong></div>
                            <div class="col-12"><span class="text-muted">Contact:</span> <strong class="text-dark" id="profile_contact">N/A</strong></div>
                        </div>
                    </div>

                    <!-- Digital Student QR Badge -->
                    <div class="p-3 bg-white rounded-3 border text-center mb-3">
                        <div class="small fw-bold text-secondary text-uppercase mb-2">Student QR Code Badge</div>
                        <img id="profile_qr_img" src="" alt="Student QR Code" style="width: 140px; height: 140px;" class="border p-2 rounded">
                    </div>

                    <a href="#" id="profile_view_attendance_btn" class="btn btn-primary w-100">
                        <i class="bi bi-calendar-check-fill me-2"></i> View Individual Attendance Records
                    </a>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$extraScripts = "
<script>
    // Handle Individual Attendance Record Edit
    document.querySelectorAll('.edit-rec-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_rec_id').value = this.dataset.id;
            document.getElementById('edit_rec_student').value = this.dataset.student;
            document.getElementById('edit_rec_date').value = this.dataset.date;
            document.getElementById('edit_rec_status').value = this.dataset.status;
            document.getElementById('edit_rec_time_in').value = this.dataset.timein || '';
            document.getElementById('edit_rec_time_out').value = this.dataset.timeout || '';
            document.getElementById('edit_rec_remarks').value = this.dataset.remarks || '';

            const modal = new bootstrap.Modal(document.getElementById('editRecordModal'));
            modal.show();
        });
    });

    // Handle View Student Profile Modal in Directory
    document.querySelectorAll('.view-profile-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.student);
            document.getElementById('profile_avatar').innerText = data.first_name.charAt(0).toUpperCase();
            document.getElementById('profile_name').innerText = data.first_name + ' ' + data.last_name;
            document.getElementById('profile_student_id').innerText = data.student_id;
            document.getElementById('profile_email').innerText = data.email;
            document.getElementById('profile_grade').innerText = data.year_level_name || 'N/A';
            document.getElementById('profile_section').innerText = data.section_name || 'N/A';
            document.getElementById('profile_course').innerText = data.course_code || 'N/A';
            document.getElementById('profile_gender').innerText = data.gender || 'N/A';
            document.getElementById('profile_contact').innerText = data.contact_number || 'N/A';

            const qrCodeValue = data.qr_code || data.student_id;
            document.getElementById('profile_qr_img').src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(qrCodeValue);
            document.getElementById('profile_view_attendance_btn').href = 'students.php?student_id=' + data.id;

            const modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));
            modal.show();
        });
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
