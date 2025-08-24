<?php
session_start();
require_once __DIR__ . '/db_config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identifier = trim($_POST['identifier'] ?? '');
  $password = $_POST['password'] ?? '';
  
  if ($identifier === '' || $password === '') {
    $error = 'Please enter email/username and password';
  } else {
    // Check if identifier is email or username and query accordingly
    $stmt = $conn->prepare('SELECT id, fullname, username, email, password FROM participants WHERE username = ? OR email = ? LIMIT 1');
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $stmt->bind_result($id, $fullname, $username, $email, $passwordHash);
    
    if ($stmt->fetch() && password_verify($password, $passwordHash)) {
      $_SESSION['user_id'] = $id;
      $_SESSION['username'] = $username;
      $_SESSION['email'] = $email;
      $_SESSION['fullname'] = $fullname;
      header('Location: user_dashboard.php');
      exit;
    } else {
      $error = 'Invalid email/username or password';
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Login</title>
  <style>
    *{box-sizing:border-box}body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;padding:20px}.login{background:#fff;padding:40px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,.2);width:100%;max-width:400px;position:relative;overflow:hidden}.login::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#667eea,#764ba2)}h1{margin:0 0 20px;color:#0d47a1}.form-group{margin-bottom:16px}label{display:block;margin-bottom:6px;font-weight:600;color:#333}input{width:100%;padding:12px 14px;border-radius:8px;border:2px solid #e1e5e9;background:#f8f9fa}input:focus{outline:none;border-color:#667eea;background:#fff;box-shadow:0 0 0 3px rgba(102,126,234,.1)}.btn{width:100%;padding:14px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer}.error{color:#dc3545;text-align:center;margin:10px 0}
  </style>
</head>
<body>
  <form class="login" method="post">
    <h1>User Login</h1>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <div class="form-group">
      <label for="identifier">Email or Username</label>
      <input type="text" id="identifier" name="identifier" placeholder="Enter your email or username" required>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button class="btn" type="submit">Login</button>
    <p style="text-align:center;margin-top:12px"><a href="userform.html" style="color:#667eea;text-decoration:none">Create an account</a></p>
  </form>
</body>
</html>

