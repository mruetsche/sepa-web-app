<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        INSERT INTO transfers (
            user_id, sender_name, sender_iban, sender_bic, sender_bank,
            recipient_name, recipient_iban, recipient_bic,
            amount, purpose_line1, purpose_line2, reference_number,
            execution_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $_POST['sender_name'] ?? '',
        str_replace(' ', '', $_POST['sender_iban'] ?? ''),
        $_POST['sender_bic'] ?? '',
        $_POST['sender_bank'] ?? '',
        $_POST['recipient_name'] ?? '',
        str_replace(' ', '', $_POST['recipient_iban'] ?? ''),
        $_POST['recipient_bic'] ?? '',
        $_POST['amount'] ?? 0,
        $_POST['purpose_line1'] ?? '',
        $_POST['purpose_line2'] ?? '',
        $_POST['reference_number'] ?? '',
        $_POST['execution_date'] ?? date('Y-m-d'),
        $_POST['status'] ?? 'draft'
    ]);
    
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
