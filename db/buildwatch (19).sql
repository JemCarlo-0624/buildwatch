-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2025 at 09:51 AM
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
(1, 'Jem Carlo Casana', 'jemcarlo@buildwatch.com', '$2y$10$W6Tsc185hkW6ljj.6zX05OZbF5.VWoaWT6x52tQG41W5IgHZrkIUa', '09123456789', 'Discaya', '2025-10-15 15:36:35'),
(2, 'Jan Julliene Narvasa', 'jjnarvasa@buildwatch.com', '$2y$10$W6Tsc185hkW6ljj.6zX05OZbF5.VWoaWT6x52tQG41W5IgHZrkIUa', '', '', '2025-10-15 15:39:08'),
(10, 'Greenfield Construction Ltd.', 'procurement@greenfield.co', '$2y$10$M8QRz.TAUBO3fu6Pz7hwx.YBan4.t0AgMFYudb5MhCtoZNKeIAAOS', '+632-8844-1000', 'Greenfield Ltd.', '2025-10-17 16:32:33'),
(11, 'Seabreeze Residences', 'admin@seabreeze.ph', '$2y$10$M8QRz.TAUBO3fu6Pz7hwx.YBan4.t0AgMFYudb5MhCtoZNKeIAAOS', '+632-8844-2000', 'Seabreeze Inc.', '2025-10-17 16:32:33'),
(12, 'Metro Retail Group', 'projects@metroretail.com', '$2y$10$M8QRz.TAUBO3fu6Pz7hwx.YBan4.t0AgMFYudb5MhCtoZNKeIAAOS', '+632-8844-3000', 'Metro Retail', '2025-10-17 16:32:33'),
(13, 'AgriTech Farms Inc.', 'ops@agritech.ph', '$2y$10$M8QRz.TAUBO3fu6Pz7hwx.YBan4.t0AgMFYudb5MhCtoZNKeIAAOS', '+632-8844-4000', 'AgriTech', '2025-10-17 16:32:33'),
(14, 'Lighthouse School Foundation', 'admin@lighthouse.edu.ph', '$2y$10$M8QRz.TAUBO3fu6Pz7hwx.YBan4.t0AgMFYudb5MhCtoZNKeIAAOS', '+632-8844-5000', 'Lighthouse School', '2025-10-17 16:32:33');

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

INSERT INTO `projects` (`id`, `name`, `description`, `status`, `completion_percentage`, `priority`, `created_by`, `client_id`, `timeline`, `start_date`, `end_date`, `category`, `created_at`, `last_activity_at`, `total_hours_spent`, `estimated_hours`) VALUES
(1, 'Commercial Building for Discaya', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', 'completed', 100, 'medium', 25, 2, '', '2025-10-20', '2025-10-31', 'commercial', '2025-10-19 05:51:39', '2025-10-19 06:31:27', 0.00, 0.00),
(2, 'Renovation of A Bridge in Dagupan', 'renovating a bridge in dagupan need for workers need this asap', 'completed', 100, 'medium', 25, 2, NULL, '2025-10-20', '2025-10-30', NULL, '2025-10-19 06:50:32', '2025-10-19 07:21:55', 0.00, 0.00),
(3, 'Discaya Building 2', 'build the discaya home in paranaque and pasig make it asap', 'completed', 100, 'medium', 25, 2, NULL, '2025-10-20', '2025-10-31', NULL, '2025-10-19 07:34:23', '2025-10-19 07:43:37', 0.00, 0.00);

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
(54, 53, 26, '2025-10-15 15:47:31'),
(65, 1, 2, '2025-10-17 16:32:33'),
(66, 1, 4, '2025-10-17 16:32:33'),
(67, 2, 3, '2025-10-17 16:32:33'),
(68, 2, 5, '2025-10-17 16:32:33'),
(69, 3, 2, '2025-10-17 16:32:33'),
(70, 4, 2, '2025-10-17 16:32:33'),
(71, 4, 6, '2025-10-17 16:32:33'),
(72, 66, 52, '2025-10-17 16:34:22'),
(73, 84, 51, '2025-10-19 05:40:31'),
(74, 84, 53, '2025-10-19 05:40:31'),
(75, 85, 49, '2025-10-19 05:40:31'),
(76, 85, 54, '2025-10-19 05:40:31'),
(77, 86, 52, '2025-10-19 05:40:31'),
(78, 86, 55, '2025-10-19 05:40:31'),
(79, 87, 51, '2025-10-19 05:40:31'),
(80, 87, 53, '2025-10-19 05:40:31'),
(81, 88, 49, '2025-10-19 05:40:31'),
(82, 88, 55, '2025-10-19 05:40:31'),
(83, 1, 25, '2025-10-19 05:51:39'),
(84, 1, 26, '2025-10-19 05:57:30'),
(85, 2, 25, '2025-10-19 06:50:32'),
(86, 2, 26, '2025-10-19 06:51:49'),
(87, 3, 25, '2025-10-19 07:34:23'),
(88, 3, 26, '2025-10-19 07:34:40');

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
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_proposals`
--

INSERT INTO `project_proposals` (`id`, `client_id`, `title`, `description`, `start_date`, `end_date`, `status`, `submitted_at`) VALUES
(1, 2, 'Commercial Building for Discaya', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', '2025-10-20', '2025-10-31', 'approved', '2025-10-19 05:50:59'),
(2, 2, 'Renovation of A Bridge in Dagupan', 'renovating a bridge in dagupan need for workers need this asap', '2025-10-20', '2025-10-30', 'approved', '2025-10-19 06:50:08'),
(3, 2, 'Discaya Building 2', 'build the discaya home in paranaque and pasig make it asap', '2025-10-20', '2025-10-31', 'approved', '2025-10-19 07:34:08');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `title`, `description`, `assigned_to`, `progress`, `due_date`, `created_at`, `completed_at`) VALUES
(1, 1, 'Prepare foundation layout and excavation', 'Mark and excavate the foundation area based on the approved project plan. Ensure correct depth and alignment before concrete works begin.', 26, 100, '2025-10-23', '2025-10-19 05:54:46', '2025-10-19 00:05:57'),
(2, 1, 'Install rebar and steel reinforcement', 'Cut, bend, and tie steel bars according to the structural layout. Verify correct spacing and coverage before concrete pouring.', 26, 100, '2025-10-25', '2025-10-19 05:54:46', '2025-10-19 00:06:05'),
(3, 1, 'Assist in foundation concrete pouring', 'Help with mixing, pouring, and leveling concrete for the foundation slab. Ensure even distribution and proper curing.', 26, 100, '2025-10-27', '2025-10-19 05:54:46', '2025-10-19 00:06:09'),
(4, 1, 'Set up column formwork and alignment', 'Assemble and secure wooden or steel formworks for columns. Ensure plumb alignment and adequate bracing before inspection.', 26, 100, '2025-10-29', '2025-10-19 05:54:46', '2025-10-19 00:06:12'),
(5, 1, 'Organize materials and clean work area', 'Clear debris, store tools properly, and ensure safe access around the site after daily operations.', 26, 100, '2025-10-31', '2025-10-19 05:54:46', '2025-10-19 00:06:17'),
(6, 1, 'Having it check', 'building inspection', 26, 100, '2025-12-21', '2025-10-19 06:00:55', '2025-10-19 00:06:21'),
(7, 1, 'ribbon cutting', 'cutting the ribbon', 26, 100, '2025-12-21', '2025-10-19 06:12:15', '2025-10-19 00:31:27'),
(8, 2, 'wider bridge', 'make the bridge wider', 26, 100, '2025-12-21', '2025-10-19 06:52:26', '2025-10-19 01:21:30'),
(9, 3, 'groundbreaking', 'groundbreaking ceremony', 26, 100, '2025-12-21', '2025-10-19 07:35:07', '2025-10-19 01:36:04');

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
(24, 'Admin', 'admin@buildwatch.com', NULL, '$2y$10$M8QRz.TAUBO3fu6Pz7hwx.YBan4.t0AgMFYudb5MhCtoZNKeIAAOS', 'admin', '2025-09-26 16:02:01'),
(25, 'Project Manager', 'pm@buildwatch.com', NULL, '$2y$10$M8QRz.TAUBO3fu6Pz7hwx.YBan4.t0AgMFYudb5MhCtoZNKeIAAOS', 'pm', '2025-09-26 16:02:01'),
(26, 'Worker', 'worker@buildwatch.com', NULL, '$2y$10$M8QRz.TAUBO3fu6Pz7hwx.YBan4.t0AgMFYudb5MhCtoZNKeIAAOS', 'worker', '2025-09-26 16:02:01');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `project_proposals`
--
ALTER TABLE `project_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
