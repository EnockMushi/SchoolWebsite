<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster']);

// Get all students for parent linking
$stmt = $pdo->query("SELECT id, full_name, reg_number FROM students ORDER BY full_name");
$all_students = $stmt->fetchAll();

// Handle Edit User Data Fetch
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_user = $stmt->fetch();
}

// Handle Add/Update User
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = sanitize($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = sanitize($_POST['full_name']);
        $role = $_POST['role'];
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $student_id = $_POST['student_id'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$username, $password, $full_name, $role, $email, $phone]);
        $new_user_id = $pdo->lastInsertId();

        // If it's a parent and a student was selected, link them
        if ($role === 'parent' && !empty($student_id)) {
            $stmt = $pdo->prepare("UPDATE students SET parent_id = ? WHERE id = ?");
            $stmt->execute([$new_user_id, $student_id]);
        }

        flash('msg', 'User added successfully.');
    } elseif (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $username = sanitize($_POST['username']);
        $full_name = sanitize($_POST['full_name']);
        $role = $_POST['role'];
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $student_id = $_POST['student_id'] ?? null;
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, full_name=?, role=?, email=?, phone=? WHERE id=?");
            $stmt->execute([$username, $password, $full_name, $role, $email, $phone, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, role=?, email=?, phone=? WHERE id=?");
            $stmt->execute([$username, $full_name, $role, $email, $phone, $id]);
        }

        // Update student link if role is parent
        if ($role === 'parent' && !empty($student_id)) {
            $stmt = $pdo->prepare("UPDATE students SET parent_id = ? WHERE id = ?");
            $stmt->execute([$id, $student_id]);
        }

        flash('msg', 'User updated successfully.');
        header("Location: users.php");
        exit();
    } elseif (isset($_POST['bulk_action'])) {
        $action = $_POST['action'];
        $ids = $_POST['user_ids'] ?? [];
        
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders) AND role != 'admin'");
                $stmt->execute($ids);
                flash('msg', count($ids) . ' users deleted successfully.');
            } elseif ($action === 'activate') {
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id IN ($placeholders) AND role != 'admin'");
                $stmt->execute($ids);
                flash('msg', count($ids) . ' users activated successfully.');
            } elseif ($action === 'deactivate') {
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id IN ($placeholders) AND role != 'admin'");
                $stmt->execute($ids);
                flash('msg', count($ids) . ' users deactivated successfully.');
            }
        }
        header("Location: users.php");
        exit();
    }
}

// Get all users except admin
$stmt = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY role, full_name");
$users = $stmt->fetchAll();
?>

<div class="dash-header rounded-4 p-4 p-md-5 mb-4 shadow-sm border text-center">
    <div class="d-flex flex-column align-items-center justify-content-center gap-4">
        <div class="min-width-0">
            <h4 class="fw-bold mb-1 text-primary fs-4 fs-md-3 text-truncate">User Management</h4>
            <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Manage all users and their access levels.</p>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="?" class="btn btn-primary btn-massive shadow-sm border-0">
                Add New User
            </a>
            <a href="javascript:history.back()" class="btn btn-body-secondary btn-massive shadow-sm border-0">
                Go Back
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi <?php echo $edit_user ? 'bi-person-gear' : 'bi-person-plus'; ?> text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0"><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h5>
                </div>
                <form action="" method="POST">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-person text-secondary"></i></span>
                                    <input type="text" name="full_name" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $edit_user ? $edit_user['full_name'] : ''; ?>" required placeholder="e.g. John Doe">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-at text-secondary"></i></span>
                                    <input type="text" name="username" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $edit_user ? $edit_user['username'] : ''; ?>" required placeholder="e.g. jdoe">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Password <?php echo $edit_user ? '<small class="fw-normal text-secondary ms-1">(Leave blank to keep current)</small>' : ''; ?></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-shield-lock text-secondary"></i></span>
                                    <input type="password" name="password" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" <?php echo $edit_user ? '' : 'required'; ?> placeholder="••••••••">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Role</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-briefcase text-secondary"></i></span>
                                    <select name="role" id="roleSelect" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-end-3" required>
                                        <option value="headmaster" <?php echo ($edit_user && $edit_user['role'] == 'headmaster') ? 'selected' : ''; ?>>Headmaster</option>
                                        <option value="teacher" <?php echo ($edit_user && $edit_user['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                        <option value="parent" <?php echo ($edit_user && $edit_user['role'] == 'parent') ? 'selected' : ''; ?>>Parent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6" id="studentLinkSection" style="display: <?php echo ($edit_user && $edit_user['role'] == 'parent') ? 'block' : 'none'; ?>;">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Link to Student (For Parents)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-mortarboard text-secondary"></i></span>
                                    <select name="student_id" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-end-3">
                                        <option value="">Select Student</option>
                                        <?php foreach ($all_students as $student): ?>
                                            <option value="<?php echo $student['id']; ?>"><?php echo $student['full_name']; ?> (<?php echo $student['reg_number']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-envelope text-secondary"></i></span>
                                    <input type="email" name="email" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $edit_user ? $edit_user['email'] : ''; ?>" placeholder="jdoe@example.com">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-secondary">Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-telephone text-secondary"></i></span>
                                    <input type="text" name="phone" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $edit_user ? $edit_user['phone'] : ''; ?>" placeholder="+255 ...">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if ($edit_user): ?>
                            <button type="submit" name="update_user" class="btn btn-primary rounded-3 py-2 fw-bold hover-translate">Update User</button>
                            <a href="users.php" class="btn btn-body-secondary rounded-3 py-2 hover-translate">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_user" class="btn btn-primary rounded-3 py-2 fw-bold hover-translate">Add User</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill text-primary fs-4"></i>
                        <h5 class="card-title fw-bold mb-0">Existing Users</h5>
                    </div>
                    <div class="d-flex gap-2">
                        <div id="bulkActions" class="d-none animate__animated animate__fadeIn">
                            <form action="" method="POST" id="bulkForm" class="d-flex gap-2">
                                <input type="hidden" name="bulk_action" value="1">
                                <select name="action" class="form-select form-select-sm bg-body-secondary border shadow-sm rounded-pill px-3" required style="width: 150px;">
                                    <option value="">Bulk Actions</option>
                                    <option value="activate">Activate Selected</option>
                                    <option value="deactivate">Deactivate Selected</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                                <button type="button" onclick="confirmBulkAction()" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm hover-translate">Apply</button>
                            </form>
                        </div>
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-search text-secondary"></i></span>
                            <input type="text" id="userSearch" class="form-control bg-body-secondary border-0" placeholder="Search users...">
                        </div>
                        <span class="badge rounded-pill border border-primary text-primary px-3 py-2 d-flex align-items-center bg-transparent">
                            <?php echo count($users); ?> Total
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0" id="usersTable">
                        <thead class="bg-body-secondary">
                            <tr>
                                <th class="border-0 rounded-start-4 px-3 py-3 text-secondary small fw-bold text-uppercase" style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input shadow-none" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Name</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Username</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Role</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Status</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Contact</th>
                                <th class="border-0 rounded-end-4 text-end px-3 py-3 text-secondary small fw-bold text-uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <tr id="noResults" style="display: none;">
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                                    <h6 class="fw-bold">No users found matching your search.</h6>
                                </td>
                            </tr>
                            <?php foreach ($users as $user): 
                                $statusInfo = getUserStatus($user['last_seen'], $user['status']);
                            ?>
                                <tr class="border-transparent">
                                    <td class="px-3">
                                        <div class="form-check">
                                            <input class="form-check-input user-checkbox shadow-none" type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" form="bulkForm">
                                        </div>
                                    </td>
                                    <td>
                                        <a href="../profile.php?id=<?php echo $user['id']; ?>" class="text-decoration-none">
                                            <div class="fw-bold text-primary hover-underline"><?php echo $user['full_name']; ?></div>
                                        </a>
                                    </td>
                                    <td class="fw-semibold small">
                                        <a href="../profile.php?id=<?php echo $user['id']; ?>" class="text-decoration-none text-body hover-underline">
                                            <span class="text-secondary opacity-75">@</span><?php echo $user['username']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill border border-primary text-primary px-3 py-2 text-capitalize bg-transparent smaller fw-bold">
                                            <?php echo $user['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="p-1 rounded-circle <?php echo str_replace('text-', 'bg-', $statusInfo['dot']); ?> shadow-sm"></span>
                                            <span class="small fw-bold text-capitalize"><?php echo $user['status']; ?></span>
                                            <?php if ($user['status'] == 'active'): ?>
                                                <span class="smaller text-secondary ms-1 opacity-75" style="font-size: 0.65rem;">(<?php echo $statusInfo['text']; ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-secondary mb-1 d-flex align-items-center gap-2">
                                            <i class="bi bi-envelope text-primary opacity-75"></i>
                                            <span><?php echo $user['email'] ?: 'N/A'; ?></span>
                                        </div>
                                        <div class="small text-secondary d-flex align-items-center gap-2">
                                            <i class="bi bi-telephone text-primary opacity-75"></i>
                                            <span><?php echo $user['phone'] ?: 'N/A'; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end px-3">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="messages.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-body-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm border-0 p-0 hover-translate" style="width: 32px; height: 32px;" title="Chat with User">
                                                <i class="bi bi-chat-dots text-primary"></i>
                                            </a>
                                            <a href="?edit_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-body-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm border-0 p-0 hover-translate" style="width: 32px; height: 32px;" title="Edit">
                                                <i class="bi bi-pencil-square text-secondary"></i>
                                            </a>
                                            <button type="button" onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-body-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm border-0 p-0 hover-translate" style="width: 32px; height: 32px;" title="Delete">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('userSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#usersTable tbody tr:not(#noResults)');
    let visibleCount = 0;
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    document.getElementById('noResults').style.display = visibleCount === 0 ? '' : 'none';
});

document.getElementById('roleSelect').addEventListener('change', function() {
    const studentSection = document.getElementById('studentLinkSection');
    if (this.value === 'parent') {
        studentSection.style.display = 'block';
    } else {
        studentSection.style.display = 'none';
    }
});

// Bulk Selection Logic
const selectAll = document.getElementById('selectAll');
const userCheckboxes = document.querySelectorAll('.user-checkbox');
const bulkActions = document.getElementById('bulkActions');

function updateBulkActionsVisibility() {
    const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
    if (checkedCount > 0) {
        bulkActions.classList.remove('d-none');
    } else {
        bulkActions.classList.add('d-none');
    }
}

selectAll.addEventListener('change', function() {
    userCheckboxes.forEach(cb => {
        cb.checked = this.checked;
    });
    updateBulkActionsVisibility();
});

userCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        updateBulkActionsVisibility();
        if (!this.checked) {
            selectAll.checked = false;
        } else {
            const allChecked = document.querySelectorAll('.user-checkbox:checked').length === userCheckboxes.length;
            selectAll.checked = allChecked;
        }
    });
});

function confirmBulkAction() {
    const action = document.querySelector('select[name="action"]').value;
    if (!action) {
        alert('Please select an action first.');
        return;
    }
    
    const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
    let message = `Are you sure you want to ${action} ${checkedCount} selected user(s)?`;
    
    if (action === 'delete') {
        message += "\n\nWARNING: This action cannot be undone and will remove all user data!";
    }
    
    if (confirm(message)) {
        document.getElementById('bulkForm').submit();
    }
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone!')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="bulk_action" value="1">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_ids[]" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
