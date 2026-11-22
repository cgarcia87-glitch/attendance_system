<?php
/**
 * Student Model
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Student {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = []) {
        $sql = "
            SELECT s.*, 
                   c.course_code, c.course_name,
                   y.name AS year_level_name, y.level_numeric,
                   sec.section_name,
                   u.username, u.avatar
            FROM students s
            LEFT JOIN courses c ON s.course_id = c.id
            LEFT JOIN year_levels y ON s.year_level_id = y.id
            LEFT JOIN sections sec ON s.section_id = sec.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = ?";
            $params[] = $filters['course_id'];
        }
        if (!empty($filters['year_level_id'])) {
            $sql .= " AND s.year_level_id = ?";
            $params[] = $filters['year_level_id'];
        }
        if (!empty($filters['section_id'])) {
            $sql .= " AND s.section_id = ?";
            $params[] = $filters['section_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $searchTerm = "%" . $filters['search'] . "%";
            $sql .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY s.last_name ASC, s.first_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   c.course_code, c.course_name,
                   y.name AS year_level_name, y.level_numeric,
                   sec.section_name,
                   u.username, u.avatar
            FROM students s
            LEFT JOIN courses c ON s.course_id = c.id
            LEFT JOIN year_levels y ON s.year_level_id = y.id
            LEFT JOIN sections sec ON s.section_id = sec.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   c.course_code, c.course_name,
                   y.name AS year_level_name,
                   sec.section_name,
                   u.username, u.avatar
            FROM students s
            LEFT JOIN courses c ON s.course_id = c.id
            LEFT JOIN year_levels y ON s.year_level_id = y.id
            LEFT JOIN sections sec ON s.section_id = sec.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function getByStudentId($studentId) {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetch();
    }

    public function create($data) {
        // Generate student ID if not supplied
        if (empty($data['student_id'])) {
            $year = date('Y');
            $random = rand(100, 999);
            $data['student_id'] = "STD-{$year}-{$random}";
        }

        $qrCode = "QR_" . $data['student_id'];

        $stmt = $this->db->prepare("
            INSERT INTO students (
                user_id, student_id, first_name, last_name, email,
                gender, birth_date, course_id, year_level_id, section_id,
                contact_number, address, emergency_contact, qr_code, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['student_id'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['gender'] ?? 'Male',
            $data['birth_date'] ?? null,
            $data['course_id'],
            $data['year_level_id'],
            $data['section_id'],
            $data['contact_number'] ?? null,
            $data['address'] ?? null,
            $data['emergency_contact'] ?? null,
            $qrCode,
            $data['status'] ?? 'active'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [
            'first_name = ?', 'last_name = ?', 'email = ?',
            'gender = ?', 'birth_date = ?', 'course_id = ?',
            'year_level_id = ?', 'section_id = ?', 'contact_number = ?',
            'address = ?', 'emergency_contact = ?', 'status = ?'
        ];
        $values = [
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['gender'] ?? 'Male',
            $data['birth_date'] ?? null,
            $data['course_id'],
            $data['year_level_id'],
            $data['section_id'],
            $data['contact_number'] ?? null,
            $data['address'] ?? null,
            $data['emergency_contact'] ?? null,
            $data['status'] ?? 'active'
        ];

        if (!empty($data['student_id'])) {
            $fields[] = 'student_id = ?';
            $values[] = $data['student_id'];
        }

        $values[] = $id;
        $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete($id) {
        // Also remove user account if linked
        $student = $this->getById($id);
        if ($student && !empty($student['user_id'])) {
            $stmtUser = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmtUser->execute([$student['user_id']]);
        }
        $stmt = $this->db->prepare("DELETE FROM students WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAttendanceStats($studentId, $filters = []) {
        $sql = "
            SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN ar.status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN ar.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN ar.status = 'LATE' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN ar.status = 'EXCUSED' THEN 1 ELSE 0 END) as excused_count
            FROM attendance_records ar
            JOIN attendance_sessions s ON ar.session_id = s.id
            WHERE ar.student_id = ?
        ";
        $params = [$studentId];

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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats = $stmt->fetch();

        $total = (int)($stats['total_records'] ?? 0);
        $present = (int)($stats['present_count'] ?? 0);
        $absent = (int)($stats['absent_count'] ?? 0);
        $late = (int)($stats['late_count'] ?? 0);
        $excused = (int)($stats['excused_count'] ?? 0);

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

    public function getAttendanceHistory($studentId, $filters = []) {
        $sql = "
            SELECT ar.*, 
                   COALESCE(ar.time_in, s.start_time, TIME(ar.marked_at)) AS time_in,
                   COALESCE(ar.time_out, s.end_time) AS time_out,
                   s.session_date, s.session_type, s.start_time, s.end_time,
                   sub.subject_code, sub.subject_name,
                   c.class_code,
                   sec.section_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                   u.full_name AS marked_by_name
            FROM attendance_records ar
            JOIN attendance_sessions s ON ar.session_id = s.id
            JOIN classes c ON ar.class_id = c.id
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN users u ON ar.marked_by = u.id
            WHERE ar.student_id = ?
        ";
        $params = [$studentId];

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
        if (!empty($filters['subject_id'])) {
            $sql .= " AND c.subject_id = ?";
            $params[] = $filters['subject_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND ar.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY s.session_date DESC, ar.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAllWithAttendanceSummary($filters = []) {
        $sql = "
            SELECT s.*, 
                   c.course_code, c.course_name,
                   y.name AS year_level_name, y.level_numeric,
                   sec.section_name,
                   COUNT(ar.id) as total_attendance,
                   SUM(CASE WHEN ar.status = 'PRESENT' THEN 1 ELSE 0 END) as total_present,
                   SUM(CASE WHEN ar.status = 'ABSENT' THEN 1 ELSE 0 END) as total_absent,
                   SUM(CASE WHEN ar.status = 'LATE' THEN 1 ELSE 0 END) as total_late,
                   SUM(CASE WHEN ar.status = 'EXCUSED' THEN 1 ELSE 0 END) as total_excused
            FROM students s
            LEFT JOIN courses c ON s.course_id = c.id
            LEFT JOIN year_levels y ON s.year_level_id = y.id
            LEFT JOIN sections sec ON s.section_id = sec.id
            LEFT JOIN attendance_records ar ON ar.student_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = ?";
            $params[] = $filters['course_id'];
        }
        if (!empty($filters['year_level_id'])) {
            $sql .= " AND s.year_level_id = ?";
            $params[] = $filters['year_level_id'];
        }
        if (!empty($filters['section_id'])) {
            $sql .= " AND s.section_id = ?";
            $params[] = $filters['section_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " GROUP BY s.id ORDER BY s.last_name ASC, s.first_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Calculate attendance percentage per student
        foreach ($results as &$row) {
            $tot = (int)($row['total_attendance'] ?? 0);
            $prs = (int)($row['total_present'] ?? 0);
            $lat = (int)($row['total_late'] ?? 0);
            $exc = (int)($row['total_excused'] ?? 0);
            $row['percentage'] = calculate_attendance_percentage($prs, $lat, $exc, $tot);
        }
        unset($row);

        return $results;
    }

    public function getEnrolledClasses($studentId) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   sub.subject_code, sub.subject_name, sub.units,
                   sec.section_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                   ce.enrollment_date
            FROM class_enrollments ce
            JOIN classes c ON ce.class_id = c.id
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN teachers t ON c.teacher_id = t.id
            WHERE ce.student_id = ? AND ce.status = 'enrolled'
            ORDER BY sub.subject_name ASC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM students");
        return $stmt->fetchColumn();
    }
}
