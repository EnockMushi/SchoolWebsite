<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Handle AJAX Progress History Request BEFORE any HTML output
if (isset($_GET['get_progress'])) {
    header('Content-Type: application/json');
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['teacher'])) {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    $student_id = $_GET['get_progress'];
    $teacher_id = $_SESSION['user_id'];
    
    // Get assigned class for security check
    $stmt = $pdo->prepare("SELECT class_id FROM teacher_assignments WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $assignment = $stmt->fetch();
    $class_id = $assignment ? $assignment['class_id'] : null;

    if (!$class_id) {
        echo json_encode([]);
        exit();
    }
    
    // Ensure teacher only sees progress for their class
    $stmt = $pdo->prepare("
        SELECT sp.*, u.full_name as teacher_name 
        FROM student_progress sp 
        JOIN users u ON sp.teacher_id = u.id 
        JOIN students s ON sp.student_id = s.id
        WHERE sp.student_id = ? AND s.class_id = ?
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute([$student_id, $class_id]);
    $progress = $stmt->fetchAll();
    echo json_encode($progress);
    exit();
}

require_once '../includes/header.php';
checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Get assigned class
$stmt = $pdo->prepare("SELECT c.* FROM classes c JOIN teacher_assignments ta ON c.id = ta.class_id WHERE ta.teacher_id = ?");
$stmt->execute([$teacher_id]);
$assigned_class = $stmt->fetch();

if (!$assigned_class) {
    echo "<div class='alert alert-warning rounded-4 border-0 shadow-sm p-4 text-center reveal'>
            <i class='bi bi-exclamation-triangle-fill fs-1 text-warning d-block mb-3'></i>
            <h4 class='fw-bold'>Access Restricted</h4>
            <p class='mb-0 text-secondary'>You are not currently assigned to any class. Please contact the Headmaster for assignment.</p>
          </div>";
    require_once '../includes/footer.php';
    exit();
}

$class_id = $assigned_class['id'];

// Handle Edit Student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $student_id = $_POST['student_id'];
    $reg_number = sanitize($_POST['reg_number']);
    $full_name = sanitize($_POST['full_name']);
    $parent_id = $_POST['parent_id'];
    
    $stmt = $pdo->prepare("UPDATE students SET reg_number = ?, full_name = ?, parent_id = ?, updated_by = ? WHERE id = ? AND class_id = ?");
    $stmt->execute([$reg_number, $full_name, $parent_id, $_SESSION['user_id'], $student_id, $class_id]);
    flash('msg', 'Student information updated successfully.');
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $reg_number = sanitize($_POST['reg_number']);
    $full_name = sanitize($_POST['full_name']);
    $parent_id = $_POST['parent_id'];
    // Insert Student
    $stmt = $pdo->prepare("INSERT INTO students (reg_number, full_name, class_id, parent_id, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$reg_number, $full_name, $class_id, $parent_id, $_SESSION['user_id']]);
    
    flash('msg', 'Student registered successfully.');
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['action'];
    $ids = $_POST['student_ids'] ?? [];
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'delete') {
            // Ensure teacher can only delete students from their own class
            $stmt = $pdo->prepare("DELETE FROM students WHERE id IN ($placeholders) AND class_id = ?");
            $params = array_merge($ids, [$class_id]);
            $stmt->execute($params);
            flash('msg', count($ids) . ' students deleted successfully.');
        }
    }
    header("Location: students.php");
    exit();
}

// Handle Add Progress Comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_progress'])) {
    $student_id = $_POST['student_id'];
    $comment = sanitize($_POST['comment']);
    
    if (!empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO student_progress (student_id, teacher_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $teacher_id, $comment]);
        flash('msg', 'Progress comment added successfully.');
    }
    header("Location: students.php");
    exit();
}

// Get Parents for dropdown
$stmt = $pdo->query("SELECT id, full_name, phone FROM users WHERE role = 'parent' ORDER BY full_name");
$parents = $stmt->fetchAll();

// Get My Students
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name as parent_name, u.phone as parent_phone, u.email as parent_email,
           creator.full_name as creator_name, updator.full_name as updator_name
    FROM students s 
    LEFT JOIN users u ON s.parent_id = u.id 
    LEFT JOIN users creator ON s.created_by = creator.id
    LEFT JOIN users updator ON s.updated_by = updator.id
    WHERE s.class_id = ?
    ORDER BY s.full_name
");
$stmt->execute([$class_id]);
$students = $stmt->fetchAll();
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-person-lines-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">
                    Student Registry - <?php echo htmlspecialchars($assigned_class['class_name'] ?? $assigned_class['name'] ?? 'Class'); ?>
                </h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Manage enrollments for your assigned class.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
</div>

<div class="row g-4 reveal">
    <!-- Registration Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 sticky-top" style="top: 100px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-person-plus-fill text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0" id="formTitle">Register New Student</h5>
                </div>
                <form action="" method="POST" id="studentForm">
                    <input type="hidden" name="student_id" id="student_id">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Registration Number</label>
                        <input type="text" name="reg_number" id="reg_number" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" required placeholder="REG/2026/001">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" required placeholder="Student Full Name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Parent/Guardian</label>
                        <select name="parent_id" id="parent_id" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-3" required>
                            <option value="">-- Select Parent --</option>
                            <?php foreach ($parents as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo $p['full_name']; ?> (<?php echo $p['phone']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="btnGroup">
                        <button type="submit" name="add_student" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">
                            <i class="bi bi-check-circle-fill me-2"></i> Register Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill text-primary fs-4"></i>
                        <h5 class="card-title fw-bold mb-0">My Students</h5>
                    </div>
                    <div class="d-flex gap-2">
                        <div id="bulkActions" class="d-none animate__animated animate__fadeIn">
                            <form action="" method="POST" id="bulkForm" class="d-flex gap-2">
                                <input type="hidden" name="bulk_action" value="1">
                                <select name="action" class="form-select form-select-sm bg-body-secondary border shadow-sm rounded-pill px-3" required style="width: 150px;">
                                    <option value="">Bulk Actions</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                                <button type="button" onclick="confirmBulkAction()" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">Apply</button>
                            </form>
                        </div>
                        <span class="badge border border-primary text-primary rounded-pill px-3 py-2 bg-transparent"><?php echo count($students); ?> Enrolled</span>
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
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Student</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Parent Details</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Audit Trail</th>
                                <th class="border-0 rounded-end-3 py-3 text-secondary small fw-bold text-uppercase text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td class="px-3">
                                        <div class="form-check">
                                            <input class="form-check-input student-checkbox shadow-none" type="checkbox" name="student_ids[]" value="<?php echo $s['id']; ?>" form="bulkForm">
                                        </div>
                                    </td>
                                    <td>
                                        <a href="../admin/student_details.php?id=<?php echo $s['id']; ?>" class="text-decoration-none text-body hover-underline">
                                            <div class="fw-bold"><?php echo $s['full_name']; ?></div>
                                        </a>
                                        <div class="small text-primary font-monospace">
                                            <a href="../admin/student_details.php?id=<?php echo $s['id']; ?>" class="text-decoration-none text-primary hover-underline">
                                                <?php echo $s['reg_number']; ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small text-body">
                                            <?php if ($s['parent_id']): ?>
                                                <a href="../profile.php?id=<?php echo $s['parent_id']; ?>" class="text-decoration-none text-body hover-underline">
                                                    <?php echo $s['parent_name']; ?>
                                                </a>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-secondary"><i class="bi bi-telephone-fill me-1 tiny"></i> <?php echo $s['parent_phone'] ?: 'No phone'; ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-secondary" style="font-size: 0.75rem;">
                                            <?php if ($s['creator_name']): ?>
                                                <div><i class="bi bi-plus-circle me-1"></i> Added by: 
                                                    <a href="../profile.php?id=<?php echo $s['created_by']; ?>" class="text-decoration-none text-secondary fw-bold hover-underline">
                                                        <?php echo $s['creator_name']; ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($s['updator_name']): ?>
                                                <div class="mt-1"><i class="bi bi-pencil-circle me-1"></i> Updated by: 
                                                    <a href="../profile.php?id=<?php echo $s['updated_by']; ?>" class="text-decoration-none text-secondary fw-bold hover-underline">
                                                        <?php echo $s['updator_name']; ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <?php if ($s['parent_id']): ?>
                                                <a href="communication.php?user_id=<?php echo $s['parent_id']; ?>" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border p-0 hover-translate" style="width: 32px; height: 32px;" title="Message Parent">
                                                    <i class="bi bi-chat-dots text-primary"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($s)); ?>)" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border p-0 hover-translate" style="width: 32px; height: 32px;" title="Edit Student">
                                                <i class="bi bi-pencil-square text-secondary"></i>
                                            </button>
                                            <button type="button" onclick="deleteStudent(<?php echo $s['id']; ?>)" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border p-0 hover-translate" style="width: 32px; height: 32px;" title="Delete Student">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                            <a href="attendance.php?student_id=<?php echo $s['id']; ?>" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border p-0 hover-translate" style="width: 32px; height: 32px;" title="Attendance">
                                                <i class="bi bi-calendar-check text-success"></i>
                                            </a>
                                            <button type="button" onclick="openProgressModal(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['full_name']); ?>')" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border p-0 hover-translate" style="width: 32px; height: 32px;" title="Progress Remarks">
                                                <i class="bi bi-journal-text text-info"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-secondary">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                        No students registered in this class.
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

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box-pro">
                        <i class="bi bi-journal-text fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="progressTitle">Student Progress Remarks</h5>
                        <p class="text-secondary small mb-0" id="progressStudentName"></p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Add Comment Form -->
                <form action="" method="POST" class="mb-4">
                    <input type="hidden" name="student_id" id="progress_student_id">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">New Progress Comment</label>
                        <textarea name="comment" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" rows="3" required placeholder="Describe the student's progress, behavior, or achievements..."></textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="add_progress" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="bi bi-send-fill me-1"></i> Post Comment
                        </button>
                    </div>
                </form>

                <hr class="my-4 opacity-25">

                <!-- History List -->
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Remark History</h6>
                <div id="progressHistory" class="progress-timeline">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress-timeline {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 5px;
}
.progress-item {
    position: relative;
    padding-left: 20px;
    border-left: 2px solid var(--bs-border-color);
    margin-bottom: 20px;
}
.progress-item::before {
    content: '';
    position: absolute;
    left: -7px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #0dcaf0;
    border: 2px solid var(--bs-body-bg);
}
</style>

<script>
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

let progressModal;
document.addEventListener('DOMContentLoaded', function() {
    progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
});

function openProgressModal(id, name) {
    document.getElementById('progress_student_id').value = id;
    document.getElementById('progressStudentName').innerText = name;
    
    const historyContainer = document.getElementById('progressHistory');
    historyContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-info" role="status"></div><p class="mt-2 text-secondary">Loading history...</p></div>';
    
    progressModal.show();
    
    fetch('students.php?get_progress=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                historyContainer.innerHTML = '<div class="text-center py-5 text-secondary"><i class="bi bi-chat-left-dots fs-1 opacity-25 d-block mb-3"></i>No progress remarks recorded yet.</div>';
                return;
            }
            
            let html = '';
            data.forEach(item => {
                const date = new Date(item.created_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'});
                html += `
                    <div class="progress-item">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="fw-bold text-body small">${item.teacher_name}</span>
                            <span class="text-secondary" style="font-size: 0.7rem;">${date}</span>
                        </div>
                        <div class="text-secondary small bg-body-secondary p-2 rounded-3 border-start border-info border-3">
                            ${item.comment}
                        </div>
                    </div>
                `;
            });
            historyContainer.innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching progress:', error);
            historyContainer.innerHTML = '<div class="text-center py-5 text-danger"><i class="bi bi-exclamation-circle fs-1 d-block mb-3"></i>Failed to load history. Please try again.</div>';
        });
}

function deleteStudent(id) {
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
    document.getElementById('student_id').value = student.id;
    document.getElementById('reg_number').value = student.reg_number;
    document.getElementById('full_name').value = student.full_name;
    document.getElementById('parent_id').value = student.parent_id;
    
    document.getElementById('btnGroup').innerHTML = `
        <div class="d-grid gap-2">
            <button type="submit" name="edit_student" class="btn btn-primary rounded-3 py-2 fw-bold">Update Information</button>
            <button type="button" onclick="window.location.reload()" class="btn btn-body-secondary rounded-3 py-2">Cancel Edit</button>
        </div>
    `;
}
</script>

<?php require_once '../includes/footer.php'; ?>
