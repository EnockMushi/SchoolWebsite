<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
checkRole(['admin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug logging
    file_put_contents('../debug_post.txt', print_r($_POST, true) . print_r($_FILES, true));
    
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
        try {
            $pdo->beginTransaction();
            
            // Handle File Uploads
            $upload_dir = '../assets/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
                $logo_name = 'logo_' . time() . '_' . $_FILES['site_logo']['name'];
                if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_dir . $logo_name)) {
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'site_logo'");
                    $stmt->execute(['assets/images/' . $logo_name]);
                }
            }

            if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] == 0) {
                $favicon_name = 'favicon_' . time() . '_' . $_FILES['site_favicon']['name'];
                if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $upload_dir . $favicon_name)) {
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'site_favicon'");
                    $stmt->execute(['assets/images/' . $favicon_name]);
                }
            }

            // First, get all existing keys to know whether to INSERT or UPDATE
            $stmt = $pdo->query("SELECT setting_key FROM site_settings");
            $existing_keys = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $update_stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $insert_stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");

            foreach ($_POST['settings'] as $key => $value) {
                if (in_array($key, $existing_keys)) {
                    $update_stmt->execute([$value, $key]);
                } else {
                    $insert_stmt->execute([$key, $value]);
                }
            }
            
            $pdo->commit();
            flash('msg', 'Settings updated successfully.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('msg', 'Error updating settings: ' . $e->getMessage(), 'alert alert-danger');
        }
    }
    header("Location: settings.php");
    exit();
}

require_once '../includes/header.php';

$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 shadow-sm border">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-gear-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-truncate fs-5 fs-md-4">System Settings</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Configure school identity and branding.</p>
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

<div>
    <?php flash('msg'); ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <!-- Branding Section -->
                        <div class="col-12">
                            <h6 class="fw-bold text-uppercase small text-secondary mb-3">School Branding</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-secondary">School Name</label>
                                    <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                        <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;"><i class="bi bi-bank text-primary"></i></span>
                                        <input type="text" name="settings[site_name]" value="<?php echo $settings['site_name'] ?? ''; ?>" class="form-control bg-body-tertiary border-0 py-2" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-secondary">School Tagline/Motto</label>
                                    <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                        <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;"><i class="bi bi-quote text-primary"></i></span>
                                        <textarea name="settings[site_tagline]" class="form-control bg-body-tertiary border-0 py-2" rows="4" placeholder="Enter school motto or tagline..."><?php echo $settings['site_tagline'] ?? ''; ?></textarea>
                                    </div>
                                    <small class="text-secondary mt-1 d-block">This appears under the school name in the header and reports.</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 bg-body-tertiary rounded-4 border shadow-sm text-center">
                                        <label class="form-label small fw-bold text-uppercase tracking-wider text-secondary d-block mb-3">School Logo</label>
                                        <div class="mb-3 logo-preview-container">
                                            <?php if (!empty($settings['site_logo'])): ?>
                                                <div class="preview-box p-3 rounded-3 shadow-sm d-inline-block">
                                                    <img src="../<?php echo $settings['site_logo']; ?>" alt="Logo" class="img-fluid" style="max-height: 100px; object-fit: contain;">
                                                </div>
                                            <?php else: ?>
                                                <div class="preview-box p-4 rounded-3 shadow-sm d-inline-block border">
                                                    <i class="bi bi-image text-muted fs-1"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-upload text-secondary"></i></span>
                                            <input type="file" name="site_logo" class="form-control bg-body-secondary border-0" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 bg-body-tertiary rounded-4 border shadow-sm text-center">
                                        <label class="form-label small fw-bold text-uppercase tracking-wider text-secondary d-block mb-3">Site Favicon</label>
                                        <div class="mb-3 favicon-preview-container">
                                            <?php if (!empty($settings['site_favicon'])): ?>
                                                <div class="preview-box p-3 rounded-3 shadow-sm d-inline-block">
                                                    <img src="../<?php echo $settings['site_favicon']; ?>" alt="Favicon" class="img-fluid" style="max-height: 60px; width: 60px; object-fit: contain;">
                                                </div>
                                            <?php else: ?>
                                                <div class="preview-box p-4 rounded-3 shadow-sm d-inline-block border">
                                                    <i class="bi bi-star text-muted fs-1"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-upload text-secondary"></i></span>
                                            <input type="file" name="site_favicon" class="form-control bg-body-secondary border-0" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Info Section -->
                        <div class="col-12 mt-5">
                            <h6 class="fw-bold text-uppercase small text-secondary mb-3">Basic Information</h6>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">School Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-envelope text-secondary"></i></span>
                                        <input type="email" name="settings[site_email]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['site_email'] ?? ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">School Phone</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-telephone text-secondary"></i></span>
                                        <input type="text" name="settings[site_phone]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['site_phone'] ?? ''; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic & Operational Section -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-secondary mb-3">Academic & Operations</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Academic Year</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-calendar-event text-secondary"></i></span>
                                        <input type="text" name="settings[academic_year]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['academic_year'] ?? ''; ?>" placeholder="2025/2026">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Current Term</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-clock-history text-secondary"></i></span>
                                        <input type="text" name="settings[current_term]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['current_term'] ?? ''; ?>" placeholder="Term 1">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">School Hours</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-clock text-secondary"></i></span>
                                        <input type="text" name="settings[school_hours]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['school_hours'] ?? ''; ?>" placeholder="Mon - Fri: 8:00 AM - 4:00 PM">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact & Location Section -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-secondary mb-3">Contact & Location</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">School Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0 align-items-start pt-2"><i class="bi bi-geo-alt text-secondary"></i></span>
                                        <textarea name="settings[site_address]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" rows="3"><?php echo $settings['site_address'] ?? ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Google Maps Embed URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0 align-items-start pt-2"><i class="bi bi-map text-secondary"></i></span>
                                        <textarea name="settings[google_maps_embed]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" rows="3" placeholder="https://www.google.com/maps/embed?..."><?php echo $settings['google_maps_embed'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="form-text small">Paste the 'src' attribute from the Google Maps iframe embed code.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media Section -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-secondary mb-3">Social Media Presence</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Facebook URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-facebook text-primary"></i></span>
                                        <input type="url" name="settings[facebook_url]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['facebook_url'] ?? ''; ?>" placeholder="https://facebook.com/...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Twitter URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-twitter-x text-primary"></i></span>
                                        <input type="url" name="settings[twitter_url]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['twitter_url'] ?? ''; ?>" placeholder="https://twitter.com/...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Instagram URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-instagram text-danger"></i></span>
                                        <input type="url" name="settings[instagram_url]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['instagram_url'] ?? ''; ?>" placeholder="https://instagram.com/...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">LinkedIn URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-linkedin text-primary"></i></span>
                                        <input type="url" name="settings[linkedin_url]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['linkedin_url'] ?? ''; ?>" placeholder="https://linkedin.com/school/...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Website Content Section -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-secondary mb-3">Website Content</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">About School (Brief)</label>
                                <textarea name="settings[site_about]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" rows="4"><?php echo $settings['site_about'] ?? ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Footer Copyright Text</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-0"><i class="bi bi-c-circle text-secondary"></i></span>
                                    <input type="text" name="settings[copyright_text]" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-end-3" value="<?php echo $settings['copyright_text'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Theme Colors -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-secondary mb-3">System Theme Colors</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-3 bg-body-secondary p-2 rounded-3">
                                        <input type="color" name="settings[primary_color]" class="form-control form-control-color border-0 bg-transparent p-0" value="<?php echo $settings['primary_color'] ?? '#1a5f7a'; ?>" style="width: 45px; height: 45px;">
                                        <div>
                                            <label class="form-label small fw-semibold text-secondary mb-0">Primary Theme Color</label>
                                            <div class="small text-secondary font-monospace"><?php echo $settings['primary_color'] ?? '#1a5f7a'; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-3 bg-body-secondary p-2 rounded-3">
                                        <input type="color" name="settings[secondary_color]" class="form-control form-control-color border-0 bg-transparent p-0" value="<?php echo $settings['secondary_color'] ?? '#86c232'; ?>" style="width: 45px; height: 45px;">
                                        <div>
                                            <label class="form-label small fw-semibold text-secondary mb-0">Secondary Theme Color</label>
                                            <div class="small text-secondary font-monospace"><?php echo $settings['secondary_color'] ?? '#86c232'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mt-5">
                            <button type="submit" name="save_settings" class="btn btn-primary px-5 py-2 rounded-3 fw-bold shadow-sm">
                                <i class="bi bi-save2-fill me-2"></i> Save Configuration
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info Column -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 glass-container security-card-glass mb-4 overflow-hidden position-relative">
            <!-- Background Icon -->
            <i class="bi bi-shield-lock position-absolute bottom-0 end-0 bg-icon" style="font-size: 120px; margin-right: -20px; margin-bottom: -20px; z-index: 0;"></i>
            
            <div class="card-body p-4 text-center position-relative" style="z-index: 1;">
                <div class="icon-box-inner rounded-circle d-inline-flex align-items-center justify-content-center mb-3 border border-white-10 shadow-sm" style="width: 60px; height: 60px; margin: 0 auto;">
                    <i class="bi bi-shield-check fs-2"></i>
                </div>
                <h5 class="fw-bold">System Security</h5>
                <p class="small mb-0 opacity-75">These settings control how the system identifies itself to users and search engines.</p>
            </div>
        </div>
        
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Quick Preview</h6>
                <div class="p-3 bg-body-secondary rounded-4 text-center border">
                    <div class="d-flex align-items-center gap-2 justify-content-center mb-2">
                        <?php if (!empty($settings['site_logo'])): ?>
                            <div class="logo-container d-inline-block" style="padding: 4px;">
                                <img src="../<?php echo $settings['site_logo']; ?>" alt="Logo" style="max-height: 30px;">
                            </div>
                        <?php endif; ?>
                        <span class="fw-bold h6 mb-0"><?php echo $settings['site_name'] ?? 'School App'; ?></span>
                    </div>
                    <div class="small text-secondary"><?php echo $settings['site_email'] ?? ''; ?></div>
                    <div class="small text-secondary"><?php echo $settings['site_phone'] ?? ''; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
