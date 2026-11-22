<?php
/**
 * Teacher Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Class.php';

class TeacherController {
    private $teacherModel;
    private $userModel;
    private $classModel;
    private $db;

    public function __construct() {
        $this->teacherModel = new Teacher();
        $this->userModel = new User();
        $this->classModel = new ClassModel();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleCreate() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        }

        $teacherId = trim($_POST['teacher_id'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $department = trim($_POST['department'] ?? 'Information Technology');
        $qualification = trim($_POST['qualification'] ?? 'Master of Science');
        $status = $_POST['status'] ?? 'active';
        $createAccount = isset($_POST['create_account']);
        $password = $_POST['password'] ?? 'teacher123';

        if (empty($firstName) || empty($lastName) || empty($email)) {
            flash('error', 'Please fill in all required teacher fields.');
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        }

        if (!empty($teacherId) && $this->teacherModel->getByTeacherId($teacherId)) {
            flash('error', 'Teacher ID already exists.');
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        }

        try {
            $this->db->beginTransaction();
            $userId = null;

            if ($createAccount) {
                $username = !empty($teacherId) ? strtolower(str_replace('-', '', $teacherId)) : 'tch' . time();
                $userId = $this->userModel->create([
                    'username'  => $username,
                    'email'     => $email,
                    'password'  => $password,
                    'role'      => 'teacher',
                    'full_name' => "Prof. {$firstName} {$lastName}",
                    'avatar'    => 'default-avatar.png',
                    'status'    => $status
                ]);
            }

            $this->teacherModel->create([
                'user_id'       => $userId,
                'teacher_id'    => $teacherId,
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'email'         => $email,
                'contact_number'=> $contactNumber,
                'department'    => $department,
                'qualification' => $qualification,
                'status'        => $status,
                'date_hired'    => !empty($_POST['date_hired']) ? $_POST['date_hired'] : date('Y-m-d')
            ]);

            log_activity($this->db, $_SESSION['user_id'], 'TEACHER_CREATE', "Created teacher profile for Prof. {$firstName} {$lastName}.");
            $this->db->commit();

            flash('success', "Teacher Prof. {$firstName} {$lastName} created successfully!");
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        } catch (Exception $e) {
            $this->db->rollBack();
            flash('error', 'Error creating teacher: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        }
    }

    public function handleUpdate() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $teacher = $this->teacherModel->getById($id);
        if (!$teacher) {
            flash('error', 'Teacher record not found.');
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        }

        $data = [
            'teacher_id'    => trim($_POST['teacher_id'] ?? $teacher['teacher_id']),
            'first_name'    => trim($_POST['first_name'] ?? $teacher['first_name']),
            'last_name'     => trim($_POST['last_name'] ?? $teacher['last_name']),
            'email'         => trim($_POST['email'] ?? $teacher['email']),
            'contact_number'=> trim($_POST['contact_number'] ?? ''),
            'department'    => trim($_POST['department'] ?? 'Information Technology'),
            'qualification' => trim($_POST['qualification'] ?? ''),
            'status'        => $_POST['status'] ?? $teacher['status'],
            'date_hired'    => !empty($_POST['date_hired']) ? $_POST['date_hired'] : $teacher['date_hired']
        ];

        $this->teacherModel->update($id, $data);

        // Sync linked user
        if (!empty($teacher['user_id'])) {
            $this->userModel->update($teacher['user_id'], [
                'full_name' => "Prof. {$data['first_name']} {$data['last_name']}",
                'email'     => $data['email'],
                'status'    => $data['status']
            ]);
        }

        log_activity($this->db, $_SESSION['user_id'], 'TEACHER_UPDATE', "Updated teacher record for Prof. {$data['first_name']} {$data['last_name']}.");
        flash('success', "Teacher record updated successfully!");
        header('Location: ' . BASE_URL . '/admin/teachers.php');
        exit;
    }

    public function handleDelete() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $teacher = $this->teacherModel->getById($id);
        if ($teacher) {
            $this->teacherModel->delete($id);
            log_activity($this->db, $_SESSION['user_id'], 'TEACHER_DELETE', "Deleted teacher Prof. {$teacher['first_name']} {$teacher['last_name']}.");
            flash('success', 'Teacher record deleted successfully.');
        } else {
            flash('error', 'Teacher not found.');
        }

        header('Location: ' . BASE_URL . '/admin/teachers.php');
        exit;
    }

    public function handleExportCsv() {
        require_role(['admin']);
        $teachers = $this->teacherModel->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=teachers_export_' . date('Y-m-d') . '.csv');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Teacher ID', 'First Name', 'Last Name', 'Email', 'Contact Number', 'Department', 'Qualification', 'Status', 'Date Hired', 'Assigned Classes']);

        foreach ($teachers as $t) {
            fputcsv($out, [
                $t['teacher_id'],
                $t['first_name'],
                $t['last_name'],
                $t['email'],
                $t['contact_number'],
                $t['department'],
                $t['qualification'],
                $t['status'],
                $t['date_hired'],
                $t['total_classes'] ?? 0
            ]);
        }
        fclose($out);
        exit;
    }
}
