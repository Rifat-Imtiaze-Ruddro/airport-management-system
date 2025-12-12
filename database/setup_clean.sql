
-- Airport Management System - CLEAN SETUP
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



-- Insert default admin user (change password immediately!)
INSERT INTO users (name, email, password, airline, role, department, job_title) VALUES
('System Administrator', 'admin@airport.com', 'admin123', 'Airport Authority', 'administrator', 'Administration', 'System Administrator');

-- Insert system message labels
INSERT INTO message_labels (label_name, label_color, is_system) VALUES
('Inbox', '#007bff', TRUE),
('Sent', '#28a745', TRUE),
('Drafts', '#6c757d', TRUE),
('Archived', '#17a2b8', TRUE);

-- Insert basic service categories
INSERT INTO service_categories (category_code, category_name, department, default_priority, estimated_duration_minutes, description) VALUES
('FUEL', 'Aircraft Fueling', 'fueling', 'high', 45, 'Refueling of aircraft before departure'),
('CATER', 'In-flight Catering', 'catering', 'normal', 60, 'Loading of meals and beverages'),
('CLEAN', 'Aircraft Cleaning', 'cleaning', 'normal', 30, 'C