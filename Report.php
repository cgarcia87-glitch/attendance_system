<?php
/**
 * Report Model
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Report {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAttendanceReport($filters = []) {
        $sql = "
            SELECT ar.*,
                   s.session_date, s.session_type, s.start_time, s.end_time,
                   st.student_id as student_number,
                   CONCAT(st.first_name, ' ', st.last_name) as student_name,
                   st.gender,
                   sub.subject_code, sub.subject_name,
                   c.class_code,
                   sec.section_name,
                   cr.course_code, cr.course_name,
                   y.name as year_level_name,
                   CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
                   u.username as recorder_username
            FROM attendance_records ar
            JOIN attendance_sessions s ON ar.session_id = s.id
            JOIN students st ON ar.student_id = st.id
            JOIN classes c ON ar.class_id = c.id
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN courses cr ON sec.course_id = cr.id
            JOIN year_levels y ON sec.year_level_id = y.id
            JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN users u ON ar.marked_by = u.id
            WHERE 1=1
        ";
        $params = [];

        // Daily / Weekly / Monthly / Range Filter
        if (!empty($filters['period'])) {
            switch ($filters['period']) {
                case 'daily':
                    $sql .= " AND s.session_date = CURDATE()";
                    break;
                case 'weekly':
                    $sql .= " AND YEARWEEK(s.session_date, 1) = YEARWEEK(CURDATE(), 1)";
                    break;
                case 'monthly':
                    $sql .= " AND MONTH(s.session_date) = MONTH(CURDATE()) AND YEAR(s.session_date) = YEAR(CURDATE())";
                    break;
            }
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND s.session_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND s.session_date <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['student_id'])) {
            $sql .= " AND ar.student_id = ?";
            $params[] = $filters['student_id'];
        }
        if (!empty($filters['teacher_id'])) {
            $sql .= " AND c.teacher_id = ?";
            $params[] = $filters['teacher_id'];
        }
        if (!empty($filters['subject_id'])) {
            $sql .= " AND c.subject_id = ?";
            $params[] = $filters['subject_id'];
        }
        if (!empty($filters['course_id'])) {
            $sql .= " AND sec.course_id = ?";
            $params[] = $filters['course_id'];
        }
        if (!empty($filters['year_level_id'])) {
            $sql .= " AND sec.year_level_id = ?";
            $params[] = $filters['year_level_id'];
        }
        if (!empty($filters['section_id'])) {
            $sql .= " AND c.section_id = ?";
            $params[] = $filters['section_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND ar.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY s.session_date DESC, sub.subject_name ASC, st.last_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSummaryStats($records) {
        $total = count($records);
        $present = 0;
        $absent = 0;
        $late = 0;
        $excused = 0;

        foreach ($records as $r) {
            switch ($r['status']) {
                case 'PRESENT': $present++; break;
                case 'ABSENT': $absent++; break;
                case 'LATE': $late++; break;
                case 'EXCUSED': $excused++; break;
            }
        }

        $percentage = calculate_attendance_percentage($present, $late, $excused, $total);

        return [
            'total'      => $total,
            'present'    => $present,
            'absent'     => $absent,
            'late'       => $late,
            'excused'    => $excused,
            'percentage' => $percentage
        ];
    }

    public function exportCsv($records, $filename = 'attendance_report.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");

        // Header Row
        fputcsv($output, [
            'Record ID', 'Student ID', 'Student Name', 'Gender',
            'Course', 'Year & Section', 'Subject Code', 'Subject Name',
            'Class Code', 'Instructor', 'Session Date', 'Session Type',
            'Status', 'Remarks', 'Recorded By', 'Timestamp'
        ]);

        foreach ($records as $r) {
            fputcsv($output, [
                $r['id'],
                $r['student_number'],
                $r['student_name'],
                $r['gender'],
                $r['course_code'],
                $r['section_name'],
                $r['subject_code'],
                $r['subject_name'],
                $r['class_code'],
                $r['teacher_name'],
                $r['session_date'],
                $r['session_type'],
                $r['status'],
                $r['remarks'] ?? '',
                $r['recorder_username'] ?? 'System',
                $r['marked_at']
            ]);
        }

        fclose($output);
        exit;
    }
}
