<?php
/**
 * Attendance Model
 */

require_once __DIR__ . '/../config/database.php';

class Attendance {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ---------------------------------------------------------
    // SESSIONS
    // ---------------------------------------------------------
    public function createSession($data) {
        // Check if session already exists for class + date + type
        $existing = $this->getSessionByClassAndDate($data['class_id'], $data['session_date'], $data['session_type'] ?? 'Regular Lecture');
        if ($existing) {
            return $existing['id'];
        }

        $qrToken = "QR_" . strtoupper(bin2hex(random_bytes(6)));

        $stmt = $this->db->prepare("
            INSERT INTO attendance_sessions (
                class_id, teacher_id, session_date, start_time, end_time,
                session_type, qr_token, notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['class_id'],
            $data['teacher_id'],
            $data['session_date'],
            $data['start_time'] ?? date('H:i:s'),
            $data['end_time'] ?? null,
            $data['session_type'] ?? 'Regular Lecture',
            $qrToken,
            $data['notes'] ?? null,
            $data['status'] ?? 'open'
        ]);
        return $this->db->lastInsertId();
    }

    public function getSessionById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   c.class_code, c.room, c.academic_year, c.semester,
                   sub.subject_code, sub.subject_name,
                   sec.section_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                   t.email as teacher_email
            FROM attendance_sessions s
            JOIN classes c ON s.class_id = c.id
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN teachers t ON s.teacher_id = t.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getSessionByClassAndDate($classId, $date, $sessionType = 'Regular Lecture') {
        $stmt = $this->db->prepare("
            SELECT * FROM attendance_sessions 
            WHERE class_id = ? AND session_date = ? AND session_type = ?
            LIMIT 1
        ");
        $stmt->execute([$classId, $date, $sessionType]);
        return $stmt->fetch();
    }

    public function closeSession($id) {
        $stmt = $this->db->prepare("UPDATE attendance_sessions SET status = 'closed', end_time = CURTIME() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ---------------------------------------------------------
    // RECORD ATTENDANCE
    // ---------------------------------------------------------
    public function recordAttendanceBatch($sessionId, $classId, $records, $markedByUserId) {
        $session = $this->getSessionById($sessionId);
        $defaultTimeIn = $session['start_time'] ?? date('H:i:s');
        $defaultTimeOut = $session['end_time'] ?? date('H:i:s', strtotime('+90 minutes'));

        $sql = "
            INSERT INTO attendance_records (session_id, student_id, class_id, status, time_in, time_out, remarks, marked_by, marked_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                time_in = VALUES(time_in),
                time_out = VALUES(time_out),
                remarks = VALUES(remarks),
                marked_by = VALUES(marked_by),
                updated_at = NOW()
        ";
        $stmt = $this->db->prepare($sql);

        $this->db->beginTransaction();
        try {
            foreach ($records as $studentId => $record) {
                $status = strtoupper($record['status'] ?? 'PRESENT');
                $remarks = $record['remarks'] ?? null;
                $timeIn = !empty($record['time_in']) ? $record['time_in'] : ($status === 'ABSENT' ? null : $defaultTimeIn);
                $timeOut = !empty($record['time_out']) ? $record['time_out'] : ($status === 'ABSENT' ? null : $defaultTimeOut);

                $stmt->execute([
                    $sessionId,
                    $studentId,
                    $classId,
                    $status,
                    $timeIn,
                    $timeOut,
                    $remarks,
                    $markedByUserId
                ]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function recordSingleAttendance($sessionId, $classId, $studentId, $status, $remarks, $markedByUserId, $timeIn = null, $timeOut = null) {
        if ($timeIn === null) $timeIn = date('H:i:s');
        if ($timeOut === null) $timeOut = date('H:i:s', strtotime('+90 minutes'));

        $stmt = $this->db->prepare("
            INSERT INTO attendance_records (session_id, student_id, class_id, status, time_in, time_out, remarks, marked_by, marked_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                time_in = VALUES(time_in),
                time_out = VALUES(time_out),
                remarks = VALUES(remarks),
                marked_by = VALUES(marked_by),
                updated_at = NOW()
        ");
        return $stmt->execute([
            $sessionId,
            $studentId,
            $classId,
            strtoupper($status),
            $timeIn,
            $timeOut,
            $remarks,
            $markedByUserId
        ]);
    }

    public function addIndividualRecord($studentId, $classId, $sessionDate, $status, $timeIn = null, $timeOut = null, $remarks = null, $userId = null) {
        // Resolve teacher for class
        $stmtC = $this->db->prepare("SELECT teacher_id FROM classes WHERE id = ?");
        $stmtC->execute([$classId]);
        $teacherId = $stmtC->fetchColumn() ?: 1;

        $sessionId = $this->createSession([
            'class_id'     => $classId,
            'teacher_id'   => $teacherId,
            'session_date' => $sessionDate,
            'session_type' => 'Regular Lecture',
            'notes'        => 'Individual attendance entry'
        ]);

        return $this->recordSingleAttendance($sessionId, $classId, $studentId, $status, $remarks, $userId, $timeIn, $timeOut);
    }

    public function getSessionRecords($sessionId) {
        $session = $this->getSessionById($sessionId);
        if (!$session) return [];

        // Fetch enrolled students and join their recorded attendance for this session
        $stmt = $this->db->prepare("
            SELECT st.id as student_id, st.student_id as student_number,
                   st.first_name, st.last_name, st.gender, st.email,
                   ar.id as record_id,
                   COALESCE(ar.status, 'PRESENT') as status,
                   COALESCE(ar.time_in, TIME(ar.marked_at)) as time_in,
                   ar.time_out,
                   ar.remarks,
                   ar.marked_at,
                   u.avatar
            FROM class_enrollments ce
            JOIN students st ON ce.student_id = st.id
            LEFT JOIN users u ON st.user_id = u.id
            LEFT JOIN attendance_records ar ON ar.student_id = st.id AND ar.session_id = ?
            WHERE ce.class_id = ? AND ce.status = 'enrolled'
            ORDER BY st.last_name ASC, st.first_name ASC
        ");
        $stmt->execute([$sessionId, $session['class_id']]);
        $records = $stmt->fetchAll();

        foreach ($records as &$r) {
            if (empty($r['time_in'])) {
                $r['time_in'] = !empty($session['start_time']) ? $session['start_time'] : date('H:i:s');
            }
            if (empty($r['time_out']) && !empty($session['end_time'])) {
                $r['time_out'] = $session['end_time'];
            }
        }
        unset($r);

        return $records;
    }

    public function updateSingleRecord($id, $status, $remarks, $userId, $timeIn = null, $timeOut = null) {
        $fields = ["status = ?", "remarks = ?", "marked_by = ?", "updated_at = NOW()"];
        $params = [strtoupper($status), $remarks, $userId];

        if ($timeIn !== null) {
            $fields[] = "time_in = ?";
            $params[] = $timeIn ?: null;
        }
        if ($timeOut !== null) {
            $fields[] = "time_out = ?";
            $params[] = $timeOut ?: null;
        }

        $params[] = $id;
        $sql = "UPDATE attendance_records SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteRecord($id) {
        $stmt = $this->db->prepare("DELETE FROM attendance_records WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ---------------------------------------------------------
    // DASHBOARD & ANALYTICS QUERIES
    // ---------------------------------------------------------
    public function getGlobalStats() {
        $today = date('Y-m-d');

        // Total Students
        $stmt = $this->db->query("SELECT COUNT(*) FROM students WHERE status = 'active'");
        $totalStudents = (int)$stmt->fetchColumn();

        // Total Teachers
        $stmt = $this->db->query("SELECT COUNT(*) FROM teachers WHERE status = 'active'");
        $totalTeachers = (int)$stmt->fetchColumn();

        // Total Classes
        $stmt = $this->db->query("SELECT COUNT(*) FROM classes WHERE status = 'active'");
        $totalClasses = (int)$stmt->fetchColumn();

        // Total Attendance Records
        $stmt = $this->db->query("SELECT COUNT(*) FROM attendance_records");
        $totalAttendance = (int)$stmt->fetchColumn();

        // Today's Stats
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_today,
                SUM(CASE WHEN ar.status = 'PRESENT' THEN 1 ELSE 0 END) as present_today,
                SUM(CASE WHEN ar.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_today,
                SUM(CASE WHEN ar.status = 'LATE' THEN 1 ELSE 0 END) as late_today,
                SUM(CASE WHEN ar.status = 'EXCUSED' THEN 1 ELSE 0 END) as excused_today
            FROM attendance_records ar
            JOIN attendance_sessions s ON ar.session_id = s.id
            WHERE s.session_date = ?
        ");
        $stmt->execute([$today]);
        $todayStats = $stmt->fetch();

        return [
            'total_students'   => $totalStudents,
            'total_teachers'   => $totalTeachers,
            'total_classes'    => $totalClasses,
            'total_attendance' => $totalAttendance,
            'present_today'    => (int)($todayStats['present_today'] ?? 0),
            'absent_today'     => (int)($todayStats['absent_today'] ?? 0),
            'late_today'       => (int)($todayStats['late_today'] ?? 0),
            'excused_today'    => (int)($todayStats['excused_today'] ?? 0),
            'total_today'      => (int)($todayStats['total_today'] ?? 0)
        ];
    }

    public function getTeacherStats($teacherId) {
        $today = date('Y-m-d');

        // Assigned Classes Count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = ? AND status = 'active'");
        $stmt->execute([$teacherId]);
        $assignedClasses = (int)$stmt->fetchColumn();

        // Total Unique Students in Assigned Classes
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT ce.student_id)
            FROM classes c
            JOIN class_enrollments ce ON c.id = ce.class_id
            WHERE c.teacher_id = ? AND c.status = 'active' AND ce.status = 'enrolled'
        ");
        $stmt->execute([$teacherId]);
        $totalStudents = (int)$stmt->fetchColumn();

        // Teacher's Today's Attendance Stats
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_today,
                SUM(CASE WHEN ar.status = 'PRESENT' THEN 1 ELSE 0 END) as present_today,
                SUM(CASE WHEN ar.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_today,
                SUM(CASE WHEN ar.status = 'LATE' THEN 1 ELSE 0 END) as late_today,
                SUM(CASE WHEN ar.status = 'EXCUSED' THEN 1 ELSE 0 END) as excused_today
            FROM attendance_records ar
            JOIN attendance_sessions s ON ar.session_id = s.id
            WHERE s.teacher_id = ? AND s.session_date = ?
        ");
        $stmt->execute([$teacherId, $today]);
        $todayStats = $stmt->fetch();

        return [
            'assigned_classes' => $assignedClasses,
            'total_students'   => $totalStudents,
            'present_today'    => (int)($todayStats['present_today'] ?? 0),
            'absent_today'     => (int)($todayStats['absent_today'] ?? 0),
            'late_today'       => (int)($todayStats['late_today'] ?? 0),
            'excused_today'    => (int)($todayStats['excused_today'] ?? 0),
            'total_today'      => (int)($todayStats['total_today'] ?? 0)
        ];
    }

    public function getDailyTrends($days = 7, $teacherId = null) {
        $sql = "
            SELECT s.session_date,
                   SUM(CASE WHEN ar.status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
                   SUM(CASE WHEN ar.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count,
                   SUM(CASE WHEN ar.status = 'LATE' THEN 1 ELSE 0 END) as late_count,
                   SUM(CASE WHEN ar.status = 'EXCUSED' THEN 1 ELSE 0 END) as excused_count
            FROM attendance_records ar
            JOIN attendance_sessions s ON ar.session_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if ($teacherId) {
            $sql .= " AND s.teacher_id = ?";
            $params[] = $teacherId;
        }

        $sql .= " GROUP BY s.session_date ORDER BY s.session_date ASC LIMIT " . (int)$days;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAttendanceByStatus($teacherId = null) {
        $sql = "
            SELECT ar.status, COUNT(*) as count
            FROM attendance_records ar
            JOIN attendance_sessions s ON ar.session_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if ($teacherId) {
            $sql .= " AND s.teacher_id = ?";
            $params[] = $teacherId;
        }

        $sql .= " GROUP BY ar.status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAttendanceByCourse() {
        $sql = "
            SELECT cr.course_code,
                   COUNT(ar.id) as total_records,
                   SUM(CASE WHEN ar.status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
                   SUM(CASE WHEN ar.status = 'LATE' THEN 1 ELSE 0 END) as late_count,
                   SUM(CASE WHEN ar.status = 'EXCUSED' THEN 1 ELSE 0 END) as excused_count
            FROM attendance_records ar
            JOIN classes c ON ar.class_id = c.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN courses cr ON sec.course_id = cr.id
            GROUP BY cr.id
            ORDER BY cr.course_code ASC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getRecentRecords($limit = 10, $teacherId = null) {
        $sql = "
            SELECT ar.*,
                   s.session_date, s.session_type,
                   st.student_id as student_number,
                   CONCAT(st.first_name, ' ', st.last_name) as student_name,
                   sub.subject_code, sub.subject_name,
                   sec.section_name,
                   CONCAT(t.first_name, ' ', t.last_name) as teacher_name
            FROM attendance_records ar
            JOIN attendance_sessions s ON ar.session_id = s.id
            JOIN students st ON ar.student_id = st.id
            JOIN classes c ON ar.class_id = c.id
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN teachers t ON c.teacher_id = t.id
            WHERE 1=1
        ";
        $params = [];

        if ($teacherId) {
            $sql .= " AND s.teacher_id = ?";
            $params[] = $teacherId;
        }

        $sql .= " ORDER BY ar.id DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAttendanceSessionsList($teacherId = null, $filters = []) {
        $sql = "
            SELECT s.*,
                   c.class_code,
                   sub.subject_code, sub.subject_name,
                   sec.section_name,
                   cr.course_code,
                   CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
                   COUNT(ar.id) as total_marked,
                   SUM(CASE WHEN ar.status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
                   SUM(CASE WHEN ar.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count,
                   SUM(CASE WHEN ar.status = 'LATE' THEN 1 ELSE 0 END) as late_count,
                   SUM(CASE WHEN ar.status = 'EXCUSED' THEN 1 ELSE 0 END) as excused_count
            FROM attendance_sessions s
            JOIN classes c ON s.class_id = c.id
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN sections sec ON c.section_id = sec.id
            JOIN courses cr ON sec.course_id = cr.id
            JOIN teachers t ON s.teacher_id = t.id
            LEFT JOIN attendance_records ar ON ar.session_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if ($teacherId) {
            $sql .= " AND s.teacher_id = ?";
            $params[] = $teacherId;
        }
        if (!empty($filters['class_id'])) {
            $sql .= " AND s.class_id = ?";
            $params[] = $filters['class_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND s.session_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND s.session_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " GROUP BY s.id ORDER BY s.session_date DESC, s.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
