-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Nov 08, 2019 at 02:41 PM
-- Server version: 8.0.16
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tvguide`
--

-- --------------------------------------------------------

--
-- Table structure for table `channel`
--

CREATE TABLE `channel` (
                           `id` int(10) UNSIGNED NOT NULL,
                           `data_id` varchar(64) NOT NULL,
                           `name` varchar(64) NOT NULL,
                           `url_name` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                           `order` tinyint(3) UNSIGNED NOT NULL DEFAULT '255',
                           `allowed` bit(1) NOT NULL DEFAULT b'0',
                           `radio` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE `group` (
                         `id` int(10) UNSIGNED NOT NULL,
                         `name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_channel`
--

CREATE TABLE `group_channel` (
                                 `group_id` int(10) UNSIGNED NOT NULL,
                                 `channel_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

CREATE TABLE `image` (
                         `id` int(10) UNSIGNED NOT NULL,
                         `name` binary(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
                           `id` int(10) UNSIGNED NOT NULL,
                           `title` varchar(256) NOT NULL,
                           `description` varchar(6000) NOT NULL,
                           `start_time` datetime NOT NULL,
                           `end_time` datetime NOT NULL,
                           `channel_id` int(10) UNSIGNED NOT NULL,
                           `season` int(10) UNSIGNED DEFAULT NULL,
                           `episode` int(10) UNSIGNED DEFAULT NULL,
                           `episodes` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `channel`
--
ALTER TABLE `channel`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `name` (`name`),
    ADD UNIQUE KEY `data_id` (`data_id`);

--
-- Indexes for table `group`
--
ALTER TABLE `group`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group_channel`
--
ALTER TABLE `group_channel`
    ADD KEY `fk_group_channel_channel_id` (`channel_id`),
    ADD KEY `fk_group_channel_group_id` (`group_id`);

--
-- Indexes for table `image`
--
ALTER TABLE `image`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `program`
--
ALTER TABLE `program`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `title` (`title`,`start_time`,`end_time`,`channel_id`),
    ADD KEY `start_time` (`start_time`),
    ADD KEY `fk_program_channel_id` (`channel_id`),
    ADD KEY `start_time_2` (`start_time`,`channel_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `channel`
--
ALTER TABLE `channel`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group`
--
ALTER TABLE `group`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `image`
--
ALTER TABLE `image`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `program`
--
ALTER TABLE `program`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `group_channel`
--
ALTER TABLE `group_channel`
    ADD CONSTRAINT `fk_group_channel_channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_group_channel_group_id` FOREIGN KEY (`group_id`) REFERENCES `group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `program`
--
ALTER TABLE `program`
    ADD CONSTRAINT `fk_program_channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
