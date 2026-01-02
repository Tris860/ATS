-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 02, 2026 at 10:32 PM
-- Server version: 8.0.31
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
-- Table structure for table `hardware`
--

DROP TABLE IF EXISTS `hardware`;
CREATE TABLE IF NOT EXISTS `hardware` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `passkey` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hardware`
--

INSERT INTO `hardware` (`id`, `name`, `passkey`) VALUES
(1, 'wemos_user', '$2y$10$Ln5DIe/bJpujtExF/0QTZegj3jPIZBG97sCpTd2ZvDeEVpQOLmw1W');

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
  `current` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `owner` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `periods`
--

INSERT INTO `periods` (`id`, `name`, `day_of_week`, `start_time`, `end_time`, `current`, `active`, `owner`, `created_at`, `updated_at`) VALUES
(1, 'Wake up', 'Monday', '17:13:00', '19:13:00', 0, 1, 'shimo@gmail.com', '2025-07-19 14:13:15', '2025-12-04 20:32:11'),
(2, 'lunch', 'Monday', '12:15:00', '12:18:00', 0, 0, 'shimo@gmail.com', '2025-07-19 14:15:45', '2025-11-27 20:58:11'),
(4, 'jdfjje', 'Tuesday', '14:57:00', '21:53:00', 0, 1, 'shimo@gmail.com', '2025-07-19 14:53:06', '2025-11-27 20:58:11'),
(6, 'Lunch Break', 'Monday', '12:00:00', '13:00:00', 0, 1, 'shimo@gmail.com', '2025-07-19 15:07:04', '2025-11-27 20:58:11'),
(7, 'Afternoon Lab', 'Tuesday', '14:00:00', '16:00:00', 0, 0, 'shimo@gmail.com', '2025-07-19 15:07:04', '2025-11-27 20:58:11'),
(8, 'Team Standup', 'Wednesday', '10:00:00', '10:15:00', 0, 1, 'shimo@gmail.com', '2025-07-19 15:07:04', '2025-11-27 20:58:11'),
(9, 'Client Demo', 'Thursday', '20:34:00', '23:59:00', 1, 1, 'shimo@gmail.com', '2025-07-19 15:07:04', '2025-12-04 20:34:11'),
(10, 'Weekend Fun', 'Saturday', '21:20:00', '23:00:00', 0, 1, 'shimo@gmail.com', '2025-07-19 15:07:04', '2025-11-29 21:23:04'),
(11, 'Happy Hour', 'Tuesday', '00:37:00', '13:56:00', 0, 1, 'shimo@gmail.com', '2025-07-21 22:56:43', '2025-11-27 20:58:11'),
(12, 'Happy Hour', 'Thursday', '20:32:00', '23:34:00', 0, 1, 'shimo@gmail.com', '2025-07-22 15:22:08', '2025-12-04 20:34:11'),
(13, 'Breakfast', 'Monday', '08:30:00', '09:00:00', 0, 1, 'shimo@gmail.com', '2025-07-22 15:56:49', '2025-11-27 20:58:11'),
(14, 'trail', 'Friday', '20:21:00', '22:30:00', 0, 1, 'shimo@gmail.com', '2025-09-20 21:12:14', '2025-11-27 20:58:11'),
(15, 'trial2', 'Sunday', '18:00:00', '22:30:00', 0, 1, 'shimo@gmail.com', '2025-09-20 21:14:54', '2025-11-27 20:58:11'),
(16, 'testing', 'Saturday', '21:25:00', '23:50:00', 0, 1, 'shimo@gmail.com', '2025-10-24 17:44:36', '2025-11-29 21:25:04'),
(17, 'testing_2', 'Sunday', '11:40:00', '23:50:04', 0, 1, 'shimo@gmail.com', '2025-10-24 17:45:14', '2025-12-01 17:13:50'),
(18, 'tester', 'Saturday', '20:24:00', '23:00:00', 0, 1, 'shimo@gmail.com', '2025-10-25 13:16:57', '2025-11-29 21:18:58'),
(20, 'test3', 'Sunday', '10:55:00', '23:00:00', 0, 1, 'shimo@gmail.com', '2025-11-07 20:30:05', '2025-11-30 11:00:54'),
(21, 'test4', 'Friday', '20:27:00', '23:59:00', 0, 1, 'shimo@gmail.com', '2025-11-07 20:32:05', '2025-11-27 20:58:11'),
(22, 'test6', 'Sunday', '11:10:00', '23:59:00', 0, 1, 'shimo@gmail.com', '2025-11-07 20:32:09', '2025-11-30 11:13:54'),
(23, 'tester', 'Sunday', '11:13:00', '23:00:00', 0, 1, 'shimo@gmail.com', '2025-11-08 11:10:56', '2025-11-30 11:40:54'),
(24, 'tris', 'Saturday', '20:43:00', '23:00:00', 0, 1, 'shimo@gmail.com', '2025-11-15 19:43:46', '2025-11-27 20:58:11');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_of_expiry` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `date_of_expiry`, `status`) VALUES
(2, '2026-02-16 23:55:24', 1);

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
  `hardware_id` int NOT NULL,
  `timetable_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `hard_switch_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `timezone` varchar(100) DEFAULT 'Africa/Kigali',
  PRIMARY KEY (`id_users`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `hardware_id` (`hardware_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_users`, `email`, `role`, `passkey`, `status`, `hardware_id`, `timetable_enabled`, `hard_switch_enabled`, `timezone`) VALUES
(2, 'shimo@gmail.com', 'Admin', '$2y$10$Qtq0ZGUiY5P.btAxPY9hrO2meXYw9EEXqGQoNf4elcpgePR25rJya', 1, 1, 1, 0, 'Africa/Kigali'),
(28, 'sezeranoshimo@gmail.com', 'Super Admin', '$2y$10$F3XrBQb.B9UYRHlGxPSeiuTm0jFB5BZ3f3/JKdlGDlBIAqctXLD1a', 1, 0, 1, 0, 'America/Sao_Paulo');

--
-- Triggers `users`
--
DROP TRIGGER IF EXISTS `after_user_insert`;
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    INSERT INTO subscriptions (user_id, date_of_expiry, status)
    VALUES (
        NEW.id_users,
        NOW(),         -- current datetime
        0              -- status is false (expired by default)
    );
END
$$
DELIMITER ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hardware`
--
ALTER TABLE `hardware`
  ADD CONSTRAINT `hardware_user` FOREIGN KEY (`id`) REFERENCES `users` (`hardware_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `periods`
--
ALTER TABLE `periods`
  ADD CONSTRAINT `periods_ibfk_1` FOREIGN KEY (`owner`) REFERENCES `users` (`email`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id_users`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
