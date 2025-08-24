<?php
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/razorpay_config.php';

if (!isset($_SESSION['user_id'])) { 
    error_log("User not logged in - attempting to access event_payment.php");
    header('Location: user_login.php'); 
    exit; 
}

$userId = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$fullname = $_SESSION['fullname'] ?? '';

if (empty($username)) {
    error_log("Username is empty in session for user_id: {$userId}");
    die('Session error: Username not found. Please login again.');
}

// Get user details from participants table
$userEmail = '';
$userPhone = '';
$stmt = $conn->prepare('SELECT email, phone FROM participants WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($userEmail, $userPhone);
$stmt->fetch();
$stmt->close();

$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// Validate event_id parameter
if (!isset($_GET['event_id'])) {
    error_log("event_id parameter missing in URL");
    $_SESSION['payment_error'] = 'Event ID is missing. Please select an event from the events page.';
    header('Location: events.php');
    exit;
}

if ($eventId <= 0) { 
    error_log("Invalid event_id parameter: " . ($_GET['event_id'] ?? 'not set') . " (converted to: {$eventId})");
    $_SESSION['payment_error'] = 'Invalid event ID. Please select an event from the events page.';
    header('Location: events.php');
    exit;
}

// Optional: Check if user came from events page (basic security)
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
if (empty($referrer) || (strpos($referrer, 'events.php') === false && strpos($referrer, 'localhost') === false)) {
    error_log("Payment page accessed directly without proper referrer: {$referrer}");
    // Don't die here, just log for security monitoring
}

// Debug session information (moved here after $eventId is defined)
error_log("Payment attempt - User ID: {$userId}, Username: {$username}, Event ID: {$eventId}");

// Check if Razorpay is configured
if (!isRazorpayConfigured()) {
    error_log("Razorpay configuration error in event_payment.php");
    die('Razorpay is not properly configured. Please check your API keys.');
}

// Fetch event
$stmt = $conn->prepare('SELECT id, name, description, amount, currency FROM events WHERE id = ? AND is_active = 1');
$stmt->bind_param('i', $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) { 
    error_log("Event not found: event_id = {$eventId}");
    die('Event not found or not active'); 
}

$eventName = htmlspecialchars($event['name']);

// Validate event data before proceeding
if (empty($event['amount']) || $event['amount'] <= 0) {
    error_log("Invalid event amount: {$event['amount']} for event_id: {$eventId}");
    die('Invalid event configuration. Please contact support.');
}

if (empty($event['currency'])) {
    error_log("Missing event currency for event_id: {$eventId}");
    die('Invalid event configuration. Please contact support.');
}

error_log("Event validation passed - Name: {$eventName}, Amount: {$event['amount']}, Currency: {$event['currency']}");

// Function to generate unique registration code
function generateRegistrationCode($conn) {
    do {
        $code = 'REG' . strtoupper(substr(md5(uniqid() . time()), 0, 8));
        $stmt = $conn->prepare('SELECT id FROM user_event_registrations WHERE registration_code = ?');
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
    } while ($exists);
    return $code;
}

// IMPORTANT: Check if user already has a registration for this event
$existingRegistration = null;
$stmt = $conn->prepare('SELECT id, payment_status, order_id, registration_code FROM user_event_registrations WHERE username = ? AND event_name = ? ORDER BY id DESC LIMIT 1');
$stmt->bind_param('ss', $username, $eventName);
$stmt->execute();
$result = $stmt->get_result();
$existingRegistration = $result->fetch_assoc();
$stmt->close();

// If user already has a successful registration, redirect with message
if ($existingRegistration && $existingRegistration['payment_status'] === 'successful') {
    $_SESSION['payment_error'] = "You are already registered for this event with successful payment.";
    header('Location: user_dashboard.php');
    exit;
}

// If user has a pending registration, use that instead of creating a new one
if ($existingRegistration && $existingRegistration['payment_status'] === 'pending') {
    $registrationId = $existingRegistration['id'];
    $orderId = $existingRegistration['order_id'];
    $registrationCode = $existingRegistration['registration_code'];
    
    // Check if the existing order is still valid (not expired)
    if ($orderId) {
        try {
            $api = getRazorpayApi();
            $order = $api->order->fetch($orderId);
            
            // If order is still valid, use it
            if ($order && $order->status === 'created') {
                // Use existing registration and order
                $_SESSION['pending_registration'] = [
                    'user_id' => $userId,
                    'username' => $username,
                    'event_id' => $eventId,
                    'registration_id' => $registrationId,
                    'registration_code' => $registrationCode,
                    'event_name' => $event['name'],
                    'amount' => $event['amount'],
                    'order_id' => $orderId
                ];
                
                // Continue to payment page with existing registration
            } else {
                // Order expired, create new one
                error_log("Attempting to create new Razorpay order (expired order) - Amount: {$event['amount']}, Currency: {$event['currency']}");
                $order = createRazorpayOrder($event['amount'], $event['currency'], 'reg_' . time());
                if (!$order) {
                    error_log("Failed to create Razorpay order (expired order) for event_id: {$eventId}, user_id: {$userId}, amount: {$event['amount']}");
                    error_log("createRazorpayOrder returned false - this indicates the function failed internally");
                    die('Failed to create payment order. Please try again. If the problem persists, contact support.');
                }
                $orderId = $order->id;
                
                // Update existing registration with new order_id
                $stmt = $conn->prepare('UPDATE user_event_registrations SET order_id = ? WHERE id = ?');
                $stmt->bind_param('si', $orderId, $registrationId);
                $stmt->execute();
                $stmt->close();
                
                // Update session data
                $_SESSION['pending_registration'] = [
                    'user_id' => $userId,
                    'username' => $username,
                    'event_id' => $eventId,
                    'registration_id' => $registrationId,
                    'registration_code' => $registrationCode,
                    'event_name' => $event['name'],
                    'amount' => $event['amount'],
                    'order_id' => $orderId
                ];
            }
        } catch (Exception $e) {
            // If there's an error with existing order, create new one
            error_log("Exception with existing order: " . $e->getMessage() . " - Creating new order");
            error_log("Attempting to create new Razorpay order (error with existing) - Amount: {$event['amount']}, Currency: {$event['currency']}");
            $order = createRazorpayOrder($event['amount'], $event['currency'], 'reg_' . time());
            if (!$order) {
                error_log("Failed to create Razorpay order (error with existing) for event_id: {$eventId}, user_id: {$userId}, amount: {$event['amount']}");
                error_log("createRazorpayOrder returned false - this indicates the function failed internally");
                die('Failed to create payment order. Please try again. If the problem persists, contact support.');
            }
            $orderId = $order->id;
            
            // Update existing registration with new order_id
            $stmt = $conn->prepare('UPDATE user_event_registrations SET order_id = ? WHERE id = ?');
            $stmt->bind_param('si', $orderId, $registrationId);
            $stmt->execute();
            $stmt->close();
            
            // Update session data
            $_SESSION['pending_registration'] = [
                'user_id' => $userId,
                'username' => $username,
                'event_id' => $eventId,
                'registration_id' => $registrationId,
                'registration_code' => $registrationCode,
                'event_name' => $event['name'],
                'amount' => $event['amount'],
                'order_id' => $orderId
            ];
        }
    } else {
        // No order_id, create new order
        error_log("No order_id found - creating new Razorpay order - Amount: {$event['amount']}, Currency: {$event['currency']}");
        $order = createRazorpayOrder($event['amount'], $event['currency'], 'reg_' . time());
        if (!$order) {
            error_log("Failed to create Razorpay order (no order_id) for event_id: {$eventId}, user_id: {$userId}, amount: {$event['amount']}");
            error_log("createRazorpayOrder returned false - this indicates the function failed internally");
            die('Failed to create payment order. Please try again. If the problem persists, contact support.');
        }
        $orderId = $order->id;
        
        // Update existing registration with new order_id
        $stmt = $conn->prepare('UPDATE user_event_registrations SET order_id = ? WHERE id = ?');
        $stmt->bind_param('si', $orderId, $registrationId);
        $stmt->execute();
        $stmt->close();
        
        // Update session data
        $_SESSION['pending_registration'] = [
            'user_id' => $userId,
            'username' => $username,
            'event_id' => $eventId,
            'registration_id' => $registrationId,
            'registration_code' => $registrationCode,
            'event_name' => $event['name'],
            'amount' => $event['amount'],
            'order_id' => $orderId
        ];
    }
} else {
    // No existing registration, create new one
    error_log("No existing registration found - creating new Razorpay order - Amount: {$event['amount']}, Currency: {$event['currency']}");
    $order = createRazorpayOrder($event['amount'], $event['currency'], 'reg_' . time());
    if (!$order) {
        error_log("Failed to create Razorpay order for event_id: {$eventId}, user_id: {$userId}, amount: {$event['amount']}");
        error_log("createRazorpayOrder returned false - this indicates the function failed internally");
        die('Failed to create payment order. Please try again. If the problem persists, contact support.');
    }
    $orderId = $order->id;
    
    // Generate unique registration code
    $registrationCode = generateRegistrationCode($conn);
    
    // Create new DB record (pending) with username and registration_code included
    $stmt = $conn->prepare('INSERT INTO user_event_registrations (username, event_id, registration_code, event_name, amount, payment_status, order_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $payment_status = 'pending';
    $stmt->bind_param('sissdss', $username, $eventId, $registrationCode, $eventName, $event['amount'], $payment_status, $orderId);
    $stmt->execute();
    $registrationId = $stmt->insert_id;
    $stmt->close();
    
    // Store registration data for payment processing
    $_SESSION['pending_registration'] = [
        'user_id' => $userId,
        'username' => $username,
        'event_id' => $eventId,
        'registration_id' => $registrationId,
        'registration_code' => $registrationCode,
        'event_name' => $event['name'],
        'amount' => $event['amount'],
        'order_id' => $orderId
    ];
}

// Ensure registration_code is set in session
if ($registrationCode) {
    $_SESSION['registration_code'] = $registrationCode;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Payment - <?php echo $eventName; ?></title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f5f7fa; 
            margin: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
        }
        .card { 
            background: #fff; 
            padding: 28px; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(0,0,0,.1); 
            max-width: 520px; 
            width: 100%; 
            text-align: center; 
        }
        .btn { 
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); 
            color: #fff; 
            border: none; 
            padding: 15px 30px; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            margin-top: 20px; 
            width: 100%; 
        }
        .btn:hover { opacity: 0.9; }
        .amount { 
            font-size: 24px; 
            font-weight: bold; 
            color: #0d47a1; 
            margin: 20px 0; 
        }
        .event-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: left;
        }
        .event-details h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .event-details p {
            margin: 5px 0;
            color: #666;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            text-align: left;
        }
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .info-box p {
            margin: 5px 0;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Complete Payment for Event</h2>
        
        <?php if ($existingRegistration && $existingRegistration['payment_status'] === 'pending'): ?>
        <div class="info-box">
            <h4>ℹ️ Existing Registration Found</h4>
            <p>You already have a pending registration for this event. Completing this payment will finalize your registration.</p>
        </div>
        <?php endif; ?>
        
        <div class="event-details">
            <h3><?php echo $eventName; ?></h3>
            <p><strong>Amount:</strong> ₹<?php echo number_format($event['amount'], 2); ?> <?php echo htmlspecialchars($event['currency']); ?></p>
            <p><strong>Registration Code:</strong> <?php echo htmlspecialchars($registrationCode); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
        </div>
        
        <button class="btn" onclick="initiatePayment()">Pay Now ₹<?php echo number_format($event['amount'], 2); ?></button>
        
        <p style="margin-top: 20px; font-size: 14px; color: #666;">
            Click the button above to proceed with secure payment via Razorpay.
        </p>
    </div>

    <script>
        function initiatePayment() {
            const options = {
                key: '<?php echo getRazorpayKeyId(); ?>',
                amount: <?php echo $event['amount'] * 100; ?>, // Amount in paise
                currency: '<?php echo $event['currency']; ?>',
                name: 'YAICESS Solutions',
                description: '<?php echo addslashes($eventName); ?> Registration',
                order_id: '<?php echo $orderId; ?>',
                prefill: {
                    name: '<?php echo addslashes($fullname); ?>',
                    email: '<?php echo addslashes($userEmail); ?>',
                    contact: '<?php echo addslashes($userPhone); ?>'
                },
                theme: {
                    color: '#667eea'
                },
                handler: function(response) {
                    // Payment successful - pass all required parameters for signature verification
                    window.location.href = 'payment_success_v2.php?registration_id=<?php echo $registrationId; ?>&payment_id=' + response.razorpay_payment_id + '&order_id=<?php echo $orderId; ?>&signature=' + response.razorpay_signature;
                },
                modal: {
                    ondismiss: function() {
                        // User closed the payment modal
                        if (confirm('Payment was cancelled. Do you want to try again?')) {
                            // Reload the page to try again
                            window.location.reload();
                        }
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();
        }
    </script>
</body>
</html>

