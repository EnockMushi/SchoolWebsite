<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster']);

$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch class details with teacher info
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as teacher_name, u.email as teacher_email, u.phone as teacher_phone
    FROM classes c 
    LEFT JOIN users u ON c.teacher_id = u.id 
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class) {
    flash('msg', 'Class not found.', 'alert alert-danger');
    redirect('classes.php');
}

// Fetch students in this class
$stmt = $pdo->prepare("
    SELECT s.*
    FROM students s 
    WHERE s.class_id = ? 
    ORDER BY s.full_name ASC
");
$stmt->execute([$class_id]);
$students = $stmt->fetchAll();

// Fetch attendance stats for this class (last 30 days)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
        COUNT(*) as total_records
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE s.class_id = ? AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute([$class_id]);
$stats = $stmt->fetch();

$attendance_rate = $stats['total_records'] > 0 
    ? round(($stats['present_count'] / $stats['total_records']) * 100, 1) 
    : 0;
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-3 p-md-4 mb-4 shadow-sm border">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-info-circle-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-truncate fs-5 fs-md-4"><?php echo $class['class_name']; ?> - <?php echo $class['section']; ?></h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Detailed overview of class enrollment.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <button onclick="window.print()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate no-print border-0">
                <i class="bi bi-printer-fill text-primary"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Print Report</span>
                <span class="fw-bold small text-secondary d-md-none">Print</span>
            </button>
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate no-print border-0">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Quick Stats -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center">
                <div class="icon-box-pro mx-auto mb-3">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo count($students); ?></h3>
                <p class="text-secondary small text-uppercase fw-bold mb-0">Total Students</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center">
                <div class="icon-box-pro mx-auto mb-3">
                    <i class="bi bi-calendar-check-fill fs-3 text-success"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo $attendance_rate; ?>%</h3>
                <p class="text-secondary small text-uppercase fw-bold mb-0">Attendance Rate (30d)</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-box-pro">
                        <i class="bi bi-person-badge-fill fs-4 text-warning"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Main Teacher</h6>
                        <p class="text-secondary small mb-0">
                            <?php if ($class['teacher_id']): ?>
                                <a href="../profile.php?id=<?php echo $class['teacher_id']; ?>" class="text-decoration-none text-primary hover-underline">
                                    <?php echo $class['teacher_name']; ?>
                                </a>
                            <?php else: ?>
                                Not Assigned
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php if ($class['teacher_name']): ?>
                    <div class="small text-secondary">
                        <div><i class="bi bi-envelope me-2"></i><?php echo $class['teacher_email'] ?: 'N/A'; ?></div>
                        <div><i class="bi bi-telephone me-2"></i><?php echo $class['teacher_phone'] ?: 'N/A'; ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Student List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="fw-bold mb-0">Class Registry</h5>
                    <a href="attendance_tracking.php?class_id=<?php echo $class_id; ?>" class="btn btn-body-secondary text-primary btn-sm rounded-pill px-3 no-print border-0 shadow-sm">
                        View Detailed Attendance <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-body-secondary">
                            <tr>
                                <th class="border-0 px-3 py-3 text-secondary small fw-bold text-uppercase">Reg No</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Full Name</th>
                                <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Last Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="px-3 fw-bold text-primary small">
                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none text-primary hover-underline">
                                            <?php echo $student['reg_number']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none hover-underline">
                                            <div class="fw-bold mb-0 small text-primary"><?php echo $student['full_name']; ?></div>
                                        </a>
                                    </td>
                                    <td class="small text-secondary">
                                        <?php 
                                            $stmt = $pdo->prepare("SELECT date FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 1");
                                            $stmt->execute([$student['id']]);
                                            $last_att = $stmt->fetchColumn();
                                            echo $last_att ?: 'Never';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    .bg-primary-subtle { background-color: #f8f9fa !important; color: black !important; border: 1px solid #dee2e6; }
    .text-primary { color: black !important; }
}
</style>

<?php require_once '../includes/footer.php'; ?>