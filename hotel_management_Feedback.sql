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
-- Table structure for table `Feedback`
--

DROP TABLE IF EXISTS `Feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Feedback` (
  `FeedbackID` int NOT NULL AUTO_INCREMENT,
  `Rating` int DEFAULT NULL,
  `Comment` text,
  `Reply` text,
  `FeedbackDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `AccountID` int NOT NULL,
  `RoomID` int NOT NULL,
  PRIMARY KEY (`FeedbackID`),
  KEY `AccountID` (`AccountID`),
  KEY `RoomID` (`RoomID`),
  KEY `idx_feedback_rating` (`Rating`),
  CONSTRAINT `Feedback_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE RESTRICT,
  CONSTRAINT `Feedback_ibfk_2` FOREIGN KEY (`RoomID`) REFERENCES `Room` (`RoomID`) ON DELETE RESTRICT,
  CONSTRAINT `Feedback_chk_1` CHECK (((`Rating` >= 1) and (`Rating` <= 5)))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Feedback`
--

LOCK TABLES `Feedback` WRITE;
/*!40000 ALTER TABLE `Feedback` DISABLE KEYS */;
INSERT INTO `Feedback` VALUES (4,5,'Good',NULL,'2025-07-27 00:00:00',20,21),(5,5,'The room was clean, tidy and very airy. I felt very comfortable during my stay.',NULL,'2025-07-28 00:00:00',21,2),(6,5,'Modern interior, well laid out. Feels like home!',NULL,'2025-07-28 00:00:00',21,9),(7,5,'Comfortable bed, fragrant sheets. Had a great night\'s sleep!',NULL,'2025-07-28 00:00:00',21,22),(8,4,'good','Thanks','2025-07-29 00:00:00',23,2),(9,5,'phòng tốt',NULL,'2025-07-29 00:00:00',25,2);
/*!40000 ALTER TABLE `Feedback` ENABLE KEYS */;
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
