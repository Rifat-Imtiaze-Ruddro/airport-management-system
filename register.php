<?php
session_start();
include 'includes/config.php';


$name = $email = $airline = $role = '';
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $airline = trim($_POST['airline'] ?? '');
    $role = $_POST['role'] ?? 'airline_staff';
    
    if (empty($name) || empty($email) || empty($password) || empty($airline)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if already registered
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already registered";
        } else {
            //$hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_password = $password;
            
            // Insert new user
            $insert_sql = "INSERT INTO users (name, email, password, airline, role) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $airline, $role);
            
            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now login with your credentials.";
                // Clear
                $name = $email = $airline = $role = '';
            } else {
                $error = "Registration failed: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join Airport Management System</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required class="form-control" 
                           value="<?php echo htmlspecialchars($name); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required class="form-control"
                           value="<?php echo htmlspecialchars($email); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required class="form-control">
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="airline">Airline/Organization</label>
                    <input type="text" id="airline" name="airline" required class="form-control"
                           value="<?php echo htmlspecialchars($airline); ?>">
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required class="form-control">
                        <option value="airline_staff" <?php echo ($role === 'airline_staff') ? 'selected' : ''; ?>>Airline Staff</option>
                        <option value="airport_manager" <?php echo ($role === 'airport_manager') ? 'selected' : ''; ?>>Airport Manager</option>
                        <option value="service_staff" <?php echo ($role === 'service_staff') ? 'selected' : ''; ?>>Service Staff</option>
                        <option value="cleaning_staff" <?php echo ($role === 'cleaning_staff') ? 'selected' : ''; ?>>Cleaning Staff</option>
                    </select>
                    <small>
                        <strong>Role Permissions:</strong><br>
                        • Airline Staff: View own airline flights<br>
                        • Airport Manager: Full airport operations<br>
                        • Service Staff: Service requests only<br>
                        • Cleaning Staff: Basic task access only
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="index.html">← Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>