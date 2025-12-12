<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

requireLogin();


$flights_count = 0;
$services_count = 0;
$pending_requests = 0;
$messages_count = 0;

// Count scheduled flights
$sql = "SELECT COUNT(*) as count FROM flights WHERE status = 'scheduled'";
$result = $conn->query($sql);
if ($result) {
    $flights_count = $result->fetch_assoc()['count'];
}

// Count active services
$sql = "SELECT COUNT(*) as count FROM service_requests WHERE status IN ('pending', 'in_progress')";
$result = $conn->query($sql);
if ($result) {
    $services_count = $result->fetch_assoc()['count'];
}

// Count pending requests
$sql = "SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'";
$result = $conn->query($sql);
if ($result) {
    $pending_requests = $result->fetch_assoc()['count'];
}

// Count unread messages for current user
$sql = "SELECT COUNT(*) as count 
        FROM message_recipients mr
        JOIN communications c ON mr.message_id = c.id
        WHERE mr.recipient_id = ? 
        AND mr.is_read = 0
        AND mr.is_deleted = 0
        AND c.is_archived = 0";

$pageTitle = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard">
            <div class="sidebar">
    <h3><i class="fas fa-airport"></i> Airport Portal</h3>
    <ul>
        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        
        <?php if (canAccessFlightManagement()): ?>
        <li><a href="flights.php"><i class="fas fa-plane"></i> Flight Schedule</a></li>
        <?php else: ?>
        <li class="access-restricted"><a href="flights.php"><i class="fas fa-plane"></i> Flight Schedule</a></li>
        <?php endif; ?>
        
        <?php if (canAccessServiceRequests()): ?>
        <li><a href="services.php"><i class="fas fa-tools"></i> Service Requests</a></li>
        <?php else: ?>
        <li class="access-restricted"><a href="services.php"><i class="fas fa-tools"></i> Service Requests</a></li>
        <?php endif; ?>
        
        <li><a href="communications.php"><i class="fas fa-comments"></i> Communications</a></li>
        
        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['administrator', 'airport_manager'])): ?>
        <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
        <?php endif; ?>
        
        <li><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
    </ul>
    
    <div class="user-role-display">
        <p><strong>Your Role:</strong> 
        <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
            <?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?>
        </span>
        </p>
        <p><strong>Airline:</strong> <?php echo $_SESSION['user_airline']; ?></p>
    </div>
    
    <!-- Quick Access Section -->
    <h3><i class="fas fa-bolt"></i> Quick Access</h3>
    <ul>
        <li><a href="services.php?priority=emergency"><i class="fas fa-exclamation-triangle"></i> Emergency Requests</a></li>
        <li><a href="services.php?status=pending"><i class="fas fa-clock"></i> Pending Services</a></li>
        <li><a href="flights.php?status=boarding"><i class="fas fa-person-boarding"></i> Boarding Flights</a></li>
        <li><a href="flights.php?status=delayed"><i class="fas fa-clock"></i> Delayed Flights</a></li>
    </ul>
</div>
            
            <div class="main-content">
                <div class="page-header">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo $_SESSION['user_name']; ?>! 👋</p>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3><?php echo $flights_count; ?></h3>
                        <p>Scheduled Flights</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $services_count; ?></h3>
                        <p>Active Services</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $pending_requests; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $messages_count; ?></h3>
                        <p>New Messages</p>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>📅 Today's Flight Schedule</h2>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Get today's flights
                            $sql = "SELECT f.*, a.airline_name, a.airline_code 
                                    FROM flights f 
                                    JOIN airlines a ON f.airline_id = a.id 
                                    WHERE DATE(f.scheduled_departure) = CURDATE() 
                                    ORDER BY f.scheduled_departure 
                                    LIMIT 5";
                            $today_flights = $conn->query($sql);
                            
                            if ($today_flights && $today_flights->num_rows > 0): 
                                while($flight = $today_flights->fetch_assoc()): 
                            ?>
                                <div class="flight-item">
                                    <span class="flight-number"><?php echo $flight['airline_code'] . $flight['flight_number']; ?></span>
                                    <span class="flight-route"><?php echo $flight['origin'] . ' → ' . $flight['destination']; ?></span>
                                    <span class="flight-time"><?php echo date('H:i', strtotime($flight['scheduled_departure'])); ?></span>
                                    <span class="status status-<?php echo $flight['status']; ?>"><?php echo ucfirst($flight['status']); ?></span>
                                </div>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <p>No flights scheduled for today.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>🔔 Recent Service Requests</h2>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Get recent service requests
                            $sql = "SELECT sr.*, f.flight_number, a.airline_code 
                                    FROM service_requests sr 
                                    JOIN flights f ON sr.flight_id = f.id 
                                    JOIN airlines a ON f.airline_id = a.id 
                                    ORDER BY sr.created_at DESC 
                                    LIMIT 3";
                            $recent_services = $conn->query($sql);
                            
                            if ($recent_services && $recent_services->num_rows > 0): 
                                while($service = $recent_services->fetch_assoc()): 
                            ?>
                                <div class="notification">
                                    <strong><?php echo ucfirst($service['service_type']); ?> - <?php echo $service['airline_code'] . $service['flight_number']; ?></strong>
                                    <p>Status: <?php echo ucfirst(str_replace('_', ' ', $service['status'])); ?></p>
                                    <small><?php echo date('M j, H:i', strtotime($service['requested_time'])); ?></small>
                                </div>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <p>No recent service requests.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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