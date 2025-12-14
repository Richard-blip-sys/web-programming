-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 12:31 PM
-- Server version: 11.4.5-MariaDB
-- PHP Version: 8.1.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `withdrawal_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `enrollment_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('active','withdrawn') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `subject_id`, `faculty_id`, `enrollment_date`, `status`) VALUES
(1, 4, 4, 2, '2025-11-18 03:13:43', 'withdrawn'),
(2, 5, 1, 2, '2025-11-18 03:58:46', 'active'),
(3, 6, 1, 2, '2025-11-26 23:42:35', 'active'),
(4, 6, 3, 2, '2025-11-26 23:42:47', 'active'),
(5, 4, 2, 2, '2025-11-27 00:15:01', 'active'),
(6, 4, 3, 2, '2025-11-27 00:18:23', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 4, 'withdrawal_approved', 'Withdrawal Approved ✅', 'Your withdrawal request for IT ELECTIVES 1 has been approved', 'student_dashboard.php', 0, '2025-11-27 00:05:19');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `units` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_code`, `subject_name`, `description`, `units`, `created_at`) VALUES
(1, 'CS101', 'Introduction to Computer Science', 'Basic programming concepts', 3, '2025-11-18 01:54:32'),
(2, 'CS102', 'Data Structures', 'Advanced data structures and algorithms', 3, '2025-11-18 01:54:32'),
(3, 'MATH201', 'Calculus I', 'Differential calculus', 4, '2025-11-18 01:54:32'),
(4, 'IT ELECTIVES 1', 'WEB DEV', 'N/A', 3, '2025-11-18 03:13:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `user_type` enum('faculty','student') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `first_name`, `last_name`, `user_type`, `created_at`) VALUES
(2, 'prof.smith', '$2y$10$iKDzGkWtANYPgh.QvbZ9n.4.CvACxcFiSTtjkVVx.PHY2DsLkteze', 'smith@university.edu', 'John', 'Smith', 'faculty', '2025-11-18 02:18:50'),
(3, 'student1', '$2y$10$svaCBW0DrbzMhVEZVAttqO7BSM6S8CKX8alc77Dn/zhkjv05ZNz.O', 'student1@university.edu', 'Jane', 'Doe', 'student', '2025-11-18 02:30:34'),
(4, 'Richard.21', '$2y$10$ngSwOsDLtVnCYsqj7ajY2.gC95LKdqPmUDldWhe1EKowV8OH/.sv2', 'chardy2106@gmail.com', 'Richard', 'Banquerigo', 'student', '2025-11-18 02:35:38'),
(5, 'Shed123', '$2y$10$6llS0uXS539voFWnnBgUE.d1kHFx23Tp7GY9IwGX3GKYFtt1TZdEG', 'shedbanq@gmail.com', 'Shedric', 'Banquerigo', 'student', '2025-11-18 03:48:43'),
(6, 'Carl.minoza', '$2y$10$e8AT6R1XZsO/2aEYdSjNLufG.OMs/Y6BbFqUqHkeqrZmMGtVllXOe', 'carlminoza@gmail.com', 'carl', 'minoza', 'student', '2025-11-26 23:42:05');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `request_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `request_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `withdrawal_requests`
--

INSERT INTO `withdrawal_requests` (`request_id`, `enrollment_id`, `reason`, `request_date`, `status`, `reviewed_by`, `review_date`, `review_notes`) VALUES
(1, 1, 'wala lang.\r\n', '2025-11-18 03:21:39', 'rejected', 2, '2025-11-18 03:38:28', 'wala lang din.'),
(2, 1, 'TEST WITHDRAWAL - 2025-11-27 00:45:04', '2025-11-26 23:45:04', 'approved', 2, '2025-11-27 00:05:19', 'Approved via test script'),
(3, 5, 'This is a test withdrawal request to verify the system is working correctly.', '2025-12-14 00:30:39', 'pending', NULL, NULL, NULL),
(4, 6, 'hahaha', '2025-12-14 00:39:28', 'pending', NULL, NULL, NULL),
(5, 5, 'halloween', '2025-12-14 00:42:04', 'pending', NULL, NULL, NULL),
(6, 5, 'halloween', '2025-12-14 01:09:43', 'pending', NULL, NULL, NULL),
(7, 6, 'i want to withdraw', '2025-12-14 01:10:25', 'pending', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`subject_id`),
  ADD KEY `idx_enrollments_student` (`student_id`),
  ADD KEY `idx_enrollments_subject` (`subject_id`),
  ADD KEY `idx_enrollments_faculty` (`faculty_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_withdrawal_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`),
  ADD CONSTRAINT `withdrawal_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
