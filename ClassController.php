<?php
/**
 * Class, Course, Section & Schedule Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Class.php';

class ClassController {
    private $classModel;
    private $db;

    public function __construct() {
        $this->classModel = new ClassModel();
        $this->db = Database::getInstance()->getConnection();
    }

    // ---------------------------------------------------------
    // CLASSES
    // ---------------------------------------------------------
    public function handleCreateClass() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/classes.php');
            exit;
        }

        $classCode = trim($_POST['class_code'] ?? '');
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $teacherId = (int)($_POST['teacher_id'] ?? 0);
        $academicYear = trim($_POST['academic_year'] ?? '2025-2026');
        $semester = trim($_POST['semester'] ?? '1st Semester');
        $room = trim($_POST['room'] ?? 'Room 301');
        $status = $_POST['status'] ?? 'active';

        if (empty($subjectId) || empty($sectionId) || empty($teacherId)) {
            flash('error', 'Subject, Section, and Teacher are required.');
            header('Location: ' . BASE_URL . '/admin/classes.php');
            exit;
        }

        try {
            $this->classModel->create([
                'class_code'    => $classCode,
                'subject_id'    => $subjectId,
                'section_id'    => $sectionId,
                'teacher_id'    => $teacherId,
                'academic_year' => $academicYear,
                'semester'      => $semester,
                'room'          => $room,
                'status'        => $status
            ]);

            log_activity($this->db, $_SESSION['user_id'], 'CLASS_CREATE', "Created class {$classCode}.");
            flash('success', 'Class created successfully!');
        } catch (Exception $e) {
            flash('error', 'Error creating class: ' . $e->getMessage());
        }

        header('Location: ' . BASE_URL . '/admin/classes.php');
        exit;
    }

    public function handleUpdateClass() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/classes.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'class_code'    => trim($_POST['class_code'] ?? ''),
            'subject_id'    => (int)($_POST['subject_id'] ?? 0),
            'section_id'    => (int)($_POST['section_id'] ?? 0),
            'teacher_id'    => (int)($_POST['teacher_id'] ?? 0),
            'academic_year' => trim($_POST['academic_year'] ?? '2025-2026'),
            'semester'      => trim($_POST['semester'] ?? '1st Semester'),
            'room'          => trim($_POST['room'] ?? 'Room 301'),
            'status'        => $_POST['status'] ?? 'active'
        ];

        $this->classModel->update($id, $data);
        log_activity($this->db, $_SESSION['user_id'], 'CLASS_UPDATE', "Updated class #{$id}.");
        flash('success', 'Class updated successfully.');
        header('Location: ' . BASE_URL . '/admin/classes.php');
        exit;
    }

    public function handleDeleteClass() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/classes.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $this->classModel->delete($id);
        log_activity($this->db, $_SESSION['user_id'], 'CLASS_DELETE', "Deleted class #{$id}.");
        flash('success', 'Class deleted successfully.');
        header('Location: ' . BASE_URL . '/admin/classes.php');
        exit;
    }

    public function handleEnrollStudent() {
        require_role(['admin', 'teacher']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $classId = (int)($_POST['class_id'] ?? 0);
        $studentId = (int)($_POST['student_id'] ?? 0);

        if ($classId && $studentId) {
            $this->classModel->enrollStudent($classId, $studentId);
            flash('success', 'Student enrolled successfully.');
        } else {
            flash('error', 'Missing class or student selection.');
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/admin/classes.php')));
        exit;
    }

    public function handleRemoveEnrollment() {
        require_role(['admin', 'teacher']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $classId = (int)($_POST['class_id'] ?? 0);
        $studentId = (int)($_POST['student_id'] ?? 0);

        if ($classId && $studentId) {
            $this->classModel->removeStudent($classId, $studentId);
            flash('success', 'Student unenrolled from class.');
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/admin/classes.php')));
        exit;
    }

    // ---------------------------------------------------------
    // COURSES
    // ---------------------------------------------------------
    public function handleCreateCourse() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/courses.php');
            exit;
        }

        $code = strtoupper(trim($_POST['course_code'] ?? ''));
        $name = trim($_POST['course_name'] ?? '');
        $dept = trim($_POST['department'] ?? 'College of Information Technology');
        $desc = trim($_POST['description'] ?? '');

        if (empty($code) || empty($name)) {
            flash('error', 'Course code and name are required.');
            header('Location: ' . BASE_URL . '/admin/courses.php');
            exit;
        }

        try {
            $this->classModel->addCourse([
                'course_code' => $code,
                'course_name' => $name,
                'department'  => $dept,
                'description' => $desc,
                'status'      => $_POST['status'] ?? 'active'
            ]);
            flash('success', "Course {$code} added successfully!");
        } catch (Exception $e) {
            flash('error', 'Error adding course: ' . $e->getMessage());
        }

        header('Location: ' . BASE_URL . '/admin/courses.php');
        exit;
    }

    public function handleUpdateCourse() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/courses.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $this->classModel->updateCourse($id, [
            'course_code' => strtoupper(trim($_POST['course_code'] ?? '')),
            'course_name' => trim($_POST['course_name'] ?? ''),
            'department'  => trim($_POST['department'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status'      => $_POST['status'] ?? 'active'
        ]);

        flash('success', 'Course updated successfully.');
        header('Location: ' . BASE_URL . '/admin/courses.php');
        exit;
    }

    public function handleDeleteCourse() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/courses.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $this->classModel->deleteCourse($id);
        flash('success', 'Course deleted successfully.');
        header('Location: ' . BASE_URL . '/admin/courses.php');
        exit;
    }

    // ---------------------------------------------------------
    // SECTIONS
    // ---------------------------------------------------------
    public function handleCreateSection() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/sections.php');
            exit;
        }

        $name = trim($_POST['section_name'] ?? '');
        $courseId = (int)($_POST['course_id'] ?? 0);
        $yearLevelId = (int)($_POST['year_level_id'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 40);

        if (empty($name) || empty($courseId) || empty($yearLevelId)) {
            flash('error', 'Section name, Course, and Year level are required.');
            header('Location: ' . BASE_URL . '/admin/sections.php');
            exit;
        }

        $this->classModel->addSection([
            'section_name'  => $name,
            'course_id'     => $courseId,
            'year_level_id' => $yearLevelId,
            'capacity'      => $capacity,
            'status'        => $_POST['status'] ?? 'active'
        ]);

        flash('success', "Section {$name} created successfully!");
        header('Location: ' . BASE_URL . '/admin/sections.php');
        exit;
    }

    public function handleUpdateSection() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/sections.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $this->classModel->updateSection($id, [
            'section_name'  => trim($_POST['section_name'] ?? ''),
            'course_id'     => (int)($_POST['course_id'] ?? 0),
            'year_level_id' => (int)($_POST['year_level_id'] ?? 0),
            'capacity'      => (int)($_POST['capacity'] ?? 40),
            'status'        => $_POST['status'] ?? 'active'
        ]);

        flash('success', 'Section updated successfully.');
        header('Location: ' . BASE_URL . '/admin/sections.php');
        exit;
    }

    public function handleDeleteSection() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/sections.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $this->classModel->deleteSection($id);
        flash('success', 'Section deleted successfully.');
        header('Location: ' . BASE_URL . '/admin/sections.php');
        exit;
    }

    // ---------------------------------------------------------
    // SCHEDULES
    // ---------------------------------------------------------
    public function handleAddSchedule() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/schedules.php');
            exit;
        }

        $classId = (int)($_POST['class_id'] ?? 0);
        $day = $_POST['day_of_week'] ?? 'Monday';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $room = trim($_POST['room'] ?? 'Room 301');

        if (empty($classId) || empty($startTime) || empty($endTime)) {
            flash('error', 'Class and times are required.');
            header('Location: ' . BASE_URL . '/admin/schedules.php');
            exit;
        }

        $this->classModel->addSchedule([
            'class_id'    => $classId,
            'day_of_week' => $day,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'room'        => $room
        ]);

        flash('success', 'Schedule added successfully.');
        header('Location: ' . BASE_URL . '/admin/schedules.php');
        exit;
    }

    public function handleDeleteSchedule() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/schedules.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $this->classModel->deleteSchedule($id);
        flash('success', 'Schedule deleted successfully.');
        header('Location: ' . BASE_URL . '/admin/schedules.php');
        exit;
    }
}
