<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

// Check permissions
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No flight ID provided']);
    exit();
}

$flight_id = (int)$_GET['id'];

// Get flight details
$sql = "SELECT f.*, a.airline_name, a.airline_code 
        FROM flights f 
        JOIN airlines a ON f.airline_id = a.id 
        WHERE f.id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $flight = $result->fetch_assoc();
    echo json_encode(['success' => true, 'flight' => $flight]);
} else {
    echo json_encode(['success' => false, 'message' => 'Flight not found']);
}

$stmt->close();
$conn->close();
?>