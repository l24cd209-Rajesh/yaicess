<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: user_login.php');
  exit;
}
$fullname = htmlspecialchars($_SESSION['fullname'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration Successful</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
    .container { background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 560px; position: relative; overflow: hidden; text-align: center; }
    .container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #28a745, #20c997); }
    .icon { width: 80px; height: 80px; border-radius: 50%; background: #28a745; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px; }
    h1 { color: #28a745; margin: 10px 0 5px; }
    p { color: #333; }
    .actions { margin-top: 24px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn { padding: 12px 22px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all .2s; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
    .btn-secondary { background: #6c757d; color: #fff; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,.15); }
  </style>
  </head>
<body>
  <div class="container">
    <div class="icon">✓</div>
    <h1>Registration Successful</h1>
    <p>Welcome, <?php echo $fullname; ?>. Your account has been created successfully.</p>
    <p style="color: #28a745; font-weight: 600;">📧 A confirmation email has been sent to your email address.</p>
    <div class="actions">
      <a class="btn btn-primary" href="user_login.php">Go to User Dashboard</a>
      <a class="btn btn-secondary" href="index.html">Go to Home Page</a>
    </div>
  </div>
</body>
</html>

