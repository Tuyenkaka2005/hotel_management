-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: hotel_management
-- ------------------------------------------------------
-- Server version	8.0.42-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Account`
--

DROP TABLE IF EXISTS `Account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Account` (
  `AccountID` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Admin','Customer') DEFAULT NULL,
  `FullName` varchar(100) NOT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `Address` text,
  `Email` varchar(100) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`AccountID`),
  UNIQUE KEY `Username` (`Username`),
  UNIQUE KEY `Email` (`Email`),
  KEY `idx_account_username` (`Username`),
  KEY `idx_account_email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Account`
--

LOCK TABLES `Account` WRITE;
/*!40000 ALTER TABLE `Account` DISABLE KEYS */;
INSERT INTO `Account` VALUES (15,'Tuyen123','$2y$10$MvBsKPQICvsiFrJrRnvSh.TqgGsrX3BVobns5iQxcqqmdqGJKdOvi','Admin','Nguyen Dang Tuyen','Male','Ha Noi 2','tuyen@s23.com','123456'),(20,'clone1','$2y$10$j3urTGN7QbxnZdsK8cUYX.tJUglWmyCMl7zszBiwq.UjrUfGpgyia','Customer','Clone1',NULL,NULL,'clon@1.com','123445677'),(21,'clone2','$2y$10$BUYXm2aNPyJAHFKX5TDsH.M5m6X/FU21P3bIsef0y0HdVcmCOsU.i','Customer','clone2',NULL,NULL,'tuyen@gm.com','113213223'),(23,'vanduy1','$2y$10$ReuES/WXbH0R/O84ouxZReu/iac1wCsDk7E9AHXf2qjI30H4z6qVC','Customer','van dat',NULL,NULL,'kien@gmail.com','0795067992'),(24,'dat111','$2y$10$OAT6kR7II1OuXEFqllfcp.vMZgRbCuzT78aG5qe05otCzNqhwstA2','Customer','van duy',NULL,NULL,'kienne@gmail.com','093848291'),(25,'huydat','$2y$10$5TmiVpk7Ni9lHyHTXsc0suUreDggppeTNUiUekNm9OyBn407hfd7W','Customer','dat',NULL,NULL,'dathuyad02@gmail.com','0363200759');
/*!40000 ALTER TABLE `Account` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-03  1:44:21
