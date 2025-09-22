-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 21, 2025 at 05:59 PM
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
(2, 'shimo@gmail.com', 'Admin', '$2y$10$222l/8armb/HRyIK7ehLsO2K.xJ9r6sbhJhqzIBoGYgG5OjG/hyW6', 1, 1, 1, 'Africa/Kigali'),
(27, 'adelin@gmail.com', 'Guest', '$2y$10$vkN5tp85nAl9oK./H.2wrOp8.O7xEvOFJo/V19.RgeA.cp/XChJ2O', 1, 1, 1, 'Africa/Kigali');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
