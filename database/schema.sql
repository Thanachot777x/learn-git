-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Aug 12, 2026 at 09:00 AM
-- Server version: 8.0.46
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `it_support`
--

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `name`, `created_at`) VALUES
(1, 'อาคาร 1', '2026-06-13 19:12:02'),
(2, 'อาคาร 2', '2026-06-13 19:12:02'),
(3, 'อาคาร 4', '2026-06-13 19:12:02'),
(4, 'อาคาร 5', '2026-06-13 19:12:02'),
(5, 'อาคาร 6', '2026-06-13 19:16:43'),
(6, 'อาคาร 7', '2026-08-10 09:06:02');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'IT ', '2026-06-13 14:09:15'),
(2, 'Account', '2026-06-13 14:09:15'),
(3, 'HR', '2026-06-13 14:09:15'),
(4, 'Manager', '2026-06-13 14:09:15'),
(5, 'Marketing', '2026-06-13 18:39:18'),
(8, 'Computer', '2026-06-29 12:12:08');

-- --------------------------------------------------------

--
-- Table structure for table `device_types`
--

CREATE TABLE `device_types` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_types`
--

INSERT INTO `device_types` (`id`, `name`, `created_at`, `sort_order`) VALUES
(1, 'Computer', '2026-06-13 19:12:02', 2),
(2, 'Printer', '2026-06-13 19:12:02', 1),
(3, 'Network', '2026-06-13 19:12:02', 3),
(5, 'Mouse', '2026-06-13 19:16:36', 5),
(6, 'Other', '2026-07-23 10:16:22', 7),
(7, 'SSD', '2026-07-23 11:26:46', 6);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `ticket_id` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `ticket_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 5, 2, 'มี Ticket ใหม่เข้ามา (TK-000002)', 'หัวข้อ: Other: Acer', '/it_support/manager/assign_tickets.php', 0, '2026-07-23 10:19:11'),
(2, 2, 2, 'มี Ticket ใหม่เข้ามา (TK-000002)', 'หัวข้อ: Other: Acer', '/it_support/admin/manage_tickets.php', 1, '2026-07-23 10:19:11'),
(3, 3, 2, 'มี Ticket ใหม่เข้ามา (TK-000002)', 'หัวข้อ: Other: Acer', '/it_support/admin/manage_tickets.php', 0, '2026-07-23 10:19:11'),
(4, 4, 2, 'ช่างรับงานแล้ว (TK-000002)', 'ช่าง IT ได้กดรับ Ticket ของคุณและกำลังเริ่มดำเนินการ', '/it_support/employee/view_ticket.php?id=2', 1, '2026-07-23 10:20:33'),
(5, 5, 2, 'ช่างรับงานแล้ว (TK-000002)', 'ช่าง pond ได้กดรับ Ticket แล้ว', '/it_support/manager/assign_tickets.php', 0, '2026-07-23 10:20:33'),
(6, 2, 2, 'ช่างรับงานแล้ว (TK-000002)', 'ช่าง pond ได้กดรับ Ticket แล้ว', '/it_support/admin/manage_tickets.php', 1, '2026-07-23 10:20:33'),
(7, 3, 2, 'ช่างรับงานแล้ว (TK-000002)', 'ช่าง pond ได้กดรับ Ticket แล้ว', '/it_support/admin/manage_tickets.php', 0, '2026-07-23 10:20:33'),
(8, 5, 3, 'มี Ticket ใหม่เข้ามา (TK-000003)', 'หัวข้อ: SSD: 2', '/it_support/manager/assign_tickets.php', 0, '2026-08-04 09:12:20'),
(9, 2, 3, 'มี Ticket ใหม่เข้ามา (TK-000003)', 'หัวข้อ: SSD: 2', '/it_support/admin/manage_tickets.php', 0, '2026-08-04 09:12:20'),
(10, 3, 3, 'มี Ticket ใหม่เข้ามา (TK-000003)', 'หัวข้อ: SSD: 2', '/it_support/admin/manage_tickets.php', 0, '2026-08-04 09:12:20'),
(11, 5, 4, 'มี Ticket ใหม่เข้ามา (TK-000004)', 'หัวข้อ: Network: 1231', '/manager/assign_tickets.php', 0, '2026-08-09 17:03:53'),
(12, 2, 4, 'มี Ticket ใหม่เข้ามา (TK-000004)', 'หัวข้อ: Network: 1231', '/admin/manage_tickets.php', 1, '2026-08-09 17:03:53'),
(13, 3, 4, 'มี Ticket ใหม่เข้ามา (TK-000004)', 'หัวข้อ: Network: 1231', '/admin/manage_tickets.php', 0, '2026-08-09 17:03:53'),
(14, 5, 5, 'มี Ticket ใหม่เข้ามา (TK-000005)', 'หัวข้อ: แจ้งปัญหาซ่อม: Acer', '/manager/assign_tickets.php', 0, '2026-08-12 04:35:10'),
(15, 2, 5, 'มี Ticket ใหม่เข้ามา (TK-000005)', 'หัวข้อ: แจ้งปัญหาซ่อม: Acer', '/admin/manage_tickets.php', 1, '2026-08-12 04:35:10'),
(16, 3, 5, 'มี Ticket ใหม่เข้ามา (TK-000005)', 'หัวข้อ: แจ้งปัญหาซ่อม: Acer', '/admin/manage_tickets.php', 0, '2026-08-12 04:35:10'),
(17, 5, 6, 'มี Ticket ใหม่เข้ามา (TK-000006)', 'หัวข้อ: Computer: Hp2030', '/manager/assign_tickets.php', 0, '2026-08-12 05:06:58'),
(18, 2, 6, 'มี Ticket ใหม่เข้ามา (TK-000006)', 'หัวข้อ: Computer: Hp2030', '/admin/manage_tickets.php', 0, '2026-08-12 05:06:58'),
(19, 3, 6, 'มี Ticket ใหม่เข้ามา (TK-000006)', 'หัวข้อ: Computer: Hp2030', '/admin/manage_tickets.php', 0, '2026-08-12 05:06:58'),
(20, 8, 4, 'คุณได้รับการมอบหมายงานใหม่ (TK-000004)', 'หัวข้อ: Network: 1231', '/technician/update_ticket.php?id=4', 0, '2026-08-12 08:41:21'),
(21, 4, 4, 'อัปเดต Ticket (TK-000004)', 'ผู้จัดการได้มอบหมายช่างผู้รับผิดชอบดูแลงานซ่อมของคุณแล้ว', '/employee/view_ticket.php?id=4', 0, '2026-08-12 08:41:21'),
(22, 8, 6, 'คุณได้รับการมอบหมายงานใหม่ (TK-000006)', 'หัวข้อ: Computer: Hp2030', '/technician/update_ticket.php?id=6', 0, '2026-08-12 08:41:24'),
(23, 4, 6, 'อัปเดต Ticket (TK-000006)', 'ผู้จัดการได้มอบหมายช่างผู้รับผิดชอบดูแลงานซ่อมของคุณแล้ว', '/employee/view_ticket.php?id=6', 0, '2026-08-12 08:41:24'),
(24, 4, 5, 'ช่างรับงานแล้ว (TK-000005)', 'ช่าง IT ได้กดรับ Ticket ของคุณและกำลังเริ่มดำเนินการ', '/employee/view_ticket.php?id=5', 0, '2026-08-12 08:41:29'),
(25, 5, 5, 'ช่างรับงานแล้ว (TK-000005)', 'ช่าง ธนโชติ จันทร์กระจ่าง ได้กดรับ Ticket แล้ว', '/manager/assign_tickets.php', 0, '2026-08-12 08:41:29'),
(26, 2, 5, 'ช่างรับงานแล้ว (TK-000005)', 'ช่าง ธนโชติ จันทร์กระจ่าง ได้กดรับ Ticket แล้ว', '/admin/manage_tickets.php', 0, '2026-08-12 08:41:29'),
(27, 3, 5, 'ช่างรับงานแล้ว (TK-000005)', 'ช่าง ธนโชติ จันทร์กระจ่าง ได้กดรับ Ticket แล้ว', '/admin/manage_tickets.php', 0, '2026-08-12 08:41:29'),
(28, 4, 4, 'อัปเดต Ticket (TK-000004)', 'สถานะ: กำลังแก้ไข | รายละเอียด: เรียบร้อย', '/employee/view_ticket.php?id=4', 0, '2026-08-12 08:43:40'),
(29, 5, 4, 'ช่างอัปเดต Ticket (TK-000004)', 'สถานะเปลี่ยนเป็น: กำลังแก้ไข', '/manager/assign_tickets.php', 0, '2026-08-12 08:43:40'),
(30, 2, 4, 'ช่างอัปเดต Ticket (TK-000004)', 'สถานะเปลี่ยนเป็น: กำลังแก้ไข', '/admin/manage_tickets.php', 0, '2026-08-12 08:43:40'),
(31, 3, 4, 'ช่างอัปเดต Ticket (TK-000004)', 'สถานะเปลี่ยนเป็น: กำลังแก้ไข', '/admin/manage_tickets.php', 0, '2026-08-12 08:43:40'),
(32, 4, 4, 'อัปเดต Ticket (TK-000004)', 'สถานะ: แก้ไขแล้ว | รายละเอียด: ลองดู', '/employee/view_ticket.php?id=4', 0, '2026-08-12 08:46:12'),
(33, 5, 4, 'ช่างอัปเดต Ticket (TK-000004)', 'สถานะเปลี่ยนเป็น: แก้ไขแล้ว', '/manager/assign_tickets.php', 0, '2026-08-12 08:46:12'),
(34, 2, 4, 'ช่างอัปเดต Ticket (TK-000004)', 'สถานะเปลี่ยนเป็น: แก้ไขแล้ว', '/admin/manage_tickets.php', 0, '2026-08-12 08:46:12'),
(35, 3, 4, 'ช่างอัปเดต Ticket (TK-000004)', 'สถานะเปลี่ยนเป็น: แก้ไขแล้ว', '/admin/manage_tickets.php', 0, '2026-08-12 08:46:12');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int NOT NULL,
  `ticket_no` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `assigned_to` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` enum('hardware','software','network','other') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'other',
  `device_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `device_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serial_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'medium',
  `building` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `floor` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `room` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_no`, `user_id`, `assigned_to`, `title`, `description`, `image_path`, `category`, `device_type`, `device_name`, `serial_no`, `priority`, `building`, `floor`, `room`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TK-000001', 4, 6, 'Computer: Acer', 'โน้ตบุ้คพัง', 'uploads/tickets/ticket_1783001263_6a4670af338db.webp', 'other', 'Computer', 'Acer', 'Com 1', 'medium', 'อาคาร 4', '3', '8303', 'resolved', '2026-07-02 14:07:43', '2026-07-21 15:39:02'),
(2, 'TK-000002', 4, 6, 'Other: Acer', 'คอมพัง', 'uploads/tickets/ticket_1784801951_6a61ea9fc76f5.jpg', 'hardware', 'Other', 'Acer', '001', 'medium', 'อาคาร 4', '3', '8301', 'in_progress', '2026-07-23 10:19:11', '2026-07-23 10:20:33'),
(3, 'TK-000003', 4, NULL, 'SSD: 2', 'Com2', NULL, 'hardware', 'SSD', '2', '001', 'high', 'อาคาร 5', '2', '1', 'open', '2026-08-04 09:12:20', '2026-08-04 09:12:20'),
(4, 'TK-000004', 4, 8, 'Network: 1231', 'rankไม่ขึ้น', NULL, 'other', 'Network', '1231', '1', 'urgent', 'อาคาร 6', '1231', '123', 'resolved', '2026-08-09 17:03:53', '2026-08-12 08:46:12'),
(5, 'TK-000005', 4, 8, 'แจ้งปัญหาซ่อม: Acer', 'หิวข้าว', NULL, 'hardware', 'อุปกรณ์ทั่วไป', 'Acer', '3', 'high', 'อาคาร 6', '2', '32', 'in_progress', '2026-08-12 04:35:10', '2026-08-12 08:41:29'),
(6, 'TK-000006', 4, 8, 'Computer: Hp2030', 'คอมพัง', NULL, 'hardware', 'Computer', 'Hp2030', '3', 'high', 'อาคาร 5', '2', '2', 'in_progress', '2026-08-12 05:06:58', '2026-08-12 08:41:24');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_updates`
--

CREATE TABLE `ticket_updates` (
  `id` int NOT NULL,
  `ticket_id` int NOT NULL,
  `updated_by` int NOT NULL,
  `old_status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `new_status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_general_ci,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_updates`
--

INSERT INTO `ticket_updates` (`id`, `ticket_id`, `updated_by`, `old_status`, `new_status`, `note`, `image`, `image_path`, `created_at`) VALUES
(1, 1, 6, 'open', 'in_progress', 'รับงานเข้าดำเนินการ', NULL, NULL, '2026-07-02 14:12:02'),
(2, 1, 6, 'in_progress', 'in_progress', 'กำลังซ่อม', NULL, NULL, '2026-07-02 14:12:18'),
(3, 1, 6, 'in_progress', 'resolved', 'เรียบร้อยละน้อง', 'update_6a4671ecc625b1.57256809.webp', NULL, '2026-07-02 14:13:00'),
(4, 1, 6, 'resolved', 'resolved', 'พนักงานห้ามนอน', NULL, NULL, '2026-07-21 11:20:58'),
(5, 1, 2, 'resolved', 'resolved', 'ไม่มีไร', NULL, NULL, '2026-07-21 11:32:11'),
(6, 1, 2, 'resolved', 'in_progress', 'ผู้ดูแลระบบอัปเดตข้อมูล Ticket', NULL, NULL, '2026-07-21 15:25:21'),
(7, 1, 2, 'in_progress', 'resolved', 'ไร', NULL, NULL, '2026-07-21 15:25:28'),
(8, 1, 6, 'resolved', 'resolved', 'x', 'update_6a5f92962b29b4.20402469.png', NULL, '2026-07-21 15:39:02'),
(9, 2, 6, 'open', 'in_progress', 'รับงานเข้าดำเนินการ', NULL, NULL, '2026-07-23 10:20:33'),
(10, 4, 5, 'open', 'in_progress', 'มอบหมายงานโดย Manager', NULL, NULL, '2026-08-12 08:41:21'),
(11, 6, 5, 'open', 'in_progress', 'มอบหมายงานโดย Manager', NULL, NULL, '2026-08-12 08:41:24'),
(12, 5, 8, 'open', 'in_progress', 'รับงานเข้าดำเนินการ', NULL, NULL, '2026-08-12 08:41:29'),
(13, 4, 8, 'in_progress', 'in_progress', 'เรียบร้อย', 'update_6a7c323c987d47.40236347.png', NULL, '2026-08-12 08:43:40'),
(14, 4, 8, 'in_progress', 'resolved', 'ลองดู', 'update_6a7c32d4cfafa0.45510872.png', NULL, '2026-08-12 08:46:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `fullname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('employee','technician','admin','manager') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `department`, `role`, `status`, `created_at`) VALUES
(2, 'admin', '$2y$10$KQCG29mCHsAJcS8gRagz3uyqwfGeXtRpShl28hAyUdMi/uWLElxZ.', 'Thanachot', 'admin@company.com', '0924378057', '', 'admin', 'active', '2026-07-02 09:39:07'),
(3, 'kritsada', '$2y$10$k17MNMTi.JjS3wutuXgKIOxhhPHdYKk/UAvTeQoeHxczHC.ilOyTa', 'kritsada', 'pp@gmail.com', '', '', 'admin', 'active', '2026-07-02 09:45:18'),
(4, 'emp1', '$2y$10$.FURv2C.QxfD8EVfK.mJ8O8BSVWR99/cv8fw9WQUZYaugIoW69NGO', 'ติ้ก กินไม่หยุด', 'tik@gmail.com', '', 'Account', 'employee', 'active', '2026-07-02 14:05:40'),
(5, 'Manager1', '$2y$10$zF8CqIKBPoYwgTBAxEE0ruUt09MlRUPoI/zO4HgE32Pvjald3Vit6', 'Bas', 'basthanachot07@gmail.com', '', 'Manager', 'manager', 'active', '2026-07-02 14:08:29'),
(6, 'tech01', '$2y$10$dLxDFjPseaQiUOR/l3HJyeREaNmsoHHtRVKd8C8BIFWQiqLNkqF4m', 'pond', 'pplnwza@gmail.com', '', 'IT', 'technician', 'active', '2026-07-02 14:11:23'),
(7, 'emp2', '$2y$10$oV1Ea60F4DvoImJPz0BrDeNOPbclNCe0uhO8270ev7vVvt0/EllTi', 'คริส มาก', '', '0824378058', 'Computer', 'employee', 'active', '2026-07-25 16:00:28'),
(8, 'tech02', '$2y$10$XWbCpGvGKGC2tBFkrerPU.oJZ5XiYPnA5peGh7stsHZD2qRAYiuEi', 'ธนโชติ จันทร์กระจ่าง', 'dekthanachot07@gmail.com', '', 'IT', 'technician', 'active', '2026-08-12 08:41:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `device_types`
--
ALTER TABLE `device_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_no` (`ticket_no`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `ticket_updates`
--
ALTER TABLE `ticket_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `device_types`
--
ALTER TABLE `device_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ticket_updates`
--
ALTER TABLE `ticket_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `ticket_updates`
--
ALTER TABLE `ticket_updates`
  ADD CONSTRAINT `ticket_updates_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  ADD CONSTRAINT `ticket_updates_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
