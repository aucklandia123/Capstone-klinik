-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: rekam_medis
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
-- Table structure for table `kunjungan`
--

DROP TABLE IF EXISTS `kunjungan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kunjungan` (
  `id_kunjungan` int(11) NOT NULL AUTO_INCREMENT,
  `id_pasien` int(11) NOT NULL,
  `keluhan` text NOT NULL,
  `diagnosa` text DEFAULT NULL,
  `terapi_penanganan` text DEFAULT NULL,
  `status_antrean` varchar(20) DEFAULT 'Menunggu',
  `tanggal_kunjungan` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_kunjungan`),
  KEY `id_pasien` (`id_pasien`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kunjungan`
--

LOCK TABLES `kunjungan` WRITE;
/*!40000 ALTER TABLE `kunjungan` DISABLE KEYS */;
INSERT INTO `kunjungan` VALUES (1,1,'-','berobat','paracetamol','Selesai','2026-07-04 14:45:05'),(2,2,'kontrol ','-','-','Selesai','2026-07-04 15:57:38'),(3,2,'kontrol ulang','sakit gigi','paracetamol','Selesai','2026-07-04 15:57:51'),(4,3,'-','-','-','Selesai','2026-07-04 16:01:16'),(5,4,'-','-','-','Selesai','2026-07-04 16:09:43'),(6,5,'Cabut gigi','-','-','Selesai','2026-07-04 16:12:04'),(7,5,'scaling','-','-','Selesai','2026-07-04 16:12:15'),(8,5,'scaling','-','-','Selesai','2026-07-04 16:15:36'),(9,6,'cabut gigi','-','-','Selesai','2026-07-04 16:16:10'),(10,6,'Scaling','-','-','Selesai','2026-07-04 16:17:45'),(11,7,'-','-','-','Selesai','2026-07-04 16:18:23'),(12,7,'kontrol','--','-','Selesai','2026-07-04 16:20:25'),(14,8,'kontrol behel','-','-','Selesai','2026-07-04 16:25:33'),(15,9,'-','Tambal','Paracetamol','Selesai','2026-07-11 13:14:05'),(16,10,'Bolong di gigi','Jarang sikat gigi','Tambal','Selesai','2026-07-11 13:15:40'),(17,9,'Control','-','-','Selesai','2026-07-11 13:15:57'),(18,9,'Control','control','control','Selesai','2026-07-11 13:23:52'),(19,11,'Tambal','-','-','Selesai','2026-07-18 14:19:06'),(21,13,'-','-','-','Selesai','2026-07-17 17:00:00'),(23,9,'Tambal','34 pulpitis','bap + eugeol\r\npct 3x1\r\namox 3x1','Selesai','2026-07-17 17:00:00'),(24,9,'Tambal','-','-','Selesai','2026-07-18 17:00:00'),(25,11,'Kunjungan Ulang / Chat Admin',NULL,NULL,'Diperiksa','2026-07-18 17:00:00'),(26,14,'tambal','-','-','Selesai','2026-07-18 17:00:00'),(27,15,'bersihin karang','-','-','Selesai','2026-09-28 17:00:00'),(28,10,'Tambal',NULL,NULL,'Menunggu','2026-09-28 17:00:00');
/*!40000 ALTER TABLE `kunjungan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pasien`
--

DROP TABLE IF EXISTS `pasien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pasien` (
  `id_pasien` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `alamat` text NOT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `riwayat_penyakit` text DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pasien`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pasien`
--

LOCK TABLES `pasien` WRITE;
/*!40000 ALTER TABLE `pasien` DISABLE KEYS */;
INSERT INTO `pasien` VALUES (1,'Budi','0000-00-00','Belum Diis','Belum Diisi','-','alergi','2026-07-04 14:45:05'),(2,'Budi','0000-00-00','Belum Diis','Belum Diisi','-','hipertensi','2026-07-04 15:57:38'),(3,'alek','0000-00-00','Laki-laki','maroco','-','-','2026-07-04 16:01:16'),(4,'eka','0000-00-00','Perempuan','pertanian','-','-','2026-07-04 16:09:43'),(5,'eka meilyana','0000-00-00','Perempuan','pertanian','-','-','2026-07-04 16:12:04'),(6,'azwari','0000-00-00','Laki-laki','Subrantas','-','-','2026-07-04 16:16:10'),(7,'fauzi','0000-00-00','Laki-laki','pertanian','-','-','2026-07-04 16:18:23'),(8,'charles leclerc','0000-00-00','Laki-laki','maroco','-','-','2026-07-04 16:25:33'),(9,'sainz','2005-02-01','Laki-laki','Jln.Riau','-','Alergi udang','2026-07-11 13:14:05'),(10,'Ocon','1996-05-06','Laki-laki','Jln.Kelapapati','08220002222','Alergi udang','2026-07-11 13:15:40'),(11,'charles','2008-03-04','Laki-laki','Jln.Monaco','-','-','2026-07-18 14:19:06'),(12,'charles','2026-07-25','-','-',NULL,'-','2026-07-18 14:48:51'),(13,'charles','2026-07-19','-','-',NULL,'-','2026-07-18 14:49:05'),(14,'charles','2026-07-04','Perempuan','sukabiris',NULL,'-','2026-07-19 05:17:45'),(15,'Nisa','2001-01-01','Perempuan','bojongsoang',NULL,'-','2026-09-29 04:17:04');
/*!40000 ALTER TABLE `pasien` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-11 22:10:42
