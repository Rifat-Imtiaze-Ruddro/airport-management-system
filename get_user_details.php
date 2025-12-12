<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

// Only allow administrators and airport managers
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!in_array($_SESSION['user_role'], ['administrator', 'airport_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No user ID provided']);
    exit();
}

$user_id = (int)$_GET['id'];

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Don't send password hash
    unset($user['password']);
    unset($user['reset_token']);
    
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>