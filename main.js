// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Close menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        }));
    }
});

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            showError(input, 'This field is required');
            isValid = false;
        } else {
            clearError(input);
        }

        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                showError(input, 'Please enter a valid email address');
                isValid = false;
            }
        }

        // Password validation
        if (input.type === 'password' && input.value && input.value.length < 6) {
            showError(input, 'Password must be at least 6 characters long');
            isValid = false;
        }
    });

    return isValid;
}

function showError(input, message) {
    clearError(input);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
    input.style.borderColor = '#e74c3c';
}

function clearError(input) {
    const errorMessage = input.parentNode.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
    input.style.borderColor = '#ddd';
}

// AJAX Form Submission
function submitForm(formId, action) {
    if (!validateForm(formId)) {
        return false;
    }

    const form = document.getElementById(formId);
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Disable submit button
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
    }

    fetch(action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'An error occurred. Please try again.');
        console.error('Error:', error);
    })
    .finally(() => {
        // Re-enable submit button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit';
        }
    });

    return false;
}

// Alert System
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    // Insert at the top of the main content
    const main = document.querySelector('main') || document.body;
    main.insertBefore(alertDiv, main.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Search Functionality
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length > 2) {
                fetch(`includes/search.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data, searchResults);
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
            } else {
                if (searchResults) {
                    searchResults.innerHTML = '';
                }
            }
        });
    }
}

function displaySearchResults(results, container) {
    if (!container) return;
    
    container.innerHTML = '';
    
    if (results.length === 0) {
        container.innerHTML = '<p>No results found</p>';
        return;
    }
    
    results.forEach(result => {
        const resultDiv = document.createElement('div');
        resultDiv.className = 'search-result';
        resultDiv.innerHTML = `
            <h4><a href="${result.url}">${result.title}</a></h4>
            <p>${result.description}</p>
        `;
        container.appendChild(resultDiv);
    });
}

// File Upload
function initFileUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validate file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('error', 'File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Show file preview for images
                if (file.type.startsWith('image/')) {
                    const preview = document.getElementById(this.id + '_preview');
                    if (preview) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                }
            }
        });
    });
}

// Dashboard Functions
function loadDashboardData() {
    fetch('includes/dashboard_data.php')
    .then(response => response.json())
    .then(data => {
        updateDashboardStats(data);
    })
    .catch(error => {
        console.error('Dashboard data error:', error);
    });
}

function updateDashboardStats(data) {
    // Update various dashboard elements
    const elements = {
        'total-projects': data.total_projects,
        'active-orders': data.active_orders,
        'total-earnings': data.total_earnings,
        'pending-payments': data.pending_payments
    };
    
    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = elements[id];
        }
    });
}

// Notification System
function checkNotifications() {
    fetch('includes/notifications.php')
    .then(response => response.json())
    .then(data => {
        updateNotificationBadge(data.unread_count);
        if (data.notifications.length > 0) {
            displayNotifications(data.notifications);
        }
    })
    .catch(error => {
        console.error('Notification error:', error);
    });
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}

function displayNotifications(notifications) {
    const container = document.getElementById('notifications-container');
    if (!container) return;
    
    container.innerHTML = '';
    
    notifications.forEach(notification => {
        const notifDiv = document.createElement('div');
        notifDiv.className = 'notification-item';
        notifDiv.innerHTML = `
            <div class="notification-content">
                <h5>${notification.title}</h5>
                <p>${notification.message}</p>
                <small>${notification.created_at}</small>
            </div>
            <button onclick="markAsRead(${notification.id})" class="btn-mark-read">×</button>
        `;
        container.appendChild(notifDiv);
    });
}

function markAsRead(notificationId) {
    fetch('includes/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            checkNotifications(); // Refresh notifications
        }
    });
}

// Initialize functions when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initSearch();
    initFileUpload();
    
    // Load dashboard data if on dashboard page
    if (document.querySelector('.dashboard')) {
        loadDashboardData();
        checkNotifications();
        
        // Refresh dashboard data every 30 seconds
        setInterval(loadDashboardData, 30000);
        setInterval(checkNotifications, 60000);
    }
});

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-ET', {
        style: 'currency',
        currency: 'ETB',
        currencyDisplay: 'code'
    }).format(amount).replace('ETB', 'Br');
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('success', 'Copied to clipboard!');
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
    });
}