<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT * FROM transfers 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$transfers = $stmt->fetchAll();

if (count($transfers) > 0): ?>
    <div class="list-group">
        <?php foreach ($transfers as $transfer): ?>
            <div class="list-group-item recent-transfer-item" onclick="loadTransfer(<?php echo $transfer['id']; ?>)">
                <div class="d-flex justify-content-between">
                    <strong><?php echo htmlspecialchars($transfer['recipient_name']); ?></strong>
                    <span class="badge bg-<?php echo $transfer['status'] == 'completed' ? 'success' : 'secondary'; ?>">
                        <?php echo $transfer['status']; ?>
                    </span>
                </div>
                <small class="text-muted">
                    <?php echo number_format($transfer['amount'], 2, ',', '.'); ?> EUR
                    - <?php echo date('d.m.Y', strtotime($transfer['created_at'])); ?>
                </small>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-muted text-center">Noch keine Überweisungen vorhanden</p>
<?php endif; ?>
