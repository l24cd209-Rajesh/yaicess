<?php
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/send_email_smtp.php';

function redirect_with_message($msg) {
  echo "<script>alert('" . addslashes($msg) . "'); window.history.back();</script>";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: userform.html');
  exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$referral = trim($_POST['referral'] ?? '');

if ($fullname === '' || $email === '' || $phone === '' || $username === '' || $password === '') {
  redirect_with_message('Please fill in all required fields.');
}

// Check for duplicate username or email
$stmt = $conn->prepare('SELECT id FROM participants WHERE username = ? OR email = ? LIMIT 1');
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $stmt->close();
  redirect_with_message('Username or email already exists. Please use a different one.');
}
$stmt->close();

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare('INSERT INTO participants (fullname, email, phone, username, password, referral) VALUES (?,?,?,?,?,?)');
$stmt->bind_param('ssssss', $fullname, $email, $phone, $username, $passwordHash, $referral);
if (!$stmt->execute()) {
  $stmt->close();
  redirect_with_message('Registration failed. Please try again.');
}
$userId = $stmt->insert_id;
$stmt->close();

// Generate and set user-friendly ID
$userFriendlyId = 'USER' . str_pad($userId, 4, '0', STR_PAD_LEFT);
$stmt = $conn->prepare('UPDATE participants SET user_code = ? WHERE id = ?');
$stmt->bind_param('si', $userFriendlyId, $userId);
$stmt->execute();
$stmt->close();

// Send registration email using SMTP
$emailSent = sendRegistrationEmailSMTP($fullname, $email, $userId);
if ($emailSent) {
    error_log("Registration email sent successfully to $email for user $fullname");
} else {
    error_log("Failed to send registration email to $email for user $fullname");
}

// Log in user
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
$_SESSION['fullname'] = $fullname;
$_SESSION['email'] = $email;

// Get the user code for display
$stmt = $conn->prepare('SELECT user_code FROM participants WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $_SESSION['user_code'] = $row['user_code'];
}
$stmt->close();

header('Location: registration_success.php');
exit;
?>

