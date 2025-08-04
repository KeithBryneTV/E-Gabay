CREATE DATABASE IF NOT EXISTS egabay_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE egabay_db;

-- Table for user roles
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT '1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert role data
INSERT INTO roles (role_name, description) VALUES 
('student', 'Regular student who can request consultations'),
('counselor', 'Provides guidance and counseling services'),
('admin', 'System administrator with full access'),
('staff', 'Support staff with limited access');

-- Table for users
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255) NULL,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Table for student profiles
CREATE TABLE IF NOT EXISTS student_profiles (
    profile_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    course VARCHAR(100),
    year_level VARCHAR(20),
    section VARCHAR(50),
    contact_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Table for counselor profiles
CREATE TABLE IF NOT EXISTS counselor_profiles (
    profile_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specialization VARCHAR(100),
    availability TEXT,
    contact_number VARCHAR(20),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Table for consultation requests
CREATE TABLE IF NOT EXISTS consultation_requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    counselor_id INT(11) NULL,
    issue_description TEXT NOT NULL,
    issue_category VARCHAR(100) NULL,
    preferred_date DATE NOT NULL,
    preferred_time TIME NOT NULL,
    communication_method ENUM('in_person', 'phone', 'video', 'email', 'chat', 'voice') NOT NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending', 'live', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    counselor_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (counselor_id) REFERENCES users(user_id)
);

-- Table for chat sessions
CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    counselor_id INT(11) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('active', 'closed') NOT NULL DEFAULT 'active',
    consultation_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (counselor_id) REFERENCES users(user_id),
    FOREIGN KEY (consultation_id) REFERENCES consultation_requests(id)
);

-- Table for chat messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT(11) NOT NULL AUTO_INCREMENT,
    chat_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (chat_id) REFERENCES chat_sessions(id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Table for feedback
CREATE TABLE IF NOT EXISTS feedback (
    id INT(11) NOT NULL AUTO_INCREMENT,
    consultation_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    rating INT(11) NOT NULL,
    comments TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (consultation_id) REFERENCES consultation_requests(id),
    FOREIGN KEY (student_id) REFERENCES users(user_id)
);

-- Table for notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL, 
    reference_id INT(11) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Table for system logs
CREATE TABLE IF NOT EXISTS system_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    value TEXT NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'General',
    is_active TINYINT(1) NOT NULL DEFAULT '1',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY setting_key (setting_key)
);

-- Insert default settings
INSERT INTO settings (setting_name, setting_key, value, description, category) VALUES
('Site Title', 'site_title', 'E-GABAY ASC - Academic Support and Counseling System', 'The title of the website', 'General'),
('Site Description', 'site_description', 'A Comprehensive Academic Support and Counseling System', 'The description of the website', 'General'),
('Admin Email', 'admin_email', 'admin@egabay.edu.ph', 'The email address for system notifications', 'General'),
('Items Per Page', 'items_per_page', '10', 'Default number of items to show per page', 'Display'),
('Current Academic Year', 'current_academic_year', '2025-2026', 'The current academic year', 'Academic');

-- Create default admin user (password: admin123)
INSERT INTO users (username, password, first_name, last_name, email, role_id) VALUES 
('admin', '$2y$10$OUKDyBqz6HNP1gwH6JejNOR/4lkaUgKygbBOGKNgkMMjCsFP9PwFe', 'System', 'Administrator', 'admin@egabay.edu.ph', 3);

-- Add indexes for better performance
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_consultation_requests_student ON consultation_requests(student_id);
CREATE INDEX idx_consultation_requests_counselor ON consultation_requests(counselor_id);
CREATE INDEX idx_chat_sessions_student ON chat_sessions(student_id);
CREATE INDEX idx_chat_sessions_counselor ON chat_sessions(counselor_id); 