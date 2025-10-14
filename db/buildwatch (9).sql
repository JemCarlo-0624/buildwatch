-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2025 at 05:37 PM
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
(52, 'WORKER TESTING', 'ASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUG', 'completed', 40, 'medium', 25, 16, 34111.00, '1', NULL, NULL, 'residential', '2025-10-14 14:53:08', '2025-10-14 15:34:05', 0.00, 0.00);

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
(52, 52, 26, '2025-10-14 14:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `project_proposals`
--

CREATE TABLE `project_proposals` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_proposals`
--

INSERT INTO `project_proposals` (`id`, `client_id`, `title`, `description`, `status`, `submitted_at`) VALUES
(22, 16, 'TESTING CLIENT PROJECT', 'PROJECT TESTING', 'approved', '2025-10-13 16:01:00'),
(23, 16, 'TESTING PROJECT 2', 'TEST', 'approved', '2025-10-13 16:06:29'),
(24, 16, 'PROJECT TESTING', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec qu', 'approved', '2025-10-13 16:26:19'),
(25, 16, 'WORKER TESTING', 'ASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUGASSIGNING WORKER BUG', 'approved', '2025-10-14 14:52:51');

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
(25, 52, 'TEST', 'ASDSADA', 26, 100, '2025-12-21', '2025-10-14 14:58:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pm','worker') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(24, 'Admin', 'admin@buildwatch.com', '$2y$10$jt4KUuCrrZqhfebw/HkwKepC0JWbtj9/Er4RrTNUmFLy6lrFoIJQy', 'admin', '2025-09-26 16:02:01'),
(25, 'Project Manager', 'pm@buildwatch.com', '$2y$10$frBQSCh4txoV5yYxOmySUu/L.x.7W03SzxVXDIjVTzB56AFUFpeFC', 'pm', '2025-09-26 16:02:01'),
(26, 'Worker', 'worker@buildwatch.com', '$2y$10$Hi7.a0GByRzcIkT2/LRa..QHGOHwNPF0ejrhqpgOpoEAeoM/ZyFAO', 'worker', '2025-09-26 16:02:01'),
(29, 'bob', 'bob@worker.com', '$2y$10$1YdBNnx1e964wKoLpn0vWO.4ZANzcpAHF/vQzTEqCgbeJWJHEynW.', 'worker', '2025-09-27 06:56:32'),
(33, 'Test PM', 'pm1@buildwatch.com', '$2y$10$RrUdXWGP04k3FsSvO7wW7eKFlDkjAFzUf74s.96iv/1YiXB/IciKe', 'pm', '2025-10-12 12:42:35'),
(34, 'Test PM1', 'pm2@buildwatch.com', '$2y$10$/fFbcR3gyomN/lD9pMZvEuDBe2vDGetRQodSdJ386UXXyTGwiJdpK', 'pm', '2025-10-12 12:43:19'),
(35, 'francs', 'testpm123@gmail.com', '$2y$10$5RdLxmRh2bcCbGLZANkrPOJGtCqAzrdueHGv06/SpH2agV0lg94SK', 'worker', '2025-10-12 12:45:06'),
(36, 'YAMEL PM', 'pm3@gmail.com', '$2y$10$ZocYnaBxpUoLeqLeI.1QEOlicy1PMtAI46s365omX6QxQM8fap0gi', 'pm', '2025-10-13 11:04:41'),
(37, 'jem carlo', 'adminwithdesign@gmail.com', '$2y$10$VdxR9T0CsphDcP929s8k/uE27JzeaMamXGcSxEES74IIJsOzaB6vW', 'pm', '2025-10-13 11:27:20'),
(38, 'worker4', 'worker5@gmail.com', '$2y$10$NnP.GRdsNOuPX40ENsKi9uAlh0AsXtl8Hgu84XtolzqHaAAKWbrmC', 'worker', '2025-10-13 14:03:51'),
(39, 'worker6', 'worker6@buildwatch.com', '$2y$10$s9hSotgjTFKDMgVEfVzFgOH8KsTy5HWWWDkKQquFV3TptOq7HAaAG', 'worker', '2025-10-14 14:51:53');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `project_proposals`
--
ALTER TABLE `project_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `project_proposals`
--
ALTER TABLE `project_proposals`
  ADD CONSTRAINT `fk_proposals_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
