<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if logged in
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Determine which user to display
$current_user_id = (int)$_SESSION['user_id'];
$view_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $current_user_id;
$is_own_profile = ($view_user_id === $current_user_id);

// RBAC: Allow all logged in users to view profiles, but only admin/headmaster can view anyone
if (!$is_own_profile && !in_array($_SESSION['role'], ['admin', 'headmaster', 'teacher', 'parent'])) {
    flash('msg', 'You do not have permission to view this profile.', 'danger');
    redirect('profile.php');
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['update_profile']) || isset($_POST['full_name']))) {
    if (!$is_own_profile) {
        flash('msg', 'You can only update your own profile.', 'danger');
        redirect('profile.php');
    }

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
        $password = $_POST['password'];

        // Debug: Log values to session flash (only for developer troubleshooting)
        // flash('debug', "Updating ID: $current_user_id, Name: $full_name, Email: $email", 'info');

        if (empty($full_name)) {
        flash('msg', 'Full name is required.', 'danger');
        redirect('profile.php' . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));
    }

    try {
        if (!empty($password)) {
            if (strlen($password) < 6) {
                throw new Exception("Password must be at least 6 characters long.");
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $result = $stmt->execute([$full_name, $email, $phone, $hashed_password, $current_user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$full_name, $email, $phone, $current_user_id]);
        }
        
        if ($result) {
            // Update session variables
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            
            $count = $stmt->rowCount();
            if ($count > 0) {
                flash('msg', 'Profile updated successfully!', 'success');
            } else {
                // If rowCount is 0, it means the values sent were identical to what's in DB
                flash('msg', 'No changes were detected. Your profile already has this information.', 'info');
            }
        } else {
            flash('msg', 'System Error: Could not save your changes. Please try again.', 'danger');
        }
        
        // Redirect back to the same page with ID if it was there
        $redirect_url = 'profile.php' . (isset($_GET['id']) ? '?id=' . (int)$_GET['id'] : '');
        header("Location: " . $redirect_url);
        exit;
    } catch (Exception $e) {
        flash('msg', 'Error updating profile: ' . $e->getMessage(), 'danger');
        $redirect_url = 'profile.php' . (isset($_GET['id']) ? '?id=' . (int)$_GET['id'] : '');
        header("Location: " . $redirect_url);
        exit;
    }
}

// Include header AFTER potential redirect
require_once 'includes/header.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$view_user_id]);
$user = $stmt->fetch();

if (!$user) {
    flash('msg', 'User not found.', 'danger');
    redirect('profile.php');
}

// Fetch children if the user is a parent
$children = [];
if ($user['role'] === 'parent') {
    $stmt = $pdo->prepare("SELECT full_name, id FROM students WHERE parent_id = ?");
    $stmt->execute([$view_user_id]);
    $children = $stmt->fetchAll();
}

// Determine chat link based on role
$chat_link = '';
if (!$is_own_profile) {
    if (in_array($_SESSION['role'], ['admin', 'headmaster'])) {
        $chat_link = "admin/messages.php?user_id=" . $view_user_id;
    } elseif ($_SESSION['role'] === 'teacher') {
        $chat_link = "teacher/communication.php?user_id=" . $view_user_id;
    } elseif ($_SESSION['role'] === 'parent') {
        $chat_link = "parent/messages.php?user_id=" . $view_user_id;
    }
}
?>

<div class="rounded-4 p-3 p-md-4 mb-4 reveal border-0" style="background: var(--bs-tertiary-bg);">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 45px; height: 45px; min-width: 45px;">
                <i class="bi bi-person-circle fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-body fs-5 fs-md-4 text-truncate"><?php echo $is_own_profile ? 'My Profile' : 'User Profile'; ?></h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate"><?php echo $is_own_profile ? 'Manage your personal information.' : 'Viewing user details.'; ?></p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <?php if (!$is_own_profile && in_array($_SESSION['role'], ['admin', 'headmaster'])): ?>
                <a href="admin/users.php" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                    <i class="bi bi-people-fill text-primary fs-6"></i>
                    <span class="fw-bold small text-secondary d-none d-md-inline">Users</span>
                </a>
            <?php endif; ?>
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
</div>

<?php flash('msg'); ?>

<div class="row reveal">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form action="" method="POST">
                    <div class="row g-3 g-md-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Full Name</label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;"><i class="bi bi-person text-primary"></i></span>
                                <input type="text" name="full_name" class="form-control bg-body-tertiary border-0 py-2" value="<?php echo $user['full_name']; ?>" required <?php echo !$is_own_profile ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Username</label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;"><i class="bi bi-at text-secondary"></i></span>
                                <input type="text" class="form-control bg-body-tertiary border-0 py-2" value="<?php echo $user['username']; ?>" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Email Address</label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;"><i class="bi bi-envelope text-primary"></i></span>
                                <input type="email" name="email" class="form-control bg-body-tertiary border-0 py-2" value="<?php echo $user['email']; ?>" <?php echo !$is_own_profile ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Phone Number</label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;"><i class="bi bi-telephone text-primary"></i></span>
                                <input type="text" name="phone" class="form-control bg-body-tertiary border-0 py-2" value="<?php echo $user['phone']; ?>" <?php echo !$is_own_profile ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        <?php if ($is_own_profile): ?>
                        <div class="col-12 mt-4" id="security">
                            <h6 class="fw-bold text-uppercase small text-secondary mb-3 pb-2 border-bottom">Security Settings</h6>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary">New Password</label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;"><i class="bi bi-lock text-primary"></i></span>
                                    <input type="password" name="password" class="form-control bg-body-tertiary border-0 py-2" placeholder="Leave blank to keep current">
                                </div>
                                <small class="text-secondary d-block mt-1">Minimum 6 characters recommended.</small>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <input type="hidden" name="update_profile" value="1">
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm hover-translate">
                                Save Profile Changes
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-4 text-center">
                <div class="mb-3">
                    <div class="text-primary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; min-width: 80px; background: var(--bs-body-secondary);">
                        <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h5 class="fw-bold mb-1 text-body"><?php echo $user['full_name']; ?></h5>
                <p class="text-secondary small mb-3">@<?php echo $user['username']; ?></p>
                <div class="badge rounded-pill px-3 py-2 text-capitalize mb-3 fw-semibold border border-primary text-primary bg-transparent">
                    <?php echo $user['role']; ?>
                </div>

                <?php if (!empty($children)): ?>
                <div class="mt-3 text-start">
                    <h6 class="fw-bold small text-secondary text-uppercase mb-2 ps-1" style="letter-spacing: 0.5px; font-size: 0.7rem;">Guardian of</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($children as $child): ?>
                            <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-body-tertiary w-100">
                                <div class="bg-body-secondary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; min-width: 30px;">
                                    <i class="bi bi-person-heart text-primary small"></i>
                                </div>
                                <div class="min-width-0">
                                    <div class="fw-semibold small text-truncate"><?php echo $child['full_name']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($chat_link): ?>
                <div class="mt-2">
                    <a href="<?php echo $chat_link; ?>" class="btn btn-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center justify-content-center gap-2 transition-up shadow-sm">
                        <span>START A CHAT</span>
                    </a>
                </div>
                <?php endif; ?>

                <hr class="my-4 opacity-50">
                <div class="text-start">
                    <div class="d-flex align-items-center gap-3 p-2 rounded-3 bg-body-tertiary">
                        <div class="bg-body-secondary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; min-width: 35px;">
                            <i class="bi bi-calendar3 text-primary"></i>
                        </div>
                        <div class="min-width-0">
                            <div class="small text-secondary" style="font-size: 0.7rem;">Account Created</div>
                            <div class="fw-semibold small">Jan 2026</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
