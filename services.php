<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

// Check if user has permission
requireRole(['administrator', 'airport_manager', 'airline_staff', 'service_staff']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>🔧 Service Requests</h1>
            <p>Manage airport service requests</p>
            <div class="user-role">
                <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?>
                </span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Service Management</h2>
                <?php if (canAccessServiceRequests()): ?>
                <button class="btn btn-primary">New Service Request</button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p><strong>Access Level:</strong> 
                <?php 
                if (in_array($_SESSION['user_role'], ['administrator', 'airport_manager'])) {
                    echo "Full access to all service requests";
                } elseif ($_SESSION['user_role'] === 'airline_staff') {
                    echo "Create and view service requests for " . $_SESSION['user_airline'];
                } elseif ($_SESSION['user_role'] === 'service_staff') {
                    echo "View and update assigned service requests";
                }
                ?>
                </p>
                
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Flight</th>
                            <th>Service Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <?php if ($_SESSION['user_role'] !== 'service_staff'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SR-2024-001</td>
                            <td>AA245</td>
                            <td>Fueling</td>
                            <td><span class="status status-scheduled">High</span></td>
                            <td><span class="status status-scheduled">Pending</span></td>
                            <td>14:15</td>
                            <?php if ($_SESSION['user_role'] !== 'service_staff'): ?>
                            <td>
                                <button class="btn btn-warning">Update</button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <td>SR-2024-002</td>
                            <td>DL189</td>
                            <td>Catering</td>
                            <td><span class="status status-boarding">Normal</span></td>
                            <td><span class="status status-boarding">In Progress</span></td>
                            <td>14:30</td>
                            <?php if ($_SESSION['user_role'] !== 'service_staff'): ?>
                            <td>
                                <button class="btn btn-warning">Update</button>
                            </td>
                            <?php endif; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; 2023 Airport Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>