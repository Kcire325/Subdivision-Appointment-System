<?php
session_start();
require_once('tcpdf/tcpdf.php'); // Adjust path to your TCPDF library

// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Resident') {
    header("Location: ../login/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'facilityreservationsystem';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$reservation_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$reservation_id) {
    die("Invalid reservation ID");
}

// Fetch reservation details
$stmt = $conn->prepare("SELECT * FROM reservations WHERE id = :id AND user_id = :user_id");
$stmt->execute([':id' => $reservation_id, ':user_id' => $user_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    die("Reservation not found");
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Facility Reservation System');
$pdf->SetTitle('Reservation Invoice');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();


$pdf->SetFont('helvetica', '', 11);


$html = '
<h2>Reservation Invoice</h2>
<br>

<table cellpadding="6" border="1" style="border-collapse: collapse;">
    <tr>
        <td width="40%"><strong>Reservation ID</strong></td>
        <td width="60%">' . htmlspecialchars($reservation['id']) . '</td>
    </tr>
    <tr>
        <td><strong>Facility</strong></td>
        <td>' . htmlspecialchars($reservation['facility_name']) . '</td>
    </tr>
    <tr>
        <td><strong>Date</strong></td>
        <td>' . date('F d, Y', strtotime($reservation['event_start_date'])) . '</td>
    </tr>
    <tr>
        <td><strong>Time</strong></td>
        <td>' . date('g:i A', strtotime($reservation['time_start'])) . ' - ' . date('g:i A', strtotime($reservation['time_end'])) . '</td>
    </tr>
    <tr>
        <td><strong>Status</strong></td>
        <td>' . ucfirst($reservation['status']) . '</td>
    </tr>
    <tr>
        <td><strong>Booked On</strong></td>
        <td>' . date('M d, Y g:i A', strtotime($reservation['created_at'])) . '</td>
    </tr>
</table>

<br><br>
';

// Output the HTML content
$pdf->writeHTML($html);

// Close and output PDF document - 'D' forces download
$pdf->Output('reservation_invoice_' . $reservation_id . '.pdf', 'D');
?>