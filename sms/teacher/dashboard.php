<?php
require_once '../includes/header.php';
checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Get assigned class
$stmt = $pdo->prepare("SELECT c.* FROM classes c JOIN teacher_assignments ta ON c.id = ta.class_id WHERE ta.teacher_id = ?");
$stmt->execute([$teacher_id]);
$assigned_class = $stmt->fetch();

$total_students = 0;
if ($assigned_class) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
    $stmt->execute([$assigned_class['id']]);
    $total_students = $stmt->fetchColumn();
}
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-person-badge-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Teacher Dashboard</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Manage class, attendance, and progress.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <a href="students.php" class="text-decoration-none">
            <div class="card h-100 stats-card border-0 transition-up">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="icon-box-pro flex-shrink-0">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary small text-uppercase fw-bold mb-1">Assigned Class</h6>
                        <h3 class="fw-bold mb-0 text-body"><?php echo $assigned_class ? $assigned_class['class_name'] : 'None'; ?></h3>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="students.php" class="text-decoration-none">
            <div class="card h-100 stats-card border-0 transition-up">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="icon-box-pro flex-shrink-0">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary small text-uppercase fw-bold mb-1">My Students</h6>
                        <h3 class="fw-bold mb-0 text-body"><?php echo $total_students; ?></h3>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="attendance.php" class="text-decoration-none">
            <div class="card h-100 stats-card border-0 transition-up">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="icon-box-pro flex-shrink-0">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary small text-uppercase fw-bold mb-1">Attendance</h6>
                        <h3 class="fw-bold mb-0 text-body"><?php 
                            if ($assigned_class) {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND student_id IN (SELECT id FROM students WHERE class_id = ?)");
                                $stmt->execute([$assigned_class['id']]);
                                echo $stmt->fetchColumn() . '/' . $total_students;
                            } else {
                                echo 'N/A';
                            }
                        ?></h3>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
.transition-up { transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
.transition-up:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
</style>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone-fill text-primary fs-4"></i>
                        <h5 class="card-title fw-bold mb-0">Latest Announcements</h5>
                    </div>
                </div>
                
                <?php
                $stmt = $pdo->query("SELECT a.*, u.full_name as author FROM announcements a JOIN users u ON a.posted_by = u.id ORDER BY a.created_at DESC LIMIT 3");
                $announcements = $stmt->fetchAll();
                
                if ($announcements): ?>
                    <div class="row g-3">
                        <?php foreach ($announcements as $ann): ?>
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 bg-body-secondary h-100 border">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge border border-primary text-primary small rounded-pill bg-transparent"><?php echo ucfirst($ann['type']); ?></span>
                                        <small class="text-secondary opacity-75"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></small>
                                    </div>
                                    <h6 class="fw-bold text-body mb-2"><?php echo htmlspecialchars($ann['title']); ?></h6>
                                    <p class="text-secondary small mb-0 text-truncate-2"><?php echo strip_tags($ann['content']); ?></p>
                                    <button class="btn btn-link text-primary p-0 mt-2 small fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#annModal<?php echo $ann['id']; ?>">Read More</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                        No announcements yet
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($announcements): ?>
    <!-- Announcement Modals -->
    <?php foreach ($announcements as $ann): ?>
        <div class="modal fade" id="annModal<?php echo $ann['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 p-md-5 pt-0">
                        <div class="text-center mb-4">
                            <span class="badge border border-primary text-primary rounded-pill px-4 py-2 mb-3 bg-transparent"><?php echo ucfirst($ann['type']); ?></span>
                            <h2 class="fw-bold text-body"><?php echo htmlspecialchars($ann['title']); ?></h2>
                            <div class="d-flex align-items-center justify-content-center gap-3 text-secondary small">
                                <span><i class="bi bi-calendar3 me-1"></i> <?php echo date('F d, Y', strtotime($ann['created_at'])); ?></span>
                                <span><i class="bi bi-person me-1"></i> <?php echo $ann['author']; ?></span>
                            </div>
                        </div>

                        <?php if ($ann['image']): ?>
                            <img src="../assets/images/announcements/<?php echo $ann['image']; ?>" class="img-fluid rounded-4 mb-4 w-100 shadow-sm" alt="Announcement">
                        <?php endif; ?>

                        <div class="news-content text-secondary" style="font-size: 1.1rem; line-height: 1.8; white-space: pre-wrap;"><?php echo htmlspecialchars($ann['content']); ?></div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0 justify-content-center">
                        <button type="button" class="btn btn-body-secondary rounded-pill px-5 fw-bold" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-list-task text-primary fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">Daily Tasks</h5>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="attendance.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm hover-translate">
                        <i class="bi bi-clipboard-check me-2"></i> Mark Attendance
                    </a>
                    <a href="students.php" class="btn btn-body-secondary border rounded-pill px-4 py-2 hover-translate shadow-sm">
                        <i class="bi bi-person-lines-fill me-2 text-primary"></i> <span class="text-secondary">Student Registry</span>
                    </a>
                    <a href="communication.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm hover-translate">
                        <i class="bi bi-chat-dots me-2"></i> Message Parents
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-bell-fill text-warning fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">Recent Notifications</h5>
                </div>
                <div class="list-group list-group-flush border-0">
                <?php
                $stmt = $pdo->prepare("
                    SELECT * FROM notifications 
                    WHERE id IN (
                        SELECT MAX(id) 
                        FROM notifications 
                        WHERE user_id = ? 
                        GROUP BY message, link
                    ) 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ");
                $stmt->execute([$teacher_id]);
                $notifications = $stmt->fetchAll();
                
                if ($notifications):
                    foreach ($notifications as $note): ?>
                        <div class="list-group-item border-0 border-bottom px-0 py-3 d-flex gap-3 bg-transparent">
                            <div class="bg-primary rounded-circle" style="width: 8px; height: 8px; margin-top: 6px; flex-shrink: 0;"></div>
                            <div>
                                <p class="mb-1 text-body small"><?php echo $note['message']; ?></p>
                                <small class="text-secondary d-flex align-items-center gap-1">
                                    <i class="bi bi-clock"></i> <?php echo date('M j, Y H:i', strtotime($note['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach;
                else:
                    echo "<div class='text-center py-5 text-secondary'>
                            <i class='bi bi-bell-slash display-4 mb-3 opacity-50'></i>
                            <p class='mb-0'>No new notifications</p>
                          </div>";
                endif;
                ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
