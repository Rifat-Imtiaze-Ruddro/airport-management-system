<?php
session_start();

// Check if user is logged in
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
    <title>Dashboard - SkyPort Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard">
            <div class="sidebar">
                <h3>🏢 Airport Portal</h3>
                <ul>
                    <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                    <li><a href="flights.php">✈️ Flight Schedule</a></li>
                    <li><a href="services.php">🔧 Service Requests</a></li>
                    <li><a href="communications.php">💬 Communications</a></li>
                </ul>
                
                <h3>✈️ Airline Services</h3>
                <ul>
                    <li><a href="#">🛬 Gate Assignment</a></li>
                    <li><a href="#">⛽ Fuel Services</a></li>
                    <li><a href="#">🍱 Catering</a></li>
                    <li><a href="#">🧹 Cleaning</a></li>
                </ul>
            </div>
            
            <div class="main-content">
                <div class="page-header">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo $_SESSION['user_name']; ?>! 👋</p>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3>12</h3>
                        <p>Scheduled Flights</p>
                    </div>
                    <div class="stat-card">
                        <h3>8</h3>
                        <p>Active Services</p>
                    </div>
                    <div class="stat-card">
                        <h3>3</h3>
                        <p>Pending Requests</p>
                    </div>
                    <div class="stat-card">
                        <h3>5</h3>
                        <p>New Messages</p>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>📅 Today's Flight Schedule</h2>
                        </div>
                        <div class="card-body">
                            <div class="flight-item">
                                <span class="flight-number">AA245</span>
                                <span class="flight-route">JFK → LHR</span>
                                <span class="flight-time">14:30 - 15:45</span>
                                <span class="status status-scheduled">Scheduled</span>
                            </div>
                            <div class="flight-item">
                                <span class="flight-number">DL189</span>
                                <span class="flight-route">ATL → CDG</span>
                                <span class="flight-time">15:00 - 16:20</span>
                                <span class="status status-boarding">Boarding</span>
                            </div>
                            <div class="flight-item">
                                <span class="flight-number">UA076</span>
                                <span class="flight-route">ORD → FRA</span>
                                <span class="flight-time">16:15 - 17:30</span>
                                <span class="status status-delayed">Delayed</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>🔔 Recent Notifications</h2>
                        </div>
                        <div class="card-body">
                            <div class="notification">
                                <strong>Gate Change</strong>
                                <p>Flight BA123 moved from Gate A12 to B08</p>
                                <small>10 minutes ago</small>
                            </div>
                            <div class="notification">
                                <strong>New Service Request</strong>
                                <p>Fueling requested for Flight DL189</p>
                                <small>25 minutes ago</small>
                            </div>
                            <div class="notification">
                                <strong>Weather Alert</strong>
                                <p>Possible delays due to incoming weather</p>
                                <small>1 hour ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; 2023 SkyPort Manager. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>