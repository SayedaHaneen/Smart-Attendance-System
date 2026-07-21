-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 19, 2026 at 02:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_sessions`
--

CREATE TABLE `active_sessions` (
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`, `full_name`, `created_at`) VALUES
(1, 'admin', 'admin@university.edu', 'admin123', 'System Admin', '2026-07-19 09:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_time` time NOT NULL,
  `status` enum('Present','Absent','Late') DEFAULT 'Present',
  `device_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `session_id`, `teacher_id`, `attendance_date`, `attendance_time`, `status`, `device_id`, `latitude`, `longitude`, `marked_at`) VALUES
(1, 18, 2, 1, '2026-07-19', '17:12:11', 'Present', NULL, NULL, NULL, '2026-07-19 12:12:11'),
(2, 16, 2, 1, '2026-07-19', '17:13:20', 'Present', NULL, NULL, NULL, '2026-07-19 12:13:20'),
(3, 17, 2, 1, '2026-07-19', '17:13:24', 'Present', NULL, NULL, NULL, '2026-07-19 12:13:24');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `session_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT 5,
  `qr_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`session_id`, `teacher_id`, `subject_id`, `semester_id`, `section_id`, `batch_id`, `session_date`, `start_time`, `end_time`, `duration_minutes`, `qr_code`, `is_active`, `created_at`) VALUES
(1, 1, 1, 2, 1, 3, '2026-07-19', '14:13:00', '14:33:00', 20, '6a5c955f939dd_1784452447', 1, '2026-07-19 09:14:07'),
(2, 1, 3, 2, 1, 3, '2026-07-19', '17:04:00', '17:24:00', 20, '6a5cbd6b0f34e_1784462699', 1, '2026-07-19 12:04:59');

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `batch_id` int(11) NOT NULL,
  `batch_year` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`batch_id`, `batch_year`, `created_at`) VALUES
(1, '2023', '2026-07-19 09:09:59'),
(2, '2024', '2026-07-19 09:09:59'),
(3, '2025', '2026-07-19 09:09:59'),
(4, '2026', '2026-07-19 09:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `created_at`) VALUES
(1, 'Software Engineering', '2026-07-19 09:09:59'),
(2, 'Computer Science', '2026-07-19 09:09:59'),
(3, 'Information Technology', '2026-07-19 09:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `log_id` int(11) NOT NULL,
  `user_type` enum('student','teacher','admin') NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`log_id`, `user_type`, `user_id`, `ip_address`, `user_agent`, `login_time`) VALUES
(1, 'student', 1, '192.168.18.3', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 09:29:37'),
(2, 'student', 1, '192.168.18.3', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 10:08:30'),
(3, 'student', 2, '192.168.18.3', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 10:09:39'),
(4, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-19 10:26:47'),
(5, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-19 10:28:35'),
(6, 'student', 3, '192.168.18.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 10:37:20'),
(7, 'student', 3, '192.168.18.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 10:37:45'),
(8, 'student', 5, '192.168.18.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 10:50:21'),
(9, 'student', 6, '192.168.18.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 10:52:29'),
(10, 'student', 7, '192.168.2.185', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-19 11:59:33'),
(11, 'student', 8, '192.168.2.229', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-19 12:00:06'),
(12, 'student', 9, '192.168.2.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-19 12:00:49'),
(13, 'student', 10, '192.168.100.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 12:01:20'),
(14, 'student', 11, '192.168.2.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-19 12:01:21'),
(15, 'student', 12, '192.168.100.54', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-07-19 12:02:08'),
(16, 'student', 9, '192.168.2.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-19 12:03:55'),
(17, 'teacher', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-19 12:04:00'),
(18, 'student', 13, '192.168.2.229', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-19 12:05:20'),
(19, 'student', 14, '192.168.100.48', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 12:05:23'),
(20, 'student', 15, '192.168.100.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 12:05:39'),
(21, 'student', 11, '192.168.2.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-19 12:06:26'),
(22, 'student', 17, '192.168.100.23', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_5_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/429.1.942703598 Mobile/15E148 Safari/604.1', '2026-07-19 12:10:45'),
(23, 'student', 16, '192.168.100.18', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 12:10:53'),
(24, 'student', 18, '192.168.100.125', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-19 12:11:25');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_type` enum('student','teacher','admin') NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_type`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 'admin', 1, 'New Student Registration', 'A new student has registered: Jhangi Meghwar (Kumar). Please approve the account.', 0, '2026-07-19 10:29:53'),
(2, 'student', 3, 'Account Approved', 'Your account has been approved by admin. You can now login.', 0, '2026-07-19 10:30:05'),
(3, 'student', 3, 'Account Approved', 'Your account has been approved by admin. You can now login.', 0, '2026-07-19 10:30:08'),
(4, 'student', 3, 'Account Approved', 'Your account has been approved by admin. You can now login.', 0, '2026-07-19 10:30:09'),
(5, 'admin', 1, 'New Student Registration', 'A new student has registered: Jhangi Meghwar (Kumar).', 0, '2026-07-19 10:46:29'),
(6, 'admin', 1, 'New Student Registration', 'A new student has registered: Kumar (Abcd).', 0, '2026-07-19 10:50:09'),
(7, 'admin', 1, 'New Student Registration', 'A new student has registered: Abd (Makhan).', 0, '2026-07-19 10:52:07'),
(8, 'admin', 1, 'New Student Registration', 'A new student has registered: Wafabatool Shah (wafa shah).', 0, '2026-07-19 11:59:22'),
(9, 'admin', 1, 'New Student Registration', 'A new student has registered: Sayeda Haneen (Haneen).', 0, '2026-07-19 11:59:48'),
(10, 'admin', 1, 'New Student Registration', 'A new student has registered: Zara (zara).', 0, '2026-07-19 12:00:24'),
(11, 'admin', 1, 'New Student Registration', 'A new student has registered: Abdul Ahad (Abdul Ahad).', 0, '2026-07-19 12:01:02'),
(12, 'admin', 1, 'New Student Registration', 'A new student has registered: Amjad Ali Shah (Amjad).', 0, '2026-07-19 12:01:07'),
(13, 'admin', 1, 'New Student Registration', 'A new student has registered: Dahani (Zafar).', 0, '2026-07-19 12:01:33'),
(14, 'admin', 1, 'New Student Registration', 'A new student has registered: Sayeda Haneen (Haneen).', 0, '2026-07-19 12:05:03'),
(15, 'admin', 1, 'New Student Registration', 'A new student has registered: Hasnain (Hasnain78).', 0, '2026-07-19 12:05:16'),
(16, 'admin', 1, 'New Student Registration', 'A new student has registered: Agha Muhammad afzal (Agha Afzal).', 0, '2026-07-19 12:05:25'),
(17, 'admin', 1, 'New Student Registration', 'A new student has registered: Sayeda Haneen (Haneen_hussain).', 0, '2026-07-19 12:10:01'),
(18, 'admin', 1, 'New Student Registration', 'A new student has registered: Rida (Rida).', 0, '2026-07-19 12:10:21'),
(19, 'admin', 1, 'New Student Registration', 'A new student has registered: Muddasir Ali Shah (Sayed).', 0, '2026-07-19 12:11:14');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_name`, `created_at`) VALUES
(1, 'A', '2026-07-19 09:09:59'),
(2, 'B', '2026-07-19 09:09:59'),
(3, 'C', '2026-07-19 09:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`semester_id`, `semester_name`, `created_at`) VALUES
(1, '1', '2026-07-19 09:09:59'),
(2, '2', '2026-07-19 09:09:59'),
(3, '3', '2026-07-19 09:09:59'),
(4, '4', '2026-07-19 09:09:59'),
(5, '5', '2026-07-19 09:09:59'),
(6, '6', '2026-07-19 09:09:59'),
(7, '7', '2026-07-19 09:09:59'),
(8, '8', '2026-07-19 09:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `roll_number` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `username`, `roll_number`, `full_name`, `email`, `password`, `department_id`, `batch_id`, `semester_id`, `section_id`, `phone`, `is_approved`, `approved_at`, `created_at`) VALUES
(1, 'akash', '2025-SE-001', 'Akash Manglani', 'akash@university.edu', 'admin123', 1, 3, 2, 1, '1234567890', 1, NULL, '2026-07-19 09:09:59'),
(4, 'Kumar', '06', 'Jhangi Meghwar', 'jhangithanwani@gmail.com', '1234567', 1, 3, 2, 1, '3400893540', 1, NULL, '2026-07-19 10:46:29'),
(5, 'Abcd', '6', 'Kumar', 'abcd@gmail.com', '123456', 1, 3, 3, 1, '3400893540', 1, NULL, '2026-07-19 10:50:09'),
(7, 'wafa shah', '2k23/IT/151', 'Wafabatool Shah', 'wafabatoolshah94@gmail.com', 'wafaaa', 3, 2, 5, 1, '03413208410', 1, NULL, '2026-07-19 11:59:22'),
(9, 'zara', '2024-BSIT-90', 'Zara', 'zara@gmail.com', '12345678', 3, 2, 7, 1, '1234567895678', 1, NULL, '2026-07-19 12:00:24'),
(10, 'Abdul Ahad', '2K23/SE/76', 'Abdul Ahad', 'ahad@gmail.com', 'ahad123', 1, 1, 8, 1, '51643434334', 1, NULL, '2026-07-19 12:01:02'),
(11, 'Amjad', '2024-IT-20', 'Amjad Ali Shah', 'sayed11@gmail.com', '12345678', 3, 2, 5, 1, '03251378803', 1, NULL, '2026-07-19 12:01:07'),
(12, 'Zafar', '2k24/IT/162', 'Dahani', '2k24-it-162@usindh.edu.pk', 'zafar@1234', 3, 2, 3, 1, '03113069420', 1, NULL, '2026-07-19 12:01:33'),
(13, 'Haneen', '49', 'Sayeda Haneen', 'syedahaneensayedbscsf23@iba-suk.edu.pk', 'abc123', 1, 3, 2, 1, '03083437891', 1, NULL, '2026-07-19 12:05:03'),
(14, 'Hasnain78', '2k24/IT/66', 'Hasnain', '2k24-it-66@usindh.edu.pk', 'has123@@@', 3, 2, 6, 1, '', 1, NULL, '2026-07-19 12:05:16'),
(15, 'Agha Afzal', '2k24-IT-15', 'Agha Muhammad afzal', '2k24-it-15@usindh.edu.pk', 'agha@6363', 3, 2, 6, 1, '03193992572', 1, NULL, '2026-07-19 12:05:25'),
(16, 'Haneen_hussain', '50', 'Sayeda Haneen', 'Syedahaneensayed.bscs23@iba-suk.edu.pk', 'abc123', 1, 3, 2, 1, '03083438869', 1, NULL, '2026-07-19 12:10:01'),
(17, 'Rida', '2024-BSIT-80', 'Rida', 'rida@gmail.com', '135790', 3, 2, 4, 1, '21845480484', 1, NULL, '2026-07-19 12:10:21'),
(18, 'Sayed', '2025-SW-20', 'Muddasir Ali Shah', 'sayed345@gmail.com', 'shah1234', 1, 3, 2, 1, '03251378803', 1, NULL, '2026-07-19 12:11:14');

-- --------------------------------------------------------

--
-- Table structure for table `student_devices`
--

CREATE TABLE `student_devices` (
  `device_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `device_identifier` varchar(255) NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_locked` tinyint(1) DEFAULT 0,
  `locked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_devices`
--

INSERT INTO `student_devices` (`device_id`, `student_id`, `device_identifier`, `device_name`, `is_active`, `registered_at`, `last_used`, `is_locked`, `locked_at`) VALUES
(1, 1, '8f1f8303ecdac7a0ead42de10a2b422d', NULL, 1, '2026-07-19 09:29:37', '2026-07-19 10:08:30', 0, NULL),
(5, 4, 'TW96aWxsYS81LjAgKExpbnV4OyBBbmRy', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Sa', 1, '2026-07-19 10:46:29', '2026-07-19 10:46:29', 0, NULL),
(6, 5, 'TW96aWxsYS81LjAgKExpbnV4OyBBbmRy', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Sa', 1, '2026-07-19 10:50:09', '2026-07-19 10:50:21', 0, NULL),
(8, 7, 'TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Sa', 1, '2026-07-19 11:59:22', '2026-07-19 11:59:33', 0, NULL),
(10, 9, 'TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Sa', 1, '2026-07-19 12:00:24', '2026-07-19 12:03:55', 0, NULL),
(11, 10, 'TW96aWxsYS81LjAgKExpbnV4OyBBbmRy', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Sa', 1, '2026-07-19 12:01:02', '2026-07-19 12:01:20', 0, NULL),
(12, 11, 'TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Sa', 1, '2026-07-19 12:01:07', '2026-07-19 12:06:26', 0, NULL),
(13, 12, 'TW96aWxsYS81LjAgKExpbnV4OyBBbmRy', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Sa', 1, '2026-07-19 12:01:33', '2026-07-19 12:02:08', 0, NULL),
(14, 13, 'TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Sa', 1, '2026-07-19 12:05:03', '2026-07-19 12:05:20', 0, NULL),
(15, 14, 'TW96aWxsYS81LjAgKExpbnV4OyBBbmRy', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Sa', 1, '2026-07-19 12:05:16', '2026-07-19 12:05:23', 0, NULL),
(16, 15, 'TW96aWxsYS81LjAgKExpbnV4OyBBbmRy', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Sa', 1, '2026-07-19 12:05:25', '2026-07-19 12:05:39', 0, NULL),
(17, 16, 'TW96aWxsYS81LjAgKExpbnV4OyBBbmRy', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Sa', 1, '2026-07-19 12:10:01', '2026-07-19 12:10:53', 0, NULL),
(18, 17, 'TW96aWxsYS81LjAgKGlQaG9uZTsgQ1BV', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_5_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GS', 1, '2026-07-19 12:10:21', '2026-07-19 12:10:45', 0, NULL),
(19, 18, 'TW96aWxsYS81LjAgKExpbnV4OyBBbmRy', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Sa', 1, '2026-07-19 12:11:14', '2026-07-19 12:11:25', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `subject_code`, `department_id`, `semester_id`, `created_at`) VALUES
(1, 'Database Systems', 'CS-301', 1, 3, '2026-07-19 09:09:59'),
(2, 'Web Development', 'CS-302', 1, 3, '2026-07-19 09:09:59'),
(3, 'Software Engineering', 'CS-303', 1, 3, '2026-07-19 09:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `username`, `email`, `password`, `full_name`, `department_id`, `phone`, `created_at`) VALUES
(1, 'teacher', 'teacher@university.edu', 'admin123', 'Mr. Ahmed', 1, '1234567890', '2026-07-19 09:09:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `unique_active_session` (`student_id`,`device_id`),
  ADD KEY `idx_active_sessions_student` (`student_id`),
  ADD KEY `idx_active_sessions_device` (`device_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD UNIQUE KEY `batch_year` (`batch_year`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `section_name` (`section_name`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`),
  ADD UNIQUE KEY `semester_name` (`semester_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `roll_number` (`roll_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `student_devices`
--
ALTER TABLE `student_devices`
  ADD PRIMARY KEY (`device_id`),
  ADD UNIQUE KEY `unique_student_device` (`student_id`,`device_identifier`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `active_sessions`
--
ALTER TABLE `active_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `student_devices`
--
ALTER TABLE `student_devices`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD CONSTRAINT `active_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `active_sessions_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `student_devices` (`device_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `attendance_sessions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`),
  ADD CONSTRAINT `attendance_sessions_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `attendance_sessions_ibfk_3` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `attendance_sessions_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`),
  ADD CONSTRAINT `attendance_sessions_ibfk_5` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `students_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`);

--
-- Constraints for table `student_devices`
--
ALTER TABLE `student_devices`
  ADD CONSTRAINT `student_devices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
