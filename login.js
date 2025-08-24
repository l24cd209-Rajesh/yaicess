// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initTabSwitching();
    initFormValidation();
    initAnimations();
    initErrorHandling();
});

// Tab Switching Functionality
function initTabSwitching() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const loginForms = document.querySelectorAll('.login-form');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and forms
            tabButtons.forEach(btn => btn.classList.remove('active'));
            loginForms.forEach(form => form.classList.remove('active'));
            
            // Add active class to clicked button and corresponding form
            this.classList.add('active');
            document.getElementById(`${targetTab}-login`).classList.add('active');
            
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
}

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.login-btn');
            const inputs = this.querySelectorAll('input[required]');
            let isValid = true;
            
            // Reset previous error states
            inputs.forEach(input => {
                input.parentElement.classList.remove('error');
                const errorMsg = input.parentElement.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
            });
            
            // Validate each required input
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    showInputError(input, 'This field is required');
                } else if (input.type === 'email' && !isValidEmail(input.value)) {
                    isValid = false;
                    showInputError(input, 'Please enter a valid email address');
                } else if (input.type === 'password' && input.value.length < 6) {
                    isValid = false;
                    showInputError(input, 'Password must be at least 6 characters');
                }
            });
            
            if (isValid) {
                handleFormSubmission(this, submitBtn);
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateInput(this);
            });
            
            input.addEventListener('input', function() {
                // Remove error state on input
                this.parentElement.classList.remove('error');
                const errorMsg = this.parentElement.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
            });
        });
    });
}

// Input validation helper
function validateInput(input) {
    const value = input.value.trim();
    
    if (input.hasAttribute('required') && !value) {
        showInputError(input, 'This field is required');
        return false;
    }
    
    if (input.type === 'email' && value && !isValidEmail(value)) {
        showInputError(input, 'Please enter a valid email address');
        return false;
    }
    
    if (input.type === 'password' && value && value.length < 6) {
        showInputError(input, 'Password must be at least 6 characters');
        return false;
    }
    
    return true;
}

// Show input error
function showInputError(input, message) {
    const inputContainer = input.parentElement;
    inputContainer.classList.add('error');
    
    // Remove existing error message
    const existingError = inputContainer.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Create error message
    const errorMsg = document.createElement('div');
    errorMsg.className = 'error-message';
    errorMsg.textContent = message;
    
    inputContainer.appendChild(errorMsg);
    
    // Add shake animation
    inputContainer.style.animation = 'errorShake 0.5s ease-in-out';
    setTimeout(() => {
        inputContainer.style.animation = '';
    }, 500);
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Handle form submission
function handleFormSubmission(form, submitBtn) {
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.classList.add('loading');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
    
    // Submit the form
    form.submit();
}

// Animations
function initAnimations() {
    // Add entrance animation to login card
    const loginCard = document.querySelector('.login-card');
    if (loginCard) {
        loginCard.style.opacity = '0';
        loginCard.style.transform = 'translateY(30px)';
        loginCard.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        
        setTimeout(() => {
            loginCard.style.opacity = '1';
            loginCard.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Add hover effects to login card
    if (loginCard) {
        loginCard.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        loginCard.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    }
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Helper functions for notifications
function getNotificationIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

function getNotificationColor(type) {
    const colors = {
        success: '#4caf50',
        error: '#ff6b6b',
        warning: '#ff9800',
        info: '#667eea'
    };
    return colors[type] || colors.info;
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    // Tab navigation
    if (e.key === 'Tab') {
        const activeElement = document.activeElement;
        if (activeElement.classList.contains('tab-btn')) {
            e.preventDefault();
            const tabButtons = document.querySelectorAll('.tab-btn');
            const currentIndex = Array.from(tabButtons).indexOf(activeElement);
            const nextIndex = e.shiftKey ? currentIndex - 1 : currentIndex + 1;
            
            if (nextIndex >= 0 && nextIndex < tabButtons.length) {
                tabButtons[nextIndex].click();
                tabButtons[nextIndex].focus();
            }
        }
    }
    
    // Enter key on form submission
    if (e.key === 'Enter') {
        const activeElement = document.activeElement;
        if (activeElement.tagName === 'INPUT' && activeElement.closest('form')) {
            const form = activeElement.closest('form');
            const submitBtn = form.querySelector('.login-btn');
            if (submitBtn) {
                submitBtn.click();
            }
        }
    }
});

// Accessibility improvements
function initAccessibility() {
    // Add ARIA labels
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.setAttribute('aria-describedby', 'password-requirements');
    });
    
    // Add focus indicators
    const focusableElements = document.querySelectorAll('button, input, a');
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.style.outline = '2px solid #667eea';
            this.style.outlineOffset = '2px';
        });
        
        element.addEventListener('blur', function() {
            this.style.outline = '';
            this.style.outlineOffset = '';
        });
    });
}

// Initialize accessibility
initAccessibility();

// Performance optimization
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Debounced input validation
const debouncedValidation = debounce(function(input) {
    validateInput(input);
}, 300);

// Add debounced validation to inputs
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('input', function() {
        debouncedValidation(this);
    });
});

// Error handling functionality
function initErrorHandling() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error) {
        const errorMessage = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        
        let message = '';
        switch(error) {
            case 'empty_fields':
                message = 'Please fill in all required fields.';
                break;
            case 'invalid_credentials':
                message = 'Invalid username or password. Please try again.';
                break;
            case 'system_error':
                message = 'System error. Please try again later.';
                break;
            default:
                message = 'An error occurred. Please try again.';
        }
        
        errorText.textContent = message;
        errorMessage.style.display = 'block';
        
        // Auto-hide error after 5 seconds
        setTimeout(() => {
            errorMessage.style.display = 'none';
        }, 5000);
    }
}
