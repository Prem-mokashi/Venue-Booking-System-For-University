<?php
session_start();
require_once '../vendor/autoload.php';
include '../api/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Get export parameters
$export_type = $_POST['export_type'] ?? 'all';
$export_date = $_POST['export_date'] ?? '';
$export_venue = $_POST['export_venue'] ?? '';
$export_status = $_POST['export_status'] ?? '';
$export_from_date = $_POST['export_from_date'] ?? '';
$export_to_date = $_POST['export_to_date'] ?? '';
$export_custom_venue = $_POST['export_custom_venue'] ?? '';
$export_custom_status = $_POST['export_custom_status'] ?? '';

// Build query based on export type
$query = "SELECT b.*, v.name AS venue_name, b.full_name AS user_name, b.email, b.phone, b.department
          FROM bookings b
          JOIN venues v ON b.venue_id = v.id
          WHERE 1";
$params = [];

switch ($export_type) {
    case 'date':
        if ($export_date) {
            $query .= " AND b.event_date = ?";
            $params[] = $export_date;
        }
        break;
    
    case 'venue':
        if ($export_venue) {
            $query .= " AND v.name = ?";
            $params[] = $export_venue;
        }
        break;
    
    case 'status':
        if ($export_status) {
            $query .= " AND b.status = ?";
            $params[] = $export_status;
        }
        break;
    
    case 'custom':
        if ($export_from_date) {
            $query .= " AND b.event_date >= ?";
            $params[] = $export_from_date;
        }
        if ($export_to_date) {
            $query .= " AND b.event_date <= ?";
            $params[] = $export_to_date;
        }
        if ($export_custom_venue) {
            $query .= " AND v.name = ?";
            $params[] = $export_custom_venue;
        }
        if ($export_custom_status) {
            $query .= " AND b.status = ?";
            $params[] = $export_custom_status;
        }
        break;
}

$query .= " ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configure Dompdf
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

// Generate export title
$export_title = 'All Bookings';
switch ($export_type) {
    case 'date':
        $export_title = 'Bookings for ' . date('d M Y', strtotime($export_date));
        break;
    case 'venue':
        $export_title = 'Bookings for ' . $export_venue;
        break;
    case 'status':
        $export_title = ucfirst($export_status) . ' Bookings';
        break;
    case 'custom':
        $export_title = 'Custom Export';
        if ($export_from_date && $export_to_date) {
            $export_title .= ' (' . date('d M Y', strtotime($export_from_date)) . ' - ' . date('d M Y', strtotime($export_to_date)) . ')';
        }
        break;
}

// Create HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }
        
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #2d3748;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
        }
        
        .university-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin: 10px 0;
        }
        
        .export-info {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: left;
            font-size: 9px;
        }
        
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        
        .status-approved { color: #059669; font-weight: bold; }
        .status-pending { color: #d97706; font-weight: bold; }
        .status-rejected { color: #dc2626; font-weight: bold; }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        
        .summary {
            background: #f9fafb;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        .summary-item {
            display: inline-block;
            margin-right: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="university-name">VISVESVARAYA TECHNOLOGICAL UNIVERSITY</div>
        <div style="font-size: 12px; color: #059669;">Venue Booking System - Admin Report</div>
        <div style="font-size: 10px; color: #6b7280; margin-top: 5px;">
            Jnana Sangama, Belagavi - 590018, Karnataka, India
        </div>
    </div>
    
    <div class="report-title">' . $export_title . '</div>
    
    <div class="export-info">
        Generated on: ' . date('d M Y, h:i A') . ' | Total Records: ' . count($bookings) . '
    </div>';

// Add summary statistics
$stats = [
    'total' => count($bookings),
    'approved' => 0,
    'pending' => 0,
    'rejected' => 0
];

foreach ($bookings as $booking) {
    if (isset($stats[$booking['status']])) {
        $stats[$booking['status']]++;
    }
}

$html .= '
    <div class="summary">
        <span class="summary-item">Total: ' . $stats['total'] . '</span>
        <span class="summary-item status-approved">Approved: ' . $stats['approved'] . '</span>
        <span class="summary-item status-pending">Pending: ' . $stats['pending'] . '</span>
        <span class="summary-item status-rejected">Rejected: ' . $stats['rejected'] . '</span>
    </div>';

if (count($bookings) > 0) {
    $html .= '
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Event Name</th>
                <th>Venue</th>
                <th>Organizer</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Event Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Booked On</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($bookings as $booking) {
        $status_class = 'status-' . strtolower($booking['status']);
        $html .= '
            <tr>
                <td>#' . str_pad($booking['id'], 4, '0', STR_PAD_LEFT) . '</td>
                <td>' . htmlspecialchars($booking['event_name']) . '</td>
                <td>' . htmlspecialchars($booking['venue_name']) . '</td>
                <td>' . htmlspecialchars($booking['user_name']) . '</td>
                <td>' . htmlspecialchars($booking['email']) . '</td>
                <td>' . htmlspecialchars($booking['phone']) . '</td>
                <td>' . htmlspecialchars($booking['department']) . '</td>
                <td>' . date('d M Y', strtotime($booking['event_date'])) . '</td>
                <td>' . date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time'])) . '</td>
                <td><span class="' . $status_class . '">' . ucfirst($booking['status']) . '</span></td>
                <td>' . date('d M Y', strtotime($booking['created_at'])) . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>';
} else {
    $html .= '<div style="text-align: center; padding: 40px; color: #6b7280;">No bookings found for the selected criteria.</div>';
}

$html .= '
    <div class="footer">
        <strong>VTU Venue Booking System</strong><br>
        This is a computer generated report. For queries contact: venues@vtu.ac.in | +91-831-2419831<br>
        Report ID: VTU-EXPORT-' . date('Ymd-His') . '
    </div>
</body>
</html>';

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render PDF
$dompdf->render();

// Generate filename
$filename = 'VTU_Bookings_Export_' . date('Y-m-d_H-i-s') . '.pdf';

// Output PDF
$dompdf->stream($filename, array('Attachment' => true));
?>