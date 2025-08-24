<?php
session_start();
require_once __DIR__ . '/db_config.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    header('Location: admin_login.html?error=empty_fields');
    exit;
}

// Check admin credentials
try {
    $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
    
    if ($admin && md5($password) === $admin['password']) {
        // Admin login successful
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_id'] = $admin['id'];
        
        // Redirect to admin dashboard
        header('Location: admin_dashboard.php');
        exit;
    } else {
        // Invalid credentials
        header('Location: admin_login.html?error=invalid_credentials');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    header('Location: admin_login.html?error=system_error');
    exit;
}
?>
