<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all transfers
$stmt = $db->prepare("
    SELECT * FROM transfers 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$transfers = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4><i class="fas fa-list"></i> Überweisungshistorie</h4>
    </div>
    <div class="card-body">
        <?php if (count($transfers) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Empfänger</th>
                            <th>IBAN</th>
                            <th>Betrag</th>
                            <th>Verwendungszweck</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transfers as $transfer): ?>
                            <tr>
                                <td><?php echo date('d.m.Y', strtotime($transfer['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($transfer['recipient_name']); ?></td>
                                <td>
                                    <small><?php 
                                        $iban = $transfer['recipient_iban'];
                                        echo substr($iban, 0, 4) . '...' . substr($iban, -4); 
                                    ?></small>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo number_format($transfer['amount'], 2, ',', '.'); ?> €</strong>
                                </td>
                                <td>
                                    <small><?php 
                                        $purpose = trim($transfer['purpose_line1'] . ' ' . $transfer['purpose_line2']);
                                        echo htmlspecialchars(substr($purpose, 0, 30));
                                        if (strlen($purpose) > 30) echo '...';
                                    ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $transfer['status'] == 'completed' ? 'success' : 
                                            ($transfer['status'] == 'draft' ? 'secondary' : 'info');
                                    ?>">
                                        <?php echo $transfer['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info" onclick="viewTransfer(<?php echo $transfer['id']; ?>)" title="Anzeigen">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary" onclick="downloadTransferPDF(<?php echo $transfer['id']; ?>)" title="PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <button class="btn btn-danger" onclick="deleteTransfer(<?php echo $transfer['id']; ?>)" title="Löschen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <button class="btn btn-success" onclick="exportTransfers()">
                    <i class="fas fa-file-csv"></i> Als CSV exportieren
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Noch keine Überweisungen vorhanden.
                <a href="#" onclick="loadPage('new-transfer')" class="alert-link">Jetzt erste Überweisung erstellen</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Transfer Details Modal -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Überweisungsdetails</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transferDetails">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-primary" onclick="printTransferDetails()">
                    <i class="fas fa-print"></i> Drucken
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function viewTransfer(id) {
    $.ajax({
        url: AJAX_URL + '/get_transfer.php',
        type: 'GET',
        data: { id: id },
        success: function(data) {
            let html = `
                <dl class="row">
                    <dt class="col-sm-3">Absender:</dt>
                    <dd class="col-sm-9">${data.sender_name}</dd>
                    
                    <dt class="col-sm-3">Absender IBAN:</dt>
                    <dd class="col-sm-9">${data.sender_iban}</dd>
                    
                    <dt class="col-sm-3">Absender BIC:</dt>
                    <dd class="col-sm-9">${data.sender_bic}</dd>
                    
                    <dt class="col-sm-3">Empfänger:</dt>
                    <dd class="col-sm-9">${data.recipient_name}</dd>
                    
                    <dt class="col-sm-3">Empfänger IBAN:</dt>
                    <dd class="col-sm-9">${data.recipient_iban}</dd>
                    
                    <dt class="col-sm-3">Empfänger BIC:</dt>
                    <dd class="col-sm-9">${data.recipient_bic}</dd>
                    
                    <dt class="col-sm-3">Betrag:</dt>
                    <dd class="col-sm-9"><strong>${formatCurrency(data.amount)}</strong></dd>
                    
                    <dt class="col-sm-3">Verwendungszweck:</dt>
                    <dd class="col-sm-9">${data.purpose_line1}<br>${data.purpose_line2 || ''}</dd>
                    
                    <dt class="col-sm-3">Referenznummer:</dt>
                    <dd class="col-sm-9">${data.reference_number || '-'}</dd>
                    
                    <dt class="col-sm-3">Ausführungsdatum:</dt>
                    <dd class="col-sm-9">${formatDate(data.execution_date)}</dd>
                    
                    <dt class="col-sm-3">Erstellt am:</dt>
                    <dd class="col-sm-9">${formatDate(data.created_at)}</dd>
                </dl>
            `;
            
            $('#transferDetails').html(html);
            $('#transferModal').modal('show');
        }
    });
}

function downloadTransferPDF(id) {
    window.location.href = AJAX_URL + '/download_transfer_pdf.php?id=' + id;
}

function deleteTransfer(id) {
    confirmAction('Möchten Sie diese Überweisung wirklich löschen?', function() {
        $.ajax({
            url: AJAX_URL + '/delete_transfer.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showAlert('Überweisung gelöscht', 'success');
                    loadPage('transfers'); // Reload page
                } else {
                    showAlert('Fehler beim Löschen', 'danger');
                }
            }
        });
    });
}

function exportTransfers() {
    window.location.href = AJAX_URL + '/export_transfers.php';
}

function printTransferDetails() {
    window.print();
}
</script>
