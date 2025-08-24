<?php
session_start();
require_once 'db_config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.html');
    exit;
}

$message = '';
$error = '';

// Handle event addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $currency = $_POST['currency'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!empty($name) && !empty($description) && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO events (name, description, amount, currency, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsi", $name, $description, $amount, $currency, $is_active);
        
        if ($stmt->execute()) {
            $message = "Event added successfully!";
        } else {
            $error = "Failed to add event: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields and ensure amount is greater than 0.";
    }
}

// Handle event update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $event_id = intval($_POST['event_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $currency = $_POST['currency'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!empty($name) && !empty($description) && $amount > 0) {
        $stmt = $conn->prepare("UPDATE events SET name = ?, description = ?, amount = ?, currency = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssdsii", $name, $description, $amount, $currency, $is_active, $event_id);
        
        if ($stmt->execute()) {
            $message = "Event updated successfully!";
        } else {
            $error = "Failed to update event: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields and ensure amount is greater than 0.";
    }
}

// Handle event deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $event_id = intval($_POST['event_id']);
    
    // Check if event has registrations
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_event_registrations WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $registrations = $result->fetch_assoc()['count'];
    $stmt->close();
    
    if ($registrations > 0) {
        $error = "Cannot delete event. It has $registrations active registrations. Consider deactivating instead.";
    } else {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        
        if ($stmt->execute()) {
            $message = "Event deleted successfully!";
        } else {
            $error = "Failed to delete event: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle event status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $event_id = intval($_POST['event_id']);
    
    // Get current status from database to ensure accuracy
    $stmt = $conn->prepare("SELECT is_active FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_event = $result->fetch_assoc();
    $stmt->close();
    
    if ($current_event) {
        // Toggle the current status
        $new_status = $current_event['is_active'] ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE events SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $event_id);
        
        if ($stmt->execute()) {
            $status_text = $new_status ? 'activated' : 'deactivated';
            $message = "Event $status_text successfully!";
        } else {
            $error = "Failed to update event status: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Event not found!";
    }
}

// Fetch all events
$events = [];
try {
    $stmt = $conn->prepare("SELECT * FROM events ORDER BY id ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error = "Error fetching events: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - Admin Dashboard</title>
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
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-input {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
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
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #333;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">YAICESS • Event Management</div>
            <div class="admin-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="admin_dashboard.php" class="logout-btn">Dashboard</a>
                <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($events); ?></div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($events, function($e) { return $e['is_active'] == 1; })); ?></div>
                <div class="stat-label">Active Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($events, function($e) { return $e['is_active'] == 0; })); ?></div>
                <div class="stat-label">Inactive Events</div>
            </div>
        </div>

        <!-- Add New Event Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Event
                </h2>
            </div>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Event Name *</label>
                        <input type="text" class="form-input" name="name" required placeholder="Enter event name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea class="form-input form-textarea" name="description" required placeholder="Enter event description"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Amount *</label>
                            <input type="number" class="form-input" name="amount" step="0.01" min="0" required placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Currency</label>
                            <select class="form-input" name="currency">
                                <option value="INR">₹ (INR)</option>
                                <option value="USD">$ (USD)</option>
                                <option value="EUR">€ (EUR)</option>
                                <option value="GBP">£ (GBP)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" class="checkbox-input" name="is_active" id="is_active" checked>
                            <label for="is_active">Active Event</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_event" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Event
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Events List Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Manage Events
                </h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #666; padding: 40px;">
                                    <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 20px; color: #ddd; display: block;"></i>
                                    <p>No events found. Add your first event above!</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['id']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($event['name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>
                                        <?php if (strlen($event['description']) > 100): ?>...<?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($event['currency'] . number_format($event['amount'], 2)); ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $event['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $event['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-warning btn-sm" onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $event['is_active'] ? '1' : '0'; ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $event['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                        onclick="return confirm('Are you sure you want to <?php echo $event['is_active'] ? 'deactivate' : 'activate'; ?> this event?')">
                                                    <i class="fas fa-<?php echo $event['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                    <?php echo $event['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" name="delete_event" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2 style="margin-bottom: 20px;">
                <i class="fas fa-edit"></i> Edit Event
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="event_id" id="edit_event_id">
                
                <div class="form-group">
                    <label class="form-label">Event Name *</label>
                    <input type="text" class="form-input" name="name" id="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea class="form-input form-textarea" name="description" id="edit_description" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Amount *</label>
                        <input type="number" class="form-input" name="amount" id="edit_amount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Currency</label>
                        <select class="form-input" name="currency" id="edit_currency">
                            <option value="INR">₹ (INR)</option>
                            <option value="USD">$ (USD)</option>
                            <option value="EUR">€ (EUR)</option>
                            <option value="GBP">£ (GBP)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" class="checkbox-input" name="is_active" id="edit_is_active">
                        <label for="edit_is_active">Active Event</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_event" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Event
                    </button>
                    <button type="button" class="btn" onclick="closeEditModal()" style="margin-left: 10px;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editEvent(event) {
            document.getElementById('edit_event_id').value = event.id;
            document.getElementById('edit_name').value = event.name;
            document.getElementById('edit_description').value = event.description;
            document.getElementById('edit_amount').value = event.amount;
            document.getElementById('edit_currency').value = event.currency;
            document.getElementById('edit_is_active').checked = event.is_active == 1;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
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
