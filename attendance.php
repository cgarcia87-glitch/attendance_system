<?php
/**
 * Administrator - Individual Student Attendance Management Module
 * Provides separated, individual attendance profiles, histories, and summaries per student.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Class.php';
require_once __DIR__ . '/../models/Subject.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';

require_role(['admin']);

$studentModel = new Student();
$attendanceModel = new Attendance();
$classModel = new ClassModel();
$subjectModel = new Subject();
$attendanceController = new AttendanceController();
$db = Database::getInstance()->getConnection();

// Handle Form Submissions (Update / Add individual student record / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_record':
                $attendanceController->handleUpdateRecord();
                break;
            case 'add_individual_record':
                $attendanceController->handleAddIndividualRecord();
                break;
            case 'delete_record':
                $attendanceController->handleDeleteRecord();
                break;
        }
    }
}

// Check if viewing a specific individual student
$selectedStudentId = (int)($_GET['student_id'] ?? 0);
$selectedStudent = null;
$studentAttendanceRecords = [];
$studentStats = null;
$studentEnrolledClasses = [];

// Filter parameters
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

// Directory filters (when viewing student directory list)
$directoryFilters = [
    'course_id'     => $_GET['course_id'] ?? '',
    'year_level_id' => $_GET['year_level_id'] ?? '',
    'section_id'    => $_GET['section_id'] ?? '',
    'status'        => $_GET['status'] ?? '',
    'search'        => $_GET['search'] ?? ''
];

$studentsList = $studentModel->getAllWithAttendanceSummary($directoryFilters);
$courses = $classModel->getCourses();
$yearLevels = $classModel->getYearLevels();
$sections = $classModel->getSections();

// Handle Export CSV for an individual student
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
            $r['time_in'] ? format_time($r['time_in']) : 'N/A',
            $r['time_out'] ? format_time($r['time_out']) : 'N/A',
            $r['status'],
            $r['remarks'] ?? '',
            $r['teacher_name'] ?? 'Instructor'
        ]);
    }
    fclose($out);
    exit;
}

$pageTitle = $selectedStudent 
    ? 'Attendance Profile: ' . $selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']
    : 'Individual Student Attendance Directory';

require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<?php if ($selectedStudent): ?>
    <!-- ============================================================= -->
    <!-- VIEW MODE: INDIVIDUAL STUDENT ATTENDANCE PROFILE & HISTORY    -->
    <!-- ============================================================= -->

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Back to Student Directory
                </a>
                <span class="text-muted">/</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
                    Individual Student Profile
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0">
                <?= e($selectedStudent['first_name']) ?> <?= e($selectedStudent['last_name']) ?>
                <span class="badge bg-light text-secondary border font-monospace fs-6 ms-2"><?= e($selectedStudent['student_id']) ?></span>
            </h3>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                <i class="bi bi-calendar-plus me-1"></i> Log Attendance Entry
            </button>
            <a href="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>&export=student_csv&<?= http_build_query($_GET) ?>" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Profile
            </button>
        </div>
    </div>

    <!-- Student Information Profile Card -->
    <div class="card-modern p-4 mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
        <div class="row align-items-center g-4">
            <div class="col-md-auto text-center">
                <div class="position-relative d-inline-block">
                    <div class="rounded-circle text-white fw-bold fs-2 d-flex align-items-center justify-content-center shadow" style="width: 85px; height: 85px; background: var(--primary-gradient);">
                        <?= strtoupper(substr($selectedStudent['first_name'], 0, 1)) ?>
                    </div>
                    <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2 border border-2 border-white">
                        <i class="bi bi-check"></i>
                    </span>
                </div>
            </div>
            <div class="col-md">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h4 class="fw-bold text-dark mb-0"><?= e($selectedStudent['first_name']) ?> <?= e($selectedStudent['last_name']) ?></h4>
                    <span class="badge bg-primary px-2 py-1 font-monospace"><?= e($selectedStudent['student_id']) ?></span>
                    <?= get_status_badge($selectedStudent['status']) ?>
                </div>
                <div class="text-secondary small mb-2">
                    <i class="bi bi-envelope me-1"></i><?= e($selectedStudent['email']) ?>
                    <?php if (!empty($selectedStudent['contact_number'])): ?>
                        &bull; <i class="bi bi-telephone me-1"></i><?= e($selectedStudent['contact_number']) ?>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-mortarboard-fill text-primary me-1"></i> Grade / Year: <strong><?= e($selectedStudent['year_level_name']) ?></strong>
                    </span>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-diagram-3-fill text-info me-1"></i> Section: <strong><?= e($selectedStudent['section_name']) ?></strong>
                    </span>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-book-half text-secondary me-1"></i> Degree: <strong><?= e($selectedStudent['course_code']) ?></strong> - <?= e($selectedStudent['course_name']) ?>
                    </span>
                </div>
            </div>
            <div class="col-md-auto text-center border-start-md ps-md-4">
                <div class="p-2 bg-white rounded-3 border d-inline-block shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=95x95&data=<?= urlencode($selectedStudent['qr_code'] ?: $selectedStudent['student_id']) ?>" alt="QR" style="width: 85px; height: 85px;">
                </div>
                <div class="small text-muted mt-1" style="font-size: 0.72rem;">Digital ID Badge</div>
            </div>
        </div>
    </div>

    <!-- Student Individual Attendance Summary Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Attendance Percentage -->
        <div class="col-sm-6 col-lg-2">
            <div class="stat-card p-3 text-center border-primary bg-primary-subtle">
                <div class="stat-title text-primary">Attendance Rate</div>
                <div class="stat-value fs-3 text-primary"><?= $studentStats['percentage'] ?>%</div>
                <small class="text-primary fw-semibold"><?= $studentStats['percentage'] >= 75 ? 'Good Standing' : 'Attendance Warning' ?></small>
            </div>
        </div>
        <!-- Total Present Days -->
        <div class="col-sm-6 col-lg-2">
            <div class="stat-card p-3 text-center border-success">
                <div class="stat-title text-success">Total Days Present</div>
                <div class="stat-value fs-3 text-success"><?= number_format($studentStats['present']) ?></div>
                <small class="text-success"><i class="bi bi-check-circle me-1"></i>Full Attendance</small>
            </div>
        </div>
        <!-- Total Absent Days -->
        <div class="col-sm-6 col-lg-2">
            <div class="stat-card p-3 text-center border-danger">
                <div class="stat-title text-danger">Total Days Absent</div>
                <div class="stat-value fs-3 text-danger"><?= number_format($studentStats['absent']) ?></div>
                <small class="text-danger"><i class="bi bi-x-circle me-1"></i>Missed Sessions</small>
            </div>
        </div>
        <!-- Total Late Days -->
        <div class="col-sm-6 col-lg-2">
            <div class="stat-card p-3 text-center border-warning">
                <div class="stat-title text-warning">Total Days Late</div>
                <div class="stat-value fs-3 text-warning"><?= number_format($studentStats['late']) ?></div>
                <small class="text-warning"><i class="bi bi-clock-history me-1"></i>Tardy</small>
            </div>
        </div>
        <!-- Total Excused Days -->
        <div class="col-sm-6 col-lg-2">
            <div class="stat-card p-3 text-center border-info">
                <div class="stat-title text-info">Total Days Excused</div>
                <div class="stat-value fs-3 text-info"><?= number_format($studentStats['excused']) ?></div>
                <small class="text-info"><i class="bi bi-shield-check me-1"></i>Official Leave</small>
            </div>
        </div>
        <!-- Total Sessions -->
        <div class="col-sm-6 col-lg-2">
            <div class="stat-card p-3 text-center">
                <div class="stat-title">Total Sessions</div>
                <div class="stat-value fs-3"><?= number_format($studentStats['total']) ?></div>
                <small class="text-muted">Recorded Meetings</small>
            </div>
        </div>
    </div>

    <!-- Attendance History Filter Bar (Daily, Weekly, Monthly, Range) -->
    <div class="card-modern p-3 mb-4 no-print">
        <form method="GET" action="<?= BASE_URL ?>/admin/attendance.php" class="row g-2 align-items-center">
            <input type="hidden" name="student_id" value="<?= $selectedStudentId ?>">

            <!-- Quick Period Filters -->
            <div class="col-md-3">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <a href="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>" class="btn <?= empty($periodFilter) && empty($dateFrom) ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
                    <a href="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>&period=daily" class="btn <?= $periodFilter === 'daily' ? 'btn-primary' : 'btn-outline-primary' ?>">Daily</a>
                    <a href="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>&period=weekly" class="btn <?= $periodFilter === 'weekly' ? 'btn-primary' : 'btn-outline-primary' ?>">Weekly</a>
                    <a href="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>&period=monthly" class="btn <?= $periodFilter === 'monthly' ? 'btn-primary' : 'btn-outline-primary' ?>">Monthly</a>
                </div>
            </div>

            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>" placeholder="From Date" title="From Date">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>" placeholder="To Date" title="To Date">
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="PRESENT" <?= $statusFilter === 'PRESENT' ? 'selected' : '' ?>>Present</option>
                    <option value="ABSENT" <?= $statusFilter === 'ABSENT' ? 'selected' : '' ?>>Absent</option>
                    <option value="LATE" <?= $statusFilter === 'LATE' ? 'selected' : '' ?>>Late</option>
                    <option value="EXCUSED" <?= $statusFilter === 'EXCUSED' ? 'selected' : '' ?>>Excused</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="subject_id" class="form-select form-select-sm">
                    <option value="">All Subjects</option>
                    <?php foreach ($studentEnrolledClasses as $sec): ?>
                        <option value="<?= $sec['subject_id'] ?>" <?= $subjectFilter == $sec['subject_id'] ? 'selected' : '' ?>>
                            <?= e($sec['subject_code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter"><i class="bi bi-funnel"></i></button>
                <a href="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>" class="btn btn-sm btn-light border" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Individual Attendance Records Table -->
    <div class="card-modern">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-check-fill text-primary me-2"></i> Attendance History for <?= e($selectedStudent['first_name']) ?> <?= e($selectedStudent['last_name']) ?>
                </h5>
                <small class="text-muted">Separate, student-isolated attendance audit log (Total: <?= count($studentAttendanceRecords) ?> sessions)</small>
            </div>
            <span class="badge bg-light text-dark border px-3 py-1">
                <?= $periodFilter ? ucfirst($periodFilter) . ' Filter Active' : 'All Recorded Dates' ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-bordered table-hover align-middle datatable w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Date</th>
                            <th style="width: 100px;">Time In</th>
                            <th style="width: 100px;">Time Out</th>
                            <th>Subject / Course</th>
                            <th>Classroom / Room</th>
                            <th>Attendance Status</th>
                            <th>Remarks / Notes</th>
                            <th>Recorded By</th>
                            <th class="text-end no-print" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studentAttendanceRecords)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-1 text-secondary d-block mb-2"></i>
                                    No attendance records found for this student matching the specified filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($studentAttendanceRecords as $rec): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= format_date($rec['session_date']) ?></div>
                                        <small class="text-muted font-monospace"><?= e($rec['session_type']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            <i class="bi bi-box-arrow-in-right text-success me-1"></i><?= $rec['time_in'] ? format_time($rec['time_in']) : '—' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            <i class="bi bi-box-arrow-right text-secondary me-1"></i><?= $rec['time_out'] ? format_time($rec['time_out']) : '—' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($rec['subject_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= e($rec['subject_code']) ?> &bull; <?= e($rec['class_code']) ?></small>
                                    </td>
                                    <td>
                                        <small class="text-secondary"><?= e($rec['section_name']) ?></small>
                                    </td>
                                    <td>
                                        <?= get_status_badge($rec['status']) ?>
                                    </td>
                                    <td>
                                        <small class="text-secondary"><?= e($rec['remarks'] ?: '—') ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= e($rec['teacher_name'] ?: ($rec['marked_by_name'] ?? 'Instructor')) ?></small>
                                    </td>
                                    <td class="text-end no-print">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-light border edit-student-record-btn" 
                                                    data-record='<?= json_encode($rec) ?>'
                                                    title="Edit this record">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_record">
                                                <input type="hidden" name="record_id" value="<?= $rec['id'] ?>">
                                                <button type="button" class="btn btn-light border text-danger btn-delete-confirm" title="Delete record">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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

    <!-- MODAL: ADD INDIVIDUAL ATTENDANCE RECORD FOR THIS STUDENT -->
    <div class="modal fade" id="addRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2 text-primary"></i> Log Attendance Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_individual_record">
                    <input type="hidden" name="student_id" value="<?= $selectedStudentId ?>">
                    <div class="modal-body p-4">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="fw-bold text-dark"><?= e($selectedStudent['first_name']) ?> <?= e($selectedStudent['last_name']) ?></div>
                            <small class="text-muted font-monospace"><?= e($selectedStudent['student_id']) ?> &bull; Grade: <?= e($selectedStudent['year_level_name']) ?> &bull; Section: <?= e($selectedStudent['section_name']) ?></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Enrolled Subject / Class *</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">-- Choose Class --</option>
                                <?php foreach ($studentEnrolledClasses as $sec): ?>
                                    <option value="<?= $sec['id'] ?>">
                                        <?= e($sec['subject_code']) ?>: <?= e($sec['subject_name']) ?> (<?= e($sec['class_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Date *</label>
                                <input type="date" name="session_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Attendance Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="PRESENT">PRESENT</option>
                                    <option value="ABSENT">ABSENT</option>
                                    <option value="LATE">LATE</option>
                                    <option value="EXCUSED">EXCUSED</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Time In</label>
                                <input type="time" name="time_in" class="form-control" value="09:00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Time Out</label>
                                <input type="time" name="time_out" class="form-control" value="10:30">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. In-person lecture, verified on time">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: EDIT INDIVIDUAL RECORD -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Attendance Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $selectedStudentId ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_record">
                    <input type="hidden" name="record_id" id="edit_rec_id">
                    <div class="modal-body p-4">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="fw-bold text-dark" id="edit_rec_subject">Subject</div>
                            <small class="text-muted" id="edit_rec_date">Date</small>
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
                            <label class="form-label small fw-bold">Remarks / Reason</label>
                            <input type="text" name="remarks" id="edit_rec_remarks" class="form-control" placeholder="e.g. Excused with medical slip">
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

<?php else: ?>
    <!-- ============================================================= -->
    <!-- DIRECTORY MODE: ORGANIZED STUDENT ATTENDANCE DIRECTORY         -->
    <!-- ============================================================= -->

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Individual Student Attendance Hub</h3>
            <p class="text-secondary small mb-0">Browse student attendance profiles. Each student maintains completely isolated, individual records and compliance summaries.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Multi-Class Reports
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card-modern p-3 mb-4">
        <form method="GET" action="<?= BASE_URL ?>/admin/attendance.php" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search student name, ID..." value="<?= e($directoryFilters['search']) ?>">
            </div>
            <div class="col-md-3">
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">All Degree Programs</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $directoryFilters['course_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['course_code']) ?> - <?= e($c['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="year_level_id" class="form-select form-select-sm">
                    <option value="">All Grade / Year Levels</option>
                    <?php foreach ($yearLevels as $y): ?>
                        <option value="<?= $y['id'] ?>" <?= $directoryFilters['year_level_id'] == $y['id'] ? 'selected' : '' ?>>
                            <?= e($y['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="section_id" class="form-select form-select-sm">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['id'] ?>" <?= $directoryFilters['section_id'] == $sec['id'] ? 'selected' : '' ?>>
                            <?= e($sec['section_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Apply Filter</button>
                <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-sm btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Student Attendance Cards Grid -->
    <div class="row g-4 mb-4">
        <?php if (empty($studentsList)): ?>
            <div class="col-12">
                <div class="card-modern p-5 text-center text-muted">
                    <i class="bi bi-people fs-1 text-secondary d-block mb-3"></i>
                    <h5>No Students Found</h5>
                    <p class="small">No students match your specified search or filtering criteria.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($studentsList as $st): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card-modern p-4 h-100 d-flex flex-column justify-content-between border">
                        <div>
                            <!-- Student Header -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; background: var(--primary-gradient); font-size: 1.15rem;">
                                    <?= strtoupper(substr($st['first_name'], 0, 1)) ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h5 class="fw-bold text-dark mb-0 text-truncate"><?= e($st['first_name']) ?> <?= e($st['last_name']) ?></h5>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border font-monospace"><?= e($st['student_id']) ?></span>
                                        <span class="badge bg-secondary-subtle text-secondary"><?= e($st['section_name']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Student Info Details -->
                            <div class="p-2 bg-light rounded-3 border mb-3 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Grade / Year:</span>
                                    <strong class="text-dark"><?= e($st['year_level_name']) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Degree / Course:</span>
                                    <span class="text-secondary"><?= e($st['course_code']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Email:</span>
                                    <span class="text-truncate" style="max-width: 170px;"><?= e($st['email']) ?></span>
                                </div>
                            </div>

                            <!-- Individual Attendance Summary Badges -->
                            <div class="row g-2 text-center small mb-3">
                                <div class="col-3">
                                    <div class="p-2 border rounded bg-success-subtle text-success">
                                        <div class="fw-bold fs-6"><?= (int)$st['total_present'] ?></div>
                                        <small style="font-size: 0.68rem;">Present</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 border rounded bg-danger-subtle text-danger">
                                        <div class="fw-bold fs-6"><?= (int)$st['total_absent'] ?></div>
                                        <small style="font-size: 0.68rem;">Absent</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 border rounded bg-warning-subtle text-warning">
                                        <div class="fw-bold fs-6"><?= (int)$st['total_late'] ?></div>
                                        <small style="font-size: 0.68rem;">Late</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 border rounded bg-info-subtle text-info">
                                        <div class="fw-bold fs-6"><?= (int)$st['total_excused'] ?></div>
                                        <small style="font-size: 0.68rem;">Excused</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="text-muted">Attendance Compliance:</span>
                                    <strong class="<?= $st['percentage'] >= 75 ? 'text-success' : 'text-danger' ?>"><?= $st['percentage'] ?>%</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?= $st['percentage'] >= 75 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= $st['percentage'] ?>%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- View Attendance Action Button -->
                        <div class="pt-2 border-top">
                            <a href="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $st['id'] ?>" class="btn btn-primary w-100 fw-bold">
                                <i class="bi bi-calendar2-check-fill me-1"></i> View Attendance Records
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Organized Student Attendance Table View -->
    <div class="card-modern">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-table text-primary me-2"></i> All Students Attendance Directory
            </h5>
            <span class="badge bg-light text-dark border"><?= count($studentsList) ?> Total Students</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle datatable w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Excused</th>
                            <th>Rate (%)</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentsList as $st): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                            <?= strtoupper(substr($st['first_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($st['first_name']) ?> <?= e($st['last_name']) ?></div>
                                            <small class="text-muted"><?= e($st['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= e($st['student_id']) ?></span></td>
                                <td><span class="text-secondary"><?= e($st['year_level_name']) ?></span></td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= e($st['section_name']) ?></span></td>
                                <td><span class="badge bg-success-subtle text-success fs-6"><?= (int)$st['total_present'] ?></span></td>
                                <td><span class="badge bg-danger-subtle text-danger fs-6"><?= (int)$st['total_absent'] ?></span></td>
                                <td><span class="badge bg-warning-subtle text-warning fs-6"><?= (int)$st['total_late'] ?></span></td>
                                <td><span class="badge bg-info-subtle text-info fs-6"><?= (int)$st['total_excused'] ?></span></td>
                                <td>
                                    <span class="fw-bold <?= $st['percentage'] >= 75 ? 'text-success' : 'text-danger' ?>">
                                        <?= $st['percentage'] ?>%
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/admin/attendance.php?student_id=<?= $st['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-calendar-check me-1"></i> View Attendance
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php
$extraScripts = "
<script>
    document.querySelectorAll('.edit-student-record-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.record);
            document.getElementById('edit_rec_id').value = data.id;
            document.getElementById('edit_rec_subject').innerText = data.subject_name + ' (' + data.subject_code + ')';
            document.getElementById('edit_rec_date').innerText = 'Session Date: ' + data.session_date;
            document.getElementById('edit_rec_status').value = data.status;
            document.getElementById('edit_rec_time_in').value = data.time_in || '';
            document.getElementById('edit_rec_time_out').value = data.time_out || '';
            document.getElementById('edit_rec_remarks').value = data.remarks || '';

            const modal = new bootstrap.Modal(document.getElementById('editRecordModal'));
            modal.show();
        });
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
