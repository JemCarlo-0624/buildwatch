-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 06:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `buildwatch`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `email`, `password`, `phone`, `company`, `created_at`) VALUES
(1, 'Jem Carlo Casana', 'jemcarlo@buildwatch.com', '$2y$10$ZB9exN0tnjqJl7vmRbOs4.OXEU/uxyyMPViZBRjiEZ9ZjskXlkSAi', '09123456789', 'Discaya', '2025-10-15 15:36:35'),
(2, 'Jan Julliene Narvasa', 'jjnarvasa@buildwatch.com', '$2y$10$Hco.MNQ.lY1ETPzK2jr8DeuinIw/rPJbaahBUwnQAUjRKLAtTUpDG', '', '', '2025-10-15 15:39:08');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('ongoing','completed','on-hold','planning') DEFAULT 'ongoing',
  `completion_percentage` int(11) DEFAULT 0,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_by` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `timeline` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_hours_spent` decimal(10,2) DEFAULT 0.00,
  `estimated_hours` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `status`, `completion_percentage`, `priority`, `created_by`, `client_id`, `budget`, `timeline`, `start_date`, `end_date`, `category`, `created_at`, `last_activity_at`, `total_hours_spent`, `estimated_hours`) VALUES
(53, '2nd Story Building', '2nd Story Building in University of Pangasinan Dagupan City', 'ongoing', 55, 'medium', 25, 2, 20000.00, '', '2025-10-31', '2026-10-31', 'residential', '2025-10-15 15:47:20', '2025-10-15 15:59:38', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `project_assignments`
--

CREATE TABLE `project_assignments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_assignments`
--

INSERT INTO `project_assignments` (`id`, `project_id`, `user_id`, `assigned_at`) VALUES
(28, 36, 34, '2025-10-12 13:51:57'),
(30, 38, 34, '2025-10-12 13:58:37'),
(31, 39, 25, '2025-10-12 14:02:01'),
(32, 40, 36, '2025-10-13 11:19:02'),
(33, 40, 35, '2025-10-13 11:19:17'),
(34, 41, 36, '2025-10-13 11:57:53'),
(35, 41, 26, '2025-10-13 11:59:13'),
(36, 41, 29, '2025-10-13 12:14:21'),
(37, 42, 25, '2025-10-13 12:38:11'),
(38, 43, 25, '2025-10-13 12:45:39'),
(39, 44, 25, '2025-10-13 12:46:41'),
(40, 45, 25, '2025-10-13 13:48:14'),
(41, 47, 25, '2025-10-13 14:03:07'),
(42, 47, 38, '2025-10-13 14:04:04'),
(43, 48, 25, '2025-10-13 15:48:50'),
(44, 48, 26, '2025-10-13 15:49:01'),
(45, 49, 25, '2025-10-13 16:02:21'),
(46, 49, 26, '2025-10-13 16:04:36'),
(47, 50, 25, '2025-10-13 16:06:47'),
(48, 50, 29, '2025-10-13 16:06:56'),
(49, 51, 25, '2025-10-13 16:26:52'),
(50, 51, 38, '2025-10-14 14:40:13'),
(51, 52, 25, '2025-10-14 14:53:08'),
(52, 52, 26, '2025-10-14 14:53:49'),
(53, 53, 25, '2025-10-15 15:47:20'),
(54, 53, 26, '2025-10-15 15:47:31');

-- --------------------------------------------------------

--
-- Table structure for table `project_proposals`
--

CREATE TABLE `project_proposals` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_proposals`
--

INSERT INTO `project_proposals` (`id`, `client_id`, `title`, `description`, `start_date`, `end_date`, `budget`, `status`, `submitted_at`) VALUES
(22, 16, 'TESTING CLIENT PROJECT', 'PROJECT TESTING', NULL, NULL, NULL, 'approved', '2025-10-13 16:01:00'),
(23, 16, 'TESTING PROJECT 2', 'TEST', NULL, NULL, NULL, 'approved', '2025-10-13 16:06:29'),
(24, 16, 'PROJECT TESTING', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec qu', NULL, NULL, NULL, 'approved', '2025-10-13 16:26:19'),
(25, 16, 'WORKER TESTING', 'ASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUG', NULL, NULL, NULL, 'approved', '2025-10-14 14:52:51'),
(26, 2, '2nd Story Building', '2nd Story Building in University of Pangasinan Dagupan City', '2025-10-31', '2026-10-31', NULL, 'approved', '2025-10-15 15:46:51');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `project_id`, `task_id`, `start_date`, `end_date`, `created_at`) VALUES
(1, 52, 25, '2025-12-21', '2025-12-22', '2025-10-14 15:01:57');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `title`, `description`, `assigned_to`, `progress`, `due_date`, `created_at`) VALUES
(18, 41, 'FOR BOB', 'ASDAD', 26, 100, '2025-12-21', '2025-10-13 11:59:40'),
(19, 44, 'teest', 'asdad', 26, 0, '2025-12-21', '2025-10-13 13:41:08'),
(20, 47, 'test1', 'asda', 38, 80, '2025-12-21', '2025-10-13 14:04:37'),
(21, 48, 'Test Task for the client', 'client testing', 26, 60, '2025-12-21', '2025-10-13 15:49:27'),
(22, 49, 'CLIENT PROJECT', '', 26, 55, '2025-12-21', '2025-10-13 16:04:51'),
(23, 50, 'TESTING', 'TEST', 29, 45, '2025-12-21', '2025-10-13 16:07:13'),
(24, 49, 'test', 'asadad', 26, 100, '2025-12-12', '2025-10-14 14:40:35'),
(25, 52, 'TEST', 'ASDSADA', 26, 100, '2025-12-21', '2025-10-14 14:58:10'),
(26, 53, 'Creating a Stairs', 'create the stairs', 26, 100, '2025-12-21', '2025-10-15 15:48:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pm','worker') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(24, 'Admin', 'admin@buildwatch.com', NULL, '$2y$10$jt4KUuCrrZqhfebw/HkwKepC0JWbtj9/Er4RrTNUmFLy6lrFoIJQy', 'admin', '2025-09-26 16:02:01'),
(25, 'Project Manager', 'pm@buildwatch.com', NULL, '$2y$10$frBQSCh4txoV5yYxOmySUu/L.x.7W03SzxVXDIjVTzB56AFUFpeFC', 'pm', '2025-09-26 16:02:01'),
(26, 'Worker', 'worker@buildwatch.com', NULL, '$2y$10$Hi7.a0GByRzcIkT2/LRa..QHGOHwNPF0ejrhqpgOpoEAeoM/ZyFAO', 'worker', '2025-09-26 16:02:01'),
(40, 'Yamel Worker', 'yamelworker@buildwatch.com', NULL, '$2y$10$k0ivqp4oKg7Xbx/6BurI8.5QAoX1WxXfjwB0RoIxL17sFBjs7ghAG', 'worker', '2025-10-15 15:41:45'),
(41, 'Yamel PM', 'yamelpm@buildwatch.com', NULL, '$2y$10$wCO3XBZ0blNOiCIBYynBSecSPvyWIhVdCrIQcGr6N42P9POiTe4aO', 'pm', '2025-10-15 15:42:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_user` (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `project_proposals`
--
ALTER TABLE `project_proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `project_proposals`
--
ALTER TABLE `project_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
