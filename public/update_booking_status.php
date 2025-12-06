<?php
session_start();

// Use PHPMailer classes from the library you installed
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 1. SETUP & SECURITY
// -----------------------------------------------------------------------------
// Ensure an admin is logged in and the request is a POST
if (!isset($_SESSION['admin_logged_in']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_login.php");
    exit;
}

// Include dependencies. The paths go up one level from 'public' to the project root.
require_once '../api/config.php';
require_once '../vendor/autoload.php';

// 2. DATA VALIDATION
// -----------------------------------------------------------------------------
$booking_id = $_POST['booking_id'] ?? null;
$status = $_POST['status'] ?? null;
$admin_remarks = trim($_POST['admin_remarks'] ?? '');

// Ensure required data is present and valid
if (!$booking_id || !$status || !in_array($status, ['approved', 'rejected'])) {
    $_SESSION['flash'] = ['type' => 'error', 'text' => 'Invalid or missing data.'];
    header("Location: admin_dashboard.php");
    exit;
}

// 3. HELPER & LOGGING FUNCTIONS
// -----------------------------------------------------------------------------
/**
 * Logs the email dispatch attempt to a file for debugging.
 */
function logDispatchAttempt($booking_id, $email, $result, $error = '') {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logMessage = date('Y-m-d H:i:s') . " - Booking ID: $booking_id - Email: $email - Result: " . ($result ? 'SUCCESS' : 'FAILURE');
    if (!empty($error)) {
        $logMessage .= " - Error: $error";
    }
    $logMessage .= PHP_EOL;
    file_put_contents($logDir . '/email_dispatch_log.txt', $logMessage, FILE_APPEND);
}

/**
 * Sends a booking status email using PHPMailer with improved error handling.
 */
function sendBookingStatusEmail($booking, $status, $remarks) {
    $mail = new PHPMailer(true);
    
    try {
        // REMOVE DEBUG IN PRODUCTION - Only enable if needed for troubleshooting
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        // $mail->Debugoutput = function($str, $level) {
        //     error_log("PHPMailer Debug: $str");
        // };

        // --- SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'premmokashi2107@gmail.com';
        $mail->Password   = 'nddhwdvvvkuwodcg'; // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ✅ Correct constant
        $mail->Port       = 465;
        $mail->Timeout    = 30; // Add timeout
        
        // Additional SMTP options for Gmail
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- RECIPIENTS ---
        $mail->setFrom('premmokashi2107@gmail.com', 'VTU Venue Booking Admin');
        $mail->addAddress($booking['email'], $booking['user_name']);
        $mail->addReplyTo('premmokashi2107@gmail.com', 'VTU Venue Booking Admin');

        // --- EMAIL CONTENT ---
        $mail->isHTML(true);
        $status_text = ucfirst($status);
        $status_icon = ($status === 'approved') ? '✅' : '❌';
        $mail->Subject = "$status_icon Booking $status_text: " . $booking['event_name'];
        
        // Check if template exists, if not use simple HTML
        $templatePath = __DIR__ . '/email_template.html';
        if (file_exists($templatePath)) {
            $emailBody = file_get_contents($templatePath);
            
            $replacements = [
                '{{user_name}}'    => htmlspecialchars($booking['user_name']),
                '{{status_icon}}'  => $status_icon,
                '{{status_text}}'  => $status_text,
                '{{status_color}}' => ($status === 'approved') ? '#28a745' : '#dc3545',
                '{{venue_name}}'   => htmlspecialchars($booking['venue_name']),
                '{{event_name}}'   => htmlspecialchars($booking['event_name']),
                '{{event_date}}'   => date('F j, Y', strtotime($booking['event_date'])),
                '{{event_time}}'   => date('g:i A', strtotime($booking['start_time'])) . ' - ' . date('g:i A', strtotime($booking['end_time'])),
                '{{current_year}}' => date('Y')
            ];

            $remarks_html = !empty($remarks) ? 
                "<div style='margin-top: 20px; padding: 15px; background-color: #fffbe6; border-left: 4px solid #ffc107; color: #856404;'>
                    <strong>Admin Remarks:</strong><br>" . nl2br(htmlspecialchars($remarks)) . "
                </div>" : '';
            $replacements['{{admin_remarks}}'] = $remarks_html;

            foreach ($replacements as $key => $value) {
                $emailBody = str_replace($key, $value, $emailBody);
            }
            
            $mail->Body = $emailBody;
        } else {
            // Fallback simple HTML email if template doesn't exist
            $remarks_section = !empty($remarks) ? "<p><strong>Admin Remarks:</strong><br>" . nl2br(htmlspecialchars($remarks)) . "</p>" : '';
            
            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>$status_icon Booking $status_text</h2>
                <p>Dear " . htmlspecialchars($booking['user_name']) . ",</p>
                <p>Your booking request has been <strong>$status_text</strong>.</p>
                
                <h3>Booking Details:</h3>
                <ul>
                    <li><strong>Event:</strong> " . htmlspecialchars($booking['event_name']) . "</li>
                    <li><strong>Venue:</strong> " . htmlspecialchars($booking['venue_name']) . "</li>
                    <li><strong>Date:</strong> " . date('F j, Y', strtotime($booking['event_date'])) . "</li>
                    <li><strong>Time:</strong> " . date('g:i A', strtotime($booking['start_time'])) . " - " . date('g:i A', strtotime($booking['end_time'])) . "</li>
                </ul>
                
                $remarks_section
                
                <p>Best regards,<br>VTU Venue Booking Team</p>
            </body>
            </html>";
        }

        $mail->AltBody = "Dear " . $booking['user_name'] . ", your booking for " . $booking['event_name'] . " has been " . $status . ".";

        $result = $mail->send();
        return ['success' => $result, 'error' => ''];
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 4. MAIN LOGIC
// -----------------------------------------------------------------------------
try {
    // Get full booking details
    $stmt = $conn->prepare("
        SELECT b.id, b.event_name, b.event_date, b.start_time, b.end_time,
               v.name AS venue_name,
               b.full_name AS user_name,
               b.email
        FROM bookings b
        JOIN venues v ON b.venue_id = v.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking with ID $booking_id not found.");
    }

    // Validate email address
    if (!filter_var($booking['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address: " . $booking['email']);
    }

    // Update the booking status in the database
    $updateStmt = $conn->prepare("UPDATE bookings SET status = ?, admin_remarks = ? WHERE id = ?");
    $updateStmt->execute([$status, $admin_remarks, $booking_id]);

    // Send the notification email
    $emailResult = sendBookingStatusEmail($booking, $status, $admin_remarks);
    logDispatchAttempt($booking_id, $booking['email'], $emailResult['success'], $emailResult['error']);

    if ($emailResult['success']) {
        $_SESSION['flash'] = ['type' => 'success', 'text' => "Booking status updated successfully. Notification sent to " . htmlspecialchars($booking['email'])];
    } else {
        $_SESSION['flash'] = ['type' => 'warning', 'text' => "Booking status updated, but email failed: " . $emailResult['error']];
    }

} catch (Exception $e) {
    error_log("Booking update error: " . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'text' => 'An error occurred: ' . $e->getMessage()];
}

header("Location: admin_dashboard.php");
exit();
?>