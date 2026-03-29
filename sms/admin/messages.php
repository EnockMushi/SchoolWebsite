<?php
require_once '../includes/header.php';
checkRole(['admin', 'headmaster']);

$admin_id = $_SESSION['user_id'];
$target_user_id = $_GET['user_id'] ?? null;

// Get all users who have sent/received messages to/from admin, or the specific target user
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.role, u.last_seen, u.status 
    FROM users u 
    LEFT JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ? OR u.id = ?)
    AND u.id != ?
    ORDER BY u.full_name
");
$stmt->execute([$admin_id, $admin_id, $target_user_id, $admin_id]);
$contacts = $stmt->fetchAll();

$target_user = null;
if ($target_user_id) {
    $stmt = $pdo->prepare("SELECT id, full_name, role, last_seen, status FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch();
}
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro shadow-sm" style="width: 45px; height: 45px; min-width: 45px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-chat-text-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-truncate fs-5 fs-md-4">System Communications</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Direct messaging with all system users.</p>
            </div>
        </div>
        <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate ms-auto border-0">
            <i class="bi bi-arrow-left-circle-fill text-primary fs-5"></i>
            <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
            <span class="fw-bold small text-secondary d-md-none">Back</span>
        </a>
    </div>
</div>

<div class="row mb-4 align-items-center d-none">
    <div class="col-md-8">
        <h2 class="h4 mb-1">System Communications</h2>
        <p class="text-secondary small mb-0">Direct messaging with all system users across different roles.</p>
    </div>
</div>

<div id="chatWrapper" class="row g-4" style="height: calc(100vh - 200px); min-height: 500px;">
    <!-- Sidebar -->
    <div id="sidebarCol" class="col-lg-4 col-xl-3 h-100">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-header bg-transparent border-0 py-3 px-3 px-md-4">
                <h5 class="fw-bold mb-3">Contacts</h5>
                <div class="input-group">
                    <span class="input-group-text bg-body-secondary border-0 rounded-start-pill ps-3">
                        <i class="bi bi-search text-secondary small"></i>
                    </span>
                    <input type="text" id="contactSearch" class="form-control bg-body-secondary border-0 rounded-end-pill px-3 py-2 small" placeholder="Search contacts...">
                </div>
            </div>
            
            <div class="card-body p-0 overflow-auto">
                <div class="list-group list-group-flush" id="contactList">
                    <?php if (empty($contacts)): ?>
                        <div class="text-center py-5 px-3">
                            <i class="bi bi-chat-dots display-6 text-secondary opacity-25"></i>
                            <p class="text-secondary small mt-2">No active contacts found</p>
                        </div>
                    <?php else: ?>
                        <div id="noResultsContacts" class="text-center py-5 px-3" style="display: none;">
                            <i class="bi bi-search display-6 text-secondary opacity-25"></i>
                            <p class="text-secondary small mt-2">No contacts match your search</p>
                        </div>
                        <?php foreach ($contacts as $contact): 
                            $statusInfo = getUserStatus($contact['last_seen'], $contact['status']);
                        ?>
                            <div class="contact-item p-1" id="contact-wrapper-<?php echo $contact['id']; ?>">
                                <a href="javascript:void(0)" 
                                   onclick="loadChat(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['full_name']); ?>', '<?php echo ucfirst($contact['role']); ?>', '<?php echo $statusInfo['text']; ?>', '<?php echo $statusInfo['dot']; ?>')" 
                                   class="contact-link d-flex align-items-center gap-3 p-3 rounded-4 text-decoration-none transition-all <?php echo ($target_user_id == $contact['id']) ? 'active' : ''; ?>" 
                                   id="contact-<?php echo $contact['id']; ?>">
                                    <div class="position-relative flex-shrink-0">
                                        <div class="avatar-md rounded-circle bg-body-secondary text-primary d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 48px; height: 48px; font-size: 1.1rem;">
                                            <?php echo strtoupper(substr($contact['full_name'], 0, 1)); ?>
                                        </div>
                                        <span class="position-absolute bottom-0 end-0 p-1 border border-2 rounded-circle <?php echo str_replace('text-', 'bg-', $statusInfo['dot']); ?>" style="width: 13px; height: 13px; border-color: var(--bs-card-bg) !important;"></span>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <div class="fw-bold text-primary text-truncate small name-label"><?php echo $contact['full_name']; ?></div>
                                            <span class="smaller text-secondary d-none d-md-block" style="font-size: 0.65rem;"><?php echo $statusInfo['text']; ?></span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <span class="badge bg-body-secondary text-secondary rounded-pill border-0 smaller py-1 px-2 text-truncate" style="font-size: 0.7rem; max-width: 120px;"><?php echo ucfirst($contact['role']); ?></span>
                                            <button onclick="event.stopPropagation(); window.location.href='../profile.php?id=<?php echo $contact['id']; ?>'" class="btn btn-link p-0 text-primary profile-btn flex-shrink-0" title="View Profile">
                                                <i class="bi bi-person-circle fs-6"></i>
                                            </button>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Area -->
    <div id="chatCol" class="col-lg-8 col-xl-9 h-100">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden d-flex flex-column">
            <!-- Header -->
            <div id="chatHeader" class="card-header bg-transparent border-0 py-2 py-md-3 px-3 px-md-4 d-flex align-items-center gap-2 gap-md-3 border-bottom" style="display: none;">
                <button onclick="showContacts()" class="btn btn-body-secondary rounded-circle d-lg-none shadow-sm p-0 d-flex align-items-center justify-content-center border-0" style="width: 35px; height: 35px; min-width: 35px;">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div id="headerAvatar" class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; min-width: 40px; font-size: 1rem;"></div>
                <div class="flex-grow-1 min-width-0">
                    <h6 id="chatTitle" class="fw-bold mb-0 text-truncate small"></h6>
                    <div id="chatStatusContainer">
                        <div id="chatStatus" class="text-success smaller" style="font-size: 0.7rem;">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i> Active Now
                        </div>
                        <div id="blockStatus" class="text-danger smaller" style="display: none; font-size: 0.7rem;">
                            <i class="bi bi-ban me-1" style="font-size: 0.45rem;"></i> Blocked
                        </div>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-link text-secondary p-0" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm rounded-4">
                        <li>
                            <button id="blockBtn" onclick="toggleBlock()" class="dropdown-item text-danger py-2 small">
                                <i class="bi bi-slash-circle me-2"></i> Block User
                            </button>
                        </li>
                        <li>
                            <button id="unblockBtn" onclick="toggleBlock()" class="dropdown-item text-success py-2 small" style="display: none;">
                                <i class="bi bi-check-circle me-2"></i> Unblock User
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button onclick="clearChat()" class="dropdown-item text-danger py-2 small">
                                <i class="bi bi-trash3 me-2"></i> Clear Chat
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Messages Box -->
            <div id="chatBox" class="card-body bg-body-tertiary overflow-auto p-3 p-md-4 d-flex flex-column gap-3">
                <div class="text-center my-auto">
                    <div class="avatar-lg rounded-circle bg-body-secondary shadow-sm mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                        <i class="bi bi-chat-quote fs-2 text-primary opacity-25"></i>
                    </div>
                    <h6 class="text-secondary fw-bold small">Select a contact to view your conversation</h6>
                    <p class="text-secondary smaller">Your system communications will appear here</p>
                </div>
            </div>

            <!-- Block Warning -->
            <div id="blockWarning" class="alert alert-danger border-0 rounded-0 m-0 py-2 px-4 smaller" style="display: none;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                This conversation is blocked. You cannot send messages.
            </div>

            <!-- Input Area -->
            <div id="chatInputArea" class="card-footer bg-transparent border-0 p-2 p-md-3" style="display: none;">
                <div class="d-flex gap-2 bg-body-secondary p-1 p-md-2 rounded-pill shadow-sm align-items-center">
                    <input type="text" id="messageInput" class="form-control border-0 bg-transparent shadow-none px-3 py-1 py-md-2 small" placeholder="Write your message...">
                    <button onclick="sendMessage()" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="bi bi-send-fill" style="font-size: 1rem;"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.contact-link {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
}
.contact-link:hover {
    background-color: var(--bs-body-secondary-bg);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.contact-link.active {
    background-color: var(--bs-body-bg) !important;
    border-color: var(--bs-primary) !important;
    box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.1) !important;
}
.contact-link.active .name-label {
    color: var(--bs-primary) !important;
}
.profile-btn {
    opacity: 0.4;
    transform: scale(0.9);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.contact-link:hover .profile-btn {
    opacity: 1;
    transform: scale(1.1);
    color: var(--bs-primary) !important;
}
@media (max-width: 991.98px) {
    #sidebarCol { display: block; }
    #chatCol { display: none; }
    #chatWrapper.chat-active #sidebarCol { display: none; }
    #chatWrapper.chat-active #chatCol { display: block; }
}
.message { 
    max-width: 85%; 
    padding: 10px 14px; 
    border-radius: 18px; 
    font-size: 0.85rem; 
    position: relative;
    line-height: 1.4;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
@media (min-width: 768px) {
    .message {
        max-width: 75%;
        padding: 12px 16px;
        font-size: 0.9rem;
    }
}
.message.sent { 
    align-self: flex-end; 
    background: linear-gradient(135deg, var(--bs-primary), #0d6efd);
    color: #fff; 
    border-bottom-right-radius: 4px;
    box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.2);
}
.message.received { 
    align-self: flex-start; 
    background: var(--bs-body-bg); 
    color: var(--bs-body-color);
    border-bottom-left-radius: 4px;
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 4px;
    display: block;
}
.message.sent .message-time { text-align: right; }
.group:hover .group-hover-opacity-100 {
    opacity: 1 !important;
}
.transition-all {
    transition: all 0.2s ease;
}
</style>

<script>
let currentReceiverId = null;
let refreshInterval = null;
let isBlocked = false;
let iBlocked = false;
let lastMessagesJson = "";

function loadChat(receiverId, title, role, statusText, statusDot) {
    currentReceiverId = receiverId;
    lastMessagesJson = "";
    
    // Show chat on mobile
    const chatWrapper = document.getElementById('chatWrapper');
    if (chatWrapper) chatWrapper.classList.add('chat-active');
    
    const chatHeader = document.getElementById('chatHeader');
    if (chatHeader) chatHeader.style.display = 'flex';
    
    const chatTitle = document.getElementById('chatTitle');
    if (chatTitle) chatTitle.innerText = title;

    const headerAvatar = document.getElementById('headerAvatar');
    if (headerAvatar) headerAvatar.innerText = title.substring(0, 1);
    
    // Update chat status in header
    const chatStatus = document.getElementById('chatStatus');
    if (chatStatus) {
        chatStatus.innerHTML = `<i class="bi bi-circle-fill me-1 ${statusDot}" style="font-size: 0.5rem;"></i> ${statusText}`;
    }
    
    document.querySelectorAll('.contact-link').forEach(link => link.classList.remove('active'));
    const activeLink = document.getElementById('contact-' + receiverId);
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    fetchMessages();
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(fetchMessages, 3000);
}

function showContacts() {
    document.getElementById('chatWrapper').classList.remove('chat-active');
}

function fetchMessages() {
    if (!currentReceiverId) return;
    
    fetch(`../api/chat.php?action=fetch&other_id=${currentReceiverId}`)
        .then(res => res.json())
        .then(data => {
            const messages = data.messages;
            isBlocked = data.blocked;
            iBlocked = data.i_blocked;

            const blockWarning = document.getElementById('blockWarning');
            if (blockWarning) blockWarning.style.display = isBlocked ? 'block' : 'none';

            const chatInputArea = document.getElementById('chatInputArea');
            if (chatInputArea) chatInputArea.style.display = isBlocked ? 'none' : 'block';

            const blockStatus = document.getElementById('blockStatus');
            if (blockStatus) blockStatus.style.display = isBlocked ? 'block' : 'none';

            const chatStatus = document.getElementById('chatStatus');
            if (chatStatus) chatStatus.style.display = isBlocked ? 'none' : 'block';

            const blockBtn = document.getElementById('blockBtn');
            const unblockBtn = document.getElementById('unblockBtn');
            if (iBlocked) {
                if (blockBtn) blockBtn.style.display = 'none';
                if (unblockBtn) unblockBtn.style.display = 'block';
            } else {
                if (blockBtn) blockBtn.style.display = 'block';
                if (unblockBtn) unblockBtn.style.display = 'none';
            }

            const currentJson = JSON.stringify(messages);
            if (currentJson === lastMessagesJson) return;
            lastMessagesJson = currentJson;

            const chatBox = document.getElementById('chatBox');
            const shouldScroll = chatBox.scrollTop + chatBox.clientHeight >= chatBox.scrollHeight - 20;
            
            if (messages.length === 0) {
                chatBox.innerHTML = `
                    <div class="text-center my-auto opacity-50">
                        <i class="bi bi-chat-dots display-4 d-block mb-2"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>`;
            } else {
                chatBox.innerHTML = messages.map(msg => {
                    const isSent = msg.sender_id == <?php echo $admin_id; ?>;
                    const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const statusIcon = isSent ? (msg.is_read == 1 ? '<i class="bi bi-check-all text-info"></i>' : '<i class="bi bi-check"></i>') : '';
                    
                    return `
                        <div class="message ${isSent ? 'sent' : 'received'} reveal group">
                            <div class="message-content">${msg.message}</div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mt-1">
                                <span class="message-time">${time} ${statusIcon}</span>
                                <button onclick="deleteMessage(${msg.id})" class="btn p-0 border-0 text-danger opacity-0 group-hover-opacity-100 transition-all" style="font-size: 0.7rem;" title="Delete message">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
            }
            
            if (shouldScroll) chatBox.scrollTop = chatBox.scrollHeight;
        });
}

function toggleBlock() {
    const action = iBlocked ? 'unblock' : 'block';
    if (!confirm(`Are you sure you want to ${action} this user?`)) return;

    fetch('../api/chat.php?action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ target_id: currentReceiverId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) fetchMessages();
        else if (data.error) alert(data.error);
    });
}

function clearChat() {
    if (!currentReceiverId) return;
    if (!confirm('Are you sure you want to CLEAR ALL messages in this conversation? This action cannot be undone.')) return;

    fetch('../api/chat.php?action=clear_chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ other_id: currentReceiverId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            lastMessagesJson = "";
            fetchMessages();
        } else if (data.error) {
            alert(data.error);
        }
    });
}

function deleteMessage(messageId) {
    if (!confirm('Delete this message?')) return;

    fetch('../api/chat.php?action=delete_message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message_id: messageId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            lastMessagesJson = "";
            fetchMessages();
        } else if (data.error) {
            alert(data.error);
        }
    });
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    if (!message || !currentReceiverId) return;
    
    input.value = '';
    fetch('../api/chat.php?action=send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiver_id: currentReceiverId, message: message })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchMessages();
            setTimeout(() => {
                const chatBox = document.getElementById('chatBox');
                chatBox.scrollTop = chatBox.scrollHeight;
            }, 100);
        }
    });
}

const messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') sendMessage();
    });
}

const contactSearch = document.getElementById('contactSearch');
if (contactSearch) {
    contactSearch.addEventListener('input', function() {
        let filter = this.value.toLowerCase().trim();
        let contacts = document.querySelectorAll('.contact-link');
        let hasResults = false;

        contacts.forEach(link => {
            let nameElement = link.querySelector('.fw-bold');
            if (nameElement) {
                let name = nameElement.innerText.toLowerCase();
                if (name.includes(filter)) {
                    link.style.display = '';
                    hasResults = true;
                } else {
                    link.style.display = 'none';
                }
            }
        });

        let noResults = document.getElementById('noResultsContacts');
        if (noResults) {
            noResults.style.display = hasResults ? 'none' : 'block';
        }
    });
}

<?php if ($target_user): 
    $targetStatus = getUserStatus($target_user['last_seen'], $target_user['status']);
?>
window.addEventListener('DOMContentLoaded', () => {
    loadChat(<?php echo $target_user['id']; ?>, '<?php echo htmlspecialchars($target_user['full_name']); ?>', '<?php echo ucfirst($target_user['role']); ?>', '<?php echo $targetStatus['text']; ?>', '<?php echo $targetStatus['dot']; ?>');
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>
