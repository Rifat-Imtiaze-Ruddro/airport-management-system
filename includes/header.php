<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-plane-departure"></i> SkyPort Manager
                <?php if (isset($_SESSION['user_role'])): ?>
                    <small style="margin-left: 15px; font-size: 0.8rem;">
                        <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?>
                        </span>
                    </small>
                <?php endif; ?>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="flights.php"><i class="fas fa-plane"></i> Flights</a></li>
                    <li><a href="services.php"><i class="fas fa-tools"></i> Services</a></li>
                    <li><a href="communications.php"><i class="fas fa-comments"></i> Messages</a></li>
                    
                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['administrator', 'airport_manager'])): ?>
                    <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                    <li>
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> 
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Logout'; ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>