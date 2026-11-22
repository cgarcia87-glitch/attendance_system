<?php
/**
 * Teacher - My Classes Module
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Class.php';

require_role(['teacher', 'admin']);

$user = current_user();
$teacherModel = new Teacher();
$classModel = new ClassModel();

$teacher = $teacherModel->getByUserId($user['id']);
$teacherId = $teacher['id'] ?? 0;

$classes = $teacherModel->getAssignedClasses($teacherId);

// View specific class roster
$selectedClassId = (int)($_GET['roster'] ?? 0);
$rosterStudents = [];
$currentClass = null;
if ($selectedClassId) {
    $currentClass = $classModel->getById($selectedClassId);
    if ($currentClass && ($currentClass['teacher_id'] == $teacherId || current_role() === 'admin')) {
        $rosterStudents = $classModel->getEnrolledStudents($selectedClassId);
    }
}

$pageTitle = 'My Assigned Classes';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">My Teaching Classes</h3>
        <p class="text-secondary small mb-0">Review your assigned subjects, student cohorts, classroom schedules, and rosters.</p>
    </div>
</div>

<?php if ($currentClass): ?>
    <!-- Student Roster Panel -->
    <div class="card-modern border-primary mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-people-fill me-2"></i> Class Roster: <?= e($currentClass['class_code']) ?> &bull; <?= e($currentClass['subject_name']) ?>
                </h5>
                <small class="text-white-50">
                    Section: <?= e($currentClass['section_name']) ?> &bull; Room: <?= e($currentClass['room']) ?>
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/teacher/take_attendance.php?class_id=<?= $currentClass['id'] ?>" class="btn btn-sm btn-light fw-bold text-primary">
                    <i class="bi bi-calendar2-check-fill me-1"></i> Take Attendance
                </a>
                <a href="<?= BASE_URL ?>/teacher/my_classes.php" class="btn btn-sm btn-outline-light">Close Roster</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Email Address</th>
                            <th>Gender</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rosterStudents)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No students currently enrolled in this class.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rosterStudents as $s): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= e($s['student_id']) ?></span></td>
                                    <td><span class="fw-bold text-dark"><?= e($s['first_name']) ?> <?= e($s['last_name']) ?></span></td>
                                    <td><small class="text-secondary"><?= e($s['email']) ?></small></td>
                                    <td><?= e($s['gender']) ?></td>
                                    <td><small class="text-muted"><?= format_date($s['enrollment_date']) ?></small></td>
                                    <td><span class="badge bg-success-subtle text-success">Active Enrolled</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Class Cards Grid -->
<div class="row g-4">
    <?php if (empty($classes)): ?>
        <div class="col-12">
            <div class="card-modern p-5 text-center text-muted">
                <i class="bi bi-journal-x fs-1 text-secondary mb-3"></i>
                <h5>No Classes Currently Assigned</h5>
                <p class="small">Please contact the system administrator to assign subjects and sections to your profile.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($classes as $c): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card-modern h-100 p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                <?= e($c['class_code']) ?>
                            </span>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-door-open me-1"></i><?= e($c['room']) ?>
                            </span>
                        </div>

                        <h5 class="fw-bold text-dark mb-1"><?= e($c['subject_name']) ?></h5>
                        <div class="text-muted small mb-3">
                            <span class="fw-semibold text-secondary"><?= e($c['subject_code']) ?></span> &bull; <?= (int)$c['units'] ?> Credit Units
                        </div>

                        <div class="p-3 bg-light rounded-3 border mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Degree / Section:</span>
                                <strong class="text-dark"><?= e($c['section_name']) ?> (<?= e($c['course_code']) ?>)</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Year Level:</span>
                                <span class="text-secondary"><?= e($c['year_level_name']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Enrolled Students:</span>
                                <strong class="text-primary"><?= (int)$c['enrolled_count'] ?> Students</strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top">
                        <a href="<?= BASE_URL ?>/teacher/take_attendance.php?class_id=<?= $c['id'] ?>" class="btn btn-primary flex-fill">
                            <i class="bi bi-calendar2-check-fill me-1"></i> Take Attendance
                        </a>
                        <a href="<?= BASE_URL ?>/teacher/my_classes.php?roster=<?= $c['id'] ?>" class="btn btn-outline-secondary" title="View Enrolled Students">
                            <i class="bi bi-people"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../views/layouts/footer.php';
?>
