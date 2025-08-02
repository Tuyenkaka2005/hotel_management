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
-- Table structure for table `Room`
--

DROP TABLE IF EXISTS `Room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Room` (
  `RoomID` int NOT NULL AUTO_INCREMENT,
  `RoomName` varchar(50) NOT NULL,
  `RoomNumber` varchar(10) NOT NULL,
  `PricePerNight` bigint unsigned DEFAULT NULL,
  `Status` enum('Available','Occupied','Maintenance','Reserved') DEFAULT 'Available',
  `RoomTypeID` int NOT NULL,
  `MaxGuests` int NOT NULL DEFAULT '2',
  `Description` text,
  PRIMARY KEY (`RoomID`),
  UNIQUE KEY `RoomNumber` (`RoomNumber`),
  KEY `idx_room_number` (`RoomNumber`),
  KEY `idx_room_status` (`Status`),
  KEY `idx_room_type` (`RoomTypeID`),
  CONSTRAINT `Room_ibfk_1` FOREIGN KEY (`RoomTypeID`) REFERENCES `RoomType` (`RoomTypeID`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Room`
--

LOCK TABLES `Room` WRITE;
/*!40000 ALTER TABLE `Room` DISABLE KEYS */;
INSERT INTO `Room` VALUES (2,'Deluxe City View','100',1200000,'Available',1,2,'Spacious deluxe room with city view, queen-sized bed and modern amenities.'),(3,'Standard Single Room','201',700000,'Available',2,1,'Cozy room for solo travelers with essential amenities and fast internet.'),(9,'Deluxe Twin Room','103',1250000,'Occupied',1,2,'Twin deluxe room ideal for friends or colleagues, with separate beds and workspace.'),(10,'Standard Family Room','202',950000,'Available',2,4,'Family-friendly room with one double and two single beds, includes free breakfast.'),(11,'Standard Double Room','200',850000,'Reserved',2,2,'Comfortable standard room with double bed, perfect for short stays.'),(13,'Deluxe Garden View','101',1300000,'Reserved',1,3,'Deluxe room overlooking garden, includes balcony, free Wi-Fi, and minibar.'),(21,'Suite Ocean View\"','300',2500000,'Available',3,4,'Luxury suite with panoramic ocean view, separate living area and private balcony.'),(22,'Presidential Suite','301',4000000,'Available',3,6,'Top-tier suite with premium facilities, two bedrooms, dining room, and VIP services.'),(23,'Suite with Jacuzzi','302',2800000,'Available',3,3,'Elegant suite with private jacuzzi, king-size bed, and relaxing ambiance.'),(24,'Deluxe City View Demo','505',100000,'Available',1,2,'phòng'),(25,'Deluxe City 1 View','601',1200000,'Available',2,3,'phòng đẹp,');
/*!40000 ALTER TABLE `Room` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-03  1:44:22
