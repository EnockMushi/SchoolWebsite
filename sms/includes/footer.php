        </div>
        <!-- End of Main Content Body -->
        
        <footer class="bg-body border-top py-4 mt-auto">
            <div class="container-fluid px-4 px-lg-5">
                <div class="row align-items-center justify-content-between">
                    <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                        <p class="mb-0 text-secondary small">&copy; <?php echo date('Y'); ?> <span class="fw-bold text-body"><?php echo getSetting('site_name', $pdo); ?></span>. All rights reserved.</p>
                        <p class="mb-0 text-secondary" style="font-size: 0.65rem; opacity: 0.5;">Developed by <a href="#" class="text-decoration-none text-secondary fw-bold">Enock Samson Mushi</a></p>
                    </div>
                    <!-- Signature: Enock Samson Mushi Made this project -->
                    <div class="col-md-6 text-center text-md-end">
                        <div class="d-flex justify-content-center justify-content-md-end gap-3">
                            <a href="<?php echo $base_path; ?>help.php" class="text-decoration-none text-secondary small hover-primary">Help Center</a>
                            <a href="<?php echo $base_path; ?>../privacy.php" class="text-decoration-none text-secondary small hover-primary">Privacy Policy</a>
                            <a href="<?php echo $base_path; ?>../terms.php" class="text-decoration-none text-secondary small hover-primary">Terms of Service</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <!-- End of page-content-wrapper -->
</div>
<!-- End of wrapper -->

<!-- Offline Bootstrap JS -->
<script src="<?php echo $base_path; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo $base_path; ?>assets/js/main.js"></script>

<script>
    // Auto-hide flash messages
    document.addEventListener('DOMContentLoaded', function() {
        const flashMsg = document.getElementById('msg-flash');
        if (flashMsg) {
            setTimeout(() => {
                flashMsg.style.transition = 'opacity 0.5s ease';
                flashMsg.style.opacity = '0';
                setTimeout(() => flashMsg.remove(), 500);
            }, 5000);
        }
    });

    // Digital Clock (if exists)
    document.addEventListener('DOMContentLoaded', function() {
        const clockElement = document.getElementById('digitalClock');
        if (clockElement) {
            setInterval(() => {
                const now = new Date();
                clockElement.textContent = now.toLocaleTimeString();
            }, 1000);
        }
    });
</script>
</body>
</html>
