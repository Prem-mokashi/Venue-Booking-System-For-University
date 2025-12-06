-- VTU Venue Booking System Database Backup
-- Generated on: 2025-08-28 19:11:29
-- Database: venue_booking

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;


-- Table structure for table `admins`
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `admins`
INSERT INTO `admins` (`id`, `username`, `password`, `email`, `created_at`) VALUES
('1', 'admin', 'admin123', 'admin@vtu.edu', '2025-08-26 03:04:46');


-- Table structure for table `audit_logs`
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booking_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_uri` text COLLATE utf8mb4_unicode_ci,
  `response_code` int DEFAULT NULL,
  `execution_time` decimal(10,4) DEFAULT NULL,
  `memory_usage` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_booking_id` (`booking_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_composite` (`admin_id`,`created_at`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `audit_logs`


-- Table structure for table `bookings`
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `venue_id` int DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','approved','rejected') DEFAULT 'pending',
  `event_date` date DEFAULT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `event_details` text,
  `department` varchar(255) DEFAULT NULL,
  `admin_remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `venue_id` (`venue_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `bookings`
INSERT INTO `bookings` (`id`, `user_id`, `venue_id`, `booking_date`, `status`, `event_date`, `event_name`, `full_name`, `email`, `phone`, `start_time`, `end_time`, `event_details`, `department`, `admin_remarks`, `created_at`) VALUES
('2', '7', '2', NULL, 'approved', '2025-08-29', 'workshop', 'malik', 'malikar1902@gmail.com', '6362074996', '10:00:00', '16:00:00', 'workshop', 'mca', NULL, '2025-08-26 03:37:22'),
('4', '8', '1', NULL, 'approved', '2025-08-30', 'workshop', 'ramesh', 'rameshbudarkatti123@gmail.com', '8088268700', '10:00:00', '12:00:00', 'workshop', 'mca', 'accepted', '2025-08-26 13:01:44'),
('6', '8', '3', NULL, 'approved', '2025-08-28', 'interview', 'ramesh', 'rameshbudarkatti123@gmail.com', '8088268700', '08:00:00', '11:00:00', 'company', 'mca', 'accepted', '2025-08-26 14:54:00');


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `email` varchar(255) DEFAULT NULL,
  `vtu_id` varchar(20) DEFAULT NULL,
  `id_verified` tinyint(1) DEFAULT '0',
  `user_type` enum('student','staff') DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vtu_id` (`vtu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `vtu_id`, `id_verified`, `user_type`, `full_name`, `department`, `semester`, `created_at`, `phone`) VALUES
('1', 'admin', '$2y$10$8O6yxfPXQ6/ZdunnTuxZl.27/AFWewa3LcGx.XX4bjzTeFjOYUfd2', 'admin', NULL, NULL, '1', NULL, 'Admin', NULL, NULL, '2025-08-25 22:38:19', NULL),
('2', 'user1', '$2y$10$WaZTho9bH6jbBR.o4sIZwepJm37N/9Ju6fphmBrjkMIVRn31DEI1C', 'user', NULL, NULL, '1', NULL, 'User1', NULL, NULL, '2025-08-25 22:38:19', NULL),
('3', 'premsm12', '$2y$10$TBGE6wMVWeUSi5eAY0Turu7OjHkEeB5P28H0A6O6VBfbTqPoJLZ2K', 'user', NULL, NULL, '1', NULL, 'Premsm12', NULL, NULL, '2025-08-25 22:38:19', NULL),
('4', 'ojas', '$2y$10$CFL37QwV/l0gNDuLKX1Wz.bQ5dFheG9EpivINFvpB1MiG7BbNbzWK', 'user', NULL, NULL, '1', NULL, 'Ojas', NULL, NULL, '2025-08-25 22:38:19', NULL),
('7', 'malik', '$2y$10$uBWcAh/w8bHSFgVYBGTJIOnmqdSs2u0EBPFlYRrwJKr9lKong81fW', 'user', 'malikar1902@gmail.com', '2VX23MC078', '1', 'student', 'Malik', NULL, NULL, '2025-08-25 23:21:31', NULL),
('8', 'ramesh', '$2y$10$ClLpGzdZgnqNKQObGORwY.oU5pF5VaEnt2z6IJd4XzkdkWb16Bk2i', 'user', 'rameshbudarkatti123@gmail.com', '2VX23MC117', '1', 'student', 'Ramesh', NULL, NULL, '2025-08-26 13:00:53', NULL);


-- Table structure for table `venues`
DROP TABLE IF EXISTS `venues`;
CREATE TABLE `venues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `description` text,
  `is_available` tinyint(1) DEFAULT '1',
  `image` varchar(255) DEFAULT 'default_venue.jpg',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `venues`
INSERT INTO `venues` (`id`, `name`, `location`, `capacity`, `price`, `description`, `is_available`, `image`) VALUES
('1', 'Dr. A P J Abdul Kalam Auditorium', 'Main Building, Ground Floor', '500', '5000.00', 'Large auditorium with modern audio-visual equipment, perfect for conferences and seminars.', '1', 'auditorium.jpg'),
('2', 'Jnana Samvada 1', 'Administrative Block, 1st Floor', '150', '2000.00', 'Elegant hall suitable for meetings, workshops and small conferences.', '1', 'senatehall1.jpg'),
('3', 'Jnana Samvada 2', 'Administrative Block, 2nd Floor', '120', '1800.00', 'Comfortable meeting space with projector and sound system.', '1', 'senatehall2.jpg'),
('4', 'Amphitheater', 'Academic Block, Outdoor', '300', '3000.00', 'Open-air amphitheater ideal for cultural events and outdoor seminars.', '0', 'amphitheater.jpg'),
('5', 'Food Court', 'Student Center', '200', '1500.00', 'Spacious area suitable for informal gatherings and student events.', '1', 'foodcourt.jpg'),
('6', 'Conference Room', 'Library Building, 3rd Floor', '50', '1000.00', 'Professional conference room with video conferencing facilities.', '1', 'conferenceroom.jpg');

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

-- Backup completed on: 2025-08-28 19:11:29
