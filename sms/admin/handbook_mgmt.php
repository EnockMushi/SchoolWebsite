<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster']);

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM student_handbook WHERE id = ?");
    if ($stmt->execute([$id])) {
        flash('msg', 'Chapter deleted successfully!');
        redirect('handbook_mgmt.php');
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_chapter'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $chapter_number = sanitize($_POST['chapter_number']);
    $title = sanitize($_POST['title']);
    $content = $_POST['content'];
    $sort_order = (int)$_POST['sort_order'];

    if (empty($title) || empty($content)) {
        $error = "Title and Content are required.";
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE student_handbook SET chapter_number = ?, title = ?, content = ?, sort_order = ? WHERE id = ?");
            $result = $stmt->execute([$chapter_number, $title, $content, $sort_order, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO student_handbook (chapter_number, title, content, sort_order) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$chapter_number, $title, $content, $sort_order]);
        }

        if ($result) {
            flash('msg', 'Handbook updated successfully!');
            redirect('handbook_mgmt.php');
        } else {
            $error = "Error saving handbook data.";
        }
    }
}

// Get Chapter for Editing
$edit_chapter = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM student_handbook WHERE id = ?");
    $stmt->execute([$id]);
    $edit_chapter = $stmt->fetch();
}

// Get All Chapters
$stmt = $pdo->query("SELECT * FROM student_handbook ORDER BY sort_order ASC, chapter_number ASC");
$chapters = $stmt->fetchAll();
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-book fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Student Handbook Management</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Create and modify student handbook chapters.</p>
            </div>
        </div>
        <a href="javascript:history.back()" class="btn btn-body-secondary rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0 shadow-sm">
            <i class="bi bi-arrow-left-circle-fill text-primary"></i>
            <span class="fw-bold small text-secondary">Back</span>
        </a>
    </div>
</div>

<?php flash('msg'); ?>
<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Form Section -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><?php echo $edit_chapter ? 'Edit Chapter' : 'Add New Chapter'; ?></h5>
                <form action="" method="POST">
                    <input type="hidden" name="id" value="<?php echo $edit_chapter['id'] ?? ''; ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">Chapter #</label>
                            <input type="text" name="chapter_number" class="form-control bg-body-tertiary border-0 py-2" value="<?php echo $edit_chapter['chapter_number'] ?? ''; ?>" placeholder="e.01">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-secondary">Chapter Title</label>
                            <input type="text" name="title" class="form-control bg-body-tertiary border-0 py-2" value="<?php echo $edit_chapter['title'] ?? ''; ?>" placeholder="e.g., Attendance Policy" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Content/Summary</label>
                            <textarea name="content" class="form-control bg-body-tertiary border-0 py-2" rows="6" placeholder="Describe the policy or rules..." required><?php echo $edit_chapter['content'] ?? ''; ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Display Order</label>
                            <input type="number" name="sort_order" class="form-control bg-body-tertiary border-0 py-2" value="<?php echo $edit_chapter['sort_order'] ?? '0'; ?>">
                            <small class="text-muted">Lower numbers appear first.</small>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" name="save_chapter" class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm">
                                <i class="bi bi-check-circle-fill me-2"></i> Save Handbook Chapter
                            </button>
                            <?php if ($edit_chapter): ?>
                                <a href="handbook_mgmt.php" class="btn btn-body-secondary w-100 py-2 rounded-3 fw-bold mt-2 border-0 shadow-sm">Cancel Editing</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- List Section -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Existing Handbook Chapters</h5>
                <?php if (empty($chapters)): ?>
                    <div class="text-center py-5 text-secondary opacity-50">
                        <i class="bi bi-book fs-1 d-block mb-2"></i>
                        No chapters added yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-0">
                            <thead>
                                <tr class="text-secondary small text-uppercase">
                                    <th class="border-0">#</th>
                                    <th class="border-0">Title</th>
                                    <th class="border-0">Order</th>
                                    <th class="border-0 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chapters as $chapter): ?>
                                    <tr>
                                        <td class="border-0 fw-bold text-primary"><?php echo $chapter['chapter_number']; ?></td>
                                        <td class="border-0">
                                            <div class="fw-bold"><?php echo $chapter['title']; ?></div>
                                            <div class="small text-secondary text-truncate" style="max-width: 250px;"><?php echo strip_tags($chapter['content']); ?></div>
                                        </td>
                                        <td class="border-0 small text-secondary"><?php echo $chapter['sort_order']; ?></td>
                                        <td class="border-0 text-end">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="?edit=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-body-secondary rounded-circle border-0 shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <i class="bi bi-pencil-fill text-primary" style="font-size: 0.85rem;"></i>
                                                </a>
                                                <a href="?delete=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-body-secondary rounded-circle border-0 shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="return confirm('Are you sure you want to delete this chapter?')">
                                                    <i class="bi bi-trash-fill text-danger" style="font-size: 0.85rem;"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
