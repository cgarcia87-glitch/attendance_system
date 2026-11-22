<?php
/**
 * Administrator - Subject Management Module
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Subject.php';
require_once __DIR__ . '/../controllers/SubjectController.php';

require_role(['admin']);

$subjectModel = new Subject();
$subjectController = new SubjectController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $subjectController->handleCreate();
                break;
            case 'update':
                $subjectController->handleUpdate();
                break;
            case 'delete':
                $subjectController->handleDelete();
                break;
        }
    }
}

$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$subjects = $subjectModel->getAll($filters);

$pageTitle = 'Subject Management';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Subjects & Curriculum</h3>
        <p class="text-secondary small mb-0">Manage course subjects, academic credit units, and course syllabi.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Subject
        </button>
    </div>
</div>

<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Credits / Units</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $sub): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-6 px-2 py-1">
                                    <?= e($sub['subject_code']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= e($sub['subject_name']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$sub['units'] ?> Units</span>
                            </td>
                            <td>
                                <small class="text-muted"><?= e($sub['description'] ?: 'None') ?></small>
                            </td>
                            <td><?= get_status_badge($sub['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border edit-subject-btn" 
                                            data-subject='<?= json_encode($sub) ?>' title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/subjects.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                        <button type="button" class="btn btn-light border text-danger btn-delete-confirm" title="Delete">
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

<!-- ADD SUBJECT MODAL -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-book-half me-2 text-primary"></i> Add Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/subjects.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject Code *</label>
                        <input type="text" name="subject_code" class="form-control" required placeholder="e.g. IT201">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject Name / Title *</label>
                        <input type="text" name="subject_name" class="form-control" required placeholder="e.g. Web Systems and Technologies">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Credits / Units</label>
                        <input type="number" name="units" class="form-control" value="3" min="1" max="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT SUBJECT MODAL -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/subjects.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_subject_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject Code *</label>
                        <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject Name / Title *</label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Credits / Units</label>
                        <input type="number" name="units" id="edit_units" class="form-control" min="1" max="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
    document.querySelectorAll('.edit-subject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.subject);
            document.getElementById('edit_subject_id').value = data.id;
            document.getElementById('edit_subject_code').value = data.subject_code;
            document.getElementById('edit_subject_name').value = data.subject_name;
            document.getElementById('edit_units').value = data.units;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_status').value = data.status;

            const modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
            modal.show();
        });
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
