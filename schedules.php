<?php
/**
 * Administrator - Class Schedule Management Module
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
                $classController->handleAddSchedule();
                break;
            case 'delete':
                $classController->handleDeleteSchedule();
                break;
        }
    }
}

$schedules = $classModel->getSchedules();
$classes = $classModel->getAll(['status' => 'active']);

$pageTitle = 'Class Schedule Management';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Class Schedules & Timetable</h3>
        <p class="text-secondary small mb-0">Define meeting days, class periods, time slots, and assigned lecture rooms.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
            <i class="bi bi-clock-history me-1"></i> Add Timetable Slot
        </button>
    </div>
</div>

<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>Day of Week</th>
                        <th>Class Code & Subject</th>
                        <th>Section</th>
                        <th>Instructor</th>
                        <th>Time Slot</th>
                        <th>Classroom / Room</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $sc): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1">
                                    <i class="bi bi-calendar-event me-1"></i><?= e($sc['day_of_week']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($sc['class_code']) ?></div>
                                <small class="text-muted"><?= e($sc['subject_name']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($sc['section_name']) ?></span></td>
                            <td><small class="text-secondary"><?= e($sc['teacher_name']) ?></small></td>
                            <td>
                                <span class="fw-semibold text-dark">
                                    <?= format_time($sc['start_time']) ?> - <?= format_time($sc['end_time']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-door-open me-1"></i><?= e($sc['room']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="<?= BASE_URL ?>/admin/schedules.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $sc['id'] ?>">
                                    <button type="button" class="btn btn-sm btn-light border text-danger btn-delete-confirm" title="Delete Schedule">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD SCHEDULE MODAL -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-fill me-2 text-primary"></i> Add Schedule Slot</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/schedules.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Class *</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>">
                                    <?= e($cl['class_code']) ?>: <?= e($cl['subject_name']) ?> (<?= e($cl['section_name']) ?> - <?= e($cl['teacher_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Day of Week *</label>
                        <select name="day_of_week" class="form-select" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" required value="09:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">End Time *</label>
                            <input type="time" name="end_time" class="form-control" required value="10:30">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Room / Venue</label>
                        <input type="text" name="room" class="form-control" value="Room 301" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../views/layouts/footer.php';
?>
