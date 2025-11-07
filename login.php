<?php
session_start();
include 'includes/config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($email) && !empty($password)) {
        // For demo purposes - simple authentication
        // In production, use password_verify() with hashed passwords
        if ($email === 'admin@airport.com' && $password === 'password') {
            // Get user from database
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_airline'] = $user['airline'];
                $_SESSION['logged_in'] = true;
                
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid email or password. Use: admin@airport.com / password";
        }
    } else {
        $error = "Please enter both email and password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SkyPort Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Login to SkyPort</h1>
                <p>Enter your credentials to access the system</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="demo-credentials">
                <p><strong>Demo Credentials:</strong></p>
                <p>Email: <strong>admin@airport.com</strong></p>
                <p>Password: <strong>password</strong></p>
            </div>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="index.html">← Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>