<?php
// Fixed paths for PHPMailer
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'config.php';

/**
 * Validate booking data before sending email
 */
function validateBookingData(array $booking): bool {
    $required = ['email', 'full_name', 'venue_name', 'event_name', 'event_date', 'start_time', 'end_time', 'booking_id'];
    
    foreach ($required as $field) {
        if (empty($booking[$field])) {
            error_log("Missing required booking field: {$field}");
            return false;
        }
    }
    
    // Validate email format
    if (!filter_var($booking['email'], FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email format: " . $booking['email']);
        return false;
    }
    
    return true;
}

/**
 * Send booking status update email
 */
function sendBookingStatusEmail(array $booking, string $status, string $comment = ''): bool {
    // Validate input data
    if (!validateBookingData($booking)) {
        error_log('Booking data validation failed');
        return false;
    }
    
    if (!in_array($status, ['approved', 'rejected', 'pending'])) {
        error_log("Invalid status: {$status}");
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'premmokashi2107@gmail.com'; // Replace with your email
        $mail->Password = '8669799711email'; // Replace with your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Additional SMTP options for better reliability
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Set timeout
        $mail->Timeout = 30;
        
        // Recipients
        $mail->setFrom('noreply@vtubooking.com', 'VTU Booking System');
        $mail->addAddress($booking['email'], $booking['full_name']);
        
        // Optional: Add reply-to address
        $mail->addReplyTo('support@vtubooking.com', 'VTU Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = createEmailSubject($booking, $status);
        $mail->Body = createEmailTemplate($booking, $status, $comment);
        $mail->AltBody = createPlainTextEmail($booking, $status, $comment);
        
        // Set email priority based on status
        if ($status === 'rejected') {
            $mail->Priority = 2; // High priority for rejections
        }
        
        $result = $mail->send();
        
        if ($result) {
            logEmailAttempt($booking['email'], $status, true);
            return true;
        } else {
            logEmailAttempt($booking['email'], $status, false, 'Send failed but no exception thrown');
            return false;
        }
        
    } catch (Exception $e) {
        $errorMsg = 'PHPMailer error: ' . $mail->ErrorInfo . ' | Exception: ' . $e->getMessage();
        error_log($errorMsg);
        logEmailAttempt($booking['email'], $status, false, $errorMsg);
        return false;
    }
}

/**
 * Create email subject line
 */
function createEmailSubject(array $booking, string $status): string {
    $statusText = ucfirst($status);
    $emoji = $status === 'approved' ? '✅' : ($status === 'rejected' ? '❌' : '⏳');
    
    return "{$emoji} VTU Booking {$statusText} - {$booking['venue_name']} - {$booking['event_name']}";
}

/**
 * Create HTML email template
 */
function createEmailTemplate(array $b, string $status, string $comment): string {
    $color = getStatusColor($status);
    $icon = getStatusIcon($status);
    $statusText = ucfirst($status);
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Status Update</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            .header {
                background: <?= $color ?>;
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: normal;
            }
            .content {
                padding: 30px;
            }
            .booking-card {
                background: #f8f9fa;
                padding: 25px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid <?= $color ?>;
            }
            .booking-row {
                display: flex;
                justify-content: space-between;
                margin: 10px 0;
                padding: 8px 0;
                border-bottom: 1px solid #e9ecef;
            }
            .booking-row:last-child {
                border-bottom: none;
            }
            .booking-label {
                font-weight: bold;
                color: #495057;
                width: 30%;
            }
            .booking-value {
                width: 65%;
                color: #212529;
            }
            .status-badge {
                display: inline-block;
                padding: 8px 16px;
                background: <?= $color ?>;
                color: white;
                border-radius: 20px;
                font-weight: bold;
                font-size: 14px;
            }
            .comment-section {
                background: #e7f3ff;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #0066cc;
            }
            .comment-section h4 {
                margin-top: 0;
                color: #0066cc;
            }
            .footer {
                background: #f8f9fa;
                padding: 20px;
                text-align: center;
                color: #6c757d;
                font-size: 14px;
                border-top: 1px solid #dee2e6;
            }
            .footer a {
                color: <?= $color ?>;
                text-decoration: none;
            }
            @media (max-width: 600px) {
                .booking-row {
                    flex-direction: column;
                }
                .booking-label, .booking-value {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><?= $icon ?> Booking <?= $statusText ?></h1>
            </div>
            
            <div class="content">
                <p>Dear <strong><?= htmlspecialchars($b['full_name']) ?></strong>,</p>
                
                <p>Your booking request has been <span class="status-badge"><?= $statusText ?></span>.</p>
                
                <div class="booking-card">
                    <h3 style="margin-top: 0; color: <?= $color ?>;">📋 Booking Details</h3>
                    
                    <div class="booking-row">
                        <div class="booking-label">Booking ID:</div>
                        <div class="booking-value">#<?= htmlspecialchars($b['booking_id']) ?></div>
                    </div>
                    
                    <div class="booking-row">
                        <div class="booking-label">Event Name:</div>
                        <div class="booking-value"><?= htmlspecialchars($b['event_name']) ?></div>
                    </div>
                    
                    <div class="booking-row">
                        <div class="booking-label">Venue:</div>
                        <div class="booking-value"><?= htmlspecialchars($b['venue_name']) ?></div>
                    </div>
                    
                    <div class="booking-row">
                        <div class="booking-label">Date:</div>
                        <div class="booking-value"><?= date('l, F j, Y', strtotime($b['event_date'])) ?></div>
                    </div>
                    
                    <div class="booking-row">
                        <div class="booking-label">Time:</div>
                        <div class="booking-value">
                            <?= date('g:i A', strtotime($b['start_time'])) ?> - 
                            <?= date('g:i A', strtotime($b['end_time'])) ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($comment)): ?>
                <div class="comment-section">
                    <h4>💬 Admin Message</h4>
                    <p><?= nl2br(htmlspecialchars($comment)) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($status === 'approved'): ?>
                <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;">
                    <p style="margin: 0; color: #155724;">
                        <strong>✅ Next Steps:</strong> Your booking is confirmed! Please arrive on time for your event. 
                        If you need to make any changes, contact the admin immediately.
                    </p>
                </div>
                <?php elseif ($status === 'rejected'): ?>
                <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545;">
                    <p style="margin: 0; color: #721c24;">
                        <strong>ℹ️ What's Next:</strong> You can submit a new booking request with different dates/times, 
                        or contact the admin for more information about available slots.
                    </p>
                </div>
                <?php endif; ?>
                
                <p>If you have any questions or concerns, please contact our support team.</p>
            </div>
            
            <div class="footer">
                <p>
                    <strong>VTU Booking System</strong><br>
                    📧 <a href="mailto:support@vtubooking.com">support@vtubooking.com</a> | 
                    📱 Contact Admin for assistance
                </p>
                <p style="margin-top: 15px; font-size: 12px;">
                    This is an automated email. Please do not reply directly to this message.<br>
                    &copy; <?= date('Y') ?> VTU Booking System. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Create plain text email version
 */
function createPlainTextEmail(array $b, string $status, string $comment): string {
    $statusText = ucfirst($status);
    $icon = $status === 'approved' ? '[APPROVED]' : ($status === 'rejected' ? '[REJECTED]' : '[PENDING]');
    
    $text = "{$icon} BOOKING {$statusText}\n";
    $text .= str_repeat('=', 50) . "\n\n";
    
    $text .= "Dear " . $b['full_name'] . ",\n\n";
    $text .= "Your booking request has been {$statusText}.\n\n";
    
    $text .= "BOOKING DETAILS:\n";
    $text .= str_repeat('-', 20) . "\n";
    $text .= "Booking ID: #" . $b['booking_id'] . "\n";
    $text .= "Event: " . $b['event_name'] . "\n";
    $text .= "Venue: " . $b['venue_name'] . "\n";
    $text .= "Date: " . date('l, F j, Y', strtotime($b['event_date'])) . "\n";
    $text .= "Time: " . date('g:i A', strtotime($b['start_time'])) . " - " . date('g:i A', strtotime($b['end_time'])) . "\n\n";
    
    if (!empty($comment)) {
        $text .= "ADMIN MESSAGE:\n";
        $text .= str_repeat('-', 15) . "\n";
        $text .= $comment . "\n\n";
    }
    
    if ($status === 'approved') {
        $text .= "NEXT STEPS:\n";
        $text .= "Your booking is confirmed! Please arrive on time for your event.\n\n";
    } elseif ($status === 'rejected') {
        $text .= "WHAT'S NEXT:\n";
        $text .= "You can submit a new booking request with different dates/times.\n\n";
    }
    
    $text .= "For questions, contact: support@vtubooking.com\n\n";
    $text .= "---\n";
    $text .= "VTU Booking System\n";
    $text .= "This is an automated email. Please do not reply.\n";
    $text .= "© " . date('Y') . " VTU Booking System";
    
    return $text;
}

/**
 * Get status color
 */
function getStatusColor(string $status): string {
    return match($status) {
        'approved' => '#28a745',
        'rejected' => '#dc3545',
        'pending' => '#ffc107',
        default => '#6c757d'
    };
}

/**
 * Get status icon
 */
function getStatusIcon(string $status): string {
    return match($status) {
        'approved' => '✅',
        'rejected' => '❌',
        'pending' => '⏳',
        default => '📋'
    };
}

/**
 * Log email attempts with detailed information
 */
function logEmailAttempt(string $email, string $status, bool $sent, string $error = ''): void {
    $logFile = __DIR__ . '/logs/email_log.txt';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $result = $sent ? 'SUCCESS' : 'FAILED';
    $logEntry = "[{$timestamp}] Email {$result}: {$email} - Status: {$status}";
    
    if (!$sent && !empty($error)) {
        $logEntry .= " - Error: {$error}";
    }
    
    $logEntry .= PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Send test email to verify configuration
 */
function sendTestEmail(string $toEmail): bool {
    $testBooking = [
        'email' => $toEmail,
        'full_name' => 'Test User',
        'venue_name' => 'Test Venue',
        'event_name' => 'Test Event',
        'event_date' => date('Y-m-d'),
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'booking_id' => '999'
    ];
    
    return sendBookingStatusEmail($testBooking, 'approved', 'This is a test email to verify the configuration.');
}

/**
 * Get email statistics
 */
function getEmailStats(): array {
    $logFile = __DIR__ . '/logs/email_log.txt';
    
    if (!file_exists($logFile)) {
        return ['total' => 0, 'success' => 0, 'failed' => 0];
    }
    
    $content = file_get_contents($logFile);
    $lines = explode(PHP_EOL, $content);
    
    $stats = ['total' => 0, 'success' => 0, 'failed' => 0];
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        $stats['total']++;
        if (strpos($line, 'SUCCESS') !== false) {
            $stats['success']++;
        } elseif (strpos($line, 'FAILED') !== false) {
            $stats['failed']++;
        }
    }
    
    return $stats;
}
?>
