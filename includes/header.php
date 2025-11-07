<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo">🏢 SkyPort Manager</div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">📊 Dashboard</a></li>
                    <li><a href="flights.php">✈️ Flights</a></li>
                    <li><a href="services.php">🔧 Services</a></li>
                    <li><a href="communications.php">💬 Messages</a></li>
                    <li><a href="logout.php" class="logout-btn">🚪 Logout (<?php echo $_SESSION['user_name'] ?? 'User'; ?>)</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>