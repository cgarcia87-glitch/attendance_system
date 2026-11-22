<?php
/**
 * Student Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Class.php';

class StudentController {
    private $studentModel;
    private $userModel;
    private $classModel;
    private $db;

    public function __construct() {
        $this->studentModel = new Student();
        $this->userModel = new User();
        $this->classModel = new ClassModel();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleCreate() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        $studentId = trim($_POST['student_id'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $gender = $_POST['gender'] ?? 'Male';
        $birthDate = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
        $courseId = (int)($_POST['course_id'] ?? 0);
        $yearLevelId = (int)($_POST['year_level_id'] ?? 0);
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $createAccount = isset($_POST['create_account']);
        $password = $_POST['password'] ?? 'student123';

        if (empty($firstName) || empty($lastName) || empty($email) || empty($courseId) || empty($yearLevelId) || empty($sectionId)) {
            flash('error', 'Please fill in all required student details.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        if (!empty($studentId) && $this->studentModel->getByStudentId($studentId)) {
            flash('error', 'Student ID is already registered.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        try {
            $this->db->beginTransaction();
            $userId = null;

            if ($createAccount) {
                $username = !empty($studentId) ? strtolower(str_replace('-', '', $studentId)) : 'std' . time();
                $existingUser = $this->userModel->findByUsername($username) || $this->userModel->findByEmail($email);
                if (!$existingUser) {
                    $userId = $this->userModel->create([
                        'username'  => $username,
                        'email'     => $email,
                        'password'  => $password,
                        'role'      => 'student',
                        'full_name' => "{$firstName} {$lastName}",
                        'avatar'    => 'default-avatar.png',
                        'status'    => $status
                    ]);
                }
            }

            $id = $this->studentModel->create([
                'user_id'           => $userId,
                'student_id'        => $studentId,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email'             => $email,
                'gender'            => $gender,
                'birth_date'        => $birthDate,
                'course_id'         => $courseId,
                'year_level_id'     => $yearLevelId,
                'section_id'        => $sectionId,
                'contact_number'    => $contactNumber,
                'address'           => $address,
                'emergency_contact' => $emergencyContact,
                'status'            => $status
            ]);

            log_activity($this->db, $_SESSION['user_id'], 'STUDENT_CREATE', "Admin created student profile for {$firstName} {$lastName}.");
            $this->db->commit();

            flash('success', "Student {$firstName} {$lastName} added successfully!");
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        } catch (Exception $e) {
            $this->db->rollBack();
            flash('error', 'Error creating student: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }
    }

    public function handleUpdate() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $student = $this->studentModel->getById($id);
        if (!$student) {
            flash('error', 'Student record not found.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        $data = [
            'student_id'        => trim($_POST['student_id'] ?? $student['student_id']),
            'first_name'        => trim($_POST['first_name'] ?? $student['first_name']),
            'last_name'         => trim($_POST['last_name'] ?? $student['last_name']),
            'email'             => trim($_POST['email'] ?? $student['email']),
            'gender'            => $_POST['gender'] ?? $student['gender'],
            'birth_date'        => !empty($_POST['birth_date']) ? $_POST['birth_date'] : null,
            'course_id'         => (int)($_POST['course_id'] ?? $student['course_id']),
            'year_level_id'     => (int)($_POST['year_level_id'] ?? $student['year_level_id']),
            'section_id'        => (int)($_POST['section_id'] ?? $student['section_id']),
            'contact_number'    => trim($_POST['contact_number'] ?? ''),
            'address'           => trim($_POST['address'] ?? ''),
            'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
            'status'            => $_POST['status'] ?? $student['status']
        ];

        $this->studentModel->update($id, $data);

        // Sync linked user full name & email
        if (!empty($student['user_id'])) {
            $this->userModel->update($student['user_id'], [
                'full_name' => "{$data['first_name']} {$data['last_name']}",
                'email'     => $data['email'],
                'status'    => $data['status']
            ]);
        }

        log_activity($this->db, $_SESSION['user_id'], 'STUDENT_UPDATE', "Updated student record #{$id} ({$data['first_name']} {$data['last_name']}).");
        flash('success', "Student record updated successfully!");
        header('Location: ' . BASE_URL . '/admin/students.php');
        exit;
    }

    public function handleDelete() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $student = $this->studentModel->getById($id);
        if ($student) {
            $this->studentModel->delete($id);
            log_activity($this->db, $_SESSION['user_id'], 'STUDENT_DELETE', "Deleted student {$student['first_name']} {$student['last_name']} ({$student['student_id']}).");
            flash('success', 'Student record deleted successfully.');
        } else {
            flash('error', 'Student not found.');
        }

        header('Location: ' . BASE_URL . '/admin/students.php');
        exit;
    }

    public function handleImportCsv() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            flash('error', 'Please select a CSV file to import.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$file) {
            flash('error', 'Failed to open uploaded CSV file.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        }

        // Skip header
        $header = fgetcsv($file);
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($file)) !== false) {
            // Expected format: StudentID, FirstName, LastName, Email, Gender, CourseID, YearLevelID, SectionID, ContactNumber
            if (count($row) < 8) {
                $skipped++;
                continue;
            }

            $studentId = trim($row[0]);
            $firstName = trim($row[1]);
            $lastName  = trim($row[2]);
            $email     = trim($row[3]);
            $gender    = trim($row[4] ?? 'Male');
            $courseId  = (int)trim($row[5]);
            $yearId    = (int)trim($row[6]);
            $sectionId = (int)trim($row[7]);
            $contact   = trim($row[8] ?? '');

            if (empty($studentId) || empty($firstName) || empty($lastName) || empty($email)) {
                $skipped++;
                continue;
            }

            // Check duplicate
            if ($this->studentModel->getByStudentId($studentId)) {
                $skipped++;
                continue;
            }

            try {
                // Create user
                $username = strtolower(str_replace(['-', ' '], '', $studentId));
                $userId = null;
                if (!$this->userModel->findByUsername($username) && !$this->userModel->findByEmail($email)) {
                    $userId = $this->userModel->create([
                        'username'  => $username,
                        'email'     => $email,
                        'password'  => 'student123',
                        'role'      => 'student',
                        'full_name' => "{$firstName} {$lastName}",
                        'avatar'    => 'default-avatar.png',
                        'status'    => 'active'
                    ]);
                }

                $this->studentModel->create([
                    'user_id'        => $userId,
                    'student_id'     => $studentId,
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'email'          => $email,
                    'gender'         => $gender,
                    'course_id'      => $courseId,
                    'year_level_id'  => $yearId,
                    'section_id'     => $sectionId,
                    'contact_number' => $contact,
                    'status'         => 'active'
                ]);
                $imported++;
            } catch (Exception $e) {
                $skipped++;
            }
        }
        fclose($file);

        log_activity($this->db, $_SESSION['user_id'], 'STUDENTS_IMPORTED', "Imported {$imported} students via CSV ({$skipped} skipped).");
        flash('success', "CSV Import Complete: {$imported} students imported, {$skipped} skipped.");
        header('Location: ' . BASE_URL . '/admin/students.php');
        exit;
    }

    public function handleExportCsv() {
        require_role(['admin', 'teacher']);
        $students = $this->studentModel->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=students_export_' . date('Y-m-d') . '.csv');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM
        fputcsv($out, ['Student ID', 'First Name', 'Last Name', 'Email', 'Gender', 'Course', 'Year Level', 'Section', 'Contact Number', 'Status', 'Date Registered']);

        foreach ($students as $s) {
            fputcsv($out, [
                $s['student_id'],
                $s['first_name'],
                $s['last_name'],
                $s['email'],
                $s['gender'],
                $s['course_code'],
                $s['year_level_name'],
                $s['section_name'],
                $s['contact_number'],
                $s['status'],
                $s['date_registered']
            ]);
        }
        fclose($out);
        exit;
    }
}
