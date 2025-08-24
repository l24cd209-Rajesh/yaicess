<?php
// Comprehensive Email Functionality Test
echo "<h2>📧 Email Functionality Test Results</h2>";

// Test 1: Check PHPMailer Installation
echo "<h3>1. PHPMailer Installation Check</h3>";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        echo "<p style='color: green;'>✅ Vendor autoload.php found</p>";
        
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "<p style='color: green;'>✅ PHPMailer class found</p>";
        } else {
            echo "<p style='color: red;'>❌ PHPMailer class not found</p>";
        }
        
        if (class_exists('PHPMailer\PHPMailer\SMTP')) {
            echo "<p style='color: green;'>✅ PHPMailer SMTP class found</p>";
        } else {
            echo "<p style='color: red;'>❌ PHPMailer SMTP class not found</p>";
        }
        
        if (class_exists('PHPMailer\PHPMailer\Exception')) {
            echo "<p style='color: green;'>✅ PHPMailer Exception class found</p>";
        } else {
            echo "<p style='color: red;'>❌ PHPMailer Exception class not found</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Vendor autoload.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading PHPMailer: " . $e->getMessage() . "</p>";
}

// Test 2: Check Email Configuration
echo "<h3>2. Email Configuration Check</h3>";
try {
    require_once 'email_config.php';
    
    $config_vars = [
        'SMTP_HOST' => defined('SMTP_HOST') ? SMTP_HOST : 'NOT DEFINED',
        'SMTP_PORT' => defined('SMTP_PORT') ? SMTP_PORT : 'NOT DEFINED',
        'SMTP_USERNAME' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT DEFINED',
        'SMTP_PASSWORD' => defined('SMTP_PASSWORD') ? '***HIDDEN***' : 'NOT DEFINED',
        'SMTP_FROM_EMAIL' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NOT DEFINED',
        'SMTP_FROM_NAME' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'NOT DEFINED'
    ];
    
    foreach ($config_vars as $var => $value) {
        if ($value !== 'NOT DEFINED') {
            echo "<p style='color: green;'>✅ $var: $value</p>";
        } else {
            echo "<p style='color: red;'>❌ $var: $value</p>";
        }
    }
    
    // Check if email template function exists
    if (function_exists('getRegistrationEmailTemplate')) {
        echo "<p style='color: green;'>✅ Email template function found</p>";
    } else {
        echo "<p style='color: red;'>❌ Email template function not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading email config: " . $e->getMessage() . "</p>";
}

// Test 3: Check SMTP Email Functions
echo "<h3>3. SMTP Email Functions Check</h3>";
try {
    require_once 'send_email_smtp.php';
    
    $functions = [
        'sendRegistrationEmailSMTP',
        'sendEventRegistrationEmail',
        'testSMTPEmailFunctionality'
    ];
    
    foreach ($functions as $function) {
        if (function_exists($function)) {
            echo "<p style='color: green;'>✅ Function $function exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Function $function not found</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading SMTP functions: " . $e->getMessage() . "</p>";
}

// Test 4: Check Background Email Functionality
echo "<h3>4. Background Email Functionality Check</h3>";
if (file_exists('send_background_email.php')) {
    echo "<p style='color: green;'>✅ Background email script exists</p>";
} else {
    echo "<p style='color: red;'>❌ Background email script missing</p>";
}

// Test 5: Test Email Template Generation
echo "<h3>5. Email Template Test</h3>";
try {
    if (function_exists('getRegistrationEmailTemplate')) {
        $template = getRegistrationEmailTemplate('Test User', 'test@example.com', 'TEST123');
        
        if (isset($template['subject']) && isset($template['html']) && isset($template['text'])) {
            echo "<p style='color: green;'>✅ Email template generated successfully</p>";
            echo "<p><strong>Subject:</strong> " . htmlspecialchars($template['subject']) . "</p>";
            echo "<p><strong>HTML Length:</strong> " . strlen($template['html']) . " characters</p>";
            echo "<p><strong>Text Length:</strong> " . strlen($template['text']) . " characters</p>";
        } else {
            echo "<p style='color: red;'>❌ Email template generation failed - missing components</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Email template function not available</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Email template test failed: " . $e->getMessage() . "</p>";
}

// Test 6: Check Database Connection for Email Functions
echo "<h3>6. Database Connection for Email Check</h3>";
try {
    require_once 'db_config.php';
    if ($conn && !$conn->connect_error) {
        echo "<p style='color: green;'>✅ Database connection successful (required for email functions)</p>";
    } else {
        echo "<p style='color: red;'>❌ Database connection failed (email functions may not work)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📋 Email Functionality Summary:</h3>";

// Count successes and failures
$success_count = 0;
$total_count = 0;

// Simple success counter (you can enhance this)
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) $success_count++;
if (function_exists('getRegistrationEmailTemplate')) $success_count++;
if (function_exists('sendRegistrationEmailSMTP')) $success_count++;
if (file_exists('send_background_email.php')) $success_count++;

$total_count = 4;
$success_rate = ($success_count / $total_count) * 100;

echo "<p><strong>Success Rate:</strong> $success_count/$total_count ($success_rate%)</p>";

if ($success_rate >= 75) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Email functionality is ready to use!</p>";
} elseif ($success_rate >= 50) {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ Email functionality has some issues but may work</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Email functionality has critical issues</p>";
}

echo "<hr>";
echo "<h3>🧪 Test Email Sending:</h3>";
echo "<p><a href='send_email_smtp.php?test=smtp'>Click here to test SMTP email sending</a></p>";
echo "<p><strong>Note:</strong> This will send a test email to the configured email address</p>";

echo "<hr>";
echo "<h3>🔧 Common Email Issues & Solutions:</h3>";
echo "<ul>";
echo "<li><strong>Gmail App Password:</strong> Make sure you're using an App Password, not your regular password</li>";
echo "<li><strong>2FA Required:</strong> Gmail requires 2FA to be enabled for App Passwords</li>";
echo "<li><strong>SMTP Port:</strong> Use port 587 for TLS or 465 for SSL</li>";
echo "<li><strong>Firewall:</strong> Ensure your server can connect to Gmail SMTP servers</li>";
echo "<li><strong>Rate Limits:</strong> Gmail has daily sending limits</li>";
echo "</ul>";
?>
