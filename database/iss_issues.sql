-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 26, 2025 at 04:36 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cis355_final`
--

-- --------------------------------------------------------

--
-- Table structure for table `iss_issues`
--

CREATE TABLE `iss_issues` (
  `id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `short_description` varchar(255) NOT NULL,
  `long_description` text NOT NULL,
  `open_date` date NOT NULL,
  `close_date` date NOT NULL,
  `priority` varchar(255) NOT NULL,
  `org` varchar(255) NOT NULL,
  `project` varchar(255) NOT NULL,
  `per_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iss_issues`
--

INSERT INTO `iss_issues` (`id`, `created_by`, `short_description`, `long_description`, `open_date`, `close_date`, `priority`, `org`, `project`, `per_id`) VALUES
(63, 2, '2', 'nuz', '2025-04-25', '0000-00-00', 'High', 'cis355', 'final', 4),
(64, 2, '2.a', 'nuz', '2025-04-25', '0000-00-00', 'Medium', 'cis355', 'final', 5),
(66, 3, '3', 'nazifa', '2025-04-25', '0000-00-00', 'High', 'cis355', 'final', 4),
(67, 3, '3.a', 'nazifa', '2025-04-25', '0000-00-00', 'Low', 'cis355', 'final', 5),
(68, 4, '4', 'joey', '2025-04-25', '0000-00-00', 'High', 'cis355', 'final', 4),
(69, 4, '4.a', 'joey', '2025-04-25', '0000-00-00', 'High', 'cis355', 'final', 5),
(70, 5, '5', 'chandler', '2025-04-25', '0000-00-00', 'High', 'cis355', 'final', 5),
(71, 5, '5.a', 'chandler', '2025-04-25', '0000-00-00', 'High', 'cis355', 'final', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `iss_issues`
--
ALTER TABLE `iss_issues`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `iss_issues`
--
ALTER TABLE `iss_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
