            </div><!-- End of container-fluid -->
        </div><!-- End of content -->
    </div><!-- End of wrapper -->
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3" style="background: rgba(248, 249, 252, 0.9); backdrop-filter: blur(10px); border-top: 1px solid rgba(255, 255, 255, 0.3);">
        <div class="container-fluid">
            <div class="text-center">
                <div class="mb-1">
                    <span class="text-muted">&copy; <?php echo date('Y'); ?> E-GABAY ASC. All rights reserved.</span>
                </div>
                <div>
                    <small class="text-muted">
                        Developed by <strong>Keith Torda</strong> & Team | 
                        <a href="#" class="text-decoration-none text-muted" data-bs-toggle="modal" data-bs-target="#creditsModal">
                            <i class="fas fa-info-circle me-1"></i>Credits
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Credits Modal -->
    <div class="modal fade" id="creditsModal" tabindex="-1" aria-labelledby="creditsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="creditsModalLabel">
                        <i class="fas fa-code me-2"></i>Development Credits
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-user-tie me-2"></i>Lead Developer
                            </h6>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Keith Bryan O.Torda</h6>
                                    <small class="text-muted">Full-Stack Developer</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-users me-2"></i>Development Team
                            </h6>
                            <div class="mb-2">
                                <i class="fas fa-user-friends me-2 text-muted"></i>
                                <strong>Keith Bryan O.Torda</strong> - UI/UX Designer
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-user-friends me-2 text-muted"></i>
                                <strong>Richael M. Ulibas</strong> - Paperworks Designer
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-user-friends me-2 text-muted"></i>
                                <strong>Christian Ancheta</strong> - Content Writer
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-tools me-2"></i>Technologies Used
                            </h6>
                            <div class="row">
                                <div class="col-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fab fa-php me-2 text-primary"></i>PHP 8.x</li>
                                        <li><i class="fas fa-database me-2 text-primary"></i>MySQL</li>
                                        <li><i class="fab fa-bootstrap me-2 text-primary"></i>Bootstrap 5</li>
                                    </ul>
                                </div>
                                <div class="col-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fab fa-js me-2 text-warning"></i>JavaScript</li>
                                        <li><i class="fab fa-css3-alt me-2 text-info"></i>CSS3</li>
                                        <li><i class="fab fa-html5 me-2 text-danger"></i>HTML5</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-calendar me-2"></i>Project Information
                            </h6>
                            <div class="mb-2">
                                <i class="fas fa-calendar-plus me-2 text-muted"></i>
                                <strong>Started:</strong> <?php echo date('2024-04-27'); ?>
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-code-branch me-2 text-muted"></i>
                                <strong>Version:</strong> 1.0.0
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-graduation-cap me-2 text-muted"></i>
                                <strong>Purpose:</strong> CAPSTONE 2 PROJECT
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <div class="alert alert-light">
                            <i class="fas fa-heart text-danger me-2"></i>
                            <strong>Built with passion for education and student support</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <?php if (isLoggedIn()): ?>
    <!-- Notifications JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/notifications.js"></script>
    <?php endif; ?>
    
    <!-- Print JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/print.js"></script>
    
    <?php if (isLoggedIn()): ?>
<script>
// Enhanced anti-back-button security for authenticated pages
(function() {
    // Prevent back button after logout by checking session status
    if (window.history && window.history.pushState) {
        // Replace current history entry to prevent back navigation to cached pages
        window.history.replaceState(null, null, window.location.href);
        
        // Listen for back/forward button events
        window.addEventListener("popstate", function(event) {
            // Immediately check session validity
            fetch("<?php echo SITE_URL; ?>/api/check_session.php", {
                method: "POST",
                credentials: "same-origin"
            }).then(response => response.json())
            .then(data => {
                if (!data.valid) {
                    // Session invalid - redirect to login
                    alert("Your session has expired. Please log in again.");
                    window.location.replace("<?php echo SITE_URL; ?>/login.php");
                    return false;
                } else {
                    // Session valid - allow navigation but refresh to ensure fresh data
                    window.location.reload();
                }
            }).catch(() => {
                // Network error - just reload the page instead of redirecting
                console.log("Session check failed on back navigation - reloading page");
                window.location.reload();
            });
            
            // Prevent the back navigation while we check
            event.preventDefault();
            return false;
        });
    }
    
    // Tab visibility session check removed to prevent unwanted redirects on Alt-Tab
})();
</script>
<?php endif; ?>

    <script>
        // Enhanced sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarCollapseDesktop = document.getElementById('sidebarCollapseDesktop');
            const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
            const sidebar = document.querySelector('.sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const content = document.querySelector('.content');
            
            // Mobile sidebar toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleMobileSidebar();
                });
            }
            
            // Mobile sidebar overlay click
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    closeMobileSidebar();
                });
            }
            
            // Mobile sidebar functions
            function toggleMobileSidebar() {
                    sidebar.classList.toggle('show');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('show');
                }
                document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
            }
            
            function closeMobileSidebar() {
                sidebar.classList.remove('show');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('show');
                }
                document.body.style.overflow = '';
            }
            
                         // Desktop sidebar collapse/expand
             function toggleSidebarCollapse() {
                 sidebar.classList.toggle('collapsed');
                 content.classList.toggle('expanded');
                 
                 // Save collapse state to localStorage
                 const isCollapsed = sidebar.classList.contains('collapsed');
                 localStorage.setItem('sidebarCollapsed', isCollapsed);
                 
                 // Update tooltips after toggle
                 setTimeout(updateTooltips, 100);
             }
            
            // Desktop navbar toggle
            if (sidebarCollapseDesktop) {
                sidebarCollapseDesktop.addEventListener('click', toggleSidebarCollapse);
            }
            
            // Sidebar internal toggle button
            if (sidebarCollapseBtn) {
                sidebarCollapseBtn.addEventListener('click', toggleSidebarCollapse);
            }
            
            // Restore sidebar state from localStorage (desktop only)
            if (window.innerWidth > 1024) {
                const savedState = localStorage.getItem('sidebarCollapsed');
                if (savedState === 'true') {
                    sidebar.classList.add('collapsed');
                    content.classList.add('expanded');
                }
            }
            
            // Ensure mobile sidebar always shows text
            function ensureMobileTextVisibility() {
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('collapsed');
                    content.classList.remove('expanded');
                }
            }
            
            // Call on load and resize
            ensureMobileTextVisibility();
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    closeMobileSidebar();
                }
                ensureMobileTextVisibility();
            });
            
            // Initialize tooltips for collapsed state
            updateTooltips();
            
            // User profile modal is now handled purely by Bootstrap data attributes — no manual JS needed to avoid duplicate overlays.
            
            // Initialize Bootstrap dropdowns properly
            function initializeDropdowns() {
                console.log('Initializing dropdowns...');
                
                // Remove any existing dropdown instances
                const existingDropdowns = document.querySelectorAll('.dropdown-toggle');
                existingDropdowns.forEach(el => {
                    const instance = bootstrap.Dropdown.getInstance(el);
                    if (instance) instance.dispose();
                });
                
                // Re-initialize all dropdowns
                const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                    console.log('Initializing dropdown:', dropdownToggleEl.id);
                    return new bootstrap.Dropdown(dropdownToggleEl);
                });
                
                console.log('Dropdowns initialized:', dropdownList.length);
            }
            
            // Wait for Bootstrap to be fully loaded
            if (typeof bootstrap !== 'undefined') {
                initializeDropdowns();
            } else {
                // Retry initialization after a delay if Bootstrap isn't ready
                setTimeout(() => {
                    if (typeof bootstrap !== 'undefined') {
                        initializeDropdowns();
                    } else {
                        console.error('Bootstrap not found!');
                    }
                }, 500);
            }
            
                         // Manual dropdown toggle fallback
            if (typeof bootstrap === 'undefined') {
                // Bootstrap not found; use manual fallback toggle
                document.addEventListener('click', function(e) {
                    const dropdownToggle = e.target.closest('.dropdown-toggle');
                    if (dropdownToggle) {
                        e.preventDefault();
                        e.stopPropagation();
                        const dropdownMenu = dropdownToggle.nextElementSibling;
                        if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                            // Close other dropdowns
                            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                                if (menu !== dropdownMenu) {
                                    menu.classList.remove('show');
                                    const toggle = menu.previousElementSibling;
                                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                                }
                            });
                            // Toggle current dropdown
                            dropdownMenu.classList.toggle('show');
                            const expanded = dropdownMenu.classList.contains('show');
                            dropdownToggle.setAttribute('aria-expanded', expanded);
                        }
                    }
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                        const toggle = menu.previousElementSibling;
                        if (toggle) toggle.setAttribute('aria-expanded', 'false');
                    });
                }
            });
            
            // Close sidebar when clicking outside on mobile/tablet
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 1024 && 
                    sidebar && 
                    sidebar.classList.contains('show') && 
                    !sidebar.contains(event.target) && 
                    !sidebarToggle.contains(event.target)) {
                    closeMobileSidebar();
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    closeMobileSidebar();
                }
            });
            
                         // Add tooltips for collapsed sidebar items
             function updateTooltips() {
                 // Dispose existing tooltips first
                 const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                 existingTooltips.forEach(el => {
                     const tooltip = bootstrap.Tooltip.getInstance(el);
                     if (tooltip) tooltip.dispose();
                 });
                 
                 const navLinks = sidebar.querySelectorAll('.nav-link');
                 navLinks.forEach(link => {
                     const span = link.querySelector('span');
                     if (span && sidebar.classList.contains('collapsed')) {
                         link.setAttribute('title', span.textContent.trim());
                         link.setAttribute('data-bs-toggle', 'tooltip');
                         link.setAttribute('data-bs-placement', 'right');
                         link.setAttribute('data-bs-delay', '{"show":500,"hide":100}');
                     } else {
                         link.removeAttribute('title');
                         link.removeAttribute('data-bs-toggle');
                         link.removeAttribute('data-bs-placement');
                         link.removeAttribute('data-bs-delay');
                     }
                 });
                 
                 // Reinitialize tooltips
                 if (window.bootstrap && sidebar.classList.contains('collapsed')) {
                     const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                     tooltipTriggerList.map(function (tooltipTriggerEl) {
                         return new bootstrap.Tooltip(tooltipTriggerEl, {
                             boundary: 'window',
                             placement: 'right',
                             offset: [0, 10]
                         });
                     });
                 }
             }
            
            // Update tooltips when sidebar state changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        updateTooltips();
                    }
                });
            });
            
            if (sidebar) {
                observer.observe(sidebar, { attributes: true });
                updateTooltips(); // Initial setup
            }

            // Mobile hamburger (navbar-toggler) opens profile modal directly
            const navToggler = document.querySelector('.navbar-toggler');
            if (navToggler) {
                navToggler.addEventListener('click', function (e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        const modalEl = document.getElementById('userProfileModal');
                        if (modalEl && typeof bootstrap !== 'undefined') {
                            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                            modalInstance.show();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html> 