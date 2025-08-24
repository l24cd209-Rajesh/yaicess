<?php
session_start();
require_once 'db_config.php';

// Fetch all active events
$events = [];
try {
    $stmt = $conn->prepare("SELECT * FROM events WHERE is_active = 1 ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Events - YAICESS Conference</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            list-style: none;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.1);
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-title {
            font-size: 36px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .page-subtitle {
            font-size: 18px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .event-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .event-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .event-price {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .event-currency {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .event-body {
            padding: 25px;
        }
        
        .event-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
            min-height: 80px;
        }
        
        .event-features {
            list-style: none;
            margin-bottom: 25px;
        }
        
        .event-features li {
            padding: 8px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .event-features li i {
            color: #667eea;
            width: 20px;
        }
        
        .event-actions {
            text-align: center;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            min-width: 150px;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login {
            background: #28a745;
        }
        
        .btn-login:hover {
            background: #218838;
        }
        
        .btn-disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .btn-disabled:hover {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }
        
        .no-events {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-events i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }
        
        .no-events h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .no-events p {
            font-size: 16px;
            line-height: 1.6;
        }
        
        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .alert {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            transition: opacity 0.3s ease;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .page-subtitle {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">YAICESS<span>.</span></div>
            <ul class="nav-links">
                <li><a href="index.html"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="index.html#about"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="index.html#agenda"><i class="fas fa-calendar"></i> Agenda</a></li>
                <li><a href="login.html"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="userform.html"><i class="fas fa-user-plus"></i> Register</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['payment_error'])): ?>
            <div class="alert">
                <strong>Payment Error:</strong> <?php echo htmlspecialchars($_SESSION['payment_error']); ?>
                <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 20px; cursor: pointer; color: #721c24;">&times;</button>
            </div>
            <?php unset($_SESSION['payment_error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['payment_success'])): ?>
            <div class="alert alert-success">
                <strong>Success:</strong> <?php echo htmlspecialchars($_SESSION['payment_success']); ?>
                <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 20px; cursor: pointer; color: #155724;">&times;</button>
            </div>
            <?php unset($_SESSION['payment_success']); ?>
        <?php endif; ?>
        
        <div class="page-header">
            <h1 class="page-title">Available Events</h1>
            <p class="page-subtitle">Choose from our exciting lineup of events and workshops. Register now to secure your spot!</p>
        </div>

        <?php if (empty($events)): ?>
            <div class="no-events">
                <i class="fas fa-calendar-times"></i>
                <h3>No Events Available</h3>
                <p>We're currently preparing our event lineup. Please check back soon for exciting events and workshops!</p>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <div class="status-badge">Active</div>
                        <div class="event-header">
                            <div class="event-name"><?php echo htmlspecialchars($event['name']); ?></div>
                            <div class="event-price"><?php echo htmlspecialchars($event['currency'] . number_format($event['amount'], 2)); ?></div>
                            <div class="event-currency">Registration Fee</div>
                        </div>
                        
                        <div class="event-body">
                            <div class="event-description">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </div>
                            
                            <ul class="event-features">
                                <li><i class="fas fa-check-circle"></i> Full event access</li>
                                <li><i class="fas fa-check-circle"></i> Certificate of participation</li>
                                <li><i class="fas fa-check-circle"></i> Networking opportunities</li>
                                <li><i class="fas fa-check-circle"></i> Workshop materials</li>
                            </ul>
                            
                            <div class="event-actions">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <!-- User is logged in, show register button -->
                                    <a href="event_payment.php?event_id=<?php echo $event['id']; ?>" class="btn">
                                        <i class="fas fa-ticket-alt"></i> Register Now
                                    </a>
                                <?php else: ?>
                                    <!-- User is not logged in, show login/register buttons -->
                                    <a href="login.html" class="btn btn-login">
                                        <i class="fas fa-sign-in-alt"></i> Login to Register
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
