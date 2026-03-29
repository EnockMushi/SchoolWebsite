/**
 * Project: School Management System
 * Author: Enock Samson Mushi
 * Signature: Enock Samson Mushi Made this project
 * Copyright: © 2026 Enock Samson Mushi. All rights reserved.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("%c Enock Samson Mushi Made this project ", "color: #0d6efd; font-weight: bold; background: #f8f9fa; padding: 5px; border-radius: 5px;");

    // Sidebar & Toggle Logic (Moved to top for priority)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const wrapper = document.getElementById('wrapper');

    if (sidebarToggle && wrapper) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            wrapper.classList.toggle('toggled');
            console.log('Sidebar toggled. Classes:', wrapper.className);
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (wrapper && wrapper.classList.contains('toggled') && window.innerWidth < 992) {
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            if (sidebarWrapper && !sidebarWrapper.contains(e.target) && !sidebarToggle.contains(e.target)) {
                wrapper.classList.remove('toggled');
            }
        }
    });

    // Page loading animation
    document.body.classList.add('animate-fade');
    
    // Safety: ensure body is visible even if animation fails or takes too long
    // Also handles the "page keeps loading" issue by ensuring visibility
    const ensureVisibility = () => {
        document.body.style.opacity = '1';
        document.body.style.visibility = 'visible';
    };
    
    setTimeout(ensureVisibility, 500);
    window.addEventListener('load', ensureVisibility);
    
    // Form Validation & Feedback
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.7';
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
            }
        });
    });

    // Alert dismissal with smooth animation
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.marginLeft = 'auto';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.fontSize = '1.2rem';
        closeBtn.onclick = () => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        };
        alert.appendChild(closeBtn);

        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 400);
            }
        }, 8000);
    });

    // Back to Top functionality
    const backToTopBtn = document.createElement('button');
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
    backToTopBtn.setAttribute('aria-label', 'Back to top');
    document.body.appendChild(backToTopBtn);

    backToTopBtn.onclick = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });

    // Smooth Scroll for Internal Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            
            // Skip if it's just "#" or empty or not a valid selector
            if (!href || href === '#' || href.startsWith('##')) return;
            
            try {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            } catch (err) {
                // If querySelector fails (e.g. href="#123" which is invalid CSS selector)
                // we just let the default browser behavior happen or ignore it
            }
        });
    });

    // Notification Handlers
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown) {
        notificationDropdown.addEventListener('show.bs.dropdown', function() {
            // Hide the red dot badge immediately for better UX
            const badge = notificationDropdown.querySelector('.badge-dot');
            if (badge) {
                badge.style.display = 'none';
            }
        });
    }

    document.querySelectorAll('.notification-dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            const id = this.getAttribute('data-id');
            const href = this.getAttribute('href');
            
            if (href && href !== '#') {
                e.preventDefault();
                
                // Immediate UI Feedback: Hide badge and remove item
                const badge = document.querySelector('.btn .badge');
                const headerBadge = document.querySelector('.dropdown-menu .badge');
                const notificationCount = parseInt(headerBadge ? headerBadge.innerText : '0');
                
                if (headerBadge && notificationCount > 0) {
                    const newCount = notificationCount - 1;
                    headerBadge.innerText = `${newCount} New`;
                    if (newCount <= 0 && badge) {
                        badge.style.display = 'none';
                        headerBadge.innerText = '0 New';
                    }
                }
                
                this.style.opacity = '0.5';
                this.style.pointerEvents = 'none';

                // Get the correct base path for the API call
                const isSubDir = window.location.pathname.includes('/admin/') || 
                               window.location.pathname.includes('/teacher/') || 
                               window.location.pathname.includes('/parent/') || 
                               window.location.pathname.includes('/headmaster/');
                const apiPath = isSubDir ? '../api/notifications.php' : 'api/notifications.php';
                
                // Fire and forget fetch with keepalive
                // We navigate after a very short delay to allow the fetch to initiate
                fetch(`${apiPath}?action=mark_read&id=${id}`, { 
                    method: 'GET',
                    keepalive: true,
                    mode: 'no-cors'
                }).catch(err => console.log('Notification mark-read fetch error:', err));

                // Navigate after a small delay to ensure fetch initiates
                setTimeout(() => {
                    window.location.href = href;
                }, 100);
            }
        });
    });
});