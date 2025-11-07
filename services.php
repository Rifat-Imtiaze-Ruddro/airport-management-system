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
    <title>Services - SkyPort Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>🔧 Service Requests</h1>
            <p>Manage airport service requests</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Service Management</h2>
                <button class="btn btn-primary">New Service Request</button>
            </div>
            <div class="card-body">
                <p>Service request management system will be implemented here.</p>
                <ul>
                    <li>Ground handling services</li>
                    <li>Fueling requests</li>
                    <li>Catering services</li>
                    <li>Cleaning services</li>
                    <li>Maintenance requests</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>