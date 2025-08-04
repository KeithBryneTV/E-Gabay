/**
 * E-GABAY ASC - Enhanced Main JavaScript
 * Modern interactive features and animations
 */

// Enhanced UI interactions with reduced animations for performance
document.addEventListener('DOMContentLoaded', function() {
    // Reduce motion preference check
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    // Smooth scrolling for anchor links with faster duration
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        if (!anchor.getAttribute('href') || anchor.getAttribute('href') === '#') return;
        
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: prefersReduced ? 'auto' : 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Enhanced card animations with reduced duration (capped at 20 for performance)
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        if (index < 20 && !prefersReduced) { // Limit animations and respect motion preference
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)'; // Reduced from 20px
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.2s ease, transform 0.2s ease'; // Reduced from 0.6s
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 25); // Reduced delay from 100ms to 25ms
        }
    });

    // Button hover effects with reduced duration
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            if (!prefersReduced) {
                this.style.transition = 'all 0.1s ease'; // Reduced from 0.3s
            }
        });
    });

    // Form validation feedback with instant response
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });

    // Enhanced tooltips with reduced delay
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: { show: 100, hide: 50 } // Reduced delays
            });
        });
    }

    // Page load progress indicator (simplified)
    if (!prefersReduced) {
        const progressBar = document.createElement('div');
        progressBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 2px;
            background: linear-gradient(90deg, #4e73df, #36b9cc);
            z-index: 9999;
            transition: width 0.1s ease;
        `;
        document.body.appendChild(progressBar);
        
        // Simple progress simulation
        let width = 0;
        const interval = setInterval(() => {
            width += Math.random() * 10;
            if (width >= 100) {
                width = 100;
                progressBar.style.width = width + '%';
                setTimeout(() => {
                    progressBar.style.opacity = '0';
                    setTimeout(() => progressBar.remove(), 100); // Reduced from 300ms
                }, 100); // Reduced from 200ms
                clearInterval(interval);
            } else {
                progressBar.style.width = width + '%';
            }
        }, 50); // Reduced from 100ms
    }
});

/**
 * Initialize smooth animations for page elements
 */
function initializeAnimations() {
    const cards = document.querySelectorAll('.card');
    const maxAnim = 30; // prevent too many animations on low-power devices
    cards.forEach((card, index) => {
        if (index > maxAnim) return; // skip extra cards
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 60);
    });
    
    // Add slide-in animation to sidebar links
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach((link, index) => {
        link.style.opacity = '0';
        link.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            link.style.transition = 'all 0.4s ease';
            link.style.opacity = '1';
            link.style.transform = 'translateX(0)';
        }, 300 + (index * 50));
    });
}

/**
 * Initialize interactive elements
 */
function initializeInteractiveElements() {
    // Enhanced button hover effects
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });
    
    // Enhanced card hover effects
    const interactiveCards = document.querySelectorAll('.dashboard-card, .stat-card');
    interactiveCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
            this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '';
        });
    });
    
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const hrefVal = this.getAttribute('href');
            if (!hrefVal || hrefVal === '#' || hrefVal.length <= 1) return; // skip empty anchors
            e.preventDefault();
            const target = document.querySelector(hrefVal);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Enhanced form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Real-time validation feedback
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateField(this);
                }
            });
        });
        
        // Enhanced form submission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showFormErrors(form);
            } else {
                showSubmissionFeedback(form);
            }
        });
    });
}

/**
 * Validate individual form field
 */
function validateField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    let isValid = true;
    let message = '';
    
    // Remove existing validation classes
    field.classList.remove('is-valid', 'is-invalid');
    
    // Required field validation
    if (isRequired && !value) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    // Password validation
    if (field.type === 'password' && value && value.length < 6) {
        isValid = false;
        message = 'Password must be at least 6 characters long';
    }
    
    // Apply validation class
    field.classList.add(isValid ? 'is-valid' : 'is-invalid');
    
    // Show/hide feedback message
    updateFieldFeedback(field, message, isValid);
    
    return isValid;
}

/**
 * Update field feedback message
 */
function updateFieldFeedback(field, message, isValid) {
    let feedback = field.parentNode.querySelector('.invalid-feedback');
    
    if (!isValid && message) {
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
        feedback.style.display = 'block';
    } else if (feedback) {
        feedback.style.display = 'none';
    }
}

/**
 * Show form submission feedback
 */
function showSubmissionFeedback(form) {
    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        submitBtn.disabled = true;
        
        // Reset button after 3 seconds if form hasn't redirected
        setTimeout(() => {
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }, 3000);
    }
}

/**
 * Show form error summary
 */
function showFormErrors(form) {
    const invalidFields = form.querySelectorAll('.is-invalid');
    if (invalidFields.length > 0) {
        // Scroll to first invalid field
        invalidFields[0].scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        
        // Add shake animation to form
        form.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
            form.style.animation = '';
        }, 500);
    }
}

/**
 * Enhanced table features
 */
function initializeTableEnhancements() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        // Add row hover effects
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fc';
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'all 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.transform = 'scale(1)';
            });
        });
        
        // Add sorting indicators to sortable headers
        const sortableHeaders = table.querySelectorAll('th[data-sort]');
        sortableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';
            
            header.addEventListener('click', function() {
                // Add sorting functionality here if needed
                console.log('Sorting by:', this.dataset.sort);
            });
        });
    });
}

/**
 * Initialize card animations and interactions
 */
function initializeCardAnimations() {
    // Counter animation for statistics
    const statNumbers = document.querySelectorAll('.stat-number, .h5.mb-0.font-weight-bold');
    
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    statNumbers.forEach(element => {
        observer.observe(element);
    });
    
    // Progress bar animations
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width || bar.getAttribute('aria-valuenow') + '%';
        bar.style.width = '0%';
        bar.style.transition = 'width 1.5s ease-in-out';
        
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
}

/**
 * Animate counter numbers
 */
function animateCounter(element) {
    const target = parseInt(element.textContent) || 0;
    const duration = 1500;
    const step = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 16);
}

/**
 * Initialize Bootstrap components
 */
function initializeBootstrapComponents() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
}

/**
 * Utility function for showing notifications
 */
function showNotification(message, type = 'info', duration = 4000) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const alertElement = document.createElement('div');
    alertElement.innerHTML = alertHtml;
    document.body.appendChild(alertElement.firstElementChild);
    
    // Auto-remove after duration
    setTimeout(() => {
        const alert = document.querySelector('.alert.position-fixed');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, duration);
}

/**
 * Utility function for confirming actions
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Add CSS animations
 */
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .animate-pulse {
        animation: pulse 2s infinite;
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    
    .loading-spinner {
        width: 3rem;
        height: 3rem;
        border: 0.3em solid #f3f3f3;
        border-top: 0.3em solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Export functions for global use
window.EGabay = {
    showNotification,
    confirmAction,
    validateField,
    animateCounter
}; 