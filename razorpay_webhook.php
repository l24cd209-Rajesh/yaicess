<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/send_email_smtp.php';

// Log webhook request
error_log("Webhook received: " . file_get_contents('php://input'));

// Get webhook data
$webhook_data = file_get_contents('php://input');
$event_json = json_decode($webhook_data, true);

if (!$event_json) {
    error_log("Webhook: Invalid JSON data received");
    http_response_code(400);
    echo "Invalid JSON";
    exit;
}

// Verify webhook signature if secret is configured
if (defined('RAZORPAY_WEBHOOK_SECRET') && RAZORPAY_WEBHOOK_SECRET !== 'YOUR_WEBHOOK_SECRET') {
    try {
        $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
        $expected_signature = hash_hmac('sha256', $webhook_data, RAZORPAY_WEBHOOK_SECRET);
        
        if (!hash_equals($expected_signature, $signature)) {
            error_log("Webhook: Signature verification failed");
            http_response_code(400);
            echo "Signature verification failed";
            exit;
        }
        error_log("Webhook: Signature verified successfully");
    } catch (Exception $e) {
        error_log("Webhook: Signature verification error: " . $e->getMessage());
        http_response_code(400);
        echo "Signature verification error";
        exit;
    }
} else {
    error_log("Webhook: Skipping signature verification (secret not configured)");
}

// Handle payment.captured event
if ($event_json['event'] == 'payment.captured') {
    $payment_id = $event_json['payload']['payment']['entity']['id'];
    $amount = $event_json['payload']['payment']['entity']['amount'];
    $email = $event_json['payload']['payment']['entity']['email'] ?? '';
    $contact = $event_json['payload']['payment']['entity']['contact'] ?? '';
    $status = $event_json['payload']['payment']['entity']['status'];
    $currency = $event_json['payload']['payment']['entity']['currency'] ?? 'INR';
    
    error_log("Payment captured - ID: $payment_id, Email: $email, Contact: $contact, Status: $status, Amount: $amount");
    
    // Only update if payment is successful
    if ($status == 'captured') {
        $updated = false;
        
        // First, check if this payment_id is already processed
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM user_event_registrations WHERE payment_id = ?");
        $checkStmt->bind_param('s', $payment_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($row['count'] > 0) {
            error_log("Webhook: Payment ID $payment_id already processed, skipping");
            $updated = true;
        } else {
            // Find the registration by amount and other details
            $registration = null;

            // Try to find by amount first
            if ($amount) {
                $stmt = $conn->prepare("
                    SELECT id, user_id, event_name, amount, payment_status
                    FROM user_event_registrations
                    WHERE ROUND(amount, 2) = ROUND(?, 2) AND payment_status = 'pending'
                    ORDER BY registration_date ASC
                    LIMIT 1
                ");
                $stmt->bind_param('d', $amount);
                $stmt->execute();
                $result = $stmt->get_result();
                $registration = $result->fetch_assoc();
                $stmt->close();
                
                if ($registration) {
                    error_log("Webhook: Found registration by amount: {$registration['user_id']} for event: {$registration['event_name']}");
                }
            }
            
            // Process the found registration
            if ($registration) {
                // Update the registration with payment details
                $updateStmt = $conn->prepare("
                    UPDATE user_event_registrations 
                    SET payment_status = 'successful', payment_id = ?
                    WHERE id = ? AND payment_status = 'pending'
                ");
                $updateStmt->bind_param("si", $payment_id, $registration['id']);
                $updateStmt->execute();
                
                if ($updateStmt->affected_rows > 0) {
                    error_log("Event registration updated successfully - ID: {$registration['id']}, User ID: {$registration['user_id']}, Event: {$registration['event_name']}, Payment ID: $payment_id");
                    $updated = true;
                } else {
                    error_log("Failed to update registration: {$registration['id']}");
                }
                $updateStmt->close();
            } else {
                error_log("Webhook: No matching registration found for payment ID: $payment_id, Amount: $amount");
            }
            
            // If still not updated, try by email
            if (!$updated && !empty($email)) {
                $userStmt = $conn->prepare("SELECT username, fullname FROM participants WHERE email = ?");
                $userStmt->bind_param("s", $email);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $user = $userResult->fetch_assoc();
                $userStmt->close();
                
                if ($user) {
                    $stmt = $conn->prepare("
                        UPDATE user_event_registrations 
                        SET payment_status = 'successful', payment_id = ? 
                        WHERE username = ? AND payment_status = 'pending' 
                        ORDER BY created_at ASC LIMIT 1
                    ");
                    $stmt->bind_param("ss", $payment_id, $user['username']);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        error_log("Event registration updated by email for user: " . $user['fullname'] . " with payment ID: $payment_id");
                        $updated = true;
                        
                        // Get event details for email
                        $eventStmt = $conn->prepare("
                            SELECT event_name FROM user_event_registrations 
                            WHERE username = ? AND payment_id = ?
                        ");
                        $eventStmt->bind_param("ss", $user['username'], $payment_id);
                        $eventStmt->execute();
                        $eventResult = $eventStmt->get_result();
                        $event = $eventResult->fetch_assoc();
                        $eventStmt->close();
                        
                        if ($event) {
                            // Enhanced email sending with better error handling
                            $emailResult = @sendEventRegistrationEmail($user['fullname'], $email, $event['event_name'], $payment_id);
                            if ($emailResult && $emailResult['success']) {
                                error_log("✅ Webhook: Confirmation email sent successfully to: " . $email . " for event: " . $event['event_name']);
                            } else {
                                error_log("❌ Webhook: Failed to send confirmation email to: " . $email . " - Error: " . ($emailResult['error'] ?? 'Unknown error'));
                            }
                        }
                    } else {
                        error_log("No pending event registration found for user by email: " . $user['fullname']);
                    }
                    $stmt->close();
                }
            }
            
            // If still not updated, try by phone number
            if (!$updated && !empty($contact)) {
                $userStmt = $conn->prepare("SELECT username, fullname FROM participants WHERE phone = ?");
                $userStmt->bind_param("s", $contact);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $user = $userResult->fetch_assoc();
                $userStmt->close();
                
                if ($user) {
                    $stmt = $conn->prepare("
                        UPDATE user_event_registrations 
                        SET payment_status = 'successful', payment_id = ? 
                        WHERE username = ? AND payment_status = 'pending' 
                        ORDER BY created_at ASC LIMIT 1
                    ");
                    $stmt->bind_param("ss", $payment_id, $user['username']);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        error_log("Event registration updated by phone for user: " . $user['fullname'] . " with payment ID: $payment_id");
                        $updated = true;
                        
                        // Get event details for email
                        $eventStmt = $conn->prepare("
                            SELECT event_name FROM user_event_registrations 
                            WHERE username = ? AND payment_id = ?
                        ");
                        $eventStmt->bind_param("ss", $user['username'], $payment_id);
                        $eventStmt->execute();
                        $eventResult = $eventStmt->get_result();
                        $event = $eventResult->fetch_assoc();
                        $eventStmt->close();
                        
                        if ($event) {
                            // Enhanced email sending with better error handling
                            $emailResult = @sendEventRegistrationEmail($user['fullname'], $email ?: '', $event['event_name'], $payment_id);
                            if ($emailResult && $emailResult['success']) {
                                error_log("✅ Webhook: Confirmation email sent successfully to: " . ($email ?: 'phone user') . " for event: " . $event['event_name']);
                            } else {
                                error_log("❌ Webhook: Failed to send confirmation email to: " . ($email ?: 'phone user') . " - Error: " . ($emailResult['error'] ?? 'Unknown error'));
                            }
                        }
                    }
                    $stmt->close();
                }
            }
        }
        
        if ($updated) {
            error_log("Webhook: Payment processed successfully for payment ID: $payment_id");
        } else {
            error_log("Webhook: Failed to process payment for payment ID: $payment_id");
        }
    }
}

// Handle payment.failed event
if ($event_json['event'] == 'payment.failed') {
    $payment_id = $event_json['payload']['payment']['entity']['id'];
    $error_code = $event_json['payload']['payment']['entity']['error_code'] ?? 'unknown';
    $error_description = $event_json['payload']['payment']['entity']['error_description'] ?? 'Payment failed';
    
    error_log("Payment failed - ID: $payment_id, Error: $error_code - $error_description");
}

// Respond to webhook
http_response_code(200);
echo "OK";
?> 