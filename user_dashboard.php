<?php
session_start();
require_once __DIR__ . '/db_config.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: user_login.php');
  exit;
}

// Fetch events
$events = [];
$res = $conn->query("SELECT id, name, description, amount, currency, is_active FROM events WHERE is_active = 1 ORDER BY id ASC");
if ($res) {
  while ($row = $res->fetch_assoc()) { 
    $events[] = $row; 
  }
}

// Fetch user registrations with payment details
$registrations = [];
$stmt = $conn->prepare('
    SELECT id, username, payment_status, payment_id, event_name, amount, registration_code, created_at
    FROM user_event_registrations
    WHERE username = ?
    ORDER BY id ASC
');
$stmt->bind_param('s', $_SESSION['username']);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) { 
    $registrations[] = $row; 
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    *{box-sizing:border-box}body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;margin:0}header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:20px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.1)}.brand{font-size:24px;font-weight:bold}.container{max-width:1100px;margin:0 auto;padding:20px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}.card{background:#fff;padding:18px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);display:flex;flex-direction:column;gap:10px}.amount{font-weight:700;color:#0d47a1}.status{font-size:12px;padding:4px 8px;border-radius:99px;display:inline-block}.success{background:#d4edda;color:#155724}.pending{background:#fff3cd;color:#856404}    .btn{background:#667eea;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;text-align:center;font-weight:600}
    
    /* Header styling */
    header a {
        background: rgba(255,255,255,0.2);
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    header a:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-1px);
    }
    .registrations-table{width:100%;border-collapse:collapse;margin-top:20px;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.08)}
    .registrations-table th,.registrations-table td{padding:12px;text-align:left;border-bottom:1px solid #eee}
    .registrations-table th{background:#f8f9fa;font-weight:600;color:#495057}
    .registrations-table tr:hover{background:#f8f9fa}

    /* Profile Banner Styles */
    .profile-banner {
      background: linear-gradient(135deg, #667eea 0%, #e91e63 100%);
      background-image: 
        linear-gradient(135deg, #667eea 0%, #e91e63 100%),
        repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.03) 10px, rgba(255,255,255,0.03) 20px);
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
      position: relative;
      overflow: hidden;
      animation: gradientShift 8s ease-in-out infinite;
    }

    @keyframes gradientShift {
      0%, 100% { background: linear-gradient(135deg, #667eea 0%, #e91e63 100%); }
      50% { background: linear-gradient(135deg, #e91e63 0%, #667eea 100%); }
    }

    .profile-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: 
        radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(255,255,255,0.05) 0%, transparent 50%);
      opacity: 0.3;
    }

    .profile-banner::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.02' fill-rule='evenodd'%3E%3Cpath d='M20 0L40 20L20 40L0 20L20 0z'/%3E%3C/g%3E%3C/svg%3E");
      opacity: 0.1;
      animation: patternMove 20s linear infinite;
    }

    @keyframes patternMove {
      0% { transform: translate(0, 0); }
      100% { transform: translate(40px, 40px); }
    }

    .profile-banner-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      z-index: 2;
    }

    .profile-section {
      display: flex;
      align-items: center;
      gap: 25px;
    }

    .profile-image-container {
      position: relative;
    }

    .profile-banner-image {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid rgba(255,255,255,0.3);
      box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }

    .profile-update-btn {
      position: absolute;
      bottom: 0;
      right: 0;
      background: #9c27b0;
      color: white;
      border: none;
      border-radius: 20px;
      padding: 8px 16px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(156, 39, 176, 0.4);
      backdrop-filter: blur(10px);
    }

    .profile-update-btn:hover {
      background: #7b1fa2;
      transform: translateY(-2px) scale(1.05);
      box-shadow: 0 6px 20px rgba(156, 39, 176, 0.6);
    }

    .profile-update-btn:active {
      transform: translateY(0) scale(0.98);
    }

    .profile-info {
      color: white;
      text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    .profile-name {
      font-size: 32px;
      font-weight: 700;
      margin: 0 0 15px 0;
      text-shadow: 0 2px 10px rgba(0,0,0,0.3);
      animation: nameGlow 3s ease-in-out infinite alternate;
    }

    @keyframes nameGlow {
      from { text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
      to { text-shadow: 0 2px 20px rgba(255,255,255,0.2), 0 2px 10px rgba(0,0,0,0.3); }
    }

    .profile-status {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .status-badge {
      background: #4caf50;
      color: white;
      padding: 6px 12px;
      border-radius: 15px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-block;
      width: fit-content;
      box-shadow: 0 2px 8px rgba(76, 175, 80, 0.4);
      animation: badgePulse 2s ease-in-out infinite;
    }

    @keyframes badgePulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .member-since {
      font-size: 14px;
      opacity: 0.9;
      font-weight: 500;
      transition: opacity 0.3s ease;
    }

    .member-since:hover {
      opacity: 1;
    }

    .profile-actions {
      display: flex;
      gap: 15px;
    }

    .edit-profile-btn {
      background: transparent;
      color: white;
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 25px;
      padding: 12px 24px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      background: rgba(255,255,255,0.1);
      position: relative;
      overflow: hidden;
    }

    .edit-profile-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s ease;
    }

    .edit-profile-btn:hover::before {
      left: 100%;
    }

    .edit-profile-btn:hover {
      background: rgba(255,255,255,0.2);
      border-color: rgba(255,255,255,0.5);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }

    .edit-profile-btn:active {
      transform: translateY(0);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .profile-banner-content {
        flex-direction: column;
        gap: 20px;
        text-align: center;
      }
      
      .profile-section {
        flex-direction: column;
        gap: 20px;
      }
      
      .profile-name {
        font-size: 28px;
      }
      
      .profile-banner {
        padding: 25px 20px;
      }
    }
  </style>
</head>
<body>
  <header>
      <div class="brand">YAICESS • User Dashboard</div>
      <div>
          Hi, <?php echo htmlspecialchars($_SESSION['username']); ?> | 
          <a href="logout.php" style="color:#ffd; text-decoration:none" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
      </div>
  </header>
  <div class="container">
    <?php if (isset($_SESSION['payment_success']) && $_SESSION['payment_success']): ?>
      <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        <strong>✅ Payment Successful!</strong><br>
        <?php echo htmlspecialchars($_SESSION['payment_success']); ?>
        <?php if (isset($_SESSION['event_name'])): ?>
          <br>Event: <strong><?php echo htmlspecialchars($_SESSION['event_name']); ?></strong>
        <?php endif; ?>
      </div>
      <?php 
        unset($_SESSION['payment_success']);
        unset($_SESSION['event_name']);
      ?>
    <?php endif; ?>

    <?php if (isset($_GET['payment_success']) && $_GET['payment_success'] == '1'): ?>
      <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        <strong>✅ Payment Successful!</strong><br>
        Your payment has been confirmed and you are now registered for the event.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['payment_cancelled']) && $_GET['payment_cancelled'] == '1'): ?>
      <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffeaa7;">
        <strong>⚠️ Payment Cancelled</strong><br>
        You cancelled the payment. You can try again from the event registration page.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['payment_failed']) && $_GET['payment_failed'] == '1'): ?>
      <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
        <strong>❌ Payment Failed</strong><br>
        There was an issue with your payment. Please try again or contact support.
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['payment_error']) && $_SESSION['payment_error']): ?>
      <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
        <strong>❌ Payment Error</strong><br>
        <?php echo htmlspecialchars($_SESSION['payment_error']); ?>
      </div>
      <?php 
        unset($_SESSION['payment_error']);
      ?>
    <?php endif; ?>

    <!-- Profile Banner Section -->
    <div class="profile-banner">
      <div class="profile-banner-content">
        <div class="profile-section">
          <div class="profile-image-container">
            <?php 
            // Fetch user profile image and registration date
            $stmt = $conn->prepare("SELECT profile_image, fullname, registered_at FROM participants WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $userData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!empty($userData['profile_image'])): ?>
              <img src="uploads/profile_images/<?php echo htmlspecialchars($userData['profile_image']); ?>" 
                   alt="Profile Image" class="profile-banner-image">
            <?php else: ?>
              <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" 
                   alt="Default Profile" class="profile-banner-image">
            <?php endif; ?>
            
            <button class="profile-update-btn" onclick="window.location.href='user_profile.php'">
              <i class="fas fa-camera"></i> Update
            </button>
          </div>
          
          <div class="profile-info">
            <h1 class="profile-name"><?php echo htmlspecialchars($userData['fullname'] ?? $_SESSION['username']); ?></h1>
            <div class="profile-status">
              <span class="status-badge">Active</span>
              <span class="member-since">Member since <?php echo date('d-m-Y', strtotime($userData['registered_at'] ?? 'now')); ?></span>
            </div>
          </div>
        </div>
        
        <div class="profile-actions">
          <button class="edit-profile-btn" onclick="window.location.href='user_profile.php'">
            <i class="fas fa-edit"></i> Edit Profile
          </button>
        </div>
      </div>
    </div>

    <h2>Available Events</h2>
    <div class="grid">
      <?php if (empty($events)): ?>
        <p>No events available right now.</p>
      <?php else: foreach ($events as $ev): $eid=(int)$ev['id']; ?>
        <?php 
        // Find the most recent registration for this event
        $registration = null;
        $eventRegistrations = array_filter($registrations, function($reg) use ($ev) {
            return $reg['event_name'] === $ev['name'];
        });
        
        if (!empty($eventRegistrations)) {
            // Sort by registration date descending and get the most recent
            usort($eventRegistrations, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            $registration = $eventRegistrations[0];
        }
        ?>
        <div class="card">
          <h3><?php echo htmlspecialchars($ev['name']); ?></h3>
          <div><?php echo htmlspecialchars($ev['description'] ?? ''); ?></div>
          <div class="amount">₹<?php echo number_format($ev['amount'], 2); ?> <?php echo htmlspecialchars($ev['currency']); ?></div>
          
          <?php if ($registration && $registration['payment_status'] === 'successful'): ?>
            <span class="status success">✅ Registered</span>
            <?php if ($registration['payment_id']): ?>
              <div style="font-size: 12px; color: #666; margin-top: 5px;">
                Payment ID: <?php echo htmlspecialchars($registration['payment_id']); ?>
              </div>
            <?php endif; ?>
            <div style="font-size: 12px; color: #28a745; margin-top: 5px;">
              Registration Date: <?php echo date('M d, Y H:i', strtotime($registration['created_at'])); ?>
            </div>
          <?php elseif ($registration && $registration['payment_status'] === 'pending'): ?>
            <span class="status pending">⏳ Pending Payment</span>
            <a class="btn" href="event_payment.php?event_id=<?php echo $eid; ?>">Complete Payment</a>
            <div style="font-size: 12px; color: #ffc107; margin-top: 5px;">
              Registration Date: <?php echo date('M d, Y H:i', strtotime($registration['created_at'])); ?>
            </div>
          <?php else: ?>
            <a class="btn" href="event_payment.php?event_id=<?php echo $eid; ?>">Register</a>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- User's Registration History -->
    <h2 style="margin-top: 40px;">My Registrations</h2>
    <?php
    // userRegistrations is already fetched above as $registrations
    ?>

    <?php if (empty($registrations)): ?>
      <p>You haven't registered for any events yet.</p>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="registrations-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Event</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Registration Code</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $reg): ?>
                <tr>
                    <td><?php echo htmlspecialchars($reg['id']); ?></td>
                    <td><?php echo htmlspecialchars($reg['username']); ?></td>
                    <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                    <td>₹<?php echo number_format($reg['amount'], 2); ?></td>
                    <td>
                        <span class="status <?php echo $reg['payment_status'] === 'successful' ? 'success' : 'pending'; ?>">
                            <?php echo ucfirst($reg['payment_status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($reg['registration_code']); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($reg['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>

