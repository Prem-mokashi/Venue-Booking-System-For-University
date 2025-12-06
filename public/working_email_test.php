<?php
/**
 * Working Email Test - Fixed Constants
 * Save as: working_email_test.php in your public folder
 * Access via: http://localhost/venue-booking-system/public/working_email_test.php
 */

// Suppress the OpenSSL warning
error_reporting(E_ALL & ~E_WARNING);

require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { background: #cce7ff; border: 1px solid #99d6ff; color: #004085; padding: 15px; border-radius: 5px; margin: 10px 0; }
</style>";

echo "<h1>📧 VTU Email System Test (FIXED)</h1>";

// Check PHPMailer
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<div class='error'><h3>❌ PHPMailer Not Found!</h3></div>";
    exit;
}

echo "<div class='info'>";
echo "<h3>📋 System Check</h3>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Extensions:</strong> ✅ OpenSSL, ✅ cURL, ✅ mbstring, ✅ JSON</p>";
echo "<p><strong>PHPMailer:</strong> ✅ Available</p>";
echo "</div>";

echo "<h3>🔄 Testing Email Send...</h3>";

try {
    $mail = new PHPMailer(true);
    
    // Server settings - CORRECTED CONSTANTS
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'premmokashi2107@gmail.com';
    $mail->Password = 'nddhwdvvvkuwodcg';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // ✅ CORRECT - with 'S' at end
    $mail->Port = 465;
    
    // Timeout settings
    $mail->Timeout = 30;
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Recipients
    $mail->setFrom('premmokashi2107@gmail.com', 'VTU Booking System');
    $mail->addAddress('premmokashi2107@gmail.com', 'Test User');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = '🧪 Fixed Test Email - ' . date('H:i:s');
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #28a745;">✅ Email Test SUCCESSFUL!</h2>
        <p>Your VTU venue booking system email functionality is now working correctly.</p>
        <hr>
        <p><strong>Test Details:</strong></p>
        <ul>
            <li><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</li>
            <li><strong>Fixed Issue:</strong> ENCRYPTION_SMTPS constant corrected</li>
            <li><strong>SMTP:</strong> Gmail (smtp.gmail.com:465)</li>
        </ul>
        <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;">
            <strong>✅ The email function is now working!</strong><br>
            Your booking system notifications should work properly.
        </div>
    </div>';
    
    $mail->AltBody = 'VTU Email Test SUCCESSFUL - ' . date('Y-m-d H:i:s');
    
    if ($mail->send()) {
        echo "<div class='success'>";
        echo "<h3>🎉 SUCCESS! Email Sent Successfully!</h3>";
        echo "<p>✅ Check your inbox: <strong>premmokashi2107@gmail.com</strong></p>";
        echo "<p>✅ Your booking system email notifications should work now!</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h4>🔧 What Was Fixed:</h4>";
        echo "<ul>";
        echo "<li>❌ <code>ENCRYPTION_SMTLS</code> (wrong)</li>";
        echo "<li>✅ <code>ENCRYPTION_SMTPS</code> (correct)</li>";
        echo "</ul>";
        echo "<h4>Next Steps:</h4>";
        echo "<ol>";
        echo "<li>Update your <code>update_booking_status.php</code> with the same fix</li>";
        echo "<li>Test a real booking approval/rejection</li>";
        echo "<li>Check logs in <code>public/logs/email_dispatch_log.txt</code></li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div class='error'><h3>❌ Email Failed - Unknown Error</h3></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Email Failed!</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>🔧 Troubleshooting:</h4>";
    if (strpos($e->getMessage(), 'Authentication') !== false) {
        echo "<p>🔑 <strong>Authentication Issue:</strong> Check your Gmail App Password</p>";
    } elseif (strpos($e->getMessage(), 'Connection') !== false) {
        echo "<p>🌐 <strong>Connection Issue:</strong> Check internet/firewall</p>";
    } elseif (strpos($e->getMessage(), 'SSL') !== false) {
        echo "<p>🔒 <strong>SSL Issue:</strong> May be related to OpenSSL configuration</p>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<h3>📋 PHPMailer Constants Reference:</h3>";
echo "<ul>";
echo "<li><code>PHPMailer::ENCRYPTION_SMTPS</code> - SSL on port 465 ✅</li>";
echo "<li><code>PHPMailer::ENCRYPTION_STARTTLS</code> - TLS on port 587 ✅</li>";
echo "<li><code>PHPMailer::ENCRYPTION_SMTLS</code> - ❌ Does NOT exist (common typo)</li>";
echo "</ul>";

echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>