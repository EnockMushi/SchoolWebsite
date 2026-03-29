<?php
require_once '../includes/header.php';
checkRole(['headmaster', 'admin']);

// Fetch stats
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$total_classes = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$pending_approvals = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$pending_requests = $pdo->query("SELECT COUNT(*) FROM teacher_requests WHERE status = 'pending'")->fetchColumn();
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-person-workspace fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Headmaster Dashboard</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Overview of school administration.</p>
            </div>
        </div>
    </div>
</div>

<?php if ($pending_approvals > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box-pro shadow-sm" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.2);">
                <i class="bi bi-person-fill-exclamation fs-4 text-warning"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1 text-warning">Signup Requests</h5>
                <p class="mb-0 text-secondary small">There are <strong><?php echo $pending_approvals; ?></strong> new registrations waiting for your analysis.</p>
            </div>
        </div>
        <a href="approvals.php" class="btn btn-warning fw-bold px-4 rounded-pill w-100 w-sm-auto shadow-sm">View Requests</a>
    </div>
<?php endif; ?>

<?php if ($pending_requests > 0): ?>
    <div class="alert alert-info border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box-pro shadow-sm" style="background: rgba(13, 202, 240, 0.1); border: 1px solid rgba(13, 202, 240, 0.2);">
                <i class="bi bi-send-fill fs-4 text-info"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1 text-info">Teacher Requests</h5>
                <p class="mb-0 text-secondary small">There are <strong><?php echo $pending_requests; ?></strong> new administrative requests waiting for review.</p>
            </div>
        </div>
        <a href="requests.php" class="btn btn-info text-white fw-bold px-4 rounded-pill w-100 w-sm-auto shadow-sm">Manage Requests</a>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <a href="classes.php" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 rounded-4 transition-up">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="icon-box-pro flex-shrink-0">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1">Total Classes</h6>
                        <h3 class="fw-bold mb-0 text-body"><?php echo $total_classes; ?></h3>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="teachers.php" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 rounded-4 transition-up">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="icon-box-pro flex-shrink-0">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1">Total Teachers</h6>
                        <h3 class="fw-bold mb-0 text-body"><?php echo $total_teachers; ?></h3>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-house-door-fill text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">School Management</h5>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="classes.php" class="btn btn-primary rounded-3 px-4 py-2 shadow-sm hover-translate">
                        <i class="bi bi-layers-fill me-2"></i> Manage Classes
                    </a>
                    <a href="handbook_mgmt.php" class="btn btn-body-secondary rounded-3 px-4 py-2 shadow-sm hover-translate border-0">
                        <i class="bi bi-book-fill me-2"></i> Student Handbook
                    </a>
                    <a href="teachers.php" class="btn btn-body-secondary border rounded-3 px-4 py-2 shadow-sm hover-translate">
                        <i class="bi bi-person-badge me-2 text-primary"></i> Teacher Assignments
                    </a>
                    <a href="requests.php" class="btn btn-info text-white rounded-3 px-4 py-2 shadow-sm hover-translate">
                        <i class="bi bi-clipboard-check me-2"></i> Teacher Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-clock-history text-warning fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">Approval Summary</h5>
                </div>
                <?php if ($pending_approvals > 0): ?>
                    <div class="p-3 bg-body-secondary rounded-4 border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary fw-semibold">Pending Signups</span>
                            <span class="badge border border-warning text-warning rounded-pill px-3 bg-transparent"><?php echo $pending_approvals; ?></span>
                        </div>
                    </div>
                    <p class="text-secondary small">Headmaster approval is required for all new teacher and parent registrations.</p>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-all text-success display-4 mb-3 opacity-50"></i>
                        <p class="text-secondary mb-0">All registration requests have been processed.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
