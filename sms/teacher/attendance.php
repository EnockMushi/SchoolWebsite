<?php
require_once '../includes/header.php';
checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get assigned class
$stmt = $pdo->prepare("SELECT c.* FROM classes c JOIN teacher_assignments ta ON c.id = ta.class_id WHERE ta.teacher_id = ?");
$stmt->execute([$teacher_id]);
$assigned_class = $stmt->fetch();

if (!$assigned_class) {
    echo "<div class='alert alert-danger'>You are not assigned to any class. Please contact the Headmaster.</div>";
    require_once '../includes/footer.php';
    exit();
}

$class_id = $assigned_class['id'];

// Process attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    foreach ($_POST['attendance'] as $student_id => $status) {
        // Check if record exists for today
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
        $stmt->execute([$student_id, $date]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, marked_by = ? WHERE id = ?");
            $stmt->execute([$status, $teacher_id, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status, marked_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $date, $status, $teacher_id]);
        }
    }
    flash('msg', 'Attendance saved successfully for ' . $date);
}

// Get students and their attendance for today
$fetchQuery = "
    SELECT s.*, a.status as att_status 
    FROM students s 
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ? 
    WHERE s.class_id = ?
";
$stmt = $pdo->prepare($fetchQuery);
$stmt->execute([$date, $class_id]);
$students = $stmt->fetchAll();
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-calendar-check fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Class Attendance</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Daily roll call for <?php echo $assigned_class['class_name']; ?></p>
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
    <div class="d-flex align-items-center justify-content-start gap-3">
        <form action="" method="GET" class="d-flex align-items-center gap-2">
            <label class="small fw-bold text-secondary text-uppercase mb-0 d-none d-sm-block">Date:</label>
            <div class="input-group shadow-sm rounded-4 overflow-hidden border">
                <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;">
                    <i class="bi bi-calendar-event text-primary"></i>
                </span>
                <input type="date" name="date" class="form-control bg-body-secondary border-0 py-2" value="<?php echo $date; ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check text-primary fs-4"></i>
                <h5 class="card-title fw-bold mb-0">Roll Call Register</h5>
            </div>
            <div class="d-flex gap-2">
                <span class="badge border border-primary text-primary rounded-pill px-3 py-2 bg-transparent">
                    <?php echo count($students); ?> Students Total
                </span>
                <?php 
                $present_count = 0;
                foreach($students as $s) if($s['att_status'] == 'present') $present_count++;
                ?>
                <span class="badge border border-success text-success rounded-pill px-3 py-2 bg-transparent">
                    <?php echo $present_count; ?> Present
                </span>
            </div>
        </div>

        <form action="" method="POST">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-0">
                    <thead class="bg-body-secondary">
                        <tr>
                            <th class="border-0 rounded-start-3 ps-3 py-3 text-secondary small fw-bold text-uppercase">Reg No</th>
                            <th class="border-0 py-3 text-secondary small fw-bold text-uppercase">Full Name</th>
                            <th class="border-0 rounded-end-3 py-3 text-secondary small fw-bold text-uppercase text-end pe-3">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary small">
                                    <a href="../admin/student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none text-primary hover-underline">
                                        <?php echo $student['reg_number']; ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="../admin/student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none text-body hover-underline">
                                        <div class="fw-bold"><?php echo $student['full_name']; ?></div>
                                    </a>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex gap-3 justify-content-end">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" id="pres_<?php echo $student['id']; ?>" value="present" <?php echo $student['att_status'] == 'present' ? 'checked' : ''; ?>>
                                            <label class="form-check-label small fw-semibold" for="pres_<?php echo $student['id']; ?>">Present</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" id="abs_<?php echo $student['id']; ?>" value="absent" <?php echo $student['att_status'] == 'absent' ? 'checked' : ''; ?>>
                                            <label class="form-check-label small fw-semibold" for="abs_<?php echo $student['id']; ?>">Absent</label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="bg-body-secondary rounded-circle d-inline-flex p-4 mb-3">
                                        <i class="bi bi-people text-secondary fs-1"></i>
                                    </div>
                                    <h6 class="fw-bold text-secondary">No Students Found</h6>
                                    <p class="text-secondary small mb-0">There are no students enrolled in this class yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($students)): ?>
                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <p class="text-secondary small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Select 'Present' or 'Absent' for each student and click save.
                    </p>
                    <button type="submit" name="save_attendance" class="btn btn-primary px-4 py-2 rounded-3 fw-bold hover-translate">
                        <i class="bi bi-check2-circle me-2"></i> Save Daily Attendance
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
