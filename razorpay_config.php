<?php
// Razorpay Configuration for API Integration
// API keys for direct payment processing

// Razorpay API Keys
define('RAZORPAY_KEY_ID', 'rzp_test_wmXorGrUS1DOqu');
define('RAZORPAY_KEY_SECRET', '6heI0kLDDlpozc0QmKAGPqxV');

// Webhook Secret (for webhook verification)
define('RAZORPAY_WEBHOOK_SECRET', 'YOUR_WEBHOOK_SECRET');  // Set this from Razorpay Dashboard

// Payment Settings
define('PAYMENT_DESCRIPTION', 'YAICESS Innovation Conference 2K25 Registration');
define('PAYMENT_CURRENCY', 'INR');

/**
 * Check if Razorpay is properly configured
 */
function isRazorpayConfigured() {
    return !empty(RAZORPAY_KEY_ID) && !empty(RAZORPAY_KEY_SECRET);
}

/**
 * Check if test mode is enabled
 */
function isTestMode() {
    return strpos(RAZORPAY_KEY_ID, 'rzp_test_') === 0;
}

/**
 * Get Razorpay API instance
 */
function getRazorpayApi() {
    try {
        error_log("getRazorpayApi called - checking vendor/autoload.php");
        
        if (!file_exists('vendor/autoload.php')) {
            error_log("ERROR: vendor/autoload.php not found!");
            throw new Exception('Vendor autoload file not found');
        }
        
        require_once 'vendor/autoload.php';
        error_log("vendor/autoload.php loaded successfully");
        
        if (!class_exists('Razorpay\Api\Api')) {
            error_log("ERROR: Razorpay\Api\Api class not found after autoload!");
            throw new Exception('Razorpay API class not found');
        }
        
        error_log("Razorpay API class found, creating instance with key: " . substr(RAZORPAY_KEY_ID, 0, 10) . "...");
        $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
        error_log("Razorpay API instance created successfully");
        
        return $api;
        
    } catch (Exception $e) {
        error_log("getRazorpayApi failed: " . $e->getMessage());
        error_log("Exception details - File: " . $e->getFile() . ", Line: " . $e->getLine());
        throw $e; // Re-throw to be caught by createRazorpayOrder
    }
}

/**
 * Create a Razorpay order
 */
function createRazorpayOrder($amount, $currency = 'INR', $receipt = null) {
    try {
        error_log("createRazorpayOrder called with: amount={$amount}, currency={$currency}, receipt={$receipt}");
        
        $api = getRazorpayApi();
        error_log("Razorpay API instance created successfully");
        
        $orderData = [
            'receipt' => $receipt ?: 'receipt_' . time(),
            'amount' => $amount * 100, // Convert to paise
            'currency' => $currency,
            'notes' => [
                'description' => PAYMENT_DESCRIPTION
            ]
        ];
        
        error_log("Order data prepared: " . json_encode($orderData));
        
        $order = $api->order->create($orderData);
        error_log("Razorpay order created successfully: " . $order->id);
        return $order;
        
    } catch (Exception $e) {
        error_log("Razorpay order creation failed: " . $e->getMessage());
        error_log("Exception details - File: " . $e->getFile() . ", Line: " . $e->getLine());
        error_log("Exception code: " . $e->getCode());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

/**
 * Verify payment signature
 */
function verifyPaymentSignature($attributes) {
    try {
        $api = getRazorpayApi();
        $api->utility->verifyPaymentSignature($attributes);
        return true;
    } catch (Exception $e) {
        error_log("Payment signature verification failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Razorpay error message
 */
function getRazorpayErrorMessage($error) {
    if (is_object($error) && method_exists($error, 'getMessage')) {
        return $error->getMessage();
    }
    return 'Payment processing error occurred';
}

/**
 * Get API key ID
 */
function getRazorpayKeyId() {
    return RAZORPAY_KEY_ID;
}

/**
 * Get API key secret
 */
function getRazorpayKeySecret() {
    return RAZORPAY_KEY_SECRET;
}
?> 