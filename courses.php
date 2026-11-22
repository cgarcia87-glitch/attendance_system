<?php
/**
 * Administrator - Course Management Module
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
                $classController->handleCreateCourse();
                break;
            case 'update':
                $classController->handleUpdateCourse();
                break;
            case 'delete':
                $classController->handleDeleteCourse();
                break;
        }
    }
}

$courses = $classModel->getCourses();

$pageTitle = 'Course Management';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Courses & Degree Programs</h3>
        <p class="text-secondary small mb-0">Manage academic departments, degree qualifications, and student enrollments.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Course
        </button>
    </div>
</div>

<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Course / Program Name</th>
                        <th>Department</th>
                        <th>Sections</th>
                        <th>Enrolled Students</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 font-monospace px-2 py-1">
                                    <?= e($c['course_code']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($c['course_name']) ?></div>
                                <small class="text-muted"><?= e($c['description'] ?: 'No description provided') ?></small>
                            </td>
                            <td><span class="text-secondary small"><?= e($c['department']) ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?= (int)$c['section_count'] ?> Sections</span></td>
                            <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= (int)$c['student_count'] ?> Students</span></td>
                            <td><?= get_status_badge($c['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border edit-course-btn" 
                                            data-course='<?= json_encode($c) ?>' title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/courses.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
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

<!-- ADD COURSE MODAL -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-mortarboard-fill me-2 text-primary"></i> Add Course Program</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/courses.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Course Code *</label>
                        <input type="text" name="course_code" class="form-control" required placeholder="e.g. BSIT">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Course Name *</label>
                        <input type="text" name="course_name" class="form-control" required placeholder="e.g. Bachelor of Science in Information Technology">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Department / College</label>
                        <input type="text" name="department" class="form-control" value="College of Information Technology">
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT COURSE MODAL -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Course Program</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/courses.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_course_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Course Code *</label>
                        <input type="text" name="course_code" id="edit_course_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Course Name *</label>
                        <input type="text" name="course_name" id="edit_course_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Department</label>
                        <input type="text" name="department" id="edit_course_department" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="edit_course_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" id="edit_course_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
    document.querySelectorAll('.edit-course-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.course);
            document.getElementById('edit_course_id').value = data.id;
            document.getElementById('edit_course_code').value = data.course_code;
            document.getElementById('edit_course_name').value = data.course_name;
            document.getElementById('edit_course_department').value = data.department;
            document.getElementById('edit_course_description').value = data.description || '';
            document.getElementById('edit_course_status').value = data.status;

            const modal = new bootstrap.Modal(document.getElementById('editCourseModal'));
            modal.show();
        });
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
