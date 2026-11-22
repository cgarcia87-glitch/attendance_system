<?php
/**
 * Teacher - Take Attendance Module
 * Supports manual marking, 1-click batch actions, and live QR code camera scanning.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Class.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';

require_role(['teacher', 'admin']);

$user = current_user();
$teacherModel = new Teacher();
$classModel = new ClassModel();
$attendanceModel = new Attendance();
$attendanceController = new AttendanceController();

$teacher = $teacherModel->getByUserId($user['id']);
$teacherId = $teacher['id'] ?? 0;

// Handle AJAX QR Scanner requests
if (isset($_GET['action']) && $_GET['action'] === 'scan_qr') {
    $attendanceController->handleAjaxQrScan();
}

// Handle Form Submissions (Saving Attendance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    $attendanceController->handleSaveAttendance();
}

$assignedClasses = $teacherModel->getAssignedClasses($teacherId);

// Determine active class and session
$selectedClassId = (int)($_GET['class_id'] ?? ($_POST['class_id'] ?? ($assignedClasses[0]['id'] ?? 0)));
$sessionDate = $_GET['session_date'] ?? date('Y-m-d');
$sessionType = $_GET['session_type'] ?? 'Regular Lecture';

$activeClass = null;
$sessionRecords = [];
$sessionId = 0;
$sessionData = null;

if ($selectedClassId) {
    $activeClass = $classModel->getById($selectedClassId);

    // Initialize or retrieve existing session
    $sessionId = $attendanceModel->createSession([
        'class_id'     => $selectedClassId,
        'teacher_id'   => $teacherId,
        'session_date' => $sessionDate,
        'session_type' => $sessionType,
        'notes'        => "Attendance session for {$sessionDate}"
    ]);

    if ($sessionId) {
        $sessionData = $attendanceModel->getSessionById($sessionId);
        $sessionRecords = $attendanceModel->getSessionRecords($sessionId);
    }
}

$pageTitle = 'Take Class Attendance';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Take Student Attendance</h3>
        <p class="text-secondary small mb-0">Record student presence, track late arrivals, and scan digital student QR badges.</p>
    </div>
    <?php if ($sessionData): ?>
        <div>
            <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                <i class="bi bi-clock-history text-primary me-1"></i> Session: <?= format_date($sessionData['session_date']) ?> (<?= e($sessionData['session_type']) ?>)
            </span>
        </div>
    <?php endif; ?>
</div>

<!-- Class & Date Selection Bar -->
<div class="card-modern p-4 mb-4">
    <form method="GET" action="<?= BASE_URL ?>/teacher/take_attendance.php" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label small fw-bold text-secondary">Select Assigned Class & Subject *</label>
            <select name="class_id" class="form-select" required>
                <?php foreach ($assignedClasses as $ac): ?>
                    <option value="<?= $ac['id'] ?>" <?= $selectedClassId == $ac['id'] ? 'selected' : '' ?>>
                        <?= e($ac['class_code']) ?>: <?= e($ac['subject_name']) ?> (<?= e($ac['section_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary">Attendance Date *</label>
            <input type="date" name="session_date" class="form-control" value="<?= e($sessionDate) ?>" required>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary">Session Type</label>
            <select name="session_type" class="form-select">
                <option value="Regular Lecture" <?= $sessionType === 'Regular Lecture' ? 'selected' : '' ?>>Regular Lecture</option>
                <option value="Laboratory" <?= $sessionType === 'Laboratory' ? 'selected' : '' ?>>Laboratory</option>
                <option value="Midterm Exam" <?= $sessionType === 'Midterm Exam' ? 'selected' : '' ?>>Midterm Exam</option>
                <option value="Final Exam" <?= $sessionType === 'Final Exam' ? 'selected' : '' ?>>Final Exam</option>
                <option value="Review" <?= $sessionType === 'Review' ? 'selected' : '' ?>>Review Session</option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-arrow-repeat me-1"></i> Load Session
            </button>
        </div>
    </form>
</div>

<?php if ($activeClass && $sessionId): ?>
    <!-- Top Action Bar & QR Scanner Trigger -->
    <div class="row g-4 mb-4">
        <!-- Live Batch Marking Tools -->
        <div class="col-lg-8">
            <div class="card-modern p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-magic text-primary me-1"></i> Fast Batch Marking Actions</h6>
                        <small class="text-muted">Set default status for all students in this session with one click.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
                        <?= count($sessionRecords) ?> Enrolled
                    </span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-success flex-fill fw-semibold py-2" id="mark-all-present">
                        <i class="bi bi-check-all me-1"></i> Mark All Present
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger flex-fill fw-semibold py-2" id="mark-all-absent">
                        <i class="bi bi-x-circle me-1"></i> Mark All Absent
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning flex-fill fw-semibold py-2" id="mark-all-late">
                        <i class="bi bi-clock-history me-1"></i> Mark All Late
                    </button>
                </div>
            </div>
        </div>

        <!-- QR Scanner Fast Launch -->
        <div class="col-lg-4">
            <div class="card-modern p-3 h-100 text-center bg-light border-primary d-flex flex-column justify-content-center">
                <div class="mb-2">
                    <i class="bi bi-qr-code-scan fs-3 text-primary"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Digital QR Code Check-in</h6>
                <p class="text-muted small mb-3">Instantly verify and mark students via camera or scanner barcode.</p>
                <button type="button" class="btn btn-sm btn-primary rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                    <i class="bi bi-camera me-1"></i> Open QR Scanner
                </button>
            </div>
        </div>
    </div>

    <!-- Attendance Form Table -->
    <form action="<?= BASE_URL ?>/teacher/take_attendance.php" method="POST" id="attendanceForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_attendance">
        <input type="hidden" name="session_id" value="<?= $sessionId ?>">
        <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">

        <div class="card-modern">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="bi bi-card-checklist text-primary me-2"></i> Class Attendance Sheet
                    </h5>
                    <small class="text-muted">
                        <?= e($activeClass['class_code']) ?> &bull; <?= e($activeClass['subject_name']) ?> (Section <?= e($activeClass['section_name']) ?>)
                    </small>
                </div>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-save2-fill me-1"></i> Save Attendance Records
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Gender</th>
                                <th style="min-width: 320px;">Attendance Status</th>
                                <th>Remarks / Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessionRecords)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 text-secondary d-block mb-2"></i>
                                        No students are currently enrolled in this class.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $idx = 1; foreach ($sessionRecords as $r): 
                                    $currentStatus = strtoupper($r['status'] ?? 'PRESENT');
                                    $studentId = $r['student_id'];
                                ?>
                                    <tr id="student_row_<?= $studentId ?>" class="<?= $currentStatus === 'PRESENT' ? 'table-success-subtle' : ($currentStatus === 'ABSENT' ? 'table-danger-subtle' : ($currentStatus === 'LATE' ? 'table-warning-subtle' : 'table-info-subtle')) ?>">
                                        <td class="text-muted fw-bold"><?= $idx++ ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                                    <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= e($r['first_name']) ?> <?= e($r['last_name']) ?></div>
                                                    <small class="text-muted"><?= e($r['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace"><?= e($r['student_number']) ?></span>
                                        </td>
                                        <td><?= e($r['gender']) ?></td>
                                        <td>
                                            <!-- Hidden status input -->
                                            <input type="hidden" name="records[<?= $studentId ?>][status]" id="status_input_<?= $studentId ?>" value="<?= $currentStatus ?>">

                                            <!-- 4 Status Pill Toggle Buttons -->
                                            <div class="attendance-toggle-group">
                                                <button type="button" class="attendance-toggle-btn btn-present <?= $currentStatus === 'PRESENT' ? 'active' : '' ?>" data-student="<?= $studentId ?>" data-status="PRESENT">
                                                    <i class="bi bi-check-circle me-1"></i>Present
                                                </button>
                                                <button type="button" class="attendance-toggle-btn btn-absent <?= $currentStatus === 'ABSENT' ? 'active' : '' ?>" data-student="<?= $studentId ?>" data-status="ABSENT">
                                                    <i class="bi bi-x-circle me-1"></i>Absent
                                                </button>
                                                <button type="button" class="attendance-toggle-btn btn-late <?= $currentStatus === 'LATE' ? 'active' : '' ?>" data-student="<?= $studentId ?>" data-status="LATE">
                                                    <i class="bi bi-clock-history me-1"></i>Late
                                                </button>
                                                <button type="button" class="attendance-toggle-btn btn-excused <?= $currentStatus === 'EXCUSED' ? 'active' : '' ?>" data-student="<?= $studentId ?>" data-status="EXCUSED">
                                                    <i class="bi bi-info-circle me-1"></i>Excused
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="records[<?= $studentId ?>][remarks]" class="form-control form-control-sm" placeholder="Optional remark (e.g. clinic slip)" value="<?= e($r['remarks'] ?? '') ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="close_session" id="closeSessionCheck" value="1">
                    <label class="form-check-label small fw-bold text-dark" for="closeSessionCheck">
                        Close Attendance Session After Saving (Mark as Completed)
                    </label>
                </div>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-save2-fill me-1"></i> Save Attendance Records
                </button>
            </div>
        </div>
    </form>
<?php endif; ?>

<!-- QR SCANNER MODAL -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-qr-code-scan me-2 text-primary"></i> Digital QR Code Attendance Check-in</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <!-- Camera QR Reader Viewport -->
                <div id="qr-reader" class="mb-3"></div>

                <div class="small text-muted mb-3">Position student QR code inside the camera frame or manually scan/type Student ID below:</div>

                <!-- Manual Scan Barcode Box -->
                <div class="input-group mb-3">
                    <input type="text" id="manual_qr_input" class="form-control" placeholder="Scan Barcode or Type Student ID (e.g. STD-2024-001)">
                    <button class="btn btn-primary" type="button" id="manual_scan_btn">
                        <i class="bi bi-check-lg"></i> Check In
                    </button>
                </div>

                <div id="scan-feedback" class="alert alert-secondary py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i> Scanner ready. Point camera at student digital badge.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal" id="stopScannerBtn">Close Scanner</button>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
    let html5QrcodeScanner = null;

    $('#qrScannerModal').on('shown.bs.modal', function () {
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner('qr-reader', {
                fps: 10,
                qrbox: { width: 220, height: 220 },
                rememberLastUsedCamera: true
            });

            html5QrcodeScanner.render(onScanSuccess, onScanError);
        }
    });

    $('#qrScannerModal').on('hidden.bs.modal', function () {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().catch(err => console.error(err));
            html5QrcodeScanner = null;
        }
    });

    function onScanSuccess(decodedText) {
        submitQrScan(decodedText, {$sessionId}, {$selectedClassId}, '" . BASE_URL . "');
    }

    function onScanError(errorMessage) {
        // Ignored for continuous scan loop
    }

    // Manual Scan Handler
    document.getElementById('manual_scan_btn').addEventListener('click', function() {
        const val = document.getElementById('manual_qr_input').value.trim();
        if (val) {
            submitQrScan(val, {$sessionId}, {$selectedClassId}, '" . BASE_URL . "');
            document.getElementById('manual_qr_input').value = '';
        }
    });

    document.getElementById('manual_qr_input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('manual_scan_btn').click();
        }
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
