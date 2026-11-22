<?php
/**
 * User & System Settings Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;
    private $db;

    public function __construct() {
        $this->userModel = new User();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleCreateUser() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'teacher';
        $fullName = trim($_POST['full_name'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            flash('error', 'All fields are required.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }

        if ($this->userModel->findByUsername($username)) {
            flash('error', 'Username is already taken.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }

        if ($this->userModel->findByEmail($email)) {
            flash('error', 'Email is already registered.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }

        $this->userModel->create([
            'username'  => $username,
            'email'     => $email,
            'password'  => $password,
            'role'      => $role,
            'full_name' => $fullName,
            'avatar'    => 'default-avatar.png',
            'status'    => $status
        ]);

        log_activity($this->db, $_SESSION['user_id'], 'USER_CREATE', "Created user account {$username} ({$role}).");
        flash('success', "User account {$username} created successfully!");
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }

    public function handleUpdateUser() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $user = $this->userModel->findById($id);
        if (!$user) {
            flash('error', 'User not found.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }

        $data = [
            'full_name' => trim($_POST['full_name'] ?? $user['full_name']),
            'email'     => trim($_POST['email'] ?? $user['email']),
            'role'      => $_POST['role'] ?? $user['role'],
            'status'    => $_POST['status'] ?? $user['status']
        ];

        $this->userModel->update($id, $data);

        // If password was also provided
        if (!empty($_POST['new_password'])) {
            $this->userModel->updatePassword($id, $_POST['new_password']);
        }

        log_activity($this->db, $_SESSION['user_id'], 'USER_UPDATE', "Updated user account #{$id}.");
        flash('success', 'User updated successfully.');
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }

    public function handleDeleteUser() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            flash('error', 'You cannot delete your own active administrator account!');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }

        $this->userModel->delete($id);
        log_activity($this->db, $_SESSION['user_id'], 'USER_DELETE', "Deleted user account #{$id}.");
        flash('success', 'User account deleted.');
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }

    public function handleUpdateSettings() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/settings.php');
            exit;
        }

        $settings = [
            'system_name'            => trim($_POST['system_name'] ?? 'Attendance Management System'),
            'institution_name'       => trim($_POST['institution_name'] ?? 'Apex Institute of Technology'),
            'academic_year'          => trim($_POST['academic_year'] ?? '2025-2026'),
            'current_semester'       => trim($_POST['current_semester'] ?? '1st Semester'),
            'allow_registration'     => isset($_POST['allow_registration']) ? '1' : '0',
            'late_threshold_minutes' => (string)(int)($_POST['late_threshold_minutes'] ?? 15),
            'contact_email'          => trim($_POST['contact_email'] ?? ''),
            'contact_phone'          => trim($_POST['contact_phone'] ?? ''),
            'institution_address'    => trim($_POST['institution_address'] ?? '')
        ];

        $stmt = $this->db->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");

        foreach ($settings as $k => $v) {
            $stmt->execute([$k, $v]);
        }

        log_activity($this->db, $_SESSION['user_id'], 'SETTINGS_UPDATE', 'System configuration updated.');
        flash('success', 'System settings saved successfully!');
        header('Location: ' . BASE_URL . '/admin/settings.php');
        exit;
    }
}
