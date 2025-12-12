<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

// Only administrators and airport managers can access user management
requireRole(['administrator', 'airport_manager']);

$pageTitle = "User Management";
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $airline = $conn->real_escape_string($_POST['airline']);
        $role = $conn->real_escape_string($_POST['role']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $department = $conn->real_escape_string($_POST['department']);
        $job_title = $conn->real_escape_string($_POST['job_title']);
        
        if ($password !== $confirm_password) {
            $message = "Passwords do not match!";
            $message_type = 'danger';
        } elseif (strlen($password) < 6) {
            $message = "Password must be at least 6 characters!";
            $message_type = 'danger';
        } else {
            // Check if email exists
            $check_sql = "SELECT id FROM users WHERE email = '$email'";
            $check_result = $conn->query($check_sql);
            
            if ($check_result->num_rows > 0) {
                $message = "Email already registered!";
                $message_type = 'danger';
            } else {
                // Hash password later
                $hashed_password = $password;
                
                $sql = "INSERT INTO users (name, email, password, airline, role, phone_number, department, job_title) 
                        VALUES ('$name', '$email', '$hashed_password', '$airline', '$role', '$phone', '$department', '$job_title')";
                
                if ($conn->query($sql)) {
                    $new_user_id = $conn->insert_id;
                    
                    // Log the activity
                    $log_sql = "INSERT INTO user_activity_log (user_id, activity_type, activity_details) 
                               VALUES ({$_SESSION['user_id']}, 'user_created', 'Created user: $name ($email)')";
                    $conn->query($log_sql);
                    
                    $message = "User $name added successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error adding user: " . $conn->error;
                    $message_type = 'danger';
                }
            }
        }
    }
    elseif (isset($_POST['update_user'])) {
        // Update user
        $user_id = (int)$_POST['user_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $airline = $conn->real_escape_string($_POST['airline']);
        $role = $conn->real_escape_string($_POST['role']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $department = $conn->real_escape_string($_POST['department']);
        $job_title = $conn->real_escape_string($_POST['job_title']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE users SET 
                name = '$name',
                email = '$email',
                airline = '$airline',
                role = '$role',
                phone_number = '$phone',
                department = '$department',
                job_title = '$job_title',
                is_active = $is_active
                WHERE id = $user_id";
        
        if ($conn->query($sql)) {
            // Log the activity
            $log_sql = "INSERT INTO user_activity_log (user_id, activity_type, activity_details) 
                       VALUES ({$_SESSION['user_id']}, 'user_updated', 'Updated user: $name ($email)')";
            $conn->query($log_sql);
            
            $message = "User updated successfully!";
            $message_type = 'success';
        } else {
            $message = "Error updating user: " . $conn->error;
            $message_type = 'danger';
        }
    }
    elseif (isset($_POST['delete_user'])) {
        // Soft delete user (deactivate)
        $user_id = (int)$_POST['user_id'];
        
        // Don't allow deleting self
        if ($user_id == $_SESSION['user_id']) {
            $message = "You cannot delete your own account!";
            $message_type = 'danger';
        } else {
            $sql = "UPDATE users SET is_active = FALSE WHERE id = $user_id";
            
            if ($conn->query($sql)) {
                // Log the activity
                $log_sql = "INSERT INTO user_activity_log (user_id, activity_type, activity_details) 
                           VALUES ({$_SESSION['user_id']}, 'user_deactivated', 'Deactivated user ID: $user_id')";
                $conn->query($log_sql);
                
                $message = "User deactivated successfully!";
                $message_type = 'success';
            } else {
                $message = "Error deactivating user: " . $conn->error;
                $message_type = 'danger';
            }
        }
    }
    elseif (isset($_POST['reset_password'])) {
        // Reset user password
        $user_id = (int)$_POST['user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $message = "Passwords do not match!";
            $message_type = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = "Password must be at least 6 characters!";
            $message_type = 'danger';
        } else {
            $hashed_password = $new_password; // Plain text for now
            
            $sql = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            
            if ($conn->query($sql)) {
                // Log the activity
                $log_sql = "INSERT INTO user_activity_log (user_id, activity_type, activity_details) 
                           VALUES ({$_SESSION['user_id']}, 'password_reset', 'Reset password for user ID: $user_id')";
                $conn->query($log_sql);
                
                $message = "Password reset successfully!";
                $message_type = 'success';
            } else {
                $message = "Error resetting password: " . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $conn->real_escape_string($_GET['role']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'active';
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'name';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";

// Apply filters
if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR airline LIKE '%$search%')";
}
if (!empty($role_filter)) {
    $sql .= " AND role = '$role_filter'";
}
if ($status === 'active') {
    $sql .= " AND is_active = TRUE";
} elseif ($status === 'inactive') {
    $sql .= " AND is_active = FALSE";
}

// Apply sorting
switch ($sort) {
    case 'email':
        $sql .= " ORDER BY email";
        break;
    case 'role':
        $sql .= " ORDER BY role, name";
        break;
    case 'created':
        $sql .= " ORDER BY created_at DESC";
        break;
    case 'name':
    default:
        $sql .= " ORDER BY name";
        break;
}

$users_result = $conn->query($sql);

// Get user statistics
$stats_sql = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active,
              SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive,
              SUM(CASE WHEN role = 'administrator' THEN 1 ELSE 0 END) as admins,
              SUM(CASE WHEN role = 'airline_staff' THEN 1 ELSE 0 END) as airline_staff,
              SUM(CASE WHEN role = 'service_staff' THEN 1 ELSE 0 END) as service_staff
              FROM users";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get airlines for dropdown
$airlines_sql = "SELECT airline_name FROM airlines UNION SELECT DISTINCT airline FROM users WHERE airline IS NOT NULL AND airline != '' ORDER BY airline_name";
$airlines_result = $conn->query($airlines_sql);
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
        .user-stats { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; min-width: 150px; }
        .stat-card h3 { font-size: 1.8rem; margin-bottom: 5px; }
        .user-filters { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .user-actions { display: flex; gap: 10px; margin-bottom: 20px; }
        .user-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .user-table th, .user-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .user-table th { background-color: #f5f5f5; font-weight: bold; }
        .user-table tr:hover { background-color: #f9f9f9; }
        .user-status { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 50px auto; padding: 20px; border-radius: 8px; max-width: 600px; position: relative; max-height: 85vh; overflow-y: auto; }
        .close-modal { position: absolute; top: 15px; right: 15px; font-size: 24px; cursor: pointer; color: #666; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        @media (max-width: 768px) { 
            .form-row { grid-template-columns: 1fr; }
            .user-stats { flex-direction: column; }
        }
        .role-badge-small { padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .role-administrator { background: #dc3545; color: white; }
        .role-airport_manager { background: #fd7e14; color: white; }
        .role-airline_staff { background: #20c997; color: white; }
        .role-service_staff { background: #0dcaf0; color: black; }
        .role-cleaning_staff { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p>Manage system users, roles, and permissions</p>
            <div class="user-role">
                <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?>
                </span>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- User Statistics -->
        <div class="user-stats">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['active']; ?></h3>
                <p>Active Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['admins']; ?></h3>
                <p>Administrators</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['airline_staff']; ?></h3>
                <p>Airline Staff</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['service_staff']; ?></h3>
                <p>Service Staff</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="user-filters">
            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label>Search:</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, email, airline..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <label>Role:</label>
                    <select name="role" class="form-control">
                        <option value="">All Roles</option>
                        <option value="administrator" <?php echo $role_filter === 'administrator' ? 'selected' : ''; ?>>Administrator</option>
                        <option value="airport_manager" <?php echo $role_filter === 'airport_manager' ? 'selected' : ''; ?>>Airport Manager</option>
                        <option value="airline_staff" <?php echo $role_filter === 'airline_staff' ? 'selected' : ''; ?>>Airline Staff</option>
                        <option value="service_staff" <?php echo $role_filter === 'service_staff' ? 'selected' : ''; ?>>Service Staff</option>
                        <option value="cleaning_staff" <?php echo $role_filter === 'cleaning_staff' ? 'selected' : ''; ?>>Cleaning Staff</option>
                    </select>
                </div>
                <div>
                    <label>Status:</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                        <option value="all">All Users</option>
                    </select>
                </div>
                <div>
                    <label>Sort By:</label>
                    <select name="sort" class="form-control">
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="role" <?php echo $sort === 'role' ? 'selected' : ''; ?>>Role</option>
                        <option value="created" <?php echo $sort === 'created' ? 'selected' : ''; ?>>Recently Added</option>
                    </select>
                </div>
                <div style="display: flex; align-items: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="users.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="user-actions">
            <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            <?php endif; ?>
            
            <a href="user_activity.php" class="btn btn-info">
                <i class="fas fa-history"></i> View Activity Logs
            </a>
            
            <button class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print"></i> Print User List
            </button>
            
            <span class="text-muted" style="margin-left: auto;">
                Showing: <?php echo $users_result->num_rows; ?> users
            </span>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h2>System Users</h2>
                <small>Manage user accounts and permissions</small>
            </div>
            <div class="card-body">
                <?php if ($users_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>Role & Department</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                            <small>ID: <?php echo $user['id']; ?></small>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                            <?php if ($user['phone_number']): ?>
                                                <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone_number']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($user['airline']): ?>
                                                <div><i class="fas fa-plane"></i> <?php echo htmlspecialchars($user['airline']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="role-badge-small role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                            </span>
                                            <?php if ($user['department']): ?>
                                                <br><small><?php echo htmlspecialchars($user['department']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($user['job_title']): ?>
                                                <br><small><i><?php echo htmlspecialchars($user['job_title']); ?></i></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="user-status status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                            <?php if ($user['last_login']): ?>
                                                <br><small>Last login: <?php echo date('M j, H:i', strtotime($user['last_login'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                <button class="btn btn-warning btn-sm" onclick="openEditModal(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                
                                                <button class="btn btn-info btn-sm" onclick="openPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                    <i class="fas fa-key"></i> Reset PW
                                                </button>
                                                
                                                <?php if ($_SESSION['user_role'] === 'administrator' && $user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" 
                                                                onclick="return confirm('Deactivate <?php echo htmlspecialchars($user['name']); ?>?')">
                                                            <i class="fas fa-user-slash"></i> Deactivate
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                        <h3>No Users Found</h3>
                        <p>Try adjusting your filters or add a new user.</p>
                        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                            <button class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-user-plus"></i> Add First User
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('addUserModal')">&times;</span>
            <h2><i class="fas fa-user-plus"></i> Add New User</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="airline_staff">Airline Staff</option>
                            <option value="administrator">Administrator</option>
                            <option value="airport_manager">Airport Manager</option>
                            <option value="service_staff">Service Staff</option>
                            <option value="cleaning_staff">Cleaning Staff</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Airline/Organization</label>
                        <select name="airline" class="form-control">
                            <option value="">Select Airline</option>
                            <?php while($airline = $airlines_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($airline['airline_name']); ?>">
                                    <?php echo htmlspecialchars($airline['airline_name']); ?>
                                </option>
                            <?php endwhile; ?>
                            <option value="Airport Authority">Airport Authority</option>
                            <option value="Ground Services">Ground Services</option>
                            <option value="Cleaning Services">Cleaning Services</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Job Title</label>
                        <input type="text" name="job_title" class="form-control">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editUserModal')">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit User</h2>
            <form method="POST" id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="airline_staff">Airline Staff</option>
                            <option value="administrator">Administrator</option>
                            <option value="airport_manager">Airport Manager</option>
                            <option value="service_staff">Service Staff</option>
                            <option value="cleaning_staff">Cleaning Staff</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Airline/Organization</label>
                        <select name="airline" id="edit_airline" class="form-control">
                            <option value="">Select Airline</option>
                            <?php 
                            $airlines_result->data_seek(0);
                            while($airline = $airlines_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($airline['airline_name']); ?>">
                                    <?php echo htmlspecialchars($airline['airline_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" id="edit_department" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Job Title</label>
                        <input type="text" name="job_title" id="edit_job_title" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1"> Active Account
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('passwordModal')">&times;</span>
            <h2><i class="fas fa-key"></i> Reset Password</h2>
            <form method="POST" id="passwordForm">
                <input type="hidden" name="user_id" id="password_user_id">
                
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    This will immediately change the user's password. They will need to use the new password on their next login.
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('passwordModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Airport Management System - User Management</p>
        </div>
    </footer>
    
    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addUserModal').style.display = 'block';
        }
        
        function openEditModal(userId) {
            // Fetch user details using AJAX
            fetch('get_user_details.php?id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        document.getElementById('edit_user_id').value = user.id;
                        document.getElementById('edit_name').value = user.name;
                        document.getElementById('edit_email').value = user.email;
                        document.getElementById('edit_role').value = user.role;
                        document.getElementById('edit_airline').value = user.airline || '';
                        document.getElementById('edit_phone').value = user.phone_number || '';
                        document.getElementById('edit_department').value = user.department || '';
                        document.getElementById('edit_job_title').value = user.job_title || '';
                        document.getElementById('edit_is_active').checked = user.is_active == 1;
                        
                        document.getElementById('editUserModal').style.display = 'block';
                    } else {
                        alert('Error loading user details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading user details. Please try again.');
                });
        }
        
        function openPasswordModal(userId, userName) {
            document.getElementById('password_user_id').value = userId;
            document.getElementById('passwordModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>