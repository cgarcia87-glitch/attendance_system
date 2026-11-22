-- ==============================================================
-- ATTENDANCE MANAGEMENT SYSTEM - DATABASE SCHEMA & SEED DATA
-- Database Name: attendance_management
-- Character Set: utf8mb4 / utf8mb4_unicode_ci
-- Compatible: MySQL 8+ / MariaDB 10.4+
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `attendance_management`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `attendance_management`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `attendance_records`;
DROP TABLE IF EXISTS `attendance_sessions`;
DROP TABLE IF EXISTS `schedules`;
DROP TABLE IF EXISTS `teacher_assignments`;
DROP TABLE IF EXISTS `class_enrollments`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `sections`;
DROP TABLE IF EXISTS `year_levels`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- 1. USERS TABLE
-- -------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'teacher', 'student') NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT 'default-avatar.png',
  `status` ENUM('active', 'inactive', 'pending') DEFAULT 'active',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. COURSES TABLE
-- -------------------------------------------------------------
CREATE TABLE `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_code` VARCHAR(20) NOT NULL UNIQUE,
  `course_name` VARCHAR(150) NOT NULL,
  `department` VARCHAR(100) DEFAULT 'College of Information Technology',
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. YEAR LEVELS TABLE
-- -------------------------------------------------------------
CREATE TABLE `year_levels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `level_numeric` INT NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. SECTIONS TABLE
-- -------------------------------------------------------------
CREATE TABLE `sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `section_name` VARCHAR(50) NOT NULL,
  `course_id` INT NOT NULL,
  `year_level_id` INT NOT NULL,
  `capacity` INT DEFAULT 40,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`year_level_id`) REFERENCES `year_levels` (`id`) ON DELETE CASCADE,
  INDEX `idx_sections_course` (`course_id`),
  INDEX `idx_sections_year` (`year_level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. SUBJECTS TABLE
-- -------------------------------------------------------------
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_code` VARCHAR(20) NOT NULL UNIQUE,
  `subject_name` VARCHAR(150) NOT NULL,
  `units` INT DEFAULT 3,
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. TEACHERS TABLE
-- -------------------------------------------------------------
CREATE TABLE `teachers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNIQUE NULL,
  `teacher_id` VARCHAR(30) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `contact_number` VARCHAR(20) NULL,
  `department` VARCHAR(100) DEFAULT 'Information Technology',
  `qualification` VARCHAR(100) DEFAULT 'Master of Science in Information Technology',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `date_hired` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_teachers_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. STUDENTS TABLE
-- -------------------------------------------------------------
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNIQUE NULL,
  `student_id` VARCHAR(30) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
  `birth_date` DATE NULL,
  `course_id` INT NOT NULL,
  `year_level_id` INT NOT NULL,
  `section_id` INT NOT NULL,
  `contact_number` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `emergency_contact` VARCHAR(100) NULL,
  `qr_code` VARCHAR(255) NULL,
  `status` ENUM('active', 'inactive', 'graduated', 'suspended') DEFAULT 'active',
  `date_registered` DATE DEFAULT (CURRENT_DATE),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`year_level_id`) REFERENCES `year_levels` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE RESTRICT,
  INDEX `idx_students_course` (`course_id`),
  INDEX `idx_students_section` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. CLASSES TABLE
-- -------------------------------------------------------------
CREATE TABLE `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_code` VARCHAR(50) NOT NULL UNIQUE,
  `subject_id` INT NOT NULL,
  `section_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `academic_year` VARCHAR(20) DEFAULT '2025-2026',
  `semester` VARCHAR(20) DEFAULT '1st Semester',
  `room` VARCHAR(50) DEFAULT 'Room 301',
  `status` ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  INDEX `idx_classes_subject` (`subject_id`),
  INDEX `idx_classes_teacher` (`teacher_id`),
  INDEX `idx_classes_section` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. CLASS ENROLLMENTS TABLE
-- -------------------------------------------------------------
CREATE TABLE `class_enrollments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `enrollment_date` DATE DEFAULT (CURRENT_DATE),
  `status` ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`),
  INDEX `idx_enrollment_class` (`class_id`),
  INDEX `idx_enrollment_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. TEACHER ASSIGNMENTS TABLE
-- -------------------------------------------------------------
CREATE TABLE `teacher_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `teacher_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `section_id` INT NOT NULL,
  `academic_year` VARCHAR(20) DEFAULT '2025-2026',
  `semester` VARCHAR(20) DEFAULT '1st Semester',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_assignment` (`teacher_id`, `subject_id`, `section_id`, `academic_year`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 11. SCHEDULES TABLE
-- -------------------------------------------------------------
CREATE TABLE `schedules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `room` VARCHAR(50) DEFAULT 'Room 301',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  INDEX `idx_schedules_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 12. ATTENDANCE SESSIONS TABLE
-- -------------------------------------------------------------
CREATE TABLE `attendance_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `session_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `session_type` ENUM('Regular Lecture', 'Laboratory', 'Midterm Exam', 'Final Exam', 'Review') DEFAULT 'Regular Lecture',
  `qr_token` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `status` ENUM('open', 'closed') DEFAULT 'closed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_class_session` (`class_id`, `session_date`, `session_type`),
  INDEX `idx_sessions_date` (`session_date`),
  INDEX `idx_sessions_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 13. ATTENDANCE RECORDS TABLE
-- -------------------------------------------------------------
CREATE TABLE `attendance_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `status` ENUM('PRESENT', 'ABSENT', 'LATE', 'EXCUSED') NOT NULL DEFAULT 'PRESENT',
  `time_in` TIME NULL,
  `time_out` TIME NULL,
  `remarks` VARCHAR(255) NULL,
  `marked_by` INT NULL,
  `marked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  UNIQUE KEY `unique_session_student` (`session_id`, `student_id`),
  INDEX `idx_records_status` (`status`),
  INDEX `idx_records_student` (`student_id`),
  INDEX `idx_records_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 14. NOTIFICATIONS TABLE
-- -------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notifications_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 15. ACTIVITY LOGS TABLE
-- -------------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_logs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 16. SYSTEM SETTINGS TABLE
-- -------------------------------------------------------------
CREATE TABLE `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `setting_group` VARCHAR(50) DEFAULT 'general',
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================
-- SAMPLE DATA INSERTIONS
-- ==============================================================

-- 1. Insert System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('system_name', 'Attendance Management System', 'general', 'Name of the application'),
('institution_name', 'Apex Institute of Technology', 'general', 'Educational institution or university name'),
('academic_year', '2025-2026', 'academic', 'Current academic year'),
('current_semester', '1st Semester', 'academic', 'Current active semester'),
('allow_registration', '1', 'registration', 'Allow public student self-registration (1=Yes, 0=No)'),
('late_threshold_minutes', '15', 'attendance', 'Grace period in minutes before student is flagged Late'),
('contact_email', 'support@attendance.com', 'contact', 'System administrator support email address'),
('contact_phone', '+1 (555) 019-2834', 'contact', 'Institution contact telephone number'),
('institution_address', '100 Innovation Way, Tech District, Silicon Valley, CA 94016', 'general', 'Physical campus address');

-- 2. Insert Default Users (Passwords: admin123, teacher123, student123)
-- bcrypt hash for 'admin123': $2y$10$t5TAe60At/TE8x/OJyxcQO39GE9rteKoVZP7RyrCn7SKz5Z6kCxeO
-- bcrypt hash for 'teacher123': $2y$10$CPuqJHVja8UcmRqcsM99mO2xbEY92648hvcPfhiaUfq6bsKtqbKeK
-- bcrypt hash for 'student123': $2y$10$xuB6et4RVC9vMRdyXTXnueStjyBToMcU4nGfdwYJ82I4mAYHVCmwi

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `avatar`, `status`) VALUES
(1, 'admin', 'admin@attendance.com', '$2y$10$t5TAe60At/TE8x/OJyxcQO39GE9rteKoVZP7RyrCn7SKz5Z6kCxeO', 'admin', 'Dr. Arthur Pendelton', 'default-avatar.png', 'active'),
(2, 'teacher', 'teacher@attendance.com', '$2y$10$CPuqJHVja8UcmRqcsM99mO2xbEY92648hvcPfhiaUfq6bsKtqbKeK', 'teacher', 'Prof. Eleanor Vance', 'default-avatar.png', 'active'),
(3, 'jroberts', 'jroberts@attendance.com', '$2y$10$CPuqJHVja8UcmRqcsM99mO2xbEY92648hvcPfhiaUfq6bsKtqbKeK', 'teacher', 'Prof. James Roberts', 'default-avatar.png', 'active'),
(4, 'student', 'student@attendance.com', '$2y$10$xuB6et4RVC9vMRdyXTXnueStjyBToMcU4nGfdwYJ82I4mAYHVCmwi', 'student', 'Alexander Hayes', 'default-avatar.png', 'active'),
(5, 'bclark', 'bclark@attendance.com', '$2y$10$xuB6et4RVC9vMRdyXTXnueStjyBToMcU4nGfdwYJ82I4mAYHVCmwi', 'student', 'Beatrice Clark', 'default-avatar.png', 'active'),
(6, 'cmiller', 'cmiller@attendance.com', '$2y$10$xuB6et4RVC9vMRdyXTXnueStjyBToMcU4nGfdwYJ82I4mAYHVCmwi', 'student', 'Christopher Miller', 'default-avatar.png', 'active'),
(7, 'dgarcia', 'dgarcia@attendance.com', '$2y$10$xuB6et4RVC9vMRdyXTXnueStjyBToMcU4nGfdwYJ82I4mAYHVCmwi', 'student', 'Diana Garcia', 'default-avatar.png', 'active'),
(8, 'ethanw', 'ethanw@attendance.com', '$2y$10$xuB6et4RVC9vMRdyXTXnueStjyBToMcU4nGfdwYJ82I4mAYHVCmwi', 'student', 'Ethan Walker', 'default-avatar.png', 'active'),
(9, 'ftaylor', 'ftaylor@attendance.com', '$2y$10$xuB6et4RVC9vMRdyXTXnueStjyBToMcU4nGfdwYJ82I4mAYHVCmwi', 'student', 'Fiona Taylor', 'default-avatar.png', 'active');

-- 3. Insert Courses
INSERT INTO `courses` (`id`, `course_code`, `course_name`, `department`, `description`, `status`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 'College of Computer Studies', 'Focuses on software engineering, network administration, and web applications.', 'active'),
(2, 'BSCS', 'Bachelor of Science in Computer Science', 'College of Computer Studies', 'Emphasizes algorithms, artificial intelligence, and computation theory.', 'active'),
(3, 'BSBA', 'Bachelor of Science in Business Administration', 'College of Business', 'Covers marketing, financial management, and organizational leadership.', 'active'),
(4, 'BSED', 'Bachelor of Secondary Education', 'College of Education', 'Prepares students for secondary school pedagogy and instructional design.', 'active');

-- 4. Insert Year Levels
INSERT INTO `year_levels` (`id`, `name`, `level_numeric`) VALUES
(1, '1st Year', 1),
(2, '2nd Year', 2),
(3, '3rd Year', 3),
(4, '4th Year', 4);

-- 5. Insert Sections
INSERT INTO `sections` (`id`, `section_name`, `course_id`, `year_level_id`, `capacity`, `status`) VALUES
(1, 'BSIT-1A', 1, 1, 40, 'active'),
(2, 'BSIT-1B', 1, 1, 40, 'active'),
(3, 'BSIT-2A', 1, 2, 35, 'active'),
(4, 'BSCS-1A', 2, 1, 35, 'active'),
(5, 'BSCS-3A', 2, 3, 30, 'active'),
(6, 'BSBA-1A', 3, 1, 45, 'active');

-- 6. Insert Subjects
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `units`, `description`, `status`) VALUES
(1, 'IT201', 'Web Systems and Technologies', 3, 'Covers client-server architecture, modern web development, and APIs.', 'active'),
(2, 'IT202', 'Database Management Systems', 3, 'Relational models, SQL, normalization, transactions, and PDO.', 'active'),
(3, 'CS101', 'Introduction to Computing', 3, 'Foundations of computer systems, binary logic, and computational problem solving.', 'active'),
(4, 'CS102', 'Data Structures and Algorithms', 3, 'Arrays, linked lists, trees, graphs, sorting, and Big-O notation.', 'active'),
(5, 'ENG101', 'Purposive Technical Communication', 3, 'Workplace writing, reporting, and technical presentation skills.', 'active'),
(6, 'MATH101', 'Discrete Mathematics', 3, 'Logic, set theory, combinatorics, and graph algorithms.', 'active');

-- 7. Insert Teachers
INSERT INTO `teachers` (`id`, `user_id`, `teacher_id`, `first_name`, `last_name`, `email`, `contact_number`, `department`, `qualification`, `status`, `date_hired`) VALUES
(1, 2, 'TCH-2024-001', 'Eleanor', 'Vance', 'teacher@attendance.com', '+1 (555) 234-5678', 'Information Technology', 'M.S. in Information Systems', 'active', '2021-08-15'),
(2, 3, 'TCH-2024-002', 'James', 'Roberts', 'jroberts@attendance.com', '+1 (555) 345-6789', 'Computer Science', 'Ph.D. in Computer Science', 'active', '2019-06-01');

-- 8. Insert Students
INSERT INTO `students` (`id`, `user_id`, `student_id`, `first_name`, `last_name`, `email`, `gender`, `birth_date`, `course_id`, `year_level_id`, `section_id`, `contact_number`, `address`, `emergency_contact`, `qr_code`, `status`, `date_registered`) VALUES
(1, 4, 'STD-2024-001', 'Alexander', 'Hayes', 'student@attendance.com', 'Male', '2005-04-12', 1, 1, 1, '+1 (555) 111-2222', '452 Elm Street, Maplewood', 'Mary Hayes (+1 555-111-9999)', 'QR_STD-2024-001', 'active', '2024-09-01'),
(2, 5, 'STD-2024-002', 'Beatrice', 'Clark', 'bclark@attendance.com', 'Female', '2005-08-23', 1, 1, 1, '+1 (555) 222-3333', '789 Oak Avenue, Lakeside', 'David Clark (+1 555-222-9999)', 'QR_STD-2024-002', 'active', '2024-09-01'),
(3, 6, 'STD-2024-003', 'Christopher', 'Miller', 'cmiller@attendance.com', 'Male', '2004-11-15', 1, 1, 1, '+1 (555) 333-4444', '12 Pine Boulevard, Greenfield', 'Sarah Miller (+1 555-333-9999)', 'QR_STD-2024-003', 'active', '2024-09-01'),
(4, 7, 'STD-2024-004', 'Diana', 'Garcia', 'dgarcia@attendance.com', 'Female', '2005-02-19', 2, 1, 4, '+1 (555) 444-5555', '304 Cedar Road, Riverdale', 'Carlos Garcia (+1 555-444-9999)', 'QR_STD-2024-004', 'active', '2024-09-01'),
(5, 8, 'STD-2024-005', 'Ethan', 'Walker', 'ethanw@attendance.com', 'Male', '2004-06-30', 2, 1, 4, '+1 (555) 555-6666', '512 Birch Lane, Hillcrest', 'Laura Walker (+1 555-555-9999)', 'QR_STD-2024-005', 'active', '2024-09-01'),
(6, 9, 'STD-2024-006', 'Fiona', 'Taylor', 'ftaylor@attendance.com', 'Female', '2005-09-08', 2, 1, 4, '+1 (555) 666-7777', '820 Walnut Court, Sunset Valley', 'Robert Taylor (+1 555-666-9999)', 'QR_STD-2024-006', 'active', '2024-09-01');

-- 9. Insert Classes
INSERT INTO `classes` (`id`, `class_code`, `subject_id`, `section_id`, `teacher_id`, `academic_year`, `semester`, `room`, `status`) VALUES
(1, 'CLS-IT201-1A', 1, 1, 1, '2025-2026', '1st Semester', 'Room 301', 'active'),
(2, 'CLS-IT202-1A', 2, 1, 1, '2025-2026', '1st Semester', 'Computer Lab 2', 'active'),
(3, 'CLS-CS102-4A', 4, 4, 2, '2025-2026', '1st Semester', 'Room 205', 'active'),
(4, 'CLS-CS101-1B', 3, 2, 2, '2025-2026', '1st Semester', 'Room 402', 'active');

-- 10. Insert Class Enrollments
INSERT INTO `class_enrollments` (`class_id`, `student_id`, `enrollment_date`, `status`) VALUES
(1, 1, '2025-09-01', 'enrolled'),
(1, 2, '2025-09-01', 'enrolled'),
(1, 3, '2025-09-01', 'enrolled'),
(2, 1, '2025-09-01', 'enrolled'),
(2, 2, '2025-09-01', 'enrolled'),
(2, 3, '2025-09-01', 'enrolled'),
(3, 4, '2025-09-01', 'enrolled'),
(3, 5, '2025-09-01', 'enrolled'),
(3, 6, '2025-09-01', 'enrolled');

-- 11. Insert Teacher Assignments
INSERT INTO `teacher_assignments` (`teacher_id`, `subject_id`, `section_id`, `academic_year`, `semester`) VALUES
(1, 1, 1, '2025-2026', '1st Semester'),
(1, 2, 1, '2025-2026', '1st Semester'),
(2, 4, 4, '2025-2026', '1st Semester'),
(2, 3, 2, '2025-2026', '1st Semester');

-- 12. Insert Schedules
INSERT INTO `schedules` (`class_id`, `day_of_week`, `start_time`, `end_time`, `room`) VALUES
(1, 'Monday', '09:00:00', '10:30:00', 'Room 301'),
(1, 'Wednesday', '09:00:00', '10:30:00', 'Room 301'),
(2, 'Tuesday', '13:00:00', '14:30:00', 'Computer Lab 2'),
(2, 'Thursday', '13:00:00', '14:30:00', 'Computer Lab 2'),
(3, 'Monday', '11:00:00', '12:30:00', 'Room 205'),
(3, 'Wednesday', '11:00:00', '12:30:00', 'Room 205');

-- 13. Insert Attendance Sessions
INSERT INTO `attendance_sessions` (`id`, `class_id`, `teacher_id`, `session_date`, `start_time`, `end_time`, `session_type`, `qr_token`, `notes`, `status`) VALUES
(1, 1, 1, '2026-11-02', '09:00:00', '10:30:00', 'Regular Lecture', 'QRSESS-20261102-1', 'Introduction to MVC Architecture in PHP', 'closed'),
(2, 1, 1, '2026-11-04', '09:00:00', '10:30:00', 'Regular Lecture', 'QRSESS-20261104-1', 'Bootstrap 5 Responsive Grid and Components', 'closed'),
(3, 1, 1, '2026-11-09', '09:00:00', '10:30:00', 'Regular Lecture', 'QRSESS-20261109-1', 'PDO Database Abstraction and Prepared Statements', 'closed'),
(4, 1, 1, '2026-11-11', '09:00:00', '10:30:00', 'Laboratory', 'QRSESS-20261111-1', 'Hands-on Lab: Student CRUD and Authentication', 'closed'),
(5, 1, 1, '2026-11-16', '09:00:00', '10:30:00', 'Regular Lecture', 'QRSESS-20261116-1', 'Attendance Analytics with Chart.js', 'open'),
(6, 2, 1, '2026-11-10', '13:00:00', '14:30:00', 'Regular Lecture', 'QRSESS-20261110-2', 'Normalization & Primary/Foreign Keys', 'closed'),
(7, 2, 1, '2026-11-12', '13:00:00', '14:30:00', 'Laboratory', 'QRSESS-20261112-2', 'SQL Queries and Aggregate Functions', 'closed'),
(8, 3, 2, '2026-11-09', '11:00:00', '12:30:00', 'Regular Lecture', 'QRSESS-20261109-3', 'Binary Search Trees and Traversals', 'closed'),
(9, 3, 2, '2026-11-11', '11:00:00', '12:30:00', 'Laboratory', 'QRSESS-20261111-3', 'Graph Representation: Adjacency Matrix', 'closed');

-- 14. Insert Attendance Records
INSERT INTO `attendance_records` (`session_id`, `student_id`, `class_id`, `status`, `remarks`, `marked_by`, `marked_at`) VALUES
-- Session 1 (IT201 on Nov 2)
(1, 1, 1, 'PRESENT', 'On time', 2, '2026-11-02 09:02:15'),
(1, 2, 1, 'PRESENT', 'On time', 2, '2026-11-02 09:04:30'),
(1, 3, 1, 'LATE', 'Arrived 18 mins late due to transit delay', 2, '2026-11-02 09:18:40'),

-- Session 2 (IT201 on Nov 4)
(2, 1, 1, 'PRESENT', 'Active participation', 2, '2026-11-04 09:01:10'),
(2, 2, 1, 'ABSENT', 'Unexcused absence', 2, '2026-11-04 09:30:00'),
(2, 3, 1, 'PRESENT', 'On time', 2, '2026-11-04 09:05:00'),

-- Session 3 (IT201 on Nov 9)
(3, 1, 1, 'PRESENT', 'On time', 2, '2026-11-09 09:00:25'),
(3, 2, 1, 'EXCUSED', 'Medical appointment with clinic slip', 2, '2026-11-09 09:10:00'),
(3, 3, 1, 'PRESENT', 'On time', 2, '2026-11-09 09:03:12'),

-- Session 4 (IT201 on Nov 11)
(4, 1, 1, 'PRESENT', 'Lab exercise submitted on time', 2, '2026-11-11 09:02:00'),
(4, 2, 1, 'PRESENT', 'Lab completed', 2, '2026-11-11 09:05:15'),
(4, 3, 1, 'LATE', 'Tardy 16 mins', 2, '2026-11-11 09:16:30'),

-- Session 5 (IT201 on Nov 16 - Today)
(5, 1, 1, 'PRESENT', 'Present today via QR Scan', 2, '2026-11-16 09:01:05'),
(5, 2, 1, 'PRESENT', 'Present today', 2, '2026-11-16 09:03:40'),
(5, 3, 1, 'LATE', 'Tardy 20 mins', 2, '2026-11-16 09:20:10'),

-- Session 6 (IT202 on Nov 10)
(6, 1, 1, 'PRESENT', 'On time', 2, '2026-11-10 13:01:50'),
(6, 2, 1, 'PRESENT', 'On time', 2, '2026-11-10 13:04:10'),
(6, 3, 1, 'PRESENT', 'On time', 2, '2026-11-10 13:02:44'),

-- Session 7 (IT202 on Nov 12)
(7, 1, 1, 'PRESENT', 'On time', 2, '2026-11-12 13:00:15'),
(7, 2, 1, 'ABSENT', 'No notice provided', 2, '2026-11-12 13:30:00'),
(7, 3, 1, 'PRESENT', 'On time', 2, '2026-11-12 13:05:22'),

-- Session 8 (CS102 on Nov 9)
(8, 4, 3, 'PRESENT', 'On time', 3, '2026-11-09 11:02:10'),
(8, 5, 3, 'PRESENT', 'On time', 3, '2026-11-09 11:03:15'),
(8, 6, 3, 'LATE', 'Tardy 15 mins', 3, '2026-11-09 11:15:00'),

-- Session 9 (CS102 on Nov 11)
(9, 4, 3, 'PRESENT', 'On time', 3, '2026-11-11 11:01:30'),
(9, 5, 3, 'ABSENT', 'Absent without notice', 3, '2026-11-11 11:30:00'),
(9, 6, 3, 'PRESENT', 'On time', 3, '2026-11-11 11:04:20');

-- 15. Insert Notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 'System Initialized', 'Welcome to the Attendance Management System. All database tables and modules are ready.', 'success', 0, NOW()),
(2, 'New Session Created', 'Attendance session for IT201 - BSIT-1A has been recorded successfully.', 'info', 0, NOW()),
(4, 'Attendance Recorded', 'Your attendance was marked as PRESENT for IT201 - Web Systems and Technologies.', 'success', 0, NOW()),
(5, 'Attendance Warning', 'You were marked ABSENT for IT202 on Nov 12. Please contact your instructor if excused.', 'warning', 0, NOW());

-- 16. Insert Activity Logs
INSERT INTO `activity_logs` (`user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 'SYSTEM_INIT', 'System database schema created and initial settings seeded.', '127.0.0.1', NOW()),
(1, 'USER_LOGIN', 'Administrator logged in to manage system configurations.', '127.0.0.1', NOW()),
(2, 'ATTENDANCE_RECORDED', 'Teacher Eleanor Vance recorded attendance for IT201 Session 5.', '127.0.0.1', NOW()),
(4, 'USER_LOGIN', 'Student Alexander Hayes logged in to view attendance dashboard.', '127.0.0.1', NOW());
