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
-- Table structure for table `RoomImage`
--

DROP TABLE IF EXISTS `RoomImage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RoomImage` (
  `ImageID` int NOT NULL AUTO_INCREMENT,
  `ImagePath` varchar(255) NOT NULL,
  `Caption` varchar(255) DEFAULT NULL,
  `RoomID` int NOT NULL,
  PRIMARY KEY (`ImageID`),
  KEY `RoomID` (`RoomID`),
  CONSTRAINT `RoomImage_ibfk_1` FOREIGN KEY (`RoomID`) REFERENCES `Room` (`RoomID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RoomImage`
--

LOCK TABLES `RoomImage` WRITE;
/*!40000 ALTER TABLE `RoomImage` DISABLE KEYS */;
INSERT INTO `RoomImage` VALUES (22,'6886447f9c9bd_room1.jpg',NULL,2),(23,'688644b38f98d_room2.jpg',NULL,13),(24,'688644dc31933_room3.jpg',NULL,9),(25,'6886450aae5a6_room4.jpg',NULL,11),(26,'6886452ea73ca_room5.jpg',NULL,3),(27,'688645671dbff_room6.jpg',NULL,10),(28,'688646e05306b_imgs_hotel1.webp',NULL,21),(29,'6886471fd67c1_img_hotel1.webp',NULL,22),(30,'6886474fe448f_imgs_hotel1.3.webp',NULL,23),(31,'68886224093bd_img_hotel3.1.webp',NULL,24),(32,'6888695b0ad53_imgs_hotel1.1.webp',NULL,25);
/*!40000 ALTER TABLE `RoomImage` ENABLE KEYS */;
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
