<?php
require_once '../includes/header.php';
checkRole(['headmaster']);

// Handle Add Class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_class'])) {
    $name = sanitize($_POST['class_name']);
    $section = sanitize($_POST['section']);
    
    $stmt = $pdo->prepare("INSERT INTO classes (class_name, section) VALUES (?, ?)");
    $stmt->execute([$name, $section]);
    flash('msg', 'Class added successfully.');
}

// Handle Delete Class
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    flash('msg', 'Class deleted successfully.');
    redirect('classes.php');
}

$stmt = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC");
$classes = $stmt->fetchAll();
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-layers-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Class Management</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Create and organize school classes.</p>
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
    <!-- Add Class Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-plus-square-fill text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">Create New Class</h5>
                </div>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Class Name</label>
                        <input type="text" name="class_name" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" required placeholder="e.g. Standard 1">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Section</label>
                        <input type="text" name="section" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" required placeholder="e.g. A, B, or Blue">
                    </div>
                    <button type="submit" name="add_class" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">
                        <i class="bi bi-check-circle-fill me-2"></i> Create Class
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Classes Table -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-layers-fill text-primary fs-4"></i>
                        <h5 class="card-title fw-bold mb-0">Existing Classes</h5>
                    </div>
                    <span class="badge bg-transparent border border-primary text-primary rounded-pill px-3 py-2"><?php echo count($classes); ?> Total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="bg-body-secondary">
                            <tr>
                                <th class="border-0 rounded-start-3 ps-3 py-3 text-secondary small fw-bold text-uppercase">Class Name</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Section</th>
                                <th class="border-0 rounded-end-3 py-3 text-secondary small fw-bold text-uppercase text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <a href="../admin/class_details.php?id=<?php echo $class['id']; ?>" class="text-decoration-none text-primary hover-underline">
                                            <?php echo $class['class_name']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-transparent text-secondary border rounded-pill px-3"><?php echo $class['section']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center">
                                            <a href="?delete=<?php echo $class['id']; ?>" class="btn btn-sm btn-body-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm border p-0" style="width: 32px; height: 32px;" title="Delete Class" onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash3 text-danger"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-secondary">
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
