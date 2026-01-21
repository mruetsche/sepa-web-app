<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    $id = $_POST['id'] ?? 0;
    
    // Sicherstellen, dass nur eigene Daten gelöscht werden
    $stmt = $db->prepare("DELETE FROM senders WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
