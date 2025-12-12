<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

requireLogin();

$pageTitle = "My Profile";
$message = '';
$message_type = '';

// Get current user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($sql);
$user = $user_result->fetch_assoc();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        
        $sql = "UPDATE users SET 
                name = '$name',
                email = '$email',
                phone_number = '$phone'
                WHERE id = $user_id";
        
        if ($conn->query($sql)) {
            // Update session
            $_SESSION['user_name'] = $name;
            
            $message = "Profile updated successfully!";
            $message_type = 'success';
            
            // Refresh user data
            $user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
            $user = $user_result->fetch_assoc();
        } else {
            $message = "Error updating profile: " . $conn->error;
            $message_type = 'danger';
        }
    }
    elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if ($current_password !== $user['password']) { // Hash password later
            $message = "Current password is incorrect!";
            $message_type = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = "New passwords do not match!";
            $message_type = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = "New password must be at least 6 characters!";
            $message_type = 'danger';
        } else {
            $sql = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
            
            if ($conn->query($sql)) {
                $message = "Password changed successfully!";
                $message_type = 'success';
            } else {
                $message = "Error changing password: " . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .profile-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Manage your account information and settings</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p>
                        <span class="role-badge role-<?php echo $user['role']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                        </span>
                        <?php if ($user['airline']): ?>
                            <br><span class="text-muted"><?php echo htmlspecialchars($user['airline']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Profile Information Form -->
                <h3><i class="fas fa-user-edit"></i> Personal Information</h3>
                <form method="POST" class="mb-5">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Job Title</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['job_title'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Account Created</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
                
                <!-- Change Password Form -->
                <h3><i class="fas fa-key"></i> Change Password</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Current Password *</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Airport Management System</p>
        </div>
    </footer>
</body>
</html>