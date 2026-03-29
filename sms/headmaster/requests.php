<?php
require_once '../includes/header.php';
checkRole(['headmaster', 'admin']);

// Handle Request Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_request'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $feedback = sanitize($_POST['feedback']);
    
    $stmt = $pdo->prepare("UPDATE teacher_requests SET status = ?, headmaster_feedback = ? WHERE id = ?");
    $stmt->execute([$status, $feedback, $request_id]);
    
    // Notify Teacher
    $stmt = $pdo->prepare("SELECT teacher_id FROM teacher_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $teacher_id = $stmt->fetchColumn();
    
    if ($teacher_id) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $status_text = ucfirst(str_replace('_', ' ', $status));
        $msg = "Your request has been updated to: " . $status_text;
        $link = "teacher/requests.php";
        $stmt->execute([$teacher_id, $msg, $link]);
    }
    
    flash('msg', 'Request updated successfully.');
    header("Location: requests.php");
    exit();
}

// Fetch All Requests
$stmt = $pdo->query("
    SELECT tr.*, u.full_name as teacher_name, u.profile_pic as teacher_pic
    FROM teacher_requests tr
    JOIN users u ON tr.teacher_id = u.id
    ORDER BY tr.created_at DESC
");
$all_requests = $stmt->fetchAll();

// Highlight specific request if ID is in URL
$highlight_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-clipboard-check fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Teacher Requests</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Manage administrative and supply requests from teaching staff.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h5 class="fw-bold mb-0">All Administrative Requests</h5>
        <div class="d-flex gap-2">
            <span class="badge bg-transparent text-warning rounded-pill px-3 py-2 border border-warning">
                <i class="bi bi-clock-history me-1"></i> Pending: <?php echo count(array_filter($all_requests, fn($r) => $r['status'] == 'pending')); ?>
            </span>
            <span class="badge bg-transparent text-info rounded-pill px-3 py-2 border border-info">
                <i class="bi bi-gear-wide-connected me-1"></i> In Progress: <?php echo count(array_filter($all_requests, fn($r) => $r['status'] == 'in_progress')); ?>
            </span>
        </div>
    </div>
    <div class="card-body p-0 p-md-4">
        <?php if (empty($all_requests)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-secondary opacity-25 d-block mb-3"></i>
                <h6 class="text-secondary">No requests found</h6>
                <p class="text-secondary small">There are no teacher requests in the system.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-secondary">
                        <tr>
                            <th class="border-0 px-4 py-3 small fw-bold text-uppercase text-secondary">Teacher</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase text-secondary">Request Details</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase text-secondary">Status</th>
                            <th class="border-0 px-4 py-3 small fw-bold text-uppercase text-secondary text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_requests as $request): ?>
                            <tr class="<?php echo $highlight_id == $request['id'] ? 'bg-body-tertiary' : ''; ?> border-bottom">
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="position-relative">
                                            <img src="../assets/img/profiles/<?php echo $request['teacher_pic'] ?: 'default.png'; ?>" 
                                                 class="rounded-circle shadow-sm border border-2 border-body" style="width: 42px; height: 42px; object-fit: cover;"
                                                 onerror="this.onerror=null;this.src='../assets/img/profiles/default.png'">
                                            <?php if($request['status'] == 'pending'): ?>
                                                <span class="position-absolute bottom-0 end-0 p-1 bg-warning border border-body rounded-circle"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold small mb-0"><?php echo htmlspecialchars($request['teacher_name']); ?></div>
                                            <div class="text-secondary extra-small d-flex align-items-center gap-1">
                                                <i class="bi bi-calendar3"></i>
                                                <?php echo date('M d, Y', strtotime($request['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="small mb-1 fw-medium">Administrative Request</div>
                                    <p class="mb-0 text-secondary small text-truncate-2" style="max-width: 350px;" title="<?php echo htmlspecialchars($request['message']); ?>">
                                        <?php echo htmlspecialchars($request['message']); ?>
                                    </p>
                                </td>
                                <td class="py-3">
                                    <?php 
                                    $statusConfig = [
                                        'pending' => ['class' => 'border-warning text-warning', 'icon' => 'bi-hourglass-split'],
                                        'in_progress' => ['class' => 'border-info text-info', 'icon' => 'bi-gear-fill'],
                                        'approved' => ['class' => 'border-success text-success', 'icon' => 'bi-check-circle-fill'],
                                        'rejected' => ['class' => 'border-danger text-danger', 'icon' => 'bi-x-circle-fill']
                                    ][$request['status']];
                                    ?>
                                    <span class="badge rounded-pill bg-transparent <?php echo $statusConfig['class']; ?> px-3 py-2 border d-inline-flex align-items-center gap-1">
                                        <i class="bi <?php echo $statusConfig['icon']; ?> small"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <button class="btn btn-body-secondary btn-sm rounded-pill px-3 shadow-sm border hover-translate d-inline-flex align-items-center gap-2" 
                                            onclick="openReviewModal(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                        <i class="bi bi-eye-fill text-primary"></i>
                                        <span class="fw-bold small text-secondary">Review</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold">Review Teacher Request</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="request_id" id="modalRequestId">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Teacher's Message</label>
                        <div class="bg-body-secondary p-3 rounded-4 small text-secondary" id="modalTeacherMessage"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Update Status</label>
                        <select name="status" id="modalStatus" class="form-select bg-body-secondary border-0 rounded-3">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Feedback / Comments</label>
                        <textarea name="feedback" id="modalFeedback" class="form-control bg-body-secondary border-0 rounded-4" rows="4" placeholder="Enter feedback for the teacher..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4">
                    <button type="button" class="btn btn-body-secondary rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_request" class="btn btn-primary rounded-3 px-4 py-2 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openReviewModal(request) {
    document.getElementById('modalRequestId').value = request.id;
    document.getElementById('modalTeacherMessage').innerText = request.message;
    document.getElementById('modalStatus').value = request.status;
    document.getElementById('modalFeedback').value = request.headmaster_feedback || '';
    
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

// Scroll to highlighted request if it exists
document.addEventListener('DOMContentLoaded', function() {
    const highlightedRow = document.querySelector('tr.bg-body-tertiary');
    if (highlightedRow) {
        setTimeout(() => {
            highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            highlightedRow.style.transition = 'background-color 2s ease';
            // Briefly flash the highlight
            setTimeout(() => {
                highlightedRow.classList.add('shadow-sm');
            }, 500);
        }, 800);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
