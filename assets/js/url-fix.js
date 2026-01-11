// SEPA WebApp URL Configuration Helper
// This file ensures AJAX calls work correctly regardless of installation directory

// Auto-detect if BASE_URL not set by PHP
if (typeof BASE_URL === "undefined") {
    const pathParts = window.location.pathname.split("/");
    pathParts.pop(); // Remove filename
    if (pathParts[pathParts.length - 1] === "pages" || 
        pathParts[pathParts.length - 1] === "ajax") {
        pathParts.pop();
    }
    window.BASE_URL = window.location.origin + pathParts.join("/");
    window.AJAX_URL = window.BASE_URL + "/ajax";
}

// Fix jQuery AJAX calls
if (typeof jQuery !== "undefined") {
    const originalAjax = jQuery.ajax;
    jQuery.ajax = function(options) {
        if (options.url) {
            // Fix relative URLs
            if (options.url.startsWith("../ajax/")) {
                options.url = AJAX_URL + "/" + options.url.substring(8);
            } else if (options.url.startsWith("ajax/")) {
                options.url = AJAX_URL + "/" + options.url.substring(5);
            }
        }
        return originalAjax.call(this, options);
    };
}
