<?php
/**
 * Subject Model
 */

require_once __DIR__ . '/../config/database.php';

class Subject {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = []) {
        $sql = "SELECT * FROM subjects WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $term = "%" . $filters['search'] . "%";
            $sql .= " AND (subject_code LIKE ? OR subject_name LIKE ?)";
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY subject_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM subjects WHERE subject_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO subjects (subject_code, subject_name, units, description, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            strtoupper(trim($data['subject_code'])),
            trim($data['subject_name']),
            (int)($data['units'] ?? 3),
            $data['description'] ?? null,
            $data['status'] ?? 'active'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE subjects SET 
                subject_code = ?,
                subject_name = ?,
                units = ?,
                description = ?,
                status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            strtoupper(trim($data['subject_code'])),
            trim($data['subject_name']),
            (int)($data['units'] ?? 3),
            $data['description'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM subjects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM subjects");
        return $stmt->fetchColumn();
    }
}
