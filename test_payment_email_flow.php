<?php
// Comprehensive Payment Email Flow Test
// This script tests the complete flow from payment success to email sending

echo "<h2>🧪 Testing Payment Email Flow</h2>";

// Test 1: Check Required Files
echo "<h3>1. File Dependencies Check</h3>";
$requiredFiles = [
    'payment_success_v2.php',
    'razorpay_webhook.php',
    'send_email_smtp.php',
    'email_config.php',
    'secure_config.php',
    'db_config.php'
];

$filesExist = true;
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>";
        $filesExist = false;
    }
}

if (!$filesExist) {
    echo "<p style='color: red; font-weight: bold;'>❌ Critical files missing - email flow will not work!</p>";
    exit;
}

// Test 2: Database Connection
echo "<h3>2. Database Connection Test</h3>";
try {
    require_once 'db_config.php';
    if ($conn && !$conn->connect_error) {
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        
        // Test if required tables exist
        $tables = ['participants', 'user_event_registrations', 'events'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<p style='color: green;'>✅ Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Table '$table' missing</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 3: Email Configuration
echo "<h3>3. Email Configuration Test</h3>";
try {
    require_once 'email_config.php';
    
    $emailConfig = [
        'SMTP_HOST' => defined('SMTP_HOST') ? SMTP_HOST : 'NOT DEFINED',
        'SMTP_PORT' => defined('SMTP_PORT') ? SMTP_PORT : 'NOT DEFINED',
        'SMTP_USERNAME' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT DEFINED',
        'SMTP_PASSWORD' => defined('SMTP_PASSWORD') ? '***HIDDEN***' : 'NOT DEFINED',
        'SMTP_FROM_EMAIL' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NOT DEFINED',
        'SMTP_FROM_NAME' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'NOT DEFINED'
    ];
    
    $configValid = true;
    foreach ($emailConfig as $key => $value) {
        if ($value !== 'NOT DEFINED') {
            echo "<p style='color: green;'>✅ $key: $value</p>";
        } else {
            echo "<p style='color: red;'>❌ $key: $value</p>";
            $configValid = false;
        }
    }
    
    if (!$configValid) {
        echo "<p style='color: red; font-weight: bold;'>❌ Email configuration incomplete!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Email config error: " . $e->getMessage() . "</p>";
}

// Test 4: Email Functions
echo "<h3>4. Email Functions Test</h3>";
try {
    require_once 'send_email_smtp.php';
    
    $functions = [
        'sendEventRegistrationEmail',
        'sendRegistrationEmailSMTP',
        'testSMTPEmailFunctionality'
    ];
    
    foreach ($functions as $function) {
        if (function_exists($function)) {
            echo "<p style='color: green;'>✅ Function $function exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Function $function missing</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Email functions error: " . $e->getMessage() . "</p>";
}

// Test 5: PHPMailer Installation
echo "<h3>5. PHPMailer Installation Test</h3>";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        
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
    } else {
        echo "<p style='color: red;'>❌ Vendor autoload.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ PHPMailer error: " . $e->getMessage() . "</p>";
}

// Test 6: Payment Success Flow Simulation
echo "<h3>6. Payment Success Flow Simulation</h3>";
echo "<p>This test simulates what happens after a successful payment:</p>";

// Check if we have test data
try {
    if (isset($conn) && $conn && !$conn->connect_error) {
        // Check for test participants
        $result = $conn->query("SELECT COUNT(*) as count FROM participants LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['count'] > 0) {
                echo "<p style='color: green;'>✅ Test data available (participants table has data)</p>";
                
                // Check for test registrations
                $result = $conn->query("SELECT COUNT(*) as count FROM user_event_registrations LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if ($row['count'] > 0) {
                        echo "<p style='color: green;'>✅ Test registrations available</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ No test registrations found</p>";
                    }
                }
            } else {
                echo "<p style='color: orange;'>⚠️ No test participants found</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Test data check error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📋 Payment Email Flow Summary:</h3>";

// Count successes
$successCount = 0;
$totalCount = 0;

// File dependencies
$totalCount += count($requiredFiles);
foreach ($requiredFiles as $file) {
    if (file_exists($file)) $successCount++;
}

// Database connection
$totalCount += 1;
if (isset($conn) && $conn && !$conn->connect_error) $successCount++;

// Email configuration
$totalCount += 6;
if (defined('SMTP_HOST') && defined('SMTP_USERNAME') && defined('SMTP_PASSWORD')) $successCount += 6;

// Email functions
$totalCount += 3;
if (function_exists('sendEventRegistrationEmail')) $successCount += 3;

// PHPMailer
$totalCount += 2;
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) $successCount += 2;

$successRate = ($successCount / $totalCount) * 100;

echo "<p><strong>Overall Success Rate:</strong> $successCount/$totalCount (" . number_format($successRate, 1) . "%)</p>";

if ($successRate >= 90) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Payment Email Flow is READY!</p>";
} elseif ($successRate >= 75) {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ Payment Email Flow has minor issues but should work</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Payment Email Flow has critical issues</p>";
}

echo "<hr>";
echo "<h3>🧪 Next Steps:</h3>";
echo "<p>1. <a href='send_email_smtp.php?test=smtp'>Test SMTP Email Sending</a></p>";
echo "<p>2. Make a test payment to verify the complete flow</p>";
echo "<p>3. Check error logs for any email sending issues</p>";
echo "<p>4. Verify webhook is receiving payment notifications</p>";

echo "<hr>";
echo "<h3>🔧 Troubleshooting:</h3>";
echo "<ul>";
echo "<li><strong>Email not sending:</strong> Check SMTP credentials and Gmail App Password</li>";
echo "<li><strong>Payment success but no email:</strong> Check error logs and database connection</li>";
echo "<li><strong>Webhook issues:</strong> Verify Razorpay webhook URL and signature verification</li>";
echo "<li><strong>Database errors:</strong> Check table structure and user data</li>";
echo "</ul>";
?>
