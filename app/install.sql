-- DRK Inventar - Production Installation
-- Generated: 2026-03-24
-- Database structure + 1 Admin user

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `compartment_products_actual`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compartment_products_actual` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compartment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `expiry_date` date NOT NULL,
  `quantity` int(11) DEFAULT 1 COMMENT 'Immer 1 - jede Zeile = eine Produktinstanz',
  `status` enum('ok','missing','expired','damaged','expiring_soon') DEFAULT 'ok',
  `last_checked` timestamp NULL DEFAULT NULL,
  `last_checked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `instance_id` varchar(50) DEFAULT NULL COMMENT 'Eindeutige Instanz-ID (optional)',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `last_checked_by` (`last_checked_by`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_compartment_product_expiry` (`compartment_id`,`product_id`,`expiry_date`),
  CONSTRAINT `compartment_products_actual_ibfk_1` FOREIGN KEY (`compartment_id`) REFERENCES `compartments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compartment_products_actual_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compartment_products_actual_ibfk_3` FOREIGN KEY (`last_checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compartment_products_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compartment_products_target` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compartment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `notes` mediumtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_compartment_product` (`compartment_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `compartment_products_target_ibfk_1` FOREIGN KEY (`compartment_id`) REFERENCES `compartments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compartment_products_target_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2527 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compartments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compartments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `container_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color_code` varchar(7) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_compartment_sort` (`container_id`,`sort_order`),
  CONSTRAINT `compartments_ibfk_1` FOREIGN KEY (`container_id`) REFERENCES `containers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=439 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `container_inspection_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `container_inspection_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `container_inspection_item_id` int(10) unsigned NOT NULL,
  `compartment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `expected_quantity` int(11) NOT NULL DEFAULT 0,
  `actual_quantity` int(11) DEFAULT NULL,
  `expiry_date_before` date DEFAULT NULL,
  `expiry_date_after` date DEFAULT NULL,
  `status_before` varchar(50) DEFAULT NULL,
  `status_after` varchar(50) DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `compartment_id` (`compartment_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_item` (`container_inspection_item_id`),
  CONSTRAINT `container_inspection_details_ibfk_1` FOREIGN KEY (`container_inspection_item_id`) REFERENCES `container_inspection_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `container_inspection_details_ibfk_2` FOREIGN KEY (`compartment_id`) REFERENCES `compartments` (`id`),
  CONSTRAINT `container_inspection_details_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `container_inspection_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `container_inspection_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `container_inspection_id` int(10) unsigned NOT NULL,
  `container_id` int(11) NOT NULL,
  `inspected_by` int(11) DEFAULT NULL,
  `inspector_name` varchar(100) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `container_id` (`container_id`),
  KEY `inspected_by` (`inspected_by`),
  KEY `idx_session_status` (`container_inspection_id`,`status`),
  CONSTRAINT `container_inspection_items_ibfk_1` FOREIGN KEY (`container_inspection_id`) REFERENCES `container_inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `container_inspection_items_ibfk_2` FOREIGN KEY (`container_id`) REFERENCES `containers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `container_inspection_items_ibfk_3` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `container_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `container_inspections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `started_by` int(11) NOT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `status` enum('in_progress','completed','cancelled') NOT NULL DEFAULT 'in_progress',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `started_by` (`started_by`),
  KEY `idx_vehicle_status` (`vehicle_id`,`status`),
  CONSTRAINT `container_inspections_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `container_inspections_ibfk_2` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `containers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `containers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('schrank','rucksack','koffer','kiste') NOT NULL,
  `color_code` varchar(7) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_container_sort` (`vehicle_id`,`sort_order`),
  CONSTRAINT `containers_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspection_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_id` int(11) NOT NULL,
  `compartment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `expected_quantity` int(11) NOT NULL DEFAULT 0,
  `actual_quantity` int(11) DEFAULT NULL,
  `expiry_date_before` date DEFAULT NULL,
  `expiry_date_after` date DEFAULT NULL,
  `status_before` varchar(50) DEFAULT NULL,
  `status_after` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('in_progress','completed','cancelled') DEFAULT 'in_progress',
  `notes` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_vehicle_inspections` (`vehicle_id`,`completed_at`),
  CONSTRAINT `inspections_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspections_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_instances` (
  `id` int(11) NOT NULL,
  `inspection_item_id` int(11) NOT NULL,
  `instance_number` smallint(6) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `missing` tinyint(1) DEFAULT 0,
  `checked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `has_expiry` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=681 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','user','fahrzeugwart','kontrolle') NOT NULL DEFAULT 'user',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v_expiring_products`;
/*!50001 DROP VIEW IF EXISTS `v_expiring_products`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_expiring_products` AS SELECT
 1 AS `product_id`,
  1 AS `product_name`,
  1 AS `expiry_date`,
  1 AS `quantity`,
  1 AS `compartment_name`,
  1 AS `container_name`,
  1 AS `vehicle_name`,
  1 AS `vehicle_id`,
  1 AS `days_until_expiry` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_last_inspections`;
/*!50001 DROP VIEW IF EXISTS `v_last_inspections`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_last_inspections` AS SELECT
 1 AS `vehicle_id`,
  1 AS `vehicle_name`,
  1 AS `completed_at`,
  1 AS `inspector_name` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('RTW','KTW','GW-SAN','LAGER') NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP VIEW IF EXISTS `v_expiring_products`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`drk_user`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_expiring_products` AS select `p`.`id` AS `product_id`,`p`.`name` AS `product_name`,`cpa`.`expiry_date` AS `expiry_date`,`cpa`.`quantity` AS `quantity`,`c`.`name` AS `compartment_name`,`cont`.`name` AS `container_name`,`v`.`name` AS `vehicle_name`,`v`.`id` AS `vehicle_id`,to_days(`cpa`.`expiry_date`) - to_days(curdate()) AS `days_until_expiry` from ((((`compartment_products_actual` `cpa` join `products` `p` on(`cpa`.`product_id` = `p`.`id`)) join `compartments` `c` on(`cpa`.`compartment_id` = `c`.`id`)) join `containers` `cont` on(`c`.`container_id` = `cont`.`id`)) join `vehicles` `v` on(`cont`.`vehicle_id` = `v`.`id`)) where `cpa`.`expiry_date` is not null and `cpa`.`expiry_date` <= curdate() + interval 90 day and `cpa`.`quantity` > 0 order by `cpa`.`expiry_date` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_last_inspections`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`drk_user`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_last_inspections` AS select 1 AS `vehicle_id`,1 AS `vehicle_name`,1 AS `completed_at`,1 AS `inspector_name` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;


-- Insert Admin User (admin / admin123)
INSERT INTO `users` (`username`, `password_hash`, `email`, `role`, `created_at`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin', NOW());

SET FOREIGN_KEY_CHECKS = 1;
