<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

$page = $_GET['page'] ?? 'new-transfer';

$allowed_pages = [
    'new-transfer' => '../pages/new-transfer.php',
    'transfers' => '../pages/transfers.php',
    'addresses' => '../pages/addresses.php',
    'senders' => '../pages/senders.php',
    'profile' => '../pages/profile.php'
];

if (isset($allowed_pages[$page])) {
    include $allowed_pages[$page];
} else {
    echo '<div class="alert alert-danger">Seite nicht gefunden</div>';
}
?>
