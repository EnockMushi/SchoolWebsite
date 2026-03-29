<?php
require_once '../includes/header.php';
checkRole(['admin']);

// Handle Add Class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_class'])) {
    $name = sanitize($_POST['class_name']);
    $section = sanitize($_POST['section']);
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    
    $stmt = $pdo->prepare("INSERT INTO classes (class_name, section, teacher_id) VALUES (?, ?, ?)");
    $stmt->execute([$name, $section, $teacher_id]);
    flash('msg', 'Class added successfully.');
}

// Handle Update Class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_class'])) {
    $id = $_POST['class_id'];
    $name = sanitize($_POST['class_name']);
    $section = sanitize($_POST['section']);
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    
    $stmt = $pdo->prepare("UPDATE classes SET class_name = ?, section = ?, teacher_id = ? WHERE id = ?");
    $stmt->execute([$name, $section, $teacher_id, $id]);
    flash('msg', 'Class updated successfully.');
    redirect('classes.php');
}

// Handle Edit Data Fetch
$edit_class = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_class = $stmt->fetch();
}

// Fetch all teachers for assignment
$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name ASC");
$teachers = $stmt->fetchAll();

// Handle Delete Class
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Check if class has students before deleting
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        flash('msg', 'Cannot delete class with enrolled students.', 'alert alert-danger');
    } else {
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        flash('msg', 'Class deleted successfully.');
    }
    redirect('classes.php');
}

$stmt = $pdo->query("
    SELECT c.*, u.full_name as teacher_name 
    FROM classes c 
    LEFT JOIN users u ON c.teacher_id = u.id 
    ORDER BY c.class_name ASC
");
$classes = $stmt->fetchAll();
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro shadow-sm" style="width: 45px; height: 45px; min-width: 45px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-building-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-truncate fs-5 fs-md-4">Class Management</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Define and organize school grade levels.</p>
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
    <!-- Add/Edit Class Form -->
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi <?php echo $edit_class ? 'bi-pencil-square' : 'bi-plus-square-fill'; ?> text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0"><?php echo $edit_class ? 'Update Class' : 'Create New Class'; ?></h5>
                </div>
                <form action="" method="POST">
                    <?php if ($edit_class): ?>
                        <input type="hidden" name="class_id" value="<?php echo $edit_class['id']; ?>">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Class Name</label>
                                <input type="text" name="class_name" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" required placeholder="e.g. Standard 1" value="<?php echo $edit_class ? $edit_class['class_name'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Section</label>
                                <input type="text" name="section" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" required placeholder="e.g. A, B, or Blue" value="<?php echo $edit_class ? $edit_class['section'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Assign Main Teacher</label>
                                <select name="teacher_id" class="form-select bg-body-secondary border-0 py-2 px-3 rounded-3">
                                    <option value="">Select Teacher (Optional)</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo ($edit_class && $edit_class['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                            <?php echo $teacher['full_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="<?php echo $edit_class ? 'edit_class' : 'add_class'; ?>" class="btn btn-primary rounded-3 py-2 fw-bold">
                            <i class="bi <?php echo $edit_class ? 'bi-check-circle-fill' : 'bi-plus-circle-fill'; ?> me-2"></i> 
                            <?php echo $edit_class ? 'Update Class' : 'Create Class'; ?>
                        </button>
                        <?php if ($edit_class): ?>
                            <a href="classes.php" class="btn btn-body-secondary border-0 rounded-3 py-2">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Classes Table -->
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-layers-fill text-primary fs-4"></i>
                        <h5 class="card-title fw-bold mb-0">Existing Classes</h5>
                    </div>
                    <span class="badge bg-body-secondary text-primary rounded-pill px-3 py-2"><?php echo count($classes); ?> Total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="bg-body-secondary">
                            <tr>
                                <th class="border-0 rounded-start-3 ps-3 py-3 text-secondary small fw-bold text-uppercase">Class Name</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Section</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Main Teacher</th>
                                <th class="border-0 rounded-end-3 py-3 text-secondary small fw-bold text-uppercase text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <a href="class_details.php?id=<?php echo $class['id']; ?>" class="text-decoration-none text-primary hover-underline">
                                            <?php echo $class['class_name']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-body-secondary text-secondary border-0 rounded-pill px-3"><?php echo $class['section']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($class['teacher_name']): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-body-secondary text-primary rounded-circle d-flex align-items-center justify-content-center small" style="width: 25px; height: 25px;">
                                                    <i class="bi bi-person-fill" style="font-size: 0.75rem;"></i>
                                                </div>
                                                <a href="../profile.php?id=<?php echo $class['teacher_id']; ?>" class="text-decoration-none text-primary hover-underline">
                                                    <span class="small fw-semibold"><?php echo $class['teacher_name']; ?></span>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-secondary small italic">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="?edit_id=<?php echo $class['id']; ?>" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border-0 p-0 hover-translate" style="width: 32px; height: 32px;" title="Edit Class">
                                                <i class="bi bi-pencil-square text-primary"></i>
                                            </a>
                                            <a href="?delete=<?php echo $class['id']; ?>" class="btn btn-body-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm border-0 p-0 hover-translate" style="width: 32px; height: 32px;" title="Delete Class" onclick="return confirm('Are you sure? This will fail if students are enrolled.')">
                                                <i class="bi bi-trash text-danger"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-secondary">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                        No classes created yet.
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
