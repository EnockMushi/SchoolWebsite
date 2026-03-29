<?php
require_once '../includes/header.php';
checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Handle Request Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_request'])) {
    $message = sanitize($_POST['request_message']);
    
    // Save to teacher_requests table
    $stmt = $pdo->prepare("INSERT INTO teacher_requests (teacher_id, message) VALUES (?, ?)");
    $stmt->execute([$teacher_id, $message]);
    $request_id = $pdo->lastInsertId();

    // Get Headmaster ID for notification
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'headmaster' LIMIT 1");
    $headmaster_id = $stmt->fetchColumn();
    
    if ($headmaster_id) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $full_msg = "NEW REQUEST FROM TEACHER (" . $_SESSION['full_name'] . ")";
        $link = "headmaster/requests.php?id=" . $request_id;
        $stmt->execute([$headmaster_id, $full_msg, $link]);
        flash('msg', 'Request submitted successfully.');
    }
    header("Location: requests.php");
    exit();
}

// Fetch My Requests
$stmt = $pdo->prepare("SELECT * FROM teacher_requests WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->execute([$teacher_id]);
$my_requests = $stmt->fetchAll();

// Handle Request Deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Security check: ensure the request belongs to the logged-in teacher
    $stmt = $pdo->prepare("DELETE FROM teacher_requests WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$delete_id, $teacher_id]);
    
    if ($stmt->rowCount() > 0) {
        flash('msg', 'Request deleted successfully.');
    } else {
        flash('msg', 'Error deleting request or unauthorized.', 'danger');
    }
    header("Location: requests.php");
    exit();
}
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-send-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Administrative Requests</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Submit requests for supplies, leave, or administrative assistance.</p>
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

<div class="card shadow-sm border-0 rounded-4 mb-4 reveal">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">My Request History</h5>
        <span class="badge border text-secondary rounded-pill px-3 bg-transparent"><?php echo count($my_requests); ?> Requests</span>
    </div>
    <div class="card-body p-4">
        <?php if (empty($my_requests)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox-fill fs-1 text-secondary opacity-25 d-block mb-3"></i>
                <h6 class="text-secondary fw-bold">No requests found</h6>
                <p class="text-secondary small">You haven't submitted any administrative requests yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-body-secondary">
                        <tr>
                            <th class="border-0 rounded-start-3 small fw-bold text-uppercase py-3 ps-3">Date</th>
                            <th class="border-0 small fw-bold text-uppercase py-3">Message</th>
                            <th class="border-0 small fw-bold text-uppercase py-3">Status</th>
                            <th class="border-0 small fw-bold text-uppercase py-3">Headmaster's Feedback</th>
                            <th class="border-0 rounded-end-3 small fw-bold text-uppercase text-end py-3 pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_requests as $request): ?>
                            <tr>
                                <td class="small fw-bold ps-3" style="white-space: nowrap;">
                                    <?php echo date('M d, Y', strtotime($request['created_at'])); ?><br>
                                    <span class="text-secondary extra-small"><?php echo date('H:i', strtotime($request['created_at'])); ?></span>
                                </td>
                                <td>
                                    <p class="mb-0 small text-truncate-2" style="max-width: 300px;">
                                        <?php echo htmlspecialchars($request['message']); ?>
                                    </p>
                                </td>
                                <td>
                                    <?php 
                                    $statusClass = [
                                        'pending' => 'border-warning text-warning',
                                        'in_progress' => 'border-info text-info',
                                        'approved' => 'border-success text-success',
                                        'rejected' => 'border-danger text-danger'
                                    ][$request['status']];
                                    ?>
                                    <span class="badge rounded-pill border <?php echo $statusClass; ?> px-3 bg-transparent">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($request['headmaster_feedback']): ?>
                                        <div class="bg-body-secondary p-2 rounded-3 border-start border-primary border-3 small">
                                            <?php echo htmlspecialchars($request['headmaster_feedback']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-secondary italic small">Awaiting review...</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-2 flex-nowrap">
                                        <button class="btn btn-primary btn-sm rounded-pill px-3 d-flex align-items-center gap-1 shadow-sm hover-translate" 
                                                onclick="openViewModal(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                            <i class="bi bi-eye-fill"></i> <span class="d-none d-sm-inline">View</span>
                                        </button>
                                        <a href="requests.php?delete_id=<?php echo $request['id']; ?>" 
                                           class="btn btn-body-secondary btn-sm rounded-pill px-3 d-flex align-items-center gap-1 shadow-sm border-0 hover-translate"
                                           onclick="return confirm('Are you sure you want to delete this request? This action cannot be undone.')">
                                            <i class="bi bi-trash3-fill text-danger"></i> <span class="d-none d-sm-inline text-danger">Delete</span>
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

<div class="row g-4 reveal">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-send-fill text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">New Request</h5>
                </div>
                
                <form action="" method="POST">
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Request Details</label>
                        <textarea name="request_message" class="form-control bg-body-secondary border-0 rounded-4 p-3" rows="6" required placeholder="Describe your request in detail (e.g., stationary needs, leave application, facility repair)..."></textarea>
                    </div>
                    <button type="submit" name="send_request" class="btn btn-primary px-4 py-2 rounded-pill fw-bold w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-send-fill"></i> Submit Request to Headmaster
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-info-circle-fill text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">How it works</h5>
                </div>
                
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 bg-body-secondary rounded-4 border-start border-primary border-4">
                        <h6 class="fw-bold mb-1 small">Submission</h6>
                        <p class="text-secondary small mb-0">Your request is immediately sent to the Headmaster's notification center.</p>
                    </div>
                    <div class="p-3 bg-body-secondary rounded-4 border-start border-info border-4">
                        <h6 class="fw-bold mb-1 small">Review Process</h6>
                        <p class="text-secondary small mb-0">Administration will review the details and contact you if further information is required.</p>
                    </div>
                    <div class="p-3 bg-body-secondary rounded-4 border-start border-success border-4">
                        <h6 class="fw-bold mb-1 small">Approval</h6>
                        <p class="text-secondary small mb-0">You will receive a system notification once your request has been processed or approved.</p>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-body-tertiary rounded-4 text-primary small d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check-fill fs-5"></i>
                    <span>Your requests are logged and timestamped for transparency.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom p-3 p-md-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-box-pro">
                        <i class="bi bi-file-earmark-text fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0">Request Details</h5>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05rem;">My Request</label>
                    <div class="bg-body-secondary p-3 rounded-4 small text-body border" id="viewTeacherMessage" style="white-space: pre-wrap; line-height: 1.6;"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05rem;">Current Status</label>
                    <div class="d-flex align-items-center">
                        <span id="viewStatusBadge" class="badge rounded-pill px-3 py-2 fw-semibold border bg-transparent"></span>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05rem;">Headmaster's Feedback</label>
                    <div id="viewFeedbackContainer" class="p-3 rounded-4 small border"></div>
                </div>
            </div>
            <div class="modal-footer border-top p-3 p-md-4 flex-column flex-sm-row gap-2">
                <a href="#" id="viewDeleteBtn" class="btn btn-body-secondary border-0 rounded-pill px-4 py-2 fw-bold flex-grow-1 flex-sm-grow-0 d-flex align-items-center justify-content-center gap-2 hover-translate" 
                   onclick="return confirm('Are you sure you want to delete this request? This action cannot be undone.')">
                    <i class="bi bi-trash3-fill text-danger"></i> <span class="text-danger">Delete Request</span>
                </a>
                <button type="button" class="btn btn-primary shadow-sm rounded-pill px-4 py-2 fw-bold flex-grow-1 flex-sm-grow-0 hover-translate" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openViewModal(request) {
    document.getElementById('viewTeacherMessage').innerText = request.message;
    
    const badge = document.getElementById('viewStatusBadge');
    const status = request.status;
    badge.innerText = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
    
    const classes = {
        'pending': 'border-warning text-warning',
        'in_progress': 'border-info text-info',
        'approved': 'border-success text-success',
        'rejected': 'border-danger text-danger'
    };
    badge.className = 'badge rounded-pill px-3 border bg-transparent ' + classes[status];

    const feedbackContainer = document.getElementById('viewFeedbackContainer');
    if (request.headmaster_feedback) {
        feedbackContainer.className = 'p-3 rounded-4 small bg-body-secondary border-start border-primary border-3 text-body';
        feedbackContainer.innerText = request.headmaster_feedback;
    } else {
        feedbackContainer.className = 'p-3 rounded-4 small bg-body-secondary text-secondary italic';
        feedbackContainer.innerText = 'No feedback provided yet. Administration is still reviewing your request.';
    }
    
    document.getElementById('viewDeleteBtn').href = 'requests.php?delete_id=' + request.id;
    
    new bootstrap.Modal(document.getElementById('viewRequestModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
