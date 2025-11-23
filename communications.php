<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communications - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>💬 Communications</h1>
            <p>Airline-Airport correspondence system</p>
            <div class="user-role">
                <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?>
                </span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Messages</h2>
                <button class="btn btn-primary">New Message</button>
            </div>
            <div class="card-body">
                <p><strong>Access Level:</strong> All users can send and receive messages</p>
                
                <div class="messages-list">
                    <div class="message-item">
                        <div class="message-header">
                            <strong>Gate Change Notification</strong>
                            <span class="message-time">10:30 AM</span>
                        </div>
                        <div class="message-preview">
                            Flight AA245 has been moved from Gate B12 to B15 due to operational requirements...
                        </div>
                        <div class="message-sender">
                            From: Airport Administrator
                        </div>
                    </div>
                    
                    <div class="message-item unread">
                        <div class="message-header">
                            <strong>Fueling Request</strong>
                            <span class="message-time">10:15 AM</span>
                        </div>
                        <div class="message-preview">
                            Requesting priority fueling for Flight DL189. Running tight on turnaround time...
                        </div>
                        <div class="message-sender">
                            From: John Smith - American Airlines
                        </div>
                    </div>
                </div>
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