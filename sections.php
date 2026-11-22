<?php
/**
 * Administrator - Section Management Module
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Class.php';
require_once __DIR__ . '/../controllers/ClassController.php';

require_role(['admin']);

$classModel = new ClassModel();
$classController = new ClassController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $classController->handleCreateSection();
                break;
            case 'update':
                $classController->handleUpdateSection();
                break;
            case 'delete':
                $classController->handleDeleteSection();
                break;
        }
    }
}

$courses = $classModel->getCourses();
$yearLevels = $classModel->getYearLevels();
$sections = $classModel->getSections();

$pageTitle = 'Section Management';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Class Sections</h3>
        <p class="text-secondary small mb-0">Organize student groups, classroom quotas, and cohort assignments.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Section
        </button>
    </div>
</div>

<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Section Name</th>
                        <th>Program / Course</th>
                        <th>Year Level</th>
                        <th>Capacity</th>
                        <th>Enrolled Students</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $sec): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-dark fs-6"><?= e($sec['section_name']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($sec['course_code']) ?></span>
                                <span class="small text-muted ms-1"><?= e($sec['course_name']) ?></span>
                            </td>
                            <td><span class="text-secondary small"><?= e($sec['year_level_name']) ?></span></td>
                            <td><span class="small text-muted"><?= (int)$sec['capacity'] ?> max seats</span></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <?= (int)$sec['student_count'] ?> Students
                                </span>
                            </td>
                            <td><?= get_status_badge($sec['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border edit-section-btn" 
                                            data-section='<?= json_encode($sec) ?>' title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/sections.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $sec['id'] ?>">
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

<!-- ADD SECTION MODAL -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3-fill me-2 text-primary"></i> Add Class Section</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/sections.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Section Name *</label>
                        <input type="text" name="section_name" class="form-control" required placeholder="e.g. BSIT-1A">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Course / Degree Program *</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['course_code']) ?> - <?= e($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Year Level *</label>
                        <select name="year_level_id" class="form-select" required>
                            <option value="">Select Year Level</option>
                            <?php foreach ($yearLevels as $y): ?>
                                <option value="<?= $y['id'] ?>"><?= e($y['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Maximum Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="40" min="1" max="150">
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT SECTION MODAL -->
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Class Section</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/sections.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_section_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Section Name *</label>
                        <input type="text" name="section_name" id="edit_section_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Course / Degree Program *</label>
                        <select name="course_id" id="edit_course_id" class="form-select" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['course_code']) ?> - <?= e($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Year Level *</label>
                        <select name="year_level_id" id="edit_year_level_id" class="form-select" required>
                            <?php foreach ($yearLevels as $y): ?>
                                <option value="<?= $y['id'] ?>"><?= e($y['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Maximum Capacity</label>
                        <input type="number" name="capacity" id="edit_capacity" class="form-control" min="1" max="150">
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
    document.querySelectorAll('.edit-section-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.section);
            document.getElementById('edit_section_id').value = data.id;
            document.getElementById('edit_section_name').value = data.section_name;
            document.getElementById('edit_course_id').value = data.course_id;
            document.getElementById('edit_year_level_id').value = data.year_level_id;
            document.getElementById('edit_capacity').value = data.capacity;
            document.getElementById('edit_status').value = data.status;

            const modal = new bootstrap.Modal(document.getElementById('editSectionModal'));
            modal.show();
        });
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
