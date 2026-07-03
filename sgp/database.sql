-- ============================================================
-- University Medical Application System - Database
-- ============================================================

CREATE DATABASE IF NOT EXISTS university_medical;
USE university_medical;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','hod','receptionist','admin') NOT NULL DEFAULT 'student',
    hod_id VARCHAR(20) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    otp VARCHAR(10) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- HODs Table (department mapping)
CREATE TABLE IF NOT EXISTS hods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hod_id VARCHAR(20) NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Applications Table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    hod_id VARCHAR(20) NOT NULL,
    reason TEXT NOT NULL,
    leave_date DATE NOT NULL,
    leave_time TIME NOT NULL,
    status ENUM('pending','hod_approved','hod_rejected','receptionist_verified') DEFAULT 'pending',
    hod_remark TEXT DEFAULT NULL,
    receptionist_remark TEXT DEFAULT NULL,
    hod_action_at DATETIME DEFAULT NULL,
    receptionist_action_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Sample Data
-- ============================================================

-- Admin (password: Admin@123)
INSERT INTO users (name, email, password, role, is_verified) VALUES
('Admin User', 'admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- HOD (password: Hod@123)
INSERT INTO users (name, email, password, role, hod_id, department, is_verified) VALUES
('Dr. Ramesh Kumar', 'hod.cs@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hod', 'HOD-CS-001', 'Computer Science', 1),
('Dr. Priya Sharma', 'hod.ec@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hod', 'HOD-EC-002', 'Electronics', 1);

-- Insert HOD records
INSERT INTO hods (user_id, hod_id, department) VALUES
(2, 'HOD-CS-001', 'Computer Science'),
(3, 'HOD-EC-002', 'Electronics');

-- Receptionist (password: Recep@123)
INSERT INTO users (name, email, password, role, is_verified) VALUES
('Anjali Mehta', 'receptionist@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', 1);

-- Students (password: Student@123)
INSERT INTO users (name, email, password, role, hod_id, department, phone, is_verified) VALUES
('Arjun Patel', 'arjun@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'HOD-CS-001', 'Computer Science', '9876543210', 1),
('Neha Singh', 'neha@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'HOD-CS-001', 'Computer Science', '9876543211', 1),
('Rohan Desai', 'rohan@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'HOD-EC-002', 'Electronics', '9876543212', 1);

-- Sample Applications
INSERT INTO applications (student_id, hod_id, reason, leave_date, leave_time, status, hod_remark, receptionist_remark, hod_action_at, receptionist_action_at) VALUES
(5, 'HOD-CS-001', 'Fever and cold, need to visit doctor', '2025-01-15', '10:00:00', 'receptionist_verified', 'Approved. Take care.', 'Student visited hospital.', '2025-01-14 14:00:00', '2025-01-15 11:00:00'),
(5, 'HOD-CS-001', 'Dental appointment', '2025-01-20', '14:00:00', 'hod_approved', 'Approved.', NULL, '2025-01-19 10:00:00', NULL),
(6, 'HOD-CS-001', 'Eye checkup', '2025-01-22', '09:00:00', 'pending', NULL, NULL, NULL, NULL),
(7, 'HOD-EC-002', 'General checkup', '2025-01-18', '11:00:00', 'hod_rejected', 'Insufficient reason provided.', NULL, '2025-01-17 09:00:00', NULL);
