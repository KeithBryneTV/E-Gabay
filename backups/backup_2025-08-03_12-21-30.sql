-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: egabay_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `message_type` enum('user','system') NOT NULL DEFAULT 'user',
  `file_path` varchar(500) DEFAULT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_ibfk_1` (`chat_id`),
  KEY `chat_messages_ibfk_2` (`user_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES (8,4,NULL,'Chat session started by counselor. This conversation is private and confidential.',0,'2025-08-01 12:14:31','system',NULL,NULL,NULL,NULL),(9,5,NULL,'Chat session started by counselor. This conversation is private and confidential.',0,'2025-08-01 12:14:47','system',NULL,NULL,NULL,NULL),(96,16,NULL,'Chat session started. This conversation is private and confidential.',0,'2025-08-02 15:33:04','system',NULL,NULL,NULL,NULL),(97,16,2,'asan bago',1,'2025-08-02 15:33:16','user',NULL,NULL,NULL,NULL),(98,16,3,'???? Shared a file: MVIMG_20250727_121952.jpg',1,'2025-08-02 15:34:40','user','uploads/chat_files/chat_688e300febff4_MVIMG_20250727_121952.jpg',NULL,'MVIMG_20250727_121952.jpg',6147930),(99,16,2,'testing',0,'2025-08-03 00:42:41','user',NULL,NULL,NULL,NULL),(100,16,2,'???? Shared a file: MVIMG_20250731_140806.jpg',0,'2025-08-03 01:43:47','user','uploads/chat_files/chat_688ebed34a0a5_MVIMG_20250731_140806.jpg',NULL,'MVIMG_20250731_140806.jpg',6259718),(101,16,NULL,'Chat session ended by student. The issue has been resolved.',0,'2025-08-03 01:44:08','system',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_sessions`
--

DROP TABLE IF EXISTS `chat_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('active','closed') NOT NULL DEFAULT 'active',
  `consultation_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_sessions_student` (`student_id`),
  KEY `idx_chat_sessions_counselor` (`counselor_id`),
  KEY `chat_sessions_ibfk_3` (`consultation_id`),
  CONSTRAINT `chat_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `chat_sessions_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `chat_sessions_ibfk_3` FOREIGN KEY (`consultation_id`) REFERENCES `consultation_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_sessions`
--

LOCK TABLES `chat_sessions` WRITE;
/*!40000 ALTER TABLE `chat_sessions` DISABLE KEYS */;
INSERT INTO `chat_sessions` VALUES (4,3,2,'3','active',NULL,'2025-08-01 12:14:31','2025-08-01 12:14:31'),(5,3,2,'3','active',NULL,'2025-08-01 12:14:47','2025-08-01 12:14:47'),(16,2,3,'Consultation #16','closed',16,'2025-08-02 15:33:04','2025-08-03 01:44:08');
/*!40000 ALTER TABLE `chat_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultation_requests`
--

DROP TABLE IF EXISTS `consultation_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consultation_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `counselor_id` int(11) DEFAULT NULL,
  `issue_description` text NOT NULL,
  `issue_category` varchar(100) DEFAULT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `communication_method` enum('in_person','phone','video','email','chat','voice') NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','live','completed','cancelled') NOT NULL DEFAULT 'pending',
  `counselor_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consultation_requests_student` (`student_id`),
  KEY `idx_consultation_requests_counselor` (`counselor_id`),
  CONSTRAINT `consultation_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `consultation_requests_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultation_requests`
--

LOCK TABLES `consultation_requests` WRITE;
/*!40000 ALTER TABLE `consultation_requests` DISABLE KEYS */;
INSERT INTO `consultation_requests` VALUES (16,2,3,'testing','Mental Health','2025-08-03','09:00:00','email',0,'completed',NULL,'2025-08-02 15:29:57','2025-08-03 01:44:08');
/*!40000 ALTER TABLE `consultation_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `counselor_profiles`
--

DROP TABLE IF EXISTS `counselor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `counselor_profiles` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `availability` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`profile_id`),
  KEY `counselor_profiles_ibfk_1` (`user_id`),
  CONSTRAINT `counselor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `counselor_profiles`
--

LOCK TABLES `counselor_profiles` WRITE;
/*!40000 ALTER TABLE `counselor_profiles` DISABLE KEYS */;
INSERT INTO `counselor_profiles` VALUES (1,3,'','',NULL,NULL,'2025-07-22 12:25:57','2025-08-03 02:53:57'),(2,11,'none','',NULL,NULL,'2025-08-03 02:51:01','2025-08-03 02:51:01');
/*!40000 ALTER TABLE `counselor_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_emails`
--

DROP TABLE IF EXISTS `failed_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `retry_count` int(11) DEFAULT 0,
  `status` enum('pending','retrying','failed','sent') DEFAULT 'pending',
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_emails`
--

LOCK TABLES `failed_emails` WRITE;
/*!40000 ALTER TABLE `failed_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultation_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `feedback_ibfk_1` (`consultation_id`),
  KEY `feedback_ibfk_2` (`student_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultation_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (6,16,2,2,'goods','2025-08-03 01:44:26');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL,
  `blocked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`),
  KEY `idx_username_time` (`username`,`attempted_at`),
  KEY `idx_blocked_until` (`blocked_until`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (3,'::1','admin','2025-08-02 14:26:05',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(4,'::1','secure','2025-08-02 14:32:30',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(5,'::1','secure','2025-08-02 14:35:33',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(6,'::1','keith','2025-08-02 14:36:11',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(7,'::1','admin','2025-08-02 14:36:24',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(8,'::1','keith','2025-08-02 14:42:43',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(9,'::1','admin','2025-08-02 14:52:13',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(10,'192.168.1.15','admin','2025-08-02 15:22:01',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(11,'192.168.1.15','keith','2025-08-02 15:24:02',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(12,'192.168.1.15','counsil','2025-08-02 15:30:11',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(13,'192.168.1.15','keith','2025-08-02 15:32:58',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(14,'192.168.1.15','counsil','2025-08-02 15:33:30',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(15,'::1','admin','2025-08-03 00:26:03',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(16,'192.168.1.5','admin','2025-08-03 00:29:31',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(17,'192.168.1.5','counsil','2025-08-03 00:31:51',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(19,'192.168.1.5','keith','2025-08-03 00:36:55',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(20,'192.168.1.5','admin','2025-08-03 00:43:22',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(21,'192.168.1.15','admin','2025-08-03 00:50:51',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(22,'192.168.1.5','admin','2025-08-03 01:24:59',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(23,'192.168.1.15','keith','2025-08-03 01:27:00',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(24,'192.168.1.5','keith','2025-08-03 01:37:23',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(25,'192.168.1.5','admin','2025-08-03 01:42:26',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(26,'192.168.1.15','keith','2025-08-03 01:56:37',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(27,'192.168.1.15','counsil','2025-08-03 01:57:21',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(28,'192.168.1.15','keith','2025-08-03 01:58:35',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(29,'::1','keith','2025-08-03 02:17:16',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(30,'192.168.1.5','keith','2025-08-03 02:17:22',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(31,'192.168.1.5','admin','2025-08-03 02:39:18',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(32,'192.168.1.15','admin','2025-08-03 02:39:37',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(33,'192.168.1.15','admin','2025-08-03 02:43:30',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(34,'192.168.1.15','admin','2025-08-03 02:46:42',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(35,'192.168.1.15','keith','2025-08-03 02:49:28',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(36,'192.168.1.15','admin','2025-08-03 02:49:47',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(37,'192.168.1.15','counsil2','2025-08-03 02:51:15',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(38,'192.168.1.15','admin','2025-08-03 02:53:26',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(40,'192.168.1.15','counsil2','2025-08-03 02:54:17',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(41,'192.168.1.5','counsil2','2025-08-03 02:56:53',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(42,'192.168.1.5','keith','2025-08-03 02:57:31',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(43,'192.168.1.5','admin','2025-08-03 02:58:13',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(44,'192.168.1.5','admin','2025-08-03 02:59:07',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(45,'192.168.1.5','counsil','2025-08-03 02:59:16',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(46,'192.168.1.5','admin','2025-08-03 03:06:29',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(47,'192.168.1.15','keith','2025-08-03 03:18:48',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(48,'192.168.1.5','keith','2025-08-03 03:30:36',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(49,'192.168.1.5','admin','2025-08-03 03:35:00',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(50,'192.168.1.5','keith','2025-08-03 03:40:31',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(51,'::1','admin','2025-08-03 05:34:42',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(52,'::1','admin','2025-08-03 05:37:52',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(53,'::1','admin','2025-08-03 06:58:56',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(54,'::1','admin','2025-08-03 07:14:07',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(55,'::1','admin','2025-08-03 07:42:24',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(56,'::1','admin','2025-08-03 07:52:32',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(57,'::1','admin','2025-08-03 07:56:58',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0',NULL),(58,'::1','admin','2025-08-03 07:58:48',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(59,'::1','admin','2025-08-03 08:16:01',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL),(60,'::1','admin','2025-08-03 08:19:43',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(61,'::1','admin','2025-08-03 08:20:50',1,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL),(62,'192.168.1.15','admin','2025-08-03 08:21:05',1,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',NULL);
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notifications_ibfk_1` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,2,'Please provide feedback for your completed consultation session.','feedback_request',4,0,'2025-08-02 05:37:54'),(2,7,'Please provide feedback for your completed consultation session.','feedback_request',14,0,'2025-08-02 08:50:25');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'student','Regular student who can request consultations',1,'2025-07-22 10:09:32','2025-07-22 10:09:32'),(2,'counselor','Provides guidance and counseling services',1,'2025-07-22 10:09:32','2025-07-22 10:09:32'),(3,'admin','System administrator with full access',1,'2025-07-22 10:09:32','2025-07-22 10:09:32'),(4,'staff','Support staff with limited access',1,'2025-07-22 10:09:32','2025-07-22 10:09:32');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'LOW',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_ip_action` (`ip_address`,`action`),
  KEY `idx_risk_level` (`risk_level`,`created_at`),
  CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
INSERT INTO `security_logs` VALUES (1,NULL,'::1','login_failed','Username: admin, Attempts: 0','MEDIUM','2025-08-02 14:25:25'),(2,NULL,'::1','login_failed','Username: admin, Attempts: 1','MEDIUM','2025-08-02 14:25:28'),(3,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-02 14:26:05'),(4,1,'::1','login_success','Successful login','LOW','2025-08-02 14:26:05'),(5,NULL,'::1','login_success','Username: secure, Attempts: 1','LOW','2025-08-02 14:32:30'),(6,NULL,'::1','login_success','Successful login','LOW','2025-08-02 14:32:30'),(7,NULL,'::1','login_success','Username: secure, Attempts: 1','LOW','2025-08-02 14:35:33'),(8,NULL,'::1','login_success','Successful login','LOW','2025-08-02 14:35:33'),(9,NULL,'::1','login_success','Username: keith, Attempts: 1','LOW','2025-08-02 14:36:11'),(10,2,'::1','login_success','Successful login','LOW','2025-08-02 14:36:11'),(11,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-02 14:36:24'),(12,1,'::1','login_success','Successful login','LOW','2025-08-02 14:36:24'),(13,NULL,'::1','login_success','Username: keith, Attempts: 1','LOW','2025-08-02 14:42:43'),(14,2,'::1','login_success','Successful login','LOW','2025-08-02 14:42:43'),(15,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-02 14:52:13'),(16,1,'::1','login_success','Successful login','LOW','2025-08-02 14:52:13'),(17,NULL,'192.168.1.15','login_success','Username: admin, Attempts: 1','LOW','2025-08-02 15:22:01'),(18,1,'192.168.1.15','login_success','Successful login','LOW','2025-08-02 15:22:01'),(19,NULL,'192.168.1.15','login_success','Username: keith, Attempts: 1','LOW','2025-08-02 15:24:02'),(20,2,'192.168.1.15','login_success','Successful login','LOW','2025-08-02 15:24:02'),(21,NULL,'192.168.1.15','login_success','Username: counsil, Attempts: 1','LOW','2025-08-02 15:30:11'),(22,3,'192.168.1.15','login_success','Successful login','LOW','2025-08-02 15:30:11'),(23,NULL,'192.168.1.15','login_success','Username: keith, Attempts: 1','LOW','2025-08-02 15:32:58'),(24,2,'192.168.1.15','login_success','Successful login','LOW','2025-08-02 15:32:58'),(25,NULL,'192.168.1.15','login_success','Username: counsil, Attempts: 1','LOW','2025-08-02 15:33:30'),(26,3,'192.168.1.15','login_success','Successful login','LOW','2025-08-02 15:33:30'),(27,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 00:26:03'),(28,1,'::1','login_success','Successful login','LOW','2025-08-03 00:26:03'),(29,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 00:29:31'),(30,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 00:29:31'),(31,NULL,'192.168.1.5','login_success','Username: counsil, Attempts: 1','LOW','2025-08-03 00:31:51'),(32,3,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 00:31:51'),(33,NULL,'192.168.1.5','login_failed','Username: asdsa, Attempts: 0','MEDIUM','2025-08-03 00:36:47'),(34,NULL,'192.168.1.5','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 00:36:55'),(35,2,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 00:36:55'),(36,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 00:43:22'),(37,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 00:43:22'),(38,NULL,'192.168.1.15','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 00:50:51'),(39,1,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 00:50:51'),(40,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 01:24:59'),(41,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 01:24:59'),(42,NULL,'192.168.1.15','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 01:27:00'),(43,2,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 01:27:00'),(44,NULL,'192.168.1.5','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 01:37:23'),(45,2,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 01:37:23'),(46,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 01:42:26'),(47,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 01:42:26'),(48,NULL,'192.168.1.15','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 01:56:37'),(49,2,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 01:56:37'),(50,NULL,'192.168.1.15','login_success','Username: counsil, Attempts: 1','LOW','2025-08-03 01:57:21'),(51,3,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 01:57:21'),(52,NULL,'192.168.1.15','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 01:58:35'),(53,2,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 01:58:35'),(54,NULL,'::1','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 02:17:16'),(55,2,'::1','login_success','Successful login','LOW','2025-08-03 02:17:16'),(56,NULL,'192.168.1.5','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 02:17:22'),(57,2,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 02:17:23'),(58,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 02:39:18'),(59,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 02:39:18'),(60,NULL,'192.168.1.15','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 02:39:37'),(61,1,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 02:39:37'),(62,NULL,'192.168.1.15','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 02:43:30'),(63,1,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 02:43:30'),(64,NULL,'192.168.1.15','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 02:46:42'),(65,1,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 02:46:42'),(66,NULL,'192.168.1.15','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 02:49:28'),(67,2,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 02:49:28'),(68,NULL,'192.168.1.15','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 02:49:47'),(69,1,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 02:49:47'),(70,NULL,'192.168.1.15','login_success','Username: counsil2, Attempts: 1','LOW','2025-08-03 02:51:15'),(71,11,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 02:51:15'),(72,NULL,'192.168.1.15','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 02:53:26'),(73,1,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 02:53:26'),(74,NULL,'192.168.1.15','login_failed','Username: counsil, Attempts: 0','MEDIUM','2025-08-03 02:54:11'),(75,NULL,'192.168.1.15','login_success','Username: counsil2, Attempts: 1','LOW','2025-08-03 02:54:17'),(76,11,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 02:54:17'),(77,NULL,'192.168.1.5','login_success','Username: counsil2, Attempts: 1','LOW','2025-08-03 02:56:53'),(78,11,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 02:56:53'),(79,NULL,'192.168.1.5','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 02:57:31'),(80,2,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 02:57:31'),(81,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 02:58:13'),(82,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 02:58:13'),(83,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 02:59:07'),(84,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 02:59:07'),(85,NULL,'192.168.1.5','login_success','Username: counsil, Attempts: 1','LOW','2025-08-03 02:59:16'),(86,3,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 02:59:16'),(87,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 03:06:29'),(88,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 03:06:29'),(89,NULL,'192.168.1.15','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 03:18:48'),(90,2,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 03:18:48'),(91,NULL,'192.168.1.5','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 03:30:36'),(92,2,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 03:30:36'),(93,NULL,'192.168.1.5','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 03:35:00'),(94,1,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 03:35:00'),(95,NULL,'192.168.1.5','login_success','Username: keith, Attempts: 1','LOW','2025-08-03 03:40:31'),(96,2,'192.168.1.5','login_success','Successful login','LOW','2025-08-03 03:40:31'),(97,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 05:34:42'),(98,1,'::1','login_success','Successful login','LOW','2025-08-03 05:34:42'),(99,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 05:37:52'),(100,1,'::1','login_success','Successful login','LOW','2025-08-03 05:37:52'),(101,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 06:58:56'),(102,1,'::1','login_success','Successful login','LOW','2025-08-03 06:58:56'),(103,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 07:14:07'),(104,1,'::1','login_success','Successful login','LOW','2025-08-03 07:14:07'),(105,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 07:42:24'),(106,1,'::1','login_success','Successful login','LOW','2025-08-03 07:42:24'),(107,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 07:52:32'),(108,1,'::1','login_success','Successful login','LOW','2025-08-03 07:52:32'),(109,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 07:56:58'),(110,1,'::1','login_success','Successful login','LOW','2025-08-03 07:56:58'),(111,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 07:58:48'),(112,1,'::1','login_success','Successful login','LOW','2025-08-03 07:58:48'),(113,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 08:16:01'),(114,1,'::1','login_success','Successful login','LOW','2025-08-03 08:16:01'),(115,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 08:19:43'),(116,1,'::1','login_success','Successful login','LOW','2025-08-03 08:19:43'),(117,NULL,'::1','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 08:20:50'),(118,1,'::1','login_success','Successful login','LOW','2025-08-03 08:20:50'),(119,NULL,'192.168.1.15','login_success','Username: admin, Attempts: 1','LOW','2025-08-03 08:21:05'),(120,1,'192.168.1.15','login_success','Successful login','LOW','2025-08-03 08:21:05');
/*!40000 ALTER TABLE `security_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_title','E-GABAY ASC - Academic Support and Counseling System','The title of the website',1,'2025-07-22 10:09:33','2025-07-22 10:09:33'),(2,'site_description','A Comprehensive Academic Support and Counseling System','The description of the website',1,'2025-07-22 10:09:33','2025-07-22 10:09:33'),(3,'admin_email','admin@egabay.edu','The email address for system notifications',1,'2025-07-22 10:09:33','2025-07-22 10:09:33'),(4,'items_per_page','10','Default number of items to show per page',1,'2025-07-22 10:09:33','2025-07-22 10:09:33'),(5,'current_academic_year','2025-2026','The current academic year',1,'2025-07-22 10:09:33','2025-07-22 10:09:33'),(6,'site_name','E-GABAY AS','Added via settings page',1,'2025-07-22 21:51:13','2025-08-02 14:52:24'),(7,'maintenance_mode','0','Added via settings page',1,'2025-07-22 21:51:13','2025-08-02 14:51:21'),(8,'allow_registrations','1','Added via settings page',1,'2025-07-22 21:51:13','2025-08-02 14:55:02'),(9,'default_role','1','Added via settings page',1,'2025-07-22 21:51:13','2025-07-22 21:51:13'),(10,'session_timeout','30','Added via settings page',1,'2025-07-22 21:51:13','2025-07-22 21:51:13'),(11,'max_login_attempts','3','Added via settings page',1,'2025-07-22 21:51:13','2025-07-22 21:58:55'),(12,'maintenance_message','We are currently performing scheduled maintenance. Please check back soon.','Added via settings page',1,'2025-07-22 21:58:45','2025-07-22 21:58:45'),(13,'maintenance_end_time','MGA KUPAL KAYO','Added via settings page',1,'2025-07-22 21:58:45','2025-08-02 14:51:02'),(14,'primary_color','#6791d0','Added via settings page',1,'2025-08-01 15:13:54','2025-08-01 15:13:54'),(15,'secondary_color','#6c757d','Added via settings page',1,'2025-08-01 15:13:54','2025-08-01 15:13:54'),(16,'logo_url','','Added via settings page',1,'2025-08-01 15:13:54','2025-08-01 15:13:54'),(17,'favicon_url','','Added via settings page',1,'2025-08-01 15:13:54','2025-08-01 15:13:54'),(18,'footer_text','© 2025 E-Gabay ASC. All Rights Reserved.','Added via settings page',1,'2025-08-01 15:13:54','2025-08-01 15:13:54'),(19,'smtp_host','smtp.gmail.com','Added via settings page',1,'2025-08-02 08:22:45','2025-08-02 08:22:45'),(20,'smtp_port','587','Added via settings page',1,'2025-08-02 08:22:45','2025-08-02 08:22:45'),(21,'smtp_username','keithniiyoow@gmail.com','Added via settings page',1,'2025-08-02 08:22:45','2025-08-03 07:18:07'),(22,'smtp_encryption','tls','Added via settings page',1,'2025-08-02 08:22:45','2025-08-02 08:22:45'),(23,'email_from_name','E-GABAY ASC','Added via settings page',1,'2025-08-02 08:22:45','2025-08-02 08:22:45'),(24,'email_from_address','keithniiyoow@gmail.com','Added via settings page',1,'2025-08-02 08:22:45','2025-08-03 07:18:07'),(25,'smtp_password','pfld ktld heru sjdw','Added via settings page',1,'2025-08-02 08:22:45','2025-08-03 07:18:07');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profiles` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `student_profiles_ibfk_1` (`user_id`),
  CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profiles`
--

LOCK TABLES `student_profiles` WRITE;
/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
INSERT INTO `student_profiles` VALUES (1,2,'123123','','','','pogi ako',NULL,NULL,'2025-07-22 11:40:36','2025-08-03 03:40:23');
/*!40000 ALTER TABLE `student_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `system_logs_ibfk_1` (`user_id`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=529 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,1,'login','User logged in','::1','2025-07-22 10:27:23'),(2,1,'login','User logged in','::1','2025-07-22 10:28:24'),(3,1,'logout','User logged out','::1','2025-07-22 11:40:42'),(4,2,'login','User logged in','::1','2025-07-22 11:40:46'),(5,2,'logout','User logged out','::1','2025-07-22 11:52:43'),(6,1,'login','User logged in','::1','2025-07-22 11:52:47'),(7,1,'logout','User logged out','::1','2025-07-22 11:53:41'),(8,2,'login','User logged in','::1','2025-07-22 11:53:46'),(9,2,'logout','User logged out','::1','2025-07-22 11:53:54'),(10,1,'login','User logged in','::1','2025-07-22 11:53:57'),(11,1,'logout','User logged out','::1','2025-07-22 12:12:46'),(12,2,'login','User logged in','::1','2025-07-22 12:12:49'),(13,2,'logout','User logged out','::1','2025-07-22 12:14:36'),(14,1,'login','User logged in','::1','2025-07-22 12:14:41'),(15,1,'logout','User logged out','::1','2025-07-22 12:16:19'),(16,2,'login','User logged in','::1','2025-07-22 12:16:22'),(17,2,'logout','User logged out','::1','2025-07-22 12:23:58'),(18,1,'login','User logged in','::1','2025-07-22 12:24:00'),(19,1,'logout','User logged out','::1','2025-07-22 12:25:59'),(20,3,'login','User logged in','::1','2025-07-22 12:26:03'),(21,3,'logout','User logged out','::1','2025-07-22 12:28:21'),(22,1,'login','User logged in','::1','2025-07-22 12:28:28'),(23,1,'logout','User logged out','::1','2025-07-22 12:29:15'),(24,3,'login','User logged in','::1','2025-07-22 12:29:23'),(25,3,'logout','User logged out','::1','2025-07-22 12:48:10'),(26,1,'login','User logged in','::1','2025-07-22 12:48:14'),(27,1,'logout','User logged out','::1','2025-07-22 14:49:24'),(28,3,'login','User logged in','::1','2025-07-22 14:58:21'),(29,3,'logout','User logged out','::1','2025-07-22 15:20:42'),(30,2,'login','User logged in','::1','2025-07-22 15:20:48'),(31,2,'logout','User logged out','::1','2025-07-22 15:22:23'),(32,1,'login','User logged in','::1','2025-07-22 15:22:28'),(33,1,'logout','User logged out','::1','2025-07-22 15:22:57'),(34,4,'login','User logged in','::1','2025-07-22 15:23:00'),(35,4,'logout','User logged out','::1','2025-07-22 15:40:20'),(36,2,'login','User logged in','::1','2025-07-22 15:40:46'),(37,2,'logout','User logged out','::1','2025-07-22 15:41:01'),(38,3,'login','User logged in','::1','2025-07-22 15:41:06'),(39,3,'logout','User logged out','::1','2025-07-22 15:42:10'),(40,1,'login','User logged in','::1','2025-07-22 15:44:17'),(41,1,'logout','User logged out','::1','2025-07-22 15:49:24'),(42,3,'login','User logged in','::1','2025-07-22 15:49:31'),(43,3,'logout','User logged out','::1','2025-07-22 15:51:02'),(44,2,'login','User logged in','::1','2025-07-22 15:51:09'),(45,2,'logout','User logged out','::1','2025-07-22 20:33:06'),(46,3,'login','User logged in','::1','2025-07-22 20:33:19'),(47,3,'logout','User logged out','::1','2025-07-22 20:42:12'),(48,2,'login','User logged in','::1','2025-07-22 20:42:28'),(49,2,'logout','User logged out','::1','2025-07-22 20:44:18'),(50,3,'login','User logged in','::1','2025-07-22 20:44:25'),(51,3,'logout','User logged out','::1','2025-07-22 20:48:44'),(52,1,'login','User logged in','::1','2025-07-22 20:48:49'),(53,1,'logout','User logged out','::1','2025-07-22 20:54:26'),(54,3,'login','User logged in','::1','2025-07-22 20:54:30'),(55,3,'logout','User logged out','::1','2025-07-22 20:55:42'),(56,2,'login','User logged in','::1','2025-07-22 20:55:45'),(57,2,'logout','User logged out','::1','2025-07-22 21:00:54'),(58,3,'login','User logged in','::1','2025-07-22 21:00:59'),(59,3,'logout','User logged out','::1','2025-07-22 21:01:28'),(60,2,'login','User logged in','::1','2025-07-22 21:01:34'),(61,2,'logout','User logged out','::1','2025-07-22 21:41:57'),(62,1,'login','User logged in','::1','2025-07-22 21:42:00'),(63,1,'logout','User logged out','::1','2025-07-22 21:51:40'),(64,2,'login','User logged in','::1','2025-07-22 21:51:53'),(65,2,'logout','User logged out','::1','2025-07-22 21:51:58'),(66,1,'login','User logged in','::1','2025-07-22 21:52:01'),(67,1,'logout','User logged out','::1','2025-07-22 21:58:57'),(68,2,'login','User logged in','::1','2025-07-22 21:59:17'),(69,2,'logout','User logged out','::1','2025-07-22 21:59:53'),(70,1,'login','User logged in','::1','2025-07-22 21:59:57'),(71,1,'system','Deleted database backup: backup_2025-07-23_00-00-36.sql','::1','2025-07-22 22:00:42'),(72,1,'logout','User logged out','::1','2025-07-22 22:01:32'),(73,3,'login','User logged in','::1','2025-07-22 22:01:35'),(74,3,'logout','User logged out','::1','2025-07-22 22:01:50'),(75,1,'login','User logged in','::1','2025-07-22 22:01:53'),(76,1,'logout','User logged out','::1','2025-07-22 22:02:03'),(77,3,'login','User logged in','::1','2025-07-22 22:02:07'),(78,3,'logout','User logged out','::1','2025-07-22 22:03:02'),(79,2,'login','User logged in','::1','2025-07-22 22:03:08'),(80,2,'logout','User logged out','::1','2025-07-22 22:03:49'),(81,1,'login','User logged in','::1','2025-07-22 23:47:24'),(82,1,'login','User logged in','::1','2025-07-22 23:47:57'),(83,1,'logout','User logged out','::1','2025-07-22 23:50:18'),(84,2,'login','User logged in','::1','2025-07-22 23:50:26'),(85,2,'logout','User logged out','::1','2025-07-22 23:54:57'),(86,1,'login','User logged in','::1','2025-07-22 23:55:00'),(87,1,'login','User logged in','::1','2025-07-24 14:15:53'),(88,1,'login','User logged in','::1','2025-07-24 21:14:54'),(89,1,'login','User logged in','::1','2025-07-28 00:41:15'),(90,1,'login','User logged in','::1','2025-07-28 00:45:39'),(91,1,'login','User logged in','::1','2025-07-28 07:53:46'),(92,1,'login','User logged in','::1','2025-07-28 08:15:40'),(93,1,'logout','User logged out','::1','2025-07-28 08:24:54'),(94,1,'login','User logged in','::1','2025-07-28 08:25:06'),(95,1,'login','User logged in','::1','2025-07-31 09:33:47'),(96,2,'login','User logged in','::1','2025-07-31 09:36:24'),(97,2,'logout','User logged out','::1','2025-07-31 09:39:01'),(98,1,'login','User logged in','::1','2025-07-31 09:39:04'),(99,1,'system','Deleted database backup: backup_2025-07-31_12-15-09.sql','::1','2025-07-31 10:15:13'),(100,1,'login','User logged in','::1','2025-08-01 01:51:19'),(101,1,'logout','User logged out','::1','2025-08-01 01:52:27'),(102,1,'login','User logged in','::1','2025-08-01 09:35:01'),(103,1,'system','Deleted database backup: backup_2025-08-01_11-58-59.sql','::1','2025-08-01 09:59:07'),(104,1,'logout','User logged out','::1','2025-08-01 10:02:05'),(107,1,'login','User logged in','::1','2025-08-01 10:03:44'),(108,1,'logout','User logged out','::1','2025-08-01 10:54:22'),(109,2,'login','User logged in','::1','2025-08-01 10:54:31'),(110,2,'logout','User logged out','::1','2025-08-01 11:02:28'),(111,1,'login','User logged in','::1','2025-08-01 11:02:31'),(112,1,'login','User logged in','::1','2025-08-01 12:05:49'),(113,1,'logout','User logged out','::1','2025-08-01 12:08:57'),(114,1,'login','User logged in','::1','2025-08-01 12:09:10'),(115,1,'logout','User logged out','::1','2025-08-01 12:12:34'),(116,2,'login','User logged in','::1','2025-08-01 12:12:39'),(117,2,'logout','User logged out','::1','2025-08-01 12:13:25'),(118,1,'login','User logged in','::1','2025-08-01 12:13:28'),(119,1,'logout','User logged out','::1','2025-08-01 12:13:51'),(120,3,'login','User logged in','::1','2025-08-01 12:13:54'),(121,3,'logout','User logged out','::1','2025-08-01 12:14:54'),(122,1,'login','User logged in','::1','2025-08-01 12:15:00'),(123,1,'logout','User logged out','::1','2025-08-01 12:15:16'),(124,2,'login','User logged in','::1','2025-08-01 12:15:23'),(125,2,'logout','User logged out','::1','2025-08-01 12:16:08'),(126,3,'login','User logged in','::1','2025-08-01 12:16:14'),(127,3,'logout','User logged out','::1','2025-08-01 12:22:55'),(128,2,'login','User logged in','::1','2025-08-01 12:23:02'),(129,2,'logout','User logged out','::1','2025-08-01 12:32:29'),(130,2,'login','User logged in','::1','2025-08-01 12:32:35'),(131,2,'logout','User logged out','::1','2025-08-01 12:34:06'),(132,3,'login','User logged in','::1','2025-08-01 12:34:09'),(133,3,'logout','User logged out','::1','2025-08-01 12:38:25'),(134,2,'login','User logged in','::1','2025-08-01 12:38:31'),(135,2,'logout','User logged out','::1','2025-08-01 12:39:09'),(136,3,'login','User logged in','::1','2025-08-01 12:39:17'),(137,3,'logout','User logged out','::1','2025-08-01 12:40:18'),(138,2,'login','User logged in','::1','2025-08-01 12:40:22'),(139,2,'logout','User logged out','::1','2025-08-01 12:41:11'),(140,1,'login','User logged in','::1','2025-08-01 12:41:13'),(141,1,'logout','User logged out','::1','2025-08-01 12:46:07'),(142,2,'login','User logged in','::1','2025-08-01 12:46:10'),(143,2,'login','User logged in','::1','2025-08-01 13:09:28'),(144,1,'logout','User logged out','::1','2025-08-01 13:12:45'),(145,1,'login','User logged in','::1','2025-08-01 13:12:48'),(146,1,'logout','User logged out','::1','2025-08-01 13:13:49'),(147,3,'login','User logged in','::1','2025-08-01 13:13:57'),(148,3,'logout','User logged out','::1','2025-08-01 13:15:44'),(149,2,'login','User logged in','::1','2025-08-01 13:17:27'),(150,2,'login','User logged in','::1','2025-08-01 13:40:08'),(151,2,'logout','User logged out','::1','2025-08-01 13:40:15'),(152,1,'login','User logged in','::1','2025-08-01 13:40:17'),(153,3,'login','User logged in','::1','2025-08-01 13:48:41'),(154,3,'logout','User logged out','::1','2025-08-01 13:54:06'),(155,1,'login','User logged in','::1','2025-08-01 13:54:47'),(156,1,'login','User logged in','::1','2025-08-01 14:06:24'),(157,1,'logout','User logged out','::1','2025-08-01 14:17:00'),(158,2,'login','User logged in','::1','2025-08-01 14:17:18'),(159,1,'login','User logged in','::1','2025-08-01 14:25:02'),(160,1,'logout','User logged out','::1','2025-08-01 14:25:07'),(161,1,'login','User logged in','::1','2025-08-01 14:26:27'),(162,1,'logout','User logged out','::1','2025-08-01 14:33:28'),(163,2,'login','User logged in','::1','2025-08-01 14:33:46'),(164,2,'logout','User logged out','::1','2025-08-01 14:35:52'),(165,1,'login','User logged in','::1','2025-08-01 14:35:55'),(166,1,'logout','User logged out','::1','2025-08-01 14:40:06'),(167,3,'login','User logged in','::1','2025-08-01 14:40:09'),(168,1,'login','User logged in','::1','2025-08-01 14:46:11'),(169,1,'logout','User logged out','::1','2025-08-01 14:46:19'),(170,2,'login','User logged in','::1','2025-08-01 14:46:27'),(171,2,'logout','User logged out','::1','2025-08-01 14:46:36'),(172,1,'login','User logged in','::1','2025-08-01 14:48:04'),(173,1,'logout','User logged out','::1','2025-08-01 14:48:54'),(174,1,'login','User logged in','::1','2025-08-01 14:49:19'),(175,1,'login','User logged in','::1','2025-08-01 14:50:08'),(176,1,'login','User logged in','::1','2025-08-01 14:51:37'),(177,1,'logout','User logged out','::1','2025-08-01 14:52:40'),(178,1,'login','User logged in','::1','2025-08-01 14:53:08'),(179,1,'logout','User logged out','::1','2025-08-01 14:53:27'),(180,2,'login','User logged in','::1','2025-08-01 14:53:46'),(181,2,'logout','User logged out','::1','2025-08-01 14:59:29'),(182,1,'login','User logged in','::1','2025-08-01 14:59:33'),(183,1,'login','User logged in','::1','2025-08-01 15:09:04'),(184,1,'system','Cleared 0 logs older than 30 days','::1','2025-08-01 15:30:11'),(185,1,'logout','User logged out','::1','2025-08-01 15:31:16'),(186,3,'login','User logged in','::1','2025-08-01 15:31:19'),(187,3,'logout','User logged out','::1','2025-08-01 15:49:35'),(188,2,'login','User logged in','::1','2025-08-01 15:49:42'),(189,2,'logout','User logged out','::1','2025-08-01 15:50:04'),(190,1,'login','User logged in','::1','2025-08-01 15:50:11'),(191,1,'login','User logged in','::1','2025-08-01 16:06:25'),(192,1,'logout','User logged out','::1','2025-08-01 16:06:32'),(193,3,'login','User logged in','::1','2025-08-01 16:06:37'),(194,1,'login','User logged in','::1','2025-08-01 23:56:19'),(195,1,'logout','User logged out','::1','2025-08-01 23:56:48'),(196,3,'login','User logged in','::1','2025-08-01 23:56:52'),(197,3,'login','User logged in','::1','2025-08-02 00:11:17'),(198,3,'logout','User logged out','::1','2025-08-02 00:12:23'),(199,2,'login','User logged in','::1','2025-08-02 00:12:29'),(200,2,'logout','User logged out','::1','2025-08-02 00:13:36'),(201,1,'login','User logged in','::1','2025-08-02 00:13:43'),(202,1,'logout','User logged out','::1','2025-08-02 00:13:51'),(203,3,'login','User logged in','::1','2025-08-02 00:13:57'),(204,3,'logout','User logged out','::1','2025-08-02 00:21:43'),(205,2,'login','User logged in','::1','2025-08-02 00:21:50'),(206,3,'logout','User logged out','::1','2025-08-02 00:22:55'),(207,2,'login','User logged in','::1','2025-08-02 00:23:02'),(208,3,'login','User logged in','::1','2025-08-02 00:31:43'),(209,3,'logout','User logged out','::1','2025-08-02 00:39:32'),(210,2,'login','User logged in','::1','2025-08-02 00:39:35'),(211,2,'logout','User logged out','::1','2025-08-02 00:48:37'),(212,3,'login','User logged in','::1','2025-08-02 00:48:40'),(213,3,'logout','User logged out','::1','2025-08-02 00:55:20'),(214,2,'login','User logged in','::1','2025-08-02 00:55:24'),(215,3,'login','User logged in','::1','2025-08-02 01:15:23'),(216,2,'login','User logged in','::1','2025-08-02 01:20:36'),(217,2,'login','User logged in','::1','2025-08-02 01:33:46'),(218,2,'logout','User logged out','::1','2025-08-02 01:44:38'),(219,3,'login','User logged in','::1','2025-08-02 01:44:44'),(220,1,'login','User logged in','::1','2025-08-02 01:52:51'),(221,1,'logout','User logged out','::1','2025-08-02 01:52:54'),(222,2,'login','User logged in','::1','2025-08-02 01:53:16'),(223,2,'logout','User logged out','::1','2025-08-02 01:59:01'),(224,3,'login','User logged in','::1','2025-08-02 01:59:06'),(225,3,'logout','User logged out','::1','2025-08-02 02:11:14'),(226,2,'login','User logged in','::1','2025-08-02 02:11:18'),(227,2,'logout','User logged out','::1','2025-08-02 02:13:19'),(228,3,'login','User logged in','::1','2025-08-02 02:13:24'),(229,3,'logout','User logged out','::1','2025-08-02 02:18:56'),(230,2,'login','User logged in','::1','2025-08-02 02:19:05'),(231,2,'logout','User logged out','::1','2025-08-02 02:19:25'),(232,3,'login','User logged in','::1','2025-08-02 02:19:31'),(233,3,'logout','User logged out','::1','2025-08-02 02:19:56'),(234,2,'login','User logged in','::1','2025-08-02 02:20:00'),(235,2,'logout','User logged out','::1','2025-08-02 02:20:11'),(236,1,'login','User logged in','::1','2025-08-02 02:20:17'),(237,1,'logout','User logged out','::1','2025-08-02 02:23:28'),(238,2,'login','User logged in','::1','2025-08-02 02:23:33'),(239,2,'logout','User logged out','::1','2025-08-02 02:28:41'),(240,1,'login','User logged in','::1','2025-08-02 02:28:46'),(241,1,'login','User logged in','::1','2025-08-02 02:47:51'),(242,1,'delete_consultation','Deleted consultation #7 for student keith torda. Removed: 18 messages, 1 chat sessions, 0 feedback, 0 notifications.','::1','2025-08-02 02:48:55'),(243,1,'logout','User logged out','::1','2025-08-02 02:59:20'),(244,2,'login','User logged in','::1','2025-08-02 02:59:26'),(245,2,'logout','User logged out','::1','2025-08-02 03:19:43'),(246,3,'login','User logged in','::1','2025-08-02 03:19:49'),(247,3,'logout','User logged out','::1','2025-08-02 03:34:09'),(248,2,'login','User logged in','::1','2025-08-02 03:34:13'),(249,2,'logout','User logged out','::1','2025-08-02 03:34:48'),(250,3,'login','User logged in','::1','2025-08-02 03:34:52'),(251,3,'logout','User logged out','::1','2025-08-02 03:35:17'),(252,2,'login','User logged in','::1','2025-08-02 03:35:23'),(253,2,'logout','User logged out','::1','2025-08-02 03:36:58'),(254,1,'login','User logged in','::1','2025-08-02 03:37:12'),(255,1,'logout','User logged out','::1','2025-08-02 03:37:22'),(256,3,'login','User logged in','::1','2025-08-02 03:37:26'),(257,3,'logout','User logged out','::1','2025-08-02 03:37:46'),(258,1,'login','User logged in','::1','2025-08-02 03:45:07'),(259,1,'delete_consultation','Deleted consultation #5 for student keith torda. Removed: 8 messages, 1 chat sessions, 0 feedback, 0 notifications.','::1','2025-08-02 04:20:03'),(260,1,'logout','User logged out','::1','2025-08-02 04:30:58'),(261,2,'login','User logged in','::1','2025-08-02 04:31:08'),(262,1,'logout','User logged out','::1','2025-08-02 04:46:43'),(263,2,'logout','User logged out','::1','2025-08-02 04:56:54'),(264,1,'login','User logged in','::1','2025-08-02 04:57:01'),(265,1,'login','User logged in','::1','2025-08-02 05:20:28'),(266,1,'database_update','Created system_notifications table','::1','2025-08-02 05:20:33'),(267,1,'database_update','Added profile_picture column to users table','::1','2025-08-02 05:20:56'),(268,1,'send_notification','Admin sent a notification to student','::1','2025-08-02 05:21:22'),(269,1,'logout','User logged out','::1','2025-08-02 05:21:25'),(270,2,'login','User logged in','::1','2025-08-02 05:21:29'),(271,2,'logout','User logged out','::1','2025-08-02 05:28:25'),(272,1,'login','User logged in','::1','2025-08-02 05:28:28'),(273,1,'login','User logged in','::1','2025-08-02 05:35:21'),(274,1,'logout','User logged out','::1','2025-08-02 05:37:07'),(275,3,'login','User logged in','::1','2025-08-02 05:37:27'),(276,3,'logout','User logged out','::1','2025-08-02 05:39:51'),(277,1,'login','User logged in','::1','2025-08-02 05:39:59'),(278,1,'send_notification','Admin sent a notification to all users','::1','2025-08-02 05:53:06'),(279,1,'send_notification','Admin sent a notification to student','::1','2025-08-02 06:45:19'),(280,1,'logout','User logged out','::1','2025-08-02 06:45:22'),(281,2,'login','User logged in','::1','2025-08-02 06:45:27'),(282,2,'logout','User logged out','::1','2025-08-02 06:59:33'),(283,3,'login','User logged in','::1','2025-08-02 06:59:36'),(284,3,'logout','User logged out','::1','2025-08-02 07:17:15'),(285,1,'login','User logged in','::1','2025-08-02 07:17:20'),(286,1,'send_notification','Admin sent a notification to all users','::1','2025-08-02 07:17:56'),(287,1,'logout','User logged out','::1','2025-08-02 07:18:06'),(288,3,'login','User logged in','::1','2025-08-02 07:18:12'),(289,3,'logout','User logged out','::1','2025-08-02 07:18:47'),(290,2,'login','User logged in','::1','2025-08-02 07:18:50'),(291,2,'logout','User logged out','::1','2025-08-02 07:19:20'),(292,3,'login','User logged in','::1','2025-08-02 07:19:23'),(293,3,'logout','User logged out','::1','2025-08-02 07:26:35'),(294,1,'login','User logged in','::1','2025-08-02 07:26:39'),(295,1,'logout','User logged out','::1','2025-08-02 07:26:57'),(296,3,'login','User logged in','::1','2025-08-02 07:27:04'),(297,3,'logout','User logged out','::1','2025-08-02 07:27:55'),(298,2,'login','User logged in','::1','2025-08-02 07:28:00'),(299,2,'logout','User logged out','::1','2025-08-02 07:36:08'),(300,3,'login','User logged in','::1','2025-08-02 07:36:13'),(301,3,'profile_update','User updated their profile picture','::1','2025-08-02 07:37:55'),(302,3,'profile_update','User updated their basic profile information','::1','2025-08-02 07:42:25'),(303,3,'logout','User logged out','::1','2025-08-02 07:42:54'),(304,2,'login','User logged in','::1','2025-08-02 07:42:58'),(305,2,'logout','User logged out','::1','2025-08-02 07:54:31'),(306,1,'login','User logged in','::1','2025-08-02 07:54:35'),(307,1,'delete_consultation','Deleted consultation #8 for student keith torda. Removed: 4 messages, 1 chat sessions, 0 feedback, 0 notifications.','::1','2025-08-02 07:55:48'),(308,1,'delete_consultation','Deleted consultation #6 for student keith torda. Removed: 19 messages, 1 chat sessions, 0 feedback, 0 notifications.','::1','2025-08-02 07:55:54'),(309,1,'delete_consultation','Deleted consultation #4 for student keith torda. Removed: 10 messages, 1 chat sessions, 0 feedback, 0 notifications.','::1','2025-08-02 07:55:57'),(310,1,'system','Deleted database backup: backup_2025-08-02_09-56-10.sql','::1','2025-08-02 07:56:14'),(311,1,'system','Created database backup: backup_2025-08-02_10-00-02.sql','::1','2025-08-02 08:00:02'),(312,1,'system','Sent test email to keithniiyoow@gmail.com','::1','2025-08-02 08:22:59'),(313,1,'logout','User logged out','::1','2025-08-02 08:28:48'),(314,2,'login','User logged in','::1','2025-08-02 08:28:54'),(315,2,'logout','User logged out','::1','2025-08-02 08:29:25'),(316,3,'login','User logged in','::1','2025-08-02 08:29:29'),(317,3,'logout','User logged out','::1','2025-08-02 08:35:37'),(318,2,'login','User logged in','::1','2025-08-02 08:35:42'),(319,2,'logout','User logged out','::1','2025-08-02 08:39:52'),(320,3,'login','User logged in','::1','2025-08-02 08:39:58'),(321,3,'logout','User logged out','::1','2025-08-02 08:41:30'),(322,1,'login','User logged in','::1','2025-08-02 08:41:33'),(323,1,'logout','User logged out','::1','2025-08-02 08:42:20'),(324,7,'login','User logged in','::1','2025-08-02 08:42:23'),(325,7,'logout','User logged out','::1','2025-08-02 08:42:47'),(326,3,'login','User logged in','::1','2025-08-02 08:42:54'),(327,3,'logout','User logged out','::1','2025-08-02 08:45:19'),(328,1,'login','User logged in','::1','2025-08-02 08:45:23'),(329,1,'update_user','Updated user: dave torda (ID: 0)','::1','2025-08-02 08:45:48'),(330,1,'logout','User logged out','::1','2025-08-02 08:45:53'),(331,3,'login','User logged in','::1','2025-08-02 08:45:56'),(332,3,'logout','User logged out','::1','2025-08-02 08:46:46'),(333,7,'login','User logged in','::1','2025-08-02 08:46:52'),(334,7,'logout','User logged out','::1','2025-08-02 08:47:12'),(335,3,'login','User logged in','::1','2025-08-02 08:47:20'),(336,3,'logout','User logged out','::1','2025-08-02 08:49:13'),(337,3,'login','User logged in','::1','2025-08-02 08:49:19'),(338,3,'logout','User logged out','::1','2025-08-02 08:58:08'),(339,1,'login','User logged in','::1','2025-08-02 08:58:11'),(340,1,'logout','User logged out','::1','2025-08-02 08:58:54'),(341,8,'login','User logged in','::1','2025-08-02 09:44:49'),(342,8,'logout','User logged out','::1','2025-08-02 09:45:54'),(343,1,'login','User logged in','::1','2025-08-02 09:55:10'),(344,1,'logout','User logged out','::1','2025-08-02 09:55:54'),(345,1,'login','User logged in','::1','2025-08-02 09:56:25'),(346,1,'update_user','Updated user: richael ulibas (ID: 6)','::1','2025-08-02 10:07:45'),(347,1,'logout','User logged out','::1','2025-08-02 10:13:41'),(348,1,'login','User logged in','::1','2025-08-02 10:14:02'),(349,1,'logout','User logged out','::1','2025-08-02 10:14:42'),(350,2,'login','User logged in','::1','2025-08-02 10:14:45'),(351,2,'logout','User logged out','::1','2025-08-02 10:19:35'),(352,2,'login','User logged in','::1','2025-08-02 10:21:56'),(353,2,'logout','User logged out','::1','2025-08-02 10:26:17'),(354,1,'login','User logged in','::1','2025-08-02 10:26:20'),(355,1,'send_notification','Admin sent a notification to all users','::1','2025-08-02 10:27:51'),(356,1,'logout','User logged out','::1','2025-08-02 10:27:58'),(357,3,'login','User logged in','::1','2025-08-02 10:28:10'),(358,3,'logout','User logged out','::1','2025-08-02 10:28:34'),(359,1,'login','User logged in','::1','2025-08-02 10:28:37'),(360,1,'update_user','Updated user: keith torda (ID: 2)','::1','2025-08-02 10:29:47'),(361,1,'profile_update','User updated their profile picture','::1','2025-08-02 10:31:41'),(362,1,'profile_update','User updated their basic profile information','::1','2025-08-02 10:31:48'),(363,1,'logout','User logged out','::1','2025-08-02 10:31:58'),(364,1,'login','User logged in','::1','2025-08-02 10:32:09'),(365,1,'update_user','Updated user: keith torda (ID: 2)','::1','2025-08-02 10:32:23'),(366,1,'logout','User logged out','::1','2025-08-02 10:32:28'),(367,2,'login','User logged in','::1','2025-08-02 10:32:31'),(368,2,'logout','User logged out','::1','2025-08-02 10:33:48'),(369,3,'login','User logged in','::1','2025-08-02 10:33:52'),(370,3,'logout','User logged out','::1','2025-08-02 10:35:02'),(371,1,'login','User logged in','::1','2025-08-02 10:35:05'),(372,1,'logout','User logged out','::1','2025-08-02 10:35:55'),(373,2,'login','User logged in','::1','2025-08-02 10:35:58'),(374,3,'login','User logged in','::1','2025-08-02 10:36:29'),(375,2,'logout','User logged out','::1','2025-08-02 10:48:14'),(376,1,'login','User logged in','::1','2025-08-02 10:48:17'),(377,2,'login','User logged in','::1','2025-08-02 11:01:05'),(378,2,'logout','User logged out','::1','2025-08-02 11:05:36'),(379,1,'login','User logged in','::1','2025-08-02 11:05:39'),(380,1,'delete_consultation','Deleted consultation #15 for student keith torda. Removed: 6 messages, 1 chat sessions, 1 feedback, 0 notifications.','::1','2025-08-02 11:10:16'),(381,1,'logout','User logged out','::1','2025-08-02 11:11:22'),(382,9,'login','User logged in','::1','2025-08-02 11:20:03'),(383,1,'login','User logged in','::1','2025-08-02 13:24:48'),(384,1,'logout','User logged out','::1','2025-08-02 13:35:36'),(385,1,'login','User logged in','::1','2025-08-02 14:10:02'),(386,1,'logout','User logged out','::1','2025-08-02 14:10:07'),(387,1,'login','User logged in successfully','::1','2025-08-02 14:26:05'),(388,1,'update_user','Updated user: dave torda (ID: 7)','::1','2025-08-02 14:29:47'),(389,1,'update_user','Updated user: richael ulibas (ID: 6)','::1','2025-08-02 14:29:56'),(390,1,'logout','User logged out','::1','2025-08-02 14:30:10'),(395,2,'login','User logged in successfully','::1','2025-08-02 14:36:11'),(396,2,'logout','User logged out','::1','2025-08-02 14:36:21'),(397,1,'login','User logged in successfully','::1','2025-08-02 14:36:24'),(398,1,'system','Created database backup: backup_2025-08-02_16-42-07.sql','::1','2025-08-02 14:42:08'),(399,1,'system','Deleted database backup: backup_2025-08-02_10-00-02.sql','::1','2025-08-02 14:42:12'),(400,2,'login','User logged in successfully','::1','2025-08-02 14:42:43'),(401,2,'logout','User logged out','::1','2025-08-02 14:51:27'),(402,1,'logout','User logged out','::1','2025-08-02 14:51:39'),(403,1,'login','User logged in successfully','::1','2025-08-02 14:52:13'),(404,1,'logout','User logged out','::1','2025-08-02 14:55:21'),(405,1,'login','User logged in successfully','192.168.1.15','2025-08-02 15:22:01'),(406,1,'logout','User logged out','192.168.1.15','2025-08-02 15:23:57'),(407,2,'login','User logged in successfully','192.168.1.15','2025-08-02 15:24:02'),(408,2,'logout','User logged out','192.168.1.15','2025-08-02 15:30:01'),(409,3,'login','User logged in successfully','192.168.1.15','2025-08-02 15:30:11'),(410,3,'logout','User logged out','192.168.1.15','2025-08-02 15:32:52'),(411,2,'login','User logged in successfully','192.168.1.15','2025-08-02 15:32:58'),(412,2,'logout','User logged out','192.168.1.15','2025-08-02 15:33:21'),(413,3,'login','User logged in successfully','192.168.1.15','2025-08-02 15:33:30'),(414,1,'login','User logged in successfully','::1','2025-08-03 00:26:03'),(415,1,'update_user','Updated user: richael ulibas (ID: 6)','::1','2025-08-03 00:28:27'),(416,1,'login','User logged in successfully','192.168.1.5','2025-08-03 00:29:31'),(417,1,'logout','User logged out','192.168.1.5','2025-08-03 00:31:45'),(418,3,'login','User logged in successfully','192.168.1.5','2025-08-03 00:31:51'),(419,3,'logout','User logged out','192.168.1.5','2025-08-03 00:36:43'),(420,2,'login','User logged in successfully','192.168.1.5','2025-08-03 00:36:55'),(421,2,'logout','User logged out','192.168.1.5','2025-08-03 00:43:19'),(422,1,'login','User logged in successfully','192.168.1.5','2025-08-03 00:43:22'),(423,1,'login','User logged in successfully','192.168.1.15','2025-08-03 00:50:51'),(424,1,'logout','User logged out','192.168.1.5','2025-08-03 01:23:51'),(425,1,'login','User logged in successfully','192.168.1.5','2025-08-03 01:24:59'),(426,1,'logout','User logged out','192.168.1.15','2025-08-03 01:26:53'),(427,2,'login','User logged in successfully','192.168.1.15','2025-08-03 01:27:00'),(428,1,'logout','User logged out','192.168.1.5','2025-08-03 01:37:17'),(429,2,'login','User logged in successfully','192.168.1.5','2025-08-03 01:37:23'),(430,2,'logout','User logged out','192.168.1.5','2025-08-03 01:37:49'),(431,1,'login','User logged in successfully','192.168.1.5','2025-08-03 01:42:26'),(432,2,'logout','User logged out','192.168.1.15','2025-08-03 01:44:41'),(433,2,'login','User logged in successfully','192.168.1.15','2025-08-03 01:56:37'),(434,2,'logout','User logged out','192.168.1.15','2025-08-03 01:57:15'),(435,3,'login','User logged in successfully','192.168.1.15','2025-08-03 01:57:21'),(436,3,'logout','User logged out','192.168.1.15','2025-08-03 01:58:31'),(437,2,'login','User logged in successfully','192.168.1.15','2025-08-03 01:58:35'),(438,2,'logout','User logged out','192.168.1.15','2025-08-03 01:59:21'),(439,2,'login','User logged in successfully','::1','2025-08-03 02:17:16'),(440,2,'login','User logged in successfully','192.168.1.5','2025-08-03 02:17:23'),(441,2,'logout','User logged out','192.168.1.5','2025-08-03 02:32:25'),(442,1,'login','User logged in successfully','192.168.1.5','2025-08-03 02:39:18'),(443,1,'login','User logged in successfully','192.168.1.15','2025-08-03 02:39:37'),(444,1,'logout','User logged out','192.168.1.15','2025-08-03 02:39:56'),(445,1,'login','User logged in successfully','192.168.1.15','2025-08-03 02:43:30'),(446,1,'login','User logged in successfully','192.168.1.15','2025-08-03 02:46:42'),(447,1,'delete_consultation','Deleted consultation #14 for student dave torda. Removed: 0 messages, 0 chat sessions, 0 feedback, 0 notifications.','192.168.1.15','2025-08-03 02:47:10'),(448,1,'delete_consultation','Deleted consultation #12 for student keith torda. Removed: 0 messages, 0 chat sessions, 1 feedback, 0 notifications.','192.168.1.15','2025-08-03 02:47:17'),(449,1,'delete_consultation','Deleted consultation #13 for student dave torda. Removed: 0 messages, 0 chat sessions, 0 feedback, 0 notifications.','192.168.1.15','2025-08-03 02:47:21'),(450,1,'delete_consultation','Deleted consultation #11 for student keith torda. Removed: 2 messages, 1 chat sessions, 1 feedback, 0 notifications.','192.168.1.15','2025-08-03 02:47:29'),(451,1,'delete_consultation','Deleted consultation #10 for student keith torda. Removed: 6 messages, 1 chat sessions, 1 feedback, 0 notifications.','192.168.1.15','2025-08-03 02:47:32'),(452,1,'update_user','Updated user: richael ulibas (ID: 6)','192.168.1.15','2025-08-03 02:48:32'),(453,1,'logout','User logged out','192.168.1.15','2025-08-03 02:49:23'),(454,2,'login','User logged in successfully','192.168.1.15','2025-08-03 02:49:28'),(455,2,'logout','User logged out','192.168.1.15','2025-08-03 02:49:41'),(456,1,'login','User logged in successfully','192.168.1.15','2025-08-03 02:49:47'),(457,1,'logout','User logged out','192.168.1.15','2025-08-03 02:51:08'),(458,11,'login','User logged in successfully','192.168.1.15','2025-08-03 02:51:15'),(459,11,'logout','User logged out','192.168.1.15','2025-08-03 02:53:19'),(460,1,'login','User logged in successfully','192.168.1.15','2025-08-03 02:53:26'),(461,1,'update_user','Updated user: Kathreeza Castillo (ID: 3)','192.168.1.15','2025-08-03 02:53:57'),(462,1,'logout','User logged out','192.168.1.15','2025-08-03 02:54:05'),(463,11,'login','User logged in successfully','192.168.1.15','2025-08-03 02:54:17'),(464,1,'logout','User logged out','192.168.1.5','2025-08-03 02:56:40'),(465,11,'login','User logged in successfully','192.168.1.5','2025-08-03 02:56:53'),(466,11,'profile_update','User updated their profile picture','192.168.1.5','2025-08-03 02:57:13'),(467,11,'profile_update','User updated their basic profile information','192.168.1.5','2025-08-03 02:57:17'),(468,11,'logout','User logged out','192.168.1.5','2025-08-03 02:57:27'),(469,2,'login','User logged in successfully','192.168.1.5','2025-08-03 02:57:31'),(470,2,'logout','User logged out','192.168.1.5','2025-08-03 02:58:08'),(471,1,'login','User logged in successfully','192.168.1.5','2025-08-03 02:58:13'),(472,1,'logout','User logged out','192.168.1.5','2025-08-03 02:58:42'),(473,1,'login','User logged in successfully','192.168.1.5','2025-08-03 02:59:07'),(474,1,'logout','User logged out','192.168.1.5','2025-08-03 02:59:13'),(475,3,'login','User logged in successfully','192.168.1.5','2025-08-03 02:59:16'),(476,3,'logout','User logged out','192.168.1.5','2025-08-03 03:06:24'),(477,1,'login','User logged in successfully','192.168.1.5','2025-08-03 03:06:29'),(478,1,'system','Cleared 0 logs older than 30 days','192.168.1.5','2025-08-03 03:06:46'),(479,1,'system','Cleared 0 logs older than 30 days','192.168.1.5','2025-08-03 03:06:57'),(480,2,'login','User logged in successfully','192.168.1.15','2025-08-03 03:18:48'),(481,1,'logout','User logged out','192.168.1.5','2025-08-03 03:21:00'),(482,1,'logout','User logged out','192.168.1.5','2025-08-03 03:30:33'),(483,2,'login','User logged in successfully','192.168.1.5','2025-08-03 03:30:36'),(484,2,'profile_update','Student updated their profile information','192.168.1.5','2025-08-03 03:34:40'),(485,2,'profile_update','Student updated their profile information','192.168.1.5','2025-08-03 03:34:48'),(486,2,'logout','User logged out','192.168.1.5','2025-08-03 03:34:57'),(487,1,'login','User logged in successfully','192.168.1.5','2025-08-03 03:35:00'),(488,1,'update_user','Updated user: keith torda (ID: 2)','192.168.1.5','2025-08-03 03:40:23'),(489,1,'logout','User logged out','192.168.1.5','2025-08-03 03:40:28'),(490,2,'login','User logged in successfully','192.168.1.5','2025-08-03 03:40:31'),(491,2,'logout','User logged out','192.168.1.5','2025-08-03 03:40:42'),(492,1,'login','User logged in successfully','::1','2025-08-03 05:34:42'),(493,1,'login','User logged in successfully','::1','2025-08-03 05:37:52'),(494,1,'login','User logged in successfully','::1','2025-08-03 06:58:56'),(495,1,'login','User logged in successfully','::1','2025-08-03 07:14:07'),(496,1,'system','Sent test email to keithorario@gmail.com','::1','2025-08-03 07:18:23'),(497,1,'logout','User logged out','::1','2025-08-03 07:19:30'),(498,1,'login','User logged in successfully','::1','2025-08-03 07:42:24'),(499,1,'logout','User logged out','::1','2025-08-03 07:46:41'),(500,1,'login','User logged in successfully','::1','2025-08-03 07:52:32'),(501,1,'login','User logged in successfully','::1','2025-08-03 07:56:58'),(502,1,'logout','User logged out','::1','2025-08-03 07:58:06'),(503,1,'login','User logged in successfully','::1','2025-08-03 07:58:48'),(504,1,'logout','User logged out','::1','2025-08-03 07:58:53'),(505,1,'login','User logged in successfully','::1','2025-08-03 08:16:01'),(506,1,'logout','User logged out','::1','2025-08-03 08:17:13'),(507,1,'login','User logged in successfully','::1','2025-08-03 08:19:43'),(508,1,'logout','User logged out','::1','2025-08-03 08:20:13'),(509,1,'login','User logged in successfully','::1','2025-08-03 08:20:50'),(510,1,'login','User logged in successfully','192.168.1.15','2025-08-03 08:21:05'),(511,1,'logout','User logged out','192.168.1.15','2025-08-03 08:21:29'),(512,1,'login','User logged in successfully','::1','2025-08-03 08:46:38'),(513,1,'update_user','Updated user: keith torda (ID: 2)','::1','2025-08-03 08:49:21'),(514,1,'update_user','Updated user: keit torda (ID: 2)','::1','2025-08-03 08:49:29'),(515,2,'login','User logged in successfully','::1','2025-08-03 08:50:01'),(516,1,'login','User logged in successfully','::1','2025-08-03 09:11:42'),(517,1,'login','User logged in successfully','::1','2025-08-03 09:18:05'),(518,1,'login','User logged in successfully','::1','2025-08-03 09:20:52'),(519,1,'login','User logged in successfully','192.168.1.15','2025-08-03 09:21:18'),(520,1,'login','User logged in successfully','192.168.1.15','2025-08-03 09:23:03'),(521,1,'update_user','Updated user: keith torda (ID: 2)','192.168.1.15','2025-08-03 09:23:29'),(522,1,'login','User logged in successfully','::1','2025-08-03 09:29:49'),(523,1,'login','User logged in successfully','::1','2025-08-03 09:31:51'),(524,2,'login','User logged in successfully','::1','2025-08-03 09:33:25'),(525,2,'login','User logged in successfully','::1','2025-08-03 09:33:54'),(526,2,'login','User logged in successfully','::1','2025-08-03 09:36:19'),(527,1,'login','User logged in successfully','192.168.1.15','2025-08-03 10:14:59'),(528,1,'login','User logged in successfully','192.168.1.15','2025-08-03 10:21:15');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_notifications`
--

DROP TABLE IF EXISTS `system_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `target_role` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(20) DEFAULT 'info',
  `category` varchar(50) DEFAULT 'system',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `target_role` (`target_role`)
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_notifications`
--

LOCK TABLES `system_notifications` WRITE;
/*!40000 ALTER TABLE `system_notifications` DISABLE KEYS */;
INSERT INTO `system_notifications` VALUES (23,NULL,'staff','Test notification for staff role','info','system','#',0,NULL,'2025-08-02 05:35:59'),(27,NULL,'staff','hi','success','announcement',NULL,0,NULL,'2025-08-02 05:36:08'),(44,NULL,'staff','Simple notification for all staffs!','success','system',NULL,0,NULL,'2025-08-02 05:47:48'),(48,4,NULL,'testing norif','info','announcement','',0,NULL,'2025-08-02 05:53:06'),(49,6,NULL,'testing norif','info','announcement','',0,NULL,'2025-08-02 05:53:06'),(65,NULL,'staff','Test notification for all staffs at 08:18:46','info','system',NULL,0,NULL,'2025-08-02 06:18:46'),(72,4,NULL,'PLEASE BE CAREFUL WHEN SENDING YOUR INFORMATIONS','warning','announcement','',0,NULL,'2025-08-02 07:17:56'),(73,6,NULL,'PLEASE BE CAREFUL WHEN SENDING YOUR INFORMATIONS','warning','announcement','',0,NULL,'2025-08-02 07:17:56'),(95,7,NULL,'Your consultation request has been approved.','success','consultation','http://localhost/EGABAY//dashboard/student/view_consultation.php?id=13',0,NULL,'2025-08-02 08:43:30'),(101,7,NULL,'Your consultation request has been approved.','success','consultation','http://localhost/EGABAY//dashboard/student/view_consultation.php?id=14',0,NULL,'2025-08-02 08:47:39'),(103,7,NULL,'Your consultation has been marked completed.','info','consultation','http://localhost/EGABAY//dashboard/student/view_consultation.php?id=14',0,NULL,'2025-08-02 08:50:16'),(104,3,NULL,'Consultation marked completed.','info','consultation','http://localhost/EGABAY//dashboard/counselor/view_consultation.php?id=14',1,'2025-08-03 08:31:57','2025-08-02 08:50:16'),(106,7,NULL,'Your consultation has been marked completed.','info','consultation','http://localhost/EGABAY//dashboard/student/view_consultation.php?id=13',0,NULL,'2025-08-02 08:50:40'),(107,3,NULL,'Consultation marked completed.','info','consultation','http://localhost/EGABAY//dashboard/counselor/view_consultation.php?id=13',1,'2025-08-03 08:31:57','2025-08-02 08:50:40'),(110,2,NULL,'APAYAO STATE COLLEGE is commited','info','event','',1,'2025-08-03 10:58:02','2025-08-02 10:27:51'),(111,3,NULL,'APAYAO STATE COLLEGE is commited','info','event','',1,'2025-08-03 08:31:57','2025-08-02 10:27:51'),(112,4,NULL,'APAYAO STATE COLLEGE is commited','info','event','',0,NULL,'2025-08-02 10:27:51'),(113,6,NULL,'APAYAO STATE COLLEGE is commited','info','event','',0,NULL,'2025-08-02 10:27:51'),(114,7,NULL,'APAYAO STATE COLLEGE is commited','info','event','',0,NULL,'2025-08-02 10:27:51'),(115,8,NULL,'APAYAO STATE COLLEGE is commited','info','event','',0,NULL,'2025-08-02 10:27:51'),(117,3,NULL,'A new consultation has been assigned to you.','info','consultation','http://localhost/egabay//dashboard/counselor/view_consultation.php?id=15',1,'2025-08-03 08:31:57','2025-08-02 10:33:23'),(118,3,NULL,'A consultation has been assigned to you and awaits approval.','info','consultation','http://localhost/egabay//dashboard/counselor/view_consultation.php?id=15',1,'2025-08-03 08:31:57','2025-08-02 10:34:53'),(120,2,NULL,'Your consultation request has been approved.','success','consultation','http://localhost/egabay//dashboard/student/view_consultation.php?id=15',1,'2025-08-03 10:58:02','2025-08-02 10:35:23'),(121,3,NULL,'A new consultation has been assigned to you.','info','consultation','http://localhost/egabay//dashboard/counselor/view_consultation.php?id=15',1,'2025-08-03 08:31:57','2025-08-02 10:35:23'),(122,2,NULL,'Your consultation has been marked completed.','info','consultation','http://localhost/egabay//dashboard/student/view_consultation.php?id=15',1,'2025-08-03 10:58:02','2025-08-02 10:39:12'),(123,3,NULL,'Consultation marked completed.','info','consultation','http://localhost/egabay//dashboard/counselor/view_consultation.php?id=15',1,'2025-08-03 08:31:57','2025-08-02 10:39:12'),(126,3,NULL,'A new consultation has been assigned to you.','info','consultation','http://192.168.1.5/egabay//dashboard/counselor/view_consultation.php?id=16',1,'2025-08-03 08:31:57','2025-08-02 15:29:57'),(127,2,NULL,'Your consultation request has been approved.','success','consultation','http://192.168.1.5/egabay//dashboard/student/view_consultation.php?id=16',1,'2025-08-03 10:58:02','2025-08-02 15:30:51'),(128,3,NULL,'A new consultation has been assigned to you.','info','consultation','http://192.168.1.5/egabay//dashboard/counselor/view_consultation.php?id=16',1,'2025-08-03 08:31:57','2025-08-02 15:30:51'),(129,2,NULL,'Your consultation has been marked completed.','info','consultation','http://192.168.1.5/egabay//dashboard/student/view_consultation.php?id=16',1,'2025-08-03 10:58:02','2025-08-03 01:44:08'),(130,3,NULL,'Consultation marked completed.','info','consultation','http://192.168.1.5/egabay//dashboard/counselor/view_consultation.php?id=16',0,NULL,'2025-08-03 01:44:08'),(133,3,NULL,'A new consultation has been assigned to you.','info','consultation','http://192.168.1.5/egabay//dashboard/counselor/view_consultation.php?id=17',0,NULL,'2025-08-03 01:57:04'),(134,2,NULL,'Your consultation request has been approved.','success','consultation','http://192.168.1.5/egabay//dashboard/student/view_consultation.php?id=17',1,'2025-08-03 10:58:02','2025-08-03 01:57:39'),(135,3,NULL,'A new consultation has been assigned to you.','info','consultation','http://192.168.1.5/egabay//dashboard/counselor/view_consultation.php?id=17',0,NULL,'2025-08-03 01:57:39'),(136,2,NULL,'Your consultation has been marked completed.','info','consultation','http://192.168.1.5/egabay//dashboard/student/view_consultation.php?id=17',1,'2025-08-03 10:58:02','2025-08-03 01:58:52'),(137,3,NULL,'Consultation marked completed.','info','consultation','http://192.168.1.5/egabay//dashboard/counselor/view_consultation.php?id=17',0,NULL,'2025-08-03 01:58:53');
/*!40000 ALTER TABLE `system_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$wxVnmoEiBRazB.IF3WxYHu8rzfO9XoTFS1PUx5S7PODHQMidYGX06','System','Administrator','admin@egabay.edu.ph',3,1,'2025-08-03 18:21:15','2025-07-22 10:09:33','2025-08-03 10:21:15','profile_688de90d7ffad.jpg',NULL,1),(2,'keith','$2y$10$N8orkCq8D7K28vaZRuzDxOhCb2ngXkhLUuZ6VSUWig21VaUgi3H7e','keith','torda','keithniiyoow@gmail.com',1,1,'2025-08-03 17:36:19','2025-07-22 11:40:36','2025-08-03 09:36:19',NULL,NULL,1),(3,'counsil','$2y$10$yy6RdCQLDXvKl.A4RaGoKO7IWR8z0ncl7TGfkFBN3PRvUWftyUp0W','Kathreeza','Castillo','counsil@gmail.com',2,1,'2025-08-03 10:59:16','2025-07-22 12:25:57','2025-08-03 02:59:16','profile_688dc05317f5c.png',NULL,1),(4,'staff','$2y$10$UYbp1BVhPTZpTeBlk/R9tutIpBAELjWvmSpXYyzalgEhMVC8ROOFm','staff','1','staff1@gmail.com',4,1,'2025-07-22 23:23:00','2025-07-22 15:22:55','2025-07-22 15:23:00',NULL,NULL,0),(7,'dave','$2y$10$J.tAVLal7dnxhSrpCjJvDuuaXBvrx5S7.fcsEYSSp3Gr1.8iEntKW','dave','torda','davetorda47@gmail.com',1,1,'2025-08-02 16:46:52','2025-08-02 08:42:18','2025-08-02 14:29:47',NULL,NULL,1),(8,'rheii','$2y$10$up9smaEvKbVzp1H5Wu47ru5Fj4DJzA8w6.TLJRraqj3EprAkwzbMO','rheii','rheii','rheii8829@gmail.com',1,1,'2025-08-02 17:44:49','2025-08-02 09:43:43','2025-08-03 03:41:03',NULL,NULL,1),(9,'keithb','$2y$10$C9Y6eiw1MI4Sipsl6.FIzOqNDGSiXMpNyaMYFaIznsicxs6eQN/GG','keithb','tordaa','stanleyvein@gmail.com',1,1,'2025-08-02 19:20:03','2025-08-02 11:19:38','2025-08-02 11:20:03',NULL,NULL,1),(11,'counsil2','$2y$10$jwixTDTpQkjDD3KS/m3zNuAanNMuJuVKJLogtIfmU63iypWtERtBC','Shitsuwy','Ulibas','magaralgems@gmail.com',2,1,'2025-08-03 10:56:53','2025-08-03 02:51:01','2025-08-03 02:57:13','profile_688ed00949eae.jpg',NULL,1),(12,'asdasd','$2y$10$Wi0Bh7ZD.geTLOw46VvbiuUPScNTqqSFjVDxZ5BkwLibzMUewmUiC','ribe','asd','ribegid295@im5z.com',1,1,NULL,'2025-08-03 05:30:07','2025-08-03 05:30:07',NULL,'b38602eb0bccbb95ba267003f09638ec19c86efb',0),(15,'egabayy','$2y$10$Ex2lzdzvvqjsA5y3olbAAuiqaHdO4VxxNoYVidVFanldCh0rG2pkq','asdasda','asdjasdj','imjoyciee@gmail.com',1,1,NULL,'2025-08-03 09:30:32','2025-08-03 09:37:55',NULL,NULL,1),(16,'Drin25','$2y$10$cWSIu284AIBl8YCTkGR0sun/mo3Rm7M9ba/sDgf3htHPnbO4o4Bd6','ALDRIN','TALAY','antalay18@gmail.com',1,1,NULL,'2025-08-03 09:37:55','2025-08-03 09:38:59',NULL,NULL,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-03 18:21:31
