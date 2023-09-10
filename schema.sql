-- MySQL dump 10.13  Distrib 8.0.20, for Linux (x86_64)
--
-- Host: localhost    Database: ylilauta
-- ------------------------------------------------------
-- Server version	8.0.20-0ubuntu0.20.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_announcement`
--

DROP TABLE IF EXISTS `admin_announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_announcement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `text` varchar(12000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `edited` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `position` tinyint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_log`
--

DROP TABLE IF EXISTS `admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action_id` smallint unsigned NOT NULL,
  `post_id` int unsigned DEFAULT NULL,
  `custom_info` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varbinary(16) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=649287 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ads`
--

DROP TABLE IF EXISTS `ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ads` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `position_id` varchar(16) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `areas` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `not_areas` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  `html` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `disabled` (`disabled`)
) ENGINE=InnoDB AUTO_INCREMENT=248 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `antispam`
--

DROP TABLE IF EXISTS `antispam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `antispam` (
  `enabled` bit(1) NOT NULL,
  `enabled_time` timestamp NOT NULL,
  KEY `enabled_time` (`enabled_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ban_bot_ip`
--

DROP TABLE IF EXISTS `ban_bot_ip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ban_bot_ip` (
  `ip` varbinary(16) NOT NULL,
  `first_seen` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `hits` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`ip`),
  KEY `time` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ban_ip`
--

DROP TABLE IF EXISTS `ban_ip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ban_ip` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ban_id` int unsigned DEFAULT NULL,
  `ip` varbinary(16) NOT NULL,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires` datetime DEFAULT NULL,
  `added_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`),
  KEY `added_by` (`added_by`),
  KEY `time` (`time`),
  KEY `expires` (`expires`),
  KEY `ban_id` (`ban_id`),
  CONSTRAINT `ban_ip_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ban_ip_ibfk_3` FOREIGN KEY (`ban_id`) REFERENCES `user_ban` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49416 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `board`
--

DROP TABLE IF EXISTS `board`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_sv_0900_ai_ci NOT NULL,
  `description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_locked` bit(1) NOT NULL DEFAULT b'0',
  `is_hidden` bit(1) NOT NULL DEFAULT b'0',
  `inactive_hours_lock` smallint unsigned DEFAULT NULL,
  `inactive_hours_delete` smallint unsigned DEFAULT NULL,
  `show_flags` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `boardurl` (`url`),
  KEY `isHidden` (`is_hidden`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `file`
--

DROP TABLE IF EXISTS `file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `file` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `extension` enum('jpg','png','mp4','m4a') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `duration` int unsigned DEFAULT NULL,
  `has_sound` bit(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16952798 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `file_delete` AFTER DELETE ON `file` FOR EACH ROW BEGIN
	INSERT IGNORE INTO file_deleted (id, extension) VALUES (old.id, old.extension);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `file_deleted`
--

DROP TABLE IF EXISTS `file_deleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_deleted` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `extension` enum('jpg','png','mp4','m4a') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `file_md5`
--

DROP TABLE IF EXISTS `file_md5`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_md5` (
  `file_id` int unsigned NOT NULL,
  `md5` binary(16) NOT NULL,
  PRIMARY KEY (`file_id`,`md5`),
  KEY `md5` (`md5`),
  CONSTRAINT `file_md5_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `file_processing`
--

DROP TABLE IF EXISTS `file_processing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_processing` (
  `file_id` int unsigned NOT NULL,
  PRIMARY KEY (`file_id`),
  CONSTRAINT `file_processing_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gold_key`
--

DROP TABLE IF EXISTS `gold_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gold_key` (
  `key` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `generated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `length` int unsigned NOT NULL DEFAULT '0',
  `is_donated` bit(1) NOT NULL DEFAULT b'0',
  `owner_id` int unsigned DEFAULT NULL,
  `is_used` bit(1) NOT NULL DEFAULT b'0',
  `used_by` int unsigned DEFAULT NULL,
  `used_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`),
  KEY `used` (`is_used`),
  KEY `used_by` (`used_by`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `gold_key_ibfk_1` FOREIGN KEY (`used_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `gold_key_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gold_key_donate`
--

DROP TABLE IF EXISTS `gold_key_donate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gold_key_donate` (
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gold_key` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `old_owner_id` int unsigned DEFAULT NULL,
  `new_owner_id` int unsigned DEFAULT NULL,
  `post_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`timestamp`,`gold_key`),
  KEY `gold_key` (`gold_key`),
  KEY `old_owner_id` (`old_owner_id`),
  KEY `new_owner_id` (`new_owner_id`),
  CONSTRAINT `gold_key_donate_ibfk_1` FOREIGN KEY (`gold_key`) REFERENCES `gold_key` (`key`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `gold_key_donate_ibfk_2` FOREIGN KEY (`old_owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `gold_key_donate_ibfk_3` FOREIGN KEY (`new_owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gold_order`
--

DROP TABLE IF EXISTS `gold_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gold_order` (
  `order_number` char(22) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int unsigned DEFAULT NULL,
  `product_id` tinyint unsigned NOT NULL,
  `quantity` smallint unsigned NOT NULL DEFAULT '1',
  `verified` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`order_number`),
  KEY `user_id` (`user_id`),
  KEY `timestamp` (`verified`,`timestamp`) USING BTREE,
  CONSTRAINT `gold_order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gold_order_key`
--

DROP TABLE IF EXISTS `gold_order_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gold_order_key` (
  `order_number` char(22) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `key` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`order_number`,`key`),
  KEY `gold_orders_keys_ibfk_2` (`key`),
  CONSTRAINT `gold_order_key_ibfk_2` FOREIGN KEY (`key`) REFERENCES `gold_key` (`key`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `gold_order_key_ibfk_3` FOREIGN KEY (`order_number`) REFERENCES `gold_order` (`order_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `info_category`
--

DROP TABLE IF EXISTS `info_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `info_category` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `url` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `position` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `url` (`url`),
  KEY `order` (`position`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `info_content`
--

DROP TABLE IF EXISTS `info_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `info_content` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `text` varchar(16000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_html` bit(1) NOT NULL DEFAULT b'0',
  `added_by` int unsigned NOT NULL,
  `time_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_by` int unsigned DEFAULT NULL,
  `time_edited` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `category_id` tinyint unsigned DEFAULT NULL,
  `position` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category_id`),
  CONSTRAINT `info_content_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `info_category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT COMMENT='Posts in front page (rules, faq etc)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `materialized_view`
--

DROP TABLE IF EXISTS `materialized_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materialized_view` (
  `view_key` varchar(60) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `view_value` varchar(16000) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  PRIMARY KEY (`view_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post`
--

DROP TABLE IF EXISTS `post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `thread_id` int unsigned NOT NULL,
  `upvote_count` smallint unsigned NOT NULL DEFAULT '0',
  `ip` varbinary(16) NOT NULL,
  `remote_port` smallint unsigned NOT NULL DEFAULT '0',
  `country_code` char(2) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `public_user_id` smallint unsigned NOT NULL DEFAULT '0',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci NOT NULL,
  `op_post` bit(1) NOT NULL DEFAULT b'0',
  `admin_post` bit(1) NOT NULL DEFAULT b'0',
  `gold_hide` bit(1) NOT NULL DEFAULT b'0',
  `gold_get` tinyint unsigned NOT NULL DEFAULT '0',
  `edited` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `latest_post_time` (`thread_id`,`time` DESC) USING BTREE,
  KEY `user_id` (`user_id`,`thread_id`),
  KEY `thread_newest_replies` (`thread_id`,`op_post`,`id` DESC) COMMENT 'Used for getting latest replies for a thread',
  CONSTRAINT `post_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `thread` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=128609740 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`debian-sys-maint`@`localhost`*/ /*!50003 TRIGGER `post_delete_before` BEFORE DELETE ON `post` FOR EACH ROW BEGIN
    
DELETE FROM post_file WHERE post_id = old.id;

    
INSERT IGNORE INTO post_deleted (id, user_id, thread_id, ip, remote_port, time, message)
VALUES (old.id, old.user_id, old.thread_id, old.ip, old.remote_port, old.time, old.message);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `post_deleted`
--

DROP TABLE IF EXISTS `post_deleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_deleted` (
  `id` int unsigned NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `thread_id` int unsigned NOT NULL,
  `ip` varbinary(16) NOT NULL,
  `remote_port` smallint unsigned NOT NULL DEFAULT '0',
  `time` timestamp NULL DEFAULT NULL,
  `subject` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci NOT NULL,
  `time_deleted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `post_deleted_delete` BEFORE DELETE ON `post_deleted` FOR EACH ROW BEGIN
    IF old.time_deleted >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This post has been deleted less than a month ago, legal reasons disallow deleting of it.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `post_edit`
--

DROP TABLE IF EXISTS `post_edit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_edit` (
  `id` int unsigned NOT NULL,
  `edit_time` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `ip` varbinary(16) NOT NULL,
  `message_before` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`,`edit_time`),
  CONSTRAINT `post_edit_ibfk_1` FOREIGN KEY (`id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post_file`
--

DROP TABLE IF EXISTS `post_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_file` (
  `post_id` int unsigned NOT NULL,
  `file_id` int unsigned NOT NULL,
  `orig_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`post_id`,`file_id`),
  KEY `fileId` (`file_id`),
  CONSTRAINT `post_file_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `post_file_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `post_file_delete` AFTER DELETE ON `post_file` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM post_file WHERE file_id = old.file_id LIMIT 1) = 0 THEN
        DELETE FROM file WHERE id = old.file_id;
	END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `post_reply`
--

DROP TABLE IF EXISTS `post_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_reply` (
  `post_id` int unsigned NOT NULL,
  `post_id_replied` int unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`post_id_replied`),
  KEY `id_refers` (`post_id_replied`),
  CONSTRAINT `post_reply_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `post_reply_ibfk_2` FOREIGN KEY (`post_id_replied`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post_report`
--

DROP TABLE IF EXISTS `post_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_report` (
  `post_id` int unsigned NOT NULL,
  `reason` varchar(6000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `report_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reported_by` varbinary(16) NOT NULL,
  `reported_by_user` int unsigned NOT NULL,
  `cleared` bit(1) NOT NULL DEFAULT b'0',
  `cleared_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`post_id`,`reported_by_user`),
  KEY `cleared` (`cleared`),
  KEY `reported_by_user` (`reported_by_user`),
  CONSTRAINT `post_report_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `post_report_ibfk_2` FOREIGN KEY (`reported_by_user`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post_tag`
--

DROP TABLE IF EXISTS `post_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_tag` (
  `post_id` int unsigned NOT NULL,
  `tag_id` varchar(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `post_tag_ibfk_4` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `post_tag_ibfk_5` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post_upvote`
--

DROP TABLE IF EXISTS `post_upvote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_upvote` (
  `post_id` int unsigned NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  UNIQUE KEY `post_id` (`post_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `post_upvote_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `post_upvote_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag` (
  `id` varchar(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `listed` bit(1) NOT NULL DEFAULT b'1',
  `obtainable` bit(1) NOT NULL DEFAULT b'1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `thread`
--

DROP TABLE IF EXISTS `thread`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `board_id` tinyint unsigned NOT NULL,
  `reply_count` smallint unsigned NOT NULL DEFAULT '0',
  `distinct_reply_count` smallint unsigned NOT NULL DEFAULT '0',
  `read_count` int unsigned NOT NULL DEFAULT '0',
  `hide_count` smallint unsigned NOT NULL DEFAULT '0',
  `follow_count` smallint unsigned NOT NULL DEFAULT '0',
  `subject` varchar(60) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bump_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_locked` bit(1) NOT NULL DEFAULT b'0',
  `is_sticky` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `board_id` (`board_id`),
  KEY `board_id_2` (`board_id`,`is_sticky` DESC,`bump_time` DESC) COMMENT 'Board get threads',
  KEY `bump_time` (`bump_time` DESC) COMMENT 'All threads page (sticky ignored)',
  CONSTRAINT `thread_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `thread_ibfk_2` FOREIGN KEY (`board_id`) REFERENCES `board` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `thread_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=128438211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `thread_delete_before` BEFORE DELETE ON `thread` FOR EACH ROW BEGIN
	DELETE FROM post WHERE thread_id = old.id;

	INSERT IGNORE INTO thread_deleted (id, user_id, board_id, subject, time)
	VALUES (old.id, old.user_id, old.board_id, old.subject, old.time);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `thread_deleted`
--

DROP TABLE IF EXISTS `thread_deleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread_deleted` (
  `id` int unsigned NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `board_id` tinyint unsigned NOT NULL,
  `subject` varchar(60) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `delete_reason` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `email` varchar(255) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `account_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_ip` varbinary(16) DEFAULT NULL,
  `last_name_change` timestamp NULL DEFAULT NULL,
  `user_class` tinyint unsigned NOT NULL DEFAULT '0',
  `gold_account_expires` datetime DEFAULT NULL,
  `last_board` tinyint unsigned NOT NULL DEFAULT '0',
  `is_suspended` bit(1) NOT NULL DEFAULT b'0',
  `language` enum('en_US.UTF-8','fi_FI.UTF-8','sv_SE.UTF-8','de_DE.UTF-8') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'en_US.UTF-8',
  `activity_points` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `lastBoard` (`last_board`),
  KEY `user_class` (`user_class`),
  KEY `remove_inactive_users` (`last_active`,`gold_account_expires`,`password`)
) ENGINE=InnoDB AUTO_INCREMENT=110582505 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_ban`
--

DROP TABLE IF EXISTS `user_ban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_ban` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `start_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` tinyint unsigned NOT NULL,
  `reason_details` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `post_id` int unsigned DEFAULT NULL,
  `banned_by` int unsigned DEFAULT NULL,
  `is_expired` bit(1) NOT NULL DEFAULT b'0',
  `is_appealed` bit(1) NOT NULL DEFAULT b'0',
  `appeal_text` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `appeal_checked` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id`),
  KEY `lastBanCheck` (`user_id`,`banned_by`),
  KEY `isAppealed_2` (`is_appealed`,`appeal_checked`,`is_expired`),
  CONSTRAINT `user_ban_ibfk_6` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_ban_chk_1` CHECK ((`start_time` < `end_time`))
) ENGINE=InnoDB AUTO_INCREMENT=175362 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_board_hide`
--

DROP TABLE IF EXISTS `user_board_hide`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_board_hide` (
  `user_id` int unsigned NOT NULL,
  `board_id` tinyint unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`board_id`),
  KEY `board_id` (`board_id`),
  CONSTRAINT `user_board_hide_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_board_hide_ibfk_2` FOREIGN KEY (`board_id`) REFERENCES `board` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_name_hide`
--

DROP TABLE IF EXISTS `user_name_hide`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_name_hide` (
  `user_id` int unsigned NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci NOT NULL,
  PRIMARY KEY (`user_id`,`name`),
  CONSTRAINT `user_name_hide_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_notification`
--

DROP TABLE IF EXISTS `user_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` tinyint unsigned NOT NULL,
  `thread_id` int unsigned DEFAULT NULL,
  `post_id` int unsigned DEFAULT NULL,
  `user_post_id` int unsigned DEFAULT NULL,
  `custom_info` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci DEFAULT NULL,
  `count` int unsigned NOT NULL DEFAULT '1',
  `is_read` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`user_id`,`type`,`thread_id`,`post_id`),
  KEY `post_id` (`post_id`),
  KEY `get_notifications` (`user_id`,`type`,`timestamp`,`is_read`),
  KEY `delete_old` (`timestamp`),
  KEY `mark_read` (`user_id`,`post_id`,`is_read`) USING BTREE,
  KEY `thread_id` (`thread_id`),
  CONSTRAINT `user_notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_notification_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_notification_ibfk_3` FOREIGN KEY (`thread_id`) REFERENCES `thread` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=473938291 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `user_id` int unsigned NOT NULL,
  `custom_css` text,
  `show_username` bit(1) NOT NULL DEFAULT b'0',
  `style` enum('ylilauta','peruslauta','halloween','northboard','ylilauta_2011') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'ylilauta',
  `board_display_style` enum('default','grid','compact') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'default',
  `hide_sidebar` bit(1) NOT NULL DEFAULT b'0',
  `hide_ads` bit(1) NOT NULL DEFAULT b'0',
  `hide_images` bit(1) NOT NULL DEFAULT b'0',
  `reply_form_at_top` bit(1) NOT NULL DEFAULT b'0',
  `auto_follow` bit(1) NOT NULL DEFAULT b'0',
  `auto_follow_reply` bit(1) NOT NULL DEFAULT b'0',
  `follow_order_by_bumptime` bit(1) NOT NULL DEFAULT b'0',
  `follow_show_floatbox` bit(1) NOT NULL DEFAULT b'0',
  `notification_from_post_replies` bit(1) NOT NULL DEFAULT b'1',
  `notification_from_thread_replies` bit(1) NOT NULL DEFAULT b'1',
  `notification_from_followed_replies` bit(1) NOT NULL DEFAULT b'1',
  `notification_from_post_upvotes` bit(1) NOT NULL DEFAULT b'1',
  `threads_per_page` tinyint unsigned NOT NULL DEFAULT '15',
  `preview_posts_per_thread` tinyint unsigned NOT NULL DEFAULT '3',
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_preferences_chk_1` CHECK ((`threads_per_page` in (5,10,15,20,25))),
  CONSTRAINT `user_preferences_chk_2` CHECK ((`preview_posts_per_thread` between 0 and 10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_recovery_key`
--

DROP TABLE IF EXISTS `user_recovery_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_recovery_key` (
  `user_id` int unsigned NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recovery_key` binary(10) NOT NULL,
  PRIMARY KEY (`user_id`,`recovery_key`),
  KEY `time` (`time`),
  CONSTRAINT `user_recovery_key_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_session`
--

DROP TABLE IF EXISTS `user_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_session` (
  `user_id` int unsigned NOT NULL,
  `session_id` binary(32) NOT NULL,
  `csrf_token` binary(32) NOT NULL,
  `login_ip` varbinary(16) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`session_id`),
  CONSTRAINT `user_session_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_statistics`
--

DROP TABLE IF EXISTS `user_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_statistics` (
  `user_id` int unsigned NOT NULL,
  `epic_threads` smallint unsigned NOT NULL DEFAULT '0',
  `threads_followed` int unsigned NOT NULL DEFAULT '0',
  `threads_hidden` int unsigned NOT NULL DEFAULT '0',
  `total_pageloads` int unsigned NOT NULL DEFAULT '0',
  `total_posts` int unsigned NOT NULL DEFAULT '0',
  `total_threads` int unsigned NOT NULL DEFAULT '0',
  `total_post_characters` int unsigned NOT NULL DEFAULT '0',
  `total_uploaded_files` int unsigned NOT NULL DEFAULT '0',
  `total_uploaded_filesize` bigint unsigned NOT NULL DEFAULT '0',
  `total_upboats_given` int unsigned NOT NULL DEFAULT '0',
  `total_upboats_received` int unsigned NOT NULL DEFAULT '0',
  `gold_accounts_donated` smallint unsigned NOT NULL DEFAULT '0',
  `gold_account_donations_received` smallint unsigned NOT NULL DEFAULT '0',
  `purchases_total_price` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_statistics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_tag`
--

DROP TABLE IF EXISTS `user_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_tag` (
  `user_id` int unsigned NOT NULL,
  `tag_id` varchar(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `unlocked` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `user_tag_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_tag_ibfk_3` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_thread_follow`
--

DROP TABLE IF EXISTS `user_thread_follow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_thread_follow` (
  `user_id` int unsigned NOT NULL,
  `thread_id` int unsigned NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen_reply` int unsigned DEFAULT NULL,
  `unread_count` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`thread_id`,`unread_count`) USING BTREE,
  KEY `thread` (`thread_id`),
  CONSTRAINT `user_thread_follow_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_thread_follow_ibfk_2` FOREIGN KEY (`thread_id`) REFERENCES `thread` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `thread_follow_update_after_insert` AFTER INSERT ON `user_thread_follow` FOR EACH ROW BEGIN
	UPDATE thread SET follow_count = follow_count+1 WHERE id = NEW.thread_id;
	
	INSERT INTO user_statistics (user_id, threads_followed)
	VALUES(NEW.user_id, 1)
	ON DUPLICATE KEY UPDATE threads_followed = threads_followed+1;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `thread_follow_update_after_delete` AFTER DELETE ON `user_thread_follow` FOR EACH ROW BEGIN
	UPDATE thread SET follow_count = follow_count-1 WHERE id = OLD.thread_id;
	
	UPDATE IGNORE user_statistics SET threads_followed = threads_followed-1 WHERE user_id = OLD.user_id AND threads_followed > 0;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `user_thread_hide`
--

DROP TABLE IF EXISTS `user_thread_hide`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_thread_hide` (
  `user_id` int unsigned NOT NULL,
  `thread_id` int unsigned NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`thread_id`),
  KEY `thread_id` (`thread_id`),
  KEY `added` (`user_id`,`added`) USING BTREE,
  CONSTRAINT `user_thread_hide_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_thread_hide_ibfk_4` FOREIGN KEY (`thread_id`) REFERENCES `thread` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `thread_hide_update_after_insert` AFTER INSERT ON `user_thread_hide` FOR EACH ROW BEGIN
	UPDATE thread SET hide_count = hide_count+1 WHERE id = NEW.thread_id;
	
	INSERT INTO user_statistics (user_id, threads_hidden)
	VALUES(NEW.user_id, 1)
	ON DUPLICATE KEY UPDATE threads_hidden = threads_hidden+1;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `thread_hide_update_after_delete` AFTER DELETE ON `user_thread_hide` FOR EACH ROW BEGIN
	UPDATE thread SET hide_count = hide_count-1 WHERE id = OLD.thread_id;
	
	UPDATE IGNORE user_statistics SET threads_hidden = threads_hidden-1 WHERE user_id = OLD.user_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `word_blacklist`
--

DROP TABLE IF EXISTS `word_blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `word_blacklist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci NOT NULL,
  `reason` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=173848 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'ylilauta'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

DROP EVENT IF EXISTS update_postcount;
DELIMITER ;;
CREATE EVENT `update_postcount`
ON SCHEDULE EVERY 1 SECOND
DO
	INSERT INTO materialized_view (view_key, view_value) VALUES
	("postcount_hour", (
		SELECT
		(SELECT COUNT(*) FROM `post` WHERE `time` > DATE_SUB(NOW(), INTERVAL 1 HOUR)) +
		(SELECT COUNT(*) FROM `post_deleted` WHERE `time` > DATE_SUB(NOW(), INTERVAL 1 HOUR))
	)) AS a ON DUPLICATE KEY UPDATE view_value = a.view_value;;
DELIMITER ;

DROP EVENT IF EXISTS update_onlinecount;
DELIMITER ;;
CREATE EVENT `update_onlinecount`
ON SCHEDULE EVERY 1 SECOND
DO
	INSERT INTO materialized_view (view_key, view_value) VALUES
	("onlinecount", (
		WITH result AS (
			SELECT last_board, COUNT(*) AS count
			FROM user
			WHERE `last_active` >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
			GROUP BY last_board
		) SELECT JSON_OBJECTAGG(last_board, count) FROM result
	)) AS a ON DUPLICATE KEY UPDATE view_value = a.view_value;;
DELIMITER ;

DROP EVENT IF EXISTS update_onlinecount_total;
DELIMITER ;;
CREATE EVENT `update_onlinecount_total`
ON SCHEDULE EVERY 1 SECOND
DO
	INSERT INTO materialized_view (view_key, view_value) VALUES
	("onlinecount_total", (
		SELECT COUNT(id) FROM user WHERE last_active >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
	)) AS a ON DUPLICATE KEY UPDATE view_value = a.view_value;;
DELIMITER ;

DROP EVENT IF EXISTS update_popular_hour;
DELIMITER ;;
CREATE EVENT `update_popular_hour`
ON SCHEDULE EVERY 30 SECOND
DO
	INSERT INTO materialized_view (view_key, view_value) VALUES
	("popular_hour", (
		WITH result AS (
			SELECT JSON_OBJECT('id', a.thread_id, 'subject', b.subject, 'boardname', c.name, 'url', url) as result
			FROM post a
			LEFT JOIN thread b ON b.id = a.thread_id
			LEFT JOIN board c ON c.id = b.board_id
			WHERE a.time >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND c.is_hidden = 0 AND c.id != 154
			GROUP BY a.thread_id
			ORDER BY COUNT(DISTINCT a.user_id) DESC
			LIMIT 20
		) SELECT JSON_ARRAYAGG(result) FROM result
	)) AS a ON DUPLICATE KEY UPDATE view_value = a.view_value;;
DELIMITER ;

DROP EVENT IF EXISTS update_popular_day;
DELIMITER ;;
CREATE EVENT `update_popular_day`
ON SCHEDULE EVERY 15 MINUTE
DO
	INSERT INTO materialized_view (view_key, view_value) VALUES
	("popular_hour", (
		WITH result AS (
			SELECT JSON_OBJECT('id', a.thread_id, 'subject', b.subject, 'boardname', c.name, 'url', url) as result
			FROM post a
			LEFT JOIN thread b ON b.id = a.thread_id
			LEFT JOIN board c ON c.id = b.board_id
			WHERE a.time >= DATE_SUB(NOW(), INTERVAL 1 DAY) AND c.is_hidden = 0 AND c.id != 154
			GROUP BY a.thread_id
			ORDER BY COUNT(DISTINCT a.user_id) DESC
			LIMIT 20
		) SELECT JSON_ARRAYAGG(result) FROM result
	)) AS a ON DUPLICATE KEY UPDATE view_value = a.view_value;;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_ip_bans;
DELIMITER ;;
CREATE EVENT `delete_old_ip_bans`
ON SCHEDULE EVERY 10 MINUTE
DO
	DELETE FROM ban_ip WHERE expires < NOW();;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_bot_blocks;
DELIMITER ;;
CREATE EVENT `delete_old_bot_blocks`
ON SCHEDULE EVERY 1 HOUR
DO
	DELETE FROM ban_bot_ip WHERE last_seen <= DATE_SUB(NOW(), INTERVAL 1 WEEK);;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_deleted_posts;
DELIMITER ;;
CREATE EVENT `delete_old_deleted_posts`
ON SCHEDULE EVERY 1 HOUR
DO
	DELETE FROM `post_deleted` WHERE `time_deleted` <= DATE_SUB(NOW(), INTERVAL 30 DAY);;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_admin_log;
DELIMITER ;;
CREATE EVENT `delete_old_admin_log`
ON SCHEDULE EVERY 1 HOUR
DO
	DELETE FROM `admin_log` WHERE `time` <= DATE_SUB(NOW(), INTERVAL 6 MONTH);;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_user_accounts;
DELIMITER ;;
CREATE EVENT `delete_old_user_accounts`
ON SCHEDULE EVERY 1 HOUR
DO
	DELETE FROM user WHERE last_active <= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND gold_account_expires IS NULL AND password IS NULL;;
	DELETE FROM user WHERE last_active <= DATE_SUB(NOW(), INTERVAL 1 YEAR) AND gold_account_expires IS NULL;;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_user_sessions;
DELIMITER ;;
CREATE EVENT `delete_old_user_sessions`
ON SCHEDULE EVERY 1 HOUR
DO
	DELETE FROM user_session WHERE last_active <= DATE_SUB(NOW(), INTERVAL 1 MONTH);;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_gold_orders;
DELIMITER ;;
CREATE EVENT `delete_old_gold_orders`
ON SCHEDULE EVERY 1 HOUR
DO
	DELETE FROM gold_order WHERE timestamp <= DATE_SUB(NOW(), INTERVAL 3 MONTH) OR (timestamp <= DATE_SUB(NOW(), INTERVAL 1 DAY) AND verified = 0);;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_user_notifications;
DELIMITER ;;
CREATE EVENT `delete_old_user_notifications`
ON SCHEDULE EVERY 1 HOUR
DO
	DELETE FROM user_notification WHERE is_read = 1 AND timestamp <= DATE_SUB(NOW(), INTERVAL 1 DAY);;
	DELETE FROM user_notification WHERE is_read = 0 AND timestamp <= DATE_SUB(NOW(), INTERVAL 1 MONTH);;
DELIMITER ;

DROP EVENT IF EXISTS delete_old_user_bans;
DELIMITER ;;
CREATE EVENT `delete_old_user_bans`
ON SCHEDULE EVERY 1 HOUR
DO
	DELETE FROM `user_ban` WHERE end_time < DATE_SUB(NOW(), INTERVAL 3 MONTH);;
DELIMITER ;

DROP EVENT IF EXISTS expire_old_gold_accounts;
DELIMITER ;;
CREATE EVENT `expire_old_gold_accounts`
ON SCHEDULE EVERY 1 HOUR
DO
	UPDATE user SET `gold_account_expires` = NULL WHERE `gold_account_expires` <= NOW();;
DELIMITER ;

DROP EVENT IF EXISTS update_epic_guys;
DELIMITER ;;
CREATE EVENT `update_epic_guys`
ON SCHEDULE EVERY 1 MINUTE
DO
	INSERT INTO user_statistics (user_id, epic_threads)
	SELECT * FROM (
		SELECT user_id, COUNT(*) AS count FROM thread WHERE board_id = 57 AND user_id IN (SELECT id FROM user) GROUP BY user_id
	) AS dt
	ON DUPLICATE KEY UPDATE epic_threads = count;;
DELIMITER ;

DROP EVENT IF EXISTS delete_expired_user_recovery_keys;
DELIMITER ;;
CREATE EVENT `delete_expired_user_recovery_keys`
ON SCHEDULE EVERY 1 MINUTE
DO
	DELETE FROM user_recovery_key WHERE time < DATE_SUB(NOW(), INTERVAL 1 HOUR);;
DELIMITER ;

DROP EVENT IF EXISTS expire_old_gold_keys;
DELIMITER ;;
CREATE EVENT `expire_old_gold_keys`
ON SCHEDULE EVERY 1 MINUTE
DO
	UPDATE gold_key SET is_used = 1 WHERE `generated` < DATE_SUB(NOW(), INTERVAL 3 MONTH) AND is_used = 0;;
DELIMITER ;