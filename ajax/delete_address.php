<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$address_id = $_POST['id'] ?? 0;

try {
    $stmt = $db->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$address_id, $user_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
