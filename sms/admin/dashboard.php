<?php
require_once '../includes/header.php';
checkRole(['admin']);

// Get some stats
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
$pending_approvals = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM students");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM classes");
$total_classes = $stmt->fetchColumn();

// Get latest announcements
$stmt = $pdo->query("SELECT a.*, u.full_name as author FROM announcements a JOIN users u ON a.posted_by = u.id ORDER BY a.created_at DESC LIMIT 5");
$latest_announcements = $stmt->fetchAll();
?>

<div class="dash-header glass-container rounded-4 p-4 p-md-5 mb-4 shadow-sm border text-center animate-up">
    <div class="d-flex align-items-center justify-content-center flex-wrap gap-3">
        <div class="min-width-0">
            <h4 class="fw-bold mb-1 text-primary text-truncate fs-4 fs-md-3">Administrator Dashboard</h4>
            <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Overview of the entire school system.</p>
        </div>
    </div>
</div>

<?php if ($pending_approvals > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 animate-up stagger-1">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box-pro shadow-sm" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.2);">
                <i class="bi bi-person-fill-exclamation fs-4 text-warning"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1 text-warning">Pending Approvals</h5>
                <p class="mb-0 small text-secondary">There are <strong><?php echo $pending_approvals; ?></strong> requests waiting for review.</p>
            </div>
        </div>
        <a href="approvals.php" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">Analyze Now</a>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4 animate-up stagger-2">
        <a href="users.php" class="text-decoration-none">
            <div class="card h-100 stats-card border-0">
                <div class="card-body d-flex flex-column align-items-center text-center p-5">
                    <div>
                        <h6 class="text-secondary small text-uppercase fw-bold mb-2">Total Users</h6>
                        <h3 class="fw-bold mb-0 fs-2"><?php echo $total_users; ?></h3>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 animate-up stagger-3">
        <a href="students.php" class="text-decoration-none">
            <div class="card h-100 stats-card border-0">
                <div class="card-body d-flex flex-column align-items-center text-center p-5">
                    <div>
                        <h6 class="text-secondary small text-uppercase fw-bold mb-2">Total Students</h6>
                        <h3 class="fw-bold mb-0 fs-2"><?php echo $total_students; ?></h3>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 animate-up stagger-4">
        <a href="classes.php" class="text-decoration-none">
            <div class="card h-100 stats-card border-0">
                <div class="card-body d-flex flex-column align-items-center text-center p-5">
                    <div>
                        <h6 class="text-secondary small text-uppercase fw-bold mb-2">Total Classes</h6>
                        <h3 class="fw-bold mb-0 fs-2"><?php echo $total_classes; ?></h3>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
<div class="row g-4 mb-4 animate-up stagger-5">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Latest School Announcements</h5>
                <a href="announcements.php" class="btn btn-body-secondary btn-sm rounded-pill px-3 border-0 shadow-sm text-primary">Manage All</a>
            </div>
            <div class="card-body p-4">
                <?php if (empty($latest_announcements)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-megaphone fs-1 opacity-25 d-block mb-2"></i>
                        No announcements posted yet.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($latest_announcements as $ann): ?>
                            <div class="list-group-item px-0 py-3 border-0 border-bottom bg-transparent">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($ann['title']); ?></h6>
                                    <span class="badge rounded-pill <?php echo $ann['type'] == 'event' ? 'border-info text-info' : ($ann['type'] == 'notice' ? 'border-warning text-warning' : 'border-primary text-primary'); ?> bg-transparent border smaller px-2">
                                        <?php echo ucfirst($ann['type']); ?>
                                    </span>
                                </div>
                                <p class="text-secondary small mb-2 text-truncate"><?php echo strip_tags($ann['content']); ?></p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <small class="text-secondary opacity-75" style="font-size: 0.75rem;">
                                        <i class="bi bi-person me-1"></i> <?php echo $ann['author']; ?>
                                    </small>
                                    <small class="text-secondary opacity-75" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock me-1"></i> <?php echo date('M d, H:i', strtotime($ann['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative">
            <!-- Background Icon -->
            <i class="bi bi-shield-check position-absolute bottom-0 end-0 text-primary opacity-10" style="font-size: 150px; margin-right: -30px; margin-bottom: -30px; z-index: 0;"></i>
            
            <div class="card-body p-4 d-flex flex-column justify-content-between text-center position-relative" style="z-index: 1;">
                <div>
                    <h5 class="fw-bold mb-4">System Quick Actions</h5>
                    <div class="d-grid gap-3">
                        <a href="users.php" class="btn btn-primary btn-massive btn-glass shadow-sm border-0 w-100">
                            Add New User
                        </a>
                        <a href="settings.php" class="btn btn-body-secondary btn-massive btn-glass shadow-sm border-0 w-100">
                            System Settings
                        </a>
                        <a href="handbook_mgmt.php" class="btn btn-body-secondary btn-massive btn-glass shadow-sm border-0 w-100">
                            Student Handbook
                        </a>
                        <a href="moderation.php" class="btn btn-body-secondary btn-massive btn-glass shadow-sm border-0 w-100">
                            Security Logs
                        </a>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="small text-secondary opacity-75 mb-0">System Version 1.0.0</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
