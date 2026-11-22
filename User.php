<?php
/**
 * User Model
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function authenticate($login, $password) {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $this->updateLastLogin($user['id']);
            return $user;
        }
        return false;
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getAll($role = null) {
        if ($role) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE role = ? ORDER BY id DESC");
            $stmt->execute([$role]);
        } else {
            $stmt = $this->db->query("SELECT * FROM users ORDER BY id DESC");
        }
        return $stmt->fetchAll();
    }

    public function create($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password, role, full_name, avatar, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['role'],
            $data['full_name'],
            $data['avatar'] ?? 'default-avatar.png',
            $data['status'] ?? 'active'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];

        if (isset($data['full_name'])) {
            $fields[] = "full_name = ?";
            $values[] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $values[] = $data['email'];
        }
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $values[] = $data['role'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $values[] = $data['status'];
        }
        if (isset($data['avatar'])) {
            $fields[] = "avatar = ?";
            $values[] = $data['avatar'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function updatePassword($id, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
                SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as total_teachers,
                SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as total_students,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users
            FROM users
        ");
        return $stmt->fetch();
    }
}
