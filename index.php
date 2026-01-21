<?php
session_start();
require_once 'config/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn() && !isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] !== 'login')) {
    header('Location: login.php');
    exit();
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEPA Überweisungsmanager</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-euro-sign"></i> SEPA Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-page="new-transfer">
                            <i class="fas fa-plus"></i> Neue Überweisung
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="transfers">
                            <i class="fas fa-list"></i> Überweisungen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="senders">
                            <i class="fas fa-university"></i> Meine Bankkonten
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="addresses">
                            <i class="fas fa-address-book"></i> Adressbuch
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username'] ?? 'Gast'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-page="profile">
                                <i class="fas fa-cog"></i> Einstellungen
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Abmelden
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container-fluid mt-4">
        <div id="app-content">
            <!-- Content will be loaded here via AJAX -->
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="d-none">
        <div class="spinner-overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Laden...</span>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Set global base URL for AJAX calls
        const BASE_URL = '<?php echo defined("BASE_URL") ? BASE_URL : ""; ?>';
        const AJAX_URL = '<?php echo defined("AJAX_URL") ? AJAX_URL : ""; ?>';
    </script>
    <script src="assets/js/url-fix.js"></script>
    <script src="assets/js/app.js"></script>
    
    <script>
    $(document).ready(function() {
        // Load initial page
        loadPage('new-transfer');
        
        // Navigation click handler
        $('[data-page]').on('click', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            
            // Update active state
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
            
            // Load page
            loadPage(page);
        });
    });
    
    function loadPage(page) {
        $('#loading-spinner').removeClass('d-none');
        
        $.ajax({
            url: 'ajax/load_page.php',
            type: 'GET',
            data: { page: page },
            success: function(response) {
                $('#app-content').html(response);
                $('#loading-spinner').addClass('d-none');
                
                // Initialize page-specific JavaScript
                if (typeof window['init_' + page.replace('-', '_')] === 'function') {
                    window['init_' + page.replace('-', '_')]();
                }
            },
            error: function() {
                $('#loading-spinner').addClass('d-none');
                showAlert('Fehler beim Laden der Seite', 'danger');
            }
        });
    }
    
    function showAlert(message, type = 'info') {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('body').append(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }
    </script>
</body>
</html>
