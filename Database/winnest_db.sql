-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Jun 18, 2026 at 08:30 PM
-- Server version: 12.0.2-MariaDB-ubu2404
-- PHP Version: 8.2.29

CREATE DATABASE IF NOT EXISTS `winnest_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `winnest_db`;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `winnest_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `breeding_pair`
--

CREATE TABLE `breeding_pair` (
  `id` int(11) NOT NULL,
  `loft_id` int(11) NOT NULL,
  `sire_id` int(11) DEFAULT NULL,
  `dam_id` int(11) DEFAULT NULL,
  `pairing_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `breeding_pair`
--

INSERT INTO `breeding_pair` (`id`, `loft_id`, `sire_id`, `dam_id`, `pairing_date`, `is_active`, `notes`) VALUES
(1, 2, 1, 2, '2026-06-10', 1, 'All is good');

-- --------------------------------------------------------

--
-- Table structure for table `breeding_record`
--

CREATE TABLE `breeding_record` (
  `id` int(11) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `nest_id` int(11) DEFAULT NULL,
  `breeding_round` varchar(100) DEFAULT NULL,
  `season_name` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `breeding_record`
--

INSERT INTO `breeding_record` (`id`, `pair_id`, `nest_id`, `breeding_round`, `season_name`, `start_date`, `notes`) VALUES
(1, 1, 1, 'Round 1', NULL, '2026-06-15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `egg`
--

CREATE TABLE `egg` (
  `id` int(11) NOT NULL,
  `breeding_record_id` int(11) NOT NULL,
  `egg_number` varchar(50) DEFAULT NULL,
  `lay_date` date DEFAULT NULL,
  `expected_hatch_date` date DEFAULT NULL,
  `actual_hatch_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Incubating',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `egg`
--

INSERT INTO `egg` (`id`, `breeding_record_id`, `egg_number`, `lay_date`, `expected_hatch_date`, `actual_hatch_date`, `status`, `notes`) VALUES
(1, 1, '1st Egg', '2026-06-15', '2026-07-03', NULL, 'Incubating', 'All good.');

-- --------------------------------------------------------

--
-- Table structure for table `health_record`
--

CREATE TABLE `health_record` (
  `id` int(11) NOT NULL,
  `pigeon_id` int(11) NOT NULL,
  `checkup_date` date DEFAULT NULL,
  `condition_name` varchar(255) DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loft`
--

CREATE TABLE `loft` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `loft_name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loft`
--

INSERT INTO `loft` (`id`, `user_id`, `loft_name`, `address`, `country`) VALUES
(1, 1, 'Sunrise Loft', '12 Pigeon Lane', 'Netherlands'),
(2, 1, 'North Loft', '45 Dovecote Rd', 'Netherlands');

-- --------------------------------------------------------

--
-- Table structure for table `nest`
--

CREATE TABLE `nest` (
  `id` int(11) NOT NULL,
  `loft_id` int(11) NOT NULL,
  `nest_number` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nest`
--

INSERT INTO `nest` (`id`, `loft_id`, `nest_number`, `status`) VALUES
(1, 1, 'N-01', 'available'),
(2, 1, 'N-02', 'available'),
(3, 1, 'N-03', 'available'),
(4, 2, 'N-01', 'available'),
(5, 2, 'N-02', 'available'),
(6, 2, 'N-03', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `pigeon`
--

CREATE TABLE `pigeon` (
  `id` int(11) NOT NULL,
  `loft_id` int(11) NOT NULL,
  `hatched_from_egg_id` int(11) DEFAULT NULL,
  `band_number` varchar(100) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sex` enum('Male','Female','Unknown') NOT NULL DEFAULT 'Unknown',
  `bloodline` varchar(255) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `is_youngster` tinyint(1) NOT NULL DEFAULT 0,
  `date_of_birth` date DEFAULT NULL,
  `photo_url` varchar(512) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pigeon`
--

INSERT INTO `pigeon` (`id`, `loft_id`, `hatched_from_egg_id`, `band_number`, `name`, `sex`, `bloodline`, `color`, `status`, `is_youngster`, `date_of_birth`, `photo_url`, `notes`) VALUES
(1, 2, NULL, 'NL 36823847', 'Blue', 'Male', 'Koopman', 'Blue (Blauw)', 'Active', 0, NULL, NULL, NULL),
(2, 2, NULL, 'NL 3680906', 'Pink', 'Female', 'Koopman', 'Black (Donker)', 'Active', 0, NULL, NULL, NULL),
(3, 2, 1, 'NL-3284-3297', 'Blink', 'Male', 'Koopman', 'Black', 'Keep as breeder', 1, '2026-06-17', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `race_performance`
--

CREATE TABLE `race_performance` (
  `id` int(11) NOT NULL,
  `pigeon_id` int(11) NOT NULL,
  `race_name` varchar(255) NOT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `race_date` date DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `placement` int(11) DEFAULT NULL,
  `total_birds_in_race` int(11) DEFAULT NULL,
  `speed_mpm` decimal(8,2) DEFAULT NULL,
  `weather_conditions` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'owner',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `full_name`, `email`, `role`, `created_at`) VALUES
(1, 'John Doe', 'john@example.com', 'owner', '2026-06-18 12:10:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `breeding_pair`
--
ALTER TABLE `breeding_pair`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loft_id` (`loft_id`),
  ADD KEY `sire_id` (`sire_id`),
  ADD KEY `dam_id` (`dam_id`);

--
-- Indexes for table `breeding_record`
--
ALTER TABLE `breeding_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pair_id` (`pair_id`),
  ADD KEY `nest_id` (`nest_id`);

--
-- Indexes for table `egg`
--
ALTER TABLE `egg`
  ADD PRIMARY KEY (`id`),
  ADD KEY `breeding_record_id` (`breeding_record_id`);

--
-- Indexes for table `health_record`
--
ALTER TABLE `health_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pigeon_id` (`pigeon_id`);

--
-- Indexes for table `loft`
--
ALTER TABLE `loft`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `nest`
--
ALTER TABLE `nest`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loft_id` (`loft_id`);

--
-- Indexes for table `pigeon`
--
ALTER TABLE `pigeon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loft_id` (`loft_id`),
  ADD KEY `fk_pigeon_egg` (`hatched_from_egg_id`);

--
-- Indexes for table `race_performance`
--
ALTER TABLE `race_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pigeon_id` (`pigeon_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `breeding_pair`
--
ALTER TABLE `breeding_pair`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `breeding_record`
--
ALTER TABLE `breeding_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `egg`
--
ALTER TABLE `egg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `health_record`
--
ALTER TABLE `health_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loft`
--
ALTER TABLE `loft`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `nest`
--
ALTER TABLE `nest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pigeon`
--
ALTER TABLE `pigeon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `race_performance`
--
ALTER TABLE `race_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `breeding_pair`
--
ALTER TABLE `breeding_pair`
  ADD CONSTRAINT `breeding_pair_ibfk_1` FOREIGN KEY (`loft_id`) REFERENCES `loft` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `breeding_pair_ibfk_2` FOREIGN KEY (`sire_id`) REFERENCES `pigeon` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `breeding_pair_ibfk_3` FOREIGN KEY (`dam_id`) REFERENCES `pigeon` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `breeding_record`
--
ALTER TABLE `breeding_record`
  ADD CONSTRAINT `breeding_record_ibfk_1` FOREIGN KEY (`pair_id`) REFERENCES `breeding_pair` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `breeding_record_ibfk_2` FOREIGN KEY (`nest_id`) REFERENCES `nest` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `egg`
--
ALTER TABLE `egg`
  ADD CONSTRAINT `egg_ibfk_1` FOREIGN KEY (`breeding_record_id`) REFERENCES `breeding_record` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_record`
--
ALTER TABLE `health_record`
  ADD CONSTRAINT `health_record_ibfk_1` FOREIGN KEY (`pigeon_id`) REFERENCES `pigeon` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loft`
--
ALTER TABLE `loft`
  ADD CONSTRAINT `loft_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nest`
--
ALTER TABLE `nest`
  ADD CONSTRAINT `nest_ibfk_1` FOREIGN KEY (`loft_id`) REFERENCES `loft` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pigeon`
--
ALTER TABLE `pigeon`
  ADD CONSTRAINT `fk_pigeon_egg` FOREIGN KEY (`hatched_from_egg_id`) REFERENCES `egg` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pigeon_ibfk_1` FOREIGN KEY (`loft_id`) REFERENCES `loft` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `race_performance`
--
ALTER TABLE `race_performance`
  ADD CONSTRAINT `race_performance_ibfk_1` FOREIGN KEY (`pigeon_id`) REFERENCES `pigeon` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
