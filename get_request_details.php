<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

// check permissions
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No request ID provided']);
    exit();
}

$request_id = (int)$_GET['id'];

// Get request details with flight and assignment info
$sql = "SELECT sr.*, f.flight_number, a.airline_code, a.airline_name,
               u.name as created_by_name,
               (SELECT GROUP_CONCAT(CONCAT(st.name, ' (', st.role, ')') SEPARATOR ', ') 
                FROM service_assignments sa 
                JOIN users st ON sa.staff_id = st.id 
                WHERE sa.request_id = sr.id) as assigned_staff
        FROM service_requests sr 
        JOIN flights f ON sr.flight_id = f.id 
        JOIN airlines a ON f.airline_id = a.id
        LEFT JOIN users u ON sr.created_by = u.id
        WHERE sr.id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $request = $result->fetch_assoc();
    echo json_encode(['success' => true, 'request' => $request]);
} else {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
}

$stmt->close();
$conn->close();
?>