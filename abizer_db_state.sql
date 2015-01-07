-- MySQL dump 10.13  Distrib 5.6.22, for osx10.10 (x86_64)
--
-- Host: localhost    Database: sffaiz
-- ------------------------------------------------------
-- Server version	5.6.22

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `sffaiz`
--

CREATE DATABASE IF NOT EXISTS `sffaiz` DEFAULT CHARACTER SET utf8;

USE `sffaiz`;

--
-- Table structure for table `family`
--

DROP TABLE IF EXISTS `family`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `family` (
  `thaali` int(11) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  UNIQUE KEY `thaali` (`thaali`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `family`
--

LOCK TABLES `family` WRITE;
/*!40000 ALTER TABLE `family` DISABLE KEYS */;
INSERT INTO `family` VALUES (5,'Pedhiwala','Mohammed','mpedhiwala@gmail.com','510-494-1520'),(6,'Bootwala','Mustafa','mabootwala@gmail.com','650-676-8849'),(7,'Patanwala','Aliasgar','apatanwala@gmail.com','650-276-8037'),(8,'Partapurwala','Murtaza','murtazap@gmail.com','510-579-4909'),(36,'Lakhia','Ali Akber','lakhia@gmail.com','510-565-7861');
/*!40000 ALTER TABLE `family` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rsvps`
--

DROP TABLE IF EXISTS `rsvps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rsvps` (
  `date` date NOT NULL,
  `thaali_id` int(11) NOT NULL,
  `rsvp` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `thaali_id` (`thaali_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rsvps`
--

LOCK TABLES `rsvps` WRITE;
/*!40000 ALTER TABLE `rsvps` DISABLE KEYS */;
INSERT INTO `rsvps` VALUES ('2014-01-06',5,1),('2014-01-07',5,1),('2015-01-06',5,1),('2014-01-06',6,1),('2014-01-07',6,1),('2015-01-06',6,1),('2014-01-06',7,1),('2014-01-07',7,1),('2015-01-06',7,1),('2014-01-06',8,1),('2014-01-07',8,1),('2015-01-06',8,1),('2014-12-01',36,1),('2014-12-02',36,0),('2015-01-06',36,1),('2015-01-07',36,1),('2015-01-08',36,1),('2015-01-09',36,0),('2015-01-10',36,0);
/*!40000 ALTER TABLE `rsvps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `week`
--

DROP TABLE IF EXISTS `week`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `week` (
  `date` date NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `week`
--

LOCK TABLES `week` WRITE;
/*!40000 ALTER TABLE `week` DISABLE KEYS */;
INSERT INTO `week` VALUES ('2014-12-16','Daal Gosht Chawal, Vegetable Tarkari'),('2014-12-17','Chicken Tarkari, Dahi, Khitchri'),('2014-12-18','Gosht Korma, Chawal, Fruit'),('2014-12-19','Keema Patra, Daal Chawal'),('2014-12-20','Chicken Tikka Masala, Khurdi, Khitchri'),('2014-12-21','Khitchro'),('2014-12-22','Kari Chawal, kheer'),('2014-12-23','Sabzi tarkari, dal, chawal'),('2014-12-24','Nihari, khitchri'),('2015-01-06','testing0'),('2015-01-07','testing1'),('2015-01-08','testing2'),('2015-01-09','testing3'),('2015-01-10','testing4'),('2015-01-11','testing5'),('2015-01-12','testing6'),('2015-01-13','testing7'),('2015-01-14','testing8'),('2015-01-15','testing9'),('2015-01-16','testing10'),('2015-01-17','testing11'),('2015-01-18','testing12'),('2015-01-19','testing13'),('2015-01-20','testing14'),('2015-01-21','testing15'),('2015-01-22','testing16'),('2015-01-23','testing17'),('2015-01-24','testing18'),('2015-01-25','testing19'),('2015-01-26','testing20'),('2015-01-27','testing21'),('2015-01-28','testing22'),('2015-01-29','testing23'),('2015-01-30','testing24'),('2015-01-31','testing25'),('2015-02-01','testing26'),('2015-02-02','testing27'),('2015-02-03','testing28'),('2015-02-04','testing29'),('2015-02-05','testing30'),('2015-02-08','kheemo'),('2015-02-19','dal'),('2015-02-20','dal'),('2015-02-21','dal chawal');
/*!40000 ALTER TABLE `week` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-01-08  2:46:48
