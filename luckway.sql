-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.38 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for lakway_delivery
CREATE DATABASE IF NOT EXISTS `lakway_delivery` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `lakway_delivery`;

-- Dumping structure for table lakway_delivery.activity_logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table lakway_delivery.activity_logs: ~0 rows (approximately)

-- Dumping structure for table lakway_delivery.delivery_earnings
CREATE TABLE IF NOT EXISTS `delivery_earnings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `delivery_person_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `earnings` decimal(10,2) DEFAULT '0.00',
  `earnings_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.delivery_earnings: ~0 rows (approximately)
INSERT INTO `delivery_earnings` (`id`, `delivery_person_id`, `order_id`, `earnings`, `earnings_date`, `created_at`) VALUES
	(1, 1, NULL, 280.00, '2025-11-13', '2025-11-13 08:43:07');

-- Dumping structure for table lakway_delivery.delivery_personnel
CREATE TABLE IF NOT EXISTS `delivery_personnel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nic_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` enum('bike','scooter','van','car','bicycle') COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT '0',
  `is_available` tinyint(1) DEFAULT '0',
  `rating` decimal(3,2) DEFAULT '5.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nic_number` (`nic_number`),
  KEY `idx_user` (`user_id`),
  KEY `idx_nic` (`nic_number`),
  CONSTRAINT `delivery_personnel_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table lakway_delivery.delivery_personnel: ~0 rows (approximately)

-- Dumping structure for table lakway_delivery.delivery_persons
CREATE TABLE IF NOT EXISTS `delivery_persons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `vehicle_type` enum('motorcycle','threewheel','car','van') NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `vehicle_image` varchar(255) NOT NULL,
  `licence_front` varchar(255) NOT NULL,
  `nic_front` varchar(255) NOT NULL,
  `nic_back` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.delivery_persons: ~0 rows (approximately)
INSERT INTO `delivery_persons` (`id`, `username`, `vehicle_type`, `vehicle_number`, `vehicle_image`, `licence_front`, `nic_front`, `nic_back`, `mobile`, `password`, `status`, `created_at`) VALUES
	(1, 'Sachith', 'motorcycle', '123456', '6914b2fc42b05.jpg', '6914b2fc42f5e.jpg', '6914b2fc43144.jpg', '6914b2fc43391.jpg', '0725876139', '$2y$10$yullFDVkrGax6EDa1Jlh7eRA8XDup5VyfydXP/GBe2aq7sJD0rRKS', 'approved', '2025-11-12 16:17:00');

-- Dumping structure for table lakway_delivery.delivery_stats
CREATE TABLE IF NOT EXISTS `delivery_stats` (
  `delivery_person_id` int NOT NULL,
  `total_deliveries` int DEFAULT '0',
  `completed_deliveries` int DEFAULT '0',
  `total_earnings` decimal(10,2) DEFAULT '0.00',
  `rating` decimal(3,2) DEFAULT '0.00',
  `last_delivery` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`delivery_person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.delivery_stats: ~0 rows (approximately)
INSERT INTO `delivery_stats` (`delivery_person_id`, `total_deliveries`, `completed_deliveries`, `total_earnings`, `rating`, `last_delivery`, `updated_at`) VALUES
	(1, 3, 3, 0.00, 0.00, '2025-11-13 07:32:17', '2025-11-13 07:32:17');

-- Dumping structure for table lakway_delivery.delivery_timeline
CREATE TABLE IF NOT EXISTS `delivery_timeline` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `delivery_person_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `delivery_person_id` (`delivery_person_id`),
  CONSTRAINT `delivery_timeline_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `delivery_timeline_ibfk_2` FOREIGN KEY (`delivery_person_id`) REFERENCES `delivery_persons` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.delivery_timeline: ~9 rows (approximately)
INSERT INTO `delivery_timeline` (`id`, `order_id`, `delivery_person_id`, `action`, `description`, `created_at`) VALUES
	(1, 2, 1, 'accepted', 'Delivery person accepted the order', '2025-11-13 07:08:18'),
	(2, 2, 1, 'out_for_delivery', 'Order is out for delivery', '2025-11-13 07:18:11'),
	(3, 1, 1, 'out_for_delivery', 'Order is out for delivery', '2025-11-13 07:18:26'),
	(4, 1, 1, 'delivered', 'Order delivered successfully', '2025-11-13 07:18:42'),
	(5, 2, 1, 'delivered', 'Order delivered successfully', '2025-11-13 07:18:54'),
	(6, 4, 1, 'delivered', 'Order delivered successfully', '2025-11-13 07:32:17'),
	(7, 6, 1, 'accepted', 'Delivery person accepted the order. Earnings: LKR 280.00', '2025-11-13 08:43:07'),
	(8, 6, 1, 'out_for_delivery', 'Order is out for delivery', '2025-11-13 08:44:57'),
	(9, 6, 1, 'delivered', 'Order delivered successfully. Total collected: LKR 3,656.00. Your earnings: LKR 280.00. Company earnings: LKR 120.00', '2025-11-13 08:45:09');

-- Dumping structure for table lakway_delivery.email_verifications
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `verification_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_code` (`user_id`,`verification_code`),
  CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table lakway_delivery.email_verifications: ~0 rows (approximately)

-- Dumping structure for table lakway_delivery.items
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_price` decimal(10,2) NOT NULL,
  `stock_count` int DEFAULT '0',
  `category` varchar(100) DEFAULT 'other',
  `description` text,
  `item_image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_category` (`category`),
  CONSTRAINT `items_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.items: ~2 rows (approximately)
INSERT INTO `items` (`id`, `store_id`, `item_name`, `item_price`, `stock_count`, `category`, `description`, `item_image`, `is_available`, `created_at`, `updated_at`) VALUES
	(3, 2, 'VEGETABLE SOUP', 1300.00, 10, 'food', 'Soup', 'uploads/items/item_2_1762940773_691457655a9df.webp', 1, '2025-11-12 09:46:13', '2025-11-12 09:46:13'),
	(4, 2, 'Bred', 120.00, 10, 'food', 'bred', 'uploads/items/item_2_1763009579_6915642b59c06.jpg', 1, '2025-11-13 04:52:44', '2025-11-13 04:52:59');

-- Dumping structure for table lakway_delivery.login_sessions
CREATE TABLE IF NOT EXISTS `login_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`session_token`),
  CONSTRAINT `login_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table lakway_delivery.login_sessions: ~0 rows (approximately)

-- Dumping structure for table lakway_delivery.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `store_id` int NOT NULL,
  `delivery_person_id` int DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_charge` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_address` text NOT NULL,
  `delivery_distance` decimal(8,2) NOT NULL,
  `user_lat` decimal(10,8) NOT NULL,
  `user_lng` decimal(11,8) NOT NULL,
  `status` enum('pending','accepted','declined','out_of_stock','ready_for_delivery','out_for_delivery','delivered') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `out_for_delivery_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.orders: ~6 rows (approximately)
INSERT INTO `orders` (`id`, `user_id`, `store_id`, `delivery_person_id`, `subtotal`, `delivery_charge`, `total_amount`, `delivery_address`, `delivery_distance`, `user_lat`, `user_lng`, `status`, `created_at`, `out_for_delivery_at`, `delivered_at`) VALUES
	(1, 2, 2, 1, 5720.00, 800.00, 6520.00, 'Police Station - Kuruwita, A4, Kuruwita, Sri Lanka', 9.00, 6.77883910, 80.36471200, 'delivered', '2025-11-12 14:13:31', '2025-11-13 07:18:26', '2025-11-13 07:18:42'),
	(2, 2, 2, 1, 5720.00, 320.00, 6040.00, 'Rathnapura Town, Sri Lanka', 3.34, 6.67630540, 80.40552630, 'delivered', '2025-11-12 16:29:59', '2025-11-13 07:18:11', '2025-11-13 07:18:54'),
	(3, 2, 2, 1, 5720.00, 720.00, 6440.00, 'Kuruwita, Sri Lanka', 8.75, 6.77701020, 80.36634260, 'delivered', '2025-11-13 04:21:28', NULL, NULL),
	(4, 2, 2, 1, 5720.00, 1200.00, 6920.00, 'Erathna, Sri Lanka', 14.97, 6.83500650, 80.40830880, 'delivered', '2025-11-13 04:49:41', NULL, '2025-11-13 07:32:17'),
	(5, 2, 2, NULL, 1826.00, 480.00, 2306.00, 'Hotel Grand Guardian, Ratnapura, Sri Lanka', 5.71, 6.74289240, 80.37940680, 'ready_for_delivery', '2025-11-13 07:35:00', NULL, NULL),
	(6, 2, 2, 1, 3256.00, 400.00, 3656.00, 'Sapthapadhi Hotel, Colombo - Batticaloa Highway, Ratnapura, Sri Lanka', 4.33, 6.73289900, 80.38361590, 'delivered', '2025-11-13 07:35:27', '2025-11-13 08:44:57', '2025-11-13 08:45:09');

-- Dumping structure for table lakway_delivery.order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.order_items: ~8 rows (approximately)
INSERT INTO `order_items` (`id`, `order_id`, `item_name`, `item_price`, `quantity`, `total_price`) VALUES
	(1, 1, 'VEGETABLE SOUP', 1430.00, 4, 5720.00),
	(2, 2, 'VEGETABLE SOUP', 1430.00, 4, 5720.00),
	(3, 3, 'VEGETABLE SOUP', 1430.00, 4, 5720.00),
	(4, 4, 'VEGETABLE SOUP', 1430.00, 4, 5720.00),
	(5, 5, 'Bred', 132.00, 3, 396.00),
	(6, 5, 'VEGETABLE SOUP', 1430.00, 1, 1430.00),
	(7, 6, 'Bred', 132.00, 3, 396.00),
	(8, 6, 'VEGETABLE SOUP', 1430.00, 2, 2860.00);

-- Dumping structure for table lakway_delivery.password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_email_code` (`email`,`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table lakway_delivery.password_resets: ~1 rows (approximately)

-- Dumping structure for table lakway_delivery.restaurants
CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `rating` float DEFAULT '4',
  `delivery_time` varchar(20) NOT NULL,
  `delivery_fee` varchar(20) NOT NULL,
  `discount` varchar(20) DEFAULT NULL,
  `profile_pic_url` varchar(255) NOT NULL,
  `colors` json NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.restaurants: ~0 rows (approximately)

-- Dumping structure for table lakway_delivery.stores
CREATE TABLE IF NOT EXISTS `stores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_name` varchar(255) NOT NULL,
  `store_type` enum('restaurant','cafe','bakery','grocery','pharmacy','convenience','other') NOT NULL,
  `br_number` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `country_code` varchar(5) DEFAULT '+94',
  `mobile_primary` varchar(15) NOT NULL,
  `mobile_secondary` varchar(15) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `store_image_path` varchar(500) DEFAULT NULL,
  `br_image_path` varchar(500) DEFAULT NULL,
  `food_certificate_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_date` timestamp NULL DEFAULT NULL,
  `admin_notes` text,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `opening_time` time NOT NULL,
  `closing_time` time NOT NULL,
  `open_24_7` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.stores: ~0 rows (approximately)
INSERT INTO `stores` (`id`, `store_name`, `store_type`, `br_number`, `email`, `country_code`, `mobile_primary`, `mobile_secondary`, `address`, `city`, `postal_code`, `latitude`, `longitude`, `password`, `store_image_path`, `br_image_path`, `food_certificate_path`, `status`, `registration_date`, `approved_date`, `admin_notes`, `last_login`, `is_active`, `opening_time`, `closing_time`, `open_24_7`) VALUES
	(2, 'Asiri Bakers', 'bakery', '123456', 'udarasachith41@gmail.com', '+94', '0725876139', '0741773588', 'P92Q+MVM, Ratnapura, Sri Lanka', 'Ratnapura', NULL, 6.70167420, 80.38935510, '$2y$10$vES/aBMpL9i2orBC4KyDA.WPVI29pwR/JhhGxGQSdcB4QA3XFou5i', 'F:\\xampp\\htdocs\\luckway/uploads/stores/store_1762934495_69143edfeab98.jpeg', 'F:\\xampp\\htdocs\\luckway/uploads/stores/br_1762934495_69143edfead38.jpeg', 'F:\\xampp\\htdocs\\luckway/uploads/stores/food_cert_1762934495_69143edfeae20.jpeg', 'approved', '2025-11-12 08:01:36', '2025-11-12 08:02:13', NULL, '2025-11-13 08:44:21', 1, '07:00:00', '22:00:00', 0);

-- Dumping structure for table lakway_delivery.store_requests
CREATE TABLE IF NOT EXISTS `store_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `profile_pic_url` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  `business_reg` varchar(50) DEFAULT NULL,
  `food_licence_url` varchar(255) DEFAULT NULL,
  `nic_front_url` varchar(255) DEFAULT NULL,
  `nic_back_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.store_requests: ~0 rows (approximately)

-- Dumping structure for table lakway_delivery.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `country_code` varchar(10) DEFAULT '+94',
  `password` varchar(255) NOT NULL,
  `user_type` enum('customer','store','delivery') DEFAULT 'customer',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lakway_delivery.users: ~0 rows (approximately)
INSERT INTO `users` (`id`, `email`, `mobile`, `country_code`, `password`, `user_type`, `is_verified`, `created_at`, `updated_at`) VALUES
	(1, 'udarasachith41@gmail.com', '725876139', '+94', '$2y$10$Ie.A3UGKIEhRthOYNpkBfOR40qlE9qi4zHs37o3Olqn9BoAgLsTKC', 'customer', 0, '2025-11-11 05:01:51', '2025-11-11 05:04:26'),
	(2, 'sachithgamage2310@gmail.com', '725876139', '+94', '$2y$10$t6t1Fm5IVhMgA48MuGXV1.ocL6AjwaNVeb1QT5Gl31t3k.8vhrbPG', 'store', 0, '2025-11-11 07:00:31', '2025-11-11 07:00:31');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
