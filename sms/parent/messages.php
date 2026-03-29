<?php
require_once '../includes/header.php';
checkRole(['parent']);

$parent_id = $_SESSION['user_id'];
$target_user_id = $_GET['user_id'] ?? null;

// Get teachers of my children
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.last_seen, u.status, s.full_name as student_name, s.id as student_id
    FROM users u 
    JOIN teacher_assignments ta ON u.id = ta.teacher_id
    JOIN students s ON s.class_id = ta.class_id 
    WHERE s.parent_id = ?
");
$stmt->execute([$parent_id]);
$teachers = $stmt->fetchAll();

$target_user = null;
if ($target_user_id) {
    foreach ($teachers as $teacher) {
        if ($teacher['id'] == $target_user_id) {
            $target_user = [
                'id' => $teacher['id'],
                'full_name' => $teacher['full_name'],
                'title' => 'Teacher: ' . $teacher['full_name'],
                'student_id' => $teacher['student_id'],
                'last_seen' => $teacher['last_seen'],
                'status' => $teacher['status']
            ];
            break;
        }
    }
}

    // Fallback: If still no target user, check if the user exists and allow chatting
    if (!$target_user) {
        $stmt = $pdo->prepare("SELECT id, full_name, role, last_seen, status FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $user_data = $stmt->fetch();
        if ($user_data) {
            $target_user = [
                'id' => $user_data['id'],
                'full_name' => $user_data['full_name'],
                'title' => ucfirst($user_data['role']) . ': ' . $user_data['full_name'],
                'student_id' => 'null',
                'last_seen' => $user_data['last_seen'],
                'status' => $user_data['status']
            ];
        }
    }

?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-chat-left-dots-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Teacher Communications</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Message your child's teachers.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <a href="javascript:history.back()" class="btn btn-body-secondary border shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-5"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
</div>

<div id="chatWrapper" class="row g-4" style="height: calc(100vh - 280px); min-height: 500px;">
    <!-- Sidebar -->
    <div id="sidebarCol" class="col-lg-4 col-xl-3 h-100">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-header bg-transparent border-0 py-3 px-3 px-md-4">
                <h5 class="fw-bold mb-3">Contacts</h5>
                <div class="input-group">
                    <span class="input-group-text bg-body-secondary border-0 rounded-start-pill ps-3">
                        <i class="bi bi-search text-secondary small"></i>
                    </span>
                    <input type="text" id="contactSearch" class="form-control bg-body-secondary border-0 rounded-end-pill px-3 py-2 small" placeholder="Search teachers...">
                </div>
            </div>
            
            <div class="card-body p-0 overflow-auto">
                <div class="list-group list-group-flush" id="contactList">
                    <div id="noResults" class="text-center py-5 px-3" style="display: none;">
                        <i class="bi bi-search display-6 text-secondary opacity-25"></i>
                        <p class="text-secondary small mt-2">No teachers match your search</p>
                    </div>
                    <?php if (empty($teachers)): ?>
                        <div class="text-center py-5 px-3">
                            <i class="bi bi-chat-dots display-6 text-secondary opacity-25"></i>
                            <p class="text-secondary small mt-2">No active teachers found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($teachers as $teacher): 
                            $statusInfo = getUserStatus($teacher['last_seen'], $teacher['status']);
                        ?>
                            <div class="contact-item p-1" id="contact-wrapper-<?php echo $teacher['id']; ?>">
                                <a href="javascript:void(0)" 
                                   onclick="loadChat(<?php echo $teacher['id']; ?>, 'Teacher: <?php echo htmlspecialchars($teacher['full_name']); ?>', <?php echo $teacher['student_id']; ?>, '<?php echo $statusInfo['text']; ?>', '<?php echo $statusInfo['dot']; ?>')" 
                                   class="contact-link d-flex align-items-center gap-3 p-3 rounded-4 text-decoration-none transition-all <?php echo ($target_user_id == $teacher['id']) ? 'active' : ''; ?>" 
                                   id="contact-<?php echo $teacher['id']; ?>">
                                    <div class="position-relative flex-shrink-0">
                                        <div class="icon-box-pro fw-bold shadow-sm" style="width: 48px; height: 48px; font-size: 1.1rem;">
                                            <?php echo strtoupper(substr($teacher['full_name'], 0, 1)); ?>
                                        </div>
                                        <span class="position-absolute bottom-0 end-0 p-1 border border-body border-2 rounded-circle <?php echo str_replace('text-', 'bg-', $statusInfo['dot']); ?>" style="width: 13px; height: 13px;"></span>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <div class="fw-bold text-body text-truncate small name-label"><?php echo $teacher['full_name']; ?></div>
                                            <span class="smaller text-secondary d-none d-md-block" style="font-size: 0.65rem;"><?php echo $statusInfo['text']; ?></span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <span class="badge bg-body-secondary border text-secondary rounded-pill smaller py-1 px-2 text-truncate" style="font-size: 0.7rem; max-width: 120px;">Teacher of <?php echo $teacher['student_name']; ?></span>
                                            <button onclick="event.stopPropagation(); window.location.href='../profile.php?id=<?php echo $teacher['id']; ?>'" class="btn btn-link p-0 text-primary profile-btn flex-shrink-0" title="View Profile">
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
            <div id="chatHeader" class="card-header bg-transparent border-0 py-2 py-md-3 px-3 px-md-4 d-flex align-items-center gap-2 gap-md-3" style="display: none;">
                <button onclick="showContacts()" class="btn btn-body-secondary border rounded-circle d-lg-none shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; min-width: 35px;">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div id="headerAvatar" class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; min-width: 40px; font-size: 1rem;"></div>
                <div class="flex-grow-1 min-width-0">
                    <h6 id="chatTitle" class="fw-bold mb-0 text-truncate small"></h6>
                    <div id="chatStatusContainer">
                        <div id="chatStatus" class="text-success smaller" style="font-size: 0.7rem;">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i> Active Now
                        </div>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-link text-secondary p-0" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border shadow-sm rounded-4">
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
            <div id="chatBox" class="card-body bg-body-secondary bg-opacity-10 overflow-auto p-3 p-md-4 d-flex flex-column gap-3">
                <div class="text-center my-auto">
                    <div class="icon-box-pro mx-auto mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-chat-quote fs-2 text-primary opacity-25"></i>
                    </div>
                    <h6 class="text-secondary fw-bold small">Select a contact to view your conversation</h6>
                    <p class="text-secondary smaller">Your system communications will appear here</p>
                </div>
            </div>

            <!-- Block Warning -->
            <div id="blockWarning" class="alert alert-danger border-0 rounded-0 m-0 py-2 px-4 small" style="display: none;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                This conversation is blocked. You cannot send messages.
            </div>

            <!-- Input Area -->
            <div id="chatInputArea" class="card-footer bg-transparent border-0 p-2 p-md-3" style="display: none;">
                <div class="d-flex gap-2 bg-body-secondary p-1 p-md-2 rounded-pill shadow-sm align-items-center border">
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
    position: relative;
    font-size: 0.85rem;
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
    color: white;
    border-bottom-right-radius: 4px;
}
.message.received {
    align-self: flex-start;
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-bottom-left-radius: 4px;
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.message-time {
    font-size: 0.7rem;
    margin-top: 4px;
    opacity: 0.8;
}
.group:hover .group-hover-opacity-100 {
    opacity: 1 !important;
}
.transition-all {
    transition: all 0.2s ease;
}
</style>

<script>
let currentReceiverId = null;
let currentStudentId = null;
let refreshInterval = null;
let isBlocked = false;
let iBlocked = false;
let lastMessagesJson = "";

// Search contacts
document.getElementById('contactSearch').addEventListener('input', function() {
    let value = this.value.toLowerCase().trim();
    let contacts = document.querySelectorAll('.contact-link');
    let hasResults = false;
    
    contacts.forEach(link => {
        let text = link.textContent.toLowerCase();
        if (text.includes(value)) {
            link.style.display = 'flex';
            hasResults = true;
        } else {
            link.style.display = 'none';
        }
    });
    
    let noResults = document.getElementById('noResults');
    if (noResults) {
        noResults.style.display = hasResults ? 'none' : 'block';
    }
});

function loadChat(receiverId, title, studentId = null, statusText = 'Offline', statusDot = 'text-danger') {
    currentReceiverId = receiverId;
    currentStudentId = studentId;
    lastMessagesJson = "";
    
    // Show chat on mobile
    document.getElementById('chatWrapper').classList.add('chat-active');
    
    const header = document.getElementById('chatHeader');
    header.style.display = 'flex';
    header.style.setProperty('display', 'flex', 'important');
    
    document.getElementById('chatTitle').innerText = title;
    const headerAvatar = document.getElementById('headerAvatar');
    headerAvatar.innerText = title.includes(': ') ? title.split(': ')[1].substring(0, 1).toUpperCase() : title.substring(0, 1).toUpperCase();
    
    // Update chat status in header
    const chatStatus = document.getElementById('chatStatus');
    chatStatus.innerHTML = `<i class="bi bi-circle-fill me-1 ${statusDot}" style="font-size: 0.5rem;"></i> ${statusText}`;
    
    document.querySelectorAll('.contact-link').forEach(link => link.classList.remove('active'));
    const contactLink = document.getElementById('contact-' + receiverId);
    if (contactLink) {
        contactLink.classList.add('active');
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
    
    let url = `../api/chat.php?action=fetch&other_id=${currentReceiverId}`;
    if (currentStudentId) url += `&student_id=${currentStudentId}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            const messages = data.messages;
            isBlocked = data.blocked;
            iBlocked = data.i_blocked;

            document.getElementById('blockWarning').style.display = isBlocked ? 'block' : 'none';
            document.getElementById('chatInputArea').style.display = isBlocked ? 'none' : 'block';
            
            const blockBtn = document.getElementById('blockBtn');
            const unblockBtn = document.getElementById('unblockBtn');
            
            if (iBlocked) {
                blockBtn.style.display = 'none';
                unblockBtn.style.display = 'block';
            } else {
                blockBtn.style.display = 'block';
                unblockBtn.style.display = 'none';
            }

            const currentJson = JSON.stringify(messages);
            if (currentJson === lastMessagesJson) return;
            lastMessagesJson = currentJson;

            const chatBox = document.getElementById('chatBox');
            const shouldScroll = chatBox.scrollTop + chatBox.clientHeight >= chatBox.scrollHeight - 50;
            
            if (messages.length === 0) {
                chatBox.innerHTML = `
                    <div class="text-center my-auto">
                        <div class="avatar-lg rounded-circle bg-white shadow-sm mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-chat-dots fs-1 text-primary opacity-25"></i>
                        </div>
                        <h6 class="text-secondary">No messages yet</h6>
                        <p class="text-secondary small">Say hello to start the conversation!</p>
                    </div>`;
            } else {
                chatBox.innerHTML = '';
                messages.forEach(msg => {
                    const isSent = msg.sender_id == <?php echo $parent_id; ?>;
                    const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    const msgDiv = document.createElement('div');
                    msgDiv.className = `message ${isSent ? 'sent' : 'received'} group`;
                    
                    let statusIcon = '';
                    if (isSent) {
                        statusIcon = msg.is_read == 1 
                            ? '<i class="bi bi-check2-all text-info ms-1"></i>' 
                            : '<i class="bi bi-check2 ms-1"></i>';
                    }

                    msgDiv.innerHTML = `
                        <div class="message-content">${msg.message}</div>
                        <div class="d-flex align-items-center justify-content-between gap-2 mt-1">
                            <span class="message-time">${time}${statusIcon}</span>
                            <button onclick="deleteMessage(${msg.id})" class="btn p-0 border-0 text-danger opacity-0 group-hover-opacity-100 transition-all" style="font-size: 0.7rem;" title="Delete message">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                    chatBox.appendChild(msgDiv);
                });
            }
            
            if (shouldScroll || chatBox.innerHTML.includes('No messages yet')) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
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
    
    fetch('../api/chat.php?action=send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            receiver_id: currentReceiverId,
            message: message,
            student_id: currentStudentId
        })
    }).then(() => {
        input.value = '';
        fetchMessages();
    });
}

// Enter to send
document.getElementById('messageInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') sendMessage();
});

<?php if ($target_user): 
    $targetStatus = getUserStatus($target_user['last_seen'], $target_user['status']);
?>
window.addEventListener('DOMContentLoaded', () => {
    loadChat(<?php echo $target_user['id']; ?>, '<?php echo addslashes($target_user['title']); ?>', <?php echo $target_user['student_id']; ?>, '<?php echo $targetStatus['text']; ?>', '<?php echo $targetStatus['dot']; ?>');
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>
