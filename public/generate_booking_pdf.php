<?php
session_start();
require_once '../vendor/autoload.php';
include '../api/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || !isset($_GET['booking_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'];

// Fetch booking details with venue and user information
$stmt = $conn->prepare("
    SELECT 
        b.*, 
        v.name as venue_name, 
        v.location as venue_location, 
        v.capacity as venue_capacity,
        v.description as venue_description,
        u.full_name as user_name,
        u.email as user_email
    FROM bookings b 
    JOIN venues v ON b.venue_id = v.id 
    JOIN users u ON b.user_id = u.id
    WHERE b.id = :booking_id AND b.user_id = :user_id
");
$stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: my_bookings.php");
    exit();
}

// Configure Dompdf
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

// Create HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #2d3748;
            margin: 0;
            padding: 0;
        }
        
        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
        }
        
        .header-content {
            display: table;
            width: 100%;
        }
        
        .logo-section {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }
        
        .university-info {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
            padding-left: 20px;
        }
        
        .vtu-logo {
            width: 65px;
            height: 65px;
            border: 3px solid #1e40af;
            border-radius: 50%;
            background: #f0f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            text-align: center;
            font-family: Arial, sans-serif;
        }
        
        .vtu-logo-img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            margin: 0;
            object-fit: cover;
        }
        
        .university-name {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
            margin: 0 0 5px 0;
            letter-spacing: 0.5px;
        }
        
        .university-name-kannada {
            font-size: 16px;
            font-weight: bold;
            color: #059669;
            margin: 0 0 8px 0;
        }
        
        .university-address {
            font-size: 11px;
            color: #4a5568;
            margin: 0;
            line-height: 1.4;
        }
        
        .university-address-kannada {
            font-size: 10px;
            color: #4a5568;
            margin: 3px 0 0 0;
        }
        
        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            margin: 25px 0 15px 0;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .document-subtitle {
            font-size: 14px;
            color: #059669;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .booking-details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 18px;
            margin: 15px 0;
        }
        
        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .detail-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            color: #374151;
            vertical-align: top;
            padding-right: 12px;
            font-size: 11px;
        }
        
        .detail-value {
            display: table-cell;
            color: #1f2937;
            vertical-align: top;
            font-size: 11px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .venue-info {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            margin: 15px 0;
        }
        
        .venue-title {
            font-size: 16px;
            font-weight: bold;
            color: #0c4a6e;
            margin-bottom: 8px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
        
        .important-note {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 10px;
            color: #991b1b;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/images/vtu_logo.png')) . '" alt="VTU Logo" class="vtu-logo-img">
            </div>
            <div class="university-info">
                <div class="university-name">VISVESVARAYA TECHNOLOGICAL UNIVERSITY</div>
                <div class="university-name-kannada">(Visvesvaraya Technological University)</div>
                <div class="university-address">
                    Jnana Sangama, Belagavi - 590018, Karnataka, India<br>
                    Phone: +91-831-2498100 | Email: registrar@vtu.ac.in
                </div>
            </div>
        </div>
    </div>
    
    <div class="document-title">Venue Booking Confirmation</div>
    <div class="document-subtitle">Official Venue Booking Confirmation</div>
    
    <div class="booking-details">
        <div class="detail-row">
            <div class="detail-label">Booking ID:</div>
            <div class="detail-value">#VTU-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT) . '</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Booking Date:</div>
            <div class="detail-value">' . date('d M Y, h:i A', strtotime($booking['created_at'])) . '</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Status:</div>
            <div class="detail-value">
                <span class="status-badge status-' . strtolower($booking['status']) . '">' . ucfirst($booking['status']) . '</span>
            </div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Applicant Name:</div>
            <div class="detail-value">' . htmlspecialchars($booking['full_name']) . '</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Email:</div>
            <div class="detail-value">' . htmlspecialchars($booking['email']) . '</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Phone:</div>
            <div class="detail-value">' . htmlspecialchars($booking['phone']) . '</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Department:</div>
            <div class="detail-value">' . htmlspecialchars($booking['department']) . '</div>
        </div>
    </div>
    
    <div class="venue-info">
        <div class="venue-title">' . htmlspecialchars($booking['venue_name']) . '</div>
        <div class="detail-row">
            <div class="detail-label">Location:</div>
            <div class="detail-value">' . htmlspecialchars($booking['venue_location']) . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Capacity:</div>
            <div class="detail-value">' . htmlspecialchars($booking['venue_capacity']) . ' people</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Type:</div>
            <div class="detail-value">Conference Hall</div>
        </div>
    </div>
    
    <div class="booking-details">
        <div class="detail-row">
            <div class="detail-label">Event Name:</div>
            <div class="detail-value">' . htmlspecialchars($booking['event_name']) . '</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Event Date:</div>
            <div class="detail-value">' . date('d M Y (l)', strtotime($booking['event_date'])) . '</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Time:</div>
            <div class="detail-value">' . date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time'])) . '</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Duration:</div>
            <div class="detail-value">' . 
                (strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 3600 . ' hours</div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Event Details:</div>
            <div class="detail-value">' . nl2br(htmlspecialchars($booking['event_details'])) . '</div>
        </div>
    </div>
    
    <div class="important-note">
        <strong>Important Instructions:</strong><br><br>
        • Please carry this confirmation along with a valid ID proof<br>
        • Report to the venue 30 minutes before the event time<br>
        • Contact the venue coordinator for any technical requirements<br>
        • Follow all university guidelines and protocols during the event<br>
        • Ensure proper conduct and maintain cleanliness of the venue<br>
        • Any damage to university property will be charged to the organizer
    </div>
    
    <div class="footer">
        <strong>For any queries contact:</strong><br>
        Venue Booking Office, VTU Administrative Block<br>
        Phone: +91-831-2419831 | Email: venues@vtu.ac.in<br><br>
        
        <div style="margin-top: 15px; border-top: 1px solid #e5e7eb; padding-top: 10px;">
            Generated on: ' . date('d M Y, h:i A') . ' | This is a computer generated document.<br>
            Document ID: VTU-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT) . '-' . date('Ymd') . '
        </div>
    </div>
</body>
</html>';

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Output PDF
$filename = 'VTU_Booking_Confirmation_' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT) . '.pdf';
$dompdf->stream($filename, array('Attachment' => true));
?>