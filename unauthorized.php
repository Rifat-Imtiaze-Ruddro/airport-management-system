<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="error-page">
            <div class="error-icon">🚫</div>
            <h1>Access Denied</h1>
            <p>You don't have permission to access this page.</p>
            <?php if (isset($_SESSION['user_role'])): ?>
            <p>Your role: <strong><?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?></strong></p>
            <?php endif; ?>
            <div class="error-actions">
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>