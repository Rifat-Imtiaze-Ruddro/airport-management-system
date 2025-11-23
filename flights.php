<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

// Check if user has permission to access flights
requireRole(['administrator', 'airport_manager', 'airline_staff']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flights - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>✈️ Flight Management</h1>
            <p>Manage flight schedules and operations</p>
            <div class="user-role">
                <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?>
                </span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Flight Schedule</h2>
                <?php if (canAccessAdminFunctions()): ?>
                <button class="btn btn-primary">Add New Flight</button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p><strong>Access Level:</strong> 
                <?php 
                if ($_SESSION['user_role'] === 'administrator') {
                    echo "Full administrative access to all flights";
                } elseif ($_SESSION['user_role'] === 'airport_manager') {
                    echo "Airport-wide flight management";
                } elseif ($_SESSION['user_role'] === 'airline_staff') {
                    echo "View and manage " . $_SESSION['user_airline'] . " flights only";
                }
                ?>
                </p>
                
                <table>
                    <thead>
                        <tr>
                            <th>Flight</th>
                            <th>Airline</th>
                            <th>Route</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <th>Gate</th>
                            <?php if (canAccessAdminFunctions()): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>AA245</td>
                            <td>American Airlines</td>
                            <td>JFK → LHR</td>
                            <td>14:30 - 15:45</td>
                            <td><span class="status status-scheduled">Scheduled</span></td>
                            <td>B12</td>
                            <?php if (canAccessAdminFunctions()): ?>
                            <td>
                                <button class="btn btn-warning">Edit</button>
                                <button class="btn btn-danger">Delete</button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <td>DL189</td>
                            <td>Delta Air Lines</td>
                            <td>ATL → CDG</td>
                            <td>15:00 - 16:20</td>
                            <td><span class="status status-boarding">Boarding</span></td>
                            <td>A08</td>
                            <?php if (canAccessAdminFunctions()): ?>
                            <td>
                                <button class="btn btn-warning">Edit</button>
                                <button class="btn btn-danger">Delete</button>
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
        <p>&copy; <?php echo date('Y'); ?> Airport Management System</p>
    </div>
    </footer>
</body>
</html>