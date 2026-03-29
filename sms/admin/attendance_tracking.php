<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster']);

// Filters
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$type = isset($_GET['type']) ? $_GET['type'] : 'monthly'; // monthly, daily, yearly

// Fetch all classes for filter
$stmt = $pdo->query("SELECT id, class_name, section FROM classes ORDER BY class_name ASC");
$classes_list = $stmt->fetchAll();

// Build query based on filters
$where_clauses = [];
$params = [];

if ($class_id > 0) {
    $where_clauses[] = "s.class_id = ?";
    $params[] = $class_id;
}

if ($type == 'daily') {
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $where_clauses[] = "a.date = ?";
    $params[] = $date;
} elseif ($type == 'monthly') {
    $where_clauses[] = "DATE_FORMAT(a.date, '%Y-%m') = ?";
    $params[] = $month;
} elseif ($type == 'yearly') {
    $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
    $where_clauses[] = "DATE_FORMAT(a.date, '%Y') = ?";
    $params[] = $year;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch attendance data
$query = "
    SELECT 
        s.id as student_id,
        s.full_name, 
        s.reg_number, 
        c.class_name, 
        c.section,
        a.date, 
        a.status,
        u.id as marked_by_id,
        u.full_name as marked_by_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON a.marked_by = u.id
    $where_sql
    ORDER BY a.date DESC, s.full_name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Calculate summary stats
$total_present = 0;
$total_absent = 0;
foreach ($attendance_records as $record) {
    if ($record['status'] == 'present') $total_present++;
    else $total_absent++;
}
$total_count = count($attendance_records);
$attendance_percentage = $total_count > 0 ? round(($total_present / $total_count) * 100, 1) : 0;
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 no-print">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro shadow-sm">
                <i class="bi bi-calendar-check-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-truncate fs-5 fs-md-4">Attendance Tracking</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Monitor student presence.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <button onclick="window.print()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-printer-fill text-primary"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Print Report</span>
                <span class="fw-bold small text-secondary d-md-none">Print</span>
            </button>
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>

    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary">Filter Type</label>
            <select name="type" class="form-select bg-body-secondary border-0 shadow-sm rounded-3" onchange="this.form.submit()">
                <option value="daily" <?php echo $type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                <option value="monthly" <?php echo $type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                <option value="yearly" <?php echo $type == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary">Class</label>
            <select name="class_id" class="form-select bg-body-secondary border-0 shadow-sm rounded-3" onchange="this.form.submit()">
                <option value="0">All Classes</option>
                <?php foreach ($classes_list as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $class_id == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo $c['class_name']; ?> (<?php echo $c['section']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary">Period</label>
            <?php if ($type == 'daily'): ?>
                <input type="date" name="date" class="form-control bg-body-secondary border-0 shadow-sm rounded-3" value="<?php echo $_GET['date'] ?? date('Y-m-d'); ?>" onchange="this.form.submit()">
            <?php elseif ($type == 'monthly'): ?>
                <input type="month" name="month" class="form-control bg-body-secondary border-0 shadow-sm rounded-3" value="<?php echo $month; ?>" onchange="this.form.submit()">
            <?php else: ?>
                <select name="year" class="form-select bg-body-secondary border-0 shadow-sm rounded-3" onchange="this.form.submit()">
                    <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($_GET['year'] ?? date('Y')) == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <a href="attendance_tracking.php" class="btn btn-body-secondary w-100 rounded-3 fw-bold border-0">Reset Filters</a>
        </div>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small text-uppercase fw-bold mb-1">Total Records</p>
                        <h3 class="fw-bold mb-0"><?php echo $total_count; ?></h3>
                    </div>
                    <div class="bg-body-secondary text-primary rounded-3 p-3">
                        <i class="bi bi-list-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small text-uppercase fw-bold mb-1">Present Count</p>
                        <h3 class="fw-bold text-success mb-0"><?php echo $total_present; ?></h3>
                    </div>
                    <div class="bg-body-secondary text-success rounded-3 p-3">
                        <i class="bi bi-person-check-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small text-uppercase fw-bold mb-1">Attendance Rate</p>
                        <h3 class="fw-bold text-primary mb-0"><?php echo $attendance_percentage; ?>%</h3>
                    </div>
                    <div class="bg-body-secondary text-info rounded-3 p-3">
                        <i class="bi bi-percent fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-body-secondary">
                    <tr>
                        <th class="border-0 px-3 py-3 text-secondary small fw-bold text-uppercase rounded-start-3">Date</th>
                        <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Student</th>
                        <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Class</th>
                        <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Status</th>
                        <th class="border-0 py-3 text-secondary small fw-bold text-uppercase rounded-end-3">Marked By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td class="px-3 small fw-bold"><?php echo $record['date']; ?></td>
                            <td>
                                <a href="student_details.php?id=<?php echo $record['student_id']; ?>" class="text-decoration-none hover-underline">
                                    <div class="fw-bold mb-0 small text-primary"><?php echo $record['full_name']; ?></div>
                                </a>
                                <div class="text-secondary" style="font-size: 0.7rem;">
                                    <a href="student_details.php?id=<?php echo $record['student_id']; ?>" class="text-decoration-none text-primary hover-underline">
                                        <?php echo $record['reg_number']; ?>
                                    </a>
                                </div>
                            </td>
                            <td><span class="badge bg-body-secondary text-secondary border-0 rounded-pill px-3"><?php echo $record['class_name']; ?> (<?php echo $record['section']; ?>)</span></td>
                            <td>
                                <span class="badge rounded-pill px-3 py-1 <?php echo $record['status'] == 'present' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                            <td class="small text-secondary">
                                <?php if ($record['marked_by_id']): ?>
                                    <a href="../profile.php?id=<?php echo $record['marked_by_id']; ?>" class="text-decoration-none text-secondary hover-underline">
                                        <?php echo $record['marked_by_name']; ?>
                                    </a>
                                <?php else: ?>
                                    System
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($attendance_records)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-calendar-x fs-1 text-secondary d-block mb-3 opacity-25"></i>
                                <p class="text-secondary fw-bold">No attendance records found for this selection.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
    .badge { border: 1px solid #ccc !important; color: black !important; background: transparent !important; }
}
</style>

<?php require_once '../includes/footer.php'; ?>