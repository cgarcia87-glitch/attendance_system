<?php
/**
 * Teacher Model
 */

require_once __DIR__ . '/../config/database.php';

class Teacher {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = []) {
        $sql = "
            SELECT t.*, u.username, u.avatar,
                   COUNT(DISTINCT c.id) as total_classes,
                   COUNT(DISTINCT ce.student_id) as total_students
            FROM teachers t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN classes c ON c.teacher_id = t.id AND c.status = 'active'
            LEFT JOIN class_enrollments ce ON ce.class_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['department'])) {
            $sql .= " AND t.department = ?";
            $params[] = $filters['department'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $term = "%" . $filters['search'] . "%";
            $sql .= " AND (t.teacher_id LIKE ? OR t.first_name LIKE ? OR t.last_name LIKE ? OR t.email LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " GROUP BY t.id ORDER BY t.last_name ASC, t.first_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.username, u.avatar
            FROM teachers t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.username, u.avatar
            FROM teachers t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function getByTeacherId($teacherId) {
        $stmt = $this->db->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$teacherId]);
        return $stmt->fetch();
    }

    public function create($data) {
        if (empty($data['teacher_id'])) {
            $year = date('Y');
            $random = rand(100, 999);
            $data['teacher_id'] = "TCH-{$year}-{$random}";
        }

        $stmt = $this->db->prepare("
            INSERT INTO teachers (
                user_id, teacher_id, first_name, last_name, email,
                contact_number, department, qualification, status, date_hired
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['teacher_id'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['contact_number'] ?? null,
            $data['department'] ?? 'Information Technology',
            $data['qualification'] ?? 'Master of Science in Information Technology',
            $data['status'] ?? 'active',
            $data['date_hired'] ?? date('Y-m-d')
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [
            'first_name = ?', 'last_name = ?', 'email = ?',
            'contact_number = ?', 'department = ?', 'qualification = ?',
            'status = ?'
        ];
        $values = [
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['contact_number'] ?? null,
            $data['department'] ?? 'Information Technology',
            $data['qualification'] ?? '',
            $data['status'] ?? 'active'
        ];

        if (!empty($data['teacher_id'])) {
            $fields[] = 'teacher_id = ?';
            $values[] = $data['teacher_id'];
        }
        if (!empty($data['date_hired'])) {
            $fields[] = 'date_hired = ?';
            $values[] = $data['date_hired'];
        }

        $values[] = $id;
        $sql = "UPDATE teachers SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete($id) {
        $teacher = $this->getById($id);
        if ($teacher && !empty($teacher['user_id'])) {
            $stmtUser = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmtUser->execute([$teacher['user_id']]);
        }
        $stmt = $this->db->prepare("DELETE FROM teachers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAssignedClasses($teacherId) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   sub.subject_code, sub.subject_name, sub.units,
                   sec.section_name,
                   cr.course_code, cr.course_name,
                   y.name as year_level_name,
                   COUNT(ce.id) as enrolled_count
            FROM classes c
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN courses cr ON sec.course_id = cr.id
            JOIN year_levels y ON sec.year_level_id = y.id
            LEFT JOIN class_enrollments ce ON c.id = ce.class_id AND ce.status = 'enrolled'
            WHERE c.teacher_id = ?
            GROUP BY c.id
            ORDER BY sub.subject_name ASC
        ");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM teachers");
        return $stmt->fetchColumn();
    }
}
