<?php
/**
 * Report Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Report.php';

class ReportController {
    private $reportModel;
    private $db;

    public function __construct() {
        $this->reportModel = new Report();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleExport() {
        require_role(['admin', 'teacher', 'student']);

        $format = $_GET['format'] ?? 'csv';
        $filters = [
            'period'        => $_GET['period'] ?? '',
            'date_from'     => $_GET['date_from'] ?? '',
            'date_to'       => $_GET['date_to'] ?? '',
            'student_id'    => $_GET['student_id'] ?? '',
            'teacher_id'    => $_GET['teacher_id'] ?? '',
            'subject_id'    => $_GET['subject_id'] ?? '',
            'course_id'     => $_GET['course_id'] ?? '',
            'year_level_id' => $_GET['year_level_id'] ?? '',
            'section_id'    => $_GET['section_id'] ?? '',
            'status'        => $_GET['status'] ?? ''
        ];

        // Security: If student, force their student_id
        if (current_role() === 'student') {
            $filters['student_id'] = $_SESSION['ref_id'];
        }

        // If teacher, and not admin, restrict to their classes if desired
        if (current_role() === 'teacher') {
            $filters['teacher_id'] = $_SESSION['ref_id'];
        }

        $records = $this->reportModel->getAttendanceReport($filters);

        if ($format === 'csv' || $format === 'excel') {
            $filename = 'attendance_report_' . date('Ymd_His') . ($format === 'excel' ? '.csv' : '.csv');
            $this->reportModel->exportCsv($records, $filename);
            exit;
        }
    }
}
