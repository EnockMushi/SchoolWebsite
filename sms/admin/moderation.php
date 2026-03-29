<?php
require_once '../includes/header.php';
checkRole(['admin']);

// Handle Message Deletion
if (isset($_GET['delete_msg'])) {
    $id = $_GET['delete_msg'];
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$id]);
    flash('msg', 'Message deleted successfully.', 'alert alert-info');
    redirect('moderation.php');
}

// Get recent messages with sender/receiver names
try {
    $stmt = $pdo->query("
        SELECT m.*, u1.full_name as sender_name, u2.full_name as receiver_name 
        FROM messages m 
        JOIN users u1 ON m.sender_id = u1.id 
        JOIN users u2 ON m.receiver_id = u2.id 
        ORDER BY m.created_at DESC 
        LIMIT 100
    ");
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $messages = [];
}
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-3 p-md-4 mb-4 shadow-sm border">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-shield-lock-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-truncate fs-5 fs-md-4">Chat Moderation</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Monitor system communication.</p>
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
    <div class="input-group shadow-sm rounded-4 overflow-hidden">
        <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;">
            <i class="bi bi-search text-primary"></i>
        </span>
        <input type="text" id="messageSearch" class="form-control bg-body-secondary border-0 py-2 py-md-3" placeholder="Search messages...">
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-secondary">
                    <tr>
                        <th scope="col" class="ps-4 border-0 py-3 text-secondary small text-uppercase">Timestamp</th>
                        <th scope="col" class="border-0 py-3 text-secondary small text-uppercase">From</th>
                        <th scope="col" class="border-0 py-3 text-secondary small text-uppercase">To</th>
                        <th scope="col" class="border-0 py-3 text-secondary small text-uppercase">Message Content</th>
                        <th scope="col" class="pe-4 text-end border-0 py-3 text-secondary small text-uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="messageTable">
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="bg-body-secondary rounded-circle d-inline-flex p-4 text-secondary opacity-50 mb-3">
                                    <i class="bi bi-chat-left-dots fs-1"></i>
                                </div>
                                <h6 class="text-secondary fw-bold">No messages found in logs</h6>
                                <p class="text-secondary small">System communications will appear here once users start chatting.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr id="noResults" style="display: none;">
                            <td colspan="5" class="text-center py-5">
                                <div class="bg-body-secondary rounded-circle d-inline-flex p-4 text-secondary opacity-50 mb-3">
                                    <i class="bi bi-search fs-1"></i>
                                </div>
                                <h6 class="text-secondary fw-bold">No matches found</h6>
                                <p class="text-secondary small">Try adjusting your search terms</p>
                            </td>
                        </tr>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="text-primary fw-bold small"><?php echo date('M j, Y', strtotime($msg['created_at'])); ?></div>
                                    <div class="text-secondary smaller opacity-75"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-3 bg-body-secondary text-primary d-flex align-items-center justify-content-center me-3 fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                            <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <a href="../profile.php?id=<?php echo $msg['sender_id']; ?>" class="text-decoration-none hover-underline">
                                                <div class="fw-bold small text-primary"><?php echo $msg['sender_name']; ?></div>
                                            </a>
                                            <div class="text-secondary smaller" style="font-size: 0.7rem;">Sender</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-3 bg-body-secondary text-info d-flex align-items-center justify-content-center me-3 fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                            <?php echo strtoupper(substr($msg['receiver_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <a href="../profile.php?id=<?php echo $msg['receiver_id']; ?>" class="text-decoration-none hover-underline">
                                                <div class="fw-bold small text-primary"><?php echo $msg['receiver_name']; ?></div>
                                            </a>
                                            <div class="text-secondary smaller" style="font-size: 0.7rem;">Receiver</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-secondary small bg-body-secondary p-2 rounded-3 text-wrap" style="max-width: 400px; line-height: 1.4;">
                                        <?php echo htmlspecialchars($msg['message']); ?>
                                    </div>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="messages.php?user_id=<?php echo $msg['sender_id']; ?>" class="btn btn-sm btn-body-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm border-0 p-0" style="width: 32px; height: 32px;" title="Join Conversation">
                                            <i class="bi bi-chat-dots text-primary"></i>
                                        </a>
                                        <a href="?delete_msg=<?php echo $msg['id']; ?>" class="btn btn-sm btn-body-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm border-0 p-0" style="width: 32px; height: 32px;" onclick="return confirm('Permanently delete this message from logs?')" title="Delete Message">
                                            <i class="bi bi-trash text-danger"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('messageSearch').addEventListener('input', function() {
    let filter = this.value.toLowerCase().trim();
    let rows = document.querySelectorAll('#messageTable tr:not(#noResults)');
    let hasResults = false;

    rows.forEach(row => {
        if(row.cells.length > 1) {
            let text = row.innerText.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        }
    });

    let noResults = document.getElementById('noResults');
    if (noResults) {
        noResults.style.display = hasResults ? 'none' : '';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
