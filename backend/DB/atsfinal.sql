-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 22, 2025 at 10:02 AM
-- Server version: 8.0.32
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `atsfinal`
--

-- --------------------------------------------------------

--
-- Table structure for table `periods`
--

DROP TABLE IF EXISTS `periods`;
CREATE TABLE IF NOT EXISTS `periods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `periods`
--

INSERT INTO `periods` (`id`, `name`, `day_of_week`, `start_time`, `end_time`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Wake up', 'Monday', '17:13:00', '19:13:00', 1, '2025-07-19 14:13:15', '2025-07-19 14:13:15'),
(2, 'lunch', 'Monday', '12:15:00', '12:18:00', 0, '2025-07-19 14:15:45', '2025-07-19 14:35:52'),
(4, 'jdfjje', 'Tuesday', '14:57:00', '21:53:00', 1, '2025-07-19 14:53:06', '2025-07-19 14:53:06'),
(6, 'Lunch Break', 'Monday', '12:00:00', '13:00:00', 1, '2025-07-19 15:07:04', '2025-09-21 17:24:15'),
(7, 'Afternoon Lab', 'Tuesday', '14:00:00', '16:00:00', 0, '2025-07-19 15:07:04', '2025-07-22 14:41:17'),
(8, 'Team Standup', 'Wednesday', '10:00:00', '10:15:00', 1, '2025-07-19 15:07:04', '2025-09-19 19:56:13'),
(9, 'Client Demo', 'Friday', '11:00:00', '12:00:00', 1, '2025-07-19 15:07:04', '2025-07-19 15:07:04'),
(10, 'Weekend Fun', 'Saturday', '20:21:00', '23:00:00', 1, '2025-07-19 15:07:04', '2025-09-20 22:21:00'),
(11, 'Happy Hour', 'Tuesday', '00:37:00', '13:56:00', 1, '2025-07-21 22:56:43', '2025-07-22 13:10:14'),
(12, 'Happy Hour', 'Thursday', '07:45:00', '15:34:00', 1, '2025-07-22 15:22:08', '2025-07-22 15:22:08'),
(13, 'Breakfast', 'Monday', '08:30:00', '09:00:00', 1, '2025-07-22 15:56:49', '2025-09-21 17:05:29'),
(14, 'trail', 'Sunday', '21:53:00', '22:30:00', 1, '2025-09-20 21:12:14', '2025-09-21 21:52:46'),
(15, 'trial2', 'Sunday', '21:54:00', '22:30:00', 1, '2025-09-20 21:14:54', '2025-09-21 21:52:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id_users` int NOT NULL AUTO_INCREMENT,
  `email` varchar(450) NOT NULL,
  `role` varchar(250) NOT NULL DEFAULT 'Guest',
  `passkey` varchar(450) NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `timetable_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `hard_switch_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `timezone` varchar(100) DEFAULT 'Africa/Kigali',
  PRIMARY KEY (`id_users`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_users`, `email`, `role`, `passkey`, `status`, `timetable_enabled`, `hard_switch_enabled`, `timezone`) VALUES
(2, 'shimo@gmail.com', 'Admin', '$2y$10$222l/8armb/HRyIK7ehLsO2K.xJ9r6sbhJhqzIBoGYgG5OjG/hyW6', 1, 0, 1, 'Africa/Kigali'),
(27, 'adelin@gmail.com', 'Guest', '$2y$10$vkN5tp85nAl9oK./H.2wrOp8.O7xEvOFJo/V19.RgeA.cp/XChJ2O', 1, 1, 1, 'Africa/Kigali');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
