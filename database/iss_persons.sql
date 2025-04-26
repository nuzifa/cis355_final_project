-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 26, 2025 at 04:30 AM
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
-- Table structure for table `iss_persons`
--

CREATE TABLE `iss_persons` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `pwd_hash` varchar(255) NOT NULL,
  `pwd_salt` varchar(255) NOT NULL,
  `admin` varchar(255) NOT NULL,
  `verify_token` varchar(255) DEFAULT NULL,
  `verified` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iss_persons`
--

INSERT INTO `iss_persons` (`id`, `fname`, `lname`, `mobile`, `email`, `pwd_hash`, `pwd_salt`, `admin`, `verify_token`, `verified`) VALUES
(1, 'George', 'Corser', '989-780-3168', 'gpcorser@svsu.edu', '0532493a0cb21d6ec931886d1becded2', 'splyxxy', 'Y', NULL, 1),
(2, 'nuz', 'zie', '111-111-1111', 'nuz@gmail.com', '0f7f67e9da7ce8974dabc887671bd522', '12345678', 'Y', NULL, 1),
(3, 'nazifa', 'naz', '111-111-1111', 'naz@gmail.com', '0f7f67e9da7ce8974dabc887671bd522', '12345678', 'N', NULL, 1),
(4, 'joey', 'trib', '123-456-7890', 'jt@gmail.com', '0f7f67e9da7ce8974dabc887671bd522', '12345678', 'N', NULL, 1),
(5, 'chanandler', 'b', '123-456-7890', 'cb@gmail.com', '0f7f67e9da7ce8974dabc887671bd522', '12345678', 'N', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `iss_persons`
--
ALTER TABLE `iss_persons`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `iss_persons`
--
ALTER TABLE `iss_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
