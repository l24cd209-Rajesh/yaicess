<?php
session_start();
require 'db_config.php';

// Check if admin is logged in - ONLY session-based authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.html');
    exit;
}

// Get comprehensive statistics
$totalUsers = 0;
$totalEventRegistrations = 0;
$successfulPayments = 0;
$pendingPayments = 0;
$totalRevenue = 0;
$eventStats = [];

try {
    // Total users registered
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM participants");
    $stmt->execute();
    $result = $stmt->get_result();
    $totalUsers = $result->fetch_assoc()['total'];
    
    // Total event registrations
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM user_event_registrations");
    $stmt->execute();
    $result = $stmt->get_result();
    $totalEventRegistrations = $result->fetch_assoc()['total'];
    
    // Successful payments
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM user_event_registrations WHERE payment_status = 'successful'");
    $stmt->execute();
    $result = $stmt->get_result();
    $successfulPayments = $result->fetch_assoc()['total'];
    
    // Pending payments
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM user_event_registrations WHERE payment_status = 'pending'");
    $stmt->execute();
    $result = $stmt->get_result();
    $pendingPayments = $result->fetch_assoc()['total'];
    
    // Total revenue calculation
    $stmt = $conn->prepare("
        SELECT SUM(uer.amount) as total_revenue 
        FROM user_event_registrations uer 
        WHERE uer.payment_status = 'successful'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $revenueRow = $result->fetch_assoc();
    $totalRevenue = $revenueRow['total_revenue'] ?? 0;
    
    // Event-wise statistics
    $stmt = $conn->prepare("
        SELECT 
            e.name as event_name,
            COALESCE(COUNT(uer.id), 0) as total_registrations,
            COALESCE(SUM(CASE WHEN uer.payment_status = 'successful' THEN 1 ELSE 0 END), 0) as successful_payments,
            COALESCE(SUM(CASE WHEN uer.payment_status = 'pending' THEN 1 ELSE 0 END), 0) as pending_payments,
            COALESCE(SUM(CASE WHEN uer.payment_status = 'successful' THEN uer.amount ELSE 0 END), 0) as revenue
        FROM events e
        LEFT JOIN user_event_registrations uer ON e.id = uer.event_id
        WHERE e.is_active = 1
        GROUP BY e.id, e.name
        ORDER BY total_registrations DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $eventStats[] = $row;
    }
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Get recent registrations
$recentRegistrations = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            id,
            username,
            event_name,
            amount,
            payment_status,
            payment_id,
            registration_code,
            created_at
        FROM user_event_registrations
        ORDER BY id ASC
        LIMIT 10
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentRegistrations[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching recent registrations: " . $e->getMessage());
}

// Get recent users
$recentUsers = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            id,
            user_code,
            username,
            email,
            phone,
            registered_at
        FROM participants
        ORDER BY id ASC
        LIMIT 10
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentUsers[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching recent users: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - YAICESS Conference</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
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
        
        .logo span {
            color: #ffd700;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-title {
            margin: 0;
            color: #333;
            font-size: 18px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .action-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn:hover {
            background: #5a6fd8;
        }
        
        .export-btn {
            background: #667eea;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: 15px 20px;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 2px solid transparent;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .admin-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">YAICESS<span>-</span> Admin</div>
            <div class="admin-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users Registered</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalEventRegistrations; ?></div>
                <div class="stat-label">Event Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $successfulPayments; ?></div>
                <div class="stat-label">Successful Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pendingPayments; ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₹<?php echo number_format($totalRevenue, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <!-- Event Statistics -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Event-wise Statistics</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Total Registrations</th>
                            <th>Successful Payments</th>
                            <th>Pending Payments</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventStats)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">No events found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($eventStats as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo $event['total_registrations']; ?></td>
                                <td>
                                    <span class="status-badge status-success"><?php echo $event['successful_payments']; ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-pending"><?php echo $event['pending_payments']; ?></span>
                                </td>
                                <td>₹<?php echo number_format($event['revenue'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Activity Tabs -->
        <div class="section">
            <div class="section-header">
                <div class="tabs">
                    <button class="tab-btn active" onclick="showTab('event-registrations')">Event Registrations</button>
                    <button class="tab-btn" onclick="showTab('user-registrations')">User Registrations</button>
                </div>
            </div>
            
            <!-- Event Registrations Tab -->
            <div id="event-registrations" class="tab-content active">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Event Name</th>
                                <th>Amount</th>
                                <th>Payment Status</th>
                                <th>Payment ID</th>
                                <th>Registration Code</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentRegistrations)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #666;">No event registrations found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentRegistrations as $registration): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($registration['id']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['username']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['event_name']); ?></td>
                                    <td>₹<?php echo number_format($registration['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $registration['payment_status'] === 'successful' ? 'success' : 'warning'; ?>">
                                            <?php echo strtoupper($registration['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $registration['payment_id'] ? htmlspecialchars($registration['payment_id']) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($registration['registration_code']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($registration['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- User Registrations Tab -->
            <div id="user-registrations" class="tab-content">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User Code</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentUsers)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #666;">No user registrations found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['user_code']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($user['registered_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Quick Actions</h2>
            </div>
            <div style="padding: 20px;">
                <a href="export_registrations.php" class="export-btn">Export Data</a>
                <a href="admin_events.php" class="export-btn" style="background: #28a745; margin-left: 15px;">Manage Events</a>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
