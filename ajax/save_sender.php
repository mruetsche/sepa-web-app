<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    $sender_id = $_POST['sender_id'] ?? '';
    
    // Prüfen ob es ein Update oder Insert ist
    if (!empty($sender_id)) {
        // Update existierendes Bankkonto
        
        // Falls als Standard gesetzt, alle anderen zurücksetzen
        if (isset($_POST['is_default']) && $_POST['is_default']) {
            $db->prepare("UPDATE senders SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }
        
        $stmt = $db->prepare("
            UPDATE senders SET 
                name = ?,
                city = ?,
                iban = ?,
                bic = ?,
                bank_name = ?,
                color = ?,
                is_default = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $_POST['name'] ?? '',
            $_POST['city'] ?? '',
            str_replace(' ', '', $_POST['iban'] ?? ''),
            strtoupper($_POST['bic'] ?? ''),
            $_POST['bank_name'] ?? '',
            $_POST['color'] ?? '#004494',
            isset($_POST['is_default']) ? 1 : 0,
            $sender_id,
            $user_id
        ]);
        
        echo json_encode(['success' => true, 'id' => $sender_id, 'action' => 'updated']);
        
    } else {
        // Neues Bankkonto anlegen
        
        // Falls als Standard gesetzt, alle anderen zurücksetzen
        if (isset($_POST['is_default']) && $_POST['is_default']) {
            $db->prepare("UPDATE senders SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }
        
        // Falls erstes Konto, automatisch als Standard setzen
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM senders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $count = $stmt->fetch()['count'];
        $isDefault = ($count == 0) ? 1 : (isset($_POST['is_default']) ? 1 : 0);
        
        $stmt = $db->prepare("
            INSERT INTO senders (
                user_id, name, city, iban, bic, bank_name, color, is_default
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $_POST['name'] ?? '',
            $_POST['city'] ?? '',
            str_replace(' ', '', $_POST['iban'] ?? ''),
            strtoupper($_POST['bic'] ?? ''),
            $_POST['bank_name'] ?? '',
            $_POST['color'] ?? '#004494',
            $isDefault
        ]);
        
        echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'action' => 'created']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
