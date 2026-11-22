<?php
/**
 * Session and Authentication Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/csrf.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'avatar'    => $_SESSION['avatar'] ?? 'default-avatar.png',
        'ref_id'    => $_SESSION['ref_id'] ?? null // teacher_id or student_id primary key in their respective tables
    ];
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        flash('error', 'Please log in to access this page.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role($allowed_roles) {
    require_login();
    
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    if (!in_array(current_role(), $allowed_roles)) {
        header('Location: ' . BASE_URL . '/unauthorized.php');
        exit;
    }
}

/**
 * Set or Get Flash Message
 * Usage:
 * Set: flash('success', 'Student added successfully!');
 * Get: $flash = flash();
 */
function flash($type = null, $message = null) {
    if ($type !== null && $message !== null) {
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message
        ];
        return null;
    }
    
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    
    return null;
}
