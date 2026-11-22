<?php
/**
 * Attendance Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Class.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Teacher.php';

class AttendanceController {
    private $attendanceModel;
    private $classModel;
    private $studentModel;
    private $teacherModel;
    private $db;

    public function __construct() {
        $this->attendanceModel = new Attendance();
        $this->classModel = new ClassModel();
        $this->studentModel = new Student();
        $this->teacherModel = new Teacher();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleSaveAttendance() {
        require_role(['admin', 'teacher']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/teacher/take_attendance.php')));
            exit;
        }

        $sessionId = (int)($_POST['session_id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        $records = $_POST['records'] ?? []; // format: [student_id => ['status' => 'PRESENT', 'remarks' => '...']]
        $closeSession = isset($_POST['close_session']);

        if (!$sessionId || !$classId) {
            flash('error', 'Session or class identifier missing.');
            header('Location: ' . BASE_URL . '/teacher/take_attendance.php');
            exit;
        }

        try {
            $this->attendanceModel->recordAttendanceBatch($sessionId, $classId, $records, $_SESSION['user_id']);

            if ($closeSession) {
                $this->attendanceModel->closeSession($sessionId);
            }

            log_activity($this->db, $_SESSION['user_id'], 'ATTENDANCE_SAVED', "Saved attendance for session #{$sessionId} (Class #{$classId}).");
            flash('success', 'Attendance records have been successfully saved!');
        } catch (Exception $e) {
            flash('error', 'Error saving attendance: ' . $e->getMessage());
        }

        // Redirect back to session view or history
        $redirectUrl = (current_role() === 'admin') 
            ? BASE_URL . "/admin/attendance.php?session_id={$sessionId}" 
            : BASE_URL . "/teacher/take_attendance.php?session_id={$sessionId}";

        header("Location: {$redirectUrl}");
        exit;
    }

    public function handleAjaxQrScan() {
        require_role(['admin', 'teacher']);
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);
        $qrCode = trim($input['qr_code'] ?? '');
        $sessionId = (int)($input['session_id'] ?? 0);
        $classId = (int)($input['class_id'] ?? 0);
        $status = strtoupper($input['status'] ?? 'PRESENT');

        if (empty($qrCode) || empty($sessionId) || empty($classId)) {
            send_json(['success' => false, 'message' => 'Missing QR code or session ID.'], 400);
        }

        // Find student by qr_code or student_id
        $stmt = $this->db->prepare("SELECT * FROM students WHERE qr_code = ? OR student_id = ?");
        $stmt->execute([$qrCode, $qrCode]);
        $student = $stmt->fetch();

        if (!$student) {
            send_json(['success' => false, 'message' => "Student not found with code: {$qrCode}"], 404);
        }

        // Check if student is enrolled in this class
        $stmtEnroll = $this->db->prepare("SELECT id FROM class_enrollments WHERE class_id = ? AND student_id = ? AND status = 'enrolled'");
        $stmtEnroll->execute([$classId, $student['id']]);
        if (!$stmtEnroll->fetch()) {
            send_json(['success' => false, 'message' => "{$student['first_name']} {$student['last_name']} is NOT enrolled in this class."], 403);
        }

        try {
            $this->attendanceModel->recordSingleAttendance($sessionId, $classId, $student['id'], $status, 'Recorded via QR Scanner', $_SESSION['user_id']);
            log_activity($this->db, $_SESSION['user_id'], 'ATTENDANCE_QR_SCAN', "Marked {$student['first_name']} {$student['last_name']} as {$status} via QR.");
            
            // Send student in-app notification
            create_notification($this->db, $student['user_id'], 'Attendance Recorded', "You have been marked {$status} for today's session via QR scan.", 'success');

            send_json([
                'success' => true,
                'message' => "Successfully recorded {$status} for {$student['first_name']} {$student['last_name']} ({$student['student_id']})!",
                'student' => [
                    'id'         => $student['id'],
                    'student_id' => $student['student_id'],
                    'name'       => "{$student['first_name']} {$student['last_name']}",
                    'status'     => $status,
                    'time'       => date('h:i A')
                ]
            ]);
        } catch (Exception $e) {
            send_json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function handleUpdateRecord() {
        require_role(['admin', 'teacher']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
            exit;
        }

        $recordId = (int)($_POST['record_id'] ?? 0);
        $status = strtoupper($_POST['status'] ?? 'PRESENT');
        $remarks = trim($_POST['remarks'] ?? '');
        $timeIn = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
        $timeOut = !empty($_POST['time_out']) ? $_POST['time_out'] : null;

        if (!$recordId) {
            flash('error', 'Record ID required.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
            exit;
        }

        $this->attendanceModel->updateSingleRecord($recordId, $status, $remarks, $_SESSION['user_id'], $timeIn, $timeOut);
        log_activity($this->db, $_SESSION['user_id'], 'ATTENDANCE_EDIT', "Updated attendance record #{$recordId} to {$status}.");
        flash('success', 'Student attendance record updated successfully.');

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
        exit;
    }

    public function handleAddIndividualRecord() {
        require_role(['admin', 'teacher']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
            exit;
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        $sessionDate = $_POST['session_date'] ?? date('Y-m-d');
        $status = strtoupper($_POST['status'] ?? 'PRESENT');
        $timeIn = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
        $timeOut = !empty($_POST['time_out']) ? $_POST['time_out'] : null;
        $remarks = trim($_POST['remarks'] ?? 'Individual record');

        if (!$studentId || !$classId) {
            flash('error', 'Student and Class selection are required.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
            exit;
        }

        try {
            $this->attendanceModel->addIndividualRecord(
                $studentId,
                $classId,
                $sessionDate,
                $status,
                $timeIn,
                $timeOut,
                $remarks,
                $_SESSION['user_id']
            );
            log_activity($this->db, $_SESSION['user_id'], 'INDIVIDUAL_ATTENDANCE_ADD', "Added attendance for student #{$studentId} ({$status}).");
            flash('success', 'Attendance record logged successfully for this student.');
        } catch (Exception $e) {
            flash('error', 'Error recording attendance: ' . $e->getMessage());
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
        exit;
    }

    public function handleDeleteRecord() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
            exit;
        }

        $recordId = (int)($_POST['record_id'] ?? 0);
        $this->attendanceModel->deleteRecord($recordId);
        log_activity($this->db, $_SESSION['user_id'], 'ATTENDANCE_RECORD_DELETE', "Deleted attendance record #{$recordId}.");
        flash('success', 'Attendance record deleted.');

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
        exit;
    }
}
