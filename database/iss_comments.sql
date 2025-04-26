-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 26, 2025 at 04:35 AM
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
-- Table structure for table `iss_comments`
--

CREATE TABLE `iss_comments` (
  `id` int(11) NOT NULL,
  `per_id` int(11) NOT NULL,
  `iss_id` int(11) NOT NULL,
  `short_comment` varchar(255) NOT NULL,
  `long_comment` text NOT NULL,
  `posted_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iss_comments`
--

INSERT INTO `iss_comments` (`id`, `per_id`, `iss_id`, `short_comment`, `long_comment`, `posted_date`) VALUES
(141, 2, 63, '2', 'nuz', '2025-04-25 19:26:14'),
(142, 2, 64, '2.a', 'nuz', '2025-04-25 19:27:25'),
(143, 3, 66, '3', 'nazifa', '2025-04-25 19:32:18'),
(144, 3, 67, '3.a', 'nazifa', '2025-04-25 19:32:32'),
(145, 4, 68, '4', 'joey', '2025-04-25 19:37:19'),
(146, 4, 69, '4.a', 'joey', '2025-04-25 19:38:31'),
(147, 5, 70, '5', 'chandler', '2025-04-25 19:49:50'),
(148, 5, 71, '5.a', 'chandler', '2025-04-25 19:50:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `iss_comments`
--
ALTER TABLE `iss_comments`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `iss_comments`
--
ALTER TABLE `iss_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
