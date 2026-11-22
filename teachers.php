<?php
/**
 * Administrator - Teacher Management Module
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Class.php';
require_once __DIR__ . '/../controllers/TeacherController.php';

require_role(['admin']);

$teacherModel = new Teacher();
$classModel = new ClassModel();
$teacherController = new TeacherController();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $teacherController->handleCreate();
                break;
            case 'update':
                $teacherController->handleUpdate();
                break;
            case 'delete':
                $teacherController->handleDelete();
                break;
        }
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $teacherController->handleExportCsv();
}

$filters = [
    'department' => $_GET['department'] ?? '',
    'status'     => $_GET['status'] ?? '',
    'search'     => $_GET['search'] ?? ''
];

$teachers = $teacherModel->getAll($filters);

$pageTitle = 'Teacher Management';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Teacher & Faculty Management</h3>
        <p class="text-secondary small mb-0">Manage faculty staff, view assigned courses, and monitor teaching schedules.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/admin/teachers.php?export=csv" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
            <i class="bi bi-person-plus-fill me-1"></i> Add Faculty Member
        </button>
    </div>
</div>

<!-- Teachers Data Table -->
<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Faculty Member</th>
                        <th>Teacher ID</th>
                        <th>Department</th>
                        <th>Qualification</th>
                        <th>Contact</th>
                        <th>Classes</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $t): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-purple text-white fw-bold d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #7c3aed; font-size: 0.9rem;">
                                        <?= strtoupper(substr($t['first_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">Prof. <?= e($t['first_name']) ?> <?= e($t['last_name']) ?></div>
                                        <small class="text-muted"><?= e($t['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= e($t['teacher_id']) ?></span>
                            </td>
                            <td><span class="fw-medium text-secondary"><?= e($t['department']) ?></span></td>
                            <td><small class="text-muted"><?= e($t['qualification']) ?></small></td>
                            <td><small class="text-secondary"><?= e($t['contact_number'] ?: 'N/A') ?></small></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <?= (int)($t['total_classes'] ?? 0) ?> active
                                </span>
                            </td>
                            <td><?= get_status_badge($t['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border edit-teacher-btn" 
                                            data-teacher='<?= json_encode($t) ?>'
                                            title="Edit Teacher">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/teachers.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button type="button" class="btn btn-light border text-danger btn-delete-confirm" title="Delete Teacher">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD TEACHER MODAL -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-badge-fill me-2 text-primary"></i> Add Faculty Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/teachers.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required placeholder="e.g. Eleanor">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required placeholder="e.g. Vance">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Teacher ID (Leave blank to auto-generate)</label>
                            <input type="text" name="teacher_id" class="form-control" placeholder="e.g. TCH-2025-003">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address *</label>
                            <input type="email" name="email" class="form-control" required placeholder="prof.name@school.edu">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Department</label>
                            <input type="text" name="department" class="form-control" value="Information Technology" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Academic Qualification</label>
                            <input type="text" name="qualification" class="form-control" placeholder="e.g. Ph.D. in Computer Science">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date Hired</label>
                            <input type="date" name="date_hired" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="create_account" id="createTeacherAccountCheck" checked>
                            <label class="form-check-label fw-bold small text-dark" for="createTeacherAccountCheck">
                                Automatically Create Teacher Login Account
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Default password will be <code>teacher123</code>.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT TEACHER MODAL -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Faculty Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/teachers.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_teacher_id">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">First Name *</label>
                            <input type="text" name="first_name" id="edit_t_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Last Name *</label>
                            <input type="text" name="last_name" id="edit_t_last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Teacher ID *</label>
                            <input type="text" name="teacher_id" id="edit_t_teacher_id" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address *</label>
                            <input type="email" name="email" id="edit_t_email" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Department</label>
                            <input type="text" name="department" id="edit_t_department" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Academic Qualification</label>
                            <input type="text" name="qualification" id="edit_t_qualification" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_number" id="edit_t_contact" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date Hired</label>
                            <input type="date" name="date_hired" id="edit_t_date_hired" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" id="edit_t_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
    document.querySelectorAll('.edit-teacher-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.teacher);
            document.getElementById('edit_teacher_id').value = data.id;
            document.getElementById('edit_t_first_name').value = data.first_name;
            document.getElementById('edit_t_last_name').value = data.last_name;
            document.getElementById('edit_t_teacher_id').value = data.teacher_id;
            document.getElementById('edit_t_email').value = data.email;
            document.getElementById('edit_t_department').value = data.department;
            document.getElementById('edit_t_qualification').value = data.qualification || '';
            document.getElementById('edit_t_contact').value = data.contact_number || '';
            document.getElementById('edit_t_date_hired').value = data.date_hired || '';
            document.getElementById('edit_t_status').value = data.status;

            const modal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
            modal.show();
        });
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
