<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    $id = $_GET['id'] ?? 0;
    
    $stmt = $db->prepare("SELECT * FROM senders WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $sender = $stmt->fetch();
    
    if ($sender) {
        // IBAN formatiert zurückgeben
        $sender['iban'] = implode(' ', str_split($sender['iban'], 4));
        echo json_encode($sender);
    } else {
        echo json_encode(['error' => 'Nicht gefunden']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
