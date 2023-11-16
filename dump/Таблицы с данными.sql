-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: zayvka.ru    Database: j15258667_lidsmet-bot
-- ------------------------------------------------------
-- Server version	5.5.5-10.5.22-MariaDB-cll-lve-log

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
-- Table structure for table `t_state`
--

DROP TABLE IF EXISTS `t_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `t_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `t_state`
--

LOCK TABLES `t_state` WRITE;
/*!40000 ALTER TABLE `t_state` DISABLE KEYS */;
INSERT INTO `t_state` VALUES (1,'Создана'),(2,'В работе'),(3,'Не актуально'),(4,'Отправил КП'),(5,'Выставил счет'),(6,'Сожжена'),(7,'Спам');
/*!40000 ALTER TABLE `t_state` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `t_param`
--

DROP TABLE IF EXISTS `t_param`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `t_param` (
  `code` varchar(45) NOT NULL,
  `value` varchar(45) DEFAULT NULL,
  `comment` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `t_param`
--

LOCK TABLES `t_param` WRITE;
/*!40000 ALTER TABLE `t_param` DISABLE KEYS */;
INSERT INTO `t_param` VALUES ('count_minute','10','количество минут'),('count_request','10','количество заявок');
/*!40000 ALTER TABLE `t_param` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `t_imap_data`
--

DROP TABLE IF EXISTS `t_imap_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `t_imap_data` (
  `domen` varchar(100) NOT NULL,
  `server` varchar(100) DEFAULT NULL,
  `port` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`domen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `t_imap_data`
--

LOCK TABLES `t_imap_data` WRITE;
/*!40000 ALTER TABLE `t_imap_data` DISABLE KEYS */;
INSERT INTO `t_imap_data` VALUES ('bk.ru','imap.mail.ru','993'),('inbox.ru','imap.mail.ru','993'),('internet.ru','imap.mail.ru','993'),('list.ru','imap.mail.ru','993'),('mail.ru','imap.mail.ru','993'),('ya.ru','imap.yandex.ru','993'),('yandex.ru','imap.yandex.ru','993');
/*!40000 ALTER TABLE `t_imap_data` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-11-16 18:31:43
