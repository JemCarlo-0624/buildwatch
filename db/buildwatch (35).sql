-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2025 at 06:57 AM
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
-- Table structure for table `budget_breakdowns`
--

CREATE TABLE `budget_breakdowns` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('materials','labor','equipment','misc') DEFAULT 'misc',
  `estimated_cost` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_reviews`
--

CREATE TABLE `budget_reviews` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `evaluated_amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Jan Julliene Narvasa', 'jjnarvasa@buildwatch.com', '$2y$10$L0990U6T0PrxZ93CSmEk3ulMNiIKelMILpgtpCFnh.ATN1pXUA3qu', '09129841241', 'Discaya', '2025-10-25 14:54:08');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `last_activity_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `project_budgets`
--

CREATE TABLE `project_budgets` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `proposed_amount` decimal(12,2) DEFAULT NULL,
  `evaluated_amount` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','pending_client') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `admin_comment` text DEFAULT NULL,
  `client_decision` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_proposals`
--

CREATE TABLE `project_proposals` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `client_decision` varchar(50) DEFAULT NULL,
  `decision_date` timestamp NULL DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Admin', 'admin@buildwatch.com', NULL, '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'admin', '2025-10-25 16:00:00'),
(2, 'Project Manager', 'pm@buildwatch.com', NULL, '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'pm', '2025-10-25 16:00:00'),
(3, 'Worker', 'worker@buildwatch.com', NULL, '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 16:00:00'),
(4, 'Maria Santos', 'maria.santos@buildwatch.com', '09178234567', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'pm', '2025-10-25 08:00:00'),
(5, 'Roberto Cruz', 'roberto.cruz@buildwatch.com', '09189345678', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'pm', '2025-10-25 08:00:00'),
(6, 'Elena Rodriguez', 'elena.rodriguez@buildwatch.com', '09196456789', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'pm', '2025-10-25 08:00:00'),
(7, 'Carlos Mendoza', 'carlos.mendoza@buildwatch.com', '09187567890', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'pm', '2025-10-25 08:00:00'),
(8, 'Juan Dela Cruz', 'juan.delacruz@buildwatch.com', '09171234567', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(9, 'Pedro Garcia', 'pedro.garcia@buildwatch.com', '09182345678', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(10, 'Miguel Torres', 'miguel.torres@buildwatch.com', '09193456789', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(11, 'Antonio Reyes', 'antonio.reyes@buildwatch.com', '09184567890', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(12, 'Jose Ramos', 'jose.ramos@buildwatch.com', '09175678901', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(13, 'Ricardo Flores', 'ricardo.flores@buildwatch.com', '09186789012', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(14, 'Fernando Santos', 'fernando.santos@buildwatch.com', '09197890123', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(15, 'Luis Martinez', 'luis.martinez@buildwatch.com', '09188901234', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(16, 'Ramon Castillo', 'ramon.castillo@buildwatch.com', '09179012345', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00'),
(17, 'Alberto Morales', 'alberto.morales@buildwatch.com', '09180123456', '$2y$10$lklDwYAIN.fh2qbo0AnBLOyspT2lY8lDCFMLOnnMrE2AGQQkHBEoy', 'worker', '2025-10-25 08:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget_breakdowns`
--
ALTER TABLE `budget_breakdowns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `budget_reviews`
--
ALTER TABLE `budget_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

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
-- Indexes for table `project_budgets`
--
ALTER TABLE `project_budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `fk_budget_creator` (`created_by`);

--
-- Indexes for table `project_proposals`
--
ALTER TABLE `project_proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `idx_proposal_client_status` (`client_id`,`status`);

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
-- AUTO_INCREMENT for table `budget_breakdowns`
--
ALTER TABLE `budget_breakdowns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_reviews`
--
ALTER TABLE `budget_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_budgets`
--
ALTER TABLE `project_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_proposals`
--
ALTER TABLE `project_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_breakdowns`
--
ALTER TABLE `budget_breakdowns`
  ADD CONSTRAINT `budget_breakdowns_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `project_budgets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_reviews`
--
ALTER TABLE `budget_reviews`
  ADD CONSTRAINT `budget_reviews_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `project_proposals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_reviews_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD CONSTRAINT `project_assignments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_budgets`
--
ALTER TABLE `project_budgets`
  ADD CONSTRAINT `fk_budget_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `project_budgets_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `project_proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_proposals`
--
ALTER TABLE `project_proposals`
  ADD CONSTRAINT `project_proposals_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
