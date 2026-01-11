// SEPA WebApp JavaScript

// Global functions
window.showAlert = function(message, type = 'info') {
    const alertId = 'alert-' + Date.now();
    const alert = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('body').append(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('#' + alertId).alert('close');
    }, 5000);
};

// IBAN Validation
window.validateIBAN = function(iban) {
    iban = iban.replace(/\s/g, '').toUpperCase();
    
    if (iban.length < 15 || iban.length > 34) return false;
    if (!/^[A-Z]{2}[0-9]{2}/.test(iban)) return false;
    
    // Move first 4 chars to end
    const rearranged = iban.substring(4) + iban.substring(0, 4);
    
    // Replace letters with numbers (A=10, B=11, ..., Z=35)
    let numericIBAN = '';
    for (let i = 0; i < rearranged.length; i++) {
        const char = rearranged[i];
        if (/[A-Z]/.test(char)) {
            numericIBAN += (char.charCodeAt(0) - 55).toString();
        } else {
            numericIBAN += char;
        }
    }
    
    // Modulo 97 check
    let remainder = 0;
    for (let i = 0; i < numericIBAN.length; i++) {
        remainder = (remainder * 10 + parseInt(numericIBAN[i])) % 97;
    }
    
    return remainder === 1;
};

// BIC Validation
window.validateBIC = function(bic) {
    bic = bic.replace(/\s/g, '').toUpperCase();
    
    // BIC is 8 or 11 characters
    if (bic.length !== 8 && bic.length !== 11) return false;
    
    // Format: 4 letters (bank code) + 2 letters (country) + 2 letters/digits (location)
    // Optional: 3 letters/digits (branch code)
    const bicRegex = /^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/;
    
    return bicRegex.test(bic);
};

// Format currency
window.formatCurrency = function(amount) {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
};

// Format date
window.formatDate = function(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('de-DE').format(date);
};

// Character counter for inputs
window.initCharCounter = function(selector, maxLength) {
    $(selector).each(function() {
        const $input = $(this);
        const $counter = $('<span class="char-counter"></span>');
        
        $input.parent().append($counter);
        
        const updateCounter = function() {
            const length = $input.val().length;
            $counter.text(length + '/' + maxLength);
            
            if (length > maxLength) {
                $counter.addClass('danger').removeClass('warning');
            } else if (length > maxLength * 0.8) {
                $counter.addClass('warning').removeClass('danger');
            } else {
                $counter.removeClass('warning danger');
            }
        };
        
        $input.on('input', updateCounter);
        updateCounter();
    });
};

// Initialize tooltips
$(document).ready(function() {
    // Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// AJAX error handler
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.error('AJAX Error:', thrownError);
    if (jqxhr.status === 401) {
        window.location.href = 'login.php';
    }
});

// Form validation helper
window.validateForm = function(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
};

// Export functions for CSV
window.exportToCSV = function(data, filename) {
    let csv = '';
    
    // Headers
    const headers = Object.keys(data[0]);
    csv += headers.join(',') + '\n';
    
    // Data rows
    data.forEach(row => {
        const values = headers.map(header => {
            const value = row[header] || '';
            return '"' + value.toString().replace(/"/g, '""') + '"';
        });
        csv += values.join(',') + '\n';
    });
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Print function
window.printElement = function(elementId) {
    const printContents = document.getElementById(elementId).innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    
    // Reload to restore JavaScript bindings
    location.reload();
};

// Session timeout warning
let sessionTimeout;
let warningTimeout;

window.resetSessionTimer = function() {
    clearTimeout(sessionTimeout);
    clearTimeout(warningTimeout);
    
    // Show warning after 50 minutes
    warningTimeout = setTimeout(function() {
        showAlert('Ihre Sitzung läuft in 10 Minuten ab. Bitte speichern Sie Ihre Arbeit.', 'warning');
    }, 50 * 60 * 1000);
    
    // Logout after 60 minutes
    sessionTimeout = setTimeout(function() {
        window.location.href = 'logout.php';
    }, 60 * 60 * 1000);
};

// Reset timer on user activity
$(document).on('click keypress', function() {
    resetSessionTimer();
});

// Initialize session timer
resetSessionTimer();

// Mobile menu toggle
window.toggleMobileMenu = function() {
    $('#navbarNav').toggleClass('show');
};

// Confirm dialog helper
window.confirmAction = function(message, callback) {
    if (confirm(message)) {
        callback();
    }
};

// Loading state helper
window.setLoading = function(element, loading) {
    const $el = $(element);
    if (loading) {
        $el.prop('disabled', true);
        $el.html('<span class="spinner-border spinner-border-sm me-2"></span>Laden...');
    } else {
        $el.prop('disabled', false);
        $el.html($el.data('original-text'));
    }
};

// Save original button text
$(document).ready(function() {
    $('button[type="submit"]').each(function() {
        $(this).data('original-text', $(this).html());
    });
});
