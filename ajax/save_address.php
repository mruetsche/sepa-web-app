<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        INSERT INTO addresses (
            user_id, name, iban, bic, bank_name
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $_POST['name'] ?? '',
        str_replace(' ', '', $_POST['iban'] ?? ''),
        $_POST['bic'] ?? '',
        $_POST['bank_name'] ?? ''
    ]);
    
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
