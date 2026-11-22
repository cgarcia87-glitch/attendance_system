<?php
/**
 * Administrator - User Accounts Management Module
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../controllers/UserController.php';

require_role(['admin']);

$userModel = new User();
$userController = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $userController->handleCreateUser();
                break;
            case 'update':
                $userController->handleUpdateUser();
                break;
            case 'delete':
                $userController->handleDeleteUser();
                break;
        }
    }
}

$roleFilter = $_GET['role'] ?? null;
$users = $userModel->getAll($roleFilter);
$userStats = $userModel->getStats();

$pageTitle = 'System Users Management';
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/layouts/sidebar.php';
require_once __DIR__ . '/../views/layouts/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">User Accounts & Roles</h3>
        <p class="text-secondary small mb-0">Manage system login credentials, grant administrative privileges, and secure accounts.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill me-1"></i> Create User Account
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="stat-card p-3">
            <div class="stat-title">Total Users</div>
            <div class="stat-value fs-3"><?= number_format($userStats['total_users']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card p-3 border-primary">
            <div class="stat-title text-primary">Administrators</div>
            <div class="stat-value fs-3 text-primary"><?= number_format($userStats['total_admins']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card p-3 border-success">
            <div class="stat-title text-success">Teachers</div>
            <div class="stat-value fs-3 text-success"><?= number_format($userStats['total_teachers']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card p-3 border-info">
            <div class="stat-title text-info">Students</div>
            <div class="stat-value fs-3 text-info"><?= number_format($userStats['total_students']) ?></div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card-modern">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle datatable w-100">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                    </div>
                                    <span class="fw-bold text-dark"><?= e($u['full_name']) ?></span>
                                </div>
                            </td>
                            <td><span class="font-monospace text-secondary">@<?= e($u['username']) ?></span></td>
                            <td><small class="text-secondary"><?= e($u['email']) ?></small></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-primary px-2 py-1"><i class="bi bi-shield-lock-fill me-1"></i>Admin</span>
                                <?php elseif ($u['role'] === 'teacher'): ?>
                                    <span class="badge bg-success px-2 py-1"><i class="bi bi-person-video3 me-1"></i>Teacher</span>
                                <?php else: ?>
                                    <span class="badge bg-info px-2 py-1"><i class="bi bi-mortarboard-fill me-1"></i>Student</span>
                                <?php endif; ?>
                            </td>
                            <td><?= get_status_badge($u['status']) ?></td>
                            <td>
                                <small class="text-muted"><?= $u['last_login'] ? format_date($u['last_login'], 'M d, Y h:i A') : 'Never' ?></small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border edit-user-btn" 
                                            data-user='<?= json_encode($u) ?>' title="Edit User">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/users.php" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="button" class="btn btn-light border text-danger btn-delete-confirm" title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2 text-primary"></i> Create System User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/users.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="e.g. John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username *</label>
                        <input type="text" name="username" class="form-control" required placeholder="e.g. jdoe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="name@domain.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password *</label>
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Min. 6 characters">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Administrator</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit User Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/users.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_user_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name *</label>
                        <input type="text" name="full_name" id="edit_u_fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address *</label>
                        <input type="email" name="email" id="edit_u_email" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Role *</label>
                            <select name="role" id="edit_u_role" class="form-select" required>
                                <option value="admin">Administrator</option>
                                <option value="teacher">Teacher</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" id="edit_u_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Reset Password (Leave blank to keep current)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="New password (optional)">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.user);
            document.getElementById('edit_user_id').value = data.id;
            document.getElementById('edit_u_fullname').value = data.full_name;
            document.getElementById('edit_u_email').value = data.email;
            document.getElementById('edit_u_role').value = data.role;
            document.getElementById('edit_u_status').value = data.status;

            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        });
    });
</script>
";

require_once __DIR__ . '/../views/layouts/footer.php';
?>
