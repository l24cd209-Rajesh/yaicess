<?php
session_start();
require_once __DIR__ . '/db_config.php';

// Log the payment failure
$failureData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'user_id' => $_SESSION['user_id'] ?? 'unknown',
    'error_code' => $_GET['error_code'] ?? 'unknown',
    'error_description' => $_GET['error_description'] ?? 'Payment failed',
    'payment_id' => $_GET['razorpay_payment_id'] ?? 'unknown',
    'amount' => $_GET['amount'] ?? 'unknown'
];

error_log("Payment failure: " . json_encode($failureData));

// Construct user-friendly error message
$errorMessages = [
    'BAD_REQUEST_ERROR' => 'Invalid payment request. Please try again.',
    'RATE_LIMIT_ERROR' => 'Too many payment attempts. Please wait a moment.',
    'AUTHENTICATION_ERROR' => 'Payment authentication failed. Please try again.',
    'INVALID_PAYMENT_ID' => 'Invalid payment ID. Please try again.',
    'PAYMENT_FAILED' => 'Payment was declined. Please check your payment method.',
    'CARD_DECLINED' => 'Your card was declined. Please try a different payment method.',
    'INSUFFICIENT_FUNDS' => 'Insufficient funds. Please try a different payment method.',
    'EXPIRED_CARD' => 'Your card has expired. Please use a different card.',
    'INVALID_CARD' => 'Invalid card details. Please check and try again.',
    'NETWORK_ERROR' => 'Network error occurred. Please try again.',
    'TIMEOUT_ERROR' => 'Payment timeout. Please try again.',
    'GATEWAY_ERROR' => 'Payment gateway error. Please try again later.'
];

$errorCode = $_GET['error_code'] ?? 'PAYMENT_FAILED';
$userMessage = $errorMessages[$errorCode] ?? 'Payment failed. Please try again.';

// Store error message in session
$_SESSION['payment_error'] = $userMessage;

// Redirect to user dashboard
header('Location: user_dashboard.php');
exit;
?>
