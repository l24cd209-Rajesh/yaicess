<?php
// Secure Configuration Loader
// This file loads sensitive configuration from environment variables or secure config files

// Load environment variables if .env file exists
function loadEnvironmentVariables() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
}

// Load environment variables
loadEnvironmentVariables();

// Email Configuration with fallback to secure config
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? 'lovelyrajesh911@gmail.com');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? 'xuuu ypub kudv aaur');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'lovelyrajesh911@gmail.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'YAICESS Innovation Conference');
define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? 'tls');
define('SMTP_AUTH', $_ENV['SMTP_AUTH'] ?? true);

// Event Details
define('EVENT_NAME', 'YAICESS Innovation Conference 2K25');
define('EVENT_DATE', 'July 30, 2025');
define('EVENT_TIME', '10:00 AM - 6:00 PM');
define('EVENT_LOCATION', 'Hyderabad, India');
define('EVENT_CONTACT', 'info@techconf2025.com');

// Security check - warn if using default credentials
if (SMTP_PASSWORD === 'xuuu ypub kudv aaur') {
    error_log('WARNING: Using default Gmail App Password. Consider using environment variables for production.');
}
?>
