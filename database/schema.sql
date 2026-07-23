-- IT Support Helpdesk Database Schema

CREATE DATABASE IF NOT EXISTS `it_support` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `it_support`;

CREATE TABLE IF NOT EXISTS `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `buildings` (`id`, `name`, `created_at`) VALUES
(1, 'อาคาร 1', '2026-06-13 19:12:02'),
(2, 'อาคาร 2', '2026-06-13 19:12:02'),
(3, 'อาคาร 4', '2026-06-13 19:12:02'),
(4, 'อาคาร 5', '2026-06-13 19:12:02'),
(5, 'อาคาร 6', '2026-06-13 19:16:43');

CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'IT Helpdesk', '2026-06-13 14:09:15'),
(2, 'Account', '2026-06-13 14:09:15'),
(3, 'HR', '2026-06-13 14:09:15'),
(4, 'Manager', '2026-06-13 14:09:15'),
(5, 'Marketing', '2026-06-13 18:39:18'),
(8, 'Computer', '2026-06-29 12:12:08');

CREATE TABLE IF NOT EXISTS `device_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `device_types` (`id`, `name`, `created_at`) VALUES
(1, 'Computer', '2026-06-13 19:12:02'),
(2, 'Printer', '2026-06-13 19:12:02'),
(3, 'Network', '2026-06-13 19:12:02'),
(4, 'Other', '2026-06-13 19:12:02'),
(5, 'Mouse', '2026-06-13 19:16:36');

CREATE TABLE IF NOT EXISTS `users` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `department`, `role`, `status`, `created_at`) VALUES
(2, 'admin', '$2y$10$KQCG29mCHsAJcS8gRagz3uyqwfGeXtRpShl28hAyUdMi/uWLElxZ.', 'Thanachot', 'admin@company.com', '0924378057', '', 'admin', 'active', '2026-07-02 09:39:07'),
(3, 'kritsada', '$2y$10$k17MNMTi.JjS3wutuXgKIOxhhPHdYKk/UAvTeQoeHxczHC.ilOyTa', 'kritsada', 'pp@gmail.com', '', '', 'admin', 'active', '2026-07-02 09:45:18'),
(4, 'emp1', '$2y$10$.FURv2C.QxfD8EVfK.mJ8O8BSVWR99/cv8fw9WQUZYaugIoW69NGO', 'ติ้ก กินไม่หยุด', 'tik@gmail.com', '', 'Marketing', 'employee', 'active', '2026-07-02 14:05:40'),
(5, 'Manager1', '$2y$10$zF8CqIKBPoYwgTBAxEE0ruUt09MlRUPoI/zO4HgE32Pvjald3Vit6', 'Bas', 'basthanachot07@gmail.com', '', '', 'manager', 'active', '2026-07-02 14:08:29'),
(6, 'tech01', '$2y$10$dLxDFjPseaQiUOR/l3HJyeREaNmsoHHtRVKd8C8BIFWQiqLNkqF4m', 'pond', 'pplnwza@gmail.com', '', 'IT Helpdesk', 'technician', 'active', '2026-07-02 14:11:23');

CREATE TABLE IF NOT EXISTS `tickets` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tickets` (`id`, `ticket_no`, `user_id`, `assigned_to`, `title`, `description`, `image_path`, `category`, `device_type`, `device_name`, `serial_no`, `priority`, `building`, `floor`, `room`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TK-000001', 4, 6, 'Computer: Acer', 'โน้ตบุ้คพัง', 'uploads/tickets/ticket_1783001263_6a4670af338db.webp', 'other', 'Computer', 'Acer', 'Com 1', 'medium', 'อาคาร 4', '3', '8303', 'resolved', '2026-07-02 14:07:43', '2026-07-02 14:13:00');

CREATE TABLE IF NOT EXISTS `ticket_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `old_status` enum('open','in_progress','resolved','closed') DEFAULT NULL,
  `new_status` enum('open','in_progress','resolved','closed') DEFAULT NULL,
  `note` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `ticket_updates_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `ticket_updates_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ticket_updates` (`id`, `ticket_id`, `updated_by`, `old_status`, `new_status`, `note`, `image`, `created_at`) VALUES
(1, 1, 6, 'open', 'in_progress', 'รับงานเข้าดำเนินการ', NULL, '2026-07-02 14:12:02'),
(2, 1, 6, 'in_progress', 'in_progress', 'กำลังซ่อม', NULL, '2026-07-02 14:12:18'),
(3, 1, 6, 'in_progress', 'resolved', 'เรียบร้อยละน้อง', 'update_6a4671ecc625b1.57256809.webp', '2026-07-02 14:13:00');

CREATE TABLE IF NOT EXISTS `notifications` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

