<?php
require_once 'includes/header.php';

// Get all announcements
$stmt = $pdo->query("SELECT a.*, u.full_name as author FROM announcements a JOIN users u ON a.posted_by = u.id ORDER BY a.created_at DESC");
$announcements = $stmt->fetchAll();
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 shadow-sm border">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-megaphone-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">School News & Announcements</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Stay updated with the latest happenings at our school.</p>
            </div>
        </div>
        <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
            <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
            <span class="fw-bold small text-secondary">Go Back</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($announcements)): ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-inbox fs-1 text-secondary opacity-25 d-block mb-3"></i>
            <h6 class="text-secondary">No announcements found</h6>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $ann): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden transition-up">
                    <?php if ($ann['image']): ?>
                        <img src="assets/images/announcements/<?php echo $ann['image']; ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="News Image">
                    <?php else: ?>
                        <div class="bg-body-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-image text-secondary opacity-25 fs-1"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge border border-primary text-primary bg-transparent rounded-pill small px-3"><?php echo ucfirst($ann['type']); ?></span>
                            <small class="text-secondary opacity-75"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></small>
                        </div>
                        <h5 class="fw-bold text-primary mb-3"><?php echo htmlspecialchars($ann['title']); ?></h5>
                        <p class="text-secondary small mb-4 line-clamp-3">
                            <?php echo strip_tags($ann['content']); ?>
                        </p>
                        <button class="btn btn-outline-primary rounded-pill w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#annModal<?php echo $ann['id']; ?>">
                            Read Full Article
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($announcements)): ?>
    <!-- Announcement Modals -->
    <?php foreach ($announcements as $ann): ?>
        <div class="modal fade" id="annModal<?php echo $ann['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 rounded-4 bg-body shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 p-md-5 pt-0">
                            <div class="text-center mb-4">
                                <span class="badge border border-primary text-primary bg-transparent rounded-pill px-4 py-2 mb-3"><?php echo ucfirst($ann['type']); ?></span>
                                <h2 class="fw-bold text-primary"><?php echo htmlspecialchars($ann['title']); ?></h2>
                            <div class="d-flex align-items-center justify-content-center gap-3 text-secondary small">
                                <span><i class="bi bi-calendar3 me-1"></i> <?php echo date('F d, Y', strtotime($ann['created_at'])); ?></span>
                                <span><i class="bi bi-person me-1"></i> <?php echo $ann['author']; ?></span>
                            </div>
                        </div>

                        <?php if ($ann['image']): ?>
                            <img src="assets/images/announcements/<?php echo $ann['image']; ?>" class="img-fluid rounded-4 mb-4 w-100 shadow-sm" alt="Announcement">
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

<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.transition-up { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.transition-up:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
</style>

<?php require_once 'includes/footer.php'; ?>
