<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all senders
$stmt = $db->prepare("
    SELECT * FROM senders 
    WHERE user_id = ? 
    ORDER BY is_default DESC, name ASC
");
$stmt->execute([$user_id]);
$senders = $stmt->fetchAll();

// Verfügbare Farben für Konten
$colors = [
    '#004494' => 'Blau',
    '#28a745' => 'Grün',
    '#dc3545' => 'Rot',
    '#ff9600' => 'Orange',
    '#6f42c1' => 'Lila',
    '#20c997' => 'Türkis',
    '#e83e8c' => 'Pink',
    '#6c757d' => 'Grau'
];
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="alert alert-info d-flex align-items-center" style="font-size: 1.1rem;">
            <i class="fas fa-info-circle fa-2x me-3"></i>
            <div>
                <strong>Meine Bankkonten</strong><br>
                Hier können Sie Ihre eigenen Bankkonten speichern, von denen Sie Überweisungen tätigen möchten.
                Das <strong>Standard-Konto</strong> wird automatisch bei neuen Überweisungen vorausgewählt.
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Linke Spalte: Vorhandene Konten -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-university"></i> Meine Bankkonten</h4>
            </div>
            <div class="card-body">
                <?php if (count($senders) > 0): ?>
                    <div class="row">
                        <?php foreach ($senders as $sender): ?>
                            <div class="col-12 mb-3">
                                <div class="sender-card" style="border-left: 5px solid <?php echo htmlspecialchars($sender['color'] ?? '#004494'); ?>">
                                    <?php if ($sender['is_default']): ?>
                                        <span class="default-badge" title="Standard-Konto">
                                            <i class="fas fa-check-circle"></i> Standard
                                        </span>
                                    <?php endif; ?>
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-1" style="color: <?php echo htmlspecialchars($sender['color'] ?? '#004494'); ?>">
                                                <i class="fas fa-piggy-bank me-2"></i>
                                                <?php echo htmlspecialchars($sender['name']); ?>
                                            </h5>
                                            <?php if ($sender['city']): ?>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-map-marker-alt"></i> 
                                                    <?php echo htmlspecialchars($sender['city']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="sender-details mt-2">
                                                <p class="mb-1">
                                                    <strong>IBAN:</strong> 
                                                    <span class="iban-display"><?php echo formatIBAN($sender['iban']); ?></span>
                                                </p>
                                                <p class="mb-1">
                                                    <strong>BIC:</strong> 
                                                    <?php echo htmlspecialchars($sender['bic']); ?>
                                                </p>
                                                <?php if ($sender['bank_name']): ?>
                                                    <p class="mb-0">
                                                        <strong>Bank:</strong> 
                                                        <?php echo htmlspecialchars($sender['bank_name']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 text-end">
                                            <div class="btn-group-vertical w-100" style="max-width: 180px;">
                                                <?php if (!$sender['is_default']): ?>
                                                    <button class="btn btn-success btn-lg mb-2" 
                                                            onclick="setDefaultSender(<?php echo $sender['id']; ?>)"
                                                            title="Als Standard setzen">
                                                        <i class="fas fa-check"></i> Als Standard
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-primary btn-lg mb-2" 
                                                        onclick="editSender(<?php echo $sender['id']; ?>)"
                                                        title="Bearbeiten">
                                                    <i class="fas fa-edit"></i> Bearbeiten
                                                </button>
                                                <button class="btn btn-outline-danger btn-lg" 
                                                        onclick="deleteSender(<?php echo $sender['id']; ?>)"
                                                        title="Löschen">
                                                    <i class="fas fa-trash"></i> Löschen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center" style="font-size: 1.1rem;">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>
                        <strong>Noch keine Bankkonten gespeichert.</strong><br>
                        Fügen Sie rechts Ihr erstes Bankkonto hinzu, um Überweisungen schneller auszufüllen.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Rechte Spalte: Neues Konto hinzufügen -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Neues Bankkonto hinzufügen</h5>
            </div>
            <div class="card-body">
                <form id="add-sender-form">
                    <input type="hidden" id="sender_id" name="sender_id" value="">
                    
                    <div class="mb-3">
                        <label for="sender_name" class="form-label fs-5">
                            <i class="fas fa-user"></i> Name / Kontoinhaber <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="sender_name" name="name" 
                               placeholder="z.B. Max Mustermann" maxlength="27" required>
                        <small class="text-muted">So wie er auf dem Überweisungsträger erscheinen soll (max. 27 Zeichen)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sender_city" class="form-label fs-5">
                            <i class="fas fa-map-marker-alt"></i> Ort
                        </label>
                        <input type="text" class="form-control form-control-lg" id="sender_city" name="city" 
                               placeholder="z.B. Berlin" maxlength="27">
                    </div>
                    
                    <div class="mb-3">
                        <label for="sender_iban" class="form-label fs-5">
                            <i class="fas fa-credit-card"></i> IBAN <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg iban-input" id="sender_iban" name="iban" 
                               placeholder="DE00 0000 0000 0000 0000 00" required>
                        <small class="text-muted">Ihre Kontonummer im IBAN-Format</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sender_bic" class="form-label fs-5">
                            <i class="fas fa-building"></i> BIC <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg bic-input" id="sender_bic" name="bic" 
                               placeholder="XXXXXXXX" maxlength="11" required>
                        <small class="text-muted">Die BIC/SWIFT Ihrer Bank (8 oder 11 Zeichen)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sender_bank_name" class="form-label fs-5">
                            <i class="fas fa-university"></i> Name der Bank
                        </label>
                        <input type="text" class="form-control form-control-lg" id="sender_bank_name" name="bank_name" 
                               placeholder="z.B. Sparkasse Berlin">
                    </div>
                    
                    <div class="mb-3">
                        <label for="sender_color" class="form-label fs-5">
                            <i class="fas fa-palette"></i> Farbe zur Unterscheidung
                        </label>
                        <div class="color-picker-grid">
                            <?php foreach ($colors as $hex => $colorName): ?>
                                <label class="color-option">
                                    <input type="radio" name="color" value="<?php echo $hex; ?>" 
                                           <?php echo $hex === '#004494' ? 'checked' : ''; ?>>
                                    <span class="color-swatch" style="background-color: <?php echo $hex; ?>" 
                                          title="<?php echo $colorName; ?>"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="sender_default" name="is_default" 
                               style="width: 1.5em; height: 1.5em;">
                        <label class="form-check-label fs-5 ms-2" for="sender_default">
                            <i class="fas fa-star text-warning"></i> Als Standard-Konto festlegen
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Bankkonto speichern
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="resetSenderForm()">
                            <i class="fas fa-undo"></i> Formular leeren
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Hilfsfunktion für IBAN-Formatierung
function formatIBAN($iban) {
    $iban = str_replace(' ', '', $iban);
    return implode(' ', str_split($iban, 4));
}
?>

<style>
/* Absender-Karten Styling */
.sender-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    background: white;
    position: relative;
    transition: all 0.3s ease;
}

.sender-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.sender-card .default-badge {
    position: absolute;
    top: 10px;
    right: 15px;
    background: #28a745;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.sender-details {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 5px;
    font-size: 1rem;
}

.iban-display {
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
    font-size: 1.05rem;
}

/* Farb-Auswahl */
.color-picker-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 10px 0;
}

.color-option {
    cursor: pointer;
}

.color-option input {
    display: none;
}

.color-swatch {
    display: block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid transparent;
    transition: all 0.2s;
}

.color-option input:checked + .color-swatch {
    border-color: #333;
    transform: scale(1.2);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.color-swatch:hover {
    transform: scale(1.1);
}

/* Große, lesbare Buttons */
.btn-lg {
    padding: 12px 24px;
    font-size: 1.1rem;
}

/* Große Formulareingaben */
.form-control-lg {
    font-size: 1.1rem;
    padding: 12px 16px;
}

.form-label.fs-5 {
    font-weight: 600;
    margin-bottom: 8px;
}
</style>

<script>
$(document).ready(function() {
    // IBAN Formatierung
    $('.iban-input').on('input', function() {
        let value = $(this).val().replace(/\s/g, '').toUpperCase();
        let formatted = '';
        
        for (let i = 0; i < value.length; i += 4) {
            if (i > 0) formatted += ' ';
            formatted += value.substr(i, 4);
        }
        
        $(this).val(formatted);
        
        if (value.length >= 15) {
            if (validateIBAN(value)) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        }
    });
    
    // BIC Formatierung
    $('.bic-input').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Formular absenden
    $('#add-sender-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const senderId = $('#sender_id').val();
        
        $.ajax({
            url: '../ajax/save_sender.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAlert(senderId ? 'Bankkonto aktualisiert!' : 'Bankkonto gespeichert!', 'success');
                    loadPage('senders');
                } else {
                    showAlert('Fehler: ' + response.message, 'danger');
                }
            },
            error: function() {
                showAlert('Fehler beim Speichern', 'danger');
            }
        });
    });
});

function validateIBAN(iban) {
    iban = iban.replace(/\s/g, '');
    
    if (iban.length < 15 || iban.length > 34) return false;
    if (!/^[A-Z]{2}[0-9]{2}/.test(iban)) return false;
    
    const rearranged = iban.substring(4) + iban.substring(0, 4);
    
    let numericIBAN = '';
    for (let i = 0; i < rearranged.length; i++) {
        const char = rearranged[i];
        if (/[A-Z]/.test(char)) {
            numericIBAN += (char.charCodeAt(0) - 55).toString();
        } else {
            numericIBAN += char;
        }
    }
    
    let remainder = 0;
    for (let i = 0; i < numericIBAN.length; i++) {
        remainder = (remainder * 10 + parseInt(numericIBAN[i])) % 97;
    }
    
    return remainder === 1;
}

function deleteSender(id) {
    confirmAction('Möchten Sie dieses Bankkonto wirklich löschen?', function() {
        $.ajax({
            url: '../ajax/delete_sender.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showAlert('Bankkonto gelöscht', 'success');
                    loadPage('senders');
                } else {
                    showAlert('Fehler: ' + response.message, 'danger');
                }
            }
        });
    });
}

function setDefaultSender(id) {
    $.ajax({
        url: '../ajax/set_default_sender.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                showAlert('Standard-Konto festgelegt', 'success');
                loadPage('senders');
            }
        }
    });
}

function editSender(id) {
    $.ajax({
        url: '../ajax/get_sender.php',
        type: 'GET',
        data: { id: id },
        success: function(data) {
            $('#sender_id').val(data.id);
            $('#sender_name').val(data.name);
            $('#sender_city').val(data.city);
            $('#sender_iban').val(data.iban);
            $('#sender_bic').val(data.bic);
            $('#sender_bank_name').val(data.bank_name);
            $('#sender_default').prop('checked', data.is_default == 1);
            $('input[name="color"][value="' + data.color + '"]').prop('checked', true);
            
            // Scroll zum Formular
            $('html, body').animate({
                scrollTop: $('#add-sender-form').offset().top - 100
            }, 500);
            
            showAlert('Bankkonto zum Bearbeiten geladen', 'info');
        }
    });
}

function resetSenderForm() {
    $('#add-sender-form')[0].reset();
    $('#sender_id').val('');
    $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
    $('input[name="color"][value="#004494"]').prop('checked', true);
}
</script>
