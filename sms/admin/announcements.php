<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster']);

// Handle Edit Data Fetch
$edit_announcement = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_announcement = $stmt->fetch();
}

// Handle Add/Update Announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if POST is empty (can happen if upload exceeds post_max_size)
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
        flash('msg', 'The uploaded file is too large.', 'alert alert-danger');
        header("Location: announcements.php");
        exit();
    }
    
    if (isset($_POST['add_announcement'])) {
        $title = sanitize($_POST['title']);
        $content = $_POST['content']; // Allow some HTML or just text
        $type = $_POST['type'];
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $posted_by = $_SESSION['user_id'];
        
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../assets/images/announcements/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $image = time() . '_' . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, type, image, posted_by, is_public) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $content, $type, $image, $posted_by, $is_public])) {
                $announcement_id = $pdo->lastInsertId();
                
                // Notify all users except Admin and Headmaster
                 $notify_stmt = $pdo->prepare("
                     INSERT INTO notifications (user_id, title, message, link) 
                     SELECT id, ?, ?, ? FROM users 
                     WHERE role NOT IN ('admin', 'headmaster') AND status = 'active'
                 ");
                 $notif_title = "New " . ucfirst($type);
                 $notif_message = $title;
                 $notif_link = "dashboard.php"; // Link to dashboard where announcements are visible
                 $notify_stmt->execute([$notif_title, $notif_message, $notif_link]);

                flash('msg', 'Announcement posted successfully.');
            } else {
                flash('msg', 'Failed to post announcement.', 'alert alert-danger');
            }
        } catch (PDOException $e) {
            flash('msg', 'Database Error: ' . $e->getMessage(), 'alert alert-danger');
        }
        header("Location: announcements.php");
        exit();
    } elseif (isset($_POST['update_announcement'])) {
        $id = $_POST['announcement_id'];
        $title = sanitize($_POST['title']);
        $content = $_POST['content'];
        $type = $_POST['type'];
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        $image = $_POST['old_image'];
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../assets/images/announcements/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $image = time() . '_' . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image);
        }

        $stmt = $pdo->prepare("UPDATE announcements SET title=?, content=?, type=?, image=?, is_public=? WHERE id=?");
        $stmt->execute([$title, $content, $type, $image, $is_public, $id]);

        flash('msg', 'Announcement updated successfully.');
        header("Location: announcements.php");
        exit();
    } elseif (isset($_POST['delete_announcement'])) {
        $id = $_POST['announcement_id'];
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        flash('msg', 'Announcement deleted successfully.');
        header("Location: announcements.php");
        exit();
    }
}

// Get all announcements
$stmt = $pdo->query("SELECT a.*, u.full_name as author FROM announcements a JOIN users u ON a.posted_by = u.id ORDER BY a.created_at DESC");
$announcements = $stmt->fetchAll();
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 shadow-sm border">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-megaphone-fill fs-4"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">School Announcements</h4>
                <p class="text-secondary small mb-0">Manage news, events and notices for the website and portal</p>
            </div>
        </div>
        <button class="btn btn-primary rounded-pill px-4 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
            <i class="bi bi-plus-lg"></i> Post Announcement
        </button>
    </div>
</div>

<?php if (isset($_SESSION['msg'])): ?>
    <?php flash('msg'); ?>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-secondary">
                        <tr>
                            <th class="px-4 py-3 border-0 text-secondary small fw-bold text-uppercase">Title</th>
                            <th class="py-3 border-0 text-secondary small fw-bold text-uppercase">Type</th>
                            <th class="py-3 border-0 text-secondary small fw-bold text-uppercase">Visibility</th>
                            <th class="py-3 border-0 text-secondary small fw-bold text-uppercase">Posted By</th>
                            <th class="py-3 border-0 text-secondary small fw-bold text-uppercase">Date</th>
                            <th class="px-4 py-3 border-0 text-secondary small fw-bold text-uppercase text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($announcements)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    No announcements found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($announcements as $ann): ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="fw-bold text-body"><?php echo htmlspecialchars($ann['title']); ?></div>
                                        <small class="text-secondary text-truncate d-block" style="max-width: 200px;"><?php echo strip_tags($ann['content']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill border bg-transparent <?php 
                                            echo $ann['type'] == 'event' ? 'border-info text-info' : 
                                                ($ann['type'] == 'notice' ? 'border-warning text-warning' : 'border-primary text-primary'); 
                                        ?> px-3">
                                            <?php echo ucfirst($ann['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ann['is_public']): ?>
                                            <span class="badge border border-success text-success bg-transparent rounded-pill px-3"><i class="bi bi-globe me-1"></i> Public</span>
                                        <?php else: ?>
                                            <span class="badge border border-secondary text-secondary bg-transparent rounded-pill px-3"><i class="bi bi-lock-fill me-1"></i> Portal Only</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-secondary fw-medium"><?php echo $ann['author']; ?></small></td>
                                    <td><small class="text-secondary"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></small></td>
                                    <td class="px-4 text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="?edit_id=<?php echo $ann['id']; ?>" class="btn btn-body-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center text-primary shadow-sm" style="width: 32px; height: 32px;" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button class="btn btn-body-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center text-danger shadow-sm" style="width: 32px; height: 32px;" onclick="confirmDelete(<?php echo $ann['id']; ?>)" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 dash-header text-white p-4">
                <h5 class="modal-title fw-bold mb-0"><i class="bi bi-megaphone me-2"></i> Post New Announcement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-4">
                <input type="hidden" name="add_announcement" value="1">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-secondary">Title</label>
                        <input type="text" name="title" class="form-control bg-body-secondary border-0 rounded-3 py-2 px-3" required placeholder="Enter announcement title">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">Type</label>
                        <select name="type" class="form-select bg-body-secondary border-0 rounded-3 py-2 px-3">
                            <option value="news">News</option>
                            <option value="event">Event</option>
                            <option value="notice">Notice</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">Content</label>
                        <textarea name="content" class="form-control bg-body-secondary border-0 rounded-3 py-2 px-3" rows="5" required placeholder="Write the announcement content here..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Image (Optional)</label>
                        <input type="file" name="image" class="form-control bg-body-secondary border-0 rounded-3" accept="image/*">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_public" id="isPublicCheck" checked>
                            <label class="form-check-label fw-bold small text-secondary" for="isPublicCheck">Display on Public Website</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-body-secondary border-0 rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_announcement" class="btn btn-primary rounded-pill px-4 shadow-sm">Post Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal Logic (using a hidden form for simplicity in this example) -->
<?php if ($edit_announcement): ?>
<div class="modal fade show" id="editModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 dash-header text-white p-4">
                <h5 class="modal-title fw-bold mb-0"><i class="bi bi-pencil-square me-2"></i> Edit Announcement</h5>
                <a href="announcements.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-4">
                <input type="hidden" name="update_announcement" value="1">
                <input type="hidden" name="announcement_id" value="<?php echo $edit_announcement['id']; ?>">
                <input type="hidden" name="old_image" value="<?php echo $edit_announcement['image']; ?>">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-secondary">Title</label>
                        <input type="text" name="title" class="form-control bg-body-secondary border-0 rounded-3 py-2 px-3" value="<?php echo htmlspecialchars($edit_announcement['title']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">Type</label>
                        <select name="type" class="form-select bg-body-secondary border-0 rounded-3 py-2 px-3">
                            <option value="news" <?php echo $edit_announcement['type'] == 'news' ? 'selected' : ''; ?>>News</option>
                            <option value="event" <?php echo $edit_announcement['type'] == 'event' ? 'selected' : ''; ?>>Event</option>
                            <option value="notice" <?php echo $edit_announcement['type'] == 'notice' ? 'selected' : ''; ?>>Notice</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">Content</label>
                        <textarea name="content" class="form-control bg-body-secondary border-0 rounded-3 py-2 px-3" rows="5" required><?php echo htmlspecialchars($edit_announcement['content']); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Image</label>
                        <input type="file" name="image" class="form-control bg-body-secondary border-0 rounded-3" accept="image/*">
                        <?php if ($edit_announcement['image']): ?>
                            <small class="text-secondary mt-1 d-block">Current: <?php echo $edit_announcement['image']; ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_public" id="editPublicCheck" <?php echo $edit_announcement['is_public'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold small text-secondary" for="editPublicCheck">Display on Public Website</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="announcements.php" class="btn btn-body-secondary border-0 rounded-pill px-4">Cancel</a>
                    <button type="submit" name="update_announcement" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="announcement_id" id="deleteId">
    <input type="hidden" name="delete_announcement" value="1">
</form>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this announcement?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>