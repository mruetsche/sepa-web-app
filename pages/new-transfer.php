<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Load saved addresses for quick selection
$stmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_favorite DESC, name ASC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

// Load saved senders (own bank accounts)
$stmt = $db->prepare("SELECT * FROM senders WHERE user_id = ? ORDER BY is_default DESC, name ASC");
$stmt->execute([$user_id]);
$senders = $stmt->fetchAll();

// Get default sender
$defaultSender = null;
foreach ($senders as $sender) {
    if ($sender['is_default']) {
        $defaultSender = $sender;
        break;
    }
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-exchange-alt"></i> SEPA-Überweisung</h4>
            </div>
            <div class="card-body">
                <form id="transfer-form">
                    <!-- Sender Section -->
                    <fieldset class="border rounded p-3 mb-4">
                        <legend class="w-auto px-2"><small>Absender (Kontoinhaber)</small></legend>
                        
                        <?php if (count($senders) > 0): ?>
                        <!-- Schnellauswahl aus gespeicherten Konten -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-university"></i> Aus meinen Bankkonten wählen:
                            </label>
                            <div class="sender-quick-select">
                                <?php foreach ($senders as $index => $sender): ?>
                                <button type="button" 
                                        class="sender-select-btn <?php echo $sender['is_default'] ? 'active' : ''; ?>" 
                                        data-sender-id="<?php echo $sender['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($sender['name']); ?>"
                                        data-city="<?php echo htmlspecialchars($sender['city'] ?? ''); ?>"
                                        data-iban="<?php echo htmlspecialchars($sender['iban']); ?>"
                                        data-bic="<?php echo htmlspecialchars($sender['bic']); ?>"
                                        data-bank="<?php echo htmlspecialchars($sender['bank_name'] ?? ''); ?>"
                                        style="border-left: 5px solid <?php echo $sender['color'] ?? '#004494'; ?>">
                                    <span class="sender-btn-name"><?php echo htmlspecialchars($sender['name']); ?></span>
                                    <span class="sender-btn-iban"><?php 
                                        $iban = $sender['iban'];
                                        echo substr($iban, 0, 4) . ' •••• ' . substr($iban, -4); 
                                    ?></span>
                                    <?php if ($sender['is_default']): ?>
                                    <span class="sender-btn-badge">Standard</span>
                                    <?php endif; ?>
                                </button>
                                <?php endforeach; ?>
                                
                                <button type="button" class="sender-select-btn sender-manual-btn" data-sender-id="manual">
                                    <span class="sender-btn-name"><i class="fas fa-edit"></i> Manuell eingeben</span>
                                    <span class="sender-btn-iban">Eigene Daten eintragen</span>
                                </button>
                            </div>
                            
                            <div class="text-end mt-2">
                                <a href="#" onclick="loadPage('senders'); return false;" class="small">
                                    <i class="fas fa-plus-circle"></i> Weiteres Bankkonto hinzufügen
                                </a>
                            </div>
                        </div>
                        
                        <hr>
                        <?php else: ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-lightbulb"></i> 
                            <strong>Tipp:</strong> Speichern Sie Ihre Bankkonten unter 
                            <a href="#" onclick="loadPage('senders'); return false;">Meine Bankkonten</a>, 
                            um sie hier schnell auswählen zu können!
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sender_name" class="form-label">Name, Vorname/Firma <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sender_name" name="sender_name" maxlength="27" required
                                       value="<?php echo $defaultSender ? htmlspecialchars($defaultSender['name']) : ''; ?>">
                                <small class="text-muted">Max. 27 Zeichen</small>
                            </div>
                            <div class="col-md-6">
                                <label for="sender_city" class="form-label">Ort</label>
                                <input type="text" class="form-control" id="sender_city" name="sender_city" maxlength="27"
                                       value="<?php echo $defaultSender ? htmlspecialchars($defaultSender['city'] ?? '') : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sender_iban" class="form-label">IBAN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control iban-input" id="sender_iban" name="sender_iban" 
                                       placeholder="DE00 0000 0000 0000 0000 00" maxlength="34" required
                                       value="<?php echo $defaultSender ? implode(' ', str_split($defaultSender['iban'], 4)) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="sender_bic" class="form-label">BIC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bic-input" id="sender_bic" name="sender_bic" 
                                       placeholder="XXXXXXXX" maxlength="11" required
                                       value="<?php echo $defaultSender ? htmlspecialchars($defaultSender['bic']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sender_bank" class="form-label">Name und Sitz des Kreditinstituts</label>
                            <input type="text" class="form-control" id="sender_bank" name="sender_bank"
                                   value="<?php echo $defaultSender ? htmlspecialchars($defaultSender['bank_name'] ?? '') : ''; ?>">
                        </div>
                    </fieldset>
                    
                    <!-- Recipient Section -->
                    <fieldset class="border rounded p-3 mb-4">
                        <legend class="w-auto px-2"><small>Empfänger</small></legend>
                        
                        <div class="mb-3">
                            <label for="recipient_select" class="form-label">Aus Adressbuch wählen</label>
                            <select class="form-select" id="recipient_select">
                                <option value="">-- Neue Adresse eingeben --</option>
                                <?php foreach ($addresses as $address): ?>
                                <option value="<?php echo $address['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($address['name']); ?>"
                                        data-iban="<?php echo htmlspecialchars($address['iban']); ?>"
                                        data-bic="<?php echo htmlspecialchars($address['bic']); ?>">
                                    <?php echo htmlspecialchars($address['name']); ?> 
                                    (<?php echo substr($address['iban'], 0, 4) . '...' . substr($address['iban'], -4); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="recipient_name" class="form-label">Name, Vorname/Firma <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" maxlength="27" required>
                            <small class="text-muted">Max. 27 Zeichen (bei maschineller Beschriftung max. 35 Zeichen)</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="recipient_iban" class="form-label">IBAN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control iban-input" id="recipient_iban" name="recipient_iban" 
                                       placeholder="DE00 0000 0000 0000 0000 00" maxlength="34" required>
                            </div>
                            <div class="col-md-6">
                                <label for="recipient_bic" class="form-label">BIC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bic-input" id="recipient_bic" name="recipient_bic" 
                                       placeholder="XXXXXXXX" maxlength="11" required>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="save_recipient">
                            <label class="form-check-label" for="save_recipient">
                                Empfänger im Adressbuch speichern
                            </label>
                        </div>
                    </fieldset>
                    
                    <!-- Transfer Details -->
                    <fieldset class="border rounded p-3 mb-4">
                        <legend class="w-auto px-2"><small>Überweisungsdetails</small></legend>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Betrag <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           step="0.01" min="0.01" required>
                                    <span class="input-group-text">EUR</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="execution_date" class="form-label">Ausführungsdatum</label>
                                <input type="date" class="form-control" id="execution_date" name="execution_date" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reference_number" class="form-label">Kunden-Referenznummer</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number" maxlength="27">
                        </div>
                        
                        <div class="mb-3">
                            <label for="purpose_line1" class="form-label">Verwendungszweck (Zeile 1)</label>
                            <input type="text" class="form-control" id="purpose_line1" name="purpose_line1" maxlength="27">
                            <small class="text-muted">Max. 27 Zeichen (bei maschineller Beschriftung max. 35 Zeichen)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="purpose_line2" class="form-label">Verwendungszweck (Zeile 2)</label>
                            <input type="text" class="form-control" id="purpose_line2" name="purpose_line2" maxlength="27">
                            <small class="text-muted">Max. 27 Zeichen (bei maschineller Beschriftung max. 35 Zeichen)</small>
                        </div>
                    </fieldset>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Zurücksetzen
                        </button>
                        <div>
                            <button type="button" class="btn btn-info me-2" onclick="saveTransfer('draft')">
                                <i class="fas fa-save"></i> Als Entwurf speichern
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-pdf"></i> PDF erstellen
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Side Panel -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5><i class="fas fa-info-circle"></i> Hinweise</h5>
            </div>
            <div class="card-body">
                <h6>SEPA-Überweisung</h6>
                <ul class="small">
                    <li>Für Überweisungen in Deutschland und andere EU-/EWR-Staaten in Euro</li>
                    <li>IBAN: Internationale Bankkontonummer (22 Zeichen für Deutschland)</li>
                    <li>BIC: Bank Identifier Code (8 oder 11 Zeichen)</li>
                    <li>Maximale Zeichenanzahl beachten!</li>
                </ul>
                
                <h6 class="mt-3">Wichtige Limits</h6>
                <ul class="small">
                    <li>Name/Firma: max. 27 Zeichen</li>
                    <li>Verwendungszweck: 2 Zeilen à 27 Zeichen</li>
                    <li>Bei maschineller Verarbeitung: bis zu 35 Zeichen möglich</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-history"></i> Letzte Überweisungen</h5>
            </div>
            <div class="card-body">
                <div id="recent-transfers">
                    <!-- Will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles für Absender-Auswahl -->
<style>
.sender-quick-select {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.sender-select-btn {
    flex: 1 1 calc(50% - 10px);
    min-width: 200px;
    padding: 12px 15px;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s ease;
    position: relative;
}

.sender-select-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.sender-select-btn.active {
    background: #e3f2fd;
    border-color: #2196f3;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
}

.sender-select-btn .sender-btn-name {
    display: block;
    font-weight: 600;
    font-size: 1rem;
    color: #333;
}

.sender-select-btn .sender-btn-iban {
    display: block;
    font-size: 0.85rem;
    color: #666;
    font-family: 'Courier New', monospace;
}

.sender-select-btn .sender-btn-badge {
    position: absolute;
    top: 5px;
    right: 8px;
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

.sender-manual-btn {
    background: #fff3cd !important;
    border-color: #ffc107 !important;
}

.sender-manual-btn:hover {
    background: #ffecb5 !important;
}

.sender-manual-btn.active {
    background: #fff3cd !important;
    border-color: #ff9800 !important;
    box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3) !important;
}
</style>

<script>
// Initialize form
$(document).ready(function() {
    // Absender-Auswahl Handler
    $('.sender-select-btn').on('click', function() {
        // Alle Buttons deaktivieren
        $('.sender-select-btn').removeClass('active');
        // Aktuellen Button aktivieren
        $(this).addClass('active');
        
        const senderId = $(this).data('sender-id');
        
        if (senderId === 'manual') {
            // Manuelle Eingabe - Felder leeren
            $('#sender_name').val('').prop('readonly', false);
            $('#sender_city').val('').prop('readonly', false);
            $('#sender_iban').val('').removeClass('is-valid is-invalid').prop('readonly', false);
            $('#sender_bic').val('').prop('readonly', false);
            $('#sender_bank').val('').prop('readonly', false);
        } else {
            // Gespeichertes Konto auswählen
            const $btn = $(this);
            const iban = $btn.data('iban');
            const formattedIban = iban.match(/.{1,4}/g).join(' ');
            
            $('#sender_name').val($btn.data('name'));
            $('#sender_city').val($btn.data('city'));
            $('#sender_iban').val(formattedIban).addClass('is-valid');
            $('#sender_bic').val($btn.data('bic'));
            $('#sender_bank').val($btn.data('bank'));
        }
    });
    
    // Format IBAN input
    $('.iban-input').on('input', function() {
        let value = $(this).val().replace(/\s/g, '').toUpperCase();
        let formatted = '';
        
        for (let i = 0; i < value.length; i += 4) {
            if (i > 0) formatted += ' ';
            formatted += value.substr(i, 4);
        }
        
        $(this).val(formatted);
        
        // Validate IBAN
        if (value.length >= 15) {
            if (validateIBAN(value)) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        }
    });
    
    // Format BIC input
    $('.bic-input').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Recipient selection
    $('#recipient_select').on('change', function() {
        const option = $(this).find(':selected');
        if (option.val()) {
            $('#recipient_name').val(option.data('name'));
            $('#recipient_iban').val(option.data('iban')).trigger('input');
            $('#recipient_bic').val(option.data('bic'));
        } else {
            $('#recipient_name, #recipient_iban, #recipient_bic').val('');
        }
    });
    
    // Form submission
    $('#transfer-form').on('submit', function(e) {
        e.preventDefault();
        generatePDF();
    });
    
    // Load recent transfers
    loadRecentTransfers();
    
    // Standard-Konto als aktiv markieren (falls vorhanden)
    const defaultBtn = $('.sender-select-btn[data-sender-id]:not([data-sender-id="manual"])').first();
    if (defaultBtn.length && defaultBtn.find('.sender-btn-badge').length) {
        defaultBtn.addClass('active');
    }
});

function validateIBAN(iban) {
    iban = iban.replace(/\s/g, '');
    
    if (iban.length < 15 || iban.length > 34) return false;
    if (!/^[A-Z]{2}[0-9]{2}/.test(iban)) return false;
    
    // Move first 4 chars to end
    const rearranged = iban.substring(4) + iban.substring(0, 4);
    
    // Replace letters with numbers
    let numericIBAN = '';
    for (let i = 0; i < rearranged.length; i++) {
        const char = rearranged[i];
        if (/[A-Z]/.test(char)) {
            numericIBAN += (char.charCodeAt(0) - 55).toString();
        } else {
            numericIBAN += char;
        }
    }
    
    // Modulo 97 check
    let remainder = 0;
    for (let i = 0; i < numericIBAN.length; i++) {
        remainder = (remainder * 10 + parseInt(numericIBAN[i])) % 97;
    }
    
    return remainder === 1;
}

function saveTransfer(status) {
    const formData = $('#transfer-form').serialize() + '&status=' + status;
    
    if ($('#save_recipient').is(':checked')) {
        // Save recipient to address book
        saveRecipientToAddressBook();
    }
    
    $.ajax({
        url: '../ajax/save_transfer.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                showAlert('Überweisung gespeichert', 'success');
                loadRecentTransfers();
            } else {
                showAlert('Fehler: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Fehler beim Speichern', 'danger');
        }
    });
}

function generatePDF() {
    const formData = $('#transfer-form').serialize();
    
    // First save the transfer
    saveTransfer('completed');
    
    // Then generate PDF
    $.ajax({
        url: '../ajax/generate_pdf.php',
        type: 'POST',
        data: formData,
        xhrFields: {
            responseType: 'blob'
        },
        success: function(blob) {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'SEPA_Ueberweisung_' + new Date().getTime() + '.pdf';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            
            showAlert('PDF wurde erstellt und heruntergeladen', 'success');
        },
        error: function() {
            showAlert('Fehler bei der PDF-Erstellung', 'danger');
        }
    });
}

function loadRecentTransfers() {
    $.ajax({
        url: '../ajax/get_recent_transfers.php',
        type: 'GET',
        success: function(response) {
            $('#recent-transfers').html(response);
        }
    });
}

function loadTransfer(id) {
    $.ajax({
        url: '../ajax/get_transfer.php',
        type: 'GET',
        data: { id: id },
        success: function(data) {
            // Fill form with transfer data
            $('#sender_name').val(data.sender_name);
            $('#sender_iban').val(data.sender_iban);
            $('#sender_bic').val(data.sender_bic);
            $('#sender_bank').val(data.sender_bank);
            $('#recipient_name').val(data.recipient_name);
            $('#recipient_iban').val(data.recipient_iban);
            $('#recipient_bic').val(data.recipient_bic);
            $('#amount').val(data.amount);
            $('#purpose_line1').val(data.purpose_line1);
            $('#purpose_line2').val(data.purpose_line2);
            $('#reference_number').val(data.reference_number);
            
            showAlert('Überweisung geladen', 'info');
        }
    });
}

function resetForm() {
    $('#transfer-form')[0].reset();
    $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
}

function saveRecipientToAddressBook() {
    const data = {
        name: $('#recipient_name').val(),
        iban: $('#recipient_iban').val(),
        bic: $('#recipient_bic').val()
    };
    
    $.ajax({
        url: '../ajax/save_address.php',
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                // Reload recipient select
                location.reload();
            }
        }
    });
}
</script>
