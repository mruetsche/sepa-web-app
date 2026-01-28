<?php
/**
 * SEPA-Überweisungsträger PDF-Download
 *
 * Lädt eine gespeicherte Überweisung aus der Datenbank und generiert ein PDF.
 */

session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

requireLogin();

// ============================================================================
// KONFIGURATION
// ============================================================================

define('SHOW_BACKGROUND', true);
define('SHOW_DEBUG_GRID', false);
define('BACKGROUND_IMAGE', __DIR__ . '/../assets/sepa-vorlage.jpg');
define('OFFSET_X', 0);
define('OFFSET_Y', 0);
define('IBAN_BOX_WIDTH', 4.23);
define('IBAN_BOX_HEIGHT', 5.0);
define('FONT_SIZE_NORMAL', 10);
define('FONT_SIZE_IBAN', 10);
define('FONT_SIZE_AMOUNT', 10);
define('FONT_SIZE_SMALL', 8);

// ============================================================================
// FELDPOSITIONEN
// ============================================================================

$positions = [
    'sender_bank' => ['x' => 5, 'y' => 93, 'width' => 90],
    'sender_bic_top' => ['x' => 70, 'y' => 93, 'width' => 45],
    'recipient_name' => ['x' => 8, 'y' => 105.5, 'width' => 148],
    'recipient_iban' => ['x' => 8, 'y' => 113, 'boxes' => 22],
    'recipient_bic' => ['x' => 8, 'y' => 123, 'width' => 60],
    'amount' => ['x' => 6, 'y' => 131.5, 'width' => 135, 'align' => 'R'],
    'purpose_line1' => ['x' => 8, 'y' => 140, 'width' => 148],
    'purpose_line2' => ['x' => 8, 'y' => 140.5, 'width' => 148],
    'sender_name' => ['x' => 8, 'y' => 156.0, 'width' => 148],
    'sender_iban' => ['x' => 18, 'y' => 165.0, 'boxes' => 20, 'skip_prefix' => 2],
    'date' => ['x' => 8, 'y' => 180, 'width' => 25],
    'receipt_iban' => ['x' => 162, 'y' => 95.2, 'width' => 44, 'fontsize' => 7],
    'receipt_sender' => ['x' => 162, 'y' => 110, 'width' => 44, 'fontsize' => 8],
    'receipt_recipient' => ['x' => 162, 'y' => 125, 'width' => 44, 'fontsize' => 8],
    'receipt_purpose' => ['x' => 162, 'y' => 145.2, 'width' => 44, 'fontsize' => 7],
    'receipt_date' => ['x' => 162, 'y' => 155.2, 'width' => 44, 'fontsize' => 8],
    'receipt_amount' => ['x' => 162, 'y' => 165.2, 'width' => 44, 'fontsize' => 9],
    'quittung_bank' => ['x' => 5, 'y' => 198, 'width' => 90],
    'quittung_bank_bic' => ['x' => 70, 'y' => 198, 'width' => 45],
    'quittung_recipient' => ['x' => 8, 'y' => 212, 'width' => 148],
    'quittung_iban' => ['x' => 8, 'y' => 220, 'boxes' => 22],
    'quittung_bic' => ['x' => 8, 'y' => 230, 'width' => 60],
    'quittung_amount' => ['x' => 95.6, 'y' => 235.9, 'width' => 43, 'align' => 'R'],
    'quittung_purpose1' => ['x' => 8, 'y' => 236, 'width' => 148],
    'quittung_purpose2' => ['x' => 8, 'y' => 240, 'width' => 148],
    'quittung_sender' => ['x' => 8, 'y' => 263.0, 'width' => 148],
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

function formatIBAN($iban) {
    return str_replace(' ', '', strtoupper(trim($iban)));
}

function formatAmount($amount) {
    return number_format((float)$amount, 2, ',', '.');
}

function truncateText($text, $maxlen) {
    return mb_substr(trim($text), 0, $maxlen);
}

function printIBANBoxes($pdf, $iban, $startX, $y, $numBoxes, $skipChars = 0) {
    $iban = formatIBAN($iban);

    if ($skipChars > 0) {
        $iban = substr($iban, $skipChars);
    }

    $pdf->SetFont('courier', 'B', FONT_SIZE_IBAN);

    for ($i = 0; $i < $numBoxes && $i < strlen($iban); $i++) {
        $x = $startX + ($i * IBAN_BOX_WIDTH) + OFFSET_X;
        $yPos = $y + OFFSET_Y;

        $pdf->SetXY($x + 0.3, $yPos + 0.8);
        $pdf->Cell(IBAN_BOX_WIDTH - 0.5, IBAN_BOX_HEIGHT - 1, $iban[$i], 0, 0, 'C');
    }
}

function printText($pdf, $text, $x, $y, $width = 0, $align = 'L', $fontsize = FONT_SIZE_NORMAL, $bold = false) {
    $x += OFFSET_X;
    $y += OFFSET_Y;

    $style = $bold ? 'B' : '';
    $pdf->SetFont('helvetica', $style, $fontsize);
    $pdf->SetXY($x, $y);
    $pdf->Cell($width, 5, $text, 0, 0, $align);
}

function printMultiText($pdf, $text, $x, $y, $width, $fontsize = FONT_SIZE_SMALL) {
    $x += OFFSET_X;
    $y += OFFSET_Y;

    $pdf->SetFont('helvetica', '', $fontsize);
    $pdf->SetXY($x, $y);
    $pdf->MultiCell($width, 3.5, $text, 0, 'L');
}

function drawDebugGrid($pdf) {
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.1);

    for ($x = 0; $x <= 210; $x += 10) {
        $pdf->Line($x, 0, $x, 297);
        $pdf->SetFont('helvetica', '', 5);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetXY($x + 0.3, 1);
        $pdf->Cell(8, 3, $x, 0, 0, 'L');
    }

    for ($y = 0; $y <= 297; $y += 10) {
        $pdf->Line(0, $y, 210, $y);
        $pdf->SetFont('helvetica', '', 5);
        $pdf->SetXY(0.5, $y + 0.3);
        $pdf->Cell(8, 3, $y, 0, 0, 'L');
    }

    $pdf->SetTextColor(0, 0, 0);
}

// ============================================================================
// Transfer aus Datenbank laden
// ============================================================================

$user_id = $_SESSION['user_id'];
$transfer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$transfer_id) {
    die('Keine Transfer-ID angegeben');
}

$stmt = $db->prepare("
    SELECT * FROM transfers
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$transfer_id, $user_id]);
$transfer = $stmt->fetch();

if (!$transfer) {
    die('Überweisung nicht gefunden');
}

// ============================================================================
// Daten vorbereiten
// ============================================================================

$recipientName = strtoupper(truncateText($transfer['recipient_name'] ?? '', 35));
$recipientIBAN = formatIBAN($transfer['recipient_iban'] ?? '');
$recipientBIC = strtoupper(trim($transfer['recipient_bic'] ?? ''));

$amount = formatAmount($transfer['amount'] ?? 0);

$refNumber = trim($transfer['reference_number'] ?? '');
$purpose1 = trim($transfer['purpose_line1'] ?? '');
$purpose2 = trim($transfer['purpose_line2'] ?? '');

$purposeLine1 = truncateText($refNumber . ($refNumber && $purpose1 ? ' ' : '') . $purpose1, 35);
$purposeLine2 = truncateText($purpose2, 35);
$purposeFull = trim($purposeLine1 . ' ' . $purposeLine2);

$senderName = strtoupper(truncateText($transfer['sender_name'] ?? '', 27));
$senderCity = strtoupper(trim($transfer['sender_city'] ?? ''));
$senderIBAN = formatIBAN($transfer['sender_iban'] ?? '');
$senderBIC = strtoupper(trim($transfer['sender_bic'] ?? ''));
$senderBank = trim($transfer['sender_bank'] ?? '');

$senderFull = $senderName;
if (!empty($senderCity)) {
    $senderFull = truncateText($senderName . ', ' . $senderCity, 27);
}

$currentDate = date('d.m.Y');

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

if (SHOW_BACKGROUND && file_exists(BACKGROUND_IMAGE)) {
    $pdf->Image(BACKGROUND_IMAGE, 0, 0, 210, 297, 'JPG', '', '', false, 300, '', false, false, 0);
}

if (SHOW_DEBUG_GRID) {
    drawDebugGrid($pdf);
}

$pdf->SetTextColor(0, 0, 0);

// ============================================================================
// TEIL 1: SEPA-ÜBERWEISUNG/ZAHLSCHEIN
// ============================================================================

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

printText($pdf, $recipientName,
    $positions['recipient_name']['x'],
    $positions['recipient_name']['y'],
    $positions['recipient_name']['width'],
    'L', FONT_SIZE_NORMAL, false);

printIBANBoxes($pdf, $recipientIBAN,
    $positions['recipient_iban']['x'],
    $positions['recipient_iban']['y'],
    $positions['recipient_iban']['boxes']);

printText($pdf, $recipientBIC,
    $positions['recipient_bic']['x'],
    $positions['recipient_bic']['y'],
    $positions['recipient_bic']['width'],
    'L', FONT_SIZE_NORMAL, false);

printText($pdf, $amount,
    $positions['amount']['x'],
    $positions['amount']['y'],
    $positions['amount']['width'],
    $positions['amount']['align'],
    FONT_SIZE_AMOUNT, true);

printText($pdf, $purposeLine1,
    $positions['purpose_line1']['x'],
    $positions['purpose_line1']['y'],
    $positions['purpose_line1']['width']);

printText($pdf, $purposeLine2,
    $positions['purpose_line2']['x'],
    $positions['purpose_line2']['y'],
    $positions['purpose_line2']['width']);

printText($pdf, $senderFull,
    $positions['sender_name']['x'],
    $positions['sender_name']['y'],
    $positions['sender_name']['width'],
    'L', FONT_SIZE_NORMAL, false);

if (substr($senderIBAN, 0, 2) === 'DE') {
    printIBANBoxes($pdf, $senderIBAN,
        $positions['sender_iban']['x'],
        $positions['sender_iban']['y'],
        $positions['sender_iban']['boxes'],
        2);
} else {
    printIBANBoxes($pdf, $senderIBAN,
        $positions['sender_iban']['x'] - 8.5,
        $positions['sender_iban']['y'],
        22);
}

printText($pdf, $currentDate,
    $positions['date']['x'],
    $positions['date']['y'],
    $positions['date']['width']);

// ============================================================================
// TEIL 2: BELEG FÜR KONTOINHABER (rechte Spalte)
// ============================================================================

printMultiText($pdf, $senderIBANFormatted,
    $positions['receipt_iban']['x'],
    $positions['receipt_iban']['y'],
    $positions['receipt_iban']['width'],
    $positions['receipt_iban']['fontsize']);

printText($pdf, truncateText($senderName, 28),
    $positions['receipt_sender']['x'],
    $positions['receipt_sender']['y'],
    $positions['receipt_sender']['width'],
    'L', $positions['receipt_sender']['fontsize']);

printText($pdf, truncateText($recipientName, 28),
    $positions['receipt_recipient']['x'],
    $positions['receipt_recipient']['y'],
    $positions['receipt_recipient']['width'],
    'L', $positions['receipt_recipient']['fontsize']);

printMultiText($pdf, truncateText($purposeFull, 80),
    $positions['receipt_purpose']['x'],
    $positions['receipt_purpose']['y'],
    $positions['receipt_purpose']['width'],
    $positions['receipt_purpose']['fontsize']);

printText($pdf, $currentDate,
    $positions['receipt_date']['x'],
    $positions['receipt_date']['y'],
    $positions['receipt_date']['width'],
    'L', $positions['receipt_date']['fontsize']);

printText($pdf, $amount . ' EUR',
    $positions['receipt_amount']['x'],
    $positions['receipt_amount']['y'],
    $positions['receipt_amount']['width'],
    'L', $positions['receipt_amount']['fontsize'], true);

// ============================================================================
// TEIL 3: ZAHLER-QUITTUNG (unterer Teil)
// ============================================================================

if (!empty($senderBank)) {
    printText($pdf, truncateText($senderBank, 50),
        $positions['quittung_bank']['x'],
        $positions['quittung_bank']['y'],
        $positions['quittung_bank']['width']);
}

if (!empty($senderBIC)) {
    printText($pdf, $senderBIC,
        $positions['quittung_bank_bic']['x'],
        $positions['quittung_bank_bic']['y'],
        $positions['quittung_bank_bic']['width']);
}

printText($pdf, $recipientName,
    $positions['quittung_recipient']['x'],
    $positions['quittung_recipient']['y'],
    $positions['quittung_recipient']['width'],
    'L', FONT_SIZE_NORMAL, false);

printIBANBoxes($pdf, $recipientIBAN,
    $positions['quittung_iban']['x'],
    $positions['quittung_iban']['y'],
    $positions['quittung_iban']['boxes']);

printText($pdf, $recipientBIC,
    $positions['quittung_bic']['x'],
    $positions['quittung_bic']['y'],
    $positions['quittung_bic']['width'],
    'L', FONT_SIZE_NORMAL, false);

printText($pdf, $amount,
    $positions['quittung_amount']['x'],
    $positions['quittung_amount']['y'],
    $positions['quittung_amount']['width'],
    $positions['quittung_amount']['align'],
    FONT_SIZE_AMOUNT, true);

printText($pdf, $purposeLine1,
    $positions['quittung_purpose1']['x'],
    $positions['quittung_purpose1']['y'],
    $positions['quittung_purpose1']['width']);

printText($pdf, $purposeLine2,
    $positions['quittung_purpose2']['x'],
    $positions['quittung_purpose2']['y'],
    $positions['quittung_purpose2']['width']);

printText($pdf, $senderFull,
    $positions['quittung_sender']['x'],
    $positions['quittung_sender']['y'],
    $positions['quittung_sender']['width'],
    'L', FONT_SIZE_NORMAL, false);

printIBANBoxes($pdf, $senderIBAN,
    $positions['quittung_sender_iban']['x'],
    $positions['quittung_sender_iban']['y'],
    $positions['quittung_sender_iban']['boxes']);

// ============================================================================
// PDF ausgeben
// ============================================================================

$filename = 'SEPA_Ueberweisung_' . $transfer_id . '_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
?>
