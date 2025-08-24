<?php
require_once 'email_config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Enhanced SMTP email sender with comprehensive error handling
 * @param string $fullname User's full name
 * @param string $email User's email address
 * @param int $participant_id Registration ID
 * @return array Success status with detailed information
 */
function sendRegistrationEmailSMTP($fullname, $email, $participant_id) {
    // Validate email address first
    $emailValidation = validateEmailAddress($email);
    if (!$emailValidation['valid']) {
        logEmailError('sendRegistrationEmailSMTP', $emailValidation['error'], [
            'fullname' => $fullname,
            'email' => $email,
            'participant_id' => $participant_id
        ]);
        return [
            'success' => false,
            'error' => $emailValidation['error'],
            'error_code' => 'INVALID_EMAIL'
        ];
    }
    
    try {
        // Get email template
        $emailTemplate = getRegistrationEmailTemplate($fullname, $email, $participant_id);
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Enable debug output (set to 0 for production)
        $mail->SMTPDebug = defined('APP_DEBUG') && APP_DEBUG ? 2 : 0;
        
        // Set timeout for better reliability
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $fullname);
        $mail->addReplyTo(EVENT_CONTACT, 'YAICESS Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $emailTemplate['subject'];
        $mail->Body = $emailTemplate['html'];
        $mail->AltBody = $emailTemplate['text'];
        
        // Send email
        $mail->send();
        
        // Log success
        logEmailError('sendRegistrationEmailSMTP', 'SUCCESS', [
            'email' => $email,
            'participant_id' => $participant_id,
            'template_subject' => $emailTemplate['subject']
        ]);
        
        return [
            'success' => true,
            'message' => 'Email sent successfully',
            'email_id' => $mail->getLastMessageID(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        // Enhanced error logging
        $errorContext = [
            'fullname' => $fullname,
            'email' => $email,
            'participant_id' => $participant_id,
            'smtp_host' => SMTP_HOST,
            'smtp_port' => SMTP_PORT,
            'smtp_username' => SMTP_USERNAME,
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine()
        ];
        
        logEmailError('sendRegistrationEmailSMTP', $e->getMessage(), $errorContext);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'error_code' => 'SMTP_ERROR',
            'error_details' => $errorContext
        ];
    }
}

/**
 * Enhanced event registration email sender
 */
function sendEventRegistrationEmail($fullname, $email, $eventName, $paymentId) {
    // Validate email address
    $emailValidation = validateEmailAddress($email);
    if (!$emailValidation['valid']) {
        logEmailError('sendEventRegistrationEmail', $emailValidation['error'], [
            'fullname' => $fullname,
            'email' => $email,
            'eventName' => $eventName,
            'paymentId' => $paymentId
        ]);
        return [
            'success' => false,
            'error' => $emailValidation['error'],
            'error_code' => 'INVALID_EMAIL'
        ];
    }
    
    try {
        $subject = 'Event Registration Confirmed - ' . $eventName;
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = defined('APP_DEBUG') && APP_DEBUG ? 2 : 0;
        $mail->Timeout = 30;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $fullname);
        $mail->addReplyTo(EVENT_CONTACT, 'YAICESS Support');
        
        // Content
        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;line-height:1.6">'
              . '<h2 style="color:#28a745">Registration Confirmed</h2>'
              . '<p>Hi ' . htmlspecialchars($fullname) . ',</p>'
              . '<p>Your registration for <strong>' . htmlspecialchars($eventName) . '</strong> is confirmed.</p>'
              . '<p><strong>Payment ID:</strong> ' . htmlspecialchars($paymentId) . '</p>'
              . '<p>We look forward to seeing you at the event!</p>'
              . '<p>— YAICESS Team</p>'
              . '</div>';
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        
        $mail->send();
        
        // Log success
        logEmailError('sendEventRegistrationEmail', 'SUCCESS', [
            'email' => $email,
            'eventName' => $eventName,
            'paymentId' => $paymentId
        ]);
        
        return [
            'success' => true,
            'message' => 'Event confirmation email sent successfully',
            'email_id' => $mail->getLastMessageID(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        $errorContext = [
            'fullname' => $fullname,
            'email' => $email,
            'eventName' => $eventName,
            'paymentId' => $paymentId,
            'exception_message' => $e->getMessage()
        ];
        
        logEmailError('sendEventRegistrationEmail', $e->getMessage(), $errorContext);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'error_code' => 'SMTP_ERROR',
            'error_details' => $errorContext
        ];
    }
}

/**
 * Enhanced SMTP test function with detailed results
 */
function testSMTPEmailFunctionality($testEmail) {
    $result = sendRegistrationEmailSMTP('Test User', $testEmail, 'TEST123');
    
    if ($result['success']) {
        logEmailError('testSMTPEmailFunctionality', 'Test email sent successfully', [
            'test_email' => $testEmail,
            'email_id' => $result['email_id'] ?? 'N/A'
        ]);
    } else {
        logEmailError('testSMTPEmailFunctionality', 'Test email failed', [
            'test_email' => $testEmail,
            'error' => $result['error'],
            'error_code' => $result['error_code']
        ]);
    }
    
    return $result;
}

/**
 * Get SMTP configuration status
 */
function getSMTPConfigurationStatus() {
    $config = [
        'host' => SMTP_HOST,
        'port' => SMTP_PORT,
        'username' => SMTP_USERNAME,
        'from_email' => SMTP_FROM_EMAIL,
        'from_name' => SMTP_FROM_NAME,
        'secure' => SMTP_SECURE,
        'auth_enabled' => SMTP_AUTH
    ];
    
    // Check if using default credentials (security warning)
    $security_warning = false;
    if (SMTP_PASSWORD === 'xuuu ypub kudv aaur') {
        $security_warning = 'Using default Gmail App Password - consider using environment variables';
    }
    
    return [
        'config' => $config,
        'security_warning' => $security_warning,
        'environment_loaded' => file_exists(__DIR__ . '/.env')
    ];
}

// Test the SMTP email functionality
if (isset($_GET['test']) && $_GET['test'] === 'smtp') {
    echo "<h2>Testing Enhanced SMTP Email Configuration</h2>";
    
    // Show configuration status
    $configStatus = getSMTPConfigurationStatus();
    echo "<h3>Configuration Status:</h3>";
    echo "<ul>";
    foreach ($configStatus['config'] as $key => $value) {
        if ($key === 'username') {
            echo "<li><strong>$key:</strong> " . substr($value, 0, 3) . "***" . substr($value, -10) . "</li>";
        } else {
            echo "<li><strong>$key:</strong> $value</li>";
        }
    }
    echo "</ul>";
    
    if ($configStatus['security_warning']) {
        echo "<p style='color: orange;'>⚠️ <strong>Security Warning:</strong> " . $configStatus['security_warning'] . "</p>";
    }
    
    if ($configStatus['environment_loaded']) {
        echo "<p style='color: green;'>✅ Environment file (.env) detected</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ No environment file found - using default configuration</p>";
    }
    
    // Test email sending
    $testEmail = SMTP_USERNAME; // Send to configured email
    echo "<h3>Testing Email Sending:</h3>";
    $result = testSMTPEmailFunctionality($testEmail);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ SMTP Email test successful!</p>";
        echo "<p><strong>Email ID:</strong> " . ($result['email_id'] ?? 'N/A') . "</p>";
        echo "<p><strong>Timestamp:</strong> " . ($result['timestamp'] ?? 'N/A') . "</p>";
        echo "<p>Check your inbox at: $testEmail</p>";
    } else {
        echo "<p style='color: red;'>❌ SMTP Email test failed!</p>";
        echo "<p><strong>Error:</strong> " . ($result['error'] ?? 'Unknown error') . "</p>";
        echo "<p><strong>Error Code:</strong> " . ($result['error_code'] ?? 'N/A') . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>📋 Next Steps:</h3>";
    echo "<p>1. If test failed, check Gmail App Password and 2FA settings</p>";
    echo "<p>2. Create .env file for secure credential storage</p>";
    echo "<p>3. Check server firewall and network connectivity</p>";
}
?>
