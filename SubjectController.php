<?php
/**
 * Subject Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Subject.php';

class SubjectController {
    private $subjectModel;
    private $db;

    public function __construct() {
        $this->subjectModel = new Subject();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleCreate() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/subjects.php');
            exit;
        }

        $code = strtoupper(trim($_POST['subject_code'] ?? ''));
        $name = trim($_POST['subject_name'] ?? '');
        $units = (int)($_POST['units'] ?? 3);
        $desc = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (empty($code) || empty($name)) {
            flash('error', 'Subject code and subject name are required.');
            header('Location: ' . BASE_URL . '/admin/subjects.php');
            exit;
        }

        if ($this->subjectModel->getByCode($code)) {
            flash('error', 'Subject code already exists.');
            header('Location: ' . BASE_URL . '/admin/subjects.php');
            exit;
        }

        try {
            $this->subjectModel->create([
                'subject_code' => $code,
                'subject_name' => $name,
                'units'        => $units,
                'description'  => $desc,
                'status'       => $status
            ]);

            log_activity($this->db, $_SESSION['user_id'], 'SUBJECT_CREATE', "Created subject {$code} - {$name}.");
            flash('success', "Subject {$code} created successfully!");
        } catch (Exception $e) {
            flash('error', 'Error creating subject: ' . $e->getMessage());
        }

        header('Location: ' . BASE_URL . '/admin/subjects.php');
        exit;
    }

    public function handleUpdate() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/subjects.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['subject_code'] ?? ''));
        $name = trim($_POST['subject_name'] ?? '');
        $units = (int)($_POST['units'] ?? 3);
        $desc = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        $this->subjectModel->update($id, [
            'subject_code' => $code,
            'subject_name' => $name,
            'units'        => $units,
            'description'  => $desc,
            'status'       => $status
        ]);

        log_activity($this->db, $_SESSION['user_id'], 'SUBJECT_UPDATE', "Updated subject #{$id} ({$code}).");
        flash('success', 'Subject updated successfully.');
        header('Location: ' . BASE_URL . '/admin/subjects.php');
        exit;
    }

    public function handleDelete() {
        require_role(['admin']);
        if (!verify_csrf_token()) {
            flash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '/admin/subjects.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $this->subjectModel->delete($id);
        log_activity($this->db, $_SESSION['user_id'], 'SUBJECT_DELETE', "Deleted subject #{$id}.");
        flash('success', 'Subject deleted successfully.');
        header('Location: ' . BASE_URL . '/admin/subjects.php');
        exit;
    }
}
