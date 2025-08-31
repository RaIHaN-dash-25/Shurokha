-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:4306
-- Generation Time: Aug 23, 2025 at 09:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shurokha_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mother_user_id` bigint(20) UNSIGNED NOT NULL,
  `doctor_user_id` bigint(20) UNSIGNED NOT NULL,
  `doctor_schedule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `mother_user_id`, `doctor_user_id`, `doctor_schedule_id`, `scheduled_at`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 2, NULL, '2025-09-01 10:00:00', 'scheduled', 'Routine checkup', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(2, 5, 4, NULL, '2025-11-05 14:00:00', 'scheduled', 'Ultrasound appointment', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(3, 7, 6, NULL, '2025-10-25 09:30:00', 'scheduled', 'Monthly checkup', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(4, 9, 8, NULL, '2025-10-17 11:00:00', 'scheduled', 'Pregnancy consultation', '2025-08-22 18:46:25', '2025-08-23 16:10:44'),
(5, 11, 10, NULL, '2025-09-30 13:00:00', 'scheduled', 'Blood pressure check', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(6, 13, 12, NULL, '2025-11-15 16:00:00', 'scheduled', 'Routine checkup', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(7, 15, 14, NULL, '2025-12-05 08:30:00', 'scheduled', 'Monthly checkup', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(8, 17, 16, NULL, '2025-12-10 10:15:00', 'scheduled', 'Ultrasound appointment', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(9, 19, 18, NULL, '2025-11-20 14:30:00', 'scheduled', 'Blood work', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(10, 4, 2, NULL, '2025-09-20 12:00:00', 'scheduled', 'Routine checkup', '2025-08-22 18:46:25', '2025-08-22 18:46:25'),
(11, 9, 2, NULL, '2025-08-25 12:00:00', 'scheduled', '', '2025-08-23 19:03:52', '2025-08-23 19:03:52'),
(12, 9, 4, NULL, '2025-08-27 09:00:00', 'scheduled', '', '2025-08-23 19:07:27', '2025-08-23 19:07:27'),
(13, 9, 4, NULL, '2025-08-27 09:00:00', 'scheduled', '', '2025-08-23 19:07:45', '2025-08-23 19:07:45');

-- --------------------------------------------------------

--
-- Table structure for table `custom_reminders`
--

CREATE TABLE `custom_reminders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mother_user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `remind_at` datetime NOT NULL,
  `status` enum('pending','done','missed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_reminders`
--

INSERT INTO `custom_reminders` (`id`, `mother_user_id`, `title`, `remind_at`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 9, 'Doctor Appointment Prep', '2025-09-14 20:00:00', 'pending', 'Prepare documents for appointment', '2025-08-23 16:13:37', '2025-08-23 16:13:37'),
(2, 9, 'Drink Extra Water', '2025-09-01 15:00:00', 'done', 'Stay hydrated during afternoon', '2025-08-23 16:13:37', '2025-08-23 16:46:13'),
(3, 9, 'Have Medicine', '2025-08-23 17:46:00', 'done', NULL, '2025-08-23 16:46:48', '2025-08-23 16:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_profiles`
--

CREATE TABLE `doctor_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_profiles`
--

INSERT INTO `doctor_profiles` (`id`, `user_id`, `specialization`, `license_number`, `phone`) VALUES
(41, 2, 'Gynecology', 'L12345', '+1234567890'),
(42, 4, 'Pediatrics', 'L12346', '+1234567891'),
(43, 8, 'Obstetrics', 'L12347', '+1234567892'),
(44, 10, 'Cardiology', 'L12348', '+1234567893'),
(45, 12, 'Neurology', 'L12349', '+1234567894'),
(46, 14, 'Orthopedics', 'L12350', '+1234567895'),
(47, 16, 'Dermatology', 'L12351', '+1234567896'),
(48, 18, 'Psychiatry', 'L12352', '+1234567897');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `doctor_user_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `slot_number` tinyint(1) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_user_id`, `day_of_week`, `slot_number`, `start_time`, `end_time`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 2, 'Monday', 1, '09:00:00', '10:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(2, 2, 'Monday', 2, '11:00:00', '12:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(3, 2, 'Monday', 3, '14:00:00', '15:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(4, 2, 'Tuesday', 1, '09:00:00', '10:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(5, 2, 'Tuesday', 2, '11:00:00', '12:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(6, 2, 'Tuesday', 3, '14:00:00', '15:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(7, 2, 'Wednesday', 1, '09:00:00', '10:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(8, 2, 'Wednesday', 2, '11:00:00', '12:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(9, 2, 'Wednesday', 3, '14:00:00', '15:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(10, 2, 'Thursday', 1, '09:00:00', '10:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(11, 2, 'Thursday', 2, '11:00:00', '12:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(12, 2, 'Thursday', 3, '14:00:00', '15:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(13, 2, 'Friday', 1, '09:00:00', '10:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(14, 2, 'Friday', 2, '11:00:00', '12:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18'),
(15, 2, 'Friday', 3, '14:00:00', '15:00:00', 1, '2025-08-23 22:23:18', '2025-08-23 22:23:18');

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mother_user_id` bigint(20) UNSIGNED NOT NULL,
  `record_date` date NOT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `blood_pressure_systolic` smallint(6) DEFAULT NULL,
  `blood_pressure_diastolic` smallint(6) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_records`
--

INSERT INTO `health_records` (`id`, `mother_user_id`, `record_date`, `weight_kg`, `blood_pressure_systolic`, `blood_pressure_diastolic`, `notes`, `created_at`) VALUES
(1, 3, '2025-08-01', 65.50, 120, 80, 'Healthy pregnancy', '2025-08-22 18:48:31'),
(2, 5, '2025-08-10', 70.20, 130, 85, 'Normal blood pressure', '2025-08-22 18:48:31'),
(3, 7, '2025-08-20', 68.30, 125, 82, 'Routine checkup', '2025-08-22 18:48:31'),
(4, 9, '2025-08-25', 66.70, 122, 78, 'Pregnancy progressing well', '2025-08-22 18:48:31'),
(5, 11, '2025-08-15', 72.10, 128, 84, 'No concerns', '2025-08-22 18:48:31'),
(6, 13, '2025-08-30', 69.50, 127, 80, 'No issues found', '2025-08-22 18:48:31'),
(7, 15, '2025-08-10', 74.00, 135, 90, 'Blood pressure slightly high', '2025-08-22 18:48:31'),
(8, 17, '2025-08-05', 71.20, 126, 83, 'Pregnancy progressing well', '2025-08-22 18:48:31'),
(9, 19, '2025-08-20', 67.80, 124, 79, 'Everything normal', '2025-08-22 18:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `lab_reports`
--

CREATE TABLE `lab_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mother_user_id` bigint(20) UNSIGNED NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `result` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_reports`
--

INSERT INTO `lab_reports` (`id`, `mother_user_id`, `report_type`, `report_date`, `result`, `file_path`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 'Blood Test', '2025-08-01', 'Hemoglobin: 12.5 g/dL', '/files/lab3_1.pdf', 'Routine check', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(2, 3, 'Urine Test', '2025-08-02', 'No infection detected', '/files/lab3_2.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(3, 3, 'Ultrasound', '2025-08-05', 'Normal', '/files/lab3_3.pdf', 'First trimester scan', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(4, 3, 'Glucose Test', '2025-08-07', 'Fasting: 90 mg/dL', '/files/lab3_4.pdf', 'Routine sugar level', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(5, 5, 'Blood Test', '2025-08-03', 'Hemoglobin: 11.8 g/dL', '/files/lab5_1.pdf', 'Mild anemia', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(6, 5, 'Urine Test', '2025-08-04', 'Protein: Negative', '/files/lab5_2.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(7, 5, 'Ultrasound', '2025-08-06', 'Fetus healthy', '/files/lab5_3.pdf', 'Second trimester scan', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(8, 5, 'Glucose Test', '2025-08-08', 'Fasting: 95 mg/dL', '/files/lab5_4.pdf', 'Routine sugar level', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(9, 7, 'Blood Test', '2025-08-05', 'Hemoglobin: 12.2 g/dL', '/files/lab7_1.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(10, 7, 'Urine Test', '2025-08-06', 'No infection detected', '/files/lab7_2.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(11, 7, 'Ultrasound', '2025-08-08', 'Normal', '/files/lab7_3.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(12, 7, 'Glucose Test', '2025-08-10', 'Fasting: 88 mg/dL', '/files/lab7_4.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(13, 9, 'Blood Test', '2025-08-07', 'Hemoglobin: 13.0 g/dL', '/files/lab9_1.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(14, 9, 'Urine Test', '2025-08-08', 'Protein: Negative', '/files/lab9_2.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(15, 9, 'Ultrasound', '2025-08-10', 'Normal', '/files/lab9_3.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(16, 9, 'Glucose Test', '2025-08-12', 'Fasting: 92 mg/dL', '/files/lab9_4.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(17, 9, 'Blood Pressure Report', '2025-08-13', '120/80 mmHg', '/files/lab9_5.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(18, 11, 'Blood Test', '2025-08-09', 'Hemoglobin: 12.7 g/dL', '/files/lab11_1.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(19, 11, 'Urine Test', '2025-08-10', 'No infection detected', '/files/lab11_2.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(20, 11, 'Ultrasound', '2025-08-12', 'Normal', '/files/lab11_3.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(21, 11, 'Glucose Test', '2025-08-14', 'Fasting: 91 mg/dL', '/files/lab11_4.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(22, 13, 'Blood Test', '2025-08-11', 'Hemoglobin: 11.9 g/dL', '/files/lab13_1.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(23, 13, 'Urine Test', '2025-08-12', 'Protein: Negative', '/files/lab13_2.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(24, 13, 'Ultrasound', '2025-08-14', 'Normal', '/files/lab13_3.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(25, 13, 'Glucose Test', '2025-08-16', 'Fasting: 89 mg/dL', '/files/lab13_4.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(26, 15, 'Blood Test', '2025-08-13', 'Hemoglobin: 12.4 g/dL', '/files/lab15_1.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(27, 15, 'Urine Test', '2025-08-14', 'No infection detected', '/files/lab15_2.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(28, 15, 'Ultrasound', '2025-08-16', 'Normal', '/files/lab15_3.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(29, 15, 'Glucose Test', '2025-08-18', 'Fasting: 93 mg/dL', '/files/lab15_4.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(30, 15, 'Blood Pressure Report', '2025-08-19', '118/78 mmHg', '/files/lab15_5.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(31, 17, 'Blood Test', '2025-08-15', 'Hemoglobin: 12.6 g/dL', '/files/lab17_1.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(32, 17, 'Urine Test', '2025-08-16', 'No infection detected', '/files/lab17_2.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(33, 17, 'Ultrasound', '2025-08-18', 'Normal', '/files/lab17_3.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26'),
(34, 17, 'Glucose Test', '2025-08-20', 'Fasting: 90 mg/dL', '/files/lab17_4.pdf', '', '2025-08-23 10:56:26', '2025-08-23 10:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `medication_records`
--

CREATE TABLE `medication_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mother_user_id` bigint(20) UNSIGNED NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `prescribed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medication_records`
--

INSERT INTO `medication_records` (`id`, `mother_user_id`, `medication_name`, `dosage`, `frequency`, `start_date`, `end_date`, `prescribed_by`, `notes`, `created_at`, `updated_at`) VALUES
(10, 3, 'Iron Supplement', '100mg', 'Once daily', '2025-08-01', '2025-08-31', 2, 'For anemia', '2025-08-23 10:45:03', '2025-08-23 10:45:03'),
(11, 5, 'Folic Acid', '400mcg', 'Once daily', '2025-08-05', '2025-09-05', 4, 'Prenatal supplement', '2025-08-23 10:45:03', '2025-08-23 10:45:03'),
(12, 7, 'Vitamin D', '2000 IU', 'Once daily', '2025-08-10', '2025-09-10', 8, 'Bone health', '2025-08-23 10:45:03', '2025-08-23 10:45:03'),
(13, 9, 'Calcium', '500mg', 'Twice daily', '2025-08-12', '2025-09-12', 10, 'Supports pregnancy', '2025-08-23 10:45:03', '2025-08-23 10:45:03'),
(14, 11, 'Paracetamol', '500mg', 'As needed', '2025-08-15', '2025-08-20', 12, 'Pain relief', '2025-08-23 10:45:03', '2025-08-23 10:45:03'),
(15, 13, 'Antibiotic A', '250mg', 'Twice daily', '2025-08-16', '2025-08-26', 14, 'UTI treatment', '2025-08-23 10:45:03', '2025-08-23 10:45:03'),
(16, 15, 'Antacid', '10ml', 'Three times daily', '2025-08-18', '2025-08-28', 16, 'Heartburn relief', '2025-08-23 10:45:03', '2025-08-23 10:45:03'),
(17, 17, 'Omega-3', '1000mg', 'Once daily', '2025-08-20', '2025-09-20', 18, 'Brain development', '2025-08-23 10:45:03', '2025-08-23 10:45:03');

-- --------------------------------------------------------

--
-- Table structure for table `medication_reminders`
--

CREATE TABLE `medication_reminders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mother_user_id` bigint(20) UNSIGNED NOT NULL,
  `medication_record_id` bigint(20) UNSIGNED NOT NULL,
  `remind_time` time NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `days_mask` smallint(5) UNSIGNED DEFAULT 127,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication_reminders`
--

INSERT INTO `medication_reminders` (`id`, `mother_user_id`, `medication_record_id`, `remind_time`, `is_enabled`, `days_mask`, `notes`, `created_at`, `updated_at`) VALUES
(1, 9, 13, '09:00:00', 1, 127, 'Daily iron tablet reminder', '2025-08-23 16:13:37', '2025-08-23 16:13:37'),
(2, 9, 13, '19:00:00', 1, 62, 'Evening calcium dose (Mon–Fri)', '2025-08-23 16:13:37', '2025-08-23 16:13:37');

-- --------------------------------------------------------

--
-- Table structure for table `medication_reminder_logs`
--

CREATE TABLE `medication_reminder_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `medication_reminder_id` bigint(20) UNSIGNED NOT NULL,
  `logged_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('taken','not_taken','skipped') NOT NULL DEFAULT 'not_taken',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication_reminder_logs`
--

INSERT INTO `medication_reminder_logs` (`id`, `medication_reminder_id`, `logged_at`, `status`, `notes`) VALUES
(1, 1, '2025-08-23 16:43:23', 'taken', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_user_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_user_id` bigint(20) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mother_profiles`
--

CREATE TABLE `mother_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `blood_type` varchar(3) DEFAULT NULL,
  `emergency_contact` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mother_profiles`
--

INSERT INTO `mother_profiles` (`id`, `user_id`, `date_of_birth`, `address`, `phone`, `due_date`, `blood_type`, `emergency_contact`) VALUES
(1, 3, '1990-05-15', '123 Maple St, Cityville', '+1234567890', '2025-09-30', 'O+', 'John Doe'),
(2, 5, '1985-09-20', '456 Oak Ave, Townville', '+1234567891', '2025-11-15', 'A-', 'James Brown'),
(3, 7, '1992-12-10', '789 Pine Rd, Villageburg', '+1234567892', '2026-01-10', 'B+', 'David Clark'),
(4, 9, '1988-07-03', '101 Birch Ln, Suburbia', '+1234567893', '2025-10-20', 'AB-', 'Elizabeth Lewis'),
(5, 11, '1993-01-22', '202 Cedar St, Greenfield', '+1234567894', '2025-12-25', 'O-', 'Richard Allen'),
(6, 13, '1991-10-15', '303 Elm Dr, Hilltop', '+1234567895', '2026-02-05', 'A+', 'Susan King'),
(7, 15, '1987-08-30', '404 Willow Blvd, Riverside', '+1234567896', '2025-08-15', 'B-', 'Jessica Young'),
(8, 17, '1994-11-05', '505 Redwood St, Parkview', '+1234567897', '2026-03-01', 'O+', 'Charles Adams'),
(9, 19, '1990-03-12', '606 Maple Dr, Citytown', '+1234567898', '2025-07-20', 'AB+', 'Nancy Baker');

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

CREATE TABLE `notification_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `app_push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `timezone` varchar(64) DEFAULT 'Asia/Dhaka',
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `user_id`, `sms_enabled`, `app_push_enabled`, `email_enabled`, `timezone`, `quiet_hours_start`, `quiet_hours_end`, `created_at`, `updated_at`) VALUES
(1, 9, 1, 0, 0, 'Asia/Dhaka', '22:00:00', '06:00:00', '2025-08-23 16:13:37', '2025-08-23 16:45:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','doctor','mother') NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `status`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'john.doe@example.com', '', 'hashed_password_1', 'admin', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(2, 'Jane Smith', 'jane.smith@example.com', '', 'hashed_password_2', 'doctor', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(3, 'Mary Johnson', 'mary.johnson@example.com', '', 'hashed_password_3', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(4, 'James Brown', 'james.brown@example.com', '', 'hashed_password_4', 'doctor', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(5, 'Patricia Taylor', 'patricia.taylor@example.com', '', 'hashed_password_5', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(6, 'Michael White', 'michael.white@example.com', '', 'hashed_password_6', 'admin', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(7, 'Linda Harris', 'linda.harris@example.com', '', 'hashed_password_7', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(8, 'David Clark', 'david.clark@example.com', '', 'hashed_password_8', 'doctor', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(9, 'Elizabeth Lewis', 'elizabeth.lewis@example.com', '', 'hashed_password_9', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(10, 'William Walker', 'william.walker@example.com', '', 'hashed_password_10', 'doctor', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(11, 'Barbara Hall', 'barbara.hall@example.com', '', 'hashed_password_11', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(12, 'Richard Allen', 'richard.allen@example.com', '', 'hashed_password_12', 'doctor', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(13, 'Susan King', 'susan.king@example.com', '', 'hashed_password_13', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(14, 'Joseph Scott', 'joseph.scott@example.com', '', 'hashed_password_14', 'doctor', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(15, 'Jessica Young', 'jessica.young@example.com', '', 'hashed_password_15', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(16, 'Charles Adams', 'charles.adams@example.com', '', 'hashed_password_16', 'doctor', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(17, 'Nancy Baker', 'nancy.baker@example.com', '', 'hashed_password_17', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(18, 'Thomas Gonzalez', 'thomas.gonzalez@example.com', '', 'hashed_password_18', 'doctor', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05'),
(19, 'Dorothy Nelson', 'dorothy.nelson@example.com', '', 'hashed_password_19', 'mother', 'Active', 1, '2025-08-22 18:37:05', '2025-08-22 18:37:05');

-- --------------------------------------------------------

--
-- Table structure for table `vaccinations`
--

CREATE TABLE `vaccinations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mother_user_id` bigint(20) UNSIGNED NOT NULL,
  `vaccine_name` varchar(120) NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `status` enum('completed','upcoming','missed') NOT NULL DEFAULT 'upcoming',
  `completed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccinations`
--

INSERT INTO `vaccinations` (`id`, `mother_user_id`, `vaccine_name`, `scheduled_date`, `status`, `completed_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 9, 'Tetanus', '2025-09-15', 'upcoming', NULL, 'Second dose due', '2025-08-23 16:13:37', '2025-08-23 16:13:37'),
(2, 9, 'Influenza', '2025-10-01', 'upcoming', NULL, 'Seasonal flu shot', '2025-08-23 16:13:37', '2025-08-23 16:13:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointments_mother` (`mother_user_id`),
  ADD KEY `idx_appointments_doctor` (`doctor_user_id`),
  ADD KEY `fk_appt_schedule` (`doctor_schedule_id`);

--
-- Indexes for table `custom_reminders`
--
ALTER TABLE `custom_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_custrem_mother_time` (`mother_user_id`,`remind_at`);

--
-- Indexes for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_doctor_day_slot` (`doctor_user_id`,`day_of_week`,`slot_number`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mother_user_id` (`mother_user_id`);

--
-- Indexes for table `lab_reports`
--
ALTER TABLE `lab_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lab_mother` (`mother_user_id`);

--
-- Indexes for table `medication_records`
--
ALTER TABLE `medication_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_med_mother` (`mother_user_id`),
  ADD KEY `fk_med_prescriber` (`prescribed_by`);

--
-- Indexes for table `medication_reminders`
--
ALTER TABLE `medication_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medrem_mother` (`mother_user_id`),
  ADD KEY `idx_medrem_course` (`medication_record_id`);

--
-- Indexes for table `medication_reminder_logs`
--
ALTER TABLE `medication_reminder_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medrem_logs_reminder` (`medication_reminder_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_user_id` (`sender_user_id`),
  ADD KEY `idx_messages_receiver` (`receiver_user_id`);

--
-- Indexes for table `mother_profiles`
--
ALTER TABLE `mother_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_notification_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vax_mother` (`mother_user_id`),
  ADD KEY `idx_vax_status_date` (`status`,`scheduled_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `custom_reminders`
--
ALTER TABLE `custom_reminders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lab_reports`
--
ALTER TABLE `lab_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `medication_records`
--
ALTER TABLE `medication_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `medication_reminders`
--
ALTER TABLE `medication_reminders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medication_reminder_logs`
--
ALTER TABLE `medication_reminder_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `mother_profiles`
--
ALTER TABLE `mother_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `vaccinations`
--
ALTER TABLE `vaccinations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`mother_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appt_schedule` FOREIGN KEY (`doctor_schedule_id`) REFERENCES `doctor_schedules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `custom_reminders`
--
ALTER TABLE `custom_reminders`
  ADD CONSTRAINT `fk_custrem_mother` FOREIGN KEY (`mother_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD CONSTRAINT `doctor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `fk_schedule_doctor` FOREIGN KEY (`doctor_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`mother_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_reports`
--
ALTER TABLE `lab_reports`
  ADD CONSTRAINT `fk_lab_mother` FOREIGN KEY (`mother_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medication_records`
--
ALTER TABLE `medication_records`
  ADD CONSTRAINT `fk_med_mother` FOREIGN KEY (`mother_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_med_prescriber` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `medication_reminders`
--
ALTER TABLE `medication_reminders`
  ADD CONSTRAINT `fk_medrem_course` FOREIGN KEY (`medication_record_id`) REFERENCES `medication_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_medrem_mother` FOREIGN KEY (`mother_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medication_reminder_logs`
--
ALTER TABLE `medication_reminder_logs`
  ADD CONSTRAINT `fk_medrem_logs_reminder` FOREIGN KEY (`medication_reminder_id`) REFERENCES `medication_reminders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mother_profiles`
--
ALTER TABLE `mother_profiles`
  ADD CONSTRAINT `mother_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD CONSTRAINT `fk_vax_mother` FOREIGN KEY (`mother_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
