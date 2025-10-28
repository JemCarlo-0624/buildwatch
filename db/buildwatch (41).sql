-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 04:45 PM
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
(130, 5, 'Restaurant Business Permits', 'misc', 125000.00, '2025-10-26 09:49:29'),
(131, 5, 'Health and Safety Compliance', 'misc', 85000.00, '2025-10-26 09:49:29'),
(132, 5, 'Outdoor Patio Construction', 'misc', 165000.00, '2025-10-26 09:49:29'),
(133, 5, 'Signage and Exterior Design', 'misc', 95000.00, '2025-10-26 09:49:29'),
(134, 5, 'Parking Area Development', 'misc', 125000.00, '2025-10-26 09:49:29'),
(135, 5, 'Project Management', 'misc', 145000.00, '2025-10-26 09:49:29'),
(136, 5, 'Contingency Fund (11%)', 'misc', 420000.00, '2025-10-26 09:49:29'),
(137, 5, 'Construction Equipment Rental', 'equipment', 95000.00, '2025-10-26 09:49:29'),
(138, 5, 'Specialized Kitchen Installation Tools', 'equipment', 65000.00, '2025-10-26 09:49:29'),
(139, 5, 'Safety and Protection Equipment', 'equipment', 45000.00, '2025-10-26 09:49:29'),
(140, 5, 'Architectural Design Specialists', 'labor', 245000.00, '2025-10-26 09:49:29'),
(141, 5, 'Construction Labor', 'labor', 335000.00, '2025-10-26 09:49:29'),
(142, 5, 'Commercial Kitchen Installation', 'labor', 185000.00, '2025-10-26 09:49:29'),
(143, 5, 'Electrical and HVAC Installation', 'labor', 225000.00, '2025-10-26 09:49:29'),
(144, 5, 'Plumbing and Gas Line Installation', 'labor', 165000.00, '2025-10-26 09:49:29'),
(145, 5, 'Interior Finishing and Painting', 'labor', 145000.00, '2025-10-26 09:49:29'),
(146, 5, 'Foundation and Structural Work', 'materials', 485000.00, '2025-10-26 09:49:29'),
(147, 5, 'Roofing and Ceiling Materials', 'materials', 295000.00, '2025-10-26 09:49:29'),
(148, 5, 'Wall Construction Materials', 'materials', 265000.00, '2025-10-26 09:49:29'),
(149, 5, 'Commercial-Grade Flooring', 'materials', 325000.00, '2025-10-26 09:49:29'),
(150, 5, 'Glass Walls and Windows', 'materials', 385000.00, '2025-10-26 09:49:29'),
(151, 5, 'Kitchen Plumbing and Fixtures', 'materials', 295000.00, '2025-10-26 09:49:29'),
(152, 5, 'Heavy-Duty Electrical System', 'materials', 425000.00, '2025-10-26 09:49:29'),
(153, 5, 'Ventilation and Exhaust Systems', 'materials', 385000.00, '2025-10-26 09:49:29'),
(154, 5, 'Fire Suppression System', 'materials', 245000.00, '2025-10-26 09:49:29'),
(155, 5, 'Interior Finishes and Decorative Elements', 'materials', 285000.00, '2025-10-26 09:49:29'),
(156, 4, 'Building Permits and DepEd Compliance', 'misc', 185000.00, '2025-10-26 09:56:50'),
(157, 4, 'Site Preparation and Excavation', 'misc', 245000.00, '2025-10-26 09:56:50'),
(158, 4, 'Accessibility Features (Ramps, Rails)', 'misc', 165000.00, '2025-10-26 09:56:50'),
(159, 4, 'Landscaping and Playground Area', 'misc', 285000.00, '2025-10-26 09:56:50'),
(160, 4, 'Project Management and Supervision', 'misc', 385000.00, '2025-10-26 09:56:50'),
(161, 4, 'Quality Assurance and Testing', 'misc', 145000.00, '2025-10-26 09:56:50'),
(162, 4, 'Contingency Fund (13%)', 'misc', 1020000.00, '2025-10-26 09:56:50'),
(163, 4, 'Tower Crane Rental', 'equipment', 285000.00, '2025-10-26 09:56:50'),
(164, 4, 'Scaffolding System', 'equipment', 165000.00, '2025-10-26 09:56:50'),
(165, 4, 'Concrete Mixing Equipment', 'equipment', 125000.00, '2025-10-26 09:56:50'),
(166, 4, 'Construction Tools and Machinery', 'equipment', 145000.00, '2025-10-26 09:56:50'),
(167, 4, 'Safety Equipment and Barriers', 'equipment', 95000.00, '2025-10-26 09:56:50'),
(168, 4, 'Architectural and Engineering Team', 'labor', 565000.00, '2025-10-26 09:56:50'),
(169, 4, 'General Construction Workers', 'labor', 720000.00, '2025-10-26 09:56:50'),
(170, 4, 'Structural Work Specialists', 'labor', 425000.00, '2025-10-26 09:56:50'),
(171, 4, 'Electrical Installation Team', 'labor', 315000.00, '2025-10-26 09:56:50'),
(172, 4, 'Plumbing Installation', 'labor', 245000.00, '2025-10-26 09:56:50'),
(173, 4, 'Carpentry and Finishing', 'labor', 285000.00, '2025-10-26 09:56:50'),
(174, 4, 'Reinforced Concrete Structure', 'materials', 1250000.00, '2025-10-26 09:56:50'),
(175, 4, 'Roofing and Ceiling Systems', 'materials', 685000.00, '2025-10-26 09:56:50'),
(176, 4, 'Walls and Partition Materials', 'materials', 520000.00, '2025-10-26 09:56:50'),
(177, 4, 'Flooring (Non-slip tiles)', 'materials', 445000.00, '2025-10-26 09:56:50'),
(178, 4, 'Windows and Ventilation Systems', 'materials', 395000.00, '2025-10-26 09:56:50'),
(179, 4, 'Doors and Security Gates', 'materials', 285000.00, '2025-10-26 09:56:50'),
(180, 4, 'Electrical System and Lighting', 'materials', 625000.00, '2025-10-26 09:56:50'),
(181, 4, 'Plumbing and Sanitary Facilities', 'materials', 385000.00, '2025-10-26 09:56:50'),
(182, 4, 'Fire Safety and Emergency Systems', 'materials', 425000.00, '2025-10-26 09:56:50'),
(183, 4, 'Paint and Waterproofing', 'materials', 295000.00, '2025-10-26 09:56:50'),
(184, 4, 'Laboratory Equipment and Fixtures', 'materials', 485000.00, '2025-10-26 09:56:50'),
(185, 4, 'Built-in Cabinets and Shelving', 'materials', 335000.00, '2025-10-26 09:56:50'),
(186, 3, 'Industrial Permits and Compliance', 'misc', 185000.00, '2025-10-26 09:57:20'),
(187, 3, 'Site Development and Grading', 'misc', 235000.00, '2025-10-26 09:57:20'),
(188, 3, 'Parking Area Construction', 'misc', 165000.00, '2025-10-26 09:57:20'),
(189, 3, 'Project Management and Supervision', 'misc', 285000.00, '2025-10-26 09:57:20'),
(190, 3, 'Insurance and Performance Bonds', 'misc', 145000.00, '2025-10-26 09:57:20'),
(191, 3, 'Contingency Fund (14%)', 'misc', 755000.00, '2025-10-26 09:57:20'),
(192, 3, 'Heavy Crane and Lifting Equipment', 'equipment', 285000.00, '2025-10-26 09:57:20'),
(193, 3, 'Welding and Fabrication Tools', 'equipment', 165000.00, '2025-10-26 09:57:20'),
(194, 3, 'Concrete Pumps and Mixers', 'equipment', 125000.00, '2025-10-26 09:57:20'),
(195, 3, 'Scaffolding and Access Equipment', 'equipment', 95000.00, '2025-10-26 09:57:20'),
(196, 3, 'Safety Gear and PPE', 'equipment', 75000.00, '2025-10-26 09:57:20'),
(197, 3, 'Structural Engineering Services', 'labor', 425000.00, '2025-10-26 09:57:20'),
(198, 3, 'Steel Fabrication and Installation', 'labor', 495000.00, '2025-10-26 09:57:20'),
(199, 3, 'General Construction Workers', 'labor', 380000.00, '2025-10-26 09:57:20'),
(200, 3, 'Industrial Electricians', 'labor', 265000.00, '2025-10-26 09:57:20'),
(201, 3, 'Fire Safety System Installation', 'labor', 185000.00, '2025-10-26 09:57:20'),
(202, 3, 'Security System Installation', 'labor', 145000.00, '2025-10-26 09:57:20'),
(203, 3, 'Steel Structure and Framework', 'materials', 920000.00, '2025-10-26 09:57:20'),
(204, 3, 'Industrial Roofing System', 'materials', 485000.00, '2025-10-26 09:57:20'),
(205, 3, 'Concrete Foundation and Floor', 'materials', 680000.00, '2025-10-26 09:57:20'),
(206, 3, 'Metal Cladding and Wall Panels', 'materials', 395000.00, '2025-10-26 09:57:20'),
(207, 3, 'Industrial Electrical System', 'materials', 540000.00, '2025-10-26 09:57:20'),
(208, 3, 'Loading Dock Equipment', 'materials', 285000.00, '2025-10-26 09:57:20'),
(209, 3, 'Fire Safety System and Sprinklers', 'materials', 420000.00, '2025-10-26 09:57:20'),
(210, 3, 'Security Systems and CCTV', 'materials', 315000.00, '2025-10-26 09:57:20'),
(211, 3, 'Office Interior Materials', 'materials', 225000.00, '2025-10-26 09:57:20'),
(212, 3, 'Industrial Doors and Gates', 'materials', 340000.00, '2025-10-26 09:57:20'),
(213, 2, 'Building Permits and Documentation', 'misc', 75000.00, '2025-10-26 09:58:21'),
(214, 2, 'Site Preparation and Excavation', 'misc', 95000.00, '2025-10-26 09:58:21'),
(215, 2, 'Landscaping and Garden Setup', 'misc', 125000.00, '2025-10-26 09:58:21'),
(216, 2, 'Project Management Fee', 'misc', 140000.00, '2025-10-26 09:58:21'),
(217, 2, 'Contingency Fund (12%)', 'misc', 345000.00, '2025-10-26 09:58:21'),
(218, 2, 'Construction Equipment Rental', 'equipment', 85000.00, '2025-10-26 09:58:21'),
(219, 2, 'Concrete Mixer and Tools', 'equipment', 55000.00, '2025-10-26 09:58:21'),
(220, 2, 'Safety Equipment', 'equipment', 35000.00, '2025-10-26 09:58:21'),
(221, 2, 'Architectural and Engineering Services', 'labor', 285000.00, '2025-10-26 09:58:21'),
(222, 2, 'General Construction Labor', 'labor', 410000.00, '2025-10-26 09:58:21'),
(223, 2, 'Masonry and Carpentry', 'labor', 195000.00, '2025-10-26 09:58:21'),
(224, 2, 'Electrical Installation', 'labor', 125000.00, '2025-10-26 09:58:21'),
(225, 2, 'Plumbing Installation', 'labor', 110000.00, '2025-10-26 09:58:21'),
(226, 2, 'Painting and Finishing', 'labor', 95000.00, '2025-10-26 09:58:21'),
(227, 2, 'Foundation Materials (Concrete, Rebar)', 'materials', 380000.00, '2025-10-26 09:58:21'),
(228, 2, 'Structural Framing and Roofing', 'materials', 520000.00, '2025-10-26 09:58:21'),
(229, 2, 'Building Permits and DepEd Compliance', 'materials', 125000.00, '2025-10-26 09:58:21'),
(230, 1, 'Building Permits and Inspections', 'misc', 110000.00, '2025-10-26 09:58:36'),
(231, 1, 'Insurance and Bonding', 'misc', 85000.00, '2025-10-26 09:58:36'),
(232, 1, 'Waste Disposal and Site Cleanup', 'misc', 75000.00, '2025-10-26 09:58:36'),
(233, 1, 'Project Management and Administration', 'misc', 165000.00, '2025-10-26 09:58:36'),
(234, 1, 'Contingency Fund (15%)', 'misc', 585000.00, '2025-10-26 09:58:36'),
(235, 1, 'Scaffolding Rental (6 months)', 'equipment', 125000.00, '2025-10-26 09:58:36'),
(236, 1, 'Crane and Heavy Equipment Rental', 'equipment', 180000.00, '2025-10-26 09:58:36'),
(237, 1, 'Power Tools and Machinery', 'equipment', 95000.00, '2025-10-26 09:58:36'),
(238, 1, 'Safety Equipment and Gear', 'equipment', 65000.00, '2025-10-26 09:58:36'),
(239, 1, 'Structural Engineers and Architects', 'labor', 380000.00, '2025-10-26 09:58:36'),
(240, 1, 'Skilled Construction Workers', 'labor', 520000.00, '2025-10-26 09:58:36'),
(241, 1, 'Electricians and Electrical Engineers', 'labor', 295000.00, '2025-10-26 09:58:36'),
(242, 1, 'Plumbers and Pipefitters', 'labor', 240000.00, '2025-10-26 09:58:36'),
(243, 1, 'HVAC Installation Specialists', 'labor', 185000.00, '2025-10-26 09:58:36'),
(244, 1, 'Painters and Finishers', 'labor', 145000.00, '2025-10-26 09:58:36'),
(245, 1, 'Structural Steel and Reinforcement', 'materials', 650000.00, '2025-10-26 09:58:36'),
(246, 1, 'Cement, Concrete and Aggregates', 'materials', 420000.00, '2025-10-26 09:58:36'),
(247, 1, 'Electrical Wiring and Components', 'materials', 385000.00, '2025-10-26 09:58:36'),
(248, 1, 'Plumbing Pipes and Fixtures', 'materials', 310000.00, '2025-10-26 09:58:36'),
(249, 1, 'Paint and Finishing Materials', 'materials', 225000.00, '2025-10-26 09:58:36'),
(250, 1, 'Flooring Materials (Tiles, Hardwood)', 'materials', 340000.00, '2025-10-26 09:58:36'),
(251, 1, 'Windows and Doors', 'materials', 280000.00, '2025-10-26 09:58:36'),
(252, 1, 'HVAC System Components', 'materials', 450000.00, '2025-10-26 09:58:36'),
(352, 11, 'Medical-Grade Flooring (Vinyl/Epoxy)', 'materials', 385000.00, '2025-10-28 12:18:27'),
(353, 11, 'Wall Partitions and Soundproofing', 'materials', 425000.00, '2025-10-28 12:18:27'),
(354, 11, 'Ceiling System with Clean Room Specs', 'materials', 295000.00, '2025-10-28 12:18:27'),
(355, 11, 'Medical-Grade Electrical System', 'materials', 485000.00, '2025-10-28 12:18:27'),
(356, 11, 'Specialized Plumbing and Fixtures', 'materials', 340000.00, '2025-10-28 12:18:27'),
(357, 11, 'HVAC with HEPA Filtration', 'materials', 520000.00, '2025-10-28 12:18:27'),
(358, 11, 'Architectural Design for Medical Facility', 'labor', 285000.00, '2025-10-28 12:18:27'),
(359, 11, 'General Construction Labor', 'labor', 425000.00, '2025-10-28 12:18:27'),
(360, 11, 'Medical Electrical Installation', 'labor', 295000.00, '2025-10-28 12:18:27'),
(361, 11, 'Plumbing and Gas Line Installation', 'labor', 225000.00, '2025-10-28 12:18:27'),
(362, 11, 'HVAC Installation Specialists', 'labor', 220000.00, '2025-10-28 12:18:27'),
(363, 11, 'Construction Equipment Rental', 'equipment', 125000.00, '2025-10-28 12:18:27'),
(364, 11, 'Medical Installation Tools', 'equipment', 185000.00, '2025-10-28 12:18:27'),
(365, 11, 'Safety and Clean Room Equipment', 'equipment', 135000.00, '2025-10-28 12:18:27'),
(366, 11, 'DOH Permits and Compliance', 'misc', 185000.00, '2025-10-28 12:18:27'),
(367, 11, 'Fire Safety and Emergency Systems', 'misc', 225000.00, '2025-10-28 12:18:27'),
(368, 11, 'Project Management', 'misc', 165000.00, '2025-10-28 12:18:27'),
(369, 11, 'Contingency Fund (10%)', 'misc', 205000.00, '2025-10-28 12:18:27'),
(370, 13, 'Premium Flooring (Hardwood/Carpet)', 'materials', 485000.00, '2025-10-28 13:48:01'),
(371, 13, 'Built-in Furniture and Headboards', 'materials', 625000.00, '2025-10-28 13:48:01'),
(372, 13, 'Bathroom Fixtures and Tiles', 'materials', 545000.00, '2025-10-28 13:48:01'),
(373, 13, 'Acoustic Insulation Materials', 'materials', 385000.00, '2025-10-28 13:48:01'),
(374, 13, 'Electrical Upgrades and Smart Controls', 'materials', 425000.00, '2025-10-28 13:48:01'),
(375, 13, 'Paint and Wall Finishes', 'materials', 245000.00, '2025-10-28 13:48:01'),
(376, 13, 'Window Treatments (Blackout)', 'materials', 215000.00, '2025-10-28 13:48:01'),
(377, 13, 'Interior Design and Planning', 'labor', 295000.00, '2025-10-28 13:48:01'),
(378, 13, 'General Renovation Labor', 'labor', 485000.00, '2025-10-28 13:48:01'),
(379, 13, 'Carpentry and Built-in Installation', 'labor', 325000.00, '2025-10-28 13:48:01'),
(380, 13, 'Electrical and Automation Installation', 'labor', 285000.00, '2025-10-28 13:48:01'),
(381, 13, 'Plumbing and Bathroom Renovation', 'labor', 295000.00, '2025-10-28 13:48:01'),
(382, 13, 'Construction Equipment Rental', 'equipment', 145000.00, '2025-10-28 13:48:01'),
(383, 13, 'Specialized Installation Tools', 'equipment', 125000.00, '2025-10-28 13:48:01'),
(384, 13, 'Safety and Protection Equipment', 'equipment', 95000.00, '2025-10-28 13:48:01'),
(385, 13, 'Building Permits and Inspections', 'misc', 125000.00, '2025-10-28 13:48:01'),
(386, 13, 'Fire Safety System Upgrades', 'misc', 245000.00, '2025-10-28 13:48:01'),
(387, 13, 'Waste Management and Cleanup', 'misc', 95000.00, '2025-10-28 13:48:01'),
(388, 13, 'Project Management and Coordination', 'misc', 225000.00, '2025-10-28 13:48:01'),
(389, 13, 'Contingency Fund (12%)', 'misc', 230000.00, '2025-10-28 13:48:01'),
(390, 12, 'Premium Marble and Tile Flooring', 'materials', 425000.00, '2025-10-28 14:28:49'),
(391, 12, 'Custom Display Shelving System', 'materials', 385000.00, '2025-10-28 14:28:49'),
(392, 12, 'Decorative Ceiling with Moldings', 'materials', 245000.00, '2025-10-28 14:28:49'),
(393, 12, 'Glass Storefront and Entrance', 'materials', 295000.00, '2025-10-28 14:28:49'),
(394, 12, 'Lighting Fixtures (Track & Accent)', 'materials', 335000.00, '2025-10-28 14:28:49'),
(395, 12, 'Interior Design Services', 'labor', 225000.00, '2025-10-28 14:28:49'),
(396, 12, 'Carpentry and Millwork', 'labor', 285000.00, '2025-10-28 14:28:49'),
(397, 12, 'Electrical and Lighting Installation', 'labor', 185000.00, '2025-10-28 14:28:49'),
(398, 12, 'Finishing and Painting', 'labor', 170000.00, '2025-10-28 14:28:49'),
(399, 12, 'Construction Tools and Equipment', 'equipment', 95000.00, '2025-10-28 14:28:49'),
(400, 12, 'Specialized Installation Tools', 'equipment', 85000.00, '2025-10-28 14:28:49'),
(401, 12, 'Safety Equipment', 'equipment', 65000.00, '2025-10-28 14:28:49'),
(402, 12, 'Building Permits and Documentation', 'misc', 95000.00, '2025-10-28 14:28:49'),
(403, 12, 'Security System Installation', 'misc', 145000.00, '2025-10-28 14:28:49'),
(404, 12, 'Sound System Installation', 'misc', 85000.00, '2025-10-28 14:28:49'),
(405, 12, 'Project Management Fee', 'misc', 95000.00, '2025-10-28 14:28:49'),
(406, 12, 'Contingency Fund (8%)', 'misc', 65000.00, '2025-10-28 14:28:49');

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
(1, 5, 1, 6055000.00, 'pending_client', 'Budget for complete restaurant facility including commercial kitchen setup, dining areas, and all required permits for food service establishment. Excludes kitchen equipment and furniture.', '2025-10-26 09:49:29'),
(2, 4, 1, 11930000.00, 'pending_client', 'Comprehensive educational facility construction following Department of Education standards and building codes. Budget includes specialized educational equipment and safety features required for schools.', '2025-10-26 09:56:50'),
(3, 3, 1, 9015000.00, 'pending_client', 'Comprehensive budget for industrial warehouse construction including all structural, electrical, security, and fire safety systems. Built to industrial standards with emphasis on durability and functionality.', '2025-10-26 09:57:20'),
(4, 2, 1, 3200000.00, 'approved', 'Budget estimate for complete residential construction including all finishes and fixtures. Excludes furniture and appliances.', '2025-10-26 09:58:21'),
(5, 1, 1, 6310000.00, 'pending_client', 'Initial budget proposal based on preliminary site assessment and client requirements. Includes 15% contingency for unforeseen circumstances.', '2025-10-26 09:58:36'),
(11, 11, 1, 5125000.00, 'pending_client', 'Comprehensive medical clinic renovation with DOH compliance. Budget includes all medical-grade systems, specialized equipment installations, and safety requirements for healthcare facility operation.', '2025-10-28 05:45:00'),
(12, 12, 1, 3280000.00, 'pending_client', 'High-end retail store interior build-out with premium finishes and custom millwork. Budget includes all display systems, lighting design, and luxury finishes appropriate for fashion boutique.', '2025-10-28 05:50:00'),
(13, 13, 1, 5895000.00, 'pending_client', 'Complete renovation of 12 hotel rooms to 4-star standards. Budget includes all furnishings, bathrooms, smart room technology, and acoustic improvements. Work will be phased to minimize guest disruption.', '2025-10-28 05:55:00'),
(14, 13, 1, 5895000.00, 'pending_client', 'Complete renovation of 12 hotel rooms to 4-star standards. Budget includes all furnishings, bathrooms, smart room technology, and acoustic improvements. Work will be phased to minimize guest disruption.', '2025-10-28 12:18:07'),
(15, 12, 1, 3280000.00, 'pending_client', 'High-end retail store interior build-out with premium finishes and custom millwork. Budget includes all display systems, lighting design, and luxury finishes appropriate for fashion boutique.', '2025-10-28 12:18:17'),
(16, 11, 1, 5125000.00, 'pending_client', 'Comprehensive medical clinic renovation with DOH compliance. Budget includes all medical-grade systems, specialized equipment installations, and safety requirements for healthcare facility operation.', '2025-10-28 12:18:27'),
(17, 13, 1, 5895000.00, 'pending_client', 'Complete renovation of 12 hotel rooms to 4-star standards. Budget includes all furnishings, bathrooms, smart room technology, and acoustic improvements. Work will be phased to minimize guest disruption.', '2025-10-28 13:48:01'),
(18, 12, 1, 3280000.00, 'pending_client', 'High-end retail store interior build-out with premium finishes and custom millwork. Budget includes all display systems, lighting design, and luxury finishes appropriate for fashion boutique.', '2025-10-28 14:28:49');

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
(1, 1, 'proposal_submitted', 'Proposal Submitted Successfully', 'Your project proposal \"Commercial Building Renovation - Discaya Office\" has been submitted and is currently under review by our team. We will notify you once the evaluation is complete.', '/client/proposals.php', 0, '2025-10-20 00:35:00'),
(2, 1, 'proposal_submitted', 'New Proposal Submitted', 'Your project proposal \"Two-Story Residential House Construction\" has been submitted successfully. Our team will review it and provide feedback within 3-5 business days.', '/client/proposals.php', 0, '2025-10-22 02:20:00'),
(3, 1, 'proposal_submitted', 'Industrial Warehouse Proposal Received', 'Thank you for submitting \"Industrial Warehouse and Storage Facility\" proposal. Due to the complexity of this industrial project, our engineering team will conduct a thorough review. Expect feedback within 5-7 business days.', '/client/proposals.php', 0, '2025-10-23 06:05:00'),
(4, 1, 'proposal_submitted', 'School Building Proposal Submitted', 'Your educational facility proposal has been received. Our team will coordinate with structural engineers and education compliance experts to ensure the design meets all safety and educational standards. Review timeline: 7-10 business days.', '/client/proposals.php', 0, '2025-10-24 01:35:00'),
(5, 1, 'proposal_submitted', 'Restaurant Complex Proposal Received', 'Your restaurant and cafe complex proposal is now under review. Our team will assess the commercial kitchen requirements and ensure compliance with food service regulations. Expected review time: 4-6 business days.', '/client/proposals.php', 0, '2025-10-24 05:20:00'),
(6, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱6,055,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=5', 0, '2025-10-26 09:49:29'),
(7, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: Modern Restaurant and Cafe Complex', 'proposals_review.php?proposal_id=5', 0, '2025-10-26 09:52:55'),
(8, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱11,930,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=4', 0, '2025-10-26 09:56:50'),
(9, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: Educational Facility - Three-Story School Building', 'proposals_review.php?proposal_id=4', 0, '2025-10-26 09:57:02'),
(10, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱9,015,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=3', 0, '2025-10-26 09:57:20'),
(11, 1, 'budget_review', 'Budget Decision', 'Client has rejected the budget for proposal: Industrial Warehouse and Storage Facility', 'proposals_review.php?proposal_id=3', 0, '2025-10-26 09:57:28'),
(12, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱6,310,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=1', 0, '2025-10-26 09:58:36'),
(13, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: Commercial Building Renovation - Discaya Office', 'proposals_review.php?proposal_id=1', 0, '2025-10-26 09:58:45'),
(14, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱1,300,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=6', 0, '2025-10-26 12:43:22'),
(15, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: Commercial Building in Bonuan', 'proposals_review.php?proposal_id=6', 0, '2025-10-26 12:44:26'),
(16, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱1,221,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=7', 0, '2025-10-27 13:36:08'),
(17, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: Commercial Building Of Upang', 'proposals_review.php?proposal_id=7', 0, '2025-10-27 13:37:10'),
(18, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱12,000,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=8', 0, '2025-10-28 10:30:54'),
(19, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: test', 'proposals_review.php?proposal_id=8', 0, '2025-10-28 11:36:47'),
(20, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱1,234.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=9', 0, '2025-10-28 11:59:46'),
(21, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: test 2', 'proposals_review.php?proposal_id=9', 0, '2025-10-28 12:00:35'),
(22, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱1,234.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=10', 0, '2025-10-28 12:09:04'),
(23, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: test 3', 'proposals_review.php?proposal_id=10', 0, '2025-10-28 12:09:41'),
(24, 1, 'proposal_submitted', 'Medical Clinic Proposal Submitted', 'Your medical clinic renovation proposal has been received. Our team will review the healthcare facility requirements and ensure compliance with DOH standards. Expected review time: 5-7 business days.', '/client/proposals.php', 0, '2025-10-28 05:00:00'),
(25, 1, 'proposal_submitted', 'Retail Store Proposal Submitted', 'Your fashion boutique build-out proposal is now under review. Our design team will assess the interior requirements and premium finish specifications. Expected review time: 3-5 business days.', '/client/proposals.php', 0, '2025-10-28 05:15:00'),
(26, 1, 'proposal_submitted', 'Hotel Renovation Proposal Submitted', 'Your 12-room hotel renovation proposal has been submitted successfully. Our team will evaluate the hospitality standards upgrade and phasing requirements. Expected review time: 4-6 business days.', '/client/proposals.php', 0, '2025-10-28 05:30:00'),
(27, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱5,125,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=11', 0, '2025-10-28 05:45:00'),
(28, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱3,280,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=12', 0, '2025-10-28 05:50:00'),
(29, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱5,895,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=13', 0, '2025-10-28 05:55:00'),
(30, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱5,895,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=13', 0, '2025-10-28 12:18:07'),
(31, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱3,280,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=12', 0, '2025-10-28 12:18:17'),
(32, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱5,125,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=11', 0, '2025-10-28 12:18:27'),
(33, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱5,895,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=13', 0, '2025-10-28 13:48:01'),
(34, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: Boutique Hotel Room Renovation - 12 Rooms', 'proposals_review.php?proposal_id=13', 0, '2025-10-28 13:50:03'),
(35, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: Medical Clinic Renovation and Expansion', 'proposals_review.php?proposal_id=11', 0, '2025-10-28 14:06:58'),
(36, 1, 'budget_review', 'Budget Re-evaluation', 'Your proposal budget has been re-evaluated to ₱3,280,000.00. Please review and accept or cancel.', 'client_budget_review.php?budget_id=12', 0, '2025-10-28 14:28:49'),
(37, 1, 'budget_review', 'Budget Decision', 'Client has approved the budget and a project has been created for: Retail Store Build-out - Fashion Boutique', 'proposals_review.php?proposal_id=12', 0, '2025-10-28 14:30:46');

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
(1, 'Modern Restaurant and Cafe Complex', 'Construction of a two-story restaurant and cafe facility featuring main dining area with 80-seat capacity, private dining rooms, commercial kitchen with modern equipment, outdoor patio seating, cafe area, bar section, storage rooms, and staff facilities. Design emphasizes ambiance, ventilation, and efficient kitchen workflow. Total floor area: 450 square meters.', 'ongoing', 0, 'medium', 1, 1, NULL, '2025-11-20', '2026-06-15', '2025-10-26 09:52:55', '2025-10-26 13:56:33', 6),
(2, 'Educational Facility - Three-Story School Building', 'Construction of a modern three-story school building with 15 classrooms, science laboratories, computer lab, library, administrative offices, faculty room, and multipurpose hall. The design prioritizes natural ventilation, accessibility compliance, and student safety with wide corridors and emergency exits. Total floor area: 2,800 square meters.', 'ongoing', 0, 'medium', 1, 1, NULL, '2026-02-01', '2027-01-30', '2025-10-26 09:57:02', '2025-10-26 13:56:33', 5),
(4, 'Commercial Building Renovation - Discaya Office', 'Complete renovation of 3-story commercial building including structural repairs, electrical system upgrade, plumbing modernization, interior finishing, and HVAC installation. The project aims to transform the existing structure into a modern office space with improved energy efficiency and contemporary design.', 'completed', 100, 'medium', 1, 1, NULL, '2025-11-15', '2026-05-30', '2025-10-26 09:58:45', '2025-10-26 13:56:33', 2),
(6, 'Commercial Building Of Upang', 'Commercial Building Of Upang arellano st dagupan city', 'ongoing', 0, 'medium', 1, 1, NULL, '2025-10-29', '2025-11-05', '2025-10-27 13:37:10', '2025-10-27 13:46:48', 2),
(10, 'Boutique Hotel Room Renovation - 12 Rooms', 'Complete renovation of 12 hotel rooms (300 sqm total) including bathrooms. Each room (25 sqm) features: bedroom area with built-in furniture, modernized bathroom with premium fixtures, new flooring, acoustic insulation, blackout curtains, smart room controls, upgraded electrical outlets with USB ports, improved lighting, fresh paint, and fire safety upgrades. Project aims to upgrade from 3-star to 4-star standard.', 'completed', 100, 'medium', 1, 1, NULL, '2025-10-30', '2025-11-01', '2025-10-28 13:50:03', '2025-10-28 14:31:00', 4),
(11, 'Medical Clinic Renovation and Expansion', 'Complete renovation and expansion of existing 200 sqm medical clinic to 350 sqm. Project includes: creating 6 consultation rooms, minor surgery room, laboratory area, pharmacy section, reception and waiting area, staff room, and storage. Requires medical-grade electrical system, specialized plumbing for medical equipment, HVAC with air filtration, fire safety compliance, and DOH accreditation requirements.', 'ongoing', 0, 'medium', 1, 1, NULL, '2025-11-01', '2025-11-02', '2025-10-28 14:06:58', '2025-10-28 14:06:58', NULL),
(12, 'Retail Store Build-out - Fashion Boutique', 'Complete interior build-out of 180 sqm retail space in commercial building for high-end fashion boutique. Includes: display areas with custom shelving and lighting, fitting rooms (4 units), cashier counter, storage room, staff area, decorative ceiling with integrated lighting, marble flooring, glass storefront, security system, sound system, and modern HVAC. Design emphasizes luxury aesthetic with premium finishes.', 'ongoing', 0, 'medium', 1, 1, NULL, '2025-10-30', '2025-11-01', '2025-10-28 14:30:46', '2025-10-28 14:33:21', 4);

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
(2, 4, 2, '2025-10-26 10:17:06'),
(3, 4, 8, '2025-10-26 10:17:10'),
(6, 2, 5, '2025-10-26 10:17:35'),
(7, 2, 10, '2025-10-26 10:17:39'),
(8, 1, 11, '2025-10-26 10:17:51'),
(9, 1, 6, '2025-10-26 10:17:54'),
(14, 6, 2, '2025-10-27 13:38:23'),
(15, 6, 8, '2025-10-27 13:38:38'),
(16, 6, 9, '2025-10-27 13:38:43'),
(17, 6, 12, '2025-10-27 13:45:28'),
(18, 10, 4, '2025-10-28 13:52:47'),
(19, 10, 13, '2025-10-28 13:52:54'),
(20, 10, 14, '2025-10-28 13:52:56'),
(21, 12, 4, '2025-10-28 14:33:21'),
(22, 12, 13, '2025-10-28 14:33:28'),
(23, 12, 15, '2025-10-28 14:33:30');

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
(1, 1, 4500000.00, 6310000.00, 'approved', 'Initial budget proposal based on preliminary site assessment and client requirements. Includes 15% contingency for unforeseen circumstances.', 'Initial budget proposal based on preliminary site assessment and client requirements. Includes 15% contingency for unforeseen circumstances.', 'approved', '2025-10-20 01:00:00', '2025-10-26 09:58:45', 1),
(2, 2, 3200000.00, 3200000.00, 'approved', 'Budget estimate for complete residential construction including all finishes and fixtures. Excludes furniture and appliances.', 'Budget estimate for complete residential construction including all finishes and fixtures. Excludes furniture and appliances.', NULL, '2025-10-22 02:30:00', '2025-10-26 09:58:21', 1),
(3, 3, 5800000.00, 9015000.00, 'rejected', 'Comprehensive budget for industrial warehouse construction including all structural, electrical, security, and fire safety systems. Built to industrial standards with emphasis on durability and functionality.', 'Comprehensive budget for industrial warehouse construction including all structural, electrical, security, and fire safety systems. Built to industrial standards with emphasis on durability and functionality.', 'rejected', '2025-10-23 06:15:00', '2025-10-26 09:57:28', 1),
(4, 4, 8500000.00, 11930000.00, 'approved', 'Comprehensive educational facility construction following Department of Education standards and building codes. Budget includes specialized educational equipment and safety features required for schools.', 'Comprehensive educational facility construction following Department of Education standards and building codes. Budget includes specialized educational equipment and safety features required for schools.', 'approved', '2025-10-24 01:45:00', '2025-10-26 09:57:02', 1),
(5, 5, 4200000.00, 6055000.00, 'approved', 'Budget for complete restaurant facility including commercial kitchen setup, dining areas, and all required permits for food service establishment. Excludes kitchen equipment and furniture.', 'Budget for complete restaurant facility including commercial kitchen setup, dining areas, and all required permits for food service establishment. Excludes kitchen equipment and furniture.', 'approved', '2025-10-24 05:30:00', '2025-10-26 09:52:55', 1),
(11, 11, 4850000.00, 5125000.00, 'approved', 'Comprehensive medical clinic renovation with DOH compliance. Budget includes all medical-grade systems, specialized equipment installations, and safety requirements for healthcare facility operation.', 'Comprehensive medical clinic renovation with DOH compliance. Budget includes all medical-grade systems, specialized equipment installations, and safety requirements for healthcare facility operation.', 'approved', '2025-10-28 05:45:00', '2025-10-28 14:06:58', 1),
(12, 12, 2950000.00, 3280000.00, 'approved', 'High-end retail store interior build-out with premium finishes and custom millwork. Budget includes all display systems, lighting design, and luxury finishes appropriate for fashion boutique.', 'High-end retail store interior build-out with premium finishes and custom millwork. Budget includes all display systems, lighting design, and luxury finishes appropriate for fashion boutique.', 'approved', '2025-10-28 05:50:00', '2025-10-28 14:30:46', 1),
(13, 13, 5620000.00, 5895000.00, 'approved', 'Complete renovation of 12 hotel rooms to 4-star standards. Budget includes all furnishings, bathrooms, smart room technology, and acoustic improvements. Work will be phased to minimize guest disruption.', 'Complete renovation of 12 hotel rooms to 4-star standards. Budget includes all furnishings, bathrooms, smart room technology, and acoustic improvements. Work will be phased to minimize guest disruption.', 'approved', '2025-10-28 05:55:00', '2025-10-28 13:50:03', 1);

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
(1, 1, 1, 'Commercial Building Renovation - Discaya Office', 'Complete renovation of 3-story commercial building including structural repairs, electrical system upgrade, plumbing modernization, interior finishing, and HVAC installation. The project aims to transform the existing structure into a modern office space with improved energy efficiency and contemporary design.', '2025-11-15', '2026-05-30', NULL, NULL, NULL, 'approved', '2025-10-20 00:30:00', 'approved', '2025-10-26 09:58:45', 4500000.00),
(2, 1, 1, 'Two-Story Residential House Construction', 'Construction of a modern two-story residential house with 4 bedrooms, 3 bathrooms, open-concept living and dining area, modern kitchen, and landscaped garden. The design emphasizes natural lighting, energy efficiency, and sustainable materials. Total floor area: 250 square meters.', '2025-12-01', '2026-09-15', NULL, NULL, NULL, 'approved', '2025-10-22 02:15:00', NULL, NULL, 3200000.00),
(3, 1, 1, 'Industrial Warehouse and Storage Facility', 'Construction of a 1,200 square meter industrial warehouse facility with high-ceiling storage area, loading dock, office space, employee facilities, security systems, and parking area. Designed for logistics and distribution operations with modern fire safety and security features.', '2026-01-10', '2026-08-20', NULL, NULL, NULL, 'rejected', '2025-10-23 06:00:00', 'rejected', '2025-10-26 09:57:28', 5800000.00),
(4, 1, 1, 'Educational Facility - Three-Story School Building', 'Construction of a modern three-story school building with 15 classrooms, science laboratories, computer lab, library, administrative offices, faculty room, and multipurpose hall. The design prioritizes natural ventilation, accessibility compliance, and student safety with wide corridors and emergency exits. Total floor area: 2,800 square meters.', '2026-02-01', '2027-01-30', NULL, NULL, NULL, 'approved', '2025-10-24 01:30:00', 'approved', '2025-10-26 09:57:02', 8500000.00),
(5, 1, 1, 'Modern Restaurant and Cafe Complex', 'Construction of a two-story restaurant and cafe facility featuring main dining area with 80-seat capacity, private dining rooms, commercial kitchen with modern equipment, outdoor patio seating, cafe area, bar section, storage rooms, and staff facilities. Design emphasizes ambiance, ventilation, and efficient kitchen workflow. Total floor area: 450 square meters.', '2025-11-20', '2026-06-15', NULL, NULL, NULL, 'approved', '2025-10-24 05:15:00', 'approved', '2025-10-26 09:52:55', 4200000.00),
(11, NULL, 1, 'Medical Clinic Renovation and Expansion', 'Complete renovation and expansion of existing 200 sqm medical clinic to 350 sqm. Project includes: creating 6 consultation rooms, minor surgery room, laboratory area, pharmacy section, reception and waiting area, staff room, and storage. Requires medical-grade electrical system, specialized plumbing for medical equipment, HVAC with air filtration, fire safety compliance, and DOH accreditation requirements.', '2026-03-01', '2026-08-30', '2025-11-01', '2025-11-02', '', 'approved', '2025-10-28 05:00:00', 'approved', '2025-10-28 14:06:58', 4850000.00),
(12, NULL, 1, 'Retail Store Build-out - Fashion Boutique', 'Complete interior build-out of 180 sqm retail space in commercial building for high-end fashion boutique. Includes: display areas with custom shelving and lighting, fitting rooms (4 units), cashier counter, storage room, staff area, decorative ceiling with integrated lighting, marble flooring, glass storefront, security system, sound system, and modern HVAC. Design emphasizes luxury aesthetic with premium finishes.', '2026-02-15', '2026-05-15', '2025-10-30', '2025-11-01', '', 'approved', '2025-10-28 05:15:00', 'approved', '2025-10-28 14:30:46', 2950000.00),
(13, NULL, 1, 'Boutique Hotel Room Renovation - 12 Rooms', 'Complete renovation of 12 hotel rooms (300 sqm total) including bathrooms. Each room (25 sqm) features: bedroom area with built-in furniture, modernized bathroom with premium fixtures, new flooring, acoustic insulation, blackout curtains, smart room controls, upgraded electrical outlets with USB ports, improved lighting, fresh paint, and fire safety upgrades. Project aims to upgrade from 3-star to 4-star standard.', '2026-04-01', '2026-07-30', '2025-10-30', '2025-11-01', '', 'approved', '2025-10-28 05:30:00', 'approved', '2025-10-28 13:50:03', 5620000.00),
(14, NULL, 1, 'Building Renovation for Upang', 'Building Renovation for Building Renovation for Upang', '2025-10-30', '2025-10-31', NULL, NULL, NULL, 'pending', '2025-10-28 13:38:56', NULL, NULL, NULL);

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
(1, 1, 'Site Survey and Soil Testing', 'Conduct comprehensive site survey and soil testing to determine foundation requirements for the restaurant complex.', 11, 100, '2025-11-25', '2025-10-26 02:30:00', '2025-11-24 06:20:00'),
(2, 1, 'Site Clearing and Excavation', 'Clear the construction site and excavate for foundation work including utilities trenching.', 11, 100, '2025-12-05', '2025-10-26 02:35:00', '2025-12-04 08:45:00'),
(3, 1, 'Foundation Layout and Marking', 'Layout and mark foundation boundaries, column positions, and utility entry points according to architectural plans.', 11, 85, '2025-12-10', '2025-10-26 02:40:00', NULL),
(4, 1, 'Foundation Concrete Pouring', 'Pour reinforced concrete foundation following structural engineering specifications.', 11, 60, '2025-12-20', '2025-10-26 02:45:00', NULL),
(5, 1, 'Ground Floor Column Construction', 'Construct reinforced concrete columns for ground floor structural support.', 11, 40, '2025-12-28', '2025-10-26 02:50:00', NULL),
(6, 1, 'First Floor Slab Installation', 'Install first floor concrete slab and beam system.', 11, 20, '2026-01-10', '2025-10-26 02:55:00', NULL),
(7, 1, 'Second Floor Structure', 'Complete second floor structural framework and roofing preparation.', 11, 0, '2026-01-25', '2025-10-26 03:00:00', NULL),
(8, 1, 'Electrical Rough-in Installation', 'Install electrical conduits, junction boxes, and main panel connections throughout the building.', 11, 0, '2026-02-05', '2025-10-26 03:05:00', NULL),
(9, 1, 'Plumbing System Installation', 'Install water supply lines, drainage pipes, and gas lines for kitchen equipment.', 11, 0, '2026-02-15', '2025-10-26 03:10:00', NULL),
(10, 1, 'HVAC Ductwork Installation', 'Install HVAC ductwork system including kitchen exhaust and dining area climate control.', 11, 0, '2026-02-25', '2025-10-26 03:15:00', NULL),
(11, 1, 'Kitchen Area Waterproofing', 'Apply waterproofing to kitchen floor and walls to prevent water damage.', 11, 0, '2026-03-05', '2025-10-26 03:20:00', NULL),
(12, 1, 'Interior Wall Partitioning', 'Construct interior partition walls for dining rooms, kitchen, storage, and staff areas.', 11, 0, '2026-03-15', '2025-10-26 03:25:00', NULL),
(13, 1, 'Commercial Kitchen Preparation', 'Prepare commercial kitchen area for equipment installation including gas lines and ventilation.', 11, 0, '2026-03-25', '2025-10-26 03:30:00', NULL),
(14, 1, 'Flooring Installation', 'Install commercial-grade flooring throughout dining areas and kitchen (non-slip tiles).', 11, 0, '2026-04-05', '2025-10-26 03:35:00', NULL),
(15, 1, 'Ceiling and Lighting Installation', 'Install false ceiling and lighting fixtures in dining areas and kitchen.', 11, 0, '2026-04-15', '2025-10-26 03:40:00', NULL),
(16, 1, 'Interior Painting', 'Paint all interior walls, ceilings, and finishes according to design specifications.', 11, 0, '2026-04-25', '2025-10-26 03:45:00', NULL),
(17, 1, 'Glass Wall and Window Installation', 'Install glass walls, windows, and entrance doors for natural lighting and aesthetic appeal.', 11, 0, '2026-05-05', '2025-10-26 03:50:00', NULL),
(18, 1, 'Outdoor Patio Construction', 'Construct outdoor patio seating area with weather-resistant flooring and fixtures.', 11, 0, '2026-05-15', '2025-10-26 03:55:00', NULL),
(19, 1, 'Fire Safety System Testing', 'Install and test fire suppression system, smoke detectors, and emergency exits.', 11, 0, '2026-05-25', '2025-10-26 04:00:00', NULL),
(20, 1, 'Final Inspection and Handover', 'Conduct final walkthrough, address punch list items, and prepare for health department inspection.', 6, 0, '2026-06-10', '2025-10-26 04:05:00', NULL),
(21, 2, 'DepEd Compliance Documentation', 'Prepare and submit all required documentation for Department of Education compliance and approval.', 5, 100, '2026-02-10', '2025-10-26 04:10:00', '2026-02-08 02:30:00'),
(22, 2, 'Site Mobilization', 'Set up site office, storage facilities, and construction equipment for school building project.', 10, 100, '2026-02-15', '2025-10-26 04:15:00', '2026-02-14 07:45:00'),
(23, 2, 'Foundation Excavation', 'Excavate for three-story building foundation including utility trenching and soil compaction.', 10, 75, '2026-02-28', '2025-10-26 04:20:00', NULL),
(24, 2, 'Foundation and Basement Construction', 'Construct reinforced concrete foundation with proper drainage system for three-story building.', 10, 50, '2026-03-15', '2025-10-26 04:25:00', NULL),
(25, 2, 'Ground Floor Column Installation', 'Install ground floor columns and beam system to support upper floors.', 10, 30, '2026-03-30', '2025-10-26 04:30:00', NULL),
(26, 2, 'Ground Floor Slab Casting', 'Cast ground floor concrete slab with proper curing and finishing.', 10, 15, '2026-04-10', '2025-10-26 04:35:00', NULL),
(27, 2, 'Second Floor Structure', 'Construct second floor columns, beams, and slab system.', 10, 0, '2026-05-05', '2025-10-26 04:40:00', NULL),
(28, 2, 'Third Floor Structure', 'Construct third floor columns, beams, and slab system.', 10, 0, '2026-05-30', '2025-10-26 04:45:00', NULL),
(29, 2, 'Roofing System Installation', 'Install roofing system with proper insulation and waterproofing for school building.', 10, 0, '2026-06-20', '2025-10-26 04:50:00', NULL),
(30, 2, 'Exterior Wall Construction', 'Build exterior walls with proper insulation and finish materials.', 10, 0, '2026-07-10', '2025-10-26 04:55:00', NULL),
(31, 2, 'Classroom Partition Walls', 'Construct partition walls for 15 classrooms with soundproofing considerations.', 10, 0, '2026-08-05', '2025-10-26 05:00:00', NULL),
(32, 2, 'Stairwell and Corridor Construction', 'Build wide corridors and emergency stairwells according to safety standards.', 10, 0, '2026-08-20', '2025-10-26 05:05:00', NULL),
(33, 2, 'Electrical System Installation', 'Install complete electrical system including backup power for emergency lighting.', 10, 0, '2026-09-10', '2025-10-26 05:10:00', NULL),
(34, 2, 'Plumbing and Sanitary Facilities', 'Install plumbing system and construct student toilet facilities on each floor.', 10, 0, '2026-09-25', '2025-10-26 05:15:00', NULL),
(35, 2, 'Natural Ventilation System', 'Install ventilation windows and systems for natural air circulation in classrooms.', 10, 0, '2026-10-10', '2025-10-26 05:20:00', NULL),
(36, 2, 'Science Laboratory Setup', 'Prepare science laboratories with specialized plumbing, electrical, and safety features.', 10, 0, '2026-10-25', '2025-10-26 05:25:00', NULL),
(37, 2, 'Computer Lab Infrastructure', 'Install computer lab infrastructure including electrical outlets and network cabling.', 10, 0, '2026-11-05', '2025-10-26 05:30:00', NULL),
(38, 2, 'Library Construction', 'Construct library area with built-in shelving and reading spaces.', 10, 0, '2026-11-15', '2025-10-26 05:35:00', NULL),
(39, 2, 'Multipurpose Hall', 'Complete multipurpose hall with stage area and storage facilities.', 10, 0, '2026-11-25', '2025-10-26 05:40:00', NULL),
(40, 2, 'Non-slip Flooring Installation', 'Install non-slip flooring throughout the building for student safety.', 10, 0, '2026-12-05', '2025-10-26 05:45:00', NULL),
(41, 2, 'Accessibility Ramps and Rails', 'Install accessibility ramps and handrails throughout the building for PWD compliance.', 10, 0, '2026-12-15', '2025-10-26 05:50:00', NULL),
(42, 2, 'Emergency Exit Signage', 'Install emergency exit signs, fire alarms, and evacuation route markings.', 10, 0, '2026-12-25', '2025-10-26 05:55:00', NULL),
(43, 2, 'Playground Area Development', 'Develop playground area with landscaping and safety surfaces.', 10, 0, '2027-01-10', '2025-10-26 06:00:00', NULL),
(44, 2, 'Final DepEd Inspection', 'Coordinate final inspection with DepEd officials for school operation permit.', 5, 0, '2027-01-25', '2025-10-26 06:05:00', NULL),
(70, 4, 'Structural Assessment', 'Conduct comprehensive structural assessment of existing 3-story building to identify repair needs.', 8, 100, '2025-11-20', '2025-10-26 08:15:00', '2025-11-19 06:30:00'),
(71, 4, 'Asbestos and Hazmat Testing', 'Test for asbestos and hazardous materials in existing building materials.', 8, 100, '2025-11-25', '2025-10-26 08:20:00', '2025-11-24 08:00:00'),
(72, 4, 'Demolition Planning', 'Plan selective demolition of interior elements while preserving structure.', 8, 100, '2025-11-28', '2025-10-26 08:25:00', '2025-10-26 06:00:05'),
(73, 4, 'Interior Demolition', 'Carefully demolish outdated interior finishes, fixtures, and non-structural walls.', 8, 100, '2025-12-10', '2025-10-26 08:30:00', '2025-10-26 03:21:56'),
(74, 4, 'Debris Removal', 'Remove all demolition debris and prepare site for renovation work.', 8, 100, '2025-12-15', '2025-10-26 08:35:00', '2025-10-26 03:36:14'),
(75, 4, 'Structural Repairs', 'Repair and reinforce structural elements including columns, beams, and slabs.', 8, 100, '2025-12-28', '2025-10-26 08:40:00', '2025-10-26 03:36:27'),
(76, 4, 'Electrical System Upgrade', 'Upgrade entire electrical system with new panels, wiring, and modern fixtures.', 8, 100, '2026-01-15', '2025-10-26 08:45:00', '2025-10-26 03:36:35'),
(77, 4, 'Plumbing Modernization', 'Replace old plumbing system with modern pipes and fixtures throughout building.', 8, 100, '2026-01-30', '2025-10-26 08:50:00', '2025-10-26 03:36:40'),
(78, 4, 'HVAC System Installation', 'Install new central HVAC system for improved climate control and energy efficiency.', 8, 100, '2026-02-15', '2025-10-26 08:55:00', '2025-10-26 03:49:29'),
(79, 4, 'New Partition Walls', 'Construct new partition walls for modern office layout with open-concept areas.', 8, 100, '2026-03-01', '2025-10-26 09:00:00', '2025-10-26 03:49:31'),
(80, 4, 'Ceiling Installation', 'Install suspended ceiling system with integrated lighting throughout all floors.', 8, 100, '2026-03-15', '2025-10-26 09:05:00', '2025-10-26 03:49:33'),
(81, 4, 'Floor Refinishing', 'Install new flooring materials including tiles and hardwood in office spaces.', 8, 100, '2026-03-30', '2025-10-26 09:10:00', '2025-10-26 03:49:36'),
(82, 4, 'Window Replacement', 'Replace old windows with energy-efficient double-pane windows.', 8, 100, '2026-04-10', '2025-10-26 09:15:00', '2025-10-26 03:49:38'),
(83, 4, 'Interior Painting', 'Paint all interior walls with modern color scheme appropriate for office environment.', 8, 100, '2026-04-25', '2025-10-26 09:20:00', '2025-10-26 03:49:42'),
(84, 4, 'Door Installation', 'Install new interior and exterior doors with modern hardware.', 8, 100, '2026-05-05', '2025-10-26 09:25:00', '2025-10-26 03:49:45'),
(85, 4, 'Lighting Fixture Installation', 'Install modern LED lighting fixtures throughout the building.', 8, 100, '2026-05-10', '2025-10-26 09:30:00', '2025-10-26 03:49:48'),
(86, 4, 'Fire Safety System Upgrade', 'Upgrade fire alarm system, sprinklers, and emergency lighting to current codes.', 8, 100, '2026-05-15', '2025-10-26 09:35:00', '2025-10-26 03:49:51'),
(87, 4, 'Final Inspection and Testing', 'Conduct final inspections, test all systems, and obtain occupancy certificate.', 8, 100, '2026-05-25', '2025-10-26 09:40:00', '2025-10-26 06:00:11'),
(88, 4, 'Client Walkthrough', 'Conduct final walkthrough with client and address any remaining items.', 8, 100, '2026-05-28', '2025-10-26 09:45:00', '2025-10-26 06:00:15'),
(90, 6, 'mag alay ng bata sa pundasyon', 'mag alay ng bata sa pundasyon', 8, 70, '2025-10-31', '2025-10-27 13:40:59', NULL),
(91, 10, 'Groundbreaking', 'Groundbreaking', 14, 100, '2025-10-31', '2025-10-28 13:53:54', '2025-10-28 06:59:17'),
(92, 10, 'gawin yung hagdan', 'gawin yung hagdan', 14, 100, '2025-12-21', '2025-10-28 13:56:24', '2025-10-28 06:59:20');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=407;

--
-- AUTO_INCREMENT for table `budget_reviews`
--
ALTER TABLE `budget_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `project_budgets`
--
ALTER TABLE `project_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `project_proposals`
--
ALTER TABLE `project_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

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
