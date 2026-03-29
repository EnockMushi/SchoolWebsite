<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster']);

// Handle Approval/Rejection
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
        flash('msg', 'User account approved successfully.');
    } elseif ($action === 'reject') {
        // We can either delete or mark as inactive. Let's delete pending rejected signups.
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);
        flash('msg', 'User signup request rejected and removed.', 'danger');
    }
    header("Location: approvals.php");
    exit();
}

// Fetch pending users
$stmt = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC");
$pending_users = $stmt->fetchAll();

// Mark signup notifications as read for this user
$stmt_mark = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND link = 'approvals.php'");
$stmt_mark->execute([$_SESSION['user_id']]);
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-4">
            <div class="icon-box-pro shadow-sm" style="min-width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-person-check-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-truncate fs-5 fs-md-4">Pending Approvals</h4>
                <p class="mb-0 d-none d-sm-block small text-truncate">Review new registration requests.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <?php if ($_SESSION['role'] == 'headmaster'): ?>
                <a href="../headmaster/requests.php" class="btn btn-info text-white shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate">
                    <i class="bi bi-clipboard-check fs-6"></i>
                    <span class="fw-bold small d-none d-md-inline">Teacher Requests</span>
                </a>
            <?php endif; ?>
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4">
        <?php if (empty($pending_users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-check-all fs-1 text-success opacity-25 d-block mb-3"></i>
                <h5 class="text-secondary">No pending approval requests</h5>
                <p class="text-secondary small">All signup requests have been processed.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-body-secondary">
                        <tr>
                            <th class="border-0 rounded-start-3 px-3 py-3">User Details</th>
                            <th class="border-0 py-3">Role</th>
                            <th class="border-0 py-3">Verification Info</th>
                            <th class="border-0 py-3">Requested On</th>
                            <th class="border-0 rounded-end-3 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                            <tr>
                                <td class="px-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-body-secondary rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 38px; height: 38px;">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <div>
                                            <a href="../profile.php?id=<?php echo $user['id']; ?>" class="text-decoration-none hover-underline">
                                                <div class="fw-bold text-primary"><?php echo $user['full_name']; ?></div>
                                                <div class="text-secondary small">@<?php echo $user['username']; ?></div>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?php echo $user['role'] == 'teacher' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning'; ?> px-3">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'parent'): ?>
                                        <div class="small">
                                            <span class="text-secondary">Child's Name:</span><br>
                                            <span class="fw-semibold text-primary"><?php echo $user['signup_student_name']; ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-secondary small italic">Standard Teacher Signup</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-secondary">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="?action=approve&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm hover-translate" onclick="return confirm('Approve this user?')">
                                            <i class="bi bi-check-lg me-1"></i> Approve
                                        </a>
                                        <a href="?action=reject&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-body-secondary rounded-pill px-3 shadow-sm border-0 hover-translate" onclick="return confirm('Reject and delete this request?')">
                                            <i class="bi bi-x-lg me-1 text-danger"></i> Reject
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

<?php require_once '../includes/footer.php'; ?>
