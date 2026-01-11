<?php
session_start();
require_once 'config/config.php';
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            if (login($_POST['username'], $_POST['password'])) {
                header('Location: index.php');
                exit();
            } else {
                $error = 'Ungültige Anmeldedaten';
            }
        } elseif ($_POST['action'] === 'register') {
            if (register($_POST['username'], $_POST['email'], $_POST['password'])) {
                $success = 'Registrierung erfolgreich. Sie können sich jetzt anmelden.';
            } else {
                $error = 'Benutzername oder E-Mail bereits vergeben';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEPA Manager - Anmeldung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .login-body {
            padding: 30px;
        }
        .tab-content {
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-euro-sign"></i> SEPA Manager</h2>
            <p class="mb-0">Überweisungsverwaltung</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#login-tab" type="button">
                        Anmelden
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#register-tab" type="button">
                        Registrieren
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Login Tab -->
                <div class="tab-pane fade show active" id="login-tab">
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <label for="login-username" class="form-label">Benutzername</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="login-username" name="username" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="login-password" class="form-label">Passwort</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="login-password" name="password" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Anmelden
                            </button>
                        </div>
                    </form>
                    <div class="mt-3 text-center text-muted">
                        <small>Standard: admin / admin123</small>
                    </div>
                </div>
                
                <!-- Register Tab -->
                <div class="tab-pane fade" id="register-tab">
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="mb-3">
                            <label for="reg-username" class="form-label">Benutzername</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="reg-username" name="username" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reg-email" class="form-label">E-Mail</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="reg-email" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reg-password" class="form-label">Passwort</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="reg-password" name="password" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Registrieren
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
