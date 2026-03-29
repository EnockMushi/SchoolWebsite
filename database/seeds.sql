USE school_db;

-- Clear existing data (optional, but good for clean seed)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE messages;
TRUNCATE TABLE notifications;
TRUNCATE TABLE attendance;
TRUNCATE TABLE students;
TRUNCATE TABLE teacher_assignments;
TRUNCATE TABLE classes;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users (Passwords are 'password123')
-- Admin
INSERT INTO users (username, password, full_name, role, email, phone) VALUES 
('admin', '$2y$12$yTqFSTyb6jXy0I5G/SrdH.Z1mlOUm0icMcoU1TSyXtp7uNURF7cNO', 'System Admin', 'admin', 'admin@example.com', '0712345678');

-- Headmaster
INSERT INTO users (username, password, full_name, role, email, phone) VALUES 
('headmaster', '$2y$12$yTqFSTyb6jXy0I5G/SrdH.Z1mlOUm0icMcoU1TSyXtp7uNURF7cNO', 'Dr. Jane Mabula', 'headmaster', 'headmaster@example.com', '0755123456');

-- Teachers
INSERT INTO users (username, password, full_name, role, email, phone) VALUES 
('teacher1', '$2y$12$yTqFSTyb6jXy0I5G/SrdH.Z1mlOUm0icMcoU1TSyXtp7uNURF7cNO', 'Mr. Peter Kamau', 'teacher', 'teacher1@example.com', '0655987654'),
('teacher2', '$2y$12$yTqFSTyb6jXy0I5G/SrdH.Z1mlOUm0icMcoU1TSyXtp7uNURF7cNO', 'Ms. Sarah Mushi', 'teacher', 'teacher2@example.com', '0688112233');

-- Parents
INSERT INTO users (username, password, full_name, role, email, phone) VALUES 
('parent1', '$2y$12$yTqFSTyb6jXy0I5G/SrdH.Z1mlOUm0icMcoU1TSyXtp7uNURF7cNO', 'John Doe', 'parent', 'john.doe@gmail.com', '0744001122'),
('parent2', '$2y$12$yTqFSTyb6jXy0I5G/SrdH.Z1mlOUm0icMcoU1TSyXtp7uNURF7cNO', 'Mary Smith', 'parent', 'mary.smith@gmail.com', '0799554433');

-- 2. Classes
INSERT INTO classes (class_name, section) VALUES 
('Standard 1', 'A'),
('Standard 1', 'B'),
('Standard 2', 'A'),
('Standard 7', 'A');

-- 3. Teacher Assignments
-- Mr. Peter Kamau (ID 3) assigned to Standard 1-A (ID 1)
INSERT INTO teacher_assignments (teacher_id, class_id) VALUES (3, 1);
-- Ms. Sarah Mushi (ID 4) assigned to Standard 7-A (ID 4)
INSERT INTO teacher_assignments (teacher_id, class_id) VALUES (4, 4);

-- 4. Students
-- Link students to parents and classes
INSERT INTO students (reg_number, full_name, class_id, parent_id) VALUES 
('REG-2026-001', 'James John Doe', 1, 5),
('REG-2026-002', 'Linda Jane Doe', 1, 5),
('REG-2026-003', 'Mary John Smith', 4, 6),
('REG-2026-004', 'Kelvin Mary Smith', 4, 6);

-- 6. Initial Messages
INSERT INTO messages (sender_id, receiver_id, message) VALUES 
(3, 2, 'Request for new chalkboards for Standard 1A.'),
(5, 3, 'Hello Mr. Peter, how is James performing in mathematics?');

-- 7. Notifications
INSERT INTO notifications (user_id, message) VALUES 
(5, 'Welcome to the parent portal. You can now track James John Doe\'s progress.');
