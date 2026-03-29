<?php
require_once 'includes/header.php';

$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE id IN (
        SELECT MAX(id) 
        FROM notifications 
        WHERE user_id = ? 
        GROUP BY message, link
    ) 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$all_notifications = $stmt->fetchAll();

// Mark all as read when visiting this page
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$user_id]);
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-bell-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">All Notifications</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Stay updated with your latest school activities and alerts.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary">Go Back</span>
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4 reveal">
    <div class="card-body p-0">
        <?php if (empty($all_notifications)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash fs-1 text-secondary opacity-25 d-block mb-3"></i>
                <h6 class="text-secondary">No notifications found</h6>
                <p class="text-secondary small">You don't have any notifications at the moment.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush rounded-4">
                <?php foreach ($all_notifications as $notif): 
                    // Handle relative links correctly
                    $link_raw = $notif['link'] ?? '';
                    $target_path = '';
                    
                    if (!empty($link_raw)) {
                        if (strpos($link_raw, 'admin/') === 0 || 
                            strpos($link_raw, 'headmaster/') === 0 || 
                            strpos($link_raw, 'teacher/') === 0 || 
                            strpos($link_raw, 'parent/') === 0) {
                            $target_path = $link_raw;
                        } else {
                            $role_dir = ($role == 'admin' ? 'admin/' : ($role == 'headmaster' ? 'headmaster/' : ($role == 'teacher' ? 'teacher/' : 'parent/')));
                            $target_path = $role_dir . $link_raw;
                        }
                    } else {
                        $target_path = '#';
                    }
                ?>
                    <a href="<?php echo $target_path; ?>" class="list-group-item list-group-item-action p-4 border-bottom border-start-4 <?php echo !$notif['is_read'] ? 'border-primary bg-body-secondary' : 'border-transparent bg-transparent'; ?> notification-item" data-id="<?php echo $notif['id']; ?>">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0">
                                <div class="icon-box-pro <?php echo !$notif['is_read'] ? 'bg-primary' : 'bg-body-secondary'; ?>" style="width: 40px; height: 40px;">
                                    <i class="bi <?php echo !$notif['is_read'] ? 'bi-info-circle-fill text-white' : 'bi-info-circle text-secondary'; ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="fw-bold mb-0 <?php echo !$notif['is_read'] ? 'text-primary' : 'text-body'; ?>">
                                        <?php echo htmlspecialchars($notif['title'] ?? 'System Notification'); ?>
                                    </h6>
                                    <small class="text-secondary opacity-75"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                                </div>
                                <p class="mb-0 text-secondary small"><?php echo htmlspecialchars($notif['message']); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function(e) {
        const id = this.getAttribute('data-id');
        const href = this.getAttribute('href');
        
        if (href !== '#') {
            e.preventDefault();
            // Mark as read then redirect
            fetch('api/notifications.php?action=mark_read&id=' + id)
                .then(() => {
                    window.location.href = href;
                });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
