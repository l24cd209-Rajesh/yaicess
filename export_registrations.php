<?php
require_once 'db_config.php';

// Get export type
$exportType = $_GET['type'] ?? 'both';

if ($exportType === 'users') {
    // Export only user registrations
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="user_registrations_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
    
    // User CSV headers
    fputcsv($output, ['ID', 'User Code', 'Username', 'Full Name', 'Email', 'Phone', 'Referral', 'Registration Date']);
    
    // Get user registrations
    $stmt = $conn->prepare("SELECT id, user_code, username, fullname, email, phone, referral, registered_at FROM participants ORDER BY registered_at ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['user_code'],
            $row['username'],
            $row['fullname'],
            $row['email'],
            $row['phone'],
            $row['referral'] ?? 'N/A',
            date('d-m-Y H:i', strtotime($row['registered_at']))
        ]);
    }
    $stmt->close();
    fclose($output);
    
} elseif ($exportType === 'events') {
    // Export only event registrations
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="event_registrations_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
    
    // Event CSV headers
    fputcsv($output, ['ID', 'Username', 'Event Name', 'Event ID', 'Amount', 'Payment Status', 'Payment ID', 'Registration Code', 'Order ID', 'Registration Date']);
    
    // Get event registrations
    $stmt = $conn->prepare("
        SELECT 
            uer.id,
            uer.username,
            uer.event_name,
            uer.event_id,
            uer.amount,
            uer.payment_status,
            uer.payment_id,
            uer.registration_code,
            uer.order_id,
            uer.created_at
        FROM user_event_registrations uer
        ORDER BY uer.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['username'],
            $row['event_name'],
            $row['event_id'],
            $row['amount'],
            $row['payment_status'],
            $row['payment_id'] ?? 'N/A',
            $row['registration_code'],
            $row['order_id'] ?? 'N/A',
            date('d-m-Y H:i', strtotime($row['created_at']))
        ]);
    }
    $stmt->close();
    fclose($output);
    
} else {
    // Export both files - show download page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Export Registration Data</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                border-radius: 15px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 40px;
                text-align: center;
                max-width: 600px;
                width: 100%;
            }
            h1 {
                color: #333;
                margin-bottom: 30px;
                font-size: 28px;
            }
            .download-section {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 25px;
                margin: 20px 0;
                border: 1px solid #e9ecef;
            }
            .download-btn {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin: 10px;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            }
            .download-btn:hover {
                background: #5a6fd8;
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            }
            .download-btn.users {
                background: #28a745;
                box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            }
            .download-btn.users:hover {
                background: #218838;
                box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            }
            .download-btn.events {
                background: #17a2b8;
                box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
            }
            .download-btn.events:hover {
                background: #138496;
                box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
            }
            .back-btn {
                display: inline-block;
                background: #6c757d;
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 6px;
                margin-top: 20px;
                transition: background 0.3s ease;
            }
            .back-btn:hover {
                background: #5a6268;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📊 Export Registration Data</h1>
            
            <div class="download-section">
                <h3>Download Files</h3>
                
                <a href="export_registrations.php?type=users" class="download-btn users">
                    📥 Download User Registrations
                </a>
                
                <a href="export_registrations.php?type=events" class="download-btn events">
                    📥 Download Event Registrations
                </a>
            </div>
            
            <a href="admin_dashboard.php" class="back-btn">← Back to Admin Dashboard</a>
        </div>
    </body>
    </html>
    <?php
}

$conn->close();
exit;
?>
