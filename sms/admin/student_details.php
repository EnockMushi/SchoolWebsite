<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster', 'teacher']);

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    header("Location: students.php");
    exit();
}

// Fetch student details with class and parent info
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section, u.full_name as parent_name, u.email as parent_email, u.phone as parent_phone
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON s.parent_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    flash('msg', 'Student not found.', 'danger');
    header("Location: students.php");
    exit();
}

// Fetch attendance stats (last 30 days)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute([$student_id]);
$attendance_stats = $stmt->fetch();
$attendance_rate = $attendance_stats['total'] > 0 ? round(($attendance_stats['present'] / $attendance_stats['total']) * 100, 1) : 0;

// Fetch recent attendance
$stmt = $pdo->prepare("
    SELECT a.*, u.id as marked_by_id, u.full_name as marked_by_name
    FROM attendance a
    LEFT JOIN users u ON a.marked_by = u.id
    WHERE a.student_id = ?
    ORDER BY a.date DESC
    LIMIT 10
");
$stmt->execute([$student_id]);
$recent_attendance = $stmt->fetchAll();

// Fetch student progress remarks
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

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 no-print shadow-sm border">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-person-badge-fill fs-4 text-primary"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-truncate fs-5 fs-md-4">Student Profile</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Details for <?php echo $student['full_name']; ?>.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <button onclick="window.print()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-printer-fill text-primary"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Print Profile</span>
                <span class="fw-bold small text-secondary d-md-none">Print</span>
            </button>
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Student Quick Info -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 text-center">
                <?php if (!empty($student['profile_pic'])): ?>
                    <img src="../assets/img/profiles/<?php echo $student['profile_pic']; ?>" 
                         class="rounded-circle shadow-sm mb-3" 
                         style="width: 80px; height: 80px; object-fit: cover;"
                         onerror="this.onerror=null;this.src='../assets/img/profiles/default.png'">
                <?php else: ?>
                    <div class="avatar rounded-circle bg-body-secondary d-inline-flex align-items-center justify-content-center fw-bold text-primary shadow-sm mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h5 class="fw-bold mb-1"><?php echo $student['full_name']; ?></h5>
                <p class="text-secondary small mb-3"><?php echo $student['reg_number']; ?></p>
                
                <div class="badge bg-body-secondary text-primary rounded-pill px-3 py-2 mb-4">
                    <?php echo $student['class_name'] . ' (' . $student['section'] . ')'; ?>
                </div>

                <hr class="my-4 opacity-25">
                
                <div class="text-start">
                    <p class="small fw-bold text-uppercase text-secondary mb-3">Parent Information</p>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-body-secondary rounded-circle p-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-person text-primary"></i></div>
                        <div>
                            <div class="small fw-bold">
                                <?php if ($student['parent_id']): ?>
                                    <a href="../profile.php?id=<?php echo $student['parent_id']; ?>" class="text-decoration-none text-primary hover-underline">
                                        <?php echo $student['parent_name']; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary">N/A</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-secondary extra-small">Parent/Guardian</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-body-secondary rounded-circle p-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-telephone text-primary"></i></div>
                        <div>
                            <div class="small fw-bold"><?php echo $student['parent_phone'] ?: 'N/A'; ?></div>
                            <div class="text-secondary extra-small">Phone Number</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-body-secondary rounded-circle p-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-envelope text-primary"></i></div>
                        <div>
                            <div class="small fw-bold"><?php echo $student['parent_email'] ?: 'N/A'; ?></div>
                            <div class="text-secondary extra-small">Email Address</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats and Progress -->
    <div class="col-md-8">
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <p class="text-secondary small text-uppercase fw-bold mb-2">Attendance Rate (30 Days)</p>
                        <div class="d-flex align-items-end gap-3">
                            <h2 class="fw-bold mb-0 text-primary"><?php echo $attendance_rate; ?>%</h2>
                            <div class="progress flex-grow-1 mb-2 bg-body-secondary" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $attendance_rate; ?>%" aria-valuenow="<?php echo $attendance_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for Attendance and Academic History -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <ul class="nav nav-tabs border-0 gap-4" id="studentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active border-0 px-0 py-2 fw-bold text-secondary" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">Recent Attendance</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 px-0 py-2 fw-bold text-secondary" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic" type="button" role="tab">Progress Remarks</button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="studentTabsContent">
                    <!-- Attendance Tab -->
                    <div class="tab-pane fade show active" id="attendance" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-body-secondary">
                                    <tr>
                                        <th class="border-0 small fw-bold text-uppercase text-secondary">Date</th>
                                        <th class="border-0 small fw-bold text-uppercase text-secondary">Status</th>
                                        <th class="border-0 small fw-bold text-uppercase text-secondary">Marked By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_attendance as $record): ?>
                                        <tr>
                                            <td class="small fw-bold"><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                            <td>
                                                <span class="badge rounded-pill px-3 py-1 border <?php echo $record['status'] == 'present' ? 'border-success text-success' : 'border-danger text-danger'; ?> bg-transparent">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                            </td>
                                            <td class="small text-secondary">
                                                <?php if ($record['marked_by_id']): ?>
                                                    <a href="../profile.php?id=<?php echo $record['marked_by_id']; ?>" class="text-decoration-none text-primary hover-underline">
                                                        <?php echo $record['marked_by_name']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-secondary">System</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recent_attendance)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <p class="text-secondary mb-0">No recent attendance records.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Progress Remarks Tab -->
                    <div class="tab-pane fade" id="academic" role="tabpanel">
                        <?php if (empty($progress_remarks)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-left-dots fs-1 text-secondary opacity-25 d-block mb-3"></i>
                                <h6 class="fw-bold text-secondary">No Progress Remarks</h6>
                                <p class="text-secondary small">Teachers haven't left any progress comments for this student yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="progress-timeline-vertical">
                                <?php foreach ($progress_remarks as $remark): ?>
                                    <div class="remark-item mb-4">
                                        <div class="d-flex gap-3">
                                            <img src="../assets/img/profiles/<?php echo $remark['teacher_pic'] ?: 'default.png'; ?>" 
                                                 class="rounded-circle shadow-sm" style="width: 32px; height: 32px; object-fit: cover;"
                                                 onerror="this.onerror=null;this.src='../assets/img/profiles/default.png'">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="fw-bold mb-0"><?php echo $remark['teacher_name']; ?></h6>
                                                    <span class="text-secondary small"><?php echo date('M d, Y H:i', strtotime($remark['created_at'])); ?></span>
                                                </div>
                                                <div class="bg-body-secondary p-3 rounded-4 border-start border-primary border-3">
                                                    <p class="mb-0 text-secondary small"><?php echo nl2br(htmlspecialchars($remark['comment'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-tabs .nav-link.active {
    color: var(--primary-color) !important;
    border-bottom: 3px solid var(--primary-color) !important;
}
.nav-tabs .nav-link:hover {
    color: var(--primary-color) !important;
}
.extra-small { font-size: 0.7rem; }

@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}
</style>

<?php require_once '../includes/footer.php'; ?>