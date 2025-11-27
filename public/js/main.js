/**
 * Main JavaScript for Contact Management System
 */

// Confirm delete action
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Show loading spinner
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.id = 'loadingSpinner';
    spinner.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(spinner);
}

// Hide loading spinner
function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.remove();
    }
}

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Select all checkboxes
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = source.checked;
    });
    updateBulkActionsVisibility();
}

// Update bulk actions visibility based on selection
function updateBulkActionsVisibility() {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    const bulkActions = document.getElementById('bulkActions');
    if (bulkActions) {
        bulkActions.style.display = checkboxes.length > 0 ? 'block' : 'none';
    }
}

// Add event listeners to individual checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateBulkActionsVisibility);
    });
});

// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Export functionality
function exportContacts(format) {
    const selectedIds = [];
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    checkboxes.forEach(function(checkbox) {
        selectedIds.push(checkbox.value);
    });

    let url = '/public/index.php?page=contact-export&format=' + format;
    if (selectedIds.length > 0) {
        url += '&ids=' + selectedIds.join(',');
    }

    window.location.href = url;
}

// Preview email template
function previewEmail() {
    const subject = document.getElementById('subject').value;
    const body = document.getElementById('body').value;

    const previewWindow = window.open('', 'Email Preview', 'width=800,height=600');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Email Preview</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .subject { font-size: 18px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
                .body { margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="subject">Subject: ${subject}</div>
            <div class="body">${body}</div>
        </body>
        </html>
    `);
    previewWindow.document.close();
}

// Character counter for text fields
function updateCharCount(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    if (input && counter) {
        const currentLength = input.value.length;
        counter.textContent = `${currentLength} / ${maxLength}`;
        if (currentLength > maxLength) {
            counter.classList.add('text-danger');
        } else {
            counter.classList.remove('text-danger');
        }
    }
}
