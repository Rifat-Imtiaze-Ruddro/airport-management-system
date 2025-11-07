<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communications - SkyPort Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>💬 Communications</h1>
            <p>Airline-Airport correspondence system</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Messages</h2>
                <button class="btn btn-primary">New Message</button>
            </div>
            <div class="card-body">
                <p>Communication system will be implemented here.</p>
                <ul>
                    <li>Send messages to airlines</li>
                    <li>Receive service requests</li>
                    <li>Emergency notifications</li>
                    <li>Schedule updates</li>
                    <li>General announcements</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>