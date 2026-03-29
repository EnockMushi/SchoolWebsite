<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster']);

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $full_name = sanitize($_POST['full_name']);
    $reg_number = sanitize($_POST['reg_number']);
    $class_id = $_POST['class_id'];
    $parent_id = $_POST['parent_id'];
    $created_by = !empty($_POST['created_by']) ? $_POST['created_by'] : $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO students (full_name, reg_number, class_id, parent_id, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $reg_number, $class_id, $parent_id, $created_by]);
        
        flash('msg', 'Student enrolled successfully.');
    } catch (Exception $e) {
        flash('msg', 'Error enrolling student: ' . $e->getMessage(), 'alert alert-danger');
    }
}

// Handle Edit Student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $id = $_POST['student_id'];
    $full_name = sanitize($_POST['full_name']);
    $reg_number = sanitize($_POST['reg_number']);
    $class_id = $_POST['class_id'];
    $parent_id = $_POST['parent_id'];
    
    // Admin can override audit data
    $created_by = $_POST['created_by'] ?? null;
    $updated_by = !empty($_POST['updated_by']) ? $_POST['updated_by'] : $_SESSION['user_id'];
    
    try {
        if ($_SESSION['role'] === 'admin' && $created_by !== null) {
            $stmt = $pdo->prepare("UPDATE students SET full_name = ?, reg_number = ?, class_id = ?, parent_id = ?, created_by = ?, updated_by = ? WHERE id = ?");
            $stmt->execute([$full_name, $reg_number, $class_id, $parent_id, $created_by, $updated_by, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE students SET full_name = ?, reg_number = ?, class_id = ?, parent_id = ?, updated_by = ? WHERE id = ?");
            $stmt->execute([$full_name, $reg_number, $class_id, $parent_id, $updated_by, $id]);
        }
        
        flash('msg', 'Student information updated.');
    } catch (Exception $e) {
        flash('msg', 'Error updating student: ' . $e->getMessage(), 'alert alert-danger');
    }
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['action'];
    $ids = $_POST['student_ids'] ?? [];
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'delete') {
            // Delete students (cascade handles related records if configured, otherwise manually handle)
            $stmt = $pdo->prepare("DELETE FROM students WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            flash('msg', count($ids) . ' students deleted successfully.');
        } elseif ($action === 'promote') {
            // Placeholder for promotion logic - maybe just a message for now or simple class update
            // For now let's just allow deleting
        }
    }
    header("Location: students.php");
    exit();
}

$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('admin', 'headmaster', 'teacher') ORDER BY full_name");
$staff_members = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'parent' ORDER BY full_name");
$parents = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT s.*, c.class_name, u.full_name as parent_name,
           creator.full_name as creator_name, updator.full_name as updator_name
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN users u ON s.parent_id = u.id
    LEFT JOIN users creator ON s.created_by = creator.id
    LEFT JOIN users updator ON s.updated_by = updator.id
    ORDER BY c.class_name, s.full_name
");
$students = $stmt->fetchAll();
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-4 p-md-5 mb-4 text-center">
    <div class="d-flex flex-column align-items-center justify-content-center gap-4">
        <div class="min-width-0">
            <h4 class="fw-bold mb-1 text-primary fs-4 fs-md-3 text-truncate">Student Management</h4>
            <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Manage enrollment, records, and class assignments.</p>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <button type="button" class="btn btn-primary btn-massive shadow-sm border-0" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                Enroll New Student
            </button>
            <a href="javascript:history.back()" class="btn btn-body-secondary btn-massive shadow-sm border-0">
                Go Back
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Add/Edit Student Form -->
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-person-plus-fill text-primary fs-4" id="formIcon"></i>
                    <h5 class="card-title fw-bold mb-0" id="formTitle">Enroll Student</h5>
                </div>
                <form action="" method="POST" id="studentForm">
                    <input type="hidden" name="student_id" id="student_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-person text-secondary"></i></span>
                                    <input type="text" name="full_name" id="full_name" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" required placeholder="Full Name">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Registration Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-hash text-secondary"></i></span>
                                    <input type="text" name="reg_number" id="reg_number" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" required placeholder="REG/2026/001">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Class</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-mortarboard text-secondary"></i></span>
                                    <select name="class_id" id="class_id" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-end-3" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>"><?php echo $class['class_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Parent/Guardian</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-people text-secondary"></i></span>
                                    <select name="parent_id" id="parent_id" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-end-3">
                                        <option value="">Select Parent</option>
                                        <?php foreach ($parents as $parent): ?>
                                            <option value="<?php echo $parent['id']; ?>"><?php echo $parent['full_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="adminAuditFields" style="display: none;">
                        <hr class="my-4 opacity-25">
                        <p class="small fw-bold text-uppercase text-secondary mb-3">Audit Override (Admin Only)</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary">Created By</label>
                                    <select name="created_by" id="created_by" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-3">
                                        <option value="">None</option>
                                        <?php foreach ($staff_members as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>"><?php echo $staff['full_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label small fw-semibold text-secondary">Updated By</label>
                                    <select name="updated_by" id="updated_by" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-3">
                                        <option value="">None</option>
                                        <?php foreach ($staff_members as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>"><?php echo $staff['full_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="btnGroup" class="mt-2">
                        <button type="submit" name="add_student" class="btn btn-primary btn-massive w-100">
                            Enroll Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-mortarboard-fill text-primary fs-4"></i>
                        <h5 class="card-title fw-bold mb-0">System Students</h5>
                    </div>
                    <div class="d-flex gap-2">
                        <div id="bulkActions" class="d-none animate__animated animate__fadeIn">
                            <form action="" method="POST" id="bulkForm" class="d-flex gap-2">
                                <input type="hidden" name="bulk_action" value="1">
                                <select name="action" class="form-select form-select-sm bg-body-secondary border-0 shadow-sm rounded-pill px-3" required style="width: 150px;">
                                    <option value="">Bulk Actions</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                                <button type="button" onclick="confirmBulkAction()" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">Apply</button>
                            </form>
                        </div>
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-search text-secondary"></i></span>
                            <input type="text" id="studentSearch" class="form-control bg-body-secondary border-0" placeholder="Search students...">
                        </div>
                        <span class="badge border border-primary text-primary rounded-pill px-3 py-2 d-flex align-items-center bg-transparent"><?php echo count($students); ?> Total</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0" id="studentsTable">
                        <thead class="bg-body-secondary">
                            <tr>
                                <th class="border-0 rounded-start-3 px-3 py-3 text-secondary small fw-bold text-uppercase" style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input shadow-none" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Reg No</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Student Name</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Class</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Parent</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Audit Trail</th>
                                <th class="border-0 rounded-end-3 py-3 text-center text-secondary small fw-bold text-uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <tr id="noResults" style="display: none;">
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                                    No students found matching your search.
                                </td>
                            </tr>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="px-3">
                                        <div class="form-check">
                                            <input class="form-check-input student-checkbox shadow-none" type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" form="bulkForm">
                                        </div>
                                    </td>
                                    <td class="fw-bold text-primary small">
                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none text-primary hover-underline">
                                            <?php echo $student['reg_number']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none hover-underline">
                                            <div class="fw-bold text-primary"><?php echo $student['full_name']; ?></div>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge border border-secondary text-secondary rounded-pill px-3 bg-transparent"><?php echo $student['class_name'] ?: 'Not Assigned'; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small">
                                            <?php if ($student['parent_id']): ?>
                                                <a href="../profile.php?id=<?php echo $student['parent_id']; ?>" class="text-decoration-none text-primary hover-underline">
                                                    <?php echo $student['parent_name']; ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-secondary">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-secondary" style="font-size: 0.75rem;">
                                            <?php if ($student['creator_name']): ?>
                                                <div><i class="bi bi-plus-circle me-1"></i> Added by: 
                                                    <a href="../profile.php?id=<?php echo $student['created_by']; ?>" class="text-decoration-none text-secondary fw-bold hover-underline">
                                                        <?php echo $student['creator_name']; ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($student['updator_name']): ?>
                                                <div class="mt-1"><i class="bi bi-pencil-circle me-1"></i> Updated by: 
                                                    <a href="../profile.php?id=<?php echo $student['updated_by']; ?>" class="text-decoration-none text-secondary fw-bold hover-underline">
                                                        <?php echo $student['updator_name']; ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <?php if ($student['parent_id']): ?>
                                                <a href="messages.php?user_id=<?php echo $student['parent_id']; ?>" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border-0 p-0 hover-translate" style="width: 32px; height: 32px;" title="Message Parent">
                                                    <i class="bi bi-chat-dots text-primary"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border p-0 hover-translate" style="width: 32px; height: 32px;" title="Edit Student">
                                                <i class="bi bi-pencil-square text-secondary"></i>
                                            </button>
                                            <button type="button" onclick="confirmDelete(<?php echo $student['id']; ?>)" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border p-0 hover-translate" style="width: 32px; height: 32px;" title="Remove Student">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-secondary">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                        No students found in the registry.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Search Logic
document.getElementById('studentSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#studentsTable tbody tr:not(#noResults)');
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

// Bulk Selection Logic
const selectAll = document.getElementById('selectAll');
const studentCheckboxes = document.querySelectorAll('.student-checkbox');
const bulkActions = document.getElementById('bulkActions');

function updateBulkActionsVisibility() {
    const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
    if (checkedCount > 0) {
        bulkActions.classList.remove('d-none');
    } else {
        bulkActions.classList.add('d-none');
    }
}

if (selectAll) {
    selectAll.addEventListener('change', function() {
        studentCheckboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkActionsVisibility();
    });
}

studentCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        updateBulkActionsVisibility();
        if (!this.checked) {
            selectAll.checked = false;
        } else {
            const allChecked = document.querySelectorAll('.student-checkbox:checked').length === studentCheckboxes.length;
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
    
    const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
    let message = `Are you sure you want to ${action} ${checkedCount} selected student(s)?`;
    
    if (action === 'delete') {
        message += "\n\nWARNING: This action cannot be undone!";
    }
    
    if (confirm(message)) {
        document.getElementById('bulkForm').submit();
    }
}

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone!')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="bulk_action" value="1">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="student_ids[]" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function editStudent(student) {
    document.getElementById('formTitle').innerText = 'Edit Student Details';
    document.getElementById('formIcon').className = 'bi bi-pencil-square text-primary fs-4';
    document.getElementById('student_id').value = student.id;
    document.getElementById('full_name').value = student.full_name;
    document.getElementById('reg_number').value = student.reg_number;
    document.getElementById('class_id').value = student.class_id;
    document.getElementById('parent_id').value = student.parent_id;
    
    // Show audit fields for admin if they exist
    const auditFields = document.getElementById('adminAuditFields');
    if (auditFields && <?php echo $_SESSION['role'] === 'admin' ? 'true' : 'false'; ?>) {
        auditFields.style.display = 'block';
        if (student.created_by) document.getElementById('created_by').value = student.created_by;
        if (student.updated_by) document.getElementById('updated_by').value = student.updated_by;
    }
    
    document.getElementById('btnGroup').innerHTML = `
        <div class="d-grid gap-2">
            <button type="submit" name="edit_student" class="btn btn-primary rounded-3 py-2 fw-bold">Update Information</button>
            <button type="button" onclick="window.location.reload()" class="btn btn-body-secondary rounded-3 py-2">Cancel Edit</button>
        </div>
    `;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('studentSearch').addEventListener('input', function() {
    let value = this.value.toLowerCase().trim();
    let rows = document.querySelectorAll('#studentsTable tbody tr:not(#noResults)');
    let hasResults = false;
    
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        if (text.includes(value)) {
            row.style.display = '';
            hasResults = true;
        } else {
            row.style.display = 'none';
        }
    });
    
    let noResults = document.getElementById('noResults');
    if (noResults) {
        noResults.style.display = hasResults ? 'none' : '';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
