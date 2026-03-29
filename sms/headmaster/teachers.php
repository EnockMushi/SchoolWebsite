<?php
require_once '../includes/header.php';
checkRole(['headmaster']);

// Handle Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_teacher'])) {
    $teacher_id = $_POST['teacher_id'];
    $class_id = $_POST['class_id'];
    
    // Check if already assigned
    $stmt = $pdo->prepare("SELECT id FROM teacher_assignments WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE teacher_assignments SET class_id = ? WHERE teacher_id = ?");
        $stmt->execute([$class_id, $teacher_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO teacher_assignments (teacher_id, class_id) VALUES (?, ?)");
        $stmt->execute([$class_id, $teacher_id]);
    }
    flash('msg', 'Teacher assigned successfully.');
}

// Get all teachers
$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name");
$teachers = $stmt->fetchAll();

// Get all classes
$stmt = $pdo->query("SELECT id, class_name, section FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get current assignments
$stmt = $pdo->query("
    SELECT u.full_name, u.id as teacher_id, c.class_name, c.section, ta.id 
    FROM teacher_assignments ta 
    JOIN users u ON ta.teacher_id = u.id 
    JOIN classes c ON ta.class_id = c.id
    ORDER BY c.class_name, u.full_name
");
$assignments = $stmt->fetchAll();
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-person-badge-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Teacher Assignments</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Link teachers to classes and sections.</p>
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

<div class="row g-4">
    <!-- Assignment Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-person-badge-fill text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">Assign Teacher</h5>
                </div>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Select Teacher</label>
                        <select name="teacher_id" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-3" required>
                            <option value="">Choose a teacher...</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo $t['full_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Select Class</label>
                        <select name="class_id" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-3" required>
                            <option value="">Choose a class...</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name']; ?> (<?php echo $c['section']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_teacher" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">
                        <i class="bi bi-link-45deg me-2"></i> Save Assignment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Assignments Table -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-list-check text-primary fs-4"></i>
                        <h5 class="card-title fw-bold mb-0">Current Assignments</h5>
                    </div>
                    <span class="badge bg-transparent border border-primary text-primary rounded-pill px-3 py-2"><?php echo count($assignments); ?> Active</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="bg-body-secondary">
                            <tr>
                                <th class="border-0 rounded-start-3 ps-3 py-3 text-secondary small fw-bold text-uppercase">Teacher Name</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Assigned Class</th>
                                <th class="border-0 rounded-end-3 py-3 text-secondary small fw-bold text-uppercase text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="icon-box-pro" style="width: 35px; height: 35px; min-width: 35px;">
                                                <i class="bi bi-person fs-6"></i>
                                            </div>
                                            <a href="../profile.php?id=<?php echo $a['teacher_id']; ?>" class="text-decoration-none text-body hover-underline">
                                                <?php echo $a['full_name']; ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-transparent text-primary border border-primary rounded-pill px-3">
                                            <?php echo $a['class_name']; ?> (<?php echo $a['section']; ?>)
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-transparent text-success border border-success rounded-pill px-3 py-1 small">
                                            <i class="bi bi-check-circle me-1"></i> Assigned
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-secondary">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                        No teacher assignments found.
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

<?php require_once '../includes/footer.php'; ?>
