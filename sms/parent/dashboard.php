<?php
require_once '../includes/header.php';
checkRole(['parent']);

$parent_id = $_SESSION['user_id'];

// Get students linked to this parent
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.parent_id = ?
");
$stmt->execute([$parent_id]);
$my_students = $stmt->fetchAll();
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-people-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Parent Dashboard</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Overview of children's performance.</p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center gap-2 mb-3 px-1">
    <h5 class="fw-bold mb-0 text-body">My Children</h5>
    <span class="badge border border-primary text-primary rounded-pill px-3 bg-transparent"><?php echo count($my_students); ?> Total</span>
</div>

<?php if ($my_students): ?>
    <div class="row g-4 mb-5">
        <?php foreach ($my_students as $student): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 pro-card border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="icon-box-pro">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-1 text-body"><?php echo $student['full_name']; ?></h5>
                            <p class="text-secondary small mb-3">Class: <?php echo $student['class_name']; ?></p>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <a href="progress.php?id=<?php echo $student['id']; ?>" class="btn btn-primary w-100 small py-2 shadow-sm hover-translate">
                                    <i class="bi bi-graph-up me-1"></i> View Progress
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card shadow-sm border-0 rounded-4 mb-5">
        <div class="card-body text-center py-5">
            <i class="bi bi-person-x display-4 text-secondary opacity-50 mb-3"></i>
            <p class="text-secondary mb-0">No students registered under your account. Please contact the administration.</p>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone-fill text-primary fs-4"></i>
                        <h5 class="card-title fw-bold mb-0">School News & Announcements</h5>
                    </div>
                </div>
                
                <?php
                $stmt = $pdo->query("SELECT a.*, u.full_name as author FROM announcements a JOIN users u ON a.posted_by = u.id WHERE a.is_public = 1 ORDER BY a.created_at DESC LIMIT 3");
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
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-chat-left-text-fill text-warning fs-4"></i>
                    <h5 class="card-title fw-bold mb-0">Quick Contact</h5>
                </div>
                <p class="text-secondary mb-4">Need to discuss your child's performance with a teacher? Send a direct message to the school administration or teachers.</p>
                <a href="messages.php" class="btn btn-primary px-5 py-2 shadow-sm hover-translate">
                    <i class="bi bi-chat-dots-fill me-2"></i> Start Conversation
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
