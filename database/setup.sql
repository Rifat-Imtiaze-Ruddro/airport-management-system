-- Airport Management System Database Setup
-- Updated
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

-- Table: communications
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


-- SAMPLE DATA
-- Insert sample airlines
INSERT INTO airlines (airline_code, airline_name, contact_email, contact_phone) VALUES
('AAL', 'American Airlines', 'ops@americanair.com', '+1-800-433-7300'),
('DAL', 'Delta Air Lines', 'operations@delta.com', '+1-800-221-1212'),
('UAL', 'United Airlines', 'airportops@united.com', '+1-800-864-8331'),
('BAW', 'British Airways', 'airport.services@ba.com', '+44-20-8738-5000'),
('AFR', 'Air France', 'operations@airfrance.fr', '+33-1-41-56-78-00');

-- Insert sample users with enhanced roles
INSERT INTO users (name, email, password, airline, role) VALUES
('Airport Administrator', 'admin@airport.com', 'password', 'Airport Authority', 'administrator'),
('John Smith - American Airlines', 'john@americanair.com', 'password', 'American Airlines', 'airline_staff'),
('Sarah Johnson - Delta', 'sarah@delta.com', 'password', 'Delta Air Lines', 'airline_staff'),
('Airport Operations Manager', 'manager@airport.com', 'password', 'Airport Authority', 'airport_manager'),
('Ground Service Staff', 'service@airport.com', 'password', 'Ground Services', 'service_staff'),
('Cleaning Crew Member', 'cleaner@airport.com', 'password', 'Cleaning Services', 'cleaning_staff');

-- Insert sample flights
INSERT INTO flights (flight_number, airline_id, origin, destination, scheduled_departure, scheduled_arrival, status, gate, terminal) VALUES
('AA245', 1, 'JFK', 'LHR', NOW() + INTERVAL 2 HOUR, NOW() + INTERVAL 8 HOUR, 'scheduled', 'B12', 'T1'),
('DL189', 2, 'ATL', 'CDG', NOW() + INTERVAL 1 HOUR, NOW() + INTERVAL 7 HOUR, 'boarding', 'A08', 'T2'),
('UA076', 3, 'ORD', 'FRA', NOW() + INTERVAL 3 HOUR, NOW() + INTERVAL 9 HOUR, 'delayed', 'C05', 'T1'),
('BA123', 4, 'LHR', 'JFK', NOW() + INTERVAL 4 HOUR, NOW() + INTERVAL 10 HOUR, 'scheduled', 'D15', 'T3'),
('AF456', 5, 'CDG', 'ATL', NOW() + INTERVAL 5 HOUR, NOW() + INTERVAL 11 HOUR, 'scheduled', 'E22', 'T2'),
('AA678', 1, 'JFK', 'LAX', NOW() + INTERVAL 6 HOUR, NOW() + INTERVAL 9 HOUR, 'scheduled', 'F10', 'T1'),
('DL321', 2, 'LAX', 'ATL', NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 3 HOUR, 'departed', 'G05', 'T2');

-- Insert sample service requests
INSERT INTO service_requests (request_id, flight_id, service_type, status, requested_time, priority, notes) VALUES
('SR-2024-001', 1, 'fueling', 'pending', NOW(), 'high', 'Full tank required for JFK-LHR long haul'),
('SR-2024-002', 2, 'catering', 'in_progress', NOW(), 'normal', 'Special meals for business class - 12 vegetarian, 8 gluten-free'),
('SR-2024-003', 3, 'cleaning', 'completed', NOW(), 'normal', 'Standard turnaround cleaning after international arrival'),
('SR-2024-004', 4, 'baggage', 'pending', NOW(), 'high', 'Extra baggage carts needed - large group checking in'),
('SR-2024-005', 5, 'maintenance', 'pending', NOW(), 'emergency', 'Minor hydraulic leak detected during inspection'),
('SR-2024-006', 6, 'boarding_bridge', 'in_progress', NOW(), 'normal', 'Position bridge at Gate F10 for AA678'),
('SR-2024-007', 7, 'pushback', 'pending', NOW(), 'high', 'Ready for pushback in 15 minutes');

-- Insert sample communications
INSERT INTO communications (subject, message, sender_id, recipient_id, message_type) VALUES
('Gate Change Notification', 'Flight AA245 has been moved from Gate B12 to B15 due to operational requirements. Please update all systems and inform passengers accordingly.', 1, 2, 'schedule_change'),
('Fueling Request - URGENT', 'Requesting priority fueling for Flight DL189. Running tight on turnaround time due to inbound delay. Need expedited service.', 2, 1, 'service_request'),
('Weather Alert - System Update', 'Potential delays expected due to incoming weather system. All flights after 18:00 may be affected. Monitor updates and prepare contingency plans.', 1, 3, 'emergency'),
('Cleaning Service Completed', 'Flight UA076 cleaning completed at 14:30. Aircraft ready for boarding at Gate C05. All cabins sanitized and restocked.', 6, 4, 'general'),
('Baggage Handling Issue', 'Additional baggage carts requested for BA123. Large tour group with excessive luggage causing congestion at check-in.', 3, 5, 'service_request'),
('Maintenance Required - AF456', 'During pre-flight inspection, minor hydraulic leak detected on AF456. Engineering team dispatched. Estimated repair time: 45 minutes.', 4, 1, 'emergency');


CREATE INDEX idx_flights_airline ON flights(airline_id);
CREATE INDEX idx_flights_status ON flights(status);
CREATE INDEX idx_flights_departure ON flights(scheduled_departure);
CREATE INDEX idx_service_requests_flight ON service_requests(flight_id);
CREATE INDEX idx_service_requests_status ON service_requests(status);
CREATE INDEX idx_communications_recipient ON communications(recipient_id);
CREATE INDEX idx_communications_read ON communications(is_read);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

SELECT 'Airport Management System Database Setup Complete!' as status;

SELECT 
    'Users' as table_name, COUNT(*) as record_count FROM users
UNION ALL SELECT 'Airlines', COUNT(*) FROM airlines
UNION ALL SELECT 'Flights', COUNT(*) FROM flights
UNION ALL SELECT 'Service Requests', COUNT(*) FROM service_requests
UNION ALL SELECT 'Communications', COUNT(*) FROM communications;