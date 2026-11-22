<?php
/**
 * Administrator - Class Offering Management Module
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/Class.php';
require_once __DIR__ . '/../models/Subject.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../controllers/ClassController.php';

require_role(['admin']);

$classModel = new ClassModel();
$subjectModel = new Subject();
$teacherModel = new Teacher();
$studentModel = new Student();
$classController = new ClassController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $classController->handleCreateClass();
                break;
            case 'update':
                $classController->handleUpdateClass();
                break;
            case 'delete':
                $classController->handleDeleteClass();
                break;
            case 'enroll_student':
                $classController->handleEnrollStudent();
                break;
            case 'remove_student':
                $classController->handleRemoveEnrollment();
                break;
        }
    }
}

$classes = $classModel->getAll();
$subjects = $subjectModel->getAll(['status' => 'active']);
$sections = $classModel->getSections();
$teachers = $teacherModel->getAll(['status' => 'active']);
$allStudents = $studentModel->getAll(['status' => 'active']);

// If specific class selected for roster view
$selectedClassId = (int)($_GET['view_roster'] ?? 0);
$rosterStudents = [];
$currentClass = null;
if ($selectedClassId) {
    $currentClass = $classModel->getById($selectedClassId);
    if ($currentClass) {
        $rosterStudents = $classModel->getEnrolledStudents($selectedClassId);
    }
}

$pageTitle = 'Class Offerings Management';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Class Offerings</h3>
        <p class="text-secondary small mb-0">Connect subjects, sections, faculty instructors, classrooms, and student rosters.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
            <i class="bi bi-plus-circle me-1"></i> Create New Class Offering
        </button>
    </div>
</div>

<?php if ($currentClass): ?>
    <!-- Roster Panel for Selected Class -->
    <div class="card-modern border-primary mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-people-fill me-2"></i> Enrolled Students: <?= e($currentClass['class_code']) ?> (<?= e($currentClass['subject_name']) ?>)
                </h5>
                <small class="text-white-50">
                    Section: <?= e($currentClass['section_name']) ?> &bull; Instructor: <?= e($currentClass['teacher_name']) ?> &bull; Room: <?= e($currentClass['room']) ?>
                </small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#enrollModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Enroll Student
                </button>
                <a href="<?= BASE_URL ?>/admin/classes.php" class="btn btn-sm btn-outline-light">Close Roster</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Program</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rosterStudents)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No students currently enrolled in this class.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rosterStudents as $rs): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= e($rs['student_id']) ?></span></td>
                                    <td><span class="fw-bold text-dark"><?= e($rs['first_name']) ?> <?= e($rs['last_name']) ?></span></td>
                                    <td><?= e($rs['course_code']) ?></td>
                                    <td><small class="text-muted"><?= format_date($rs['enrollment_date']) ?></small></td>
                                    <td><span class="badge bg-success-subtle text-success">Enrolled</span></td>
                                    <td class="text-end">
                                        <form method="POST" action="<?= BASE_URL ?>/admin/classes.php?view_roster=<?= $selectedClassId ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="remove_student">
                                            <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
                                            <input type="hidden" name="student_id" value="<?= $rs['id'] ?>">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Unenroll">
                                                <i class="bi bi-x-circle me-1"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Classes List Table -->
<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Class Code</th>
                        <th>Subject</th>
                        <th>Section & Course</th>
                        <th>Instructor</th>
                        <th>Room / Term</th>
                        <th>Enrolled</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $cl): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-6 px-2 py-1">
                                    <?= e($cl['class_code']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($cl['subject_name']) ?></div>
                                <small class="text-muted"><?= e($cl['subject_code']) ?> &bull; <?= (int)$cl['units'] ?> units</small>
                            </td>
                            <td>
                                <span class="fw-semibold text-secondary"><?= e($cl['section_name']) ?></span>
                                <div class="small text-muted"><?= e($cl['course_code']) ?> (<?= e($cl['year_level_name']) ?>)</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">Prof. <?= e($cl['teacher_name']) ?></div>
                                <small class="text-muted"><?= e($cl['teacher_email']) ?></small>
                            </td>
                            <td>
                                <div><i class="bi bi-door-open me-1 text-muted"></i><?= e($cl['room']) ?></div>
                                <small class="text-muted"><?= e($cl['semester']) ?></small>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/classes.php?view_roster=<?= $cl['id'] ?>" class="badge bg-info-subtle text-info border border-info-subtle text-decoration-none py-1 px-2">
                                    <i class="bi bi-people me-1"></i><?= (int)$cl['enrolled_count'] ?> Students
                                </a>
                            </td>
                            <td><?= get_status_badge($cl['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/admin/classes.php?view_roster=<?= $cl['id'] ?>" class="btn btn-light border text-primary" title="View Student Roster">
                                        <i class="bi bi-people"></i>
                                    </a>
                                    <button type="button" class="btn btn-light border edit-class-btn" 
                                            data-class='<?= json_encode($cl) ?>' title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/classes.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $cl['id'] ?>">
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

<!-- ADD CLASS MODAL -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-building me-2 text-primary"></i> Create Class Offering</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/classes.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Class Code *</label>
                            <input type="text" name="class_code" class="form-control" placeholder="e.g. CLS-IT201-1A" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Subject *</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>"><?= e($sub['subject_code']) ?> - <?= e($sub['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Section *</label>
                            <select name="section_id" class="form-select" required>
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= $sec['id'] ?>"><?= e($sec['section_name']) ?> (<?= e($sec['course_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Assigned Teacher / Instructor *</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">Select Instructor</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= $t['id'] ?>">Prof. <?= e($t['first_name']) ?> <?= e($t['last_name']) ?> (<?= e($t['department']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Academic Year</label>
                            <input type="text" name="academic_year" class="form-control" value="<?= ACADEMIC_YEAR ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Semester</label>
                            <input type="text" name="semester" class="form-control" value="<?= CURRENT_SEMESTER ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Classroom / Room</label>
                            <input type="text" name="room" class="form-control" value="Room 301" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT CLASS MODAL -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Class Offering</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/classes.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_class_id">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Class Code *</label>
                            <input type="text" name="class_code" id="edit_class_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Subject *</label>
                            <select name="subject_id" id="edit_subject_id" class="form-select" required>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>"><?= e($sub['subject_code']) ?> - <?= e($sub['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Section *</label>
                            <select name="section_id" id="edit_section_id" class="form-select" required>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= $sec['id'] ?>"><?= e($sec['section_name']) ?> (<?= e($sec['course_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Assigned Teacher *</label>
                            <select name="teacher_id" id="edit_teacher_id" class="form-select" required>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= $t['id'] ?>">Prof. <?= e($t['first_name']) ?> <?= e($t['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Academic Year</label>
                            <input type="text" name="academic_year" id="edit_academic_year" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Semester</label>
                            <input type="text" name="semester" id="edit_semester" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Classroom / Room</label>
                            <input type="text" name="room" id="edit_room" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($currentClass): ?>
<!-- ENROLL STUDENT MODAL -->
<div class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2 text-primary"></i> Enroll Student in Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/classes.php?view_roster=<?= $selectedClassId ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="enroll_student">
                <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Student *</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach ($allStudents as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= e($st['student_id']) ?> - <?= e($st['last_name']) ?>, <?= e($st['first_name']) ?> (<?= e($st['course_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Enroll Student</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$extraScripts = "
<script>
    document.querySelectorAll('.edit-class-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.class);
            document.getElementById('edit_class_id').value = data.id;
            document.getElementById('edit_class_code').value = data.class_code;
            document.getElementById('edit_subject_id').value = data.subject_id;
            document.getElementById('edit_section_id').value = data.section_id;
            document.getElementById('edit_teacher_id').value = data.teacher_id;
            document.getElementById('edit_academic_year').value = data.academic_year;
            document.getElementById('edit_semester').value = data.semester;
            document.getElementById('edit_room').value = data.room;
            document.getElementById('edit_status').value = data.status;

            const modal = new bootstrap.Modal(document.getElementById('editClassModal'));
            modal.show();
        });
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
