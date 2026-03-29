<?php
require_once 'includes/header.php';
?>

<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-question-circle-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">Help Center</h4>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Find answers and support for using the school management system.</p>
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

<div class="row g-4 reveal">
    <div class="col-lg-8">
        <!-- Search Section -->
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">How can we help you?</h5>
                <div class="input-group bg-body-secondary rounded-pill px-3 py-1 border-0 shadow-none mb-2">
                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-secondary"></i></span>
                    <input type="text" class="form-control bg-transparent border-0 shadow-none" placeholder="Search for help topics...">
                </div>
                <p class="text-secondary small mb-0 ms-2">Popular topics: <span class="text-primary cursor-pointer">Attendance</span>, <span class="text-primary cursor-pointer">Fee Payment</span>, <span class="text-primary cursor-pointer">Requests</span></p>
            </div>
        </div>

        <!-- FAQs -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Frequently Asked Questions</h5>
                
                <div class="accordion accordion-flush" id="faqAccordion">
                    <div class="accordion-item border-0 mb-3 rounded-4 bg-body-secondary">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-transparent fw-bold text-body rounded-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How do I update my profile information?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small pt-0">
                                You can update your profile by clicking on your name in the top right corner and selecting "My Profile". From there, you can edit your details and upload a new profile picture.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-3 rounded-4 bg-body-secondary">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-transparent fw-bold text-body rounded-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                I forgot my password, what should I do?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small pt-0">
                                On the login page, click the "Forgot Password" link. You will be asked to enter your registered email address to receive instructions on how to reset your password.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-3 rounded-4 bg-body-secondary">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-transparent fw-bold text-body rounded-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                How can I view my child's academic progress?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small pt-0">
                                If you are a parent, you can access the "Progress" section from your sidebar menu. This section provides a detailed overview of your child's grades, attendance, and teacher remarks.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-0 rounded-4 bg-body-secondary">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-transparent fw-bold text-body rounded-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                How do I contact the school administration?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small pt-0">
                                You can use the "Messages" section to send a direct message to the school administration or specific teachers. Alternatively, you can find the school's contact information in the footer of this page.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Contact Support -->
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-4 text-center">
                <div class="icon-box-pro mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-headset fs-2"></i>
                </div>
                <h5 class="fw-bold mb-2">Need direct help?</h5>
                <p class="text-secondary small mb-4">Our support team is available from Monday to Friday, 8:00 AM - 5:00 PM.</p>
                <a href="mailto:support@schoolapp.com" class="btn btn-primary w-100 rounded-3 py-2 fw-bold mb-2">
                    <i class="bi bi-envelope-fill me-2"></i> Email Support
                </a>
                <a href="tel:+255123456789" class="btn btn-body-secondary border-0 w-100 rounded-3 py-2 fw-bold hover-translate shadow-sm">
                    <i class="bi bi-telephone-fill me-2 text-primary"></i> <span class="text-secondary">Call Us</span>
                </a>
            </div>
        </div>

        <!-- Documentation Links -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Quick Guides</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="#" class="text-decoration-none text-secondary small d-flex align-items-center gap-2 p-2 rounded-3 bg-body-secondary hover-primary transition-all">
                        <i class="bi bi-file-earmark-pdf text-danger"></i> Teacher User Manual
                    </a>
                    <a href="#" class="text-decoration-none text-secondary small d-flex align-items-center gap-2 p-2 rounded-3 bg-body-secondary hover-primary transition-all">
                        <i class="bi bi-file-earmark-pdf text-danger"></i> Parent Guide
                    </a>
                    <a href="#" class="text-decoration-none text-secondary small d-flex align-items-center gap-2 p-2 rounded-3 bg-body-secondary hover-primary transition-all">
                        <i class="bi bi-shield-check text-success"></i> Security Best Practices
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cursor-pointer { cursor: pointer; }
.transition-all { transition: all 0.2s ease; }
.hover-primary:hover { background-color: var(--primary-subtle) !important; color: var(--primary-color) !important; transform: translateX(5px); }
.accordion-button:not(.collapsed) { background-color: var(--primary-subtle); color: var(--primary-color); }
</style>

<?php require_once 'includes/footer.php'; ?>
