<?php
session_start();
require_once __DIR__ . '/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'profile';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM participants WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) >= 8) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE participants SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $userId);
                
                if ($stmt->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Failed to update password.";
                }
                $stmt->close();
            } else {
                $error = "New password must be at least 8 characters long.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $uploadDir = 'uploads/profile_images/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['profile_image'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif');
    
    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 5000000) {
                $fileNameNew = $username . '_' . time() . '.' . $fileExt;
                $fileDestination = $uploadDir . $fileNameNew;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    $stmt = $conn->prepare("UPDATE participants SET profile_image = ? WHERE id = ?");
                    $stmt->bind_param("si", $fileNameNew, $userId);
                    if ($stmt->execute()) {
                        $message = "Profile image updated successfully!";
                    } else {
                        $error = "Failed to update database with image path.";
                    }
                    $stmt->close();
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "File size too large. Maximum size is 5MB.";
            }
        } else {
            $error = "Error uploading file.";
        }
    } else {
        $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
    }
}

// Handle profile image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    // Get current image path
    $stmt = $conn->prepare("SELECT profile_image FROM participants WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentImage = $result->fetch_assoc();
    $stmt->close();
    
    if ($currentImage && !empty($currentImage['profile_image'])) {
        // Delete physical file
        $imagePath = 'uploads/profile_images/' . $currentImage['profile_image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        // Update database to remove image path
        $stmt = $conn->prepare("UPDATE participants SET profile_image = NULL WHERE id = ?");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $message = "Profile image deleted successfully!";
        } else {
            $error = "Failed to delete profile image from database.";
        }
        $stmt->close();
    } else {
        $error = "No profile image to delete.";
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $referral = trim($_POST['referral']);
    
    if (!empty($fullname) && !empty($email) && !empty($phone)) {
        $stmt = $conn->prepare("UPDATE participants SET fullname = ?, email = ?, phone = ?, referral = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $fullname, $email, $phone, $referral, $userId);
        
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM participants WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch user's event registrations
$stmt = $conn->prepare("
    SELECT uer.*, e.name as event_name, e.description 
    FROM user_event_registrations uer 
    LEFT JOIN events e ON uer.event_id = e.id 
    WHERE uer.username = ? 
    ORDER BY uer.created_at DESC
");
$stmt->bind_param("s", $username);
$stmt->execute();
$registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$totalEvents = count($registrations);
$completedEvents = count(array_filter($registrations, function($r) { return $r['payment_status'] === 'successful'; }));
$pendingEvents = count(array_filter($registrations, function($r) { return $r['payment_status'] === 'pending'; }));
$totalSpent = array_sum(array_map(function($r) { return $r['payment_status'] === 'successful' ? $r['amount'] : 0; }, $registrations));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced User Profile - YAICESS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; }
        .header { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 20px; display: flex; justify-content: space-between; align-items: center; color: white; }
        .brand { font-size: 24px; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin-left: 20px; padding: 8px 16px; border-radius: 20px; transition: all 0.3s ease; }
        .nav-links a:hover { background: rgba(255, 255, 255, 0.2); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .tabs { display: flex; background: white; border-radius: 15px 15px 0 0; overflow: hidden; margin-bottom: 0; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
        .tab-btn { flex: 1; padding: 20px; background: #f8f9fa; border: none; cursor: pointer; font-size: 16px; font-weight: 600; color: #666; transition: all 0.3s ease; border-bottom: 3px solid transparent; }
        .tab-btn.active { background: white; color: #667eea; border-bottom-color: #667eea; }
        .tab-btn:hover { background: #e9ecef; }
        
        .tab-content { background: white; border-radius: 0 0 15px 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); display: none; }
        .tab-content.active { display: block; }
        
        .profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-bottom: 30px; }
        .profile-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); text-align: center; }
        .profile-image-container { position: relative; margin-bottom: 20px; }
        .profile-image { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #667eea; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); }
        .profile-image-upload { position: absolute; bottom: 0; right: 0; background: #667eea; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
        .profile-image-upload:hover { background: #5a6fd8; transform: scale(1.1); }
        .profile-name { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .profile-username { color: #667eea; font-size: 16px; margin-bottom: 20px; }
        
        .profile-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px; }
        .stat-item { background: #f8f9fa; padding: 15px; border-radius: 10px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 12px; color: #666; text-transform: uppercase; }
        
        .details-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); }
        .card-title { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-input { width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 16px; transition: all 0.3s ease; }
        .form-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
        .btn:hover { background: #5a6fd8; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .hidden { display: none; }
        
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .tabs { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">YAICESS</div>
        <div class="nav-links">
            <a href="user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="user_profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" onclick="showTab('profile')">Profile</button>
            <button class="tab-btn <?php echo $activeTab === 'security' ? 'active' : ''; ?>" onclick="showTab('security')">Security</button>
        </div>

        <!-- Profile Tab -->
        <div id="profile" class="tab-content <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
            <div class="profile-grid">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-image-container">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="uploads/profile_images/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                 alt="Profile Image" class="profile-image" id="profileImage">
                        <?php else: ?>
                            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" 
                                 alt="Default Profile" class="profile-image" id="profileImage">
                        <?php endif; ?>
                        
                        <button class="profile-image-upload" onclick="document.getElementById('imageInput').click()">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>

                    <?php if (!empty($user['profile_image'])): ?>
                        <form method="POST" action="" style="margin-top: 10px;">
                            <button type="submit" name="delete_image" class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete your profile image?')">
                                <i class="fas fa-trash"></i> Delete Image
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="profile-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                    <div class="profile-username">@<?php echo htmlspecialchars($username); ?></div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $totalEvents; ?></div>
                            <div class="stat-label">Total Events</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $completedEvents; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>

                    <!-- Hidden file input for image upload -->
                    <form id="imageForm" method="POST" enctype="multipart/form-data" class="hidden">
                        <input type="file" id="imageInput" name="profile_image" accept="image/*" onchange="submitImage()">
                    </form>
                </div>

                <!-- Profile Details Card -->
                <div class="details-card">
                    <div class="card-title">
                        <i class="fas fa-user-edit"></i>
                        Profile Information
                    </div>

                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-input" name="fullname" 
                                       value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($username); ?>" disabled>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-input" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-input" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Referral Code</label>
                            <input type="text" class="form-input" name="referral" 
                                   value="<?php echo htmlspecialchars($user['referral'] ?? ''); ?>" 
                                   placeholder="How did you hear about us?">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">User Code</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['user_code']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Registration Date</label>
                                <input type="text" class="form-input" 
                                       value="<?php echo date('d-m-Y', strtotime($user['registered_at'])); ?>" disabled>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div id="security" class="tab-content <?php echo $activeTab === 'security' ? 'active' : ''; ?>">
            <div class="details-card">
                <div class="card-title">
                    <i class="fas fa-shield-alt"></i>
                    Security Settings
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-input" name="current_password" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-input" name="new_password" required 
                                   minlength="8" placeholder="Minimum 8 characters">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-input" name="confirm_password" required 
                                   minlength="8" placeholder="Confirm your new password">
                        </div>
                    </div>

                    <button type="submit" name="change_password" class="btn">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>

                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <h4 style="margin-bottom: 15px; color: #333;">
                        <i class="fas fa-info-circle"></i> Password Requirements
                    </h4>
                    <ul style="color: #666; line-height: 1.6;">
                        <li>Minimum 8 characters long</li>
                        <li>Use a combination of letters, numbers, and symbols</li>
                        <li>Avoid using personal information</li>
                        <li>Don't reuse passwords from other accounts</li>
                    </ul>
                </div>
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
            
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        }

        function submitImage() {
            document.getElementById('imageForm').submit();
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
