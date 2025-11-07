-- Airport Management System Database Setup
-- Run this SQL in phpMyAdmin to create the database structure

CREATE DATABASE IF NOT EXISTS airport_management;
USE airport_management;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    airline VARCHAR(100),
    role ENUM('airline_staff', 'airport_staff', 'service_provider', 'administrator') DEFAULT 'airline_staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Airlines table
CREATE TABLE airlines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    airline_code VARCHAR(3) UNIQUE NOT NULL,
    airline_name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Flights table
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(id)
);

-- Service requests table
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(20) UNIQUE NOT NULL,
    flight_id INT,
    service_type ENUM('fueling', 'catering', 'cleaning', 'maintenance', 'baggage', 'boarding_bridge', 'pushback') NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    requested_time DATETIME,
    completed_time DATETIME,
    priority ENUM('low', 'normal', 'high', 'emergency') DEFAULT 'normal',
    notes TEXT,
    created_by INT,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flight_id) REFERENCES flights(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Communications table
CREATE TABLE communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sender_id INT,
    recipient_id INT,
    message_type ENUM('general', 'service_request', 'emergency', 'schedule_change') DEFAULT 'general',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id)
);

-- Sample Data
INSERT INTO airlines (airline_code, airline_name, contact_email, contact_phone) VALUES
('AAL', 'American Airlines', 'ops@americanair.com', '+1-800-433-7300'),
('DAL', 'Delta Air Lines', 'operations@delta.com', '+1-800-221-1212'),
('UAL', 'United Airlines', 'airportops@united.com', '+1-800-864-8331'),
('BAW', 'British Airways', 'airport.services@ba.com', '+44-20-8738-5000'),
('AFR', 'Air France', 'operations@airfrance.fr', '+33-1-41-56-78-00');

INSERT INTO users (name, email, password, airline, role) VALUES
('Airport Administrator', 'admin@airport.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Airport Authority', 'administrator'),
('John Smith', 'john@americanair.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'American Airlines', 'airline_staff'),
('Sarah Johnson', 'sarah@delta.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Delta Air Lines', 'airline_staff');

INSERT INTO flights (flight_number, airline_id, origin, destination, scheduled_departure, scheduled_arrival, status, gate, terminal) VALUES
('AA245', 1, 'JFK', 'LHR', NOW() + INTERVAL 2 HOUR, NOW() + INTERVAL 8 HOUR, 'scheduled', 'B12', 'T1'),
('DL189', 2, 'ATL', 'CDG', NOW() + INTERVAL 1 HOUR, NOW() + INTERVAL 7 HOUR, 'boarding', 'A08', 'T2'),
('UA076', 3, 'ORD', 'FRA', NOW() + INTERVAL 3 HOUR, NOW() + INTERVAL 9 HOUR, 'delayed', 'C05', 'T1'),
('BA123', 4, 'LHR', 'JFK', NOW() + INTERVAL 4 HOUR, NOW() + INTERVAL 10 HOUR, 'scheduled', 'D15', 'T3'),
('AF456', 5, 'CDG', 'ATL', NOW() + INTERVAL 5 HOUR, NOW() + INTERVAL 11 HOUR, 'scheduled', 'E22', 'T2');

INSERT INTO service_requests (request_id, flight_id, service_type, status, requested_time, priority, notes) VALUES
('SR-2024-001', 1, 'fueling', 'pending', NOW(), 'high', 'Full tank required for JFK-LHR'),
('SR-2024-002', 2, 'catering', 'in_progress', NOW(), 'normal', 'Special meals for business class'),
('SR-2024-003', 3, 'cleaning', 'completed', NOW(), 'normal', 'Standard turnaround cleaning'),
('SR-2024-004', 4, 'baggage', 'pending', NOW(), 'high', 'Extra baggage carts needed');

INSERT INTO communications (subject, message, sender_id, recipient_id, message_type) VALUES
('Gate Change Notification', 'Flight AA245 has been moved from Gate B12 to B15 due to operational requirements.', 1, 2, 'schedule_change'),
('Fueling Request', 'Requesting priority fueling for Flight DL189. Running tight on turnaround time.', 2, 1, 'service_request'),
('Weather Alert', 'Potential delays expected due to incoming weather system. Monitor updates.', 1, 3, 'emergency');
