<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$_POST['email'], $user['id']]);
        $message = 'Profil aktualisiert';
    } elseif (isset($_POST['change_password'])) {
        if (password_verify($_POST['old_password'], $user['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $newHash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newHash, $user['id']]);
                $message = 'Passwort geändert';
            } else {
                $message = 'Passwörter stimmen nicht überein';
            }
        } else {
            $message = 'Altes Passwort ist falsch';
        }
    }
    $user = getCurrentUser(); // Reload user data
}
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-user"></i> Profil-Einstellungen</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Benutzername</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-Mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Registriert seit</label>
                        <input type="text" class="form-control" value="<?php echo date('d.m.Y', strtotime($user['created_at'])); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Letzter Login</label>
                        <input type="text" class="form-control" value="<?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Profil speichern
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h4><i class="fas fa-key"></i> Passwort ändern</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Altes Passwort</label>
                        <input type="password" class="form-control" id="old_password" name="old_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Neues Passwort</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-lock"></i> Passwort ändern
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-info text-white">
                <h4><i class="fas fa-chart-bar"></i> Statistik</h4>
            </div>
            <div class="card-body">
                <?php
                // Get statistics
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM transfers WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $transferCount = $stmt->fetch()['count'];
                
                $stmt = $db->prepare("SELECT SUM(amount) as total FROM transfers WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $totalAmount = $stmt->fetch()['total'] ?? 0;
                
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM addresses WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $addressCount = $stmt->fetch()['count'];
                ?>
                
                <dl class="row">
                    <dt class="col-sm-6">Überweisungen:</dt>
                    <dd class="col-sm-6"><?php echo $transferCount; ?></dd>
                    
                    <dt class="col-sm-6">Gesamtsumme:</dt>
                    <dd class="col-sm-6"><?php echo number_format($totalAmount, 2, ',', '.'); ?> EUR</dd>
                    
                    <dt class="col-sm-6">Gespeicherte Adressen:</dt>
                    <dd class="col-sm-6"><?php echo $addressCount; ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
