<?php
/**
 * Class Model (Named ClassModel due to PHP 'class' reserved keyword)
 */

require_once __DIR__ . '/../config/database.php';

class ClassModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ---------------------------------------------------------
    // CLASSES CRUD
    // ---------------------------------------------------------
    public function getAll($filters = []) {
        $sql = "
            SELECT c.*, 
                   sub.subject_code, sub.subject_name, sub.units,
                   sec.section_name,
                   cr.course_code, cr.course_name,
                   y.name as year_level_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                   t.email as teacher_email,
                   COUNT(DISTINCT ce.student_id) as enrolled_count
            FROM classes c
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN courses cr ON sec.course_id = cr.id
            JOIN year_levels y ON sec.year_level_id = y.id
            JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN class_enrollments ce ON c.id = ce.class_id AND ce.status = 'enrolled'
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['teacher_id'])) {
            $sql .= " AND c.teacher_id = ?";
            $params[] = $filters['teacher_id'];
        }
        if (!empty($filters['subject_id'])) {
            $sql .= " AND c.subject_id = ?";
            $params[] = $filters['subject_id'];
        }
        if (!empty($filters['section_id'])) {
            $sql .= " AND c.section_id = ?";
            $params[] = $filters['section_id'];
        }
        if (!empty($filters['academic_year'])) {
            $sql .= " AND c.academic_year = ?";
            $params[] = $filters['academic_year'];
        }
        if (!empty($filters['semester'])) {
            $sql .= " AND c.semester = ?";
            $params[] = $filters['semester'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $term = "%" . $filters['search'] . "%";
            $sql .= " AND (c.class_code LIKE ? OR sub.subject_name LIKE ? OR sec.section_name LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " GROUP BY c.id ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   sub.subject_code, sub.subject_name, sub.units,
                   sec.section_name, sec.course_id, sec.year_level_id,
                   cr.course_code, cr.course_name,
                   y.name as year_level_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                   t.email as teacher_email,
                   COUNT(DISTINCT ce.student_id) as enrolled_count
            FROM classes c
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN courses cr ON sec.course_id = cr.id
            JOIN year_levels y ON sec.year_level_id = y.id
            JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN class_enrollments ce ON c.id = ce.class_id AND ce.status = 'enrolled'
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        if (empty($data['class_code'])) {
            $data['class_code'] = "CLS-" . strtoupper(bin2hex(random_bytes(3)));
        }

        $stmt = $this->db->prepare("
            INSERT INTO classes (
                class_code, subject_id, section_id, teacher_id,
                academic_year, semester, room, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['class_code'],
            $data['subject_id'],
            $data['section_id'],
            $data['teacher_id'],
            $data['academic_year'] ?? '2025-2026',
            $data['semester'] ?? '1st Semester',
            $data['room'] ?? 'Room 301',
            $data['status'] ?? 'active'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE classes SET 
                class_code = ?,
                subject_id = ?,
                section_id = ?,
                teacher_id = ?,
                academic_year = ?,
                semester = ?,
                room = ?,
                status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['class_code'],
            $data['subject_id'],
            $data['section_id'],
            $data['teacher_id'],
            $data['academic_year'],
            $data['semester'],
            $data['room'],
            $data['status'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM classes WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ---------------------------------------------------------
    // ENROLLMENTS
    // ---------------------------------------------------------
    public function getEnrolledStudents($classId) {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   c.course_code, 
                   sec.section_name,
                   ce.enrollment_date, ce.status as enrollment_status,
                   u.avatar
            FROM class_enrollments ce
            JOIN students s ON ce.student_id = s.id
            JOIN sections sec ON s.section_id = sec.id
            JOIN courses c ON s.course_id = c.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE ce.class_id = ? AND ce.status = 'enrolled'
            ORDER BY s.last_name ASC, s.first_name ASC
        ");
        $stmt->execute([$classId]);
        return $stmt->fetchAll();
    }

    public function enrollStudent($classId, $studentId) {
        $stmt = $this->db->prepare("
            INSERT INTO class_enrollments (class_id, student_id, enrollment_date, status)
            VALUES (?, ?, CURDATE(), 'enrolled')
            ON DUPLICATE KEY UPDATE status = 'enrolled'
        ");
        return $stmt->execute([$classId, $studentId]);
    }

    public function removeStudent($classId, $studentId) {
        $stmt = $this->db->prepare("DELETE FROM class_enrollments WHERE class_id = ? AND student_id = ?");
        return $stmt->execute([$classId, $studentId]);
    }

    // ---------------------------------------------------------
    // SCHEDULES
    // ---------------------------------------------------------
    public function getSchedules($classId = null) {
        if ($classId) {
            $stmt = $this->db->prepare("
                SELECT s.*, c.class_code, sub.subject_name, sec.section_name,
                       CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
                FROM schedules s
                JOIN classes c ON s.class_id = c.id
                JOIN subjects sub ON c.subject_id = sub.id
                JOIN sections sec ON c.section_id = sec.id
                JOIN teachers t ON c.teacher_id = t.id
                WHERE s.class_id = ?
                ORDER BY FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time ASC
            ");
            $stmt->execute([$classId]);
        } else {
            $stmt = $this->db->query("
                SELECT s.*, c.class_code, sub.subject_name, sec.section_name,
                       CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
                FROM schedules s
                JOIN classes c ON s.class_id = c.id
                JOIN subjects sub ON c.subject_id = sub.id
                JOIN sections sec ON c.section_id = sec.id
                JOIN teachers t ON c.teacher_id = t.id
                ORDER BY FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time ASC
            ");
        }
        return $stmt->fetchAll();
    }

    public function addSchedule($data) {
        $stmt = $this->db->prepare("
            INSERT INTO schedules (class_id, day_of_week, start_time, end_time, room)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['class_id'],
            $data['day_of_week'],
            $data['start_time'],
            $data['end_time'],
            $data['room'] ?? 'Room 301'
        ]);
    }

    public function deleteSchedule($id) {
        $stmt = $this->db->prepare("DELETE FROM schedules WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ---------------------------------------------------------
    // COURSES
    // ---------------------------------------------------------
    public function getCourses() {
        $stmt = $this->db->query("
            SELECT c.*, COUNT(DISTINCT s.id) as student_count, COUNT(DISTINCT sec.id) as section_count
            FROM courses c
            LEFT JOIN sections sec ON sec.course_id = c.id
            LEFT JOIN students s ON s.course_id = c.id
            GROUP BY c.id
            ORDER BY c.course_code ASC
        ");
        return $stmt->fetchAll();
    }

    public function addCourse($data) {
        $stmt = $this->db->prepare("
            INSERT INTO courses (course_code, course_name, department, description, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            strtoupper(trim($data['course_code'])),
            trim($data['course_name']),
            $data['department'] ?? 'College of Information Technology',
            $data['description'] ?? null,
            $data['status'] ?? 'active'
        ]);
    }

    public function updateCourse($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE courses SET 
                course_code = ?,
                course_name = ?,
                department = ?,
                description = ?,
                status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            strtoupper(trim($data['course_code'])),
            trim($data['course_name']),
            $data['department'] ?? 'College of Information Technology',
            $data['description'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
    }

    public function deleteCourse($id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ---------------------------------------------------------
    // SECTIONS & YEAR LEVELS
    // ---------------------------------------------------------
    public function getYearLevels() {
        $stmt = $this->db->query("SELECT * FROM year_levels ORDER BY level_numeric ASC");
        return $stmt->fetchAll();
    }

    public function getSections($filters = []) {
        $sql = "
            SELECT sec.*, c.course_code, c.course_name, y.name as year_level_name,
                   COUNT(DISTINCT s.id) as student_count
            FROM sections sec
            JOIN courses c ON sec.course_id = c.id
            JOIN year_levels y ON sec.year_level_id = y.id
            LEFT JOIN students s ON s.section_id = sec.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['course_id'])) {
            $sql .= " AND sec.course_id = ?";
            $params[] = $filters['course_id'];
        }
        if (!empty($filters['year_level_id'])) {
            $sql .= " AND sec.year_level_id = ?";
            $params[] = $filters['year_level_id'];
        }

        $sql .= " GROUP BY sec.id ORDER BY sec.section_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function addSection($data) {
        $stmt = $this->db->prepare("
            INSERT INTO sections (section_name, course_id, year_level_id, capacity, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            trim($data['section_name']),
            $data['course_id'],
            $data['year_level_id'],
            $data['capacity'] ?? 40,
            $data['status'] ?? 'active'
        ]);
    }

    public function updateSection($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE sections SET 
                section_name = ?,
                course_id = ?,
                year_level_id = ?,
                capacity = ?,
                status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['section_name']),
            $data['course_id'],
            $data['year_level_id'],
            $data['capacity'] ?? 40,
            $data['status'] ?? 'active',
            $id
        ]);
    }

    public function deleteSection($id) {
        $stmt = $this->db->prepare("DELETE FROM sections WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM classes WHERE status = 'active'");
        return $stmt->fetchColumn();
    }

    public function countSections() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM sections WHERE status = 'active'");
        return $stmt->fetchColumn();
    }
}
