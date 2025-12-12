<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

// Check if user has permission to access flights
requireRole(['administrator', 'airport_manager', 'airline_staff']);

$pageTitle = "Flight Management";
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_flight'])) {
        // Add new flight
        $flight_number = $conn->real_escape_string($_POST['flight_number']);
        $airline_id = (int)$_POST['airline_id'];
        $origin = $conn->real_escape_string($_POST['origin']);
        $destination = $conn->real_escape_string($_POST['destination']);
        $scheduled_departure = $conn->real_escape_string($_POST['scheduled_departure']);
        $scheduled_arrival = $conn->real_escape_string($_POST['scheduled_arrival']);
        $gate = $conn->real_escape_string($_POST['gate']);
        $terminal = $conn->real_escape_string($_POST['terminal']);
        $status = $conn->real_escape_string($_POST['status']);
        $aircraft_type = $conn->real_escape_string($_POST['aircraft_type']);
        $checkin_counter = $conn->real_escape_string($_POST['checkin_counter']);
        $baggage_carousel = $conn->real_escape_string($_POST['baggage_carousel']);
        $passenger_count = (int)$_POST['passenger_count'];
        $notes = $conn->real_escape_string($_POST['notes']);
        
        $sql = "INSERT INTO flights (flight_number, airline_id, origin, destination, 
                scheduled_departure, scheduled_arrival, gate, terminal, status, 
                aircraft_type, checkin_counter, baggage_carousel, passenger_count, notes) 
                VALUES ('$flight_number', $airline_id, '$origin', '$destination', 
                '$scheduled_departure', '$scheduled_arrival', '$gate', '$terminal', '$status', 
                '$aircraft_type', '$checkin_counter', '$baggage_carousel', $passenger_count, '$notes')";
        
        if ($conn->query($sql)) {
            $message = "Flight $flight_number added successfully!";
            $message_type = 'success';
        } else {
            $message = "Error adding flight: " . $conn->error;
            $message_type = 'danger';
        }
    } 
    elseif (isset($_POST['update_flight'])) {
        // Update flight
        $flight_id = (int)$_POST['flight_id'];
        $status = $conn->real_escape_string($_POST['status']);
        $gate = $conn->real_escape_string($_POST['gate']);
        $terminal = $conn->real_escape_string($_POST['terminal']);
        $checkin_counter = $conn->real_escape_string($_POST['checkin_counter']);
        $baggage_carousel = $conn->real_escape_string($_POST['baggage_carousel']);
        $notes = $conn->real_escape_string($_POST['notes']);
        
        $sql = "UPDATE flights SET 
                status = '$status',
                gate = '$gate',
                terminal = '$terminal',
                checkin_counter = '$checkin_counter',
                baggage_carousel = '$baggage_carousel',
                notes = '$notes'
                WHERE id = $flight_id";
        
        if ($conn->query($sql)) {
            $message = "Flight updated successfully!";
            $message_type = 'success';
        } else {
            $message = "Error updating flight: " . $conn->error;
            $message_type = 'danger';
        }
    }
    elseif (isset($_POST['delete_flight'])) {
        // Delete flight
        if ($_SESSION['user_role'] === 'administrator') {
            $flight_id = (int)$_POST['flight_id'];
            
            $delete_sql = "DELETE FROM flights WHERE id = $flight_id";
            if ($conn->query($delete_sql)) {
                $message = "Flight deleted successfully!";
                $message_type = 'success';
            } else {
                $message = "Error deleting flight: " . $conn->error;
                $message_type = 'danger';
            }
        } else {
            $message = "You don't have permission to delete flights!";
            $message_type = 'danger';
        }
    }
}


// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$airline = isset($_GET['airline']) ? (int)$_GET['airline'] : 0;
$date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');

// Build query based on user role and filters
$sql = "SELECT f.*, a.airline_name, a.airline_code 
        FROM flights f 
        JOIN airlines a ON f.airline_id = a.id 
        WHERE 1=1";

// Apply filters
if (!empty($search)) {
    $sql .= " AND (f.flight_number LIKE '%$search%' OR a.airline_name LIKE '%$search%' OR f.origin LIKE '%$search%' OR f.destination LIKE '%$search%')";
}
if (!empty($status)) {
    $sql .= " AND f.status = '$status'";
}
if ($airline > 0) {
    $sql .= " AND f.airline_id = $airline";
}
if (!empty($date)) {
    $sql .= " AND DATE(f.scheduled_departure) = '$date'";
}

$sql .= " ORDER BY f.scheduled_departure ASC";

$flights_result = $conn->query($sql);

// Get airlines for dropdown
$airlines_sql = "SELECT * FROM airlines ORDER BY airline_name";
$airlines_result = $conn->query($airlines_sql);

// Get flight status counts for quick stats
$stats_sql = "SELECT status, COUNT(*) as count FROM flights GROUP BY status";
$stats_result = $conn->query($stats_sql);
$status_counts = [];
while ($row = $stats_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
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
        .quick-stats { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-badge { padding: 8px 15px; border-radius: 20px; font-size: 0.9rem; cursor: pointer; }
        .filters { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .flight-actions { display: flex; gap: 5px; }
        .flight-actions .btn { padding: 5px 10px; font-size: 0.85rem; }
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
            overflow-y: auto;
        }
        .modal-content { 
            background: white; 
            margin: 30px auto; 
            padding: 20px; 
            border-radius: 8px; 
            max-width: 800px; 
            position: relative;
            max-height: 85vh;
            overflow-y: auto;
        }
        .close-modal { 
            position: absolute; 
            top: 15px; 
            right: 15px; 
            font-size: 24px; 
            cursor: pointer; 
            color: #666;
            z-index: 1001;
        }
        .form-row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            margin-bottom: 15px; 
        }
        @media (max-width: 768px) { 
            .form-row { 
                grid-template-columns: 1fr; 
            }
            .modal-content {
                margin: 10px;
                width: calc(100% - 20px);
            }
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; font-weight: bold; }
        tr:hover { background-color: #f9f9f9; }
        
        /* Add scrollable form container */
        .modal-form-container {
            max-height: 65vh;
            overflow-y: auto;
            padding-right: 10px;
        }
        .modal-form-container::-webkit-scrollbar {
            width: 8px;
        }
        .modal-form-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .modal-form-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .modal-form-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-plane"></i> Flight Management</h1>
            <p>Manage flight schedules, gates, and operations</p>
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
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <?php 
            $statuses = ['scheduled', 'boarding', 'departed', 'arrived', 'delayed', 'cancelled'];
            foreach ($statuses as $status_item): 
                $count = $status_counts[$status_item] ?? 0;
                if ($count > 0):
            ?>
                <span class="stat-badge status-<?php echo $status_item; ?>" 
                      onclick="window.location='flights.php?status=<?php echo $status_item; ?>'">
                    <?php echo ucfirst($status_item); ?>: <?php echo $count; ?>
                </span>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="" class="filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label>Search:</label>
                    <input type="text" name="search" class="form-control" placeholder="Flight number, airline, route..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <label>Status:</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <?php foreach ($statuses as $status_item): ?>
                            <option value="<?php echo $status_item; ?>" <?php echo $status === $status_item ? 'selected' : ''; ?>>
                                <?php echo ucfirst($status_item); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Airline:</label>
                    <select name="airline" class="form-control">
                        <option value="">All Airlines</option>
                        <?php 
                        $airlines_result->data_seek(0);
                        while($airline_row = $airlines_result->fetch_assoc()): ?>
                            <option value="<?php echo $airline_row['id']; ?>" 
                                <?php echo $airline == $airline_row['id'] ? 'selected' : ''; ?>>
                                <?php echo $airline_row['airline_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Date:</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
                </div>
                <div style="display: flex; align-items: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="flights.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons mb-4">
            <?php if (canAccessAdminFunctions()): ?>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Flight
                </button>
            <?php endif; ?>
            <button class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print"></i> Print Schedule
            </button>
            <span class="text-muted" style="margin-left: auto;">
                Total: <?php echo $flights_result->num_rows; ?> flights found
            </span>
        </div>
        
        <!-- Flights Table -->
        <div class="card">
            <div class="card-header">
                <h2>Flight Schedule</h2>
                <small><?php echo date('F j, Y'); ?></small>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Flight</th>
                                <th>Airline</th>
                                <th>Route</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Gate/Terminal</th>
                                <th>Check-in/Baggage</th>
                                <th>Aircraft</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($flights_result->num_rows > 0): ?>
                                <?php while($flight = $flights_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $flight['airline_code'] . $flight['flight_number']; ?></strong>
                                        </td>
                                        <td><?php echo $flight['airline_name']; ?></td>
                                        <td>
                                            <strong><?php echo $flight['origin']; ?></strong> → 
                                            <strong><?php echo $flight['destination']; ?></strong>
                                        </td>
                                        <td>
                                            <div><strong>Dep:</strong> <?php echo date('H:i', strtotime($flight['scheduled_departure'])); ?></div>
                                            <div><strong>Arr:</strong> <?php echo date('H:i', strtotime($flight['scheduled_arrival'])); ?></div>
                                        </td>
                                        <td>
                                            <span class="status status-<?php echo $flight['status']; ?>">
                                                <?php echo ucfirst($flight['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><strong>Gate:</strong> <?php echo $flight['gate'] ?: 'TBD'; ?></div>
                                            <div><strong>Term:</strong> <?php echo $flight['terminal'] ?: '--'; ?></div>
                                        </td>
                                        <td>
                                            <div><strong>Check-in:</strong> <?php echo $flight['checkin_counter'] ?: '--'; ?></div>
                                            <div><strong>Baggage:</strong> <?php echo $flight['baggage_carousel'] ?: '--'; ?></div>
                                        </td>
                                        <td>
                                            <small><?php echo $flight['aircraft_type'] ?: '--'; ?></small>
                                        </td>
                                        <td>
                                            <div class="flight-actions">
                                                <button class="btn btn-warning btn-sm" onclick="openEditModal(<?php echo $flight['id']; ?>)">
                                                    Edit
                                                </button>
                                                <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
                                                        <button type="submit" name="delete_flight" class="btn btn-danger btn-sm" 
                                                                onclick="return confirm('Delete flight <?php echo $flight['airline_code'] . $flight['flight_number']; ?>?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 20px;">
                                        No flights found. Try adjusting your filters or add a new flight.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Flight Modal -->
    <div id="addFlightModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('addFlightModal')">&times;</span>
            <h2>Add New Flight</h2>
            <div class="modal-form-container">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Flight Number *</label>
                            <input type="text" name="flight_number" class="form-control" required 
                                   pattern="[A-Z0-9]{2,10}" title="2-10 alphanumeric characters">
                        </div>
                        <div class="form-group">
                            <label>Airline *</label>
                            <select name="airline_id" class="form-control" required>
                                <option value="">Select Airline</option>
                                <?php 
                                $airlines_result->data_seek(0);
                                while($airline = $airlines_result->fetch_assoc()): ?>
                                    <option value="<?php echo $airline['id']; ?>">
                                        <?php echo $airline['airline_name']; ?> (<?php echo $airline['airline_code']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Origin (IATA Code) *</label>
                            <input type="text" name="origin" class="form-control" required 
                                   pattern="[A-Z]{3}" title="3-letter IATA code" maxlength="3" style="text-transform: uppercase;">
                        </div>
                        <div class="form-group">
                            <label>Destination (IATA Code) *</label>
                            <input type="text" name="destination" class="form-control" required 
                                   pattern="[A-Z]{3}" title="3-letter IATA code" maxlength="3" style="text-transform: uppercase;">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Scheduled Departure *</label>
                            <input type="datetime-local" name="scheduled_departure" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Scheduled Arrival *</label>
                            <input type="datetime-local" name="scheduled_arrival" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Gate</label>
                            <input type="text" name="gate" class="form-control" maxlength="10">
                        </div>
                        <div class="form-group">
                            <label>Terminal</label>
                            <input type="text" name="terminal" class="form-control" maxlength="5">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="boarding">Boarding</option>
                                <option value="delayed">Delayed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Aircraft Type</label>
                            <input type="text" name="aircraft_type" class="form-control" placeholder="e.g., Boeing 737">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Check-in Counter</label>
                            <input type="text" name="checkin_counter" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Baggage Carousel</label>
                            <input type="text" name="baggage_carousel" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Passenger Count</label>
                            <input type="number" name="passenger_count" class="form-control" min="0" max="1000">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Any special instructions or notes..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" name="add_flight" class="btn btn-primary">Add Flight</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addFlightModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Flight Modal -->
    <div id="editFlightModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editFlightModal')">&times;</span>
            <h2>Edit Flight</h2>
            <form method="POST" id="editFlightForm">
                <input type="hidden" name="flight_id" id="edit_flight_id">
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="scheduled">Scheduled</option>
                        <option value="boarding">Boarding</option>
                        <option value="departed">Departed</option>
                        <option value="arrived">Arrived</option>
                        <option value="delayed">Delayed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Gate</label>
                        <input type="text" name="gate" id="edit_gate" class="form-control" maxlength="10">
                    </div>
                    <div class="form-group">
                        <label>Terminal</label>
                        <input type="text" name="terminal" id="edit_terminal" class="form-control" maxlength="5">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Check-in Counter</label>
                        <input type="text" name="checkin_counter" id="edit_checkin_counter" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Baggage Carousel</label>
                        <input type="text" name="baggage_carousel" id="edit_baggage_carousel" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update_flight" class="btn btn-primary">Update Flight</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editFlightModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Airport Management System</p>
        </div>
    </footer>
    
    <script>
        function openAddModal() {
            document.getElementById('addFlightModal').style.display = 'block';
        }
        
        function openEditModal(flightId) {
            // Fetch flight details using AJAX
            fetch('get_flight_details.php?id=' + flightId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const flight = data.flight;
                        document.getElementById('edit_flight_id').value = flight.id;
                        document.getElementById('edit_status').value = flight.status;
                        document.getElementById('edit_gate').value = flight.gate || '';
                        document.getElementById('edit_terminal').value = flight.terminal || '';
                        document.getElementById('edit_checkin_counter').value = flight.checkin_counter || '';
                        document.getElementById('edit_baggage_carousel').value = flight.baggage_carousel || '';
                        document.getElementById('edit_notes').value = flight.notes || '';
                        
                        document.getElementById('editFlightModal').style.display = 'block';
                    } else {
                        alert('Error loading flight details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading flight details. Please try again.');
                });
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
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