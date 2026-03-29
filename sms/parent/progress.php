<?php
require_once '../includes/header.php';
checkRole(['parent']);

$parent_id = $_SESSION['user_id'];
$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    redirect('dashboard.php');
}

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND parent_id = ?");
$stmt->execute([$student_id, $parent_id]);
$student = $stmt->fetch();

if (!$student) {
    redirect('dashboard.php');
}

// Get Attendance summary
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status");
$stmt->execute([$student_id]);
$attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$total_days = array_sum($attendance);
$present_days = $attendance['present'] ?? 0;
$attendance_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100) : 0;

// Get Progress Remarks
$stmt = $pdo->prepare("
    SELECT sp.*, u.full_name as teacher_name, u.profile_pic as teacher_pic
    FROM student_progress sp
    JOIN users u ON sp.teacher_id = u.id
    WHERE sp.student_id = ?
    ORDER BY sp.created_at DESC
");
$stmt->execute([$student_id]);
$progress_remarks = $stmt->fetchAll();
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-graph-up-arrow fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Academic Progress</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Performance for <?php echo $student['full_name']; ?></p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <a href="javascript:history.back()" class="btn btn-body-secondary border shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="icon-box-pro mx-auto mb-3">
                <i class="bi bi-calendar-check fs-4"></i>
            </div>
            <h6 class="text-secondary small text-uppercase fw-bold">Attendance Rate</h6>
            <h4 class="fw-bold mb-0"><?php echo $attendance_percentage; ?>%</h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="icon-box-pro mx-auto mb-3" style="background: rgba(25, 135, 84, 0.1); border: 1px solid rgba(25, 135, 84, 0.2);">
                <i class="bi bi-person-check fs-4 text-success"></i>
            </div>
            <h6 class="text-secondary small text-uppercase fw-bold">Total Present</h6>
            <h4 class="fw-bold text-success mb-0"><?php echo $present_days; ?> Days</h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="icon-box-pro mx-auto mb-3" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2);">
                <i class="bi bi-person-x fs-4 text-danger"></i>
            </div>
            <h6 class="text-secondary small text-uppercase fw-bold">Total Absent</h6>
            <h4 class="fw-bold text-danger mb-0"><?php echo $attendance['absent'] ?? 0; ?> Days</h4>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Recent Attendance History</h5>
        <span class="badge bg-body-secondary border text-secondary rounded-pill px-3 py-2">Last 10 Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-secondary">
                    <tr>
                        <th class="ps-4 border-0 py-3 text-secondary small text-uppercase fw-bold">Date</th>
                        <th class="pe-4 border-0 py-3 text-secondary small text-uppercase fw-bold text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 10");
                    $stmt->execute([$student_id]);
                    $history = $stmt->fetchAll();
                    
                    if (empty($history)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-5">
                                <div class="text-secondary opacity-50 mb-3">
                                    <i class="bi bi-calendar-x display-4"></i>
                                </div>
                                <h6 class="text-secondary fw-bold">No attendance records found</h6>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $row): ?>
                            <tr class="border-transparent">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box-pro me-3" style="width: 40px; height: 40px;">
                                            <i class="bi bi-calendar3 text-primary"></i>
                                        </div>
                                        <span class="fw-bold"><?php echo date('F j, Y', strtotime($row['date'])); ?></span>
                                    </div>
                                </td>
                                <td class="pe-4 text-center">
                                    <?php 
                                        $statusClass = $row['status'] == 'present' ? 'border-success text-success' : 'border-danger text-danger';
                                    ?>
                                    <span class="badge bg-transparent border <?php echo $statusClass; ?> rounded-pill px-3 py-2" style="min-width: 100px;">
                                        <i class="bi bi-<?php echo $row['status'] == 'present' ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Teacher Remarks Section -->
<div class="card border-0 shadow-sm rounded-4 mt-4 mb-5">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box-pro">
                <i class="bi bi-journal-text"></i>
            </div>
            <h5 class="fw-bold mb-0">Teacher Progress Remarks</h5>
        </div>
    </div>
    <div class="card-body p-4">
        <?php if (empty($progress_remarks)): ?>
            <div class="text-center py-5">
                <i class="bi bi-chat-left-dots fs-1 text-secondary opacity-25 d-block mb-3"></i>
                <h6 class="fw-bold text-secondary">No Progress Remarks Yet</h6>
                <p class="text-secondary small mb-0">Teachers will post comments here about your child's progress and behavior.</p>
            </div>
        <?php else: ?>
            <div class="progress-timeline-vertical">
                <?php foreach ($progress_remarks as $remark): ?>
                    <div class="remark-item mb-4 last-child-mb-0">
                        <div class="d-flex gap-3">
                            <img src="../assets/img/profiles/<?php echo $remark['teacher_pic'] ?: 'default.png'; ?>" 
                                 class="rounded-circle shadow-sm border border-secondary border-opacity-25" style="width: 40px; height: 40px; object-fit: cover;"
                                 onerror="this.onerror=null;this.src='../assets/img/profiles/default.png'">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0 text-primary"><?php echo $remark['teacher_name']; ?></h6>
                                    <span class="text-secondary small bg-body-secondary border px-3 py-1 rounded-pill" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock me-1"></i><?php echo date('M d, Y H:i', strtotime($remark['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="bg-body-secondary p-3 rounded-4 border-start border-primary border-4 shadow-sm">
                                    <p class="mb-0 text-secondary small" style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($remark['comment'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
