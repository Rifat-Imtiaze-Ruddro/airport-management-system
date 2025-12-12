<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

// Check if user has permission
requireRole(['administrator', 'airport_manager', 'airline_staff', 'service_staff']);

$pageTitle = "Service Requests";
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_request'])) {
        // Create new service request
        $flight_id = (int)$_POST['flight_id'];
        $service_type = $conn->real_escape_string($_POST['service_type']);
        $priority = $conn->real_escape_string($_POST['priority']);
        $department = $conn->real_escape_string($_POST['department']);
        $notes = $conn->real_escape_string($_POST['notes']);
        $estimated_completion = $conn->real_escape_string($_POST['estimated_completion']);
        
        // Generate unique request ID
        $request_id = 'SR-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO service_requests (request_id, flight_id, service_type, priority, 
                department, notes, estimated_completion_time, requested_time, created_by) 
                VALUES ('$request_id', $flight_id, '$service_type', '$priority', 
                '$department', '$notes', '$estimated_completion', NOW(), {$_SESSION['user_id']})";
        
        if ($conn->query($sql)) {
            $message = "Service request $request_id created successfully!";
            $message_type = 'success';
        } else {
            $message = "Error creating request: " . $conn->error;
            $message_type = 'danger';
        }
    }
    elseif (isset($_POST['update_status'])) {
        // Update request status
        $request_id = (int)$_POST['request_id'];
        $status = $conn->real_escape_string($_POST['status']);
        $completion_notes = $conn->real_escape_string($_POST['completion_notes']);
        
        $sql = "UPDATE service_requests SET status = '$status'";
        
        if ($status === 'completed') {
            $sql .= ", completed_time = NOW(), completion_notes = '$completion_notes'";
        }
        
        $sql .= " WHERE id = $request_id";
        
        if ($conn->query($sql)) {
            $message = "Service request status updated to " . ucfirst($status) . "!";
            $message_type = 'success';
        } else {
            $message = "Error updating request: " . $conn->error;
            $message_type = 'danger';
        }
    }
    elseif (isset($_POST['assign_staff'])) {
        // Assign staff to request
        $request_id = (int)$_POST['request_id'];
        $staff_id = (int)$_POST['staff_id'];
        
        $sql = "INSERT INTO service_assignments (request_id, staff_id, assigned_by, status) 
                VALUES ($request_id, $staff_id, {$_SESSION['user_id']}, 'assigned')";
        
        if ($conn->query($sql)) {
            // Update main request status to in_progress
            $update_sql = "UPDATE service_requests SET status = 'in_progress' WHERE id = $request_id";
            $conn->query($update_sql);
            
            $message = "Staff assigned to service request!";
            $message_type = 'success';
        } else {
            $message = "Error assigning staff: " . $conn->error;
            $message_type = 'danger';
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$priority = isset($_GET['priority']) ? $conn->real_escape_string($_GET['priority']) : '';
$department = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';
$date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');

// Build query based on user role
$sql = "SELECT sr.*, f.flight_number, a.airline_code, a.airline_name,
               u.name as created_by_name,
               (SELECT COUNT(*) FROM service_assignments sa WHERE sa.request_id = sr.id) as assignment_count
        FROM service_requests sr 
        JOIN flights f ON sr.flight_id = f.id 
        JOIN airlines a ON f.airline_id = a.id
        LEFT JOIN users u ON sr.created_by = u.id
        WHERE 1=1";

// Role-based filters
if ($_SESSION['user_role'] === 'service_staff') {
    // Service staff can only see requests in their department
    $user_dept_sql = "SELECT department FROM users WHERE id = {$_SESSION['user_id']}";
    $dept_result = $conn->query($user_dept_sql);
    if ($dept_result->num_rows > 0) {
        $user_dept = $dept_result->fetch_assoc()['department'];
        if ($user_dept) {
            $sql .= " AND sr.department = '$user_dept'";
        }
    }
} elseif ($_SESSION['user_role'] === 'airline_staff') {
    // Airline staff can only see their airline's flights
    $user_airline = $_SESSION['user_airline'];
    $airline_id_sql = "SELECT id FROM airlines WHERE airline_name LIKE '%$user_airline%'";
    $airline_result = $conn->query($airline_id_sql);
    if ($airline_result->num_rows > 0) {
        $airline_id = $airline_result->fetch_assoc()['id'];
        $sql .= " AND f.airline_id = $airline_id";
    }
}

// Apply filters
if (!empty($search)) {
    $sql .= " AND (sr.request_id LIKE '%$search%' OR f.flight_number LIKE '%$search%' 
              OR a.airline_name LIKE '%$search%' OR sr.service_type LIKE '%$search%')";
}
if (!empty($status)) {
    $sql .= " AND sr.status = '$status'";
}
if (!empty($priority)) {
    $sql .= " AND sr.priority = '$priority'";
}
if (!empty($department)) {
    $sql .= " AND sr.department = '$department'";
}
if (!empty($date)) {
    $sql .= " AND DATE(sr.requested_time) = '$date'";
}

$sql .= " ORDER BY 
          CASE sr.priority 
            WHEN 'emergency' THEN 1
            WHEN 'high' THEN 2
            WHEN 'normal' THEN 3
            WHEN 'low' THEN 4
          END,
          sr.requested_time DESC";

$requests_result = $conn->query($sql);

// Get statistics
$stats_sql = "SELECT 
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
              SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
              SUM(CASE WHEN priority = 'emergency' THEN 1 ELSE 0 END) as emergency,
              COUNT(*) as total
              FROM service_requests";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get flights for dropdown
$flights_sql = "SELECT f.id, f.flight_number, a.airline_code, f.origin, f.destination 
                FROM flights f 
                JOIN airlines a ON f.airline_id = a.id 
                WHERE f.scheduled_departure > NOW() - INTERVAL 2 HOUR
                ORDER BY f.scheduled_departure ASC";
$flights_result = $conn->query($flights_sql);

// Get service staff for assignment
$staff_sql = "SELECT id, name, role FROM users WHERE role IN ('service_staff', 'cleaning_staff') 
              ORDER BY name";
$staff_result = $conn->query($staff_sql);

// Get service categories
$categories_sql = "SELECT * FROM service_categories ORDER BY category_name";
$categories_result = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .service-stats { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; min-width: 150px; }
        .stat-card h3 { font-size: 1.8rem; margin-bottom: 5px; }
        .priority-emergency { background: #dc3545; color: white; }
        .priority-high { background: #fd7e14; color: white; }
        .priority-normal { background: #ffc107; color: black; }
        .priority-low { background: #28a745; color: white; }
        .service-filters { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .request-item { border-left: 4px solid; margin-bottom: 10px; padding: 10px; background: white; border-radius: 4px; }
        .request-item.emergency { border-left-color: #dc3545; background: #f8d7da; }
        .request-item.high { border-left-color: #fd7e14; background: #fff3cd; }
        .request-item.normal { border-left-color: #ffc107; background: #f8f9fa; }
        .request-item.low { border-left-color: #28a745; background: #d4edda; }
        .request-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .request-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .request-actions { display: flex; gap: 5px; margin-top: 10px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 50px auto; padding: 20px; border-radius: 8px; max-width: 800px; position: relative; max-height: 80vh; overflow-y: auto; }
        .close-modal { position: absolute; top: 15px; right: 15px; font-size: 24px; cursor: pointer; color: #666; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        @media (max-width: 768px) { 
            .form-row, .request-details { grid-template-columns: 1fr; }
            .service-stats { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-tools"></i> Service Requests</h1>
            <p>Manage airport ground services and maintenance requests</p>
            <div class="user-role">
                <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?>
                </span>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Service Statistics -->
        <div class="service-stats">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Requests</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['in_progress']; ?></h3>
                <p>In Progress</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['completed']; ?></h3>
                <p>Completed</p>
            </div>
            <div class="stat-card" style="background: #dc3545; color: white;">
                <h3><?php echo $stats['emergency']; ?></h3>
                <p>Emergency Priority</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="service-filters">
            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label>Search:</label>
                    <input type="text" name="search" class="form-control" placeholder="Request ID, flight, service..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <label>Status:</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label>Priority:</label>
                    <select name="priority" class="form-control">
                        <option value="">All Priorities</option>
                        <option value="emergency" <?php echo $priority === 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="normal" <?php echo $priority === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div>
                    <label>Department:</label>
                    <select name="department" class="form-control">
                        <option value="">All Departments</option>
                        <option value="fueling" <?php echo $department === 'fueling' ? 'selected' : ''; ?>>Fueling</option>
                        <option value="catering" <?php echo $department === 'catering' ? 'selected' : ''; ?>>Catering</option>
                        <option value="cleaning" <?php echo $department === 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                        <option value="maintenance" <?php echo $department === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="baggage" <?php echo $department === 'baggage' ? 'selected' : ''; ?>>Baggage</option>
                        <option value="gate_ops" <?php echo $department === 'gate_ops' ? 'selected' : ''; ?>>Gate Operations</option>
                        <option value="security" <?php echo $department === 'security' ? 'selected' : ''; ?>>Security</option>
                    </select>
                </div>
                <div>
                    <label>Date:</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
                </div>
                <div style="display: flex; align-items: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="services.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons mb-4">
            <?php if (in_array($_SESSION['user_role'], ['administrator', 'airport_manager', 'airline_staff'])): ?>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Create Service Request
                </button>
            <?php endif; ?>
            
            <?php if (in_array($_SESSION['user_role'], ['administrator', 'airport_manager'])): ?>
                <button class="btn btn-info" onclick="openAssignModal()">
                    <i class="fas fa-user-check"></i> Assign Staff
                </button>
            <?php endif; ?>
            
            <button class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
            
            <span class="text-muted" style="margin-left: auto;">
                Showing: <?php echo $requests_result->num_rows; ?> requests
            </span>
        </div>
        
        <!-- Service Requests List -->
        <div class="card">
            <div class="card-header">
                <h2>Service Requests</h2>
                <small>Sorted by priority and request time</small>
            </div>
            <div class="card-body">
                <?php if ($requests_result->num_rows > 0): ?>
                    <?php while($request = $requests_result->fetch_assoc()): ?>
                        <div class="request-item <?php echo $request['priority']; ?>">
                            <div class="request-header">
                                <div>
                                    <strong><?php echo $request['request_id']; ?></strong> - 
                                    <span class="status status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                    <span class="priority-<?php echo $request['priority']; ?>" style="padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                                        <?php echo ucfirst($request['priority']); ?> Priority
                                    </span>
                                </div>
                                <div>
                                    <small>Created: <?php echo date('M j, H:i', strtotime($request['requested_time'])); ?></small>
                                    <?php if ($request['estimated_completion_time']): ?>
                                        <br><small>Est. Complete: <?php echo date('H:i', strtotime($request['estimated_completion_time'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="request-details">
                                <div>
                                    <strong>Flight:</strong> <?php echo $request['airline_code'] . $request['flight_number']; ?>
                                </div>
                                <div>
                                    <strong>Service:</strong> <?php echo ucfirst($request['service_type']); ?>
                                </div>
                                <div>
                                    <strong>Department:</strong> <?php echo ucfirst(str_replace('_', ' ', $request['department'])); ?>
                                </div>
                                <div>
                                    <strong>Created By:</strong> <?php echo $request['created_by_name']; ?>
                                </div>
                                <div>
                                    <strong>Assignments:</strong> <?php echo $request['assignment_count']; ?> staff assigned
                                </div>
                            </div>
                            
                            <?php if ($request['notes']): ?>
                                <div style="margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.5); border-radius: 4px;">
                                    <strong>Notes:</strong> <?php echo $request['notes']; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="request-actions">
                                <?php if ($_SESSION['user_role'] === 'service_staff' && $request['status'] === 'pending'): ?>
                                    <button class="btn btn-warning btn-sm" onclick="updateStatus(<?php echo $request['id']; ?>, 'in_progress')">
                                        <i class="fas fa-play"></i> Start Work
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($_SESSION['user_role'] === 'service_staff' && $request['status'] === 'in_progress'): ?>
                                    <button class="btn btn-success btn-sm" onclick="openCompleteModal(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-check"></i> Mark Complete
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (in_array($_SESSION['user_role'], ['administrator', 'airport_manager'])): ?>
                                    <button class="btn btn-info btn-sm" onclick="openAssignToModal(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-user-plus"></i> Assign Staff
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($request['status'] !== 'completed' && $request['status'] !== 'cancelled'): ?>
                                    <button class="btn btn-danger btn-sm" onclick="updateStatus(<?php echo $request['id']; ?>, 'cancelled')">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                        <h3>No Service Requests Found</h3>
                        <p>Try adjusting your filters or create a new service request.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Request Modal -->
    <div id="createRequestModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('createRequestModal')">&times;</span>
            <h2><i class="fas fa-plus-circle"></i> Create Service Request</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Flight *</label>
                        <select name="flight_id" class="form-control" required>
                            <option value="">Select Flight</option>
                            <?php while($flight = $flights_result->fetch_assoc()): ?>
                                <option value="<?php echo $flight['id']; ?>">
                                    <?php echo $flight['airline_code'] . $flight['flight_number']; ?> 
                                    (<?php echo $flight['origin']; ?> → <?php echo $flight['destination']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Service Type *</label>
                        <select name="service_type" class="form-control" required onchange="updateDepartment(this)">
                            <option value="">Select Service Type</option>
                            <?php 
                            $categories_result->data_seek(0);
                            while($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_code']; ?>" 
                                        data-department="<?php echo $category['department']; ?>"
                                        data-priority="<?php echo $category['default_priority']; ?>">
                                    <?php echo $category['category_name']; ?> 
                                    (<?php echo $category['estimated_duration_minutes']; ?> min)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Priority *</label>
                        <select name="priority" id="priority_select" class="form-control" required>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="emergency">Emergency</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department" id="department_select" class="form-control" required>
                            <option value="maintenance">Maintenance</option>
                            <option value="fueling">Fueling</option>
                            <option value="catering">Catering</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="baggage">Baggage</option>
                            <option value="gate_ops">Gate Operations</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Estimated Completion Time</label>
                        <input type="datetime-local" name="estimated_completion" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes / Instructions</label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Describe the service request, any special requirements, or instructions..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="create_request" class="btn btn-primary">Create Request</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createRequestModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Complete Request Modal -->
    <div id="completeRequestModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('completeRequestModal')">&times;</span>
            <h2><i class="fas fa-check-circle"></i> Complete Service Request</h2>
            <form method="POST" id="completeForm">
                <input type="hidden" name="request_id" id="complete_request_id">
                <input type="hidden" name="status" value="completed">
                
                <div class="form-group">
                    <label>Completion Notes *</label>
                    <textarea name="completion_notes" class="form-control" rows="4" required 
                              placeholder="Describe work completed, any issues encountered, or final notes..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update_status" class="btn btn-success">Mark as Complete</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('completeRequestModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Assign Staff Modal -->
    <div id="assignStaffModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('assignStaffModal')">&times;</span>
            <h2><i class="fas fa-user-check"></i> Assign Staff to Request</h2>
            <form method="POST" id="assignForm">
                <input type="hidden" name="request_id" id="assign_request_id">
                
                <div class="form-group">
                    <label>Select Staff Member *</label>
                    <select name="staff_id" class="form-control" required>
                        <option value="">Select Staff</option>
                        <?php while($staff = $staff_result->fetch_assoc()): ?>
                            <option value="<?php echo $staff['id']; ?>">
                                <?php echo $staff['name']; ?> (<?php echo ucfirst(str_replace('_', ' ', $staff['role'])); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="assign_staff" class="btn btn-primary">Assign Staff</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('assignStaffModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Airport Management System - Service Requests</p>
        </div>
    </footer>
    
    <script>
        // Modal functions
        function openCreateModal() {
            document.getElementById('createRequestModal').style.display = 'block';
        }
        
        function openCompleteModal(requestId) {
            document.getElementById('complete_request_id').value = requestId;
            document.getElementById('completeRequestModal').style.display = 'block';
        }
        
        function openAssignToModal(requestId) {
            document.getElementById('assign_request_id').value = requestId;
            document.getElementById('assignStaffModal').style.display = 'block';
        }
        
        function openAssignModal() {
            // This would open a different modal for bulk assignments
            alert('Bulk assignment feature coming soon!');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Update department and priority based on service type
        function updateDepartment(select) {
            const selectedOption = select.options[select.selectedIndex];
            const department = selectedOption.getAttribute('data-department');
            const priority = selectedOption.getAttribute('data-priority');
            
            if (department) {
                document.getElementById('department_select').value = department;
            }
            if (priority) {
                document.getElementById('priority_select').value = priority;
            }
        }
        
        // Update status using AJAX
        function updateStatus(requestId, newStatus) {
            if (confirm(`Change request status to ${newStatus}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const requestIdInput = document.createElement('input');
                requestIdInput.type = 'hidden';
                requestIdInput.name = 'request_id';
                requestIdInput.value = requestId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'update_status';
                submitInput.value = '1';
                
                form.appendChild(requestIdInput);
                form.appendChild(statusInput);
                form.appendChild(submitInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>