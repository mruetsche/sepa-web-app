<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all addresses
$stmt = $db->prepare("
    SELECT * FROM addresses 
    WHERE user_id = ? 
    ORDER BY is_favorite DESC, name ASC
");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-address-book"></i> Adressbuch</h4>
            </div>
            <div class="card-body">
                <?php if (count($addresses) > 0): ?>
                    <div class="row">
                        <?php foreach ($addresses as $address): ?>
                            <div class="col-md-6 mb-3">
                                <div class="address-card">
                                    <?php if ($address['is_favorite']): ?>
                                        <span class="favorite-badge" title="Favorit">
                                            <i class="fas fa-star"></i>
                                        </span>
                                    <?php endif; ?>
                                    <h5><?php echo htmlspecialchars($address['name']); ?></h5>
                                    <?php if ($address['company']): ?>
                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($address['company']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1">
                                        <small>
                                            <strong>IBAN:</strong> <?php 
                                                $iban = $address['iban'];
                                                echo substr($iban, 0, 4) . ' **** **** ' . substr($iban, -4); 
                                            ?><br>
                                            <strong>BIC:</strong> <?php echo htmlspecialchars($address['bic']); ?>
                                        </small>
                                    </p>
                                    <div class="btn-group btn-group-sm mt-2">
                                        <button class="btn btn-outline-primary" onclick="editAddress(<?php echo $address['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="toggleFavorite(<?php echo $address['id']; ?>)">
                                            <i class="fas fa-star"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteAddress(<?php echo $address['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Noch keine Adressen gespeichert.
                        Fügen Sie Empfänger beim Erstellen einer Überweisung hinzu.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-plus"></i> Neue Adresse hinzufügen</h5>
            </div>
            <div class="card-body">
                <form id="add-address-form">
                    <div class="mb-3">
                        <label for="new_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_company" class="form-label">Firma</label>
                        <input type="text" class="form-control" id="new_company" name="company">
                    </div>
                    <div class="mb-3">
                        <label for="new_iban" class="form-label">IBAN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control iban-input" id="new_iban" name="iban" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_bic" class="form-label">BIC <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bic-input" id="new_bic" name="bic" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_bank_name" class="form-label">Bank Name</label>
                        <input type="text" class="form-control" id="new_bank_name" name="bank_name">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="new_favorite" name="is_favorite">
                        <label class="form-check-label" for="new_favorite">
                            Als Favorit markieren
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-save"></i> Speichern
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // IBAN formatting
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
    
    // BIC formatting
    $('.bic-input').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Add address form
    $('#add-address-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: '../ajax/save_address.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAlert('Adresse gespeichert', 'success');
                    loadPage('addresses');
                } else {
                    showAlert('Fehler: ' + response.message, 'danger');
                }
            }
        });
    });
});

function deleteAddress(id) {
    confirmAction('Möchten Sie diese Adresse wirklich löschen?', function() {
        $.ajax({
            url: '../ajax/delete_address.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showAlert('Adresse gelöscht', 'success');
                    loadPage('addresses');
                }
            }
        });
    });
}

function toggleFavorite(id) {
    $.ajax({
        url: '../ajax/toggle_favorite.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                loadPage('addresses');
            }
        }
    });
}

function editAddress(id) {
    // Load address data and show in form
    $.ajax({
        url: '../ajax/get_address.php',
        type: 'GET',
        data: { id: id },
        success: function(data) {
            $('#new_name').val(data.name);
            $('#new_company').val(data.company);
            $('#new_iban').val(data.iban);
            $('#new_bic').val(data.bic);
            $('#new_bank_name').val(data.bank_name);
            $('#new_favorite').prop('checked', data.is_favorite == 1);
            
            // Change form to update mode
            $('#add-address-form').attr('data-update-id', id);
            showAlert('Adresse zum Bearbeiten geladen', 'info');
        }
    });
}
</script>
