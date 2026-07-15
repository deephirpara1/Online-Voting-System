/**
 * VoteSecure — Shared JavaScript
 * Online Voting System
 */

// ─── Sidebar Toggle ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const toggleBtn = document.querySelector('.sidebar-toggle');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
});


// ─── SweetAlert2 Helpers ─────────────────────────────────────────────────────

/**
 * Show a success toast notification.
 */
function showToast(message, type = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: type,
        title: message
    });
}

/**
 * Show a confirmation dialog.
 * @returns {Promise<boolean>}
 */
function confirmAction(title, text, confirmText = 'Yes, proceed', icon = 'warning') {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
            popup: 'animate__animated animate__fadeInUp',
        }
    }).then((result) => result.isConfirmed);
}

/**
 * Show a delete confirmation with specific styling.
 */
function confirmDelete(itemName = 'this item') {
    return Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete <strong>${itemName}</strong>. This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash me-1"></i> Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => result.isConfirmed);
}


// ─── AJAX Form Submission ─────────────────────────────────────────────────────

/**
 * Submit a form via AJAX (FormData).
 * @param {string} url - The endpoint URL
 * @param {FormData} formData - The form data
 * @param {object} options - { onSuccess, onError, method }
 */
async function ajaxSubmit(url, formData, options = {}) {
    const method = options.method || 'POST';

    try {
        const response = await fetch(url, {
            method: method,
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            if (options.onSuccess) options.onSuccess(data);
        } else {
            if (options.onError) {
                options.onError(data);
            } else {
                showToast(data.message || 'An error occurred', 'error');
            }
        }

        return data;
    } catch (error) {
        console.error('AJAX Error:', error);
        showToast('Network error. Please try again.', 'error');
        if (options.onError) options.onError({ success: false, message: error.message });
        return null;
    }
}


// ─── Form Validation ─────────────────────────────────────────────────────────

/**
 * Add Bootstrap validation classes to a form.
 */
function initFormValidation(formSelector) {
    const form = document.querySelector(formSelector);
    if (!form) return;

    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
}

/**
 * Reset a form and remove validation classes.
 */
function resetForm(formSelector) {
    const form = document.querySelector(formSelector);
    if (!form) return;

    form.reset();
    form.classList.remove('was-validated');

    // Clear any custom error messages
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}


// ─── Loading Indicator ───────────────────────────────────────────────────────

/**
 * Show a loading overlay.
 */
function showLoading(message = 'Processing...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Hide the loading overlay.
 */
function hideLoading() {
    Swal.close();
}


// ─── Button Loading State ────────────────────────────────────────────────────

/**
 * Set a button to loading state.
 */
function setButtonLoading(btn, loading = true) {
    if (loading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...';
        btn.disabled = true;
    } else {
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        btn.disabled = false;
    }
}


// ─── Image Preview ───────────────────────────────────────────────────────────

/**
 * Preview an uploaded image before submission.
 * @param {HTMLInputElement} input - The file input element
 * @param {string} previewSelector - CSS selector for the preview <img> element
 */
function previewImage(input, previewSelector) {
    const preview = document.querySelector(previewSelector);
    if (!preview || !input.files || !input.files[0]) return;

    const file = input.files[0];

    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showToast('Please select a valid image file (JPG, PNG, GIF, WEBP)', 'error');
        input.value = '';
        return;
    }

    // Validate file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        showToast('Image must be less than 2MB', 'error');
        input.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}


// ─── DataTables Initialization Helper ────────────────────────────────────────

/**
 * Initialize a DataTable with custom defaults.
 * @param {string} selector - Table CSS selector
 * @param {object} options - Additional DataTables options
 * @returns {DataTable}
 */
function initDataTable(selector, options = {}) {
    const defaults = {
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        language: {
            search: '<i class="fas fa-search me-1"></i>',
            searchPlaceholder: 'Search...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            emptyTable: '<div class="empty-state py-3"><i class="fas fa-inbox d-block mb-2" style="font-size:2rem;color:#adb5bd;"></i>No data available</div>',
        },
        dom: '<"row mb-3"<"col-sm-6"l><"col-sm-6"f>>rt<"row mt-3"<"col-sm-6"i><"col-sm-6"p>>',
    };

    return $(selector).DataTable({ ...defaults, ...options });
}


// ─── Chart.js Theme Defaults ─────────────────────────────────────────────────

const chartColors = [
    '#667eea', '#764ba2', '#1cc88a', '#f6c23e',
    '#e74a3b', '#36b9cc', '#6610f2', '#fd7e14',
    '#20c997', '#6f42c1'
];

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                font: {
                    family: 'Inter',
                    size: 13
                },
                padding: 15,
                usePointStyle: true,
            }
        },
        tooltip: {
            backgroundColor: '#1e1e2d',
            titleFont: { family: 'Inter', size: 13, weight: '600' },
            bodyFont: { family: 'Inter', size: 12 },
            padding: 12,
            cornerRadius: 8,
        }
    }
};


// ─── Utility Functions ───────────────────────────────────────────────────────

/**
 * Debounce a function.
 */
function debounce(func, wait = 300) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

/**
 * Format a number with commas.
 */
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

/**
 * Copy text to clipboard.
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copied to clipboard!', 'success');
    } catch (err) {
        showToast('Failed to copy', 'error');
    }
}

/**
 * Auto-dismiss Bootstrap alerts after a delay.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
