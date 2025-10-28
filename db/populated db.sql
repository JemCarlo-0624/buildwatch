-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 06:45 PM
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

--
-- Dumping data for table `budget_breakdowns`
--

INSERT INTO `budget_breakdowns` (`id`, `budget_id`, `item_name`, `category`, `estimated_cost`, `created_at`) VALUES
(1, 1, 'Kitchen Equipment Gas Lines and Connections', 'materials', 280000.00, '2025-10-20 01:20:00'),
(2, 1, 'Commercial Grease Trap System', 'equipment', 320000.00, '2025-10-20 01:20:00'),
(3, 1, 'Fire Suppression System (Kitchen Grade)', 'equipment', 580000.00, '2025-10-20 01:21:00'),
(4, 1, 'Industrial Exhaust and Ventilation System', 'equipment', 680000.00, '2025-10-20 01:21:00'),
(5, 1, 'Commercial Grade Tiles and Flooring (800 sqm)', 'materials', 420000.00, '2025-10-20 01:22:00'),
(6, 1, 'Stainless Steel Fixtures and Counters', 'materials', 380000.00, '2025-10-20 01:22:00'),
(7, 1, 'Electrical Upgrade (3-Phase, Multiple Circuits)', 'materials', 450000.00, '2025-10-20 01:23:00'),
(8, 1, 'Water Supply System and Plumbing', 'materials', 290000.00, '2025-10-20 01:23:00'),
(9, 1, 'Specialized Installation Labor (2.5 months)', 'labor', 380000.00, '2025-10-20 01:24:00'),
(10, 1, 'Electrician and Plumber Labor', 'labor', 220000.00, '2025-10-20 01:24:00'),
(11, 1, 'Tiling and Finishing Labor', 'labor', 180000.00, '2025-10-20 01:25:00'),
(12, 1, 'Health and Safety Permits', 'misc', 120000.00, '2025-10-20 01:25:00'),
(13, 1, 'Environmental Compliance and Testing', 'misc', 85000.00, '2025-10-20 01:26:00'),
(14, 1, 'Mall Management Coordination Fees', 'misc', 65000.00, '2025-10-20 01:26:00'),
(15, 1, 'Contingency and Cleanup', 'misc', 70000.00, '2025-10-20 01:27:00'),
(16, 2, 'Swimming Pool Construction (Olympic size)', 'materials', 1200000.00, '2025-10-22 06:35:00'),
(17, 2, 'Pool Filtration and Pump System', 'equipment', 450000.00, '2025-10-22 06:35:00'),
(18, 2, 'Pool Tiles and Finishing', 'materials', 380000.00, '2025-10-22 06:36:00'),
(19, 2, 'Gym Flooring (Rubber matting, 200 sqm)', 'materials', 280000.00, '2025-10-22 06:36:00'),
(20, 2, 'Function Hall Flooring and Walls', 'materials', 420000.00, '2025-10-22 06:37:00'),
(21, 2, 'Children Play Area Safety Surfacing', 'materials', 320000.00, '2025-10-22 06:37:00'),
(22, 2, 'Sky Garden Landscaping Materials', 'materials', 380000.00, '2025-10-22 06:38:00'),
(23, 2, 'Waterproofing System (Complete floor)', 'materials', 520000.00, '2025-10-22 06:38:00'),
(24, 2, 'HVAC System for Indoor Areas', 'equipment', 580000.00, '2025-10-22 06:39:00'),
(25, 2, 'Electrical and Lighting Systems', 'materials', 390000.00, '2025-10-22 06:39:00'),
(26, 2, 'Pool Construction Labor (Specialist)', 'labor', 420000.00, '2025-10-22 06:40:00'),
(27, 2, 'General Construction Labor (4 months)', 'labor', 580000.00, '2025-10-22 06:40:00'),
(28, 2, 'Waterproofing Application Labor', 'labor', 180000.00, '2025-10-22 06:41:00'),
(29, 2, 'Landscaping Installation Labor', 'labor', 150000.00, '2025-10-22 06:41:00'),
(30, 2, 'Building Permits and Inspections', 'misc', 120000.00, '2025-10-22 06:42:00'),
(31, 2, 'Testing and Commissioning', 'misc', 110000.00, '2025-10-22 06:42:00'),
(49, 3, 'Engineering Testing and Commissioning', 'misc', 120000.00, '2025-10-28 17:40:03'),
(50, 3, 'MERALCO Connection and Permits', 'misc', 180000.00, '2025-10-28 17:40:03'),
(51, 3, 'Factory Safety Compliance', 'misc', 140000.00, '2025-10-28 17:40:03'),
(52, 3, 'Floor Coating Application (Specialist)', 'labor', 220000.00, '2025-10-28 17:40:03'),
(53, 3, 'Industrial Electrician Labor', 'labor', 320000.00, '2025-10-28 17:40:03'),
(54, 3, 'Electrical Engineering and Installation', 'labor', 480000.00, '2025-10-28 17:40:03'),
(55, 3, 'Structural Work and Concrete Labor', 'labor', 380000.00, '2025-10-28 17:40:03'),
(56, 3, 'Material Handling Rails and Supports', 'materials', 380000.00, '2025-10-28 17:40:03'),
(57, 3, 'Fire Detection and Suppression System', 'equipment', 520000.00, '2025-10-28 17:40:03'),
(58, 3, 'Industrial LED Lighting System', 'equipment', 420000.00, '2025-10-28 17:40:03'),
(59, 3, 'Heavy-Duty Ventilation and Exhaust', 'equipment', 680000.00, '2025-10-28 17:40:03'),
(60, 3, 'Concrete Equipment Foundations', 'materials', 450000.00, '2025-10-28 17:40:03'),
(61, 3, 'Industrial Grade Epoxy Floor Coating (1200 sqm)', 'materials', 580000.00, '2025-10-28 17:40:03'),
(62, 3, 'Industrial Wiring and Conduits', 'materials', 520000.00, '2025-10-28 17:40:03'),
(63, 3, 'Structural Steel Reinforcement', 'materials', 780000.00, '2025-10-28 17:40:03'),
(64, 3, 'Power Transformer and Substation (1000 KVA)', 'equipment', 1200000.00, '2025-10-28 17:40:03'),
(65, 3, 'Heavy-Duty Electrical Distribution Panels', 'materials', 680000.00, '2025-10-28 17:40:03');

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

--
-- Dumping data for table `budget_reviews`
--

INSERT INTO `budget_reviews` (`id`, `proposal_id`, `admin_id`, `evaluated_amount`, `status`, `remarks`, `created_at`) VALUES
(1, 1, 1, 4100000.00, 'pending', 'Safety systems cannot be compromised. Awaiting client approval for enhanced specifications.', '2025-10-27 02:45:00'),
(2, 2, 1, 5800000.00, 'pending', 'Budget is accurate and realistic. Ready for client approval.', '2025-10-28 01:20:00'),
(3, 3, 1, 7350000.00, 'pending', 'Power infrastructure is critical for factory operations. Structural integrity non-negotiable.', '2025-10-28 07:10:00'),
(4, 3, 1, 8050000.00, 'pending_client', 'Power infrastructure upgrade for 1000 KVA capacity (₱650k). Structural reinforcement for machinery load (₱350k). Industrial-grade epoxy flooring (₱150k).', '2025-10-28 17:40:03');

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `client_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'project_completed', 'Project Successfully Completed', 'Your Corporate Headquarters Building Renovation project has been completed on schedule. Final documentation is ready for review.', '/projects/1', 1, '2025-10-05 10:15:00'),
(2, 1, 'budget_review', 'Budget Review - Food Court Project', 'The budget for your SM Megamall Food Court Renovation has been evaluated at ₱4,100,000.00. Please review and provide your decision.', '/proposals/1', 0, '2025-10-27 02:50:00'),
(3, 1, 'budget_review', 'Budget Approved - Amenity Floor', 'The budget for your The Peak Residences Amenity Floor project has been evaluated at ₱5,800,000.00 (exact as proposed). Awaiting your approval.', '/proposals/2', 0, '2025-10-28 01:25:00'),
(4, 1, 'budget_review', 'Budget Review Required - Factory Project', 'The budget for your Manufacturing Plant Assembly Line has been evaluated at ₱7,350,000.00. Please review the adjusted costs for power infrastructure upgrades.', '/proposals/3', 0, '2025-10-28 07:15:00'),
(5, 1, 'project_update', 'Hospital Laboratory Wing Progress', 'Your St. Luke\'s Hospital Laboratory Wing Extension is now 42% complete. Clean room construction is progressing well.', '/projects/2', 0, '2025-10-28 08:50:00'),
(6, 1, 'proposal_submitted', 'New Project Awaiting Planning', 'Your Hotel Renovation project is in planning stage. Assignment of project team is pending budget approval.', '/projects/3', 0, '2025-10-18 05:30:00'),
(7, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱8,050,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=3', 0, '2025-10-28 17:40:03');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `project_manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `status`, `completion_percentage`, `priority`, `created_by`, `client_id`, `timeline`, `start_date`, `end_date`, `created_at`, `last_activity_at`, `project_manager_id`) VALUES
(1, 'Corporate Headquarters Building Renovation', 'Complete renovation of 3-story corporate office building (1,500 sqm total). Modernization of facade, lobby area, meeting rooms, electrical systems, and common areas. Includes new curtain wall installation and parking lot resurfacing.', 'completed', 100, 'high', 1, 1, '2 months', '2025-08-05', '2025-10-05', '2025-07-28 02:00:00', '2025-10-05 10:00:00', 4),
(2, 'St. Luke\'s Hospital - Laboratory Wing Extension', 'Construction of new laboratory wing (600 sqm). Specialized medical-grade facilities including clean rooms, equipment rooms, specimen storage, and staff areas. Advanced HVAC with HEPA filtration, medical gas systems, and laboratory-grade utilities.', 'ongoing', 42, 'high', 1, 1, '4 months', '2025-09-15', '2026-01-15', '2025-09-05 01:30:00', '2025-10-28 08:45:00', 5),
(3, 'Hotel Renovation - Guest Rooms and Lobby', 'Renovation of 50 guest rooms and main lobby area of boutique hotel. Includes bathroom upgrades, furniture replacement, new flooring, lighting modernization, and lobby reception redesign. Focus on upscale finishes and guest experience.', 'planning', 0, 'medium', 1, 1, '3 months', '2025-12-01', '2026-02-28', '2025-10-18 05:20:00', '2025-10-28 03:15:00', NULL);

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
(1, 1, 4, '2025-07-28 02:30:00'),
(2, 1, 8, '2025-07-28 02:30:00'),
(3, 1, 9, '2025-07-28 02:30:00'),
(4, 2, 5, '2025-09-05 02:00:00'),
(5, 2, 10, '2025-09-05 02:00:00'),
(6, 2, 11, '2025-09-05 02:00:00');

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

--
-- Dumping data for table `project_budgets`
--

INSERT INTO `project_budgets` (`id`, `proposal_id`, `proposed_amount`, `evaluated_amount`, `status`, `remarks`, `admin_comment`, `client_decision`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 1, 3500000.00, 4100000.00, 'approved', 'Enhanced fire suppression system mandatory for food court operations (₱280k). Premium ventilation with odor control required by mall management (₱220k). Additional grease interceptor for environmental compliance (₱100k).', 'Budget increase necessary for safety and regulatory compliance. Mall management has strict requirements. Recommend approval to meet all safety standards.', 'approved', '2025-10-20 01:15:00', '2025-10-28 17:39:15', 1),
(2, 2, 5800000.00, 5800000.00, 'approved', 'All cost estimates are accurate. Materials pricing confirmed with suppliers. Labor allocation is appropriate for the timeline. Swimming pool contractor has competitive rates.', 'Budget is well-prepared and realistic. No adjustments needed. Timeline is adequate for quality work including pool curing period.', '\napproved', '2025-10-22 06:30:00', '2025-10-28 17:39:19', 1),
(3, 3, 6200000.00, 8050000.00, 'pending_client', 'Power infrastructure upgrade for 1000 KVA capacity (₱650k). Structural reinforcement for machinery load (₱350k). Industrial-grade epoxy flooring (₱150k).', 'Power infrastructure upgrade for 1000 KVA capacity (₱650k). Structural reinforcement for machinery load (₱350k). Industrial-grade epoxy flooring (₱150k).', 'pending', '2025-10-25 03:00:00', '2025-10-28 17:40:03', 1);

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
  `evaluated_start_date` date DEFAULT NULL,
  `evaluated_end_date` date DEFAULT NULL,
  `evaluation_notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `client_decision` varchar(50) DEFAULT NULL,
  `decision_date` timestamp NULL DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_proposals`
--

INSERT INTO `project_proposals` (`id`, `admin_id`, `client_id`, `title`, `description`, `start_date`, `end_date`, `evaluated_start_date`, `evaluated_end_date`, `evaluation_notes`, `status`, `submitted_at`, `client_decision`, `decision_date`, `budget`) VALUES
(1, 1, 1, 'SM Megamall Food Court Renovation', 'Complete renovation of 800 sqm food court area including kitchen facilities, dining area flooring, ventilation upgrade, grease trap installation, and fire suppression system. Modernization of electrical and water systems for multiple food stalls.', '2025-11-15', '2026-01-30', '2025-11-20', '2026-02-15', 'Additional fire safety requirements needed. Timeline extended to accommodate health permit inspections. Enhanced ventilation system required for cooking emissions.', 'pending', '2025-10-20 01:15:00', NULL, NULL, 3500000.00),
(2, 1, 1, 'The Peak Residences - Amenity Floor Construction', 'Construction of 5th floor amenity area for residential condominium. Includes swimming pool, gym equipment room, function hall, children\'s play area, and sky garden. Waterproofing, landscaping, and specialized equipment installation.', '2025-12-01', '2026-03-31', '2025-12-05', '2026-04-20', 'Waterproofing specifications must meet PEZA standards. Swimming pool requires specialized contractor certification. Longer timeline needed for pool curing and testing phases.', 'pending', '2025-10-22 06:30:00', NULL, NULL, 5800000.00),
(3, 1, 1, 'Discaya Manufacturing Plant - Assembly Line Installation', 'Setup of automated assembly line in 1200 sqm factory floor. Includes heavy-duty electrical infrastructure, industrial lighting, ventilation systems, epoxy floor coating, equipment foundations, and material handling systems.', '2025-11-10', '2026-02-28', '2025-11-12', '2026-03-10', 'Power supply upgrade required (1000 KVA capacity). Additional structural reinforcement for heavy machinery. Specialized epoxy coating needs 14-day curing period.', 'pending', '2025-10-25 03:00:00', NULL, NULL, 6200000.00);

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
(1, 1, 'Facade and Curtain Wall Removal', 'Safe removal of old facade panels and curtain wall system. Proper disposal and site preparation for new installation. Weather protection during transition.', 8, 100, '2025-08-20', '2025-07-28 03:00:00', '2025-08-19 09:30:00'),
(2, 1, 'New Curtain Wall Installation', 'Installation of modern aluminum and glass curtain wall system. Waterproofing, sealing, and quality checks. Compliance with building codes.', 9, 100, '2025-09-10', '2025-07-28 03:05:00', '2025-09-09 08:45:00'),
(3, 1, 'Electrical System Upgrade', 'Complete electrical panel replacement, new wiring for all floors, LED lighting installation, and emergency power system setup.', 8, 100, '2025-09-18', '2025-07-28 03:10:00', '2025-09-17 07:20:00'),
(4, 1, 'Interior Renovation and Painting', 'Lobby redesign, meeting room upgrades, corridor improvements, complete painting of all interior spaces, and furniture installation.', 9, 100, '2025-09-28', '2025-07-28 03:15:00', '2025-09-27 10:10:00'),
(5, 1, 'Parking Lot and Final Inspection', 'Parking lot resurfacing, striping, landscaping touch-ups, final building inspection, and client walkthrough. Project closeout documentation.', 8, 100, '2025-10-05', '2025-07-28 03:20:00', '2025-10-05 10:00:00'),
(6, 2, 'Foundation and Structural Framework', 'Medical-grade foundation construction with vibration isolation. Structural steel framework for laboratory wing. Seismic compliance verification.', 10, 100, '2025-10-10', '2025-09-05 02:30:00', '2025-10-09 09:00:00'),
(7, 2, 'Clean Room Construction', 'Construction of ISO Class 7 clean rooms with specialized wall panels, ceilings, and sealed flooring. Air lock installation and pressure controls.', 11, 75, '2025-11-05', '2025-09-05 02:35:00', NULL),
(8, 2, 'HVAC and HEPA Filtration System', 'Installation of specialized HVAC with HEPA filtration, temperature and humidity controls, and air quality monitoring systems for laboratory environment.', 10, 55, '2025-11-25', '2025-09-05 02:40:00', NULL),
(9, 2, 'Medical Gas and Plumbing Systems', 'Installation of medical gas manifolds, laboratory-grade plumbing, specialized sinks, emergency eyewash stations, and chemical waste handling systems.', 11, 30, '2025-12-15', '2025-09-05 02:45:00', NULL),
(10, 2, 'Equipment Installation and Commissioning', 'Laboratory equipment foundations, electrical connections, network cabling, final testing, and health department inspection. Operational readiness verification.', 10, 10, '2026-01-15', '2025-09-05 02:50:00', NULL);

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
  ADD KEY `idx_status` (`status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `budget_reviews`
--
ALTER TABLE `budget_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `project_budgets`
--
ALTER TABLE `project_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `project_proposals`
--
ALTER TABLE `project_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
