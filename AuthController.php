<?php
/**
 * Auth Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Teacher.php';

class AuthController {
    private $userModel;
    private $studentModel;
    private $teacherModel;
    private $db;

    public function __construct() {
        $this->userModel = new User();
        $this->studentModel = new Student();
        $this->teacherModel = new Teacher();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }

        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }

        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            flash('error', 'Please enter both username/email and password.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }

        $user = $this->userModel->authenticate($login, $password);

        if (!$user) {
            flash('error', 'Invalid login credentials or inactive account.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }

        // Setup session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['avatar'] = $user['avatar'] ?? 'default-avatar.png';

        // Retrieve reference ID for teacher or student
        if ($user['role'] === 'teacher') {
            $t = $this->teacherModel->getByUserId($user['id']);
            $_SESSION['ref_id'] = $t['id'] ?? null;
        } elseif ($user['role'] === 'student') {
            $s = $this->studentModel->getByUserId($user['id']);
            $_SESSION['ref_id'] = $s['id'] ?? null;
        } else {
            $_SESSION['ref_id'] = $user['id'];
        }

        log_activity($this->db, $user['id'], 'USER_LOGIN', "User {$user['username']} logged in successfully as {$user['role']}.");
        flash('success', "Welcome back, {$user['full_name']}!");

        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
                break;
            case 'teacher':
                header('Location: ' . BASE_URL . '/teacher/dashboard.php');
                break;
            case 'student':
                header('Location: ' . BASE_URL . '/student/dashboard.php');
                break;
            default:
                header('Location: ' . BASE_URL . '/index.php');
                break;
        }
        exit;
    }

    public function handleRegister() {
        if (!ALLOW_REGISTRATION) {
            flash('error', 'Self-registration is currently disabled by the administrator.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }

        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $studentId = trim($_POST['student_id'] ?? '');
        $courseId = (int)($_POST['course_id'] ?? 0);
        $yearLevelId = (int)($_POST['year_level_id'] ?? 0);
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $gender = $_POST['gender'] ?? 'Male';
        $contactNumber = trim($_POST['contact_number'] ?? '');

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($studentId) || empty($courseId) || empty($yearLevelId) || empty($sectionId)) {
            flash('error', 'Please fill in all required fields.');
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }

        if ($password !== $confirmPassword) {
            flash('error', 'Passwords do not match.');
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }

        if (strlen($password) < 6) {
            flash('error', 'Password must be at least 6 characters long.');
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }

        if ($this->userModel->findByUsername($username)) {
            flash('error', 'Username is already taken. Please choose another.');
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }

        if ($this->userModel->findByEmail($email)) {
            flash('error', 'Email address is already registered.');
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }

        if ($this->studentModel->getByStudentId($studentId)) {
            flash('error', 'Student ID number already exists in the system.');
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }

        try {
            $this->db->beginTransaction();

            // Create User
            $userId = $this->userModel->create([
                'username'  => $username,
                'email'     => $email,
                'password'  => $password,
                'role'      => 'student',
                'full_name' => "{$firstName} {$lastName}",
                'avatar'    => 'default-avatar.png',
                'status'    => 'active'
            ]);

            // Create Student
            $this->studentModel->create([
                'user_id'        => $userId,
                'student_id'     => $studentId,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'email'          => $email,
                'gender'         => $gender,
                'course_id'      => $courseId,
                'year_level_id'  => $yearLevelId,
                'section_id'     => $sectionId,
                'contact_number' => $contactNumber,
                'status'         => 'active'
            ]);

            log_activity($this->db, $userId, 'STUDENT_REGISTER', "Student account registered for {$firstName} {$lastName} ({$studentId}).");
            create_notification($this->db, $userId, 'Welcome to AMS', 'Your student account has been created successfully.', 'success');

            $this->db->commit();

            flash('success', 'Registration successful! You may now log in with your credentials.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        } catch (Exception $e) {
            $this->db->rollBack();
            flash('error', 'Registration error: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/register.php');
            exit;
        }
    }

    public function handleLogout() {
        if (is_logged_in()) {
            log_activity($this->db, $_SESSION['user_id'], 'USER_LOGOUT', "User {$_SESSION['username']} logged out.");
        }
        
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        session_start();
        flash('info', 'You have been logged out safely.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    public function handleProfileUpdate() {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($fullName) || empty($email)) {
            flash('error', 'Full name and email are required.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        // Handle Avatar Upload
        $avatarName = null;
        if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
                $avatarName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $targetDir = ROOT_PATH . '/uploads/avatars/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $avatarName);
                $_SESSION['avatar'] = $avatarName;
            }
        }

        $updateData = [
            'full_name' => $fullName,
            'email'     => $email
        ];
        if ($avatarName) {
            $updateData['avatar'] = $avatarName;
        }

        $this->userModel->update($userId, $updateData);
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;

        // If student or teacher, update contact / address
        if (current_role() === 'student' && !empty($_SESSION['ref_id'])) {
            $stmt = $this->db->prepare("UPDATE students SET contact_number = ?, address = ?, email = ? WHERE id = ?");
            $stmt->execute([$contactNumber, $address, $email, $_SESSION['ref_id']]);
        } elseif (current_role() === 'teacher' && !empty($_SESSION['ref_id'])) {
            $stmt = $this->db->prepare("UPDATE teachers SET contact_number = ?, email = ? WHERE id = ?");
            $stmt->execute([$contactNumber, $email, $_SESSION['ref_id']]);
        }

        log_activity($this->db, $userId, 'PROFILE_UPDATE', 'User profile information updated.');
        flash('success', 'Profile updated successfully.');
        header('Location: ' . BASE_URL . '/profile.php');
        exit;
    }

    public function handlePasswordChange() {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            flash('error', 'All password fields are required.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            flash('error', 'New passwords do not match.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        if (strlen($newPassword) < 6) {
            flash('error', 'New password must be at least 6 characters long.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        $user = $this->userModel->findById($userId);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            flash('error', 'Incorrect current password.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }

        $this->userModel->updatePassword($userId, $newPassword);
        log_activity($this->db, $userId, 'PASSWORD_CHANGE', 'User updated account password.');
        flash('success', 'Password updated successfully!');
        header('Location: ' . BASE_URL . '/profile.php');
        exit;
    }
}
