-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: it_support
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `buildings`
--

DROP TABLE IF EXISTS `buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buildings`
--

LOCK TABLES `buildings` WRITE;
/*!40000 ALTER TABLE `buildings` DISABLE KEYS */;
INSERT INTO `buildings` VALUES (1,'อาคาร 1','2026-06-13 19:12:02'),(2,'อาคาร 2','2026-06-13 19:12:02'),(3,'อาคาร 4','2026-06-13 19:12:02'),(4,'อาคาร 5','2026-06-13 19:12:02'),(5,'อาคาร 6','2026-06-13 19:16:43');
/*!40000 ALTER TABLE `buildings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'IT Helpdesk','2026-06-13 14:09:15'),(2,'Account','2026-06-13 14:09:15'),(3,'HR','2026-06-13 14:09:15'),(4,'Manager','2026-06-13 14:09:15'),(5,'Marketing','2026-06-13 18:39:18'),(8,'Computer','2026-06-29 12:12:08');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_types`
--

DROP TABLE IF EXISTS `device_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `device_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device_types`
--

LOCK TABLES `device_types` WRITE;
/*!40000 ALTER TABLE `device_types` DISABLE KEYS */;
INSERT INTO `device_types` VALUES (1,'Computer','2026-06-13 19:12:02',1),(2,'Printer','2026-06-13 19:12:02',2),(3,'Network','2026-06-13 19:12:02',3),(5,'Mouse','2026-06-13 19:16:36',5),(6,'Other','2026-07-23 10:16:22',7),(7,'SSD','2026-07-23 11:26:46',6);
/*!40000 ALTER TABLE `device_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,5,2,'มี Ticket ใหม่เข้ามา (TK-000002)','หัวข้อ: Other: Acer','/it_support/manager/assign_tickets.php',0,'2026-07-23 10:19:11'),(2,2,2,'มี Ticket ใหม่เข้ามา (TK-000002)','หัวข้อ: Other: Acer','/it_support/admin/manage_tickets.php',1,'2026-07-23 10:19:11'),(3,3,2,'มี Ticket ใหม่เข้ามา (TK-000002)','หัวข้อ: Other: Acer','/it_support/admin/manage_tickets.php',0,'2026-07-23 10:19:11'),(4,4,2,'ช่างรับงานแล้ว (TK-000002)','ช่าง IT ได้กดรับ Ticket ของคุณและกำลังเริ่มดำเนินการ','/it_support/employee/view_ticket.php?id=2',1,'2026-07-23 10:20:33'),(5,5,2,'ช่างรับงานแล้ว (TK-000002)','ช่าง pond ได้กดรับ Ticket แล้ว','/it_support/manager/assign_tickets.php',0,'2026-07-23 10:20:33'),(6,2,2,'ช่างรับงานแล้ว (TK-000002)','ช่าง pond ได้กดรับ Ticket แล้ว','/it_support/admin/manage_tickets.php',1,'2026-07-23 10:20:33'),(7,3,2,'ช่างรับงานแล้ว (TK-000002)','ช่าง pond ได้กดรับ Ticket แล้ว','/it_support/admin/manage_tickets.php',0,'2026-07-23 10:20:33');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_updates`
--

DROP TABLE IF EXISTS `ticket_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `old_status` enum('open','in_progress','resolved','closed') DEFAULT NULL,
  `new_status` enum('open','in_progress','resolved','closed') DEFAULT NULL,
  `note` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `ticket_updates_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `ticket_updates_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_updates`
--

LOCK TABLES `ticket_updates` WRITE;
/*!40000 ALTER TABLE `ticket_updates` DISABLE KEYS */;
INSERT INTO `ticket_updates` VALUES (1,1,6,'open','in_progress','รับงานเข้าดำเนินการ',NULL,NULL,'2026-07-02 14:12:02'),(2,1,6,'in_progress','in_progress','กำลังซ่อม',NULL,NULL,'2026-07-02 14:12:18'),(3,1,6,'in_progress','resolved','เรียบร้อยละน้อง','update_6a4671ecc625b1.57256809.webp',NULL,'2026-07-02 14:13:00'),(4,1,6,'resolved','resolved','พนักงานห้ามนอน',NULL,NULL,'2026-07-21 11:20:58'),(5,1,2,'resolved','resolved','ไม่มีไร',NULL,NULL,'2026-07-21 11:32:11'),(6,1,2,'resolved','in_progress','ผู้ดูแลระบบอัปเดตข้อมูล Ticket',NULL,NULL,'2026-07-21 15:25:21'),(7,1,2,'in_progress','resolved','ไร',NULL,NULL,'2026-07-21 15:25:28'),(8,1,6,'resolved','resolved','x','update_6a5f92962b29b4.20402469.png',NULL,'2026-07-21 15:39:02'),(9,2,6,'open','in_progress','รับงานเข้าดำเนินการ',NULL,NULL,'2026-07-23 10:20:33');
/*!40000 ALTER TABLE `ticket_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category` enum('hardware','software','network','other') NOT NULL DEFAULT 'other',
  `device_type` varchar(100) DEFAULT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `building` varchar(100) DEFAULT NULL,
  `floor` varchar(20) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_no` (`ticket_no`),
  KEY `user_id` (`user_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES (1,'TK-000001',4,6,'Computer: Acer','โน้ตบุ้คพัง','uploads/tickets/ticket_1783001263_6a4670af338db.webp','other','Computer','Acer','Com 1','medium','อาคาร 4','3','8303','resolved','2026-07-02 14:07:43','2026-07-21 15:39:02'),(2,'TK-000002',4,6,'Other: Acer','คอมพัง','uploads/tickets/ticket_1784801951_6a61ea9fc76f5.jpg','hardware','Other','Acer','001','medium','อาคาร 4','3','8301','in_progress','2026-07-23 10:19:11','2026-07-23 10:20:33');
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `role` enum('employee','technician','admin','manager') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'admin','$2y$10$KQCG29mCHsAJcS8gRagz3uyqwfGeXtRpShl28hAyUdMi/uWLElxZ.','Thanachot','admin@company.com','0924378057','','admin','active','2026-07-02 09:39:07'),(3,'kritsada','$2y$10$k17MNMTi.JjS3wutuXgKIOxhhPHdYKk/UAvTeQoeHxczHC.ilOyTa','kritsada','pp@gmail.com','','','admin','active','2026-07-02 09:45:18'),(4,'emp1','$2y$10$.FURv2C.QxfD8EVfK.mJ8O8BSVWR99/cv8fw9WQUZYaugIoW69NGO','ติ้ก กินไม่หยุด','tik@gmail.com','','Account','employee','active','2026-07-02 14:05:40'),(5,'Manager1','$2y$10$zF8CqIKBPoYwgTBAxEE0ruUt09MlRUPoI/zO4HgE32Pvjald3Vit6','Bas','basthanachot07@gmail.com','','Manager','manager','active','2026-07-02 14:08:29'),(6,'tech01','$2y$10$dLxDFjPseaQiUOR/l3HJyeREaNmsoHHtRVKd8C8BIFWQiqLNkqF4m','pond','pplnwza@gmail.com','','IT Helpdesk','technician','active','2026-07-02 14:11:23'),(7,'emp2','$2y$10$oV1Ea60F4DvoImJPz0BrDeNOPbclNCe0uhO8270ev7vVvt0/EllTi','คริส มาก','','0824378058','Computer','employee','active','2026-07-25 16:00:28');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-03 21:55:45
