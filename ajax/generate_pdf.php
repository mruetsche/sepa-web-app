<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

requireLogin();

// Get form data
$data = $_POST;

// Create new PDF document
class SEPA_PDF extends TCPDF {
    public function Header() {
        // No header
    }
    
    public function Footer() {
        // No footer
    }
}

// Create PDF
$pdf = new SEPA_PDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SEPA Manager');
$pdf->SetAuthor($_SESSION['username']);
$pdf->SetTitle('SEPA-Überweisung');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins (matching the original form)
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Draw the SEPA transfer form based on the uploaded template
// Main form area
$pdf->SetLineWidth(0.5);
$pdf->SetDrawColor(200, 200, 200);

// Title
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'SEPA-Überweisung/Zahlschein', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'Für Überweisungen in Deutschland und in andere EU-/EWR-Staaten in Euro.', 0, 1, 'C');
$pdf->Ln(5);

// Draw form boxes
$pdf->SetFont('helvetica', '', 9);

// Recipient section
$y = 40;
$pdf->SetXY(15, $y);
$pdf->Cell(0, 5, 'Angaben zum Zahlungsempfänger: Name, Vorname/Firma (max. 27 Stellen, bei maschineller Beschriftung max. 35 Stellen)', 0, 1);
$pdf->Rect(15, $y + 5, 180, 10);
$pdf->SetXY(17, $y + 7);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 5, strtoupper(substr($data['recipient_name'], 0, 35)), 0);

// Recipient IBAN
$y += 20;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y);
$pdf->Cell(20, 5, 'IBAN', 0);
$pdf->SetFont('helvetica', 'B', 11);
// Draw IBAN boxes
for ($i = 0; $i < 22; $i++) {
    $pdf->Rect(35 + ($i * 7), $y, 7, 7);
    if (isset($data['recipient_iban'])) {
        $iban = str_replace(' ', '', $data['recipient_iban']);
        if (isset($iban[$i])) {
            $pdf->SetXY(35 + ($i * 7) + 2, $y + 1);
            $pdf->Cell(3, 5, $iban[$i], 0, 0, 'C');
        }
    }
}

// Recipient BIC
$y += 10;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y);
$pdf->Cell(50, 5, 'BIC des Kreditinstituts/Zahlungsdienstleisters (8 oder 11 Stellen)', 0);
$pdf->Rect(15, $y + 5, 50, 7);
$pdf->SetXY(17, $y + 6);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, $data['recipient_bic'], 0);

// Amount
$y += 15;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(120, $y);
$pdf->Cell(30, 5, 'Betrag: Euro, Cent', 0);
$pdf->Rect(150, $y, 40, 10);
$pdf->SetXY(152, $y + 2);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(36, 5, number_format($data['amount'], 2, ',', '.'), 0, 0, 'R');

// Purpose / Reference
$y += 15;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y);
$pdf->Cell(0, 5, 'Kunden-Referenznummer - Verwendungszweck, ggf. Name und Anschrift des Zahlers', 0, 1);
$pdf->Rect(15, $y + 5, 180, 8);
$pdf->SetXY(17, $y + 6);
$pdf->SetFont('helvetica', '', 10);
$text1 = trim($data['reference_number'] . ' ' . $data['purpose_line1']);
$pdf->Cell(0, 5, substr($text1, 0, 35), 0);

// Second line of purpose
$y += 13;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y);
$pdf->Cell(0, 5, 'noch Verwendungszweck (insgesamt max. 2 Zeilen à 27 Stellen, bei maschineller Beschriftung max. 2 Zeilen à 35 Stellen)', 0, 1);
$pdf->Rect(15, $y + 5, 180, 8);
$pdf->SetXY(17, $y + 6);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, substr($data['purpose_line2'], 0, 35), 0);

// Sender section
$y += 20;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y);
$pdf->Cell(0, 5, 'Angaben zum Kontoinhaber/Zahler: Name, Vorname/Firma, Ort (max. 27 Stellen, keine Straßen- oder Postfachangaben)', 0, 1);
$pdf->Rect(15, $y + 5, 180, 10);
$pdf->SetXY(17, $y + 7);
$pdf->SetFont('helvetica', 'B', 11);
$sender = $data['sender_name'];
if (!empty($data['sender_city'])) {
    $sender .= ', ' . $data['sender_city'];
}
$pdf->Cell(0, 5, strtoupper(substr($sender, 0, 27)), 0);

// Sender IBAN
$y += 20;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y);
$pdf->Cell(20, 5, 'IBAN', 0);
$pdf->SetXY(25, $y);
$pdf->Cell(5, 7, 'D', 0, 0, 'C');
$pdf->SetXY(30, $y);
$pdf->Cell(5, 7, 'E', 0, 0, 'C');
// Draw IBAN boxes for sender
for ($i = 0; $i < 22; $i++) {
    $pdf->Rect(35 + ($i * 7), $y, 7, 7);
    if (isset($data['sender_iban'])) {
        $iban = str_replace(' ', '', $data['sender_iban']);
        if (isset($iban[$i])) {
            $pdf->SetXY(35 + ($i * 7) + 2, $y + 1);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(3, 5, $iban[$i], 0, 0, 'C');
        }
    }
}

// Date and Signature fields
$y += 15;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y);
$pdf->Cell(30, 5, 'Datum', 0);
$pdf->SetXY(100, $y);
$pdf->Cell(30, 5, 'Unterschrift(en)', 0);

$pdf->Rect(15, $y + 5, 40, 10);
$pdf->Rect(100, $y + 5, 95, 10);

// Add date
$pdf->SetXY(17, $y + 7);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, date('d.m.Y'), 0);

// Add bank info if available
if (!empty($data['sender_bank'])) {
    $y += 20;
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(15, $y);
    $pdf->Cell(50, 5, 'Name und Sitz des überweisenden Kreditinstituts', 0);
    $pdf->SetXY(100, $y);
    $pdf->Cell(20, 5, 'BIC', 0);
    
    $pdf->Rect(15, $y + 5, 80, 8);
    $pdf->Rect(100, $y + 5, 40, 8);
    
    $pdf->SetXY(17, $y + 6);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(75, 5, substr($data['sender_bank'], 0, 40), 0);
    
    $pdf->SetXY(102, $y + 6);
    $pdf->Cell(35, 5, $data['sender_bic'], 0);
}

// Add separator line
$pdf->SetDrawColor(255, 150, 0);
$pdf->SetLineWidth(1);
$pdf->Line(10, 180, 200, 180);

// Receipt section (Beleg für Kontoinhaber)
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(15, 185);
$pdf->Cell(0, 5, 'Beleg für Kontoinhaber', 0, 1);

$pdf->SetFont('helvetica', '', 9);
$y = 195;

// Compact receipt info
$pdf->SetXY(15, $y);
$pdf->Cell(30, 5, 'IBAN des Kontoinhabers:', 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, str_replace(' ', '', $data['sender_iban']), 0, 1);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y + 7);
$pdf->Cell(30, 5, 'Empfänger:', 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, $data['recipient_name'], 0, 1);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y + 14);
$pdf->Cell(30, 5, 'IBAN Empfänger:', 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, str_replace(' ', '', $data['recipient_iban']), 0, 1);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y + 21);
$pdf->Cell(30, 5, 'Betrag:', 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, number_format($data['amount'], 2, ',', '.') . ' EUR', 0, 1);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y + 28);
$pdf->Cell(30, 5, 'Verwendungszweck:', 0);
$pdf->SetFont('helvetica', '', 9);
$purpose = trim($data['purpose_line1'] . ' ' . $data['purpose_line2']);
$pdf->Cell(0, 5, substr($purpose, 0, 50), 0, 1);

$pdf->SetXY(15, $y + 35);
$pdf->Cell(30, 5, 'Datum:', 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, date('d.m.Y'), 0, 1);

// Output PDF
$pdf->Output('SEPA_Ueberweisung_' . date('YmdHis') . '.pdf', 'D');
?>
