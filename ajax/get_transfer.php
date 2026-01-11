<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$transfer_id = $_GET['id'] ?? 0;

$stmt = $db->prepare("
    SELECT * FROM transfers 
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$transfer_id, $user_id]);
$transfer = $stmt->fetch();

if ($transfer) {
    echo json_encode($transfer);
} else {
    echo json_encode(['error' => 'Transfer not found']);
}
?>
