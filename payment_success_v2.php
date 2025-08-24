<?php
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/razorpay_config.php';
require_once __DIR__ . '/send_email_smtp.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$registrationCode = $_SESSION['registration_code'] ?? '';
$registrationId = isset($_GET['registration_id']) ? (int)$_GET['registration_id'] : 0;
$paymentId = $_GET['payment_id'] ?? '';
$orderId = $_GET['order_id'] ?? '';
$signature = $_GET['signature'] ?? '';

// Log all received parameters for debugging
error_log("Payment success V2 - All GET parameters: " . json_encode($_GET));
error_log("Payment success V2 - User ID: $userId, Username: $username, Registration Code: $registrationCode, Registration ID: $registrationId");

$success = false;
$message = '';

try {
    // Verify the registration exists and belongs to this user
    $stmt = $conn->prepare("
        SELECT id, username, event_name, amount, payment_status, registration_code
        FROM user_event_registrations
        WHERE id = ? AND username = ? AND payment_status = 'pending'
    ");
    $stmt->bind_param("is", $registrationId, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $registration = $result->fetch_assoc();
    $stmt->close();
    
    if (!$registration) {
        error_log("Payment error: No pending registration found for ID: $registrationId, Username: $username");
        $_SESSION['payment_error'] = "No pending registration found. Please try registering again.";
        header('Location: user_dashboard.php');
        exit;
    }
    
    // Verify registration code matches
    if ($registration['registration_code'] !== $registrationCode) {
        error_log("Payment error: Registration code mismatch for ID: $registrationId, Expected: $registrationCode, Found: " . $registration['registration_code']);
        $_SESSION['payment_error'] = "Registration verification failed. Please try registering again.";
        header('Location: user_dashboard.php');
        exit;
    }
    
    // If payment details are provided, verify with Razorpay
    if ($paymentId && $orderId && $signature) {
        // Verify payment signature - Razorpay requires order_id for verification
        $attributes = [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature
        ];
        
        if (verifyPaymentSignature($attributes)) {
            error_log("Payment signature verified successfully for payment ID: $paymentId");
            
            // Payment is successful, update registration immediately
            $stmt = $conn->prepare("
                UPDATE user_event_registrations 
                SET payment_status = 'successful', payment_id = ?
                WHERE id = ? AND payment_status = 'pending'
            ");
            $stmt->bind_param("si", $paymentId, $registrationId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success = true;
                $eventName = $registration['event_name'];
                
                // Set success message immediately
                $_SESSION['payment_success'] = "Payment successful! You have been registered for $eventName.";
                $_SESSION['event_name'] = $eventName;
                error_log("Payment success - Registration ID: $registrationId, Event: $eventName, Payment ID: $paymentId");
                
                // 🔧 FIX: SEND CONFIRMATION EMAIL AFTER SUCCESSFUL PAYMENT
                try {
                    // Get user's email address
                    $userStmt = $conn->prepare("SELECT email, fullname FROM participants WHERE username = ?");
                    $userStmt->bind_param("s", $username);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result();
                    $user = $userResult->fetch_assoc();
                    $userStmt->close();
                    
                    if ($user && !empty($user['email'])) {
                        error_log("Sending confirmation email to: " . $user['email'] . " for event: $eventName");
                        
                        // Send confirmation email
                        $emailResult = sendEventRegistrationEmail(
                            $user['fullname'] ?: $username,
                            $user['email'],
                            $eventName,
                            $paymentId
                        );
                        
                        if ($emailResult['success']) {
                            error_log("✅ Confirmation email sent successfully to: " . $user['email']);
                            $_SESSION['email_sent'] = true;
                        } else {
                            error_log("❌ Failed to send confirmation email: " . ($emailResult['error'] ?? 'Unknown error'));
                            $_SESSION['email_error'] = "Payment successful but confirmation email failed to send. Please check your email or contact support.";
                        }
                    } else {
                        error_log("⚠️ User email not found for username: $username - cannot send confirmation email");
                        $_SESSION['email_warning'] = "Payment successful but we couldn't send a confirmation email. Please contact support.";
                    }
                    
                } catch (Exception $emailException) {
                    error_log("❌ Exception while sending confirmation email: " . $emailException->getMessage());
                    $_SESSION['email_error'] = "Payment successful but there was an issue sending the confirmation email. Please contact support.";
                }
                
                // Redirect immediately for better user experience
                header('Location: user_dashboard.php');
                exit;
                
            } else {
                $_SESSION['payment_error'] = "Payment received but failed to update registration. Please contact support.";
                error_log("Failed to update registration for payment - Registration ID: $registrationId");
            }
            $stmt->close();
        } else {
            $_SESSION['payment_error'] = "Payment verification failed. Please contact support.";
            error_log("Payment signature verification failed for payment ID: $paymentId");
        }
    } else {
        // No payment details provided - this shouldn't happen in normal flow
        $_SESSION['payment_error'] = "Payment details missing. Please try the payment again.";
        error_log("Payment details missing - Registration ID: $registrationId");
    }
    
} catch (Exception $e) {
    $_SESSION['payment_error'] = "An error occurred while processing your payment. Please contact support.";
    error_log("Payment processing error: " . $e->getMessage());
}

// Redirect to user dashboard
header('Location: user_dashboard.php');
exit;
?>
