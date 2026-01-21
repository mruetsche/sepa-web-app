<?php
/**
 * SEPA-Überweisungsträger PDF-Generator
 * 
 * Druckt die Daten exakt positioniert auf einen SEPA-Überweisungsträger.
 * Das Hintergrundbild kann für Testzwecke aktiviert werden.
 * 
 * Basierend auf Standard-Vordruck Art.-Nr. ZV 570/ZV 572
 */

session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

requireLogin();

// ============================================================================
// KONFIGURATION
// ============================================================================

// WICHTIG: Für Tests auf TRUE setzen, für echten Druck auf FALSE
define('SHOW_BACKGROUND', true);  // Hintergrundbild anzeigen?
define('SHOW_DEBUG_GRID', false); // Debug-Raster anzeigen?

// Pfad zum Hintergrundbild
define('BACKGROUND_IMAGE', __DIR__ . '/../assets/sepa-vorlage.jpg');

// Globale Offsets für Feinabstimmung (in mm)
define('OFFSET_X', 0);
define('OFFSET_Y', 0);

// IBAN-Kästchen Maße (angepasst an Vordruck)
define('IBAN_BOX_WIDTH', 4.23);   // Breite eines IBAN-Kästchens
define('IBAN_BOX_HEIGHT', 5.0);   // Höhe eines IBAN-Kästchens

// Schriftgrößen
define('FONT_SIZE_NORMAL', 10);
define('FONT_SIZE_IBAN', 10);
define('FONT_SIZE_AMOUNT', 10);
define('FONT_SIZE_SMALL', 8);

// ============================================================================
// FELDPOSITIONEN - Exakt angepasst an das Formular (in mm)
// Gemessen vom linken/oberen Rand der A4-Seite
// ============================================================================

$positions = [
    // =========================================================================
    // TEIL 1: SEPA-ÜBERWEISUNG/ZAHLSCHEIN (oberer Hauptteil)
    // =========================================================================
    
    // Name und Sitz des überweisenden Kreditinstituts (Zeile bei Y≈35mm)
    'sender_bank' => ['x' => 5, 'y' => 93, 'width' => 90],
    'sender_bic_top' => ['x' => 70, 'y' => 93, 'width' => 45],
    
    // Empfänger Name - in den orangenen Kästchen (Y≈47mm)
    'recipient_name' => ['x' => 8, 'y' => 105.5, 'width' => 148],
    
    // Empfänger IBAN - Kästchenreihe (Y≈59mm)
    'recipient_iban' => ['x' => 8, 'y' => 113, 'boxes' => 22],
    
    // Empfänger BIC (Y≈71mm)
    'recipient_bic' => ['x' => 8, 'y' => 123, 'width' => 60],
    
    // Betrag Euro, Cent - rechtsbündig (Y≈82mm)
    'amount' => ['x' => 6, 'y' => 131,5, 'width' => 135, 'align' => 'R'],
    
    // Verwendungszweck Zeile 1 (Y≈93mm)
    'purpose_line1' => ['x' => 8, 'y' => 140, 'width' => 148],
    
    // Verwendungszweck Zeile 2 (Y≈104mm)
    'purpose_line2' => ['x' => 8, 'y' => 140.5, 'width' => 148],
    
    // Absender/Zahler: Name, Ort (Y≈116mm)
    'sender_name' => ['x' => 8, 'y' => 156.0, 'width' => 148],
    
    // Absender IBAN - nach vorgedrucktem "D E" (Y≈127mm)
    // X-Position: Nach "D" und "E" (ca. 2 Kästchen = 8.5mm vom Rand)
    'sender_iban' => ['x' => 18, 'y' => 165.0, 'boxes' => 20, 'skip_prefix' => 2],
    
    // Datum (Y≈140mm)
    'date' => ['x' => 8, 'y' => 180, 'width' => 25],
    
    // =========================================================================
    // TEIL 2: BELEG FÜR KONTOINHABER (rechte Spalte, X≈162mm)
    // =========================================================================
    
    // IBAN des Kontoinhabers
    'receipt_iban' => ['x' => 162, 'y' => 95.2, 'width' => 44, 'fontsize' => 7],
    
    // Kontoinhaber
    'receipt_sender' => ['x' => 162, 'y' => 110, 'width' => 44, 'fontsize' => 8],
    
    // Zahlungsempfänger
    'receipt_recipient' => ['x' => 162, 'y' => 125, 'width' => 44, 'fontsize' => 8],
    
    // Verwendungszweck
    'receipt_purpose' => ['x' => 162, 'y' => 145.2, 'width' => 44, 'fontsize' => 7],
    
    // Datum
    'receipt_date' => ['x' => 162, 'y' => 155.2, 'width' => 44, 'fontsize' => 8],
    
    // Betrag: Euro, Cent
    'receipt_amount' => ['x' => 162, 'y' => 165.2, 'width' => 44, 'fontsize' => 9],
    
    // =========================================================================
    // TEIL 3: BELEG FÜR KONTOINHABER/ZAHLER-QUITTUNG (unterer Teil)
    // =========================================================================
    
    // Name und Sitz des überweisenden Kreditinstituts (Y≈168mm)
    'quittung_bank' => ['x' => 5, 'y' => 198, 'width' => 90],
    'quittung_bank_bic' => ['x' => 70, 'y' => 198, 'width' => 45],
    
    // Empfänger Name (Y≈182mm)
    'quittung_recipient' => ['x' => 8, 'y' => 212, 'width' => 148],
    
    // Empfänger IBAN (Y≈194mm)
    'quittung_iban' => ['x' => 8, 'y' => 220, 'boxes' => 22],
    
    // Empfänger BIC (Y≈206mm)
    'quittung_bic' => ['x' => 8, 'y' => 230, 'width' => 60],
    
    // Betrag (Y≈217mm)
    'quittung_amount' => ['x' => 95.6, 'y' => 235.9, 'width' => 43, 'align' => 'R'],
    
    // Verwendungszweck Zeile 1 (Y≈229mm)
    'quittung_purpose1' => ['x' => 8, 'y' => 236, 'width' => 148],
    
    // Verwendungszweck Zeile 2 (Y≈240mm)
    'quittung_purpose2' => ['x' => 8, 'y' => 240, 'width' => 148],
    
    // Auftraggeber Name + Ort (Y≈251mm)
    'quittung_sender' => ['x' => 8, 'y' => 263.0, 'width' => 148],
    
    // Auftraggeber IBAN (Y≈263mm)
    'quittung_sender_iban' => ['x' => 8, 'y' => 270, 'boxes' => 22],
];

// ============================================================================
// PDF-Klasse ohne Header/Footer
// ============================================================================

class SEPA_PDF extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

// ============================================================================
// Hilfsfunktionen
// ============================================================================

/**
 * IBAN ohne Leerzeichen, Großbuchstaben
 */
function formatIBAN($iban) {
    return str_replace(' ', '', strtoupper(trim($iban)));
}

/**
 * Betrag im deutschen Format
 */
function formatAmount($amount) {
    return number_format((float)$amount, 2, ',', '.');
}

/**
 * Text kürzen
 */
function truncateText($text, $maxlen) {
    return mb_substr(trim($text), 0, $maxlen);
}

/**
 * IBAN in Kästchen drucken
 */
function printIBANBoxes($pdf, $iban, $startX, $y, $numBoxes, $skipChars = 0) {
    $iban = formatIBAN($iban);
    
    // Zeichen überspringen (z.B. "DE" wenn vorgedruckt)
    if ($skipChars > 0) {
        $iban = substr($iban, $skipChars);
    }
    
    $pdf->SetFont('courier', 'B', FONT_SIZE_IBAN);
    
    for ($i = 0; $i < $numBoxes && $i < strlen($iban); $i++) {
        $x = $startX + ($i * IBAN_BOX_WIDTH) + OFFSET_X;
        $yPos = $y + OFFSET_Y;
        
        // Zeichen mittig im Kästchen
        $pdf->SetXY($x + 0.3, $yPos + 0.8);
        $pdf->Cell(IBAN_BOX_WIDTH - 0.5, IBAN_BOX_HEIGHT - 1, $iban[$i], 0, 0, 'C');
    }
}

/**
 * Text an Position drucken
 */
function printText($pdf, $text, $x, $y, $width = 0, $align = 'L', $fontsize = FONT_SIZE_NORMAL, $bold = false) {
    $x += OFFSET_X;
    $y += OFFSET_Y;
    
    $style = $bold ? 'B' : '';
    $pdf->SetFont('helvetica', $style, $fontsize);
    $pdf->SetXY($x, $y);
    $pdf->Cell($width, 5, $text, 0, 0, $align);
}

/**
 * Mehrzeiliger Text
 */
function printMultiText($pdf, $text, $x, $y, $width, $fontsize = FONT_SIZE_SMALL) {
    $x += OFFSET_X;
    $y += OFFSET_Y;
    
    $pdf->SetFont('helvetica', '', $fontsize);
    $pdf->SetXY($x, $y);
    $pdf->MultiCell($width, 3.5, $text, 0, 'L');
}

/**
 * Debug-Raster zeichnen
 */
function drawDebugGrid($pdf) {
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.1);
    
    // Vertikale Linien alle 10mm
    for ($x = 0; $x <= 210; $x += 10) {
        $pdf->Line($x, 0, $x, 297);
        $pdf->SetFont('helvetica', '', 5);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetXY($x + 0.3, 1);
        $pdf->Cell(8, 3, $x, 0, 0, 'L');
    }
    
    // Horizontale Linien alle 10mm
    for ($y = 0; $y <= 297; $y += 10) {
        $pdf->Line(0, $y, 210, $y);
        $pdf->SetFont('helvetica', '', 5);
        $pdf->SetXY(0.5, $y + 0.3);
        $pdf->Cell(8, 3, $y, 0, 0, 'L');
    }
    
    $pdf->SetTextColor(0, 0, 0);
}

// ============================================================================
// Formulardaten verarbeiten
// ============================================================================

$data = $_POST;

// Daten vorbereiten und formatieren
$recipientName = strtoupper(truncateText($data['recipient_name'] ?? '', 35));
$recipientIBAN = formatIBAN($data['recipient_iban'] ?? '');
$recipientBIC = strtoupper(trim($data['recipient_bic'] ?? ''));

$amount = formatAmount($data['amount'] ?? 0);

$refNumber = trim($data['reference_number'] ?? '');
$purpose1 = trim($data['purpose_line1'] ?? '');
$purpose2 = trim($data['purpose_line2'] ?? '');

// Verwendungszweck kombinieren
$purposeLine1 = truncateText($refNumber . ($refNumber && $purpose1 ? ' ' : '') . $purpose1, 35);
$purposeLine2 = truncateText($purpose2, 35);
$purposeFull = trim($purposeLine1 . ' ' . $purposeLine2);

$senderName = strtoupper(truncateText($data['sender_name'] ?? '', 27));
$senderCity = strtoupper(trim($data['sender_city'] ?? ''));
$senderIBAN = formatIBAN($data['sender_iban'] ?? '');
$senderBIC = strtoupper(trim($data['sender_bic'] ?? ''));
$senderBank = trim($data['sender_bank'] ?? '');

// Sender Name + Ort kombinieren
$senderFull = $senderName;
if (!empty($senderCity)) {
    $senderFull = truncateText($senderName . ', ' . $senderCity, 27);
}

$currentDate = date('d.m.Y');

// IBAN formatiert für Beleg (mit Leerzeichen)
$senderIBANFormatted = implode(' ', str_split($senderIBAN, 4));
$recipientIBANFormatted = implode(' ', str_split($recipientIBAN, 4));

// ============================================================================
// PDF erstellen
// ============================================================================

$pdf = new SEPA_PDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SEPA Manager');
$pdf->SetAuthor($_SESSION['username'] ?? 'System');
$pdf->SetTitle('SEPA-Überweisung');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

$pdf->AddPage();

// ============================================================================
// Hintergrundbild (nur für Tests)
// ============================================================================

if (SHOW_BACKGROUND && file_exists(BACKGROUND_IMAGE)) {
    $pdf->Image(BACKGROUND_IMAGE, 0, 0, 210, 297, 'JPG', '', '', false, 300, '', false, false, 0);
}

// Debug-Raster
if (SHOW_DEBUG_GRID) {
    drawDebugGrid($pdf);
}

// Textfarbe: Schwarz
$pdf->SetTextColor(0, 0, 0);

// ============================================================================
// TEIL 1: SEPA-ÜBERWEISUNG/ZAHLSCHEIN
// ============================================================================

// Bank Name + BIC (oberste Zeile im Formular)
if (!empty($senderBank)) {
    printText($pdf, truncateText($senderBank, 50),
        $positions['sender_bank']['x'],
        $positions['sender_bank']['y'],
        $positions['sender_bank']['width']);
}

if (!empty($senderBIC)) {
    printText($pdf, $senderBIC,
        $positions['sender_bic_top']['x'],
        $positions['sender_bic_top']['y'],
        $positions['sender_bic_top']['width']);
}

// Empfänger Name (in den orangenen Kästchen)
printText($pdf, $recipientName,
    $positions['recipient_name']['x'],
    $positions['recipient_name']['y'],
    $positions['recipient_name']['width'],
    'L', FONT_SIZE_NORMAL, false);

// Empfänger IBAN
printIBANBoxes($pdf, $recipientIBAN,
    $positions['recipient_iban']['x'],
    $positions['recipient_iban']['y'],
    $positions['recipient_iban']['boxes']);

// Empfänger BIC
printText($pdf, $recipientBIC,
    $positions['recipient_bic']['x'],
    $positions['recipient_bic']['y'],
    $positions['recipient_bic']['width'],
    'L', FONT_SIZE_NORMAL, false);

// Betrag
printText($pdf, $amount,
    $positions['amount']['x'],
    $positions['amount']['y'],
    $positions['amount']['width'],
    $positions['amount']['align'],
    FONT_SIZE_AMOUNT, true);

// Verwendungszweck Zeile 1
printText($pdf, $purposeLine1,
    $positions['purpose_line1']['x'],
    $positions['purpose_line1']['y'],
    $positions['purpose_line1']['width']);

// Verwendungszweck Zeile 2
printText($pdf, $purposeLine2,
    $positions['purpose_line2']['x'],
    $positions['purpose_line2']['y'],
    $positions['purpose_line2']['width']);

// Auftraggeber Name + Ort
printText($pdf, $senderFull,
    $positions['sender_name']['x'],
    $positions['sender_name']['y'],
    $positions['sender_name']['width'],
    'L', FONT_SIZE_NORMAL, false);

// Auftraggeber IBAN (ohne "DE", da vorgedruckt)
if (substr($senderIBAN, 0, 2) === 'DE') {
    printIBANBoxes($pdf, $senderIBAN,
        $positions['sender_iban']['x'],
        $positions['sender_iban']['y'],
        $positions['sender_iban']['boxes'],
        2);  // "DE" überspringen
} else {
    // Nicht-deutsche IBAN: komplett drucken (weiter links anfangen)
    printIBANBoxes($pdf, $senderIBAN,
        $positions['sender_iban']['x'] - 8.5,
        $positions['sender_iban']['y'],
        22);
}

// Datum
printText($pdf, $currentDate,
    $positions['date']['x'],
    $positions['date']['y'],
    $positions['date']['width']);

// ============================================================================
// TEIL 2: BELEG FÜR KONTOINHABER (rechte Spalte)
// ============================================================================

// IBAN des Kontoinhabers (Absender)
printMultiText($pdf, $senderIBANFormatted,
    $positions['receipt_iban']['x'],
    $positions['receipt_iban']['y'],
    $positions['receipt_iban']['width'],
    $positions['receipt_iban']['fontsize']);

// Kontoinhaber (Absender)
printText($pdf, truncateText($senderName, 28),
    $positions['receipt_sender']['x'],
    $positions['receipt_sender']['y'],
    $positions['receipt_sender']['width'],
    'L', $positions['receipt_sender']['fontsize']);

// Zahlungsempfänger
printText($pdf, truncateText($recipientName, 28),
    $positions['receipt_recipient']['x'],
    $positions['receipt_recipient']['y'],
    $positions['receipt_recipient']['width'],
    'L', $positions['receipt_recipient']['fontsize']);

// Verwendungszweck
printMultiText($pdf, truncateText($purposeFull, 80),
    $positions['receipt_purpose']['x'],
    $positions['receipt_purpose']['y'],
    $positions['receipt_purpose']['width'],
    $positions['receipt_purpose']['fontsize']);

// Datum
printText($pdf, $currentDate,
    $positions['receipt_date']['x'],
    $positions['receipt_date']['y'],
    $positions['receipt_date']['width'],
    'L', $positions['receipt_date']['fontsize']);

// Betrag
printText($pdf, $amount . ' EUR',
    $positions['receipt_amount']['x'],
    $positions['receipt_amount']['y'],
    $positions['receipt_amount']['width'],
    'L', $positions['receipt_amount']['fontsize'], true);

// ============================================================================
// TEIL 3: ZAHLER-QUITTUNG (unterer Teil)
// ============================================================================

// Bank Name
if (!empty($senderBank)) {
    printText($pdf, truncateText($senderBank, 50),
        $positions['quittung_bank']['x'],
        $positions['quittung_bank']['y'],
        $positions['quittung_bank']['width']);
}

// Bank BIC
if (!empty($senderBIC)) {
    printText($pdf, $senderBIC,
        $positions['quittung_bank_bic']['x'],
        $positions['quittung_bank_bic']['y'],
        $positions['quittung_bank_bic']['width']);
}

// Empfänger Name
printText($pdf, $recipientName,
    $positions['quittung_recipient']['x'],
    $positions['quittung_recipient']['y'],
    $positions['quittung_recipient']['width'],
    'L', FONT_SIZE_NORMAL, false);

// Empfänger IBAN
printIBANBoxes($pdf, $recipientIBAN,
    $positions['quittung_iban']['x'],
    $positions['quittung_iban']['y'],
    $positions['quittung_iban']['boxes']);

// Empfänger BIC
printText($pdf, $recipientBIC,
    $positions['quittung_bic']['x'],
    $positions['quittung_bic']['y'],
    $positions['quittung_bic']['width'],
    'L', FONT_SIZE_NORMAL, false);

// Betrag
printText($pdf, $amount,
    $positions['quittung_amount']['x'],
    $positions['quittung_amount']['y'],
    $positions['quittung_amount']['width'],
    $positions['quittung_amount']['align'],
    FONT_SIZE_AMOUNT, true);

// Verwendungszweck Zeile 1
printText($pdf, $purposeLine1,
    $positions['quittung_purpose1']['x'],
    $positions['quittung_purpose1']['y'],
    $positions['quittung_purpose1']['width']);

// Verwendungszweck Zeile 2
printText($pdf, $purposeLine2,
    $positions['quittung_purpose2']['x'],
    $positions['quittung_purpose2']['y'],
    $positions['quittung_purpose2']['width']);

// Auftraggeber Name + Ort
printText($pdf, $senderFull,
    $positions['quittung_sender']['x'],
    $positions['quittung_sender']['y'],
    $positions['quittung_sender']['width'],
    'L', FONT_SIZE_NORMAL, false);

// Auftraggeber IBAN
printIBANBoxes($pdf, $senderIBAN,
    $positions['quittung_sender_iban']['x'],
    $positions['quittung_sender_iban']['y'],
    $positions['quittung_sender_iban']['boxes']);

// ============================================================================
// PDF ausgeben
// ============================================================================

$filename = 'SEPA_Ueberweisung_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');
?>