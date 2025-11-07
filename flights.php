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
    <title>Flights - SkyPort Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>✈️ Flight Management</h1>
            <p>Manage flight schedules and operations</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Flight Schedule</h2>
                <button class="btn btn-primary">Add New Flight</button>
            </div>
            <div class="card-body">
                <p>Flight management system will be implemented here.</p>
                <ul>
                    <li>View all scheduled flights</li>
                    <li>Add new flights</li>
                    <li>Update flight status</li>
                    <li>Assign gates</li>
                    <li>Track arrivals and departures</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>