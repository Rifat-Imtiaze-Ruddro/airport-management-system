
-- Airport Management System - COMPLETE SETUP
-- Data generated using AI tools for demonstration purposes

-- Create database
CREATE DATABASE IF NOT EXISTS airport_management;
USE airport_management;

-- Table: users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    airline VARCHAR(100),
    role ENUM(
        'administrator', 
        'airport_manager', 
        'airline_staff', 
        'service_staff', 
        'cleaning_staff'
    ) DEFAULT 'airline_staff',
    phone_number VARCHAR(20),
    department VARCHAR(100),
    job_title VARCHAR(100),
    last_login DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    profile_picture VARCHAR(255),
    reset_token VARCHAR(64),
    reset_token_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: airlines
CREATE TABLE airlines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    airline_code VARCHAR(3) UNIQUE NOT NULL,
    airline_name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: flights
CREATE TABLE flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_number VARCHAR(10) NOT NULL,
    airline_id INT,
    origin VARCHAR(3) NOT NULL,
    destination VARCHAR(3) NOT NULL,
    scheduled_departure DATETIME,
    scheduled_arrival DATETIME,
    actual_departure DATETIME,
    actual_arrival DATETIME,
    status ENUM('scheduled', 'boarding', 'departed', 'arrived', 'delayed', 'cancelled') DEFAULT 'scheduled',
    gate VARCHAR(10),
    terminal VARCHAR(5),
    aircraft_type VARCHAR(50),
    checkin_counter VARCHAR(20),
    baggage_carousel VARCHAR(20),
    aircraft_registration VARCHAR(20),
    flight_duration INT COMMENT 'in minutes',
    passenger_count INT DEFAULT 0,
    crew_count INT DEFAULT 2,
    fuel_required_liters INT,
    catering_required BOOLEAN DEFAULT FALSE,
    cleaning_required BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(id)
);

-- Table: service_requests
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(20) UNIQUE NOT NULL,
    flight_id INT,
    service_type ENUM('fueling', 'catering', 'cleaning', 'maintenance', 'baggage', 'boarding_bridge', 'pushback') NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'emergency') DEFAULT 'normal',
    department ENUM('fueling', 'catering', 'cleaning', 'maintenance', 'baggage', 'gate_ops', 'security') DEFAULT 'maintenance',
    estimated_completion_time DATETIME,
    requested_time DATETIME,
    completed_time DATETIME,
    urgency_level ENUM('routine', 'urgent', 'critical') DEFAULT 'routine',
    notes TEXT,
    completion_notes TEXT,
    created_by INT,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flight_id) REFERENCES flights(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Table: service_assignments
CREATE TABLE service_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT,
    staff_id INT,
    assigned_by INT,
    assigned_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('assigned', 'in_progress', 'completed', 'rejected') DEFAULT 'assigned',
    notes TEXT,
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (staff_id) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- Table: service_categories
CREATE TABLE service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(10) UNIQUE NOT NULL,
    category_name VARCHAR(50) NOT NULL,
    department VARCHAR(50),
    default_priority ENUM('low', 'normal', 'high', 'emergency') DEFAULT 'normal',
    estimated_duration_minutes INT,
    description TEXT
);

-- Table: communications
CREATE TABLE communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sender_id INT,
    recipient_id INT,
    message_type ENUM('general', 'service_request', 'emergency', 'schedule_change') DEFAULT 'general',
    parent_id INT DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    is_urgent BOOLEAN DEFAULT FALSE,
    attachment_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES communications(id) ON DELETE SET NULL
);

-- Table: message_recipients
CREATE TABLE message_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    recipient_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_time DATETIME DEFAULT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (message_id) REFERENCES communications(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_message_recipient (message_id, recipient_id)
);

-- Table: message_labels
CREATE TABLE message_labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label_name VARCHAR(50) UNIQUE NOT NULL,
    label_color VARCHAR(7) DEFAULT '#007bff',
    is_system BOOLEAN DEFAULT FALSE
);

-- Table: message_label_assignments
CREATE TABLE message_label_assignments (
    message_id INT NOT NULL,
    label_id INT NOT NULL,
    assigned_by INT,
    assigned_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id, label_id),
    FOREIGN KEY (message_id) REFERENCES communications(id) ON DELETE CASCADE,
    FOREIGN KEY (label_id) REFERENCES message_labels(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table: user_activity_log
CREATE TABLE user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table: password_history
CREATE TABLE password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table: user_notification_preferences
CREATE TABLE user_notification_preferences (
    user_id INT PRIMARY KEY,
    email_notifications BOOLEAN DEFAULT TRUE,
    push_notifications BOOLEAN DEFAULT TRUE,
    notify_on_message BOOLEAN DEFAULT TRUE,
    notify_on_urgent BOOLEAN DEFAULT TRUE,
    notify_on_service_update BOOLEAN DEFAULT TRUE,
    notify_on_flight_change BOOLEAN DEFAULT TRUE,
    daily_digest BOOLEAN DEFAULT FALSE,
    digest_time TIME DEFAULT '08:00:00',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: user_permissions
CREATE TABLE user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_code VARCHAR(50) NOT NULL,
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_permission (user_id, permission_code)
);

-- Users table indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_active ON users(is_active);

-- Flights table indexes
CREATE INDEX idx_flights_airline ON flights(airline_id);
CREATE INDEX idx_flights_status ON flights(status);
CREATE INDEX idx_flights_departure ON flights(scheduled_departure);
CREATE INDEX idx_flights_gate ON flights(gate);
CREATE INDEX idx_flights_terminal ON flights(terminal);
CREATE INDEX idx_flights_destination ON flights(destination);
CREATE INDEX idx_flights_origin ON flights(origin);

-- Service requests indexes
CREATE INDEX idx_service_requests_flight ON service_requests(flight_id);
CREATE INDEX idx_service_requests_status ON service_requests(status);
CREATE INDEX idx_service_requests_priority ON service_requests(priority);
CREATE INDEX idx_service_requests_department ON service_requests(department);
CREATE INDEX idx_service_requests_created_time ON service_requests(requested_time);

-- Communications indexes
CREATE INDEX idx_comms_sender ON communications(sender_id);
CREATE INDEX idx_comms_parent ON communications(parent_id);
CREATE INDEX idx_comms_created ON communications(created_at);
CREATE INDEX idx_msg_recipients_message ON message_recipients(message_id);
CREATE INDEX idx_msg_recipients_user ON message_recipients(recipient_id);

-- Activity log indexes
CREATE INDEX idx_activity_user ON user_activity_log(user_id);
CREATE INDEX idx_activity_type ON user_activity_log(activity_type);
CREATE INDEX idx_activity_time ON user_activity_log(created_at);


-- DEMO DATA INSERTION

-- Insert sample airlines
INSERT INTO airlines (airline_code, airline_name, contact_email, contact_phone) VALUES
('AAL', 'American Airlines', 'ops@americanair.com', '+1-800-433-7300'),
('DAL', 'Delta Air Lines', 'operations@delta.com', '+1-800-221-1212'),
('UAL', 'United Airlines', 'airportops@united.com', '+1-800-864-8331'),
('BAW', 'British Airways', 'airport.services@ba.com', '+44-20-8738-5000'),
('AFR', 'Air France', 'operations@airfrance.fr', '+33-1-41-56-78-00'),
('EMI', 'Emirates', 'operations@emirates.com', '+971-4-708-7777'),
('SIA', 'Singapore Airlines', 'ops@singaporeair.com', '+65-6223-8888');

-- Insert sample users with enhanced roles
INSERT INTO users (name, email, password, airline, role, phone_number, department, job_title) VALUES
('Airport Administrator', 'admin@airport.com', 'password', 'Airport Authority', 'administrator', '+1-555-0100', 'Administration', 'System Administrator'),
('John Smith', 'john@americanair.com', 'password', 'American Airlines', 'airline_staff', '+1-555-0102', 'Flight Operations', 'Airline Representative'),
('Sarah Johnson', 'sarah@delta.com', 'password', 'Delta Air Lines', 'airline_staff', '+1-555-0103', 'Customer Service', 'Ground Coordinator'),
('Airport Operations Manager', 'manager@airport.com', 'password', 'Airport Authority', 'airport_manager', '+1-555-0101', 'Airport Operations', 'Operations Manager'),
('Ground Service Staff', 'service@airport.com', 'password', 'Ground Services', 'service_staff', '+1-555-0104', 'Ground Services', 'Service Coordinator'),
('Cleaning Crew Member', 'cleaner@airport.com', 'password', 'Cleaning Services', 'cleaning_staff', '+1-555-0105', 'Janitorial Services', 'Cleaning Supervisor'),
('Fuel Service Technician', 'fuel@airport.com', 'password', 'Fuel Services Ltd', 'service_staff', '+1-555-0106', 'Fueling Department', 'Fuel Technician'),
('Baggage Handler', 'baggage@airport.com', 'password', 'Baggage Services', 'service_staff', '+1-555-0107', 'Baggage Handling', 'Senior Handler');

-- Insert sample flights
INSERT INTO flights (flight_number, airline_id, origin, destination, scheduled_departure, scheduled_arrival, status, gate, terminal, aircraft_type, checkin_counter, baggage_carousel, passenger_count) VALUES
('AA245', 1, 'JFK', 'LHR', DATE_ADD(NOW(), INTERVAL 2 HOUR), DATE_ADD(NOW(), INTERVAL 8 HOUR), 'scheduled', 'B12', 'T1', 'Boeing 777', 'C12', 'B04', 280),
('DL189', 2, 'ATL', 'CDG', DATE_ADD(NOW(), INTERVAL 1 HOUR), DATE_ADD(NOW(), INTERVAL 7 HOUR), 'boarding', 'A08', 'T2', 'Airbus A330', 'A05', 'B02', 220),
('UA076', 3, 'ORD', 'FRA', DATE_ADD(NOW(), INTERVAL 3 HOUR), DATE_ADD(NOW(), INTERVAL 9 HOUR), 'delayed', 'C05', 'T1', 'Boeing 787', 'C08', 'B07', 240),
('BA123', 4, 'LHR', 'JFK', DATE_ADD(NOW(), INTERVAL 4 HOUR), DATE_ADD(NOW(), INTERVAL 10 HOUR), 'scheduled', 'D15', 'T3', 'Airbus A380', 'D15', 'B01', 450),
('AF456', 5, 'CDG', 'ATL', DATE_ADD(NOW(), INTERVAL 5 HOUR), DATE_ADD(NOW(), INTERVAL 11 HOUR), 'scheduled', 'E22', 'T2', 'Airbus A350', 'E10', 'B03', 300),
('AA678', 1, 'JFK', 'LAX', DATE_ADD(NOW(), INTERVAL 6 HOUR), DATE_ADD(NOW(), INTERVAL 9 HOUR), 'scheduled', 'F10', 'T1', 'Boeing 737', 'F07', 'B05', 160),
('DL321', 2, 'LAX', 'ATL', DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_ADD(NOW(), INTERVAL 3 HOUR), 'departed', 'G05', 'T2', 'Airbus A320', 'G03', 'B06', 180),
('EK202', 6, 'DXB', 'JFK', DATE_ADD(NOW(), INTERVAL 8 HOUR), DATE_ADD(NOW(), INTERVAL 18 HOUR), 'scheduled', 'H08', 'T4', 'Boeing 777', 'H12', 'B08', 350),
('SQ317', 7, 'SIN', 'LHR', DATE_ADD(NOW(), INTERVAL 10 HOUR), DATE_ADD(NOW(), INTERVAL 22 HOUR), 'scheduled', 'I03', 'T3', 'Airbus A350', 'I05', 'B09', 280);

-- Insert service categories
INSERT INTO service_categories (category_code, category_name, department, default_priority, estimated_duration_minutes, description) VALUES
('FUEL', 'Aircraft Fueling', 'fueling', 'high', 45, 'Refueling of aircraft before departure'),
('CATER', 'In-flight Catering', 'catering', 'normal', 60, 'Loading of meals and beverages'),
('CLEAN', 'Aircraft Cleaning', 'cleaning', 'normal', 30, 'Cabin cleaning and sanitization'),
('MAINT', 'Maintenance Check', 'maintenance', 'emergency', 120, 'Technical maintenance and repairs'),
('BAGG', 'Baggage Handling', 'baggage', 'normal', 45, 'Loading/unloading of passenger luggage'),
('GATE', 'Gate Operations', 'gate_ops', 'high', 15, 'Gate assignment and boarding bridge operation'),
('PUSH', 'Aircraft Pushback', 'gate_ops', 'normal', 20, 'Pushback and towing operations'),
('DEICE', 'De-icing Service', 'maintenance', 'urgent', 40, 'Aircraft de-icing in cold weather'),
('WATER', 'Potable Water Service', 'maintenance', 'routine', 25, 'Refilling of potable water tanks'),
('LAV', 'Lavatory Service', 'cleaning', 'routine', 20, 'Emptying and cleaning of lavatories');

-- Insert sample service requests
INSERT INTO service_requests (request_id, flight_id, service_type, status, priority, department, requested_time, estimated_completion_time, notes) VALUES
('SR-2024-001', 1, 'fueling', 'pending', 'high', 'fueling', NOW(), DATE_ADD(NOW(), INTERVAL 45 MINUTE), 'Full tank required for JFK-LHR long haul'),
('SR-2024-002', 2, 'catering', 'in_progress', 'normal', 'catering', NOW(), DATE_ADD(NOW(), INTERVAL 60 MINUTE), 'Special meals for business class - 12 vegetarian, 8 gluten-free'),
('SR-2024-003', 3, 'cleaning', 'completed', 'normal', 'cleaning', DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 30 MINUTE), 'Standard turnaround cleaning after international arrival'),
('SR-2024-004', 4, 'baggage', 'pending', 'high', 'baggage', NOW(), DATE_ADD(NOW(), INTERVAL 45 MINUTE), 'Extra baggage carts needed - large group checking in'),
('SR-2024-005', 5, 'maintenance', 'pending', 'emergency', 'maintenance', NOW(), DATE_ADD(NOW(), INTERVAL 120 MINUTE), 'Minor hydraulic leak detected during inspection'),
('SR-2024-006', 6, 'boarding_bridge', 'in_progress', 'normal', 'gate_ops', NOW(), DATE_ADD(NOW(), INTERVAL 15 MINUTE), 'Position bridge at Gate F10 for AA678'),
('SR-2024-007', 7, 'pushback', 'pending', 'high', 'gate_ops', NOW(), DATE_ADD(NOW(), INTERVAL 20 MINUTE), 'Ready for pushback in 15 minutes'),
('SR-2024-008', 8, 'fueling', 'pending', 'normal', 'fueling', NOW(), DATE_ADD(NOW(), INTERVAL 45 MINUTE), 'Standard fueling for DXB-JFK flight'),
('SR-2024-009', 9, 'catering', 'pending', 'normal', 'catering', NOW(), DATE_ADD(NOW(), INTERVAL 60 MINUTE), 'Premium class catering for Singapore Airlines');

-- Insert sample service assignments
INSERT INTO service_assignments (request_id, staff_id, assigned_by, status) VALUES
(2, 5, 1, 'in_progress'),
(6, 5, 4, 'completed'),
(1, 7, 4, 'assigned'),
(4, 8, 1, 'assigned');

-- Insert message labels
INSERT INTO message_labels (label_name, label_color, is_system) VALUES
('Inbox', '#007bff', TRUE),
('Sent', '#28a745', TRUE),
('Drafts', '#6c757d', TRUE),
('Archived', '#17a2b8', TRUE),
('Important', '#dc3545', FALSE),
('Follow-up', '#fd7e14', FALSE),
('Meeting', '#6f42c1', FALSE),
('Flight Related', '#20c997', FALSE),
('Service Request', '#e83e8c', FALSE),
('Urgent', '#ffc107', FALSE);

-- Insert sample communications
INSERT INTO communications (subject, message, sender_id, recipient_id, message_type, is_urgent) VALUES
('Gate Change Notification', 'Flight AA245 has been moved from Gate B12 to B15 due to operational requirements. Please update all systems and inform passengers accordingly.', 1, 2, 'schedule_change', FALSE),
('Fueling Request - URGENT', 'Requesting priority fueling for Flight DL189. Running tight on turnaround time due to inbound delay. Need expedited service.', 2, 1, 'service_request', TRUE),
('Weather Alert - System Update', 'Potential delays expected due to incoming weather system. All flights after 18:00 may be affected. Monitor updates and prepare contingency plans.', 1, 3, 'emergency', TRUE),
('Cleaning Service Completed', 'Flight UA076 cleaning completed at 14:30. Aircraft ready for boarding at Gate C05. All cabins sanitized and restocked.', 6, 4, 'general', FALSE),
('Baggage Handling Issue', 'Additional baggage carts requested for BA123. Large tour group with excessive luggage causing congestion at check-in.', 3, 5, 'service_request', FALSE),
('Maintenance Required - AF456', 'During pre-flight inspection, minor hydraulic leak detected on AF456. Engineering team dispatched. Estimated repair time: 45 minutes.', 4, 1, 'emergency', TRUE),
('New Flight Schedule', 'New flight SQ317 from Singapore to London added to schedule. Gate I03, Terminal 3. Please prepare necessary services.', 1, 2, 'schedule_change', FALSE),
('Catering Update', 'Special dietary requirements for 20 passengers on EK202. Details attached. Please coordinate with catering department.', 2, 5, 'service_request', FALSE);

-- Insert group message
INSERT INTO communications (subject, message, sender_id, message_type, is_urgent) VALUES
('Weather Alert: Thunderstorms Expected', 'All flights after 18:00 may experience delays due to incoming thunderstorms. Please monitor weather updates and prepare contingency plans.', 1, 'emergency', TRUE);

-- Get the group message ID and assign to multiple recipients
SET @group_msg_id = LAST_INSERT_ID();
INSERT INTO message_recipients (message_id, recipient_id) SELECT @group_msg_id, id FROM users WHERE role != 'administrator';

-- Insert replies to create threads
INSERT INTO communications (subject, message, sender_id, recipient_id, message_type, parent_id) VALUES
('Re: Gate Change Notification', 'Thanks for the update. We have informed our ground crew and updated passenger displays.', 2, 1, 'schedule_change', 1),
('Re: Re: Gate Change Notification', 'Great, please also update the baggage handling system.', 1, 2, 'schedule_change', 1),
('Re: Fueling Request', 'Fueling team dispatched. ETA 10 minutes. Will update when complete.', 1, 2, 'service_request', 2);

-- Insert activity logs
INSERT INTO user_activity_log (user_id, activity_type, activity_details, ip_address) VALUES
(1, 'login', 'User logged in successfully', '192.168.1.100'),
(1, 'profile_update', 'Updated profile information', '192.168.1.100'),
(2, 'login', 'User logged in successfully', '192.168.1.101'),
(2, 'flight_created', 'Created flight AA789', '192.168.1.101'),
(3, 'login', 'User logged in successfully', '192.168.1.102'),
(3, 'service_request', 'Created service request SR-2024-008', '192.168.1.102'),
(4, 'login', 'User logged in successfully', '192.168.1.103'),
(5, 'login', 'User logged in successfully', '192.168.1.104'),
(6, 'login', 'User logged in successfully', '192.168.1.105');

-- Insert notification preferences
INSERT INTO user_notification_preferences (user_id) SELECT id FROM users ON DUPLICATE KEY UPDATE user_id = user_id;


SELECT '✅ Airport Management System Database Setup Complete!' as status;

SELECT 
    'Users' as table_name, COUNT(*) as record_count FROM users
UNION ALL SELECT 'Airlines', COUNT(*) FROM airlines
UNION ALL SELECT 'Flights', COUNT(*) FROM flights
UNION ALL SELECT 'Service Requests', COUNT(*) FROM service_requests
UNION ALL SELECT 'Service Assignments', COUNT(*) FROM service_assignments
UNION ALL SELECT 'Service Categories', COUNT(*) FROM service_categories
UNION ALL SELECT 'Communications', COUNT(*) FROM communications
UNION ALL SELECT 'Message Recipients', COUNT(*) FROM message_recipients
UNION ALL SELECT 'Message Labels', COUNT(*) FROM message_labels
UNION ALL SELECT 'Activity Logs', COUNT(*) FROM user_activity_log;