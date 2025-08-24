<?php
// Enhanced Background Email Sender
// This script can be called asynchronously to send confirmation emails
// Usage: POST request with user_id, event_name, payment_id

// Load required files
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/send_email_smtp.php';

// Set response headers for JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'error_code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

// Get parameters with validation
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$eventName = $_POST['event_name'] ?? '';
$paymentId = $_POST['payment_id'] ?? '';

// Validate required parameters
$errors = [];
if (!$userId) {
    $errors[] = 'User ID is required and must be a valid integer';
}
if (empty($eventName)) {
    $errors[] = 'Event name is required';
}
if (empty($paymentId)) {
    $errors[] = 'Payment ID is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters',
        'error_code' => 'MISSING_PARAMETERS',
        'details' => $errors
    ]);
    exit;
}

try {
    // Test database connection first
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
    }
    
    // Get user details for email
    $stmt = $conn->prepare("SELECT username, email FROM participants WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare database statement: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception('Failed to execute database query: ' . $stmt->error);
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found',
            'error_code' => 'USER_NOT_FOUND',
            'user_id' => $userId
        ]);
        exit;
    }
    
    // Validate email address
    if (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address for user: ' . $user['username']);
    }
    
    // Send confirmation email
    $emailResult = sendEventRegistrationEmail(
        $user['username'],
        $user['email'],
        $eventName,
        $paymentId
    );
    
    if ($emailResult['success']) {
        // Log success
        error_log("Background email sent successfully to {$user['email']} for event: $eventName");
        
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully',
            'email_id' => $emailResult['email_id'] ?? 'N/A',
            'timestamp' => $emailResult['timestamp'] ?? date('Y-m-d H:i:s'),
            'recipient' => $user['email'],
            'event_name' => $eventName
        ]);
    } else {
        // Log failure with detailed error information
        error_log("Background email failed for user: {$user['username']}, event: $eventName");
        error_log("Email error details: " . json_encode($emailResult));
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Email sending failed',
            'error_code' => 'EMAIL_SENDING_FAILED',
            'email_error' => $emailResult['error'] ?? 'Unknown email error',
            'email_error_code' => $emailResult['error_code'] ?? 'N/A',
            'recipient' => $user['email'],
            'event_name' => $eventName
        ]);
    }
    
} catch (Exception $e) {
    // Log comprehensive error information
    $errorContext = [
        'user_id' => $userId,
        'event_name' => $eventName,
        'payment_id' => $paymentId,
        'exception_message' => $e->getMessage(),
        'exception_file' => $e->getFile(),
        'exception_line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    error_log("Background email error: " . json_encode($errorContext));
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'error_code' => 'INTERNAL_ERROR',
        'details' => $errorContext
    ]);
}

// Close database connection
if (isset($conn) && $conn) {
    $conn->close();
}
?>
