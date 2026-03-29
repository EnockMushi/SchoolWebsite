<?php
require_once __DIR__ . '/includes/header.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results_found = false;

// Initialize result arrays
$user_results = [];
$student_results = [];

if (!empty($query)) {
    $search_term = "%$query%";

    // Search Users
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (full_name LIKE ? OR email LIKE ? OR username LIKE ? OR role LIKE ?) AND id != ? LIMIT 10");
    $stmt->execute([$search_term, $search_term, $search_term, $search_term, $_SESSION['user_id']]);
    $user_results = $stmt->fetchAll();

    // Search Students
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.full_name LIKE ? OR s.reg_number LIKE ? LIMIT 10");
    $stmt->execute([$search_term, $search_term]);
    $student_results = $stmt->fetchAll();

    $total_results = count($user_results) + count($student_results);
    
    if ($total_results > 0) {
        $results_found = true;
        // Provide feedback via flash session
        if (!isset($_SESSION['search_feedback_shown'])) {
            flash('success', "Found $total_results result" . ($total_results > 1 ? 's' : '') . " for '" . htmlspecialchars($query) . "'");
            $_SESSION['search_feedback_shown'] = true;
        }
    } else {
        if (!isset($_SESSION['search_feedback_shown'])) {
            flash('warning', "No results found for '" . htmlspecialchars($query) . "'");
            $_SESSION['search_feedback_shown'] = true;
        }
    }
} else {
    unset($_SESSION['search_feedback_shown']);
}
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-search fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Search Results</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">
                    <?php if ($results_found): ?>
                        Found <span class="badge bg-primary-subtle text-primary rounded-pill"><?php echo $total_results; ?></span> results for <span class="fw-bold text-primary">"<?php echo htmlspecialchars($query); ?>"</span>
                    <?php else: ?>
                        No results found for <span class="fw-bold text-danger">"<?php echo htmlspecialchars($query); ?>"</span>
                    <?php endif; ?>
                </p>
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

<div class="search-container reveal">
    <?php if (!$results_found): ?>
        <div class="card shadow-sm border-0 rounded-4 text-center p-5">
            <div class="bg-body-secondary rounded-circle d-inline-flex p-4 mb-3 mx-auto">
                <i class="bi bi-search text-secondary fs-1"></i>
            </div>
            <h5 class="fw-bold text-secondary">No Results Found</h5>
            <p class="text-secondary small mb-0">Try searching with different keywords or check for spelling errors.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            
            <?php if (!empty($user_results)): ?>
                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-people-fill text-primary fs-4"></i>
                        <h5 class="fw-bold mb-0">Users</h5>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($user_results as $user): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card shadow-sm border-0 rounded-4 h-100 transition-up">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="icon-box-pro" style="width: 50px; height: 50px; flex-shrink: 0;">
                                                <i class="bi bi-person fs-4"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <h6 class="fw-bold mb-1 text-truncate">
                                                    <a href="profile.php?id=<?php echo $user['id']; ?>" class="text-decoration-none text-body hover-underline">
                                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                                    </a>
                                                </h6>
                                                <div class="text-secondary small text-truncate">
                                                    <span class="badge bg-transparent text-primary border border-primary rounded-pill px-2 me-1"><?php echo ucfirst($user['role']); ?></span>
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 pt-3 border-top d-flex gap-2">
                                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                                <a href="admin/users.php?edit_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-body-secondary border-0 rounded-3 flex-grow-1">Manage</a>
                                            <?php endif; ?>
                                            <a href="<?php echo $_SESSION['role']; ?>/messages.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary rounded-3 flex-grow-1">Message</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($student_results)): ?>
                <div class="col-12 mt-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-mortarboard-fill text-success fs-4"></i>
                        <h5 class="fw-bold mb-0">Students</h5>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($student_results as $student): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card shadow-sm border-0 rounded-4 h-100 transition-up">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="icon-box-pro" style="width: 50px; height: 50px; flex-shrink: 0;">
                                                <i class="bi bi-person-badge fs-4 text-success"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <h6 class="fw-bold mb-1 text-truncate"><?php echo htmlspecialchars($student['full_name']); ?></h6>
                                                <div class="text-secondary small text-truncate">
                                                    Reg: <a href="admin/student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none text-primary hover-underline"><?php echo htmlspecialchars($student['reg_number']); ?></a><br>
                                                    Class: <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 pt-3 border-top">
                                            <?php if (in_array($_SESSION['role'], ['admin', 'headmaster', 'teacher'])): ?>
                                                <a href="admin/student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-body-secondary border-0 rounded-3 w-100">View Details</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.transition-up { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.transition-up:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
