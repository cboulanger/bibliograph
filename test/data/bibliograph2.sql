-- MySQL dump 10.13  Distrib 5.5.57, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: 
-- ------------------------------------------------------
-- Server version	5.5.57-0ubuntu0.14.04.1

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
-- Current Database: `bibliograph`
--

-- CREATE DATABASE /*!32312 IF NOT EXISTS*/ `bibliograph` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `tests`;

--
-- Table structure for table `data_Config`
--

DROP TABLE IF EXISTS `data_Config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_Config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` smallint(6) DEFAULT NULL,
  `default` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `customize` int(1) NOT NULL DEFAULT '0',
  `final` int(1) NOT NULL DEFAULT '0',
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_Config`
--

LOCK TABLES `data_Config` WRITE;
/*!40000 ALTER TABLE `data_Config` DISABLE KEYS */;
INSERT INTO `data_Config` VALUES (1,0,'chicago-author-date',1,0,'csl.style.default','2017-10-19 21:53:27','2017-10-19 21:53:27'),(2,0,'en',1,0,'application.locale','2017-10-19 21:53:27','2017-10-19 21:53:27'),(3,0,'Bibliograph Online Bibliographic Data Manager',0,0,'application.title','2017-10-19 21:53:28','2017-10-19 21:53:28'),(4,0,'bibliograph/icon/bibliograph-logo.png',0,0,'application.logo','2017-10-19 21:53:28','2017-10-19 21:53:28'),(5,0,'normal',0,0,'bibliograph.access.mode','2017-10-19 21:53:28','2017-10-19 21:53:28'),(6,0,NULL,0,0,'bibliograph.access.no-access-message','2017-10-19 21:53:28','2017-10-19 21:53:28'),(7,1,'50',0,0,'bibliograph.duplicates.threshold','2017-10-19 21:53:28','2017-10-19 21:53:28'),(8,1,'500',0,0,'plugin.csl.bibliography.maxfolderrecords','2017-10-19 21:53:28','2017-10-19 21:53:28'),(9,2,'false',0,0,'access.enforce_https_login','2017-10-19 21:53:28','2017-10-19 21:53:28'),(10,0,'hashed',0,0,'authentication.method','2017-10-19 21:53:28','2017-10-19 21:53:28'),(11,3,NULL,0,0,'datasource.database1.fields.exclude','2017-10-19 21:53:28','2017-10-19 21:53:28'),(12,3,NULL,0,0,'datasource.database2.fields.exclude','2017-10-19 21:53:28','2017-10-19 21:53:28'),(13,1,'3',0,0,'backup.daysToKeepBackupFor','2017-10-19 21:53:29','2017-10-19 21:53:29'),(14,2,'false',0,0,'debug.recordJsonRpcTraffic','2017-10-19 21:53:29','2017-10-19 21:55:52'),(15,0,'parser',0,0,'bibliograph.sortableName.engine','2017-10-19 21:53:29','2017-10-19 21:53:29'),(16,0,'localhost',0,0,'nnforum.searchdomain','2017-10-19 21:53:29','2017-10-19 21:53:29'),(17,1,'0',0,0,'nnforum.readposts','2017-10-19 21:53:29','2017-10-19 21:53:29'),(18,0,'z3950_voyager',1,0,'z3950.lastDatasource','2017-10-19 21:53:29','2017-10-19 21:55:37');
/*!40000 ALTER TABLE `data_Config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_Datasource`
--

DROP TABLE IF EXISTS `data_Datasource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_Datasource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `title` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `schema` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `host` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `database` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `encoding` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'utf-8',
  `prefix` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resourcepath` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `readonly` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_Datasource`
--

LOCK TABLES `data_Datasource` WRITE;
/*!40000 ALTER TABLE `data_Datasource` DISABLE KEYS */;
INSERT INTO `data_Datasource` VALUES (1,'access','2017-10-19 21:53:25','2017-10-19 21:53:25',NULL,NULL,'qcl.schema.access','mysql',NULL,NULL,NULL,NULL,NULL,'utf-8',NULL,NULL,1,0,1),(2,'database1','2017-10-19 21:53:28','2017-10-19 21:53:28','Database 1',NULL,'bibliograph.schema.bibliograph2','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,NULL,1,0,0),(3,'database2','2017-10-19 21:53:28','2017-10-19 21:53:28','Database 2',NULL,'bibliograph.schema.bibliograph2','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,NULL,1,0,0),(4,'setup','2017-10-19 21:53:28','2017-10-19 21:53:28',NULL,NULL,'none','dummy',NULL,NULL,NULL,NULL,NULL,'utf-8',NULL,NULL,0,0,1),(5,'bibliograph_import','2017-10-19 21:53:28','2017-10-19 21:53:29',NULL,NULL,'bibliograph.schema.bibliograph2','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,NULL,1,0,1),(6,'bibliograph_export','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,'qcl.schema.filesystem.local','file',NULL,NULL,NULL,NULL,NULL,'utf-8',NULL,'/tmp',1,0,1),(7,'backup_files','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,'Datasource containing backup files.','qcl.schema.filesystem.local','file',NULL,NULL,NULL,NULL,NULL,'utf-8',NULL,'/var/lib/bibliograph',1,0,1),(8,'z3950_BVB01MCZ','2017-10-19 21:53:29','2017-10-19 21:53:29','Bibliotheksverbund Bayern (BVB)/B3Kat',NULL,'bibliograph.schema.z3950','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,'/var/www/html/bibliograph/plugins/z3950/services/class/z3950/servers/193.174.96.24-31310-BVBSR011.xml',1,0,1),(9,'z3950_NEBIS','2017-10-19 21:53:29','2017-10-19 21:53:29','Das Netzwerk von Bibliotheken und Informationsstellen in der Schweiz',NULL,'bibliograph.schema.z3950','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,'/var/www/html/bibliograph/plugins/z3950/services/class/z3950/servers/opac.nebis.ch-9909-NEBIS.xml',1,0,1),(10,'z3950_stabikat','2017-10-19 21:53:29','2017-10-19 21:53:29','Staatsbibliothek Berlin',NULL,'bibliograph.schema.z3950','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,'/var/www/html/bibliograph/plugins/z3950/services/class/z3950/servers/z3950.gbv.de-20010-stabikat.xml',1,0,1),(11,'z3950_gvk','2017-10-19 21:53:29','2017-10-19 21:53:29','Gemeinsamer Verbundkatalog - Bremen, Hamburg, Mecklenburg-Vorpommern, Niedersachsen, Sachsen-Anhalt,',NULL,'bibliograph.schema.z3950','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,'/var/www/html/bibliograph/plugins/z3950/services/class/z3950/servers/z3950.gbv.de-210-GVK-de.xml',1,0,1),(12,'z3950_U-KBV90','2017-10-19 21:53:29','2017-10-19 21:53:29','Kooperativer Bibliotheksverbund Berlin-Brandenburg (KOBV)',NULL,'bibliograph.schema.z3950','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,'/var/www/html/bibliograph/plugins/z3950/services/class/z3950/servers/z3950.kobv.de-9991-U-KBV90.xml',1,0,1),(13,'z3950_voyager','2017-10-19 21:53:29','2017-10-19 21:53:29','Library of Congress',NULL,'bibliograph.schema.z3950','mysql','0.0.0.0',3306,'bibliograph',NULL,NULL,'utf-8',NULL,'/var/www/html/bibliograph/plugins/z3950/services/class/z3950/servers/z3950.loc.gov-7090-voyager.xml',1,0,1);
/*!40000 ALTER TABLE `data_Datasource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_DatasourceSchema`
--

DROP TABLE IF EXISTS `data_DatasourceSchema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_DatasourceSchema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `class` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_DatasourceSchema`
--

LOCK TABLES `data_DatasourceSchema` WRITE;
/*!40000 ALTER TABLE `data_DatasourceSchema` DISABLE KEYS */;
INSERT INTO `data_DatasourceSchema` VALUES (1,'qcl.schema.access','2017-10-19 21:53:26','2017-10-19 21:53:26','qcl_access_DatasourceModel','The schema the qcl datasource supplying the models for access control',1),(2,'bibliograph.schema.bibliograph2','2017-10-19 21:53:28','2017-10-19 21:53:28','bibliograph_model_BibliographicDatasourceModel','The schema of Bibliograph 2.0 datasources',1),(3,'qcl.schema.filesystem.local','2017-10-19 21:53:28','2017-10-19 21:53:28','qcl_io_filesystem_local_Datasource','A datasource providing access to local files ...',1),(4,'bibliograph.schema.z3950','2017-10-19 21:53:29','2017-10-19 21:53:29','z3950_DatasourceModel','Datasource model for Z39.50 Datasources',1);
/*!40000 ALTER TABLE `data_DatasourceSchema` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_ExportFormat`
--

DROP TABLE IF EXISTS `data_ExportFormat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_ExportFormat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `class` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'invalid',
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'invalid',
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `extension` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'txt',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_ExportFormat`
--

LOCK TABLES `data_ExportFormat` WRITE;
/*!40000 ALTER TABLE `data_ExportFormat` DISABLE KEYS */;
INSERT INTO `data_ExportFormat` VALUES (1,'bibtex','2017-10-19 21:53:29','2017-10-19 21:53:29','bibliograph_model_export_Bibtex','BibTeX',NULL,1,'bibliograph','bib'),(2,'Csv','2017-10-19 21:53:29','2017-10-19 21:53:29','bibliograph_model_export_Csv','Comma-separated values',NULL,1,'bibliograph','csv'),(3,'endnote','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_export_Endnote','Endnote tagged format',NULL,1,'bibutils','end'),(4,'isi','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_export_Isi','ISI tagged format',NULL,1,'bibutils','isi'),(5,'mods','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_export_Mods','MODS',NULL,1,'bibutils','xml'),(6,'ris','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_export_Ris','RIS tagged format',NULL,1,'bibutils','ris'),(7,'wordbib','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_export_Wordbib','Word 2007 bibliography format',NULL,1,'bibutils','xml');
/*!40000 ALTER TABLE `data_ExportFormat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_Group`
--

DROP TABLE IF EXISTS `data_Group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_Group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ldap` int(1) NOT NULL DEFAULT '0',
  `defaultRole` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_Group`
--

LOCK TABLES `data_Group` WRITE;
/*!40000 ALTER TABLE `data_Group` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_Group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_ImportFormat`
--

DROP TABLE IF EXISTS `data_ImportFormat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_ImportFormat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `class` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'invalid',
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'invalid',
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `extension` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'txt',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_ImportFormat`
--

LOCK TABLES `data_ImportFormat` WRITE;
/*!40000 ALTER TABLE `data_ImportFormat` DISABLE KEYS */;
INSERT INTO `data_ImportFormat` VALUES (1,'bibtex','2017-10-19 21:53:29','2017-10-19 21:53:29','bibliograph_model_import_Bibtex','BibTeX',NULL,1,'bibliograph','bib'),(2,'Csv','2017-10-19 21:53:29','2017-10-19 21:53:29','bibliograph_model_import_Csv','Comma-separated values (UTF-8)',NULL,1,'bibliograph','csv'),(3,'endnote','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_import_Endnote','Endnote tagged format',NULL,1,'bibutils','end'),(4,'endnotexml','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_import_EndnoteXml','Endnote xml format',NULL,1,'bibutils','endx'),(5,'isi','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_import_Isi','ISI tagged format',NULL,1,'bibutils','isi'),(6,'mods','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_import_Mods','MODS',NULL,1,'bibutils','xml'),(7,'pubmed','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_import_PubMed','PubMed XML format',NULL,1,'bibutils','med'),(8,'ris','2017-10-19 21:53:29','2017-10-19 21:53:29','bibutils_import_Ris','RIS tagged format',NULL,1,'bibutils','ris');
/*!40000 ALTER TABLE `data_ImportFormat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_Messages`
--

DROP TABLE IF EXISTS `data_Messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_Messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` blob,
  `SessionId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=40 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_Messages`
--

LOCK TABLES `data_Messages` WRITE;
/*!40000 ALTER TABLE `data_Messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_Messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_Permission`
--

DROP TABLE IF EXISTS `data_Permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_Permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_Permission`
--

LOCK TABLES `data_Permission` WRITE;
/*!40000 ALTER TABLE `data_Permission` DISABLE KEYS */;
INSERT INTO `data_Permission` VALUES (1,'*','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,'Can do everything',1),(2,'access.manage','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,'Manage users, roles, permissions, and datasources',1),(3,'application.reportBug','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(4,'config.default.edit','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(5,'config.key.add','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(6,'config.key.edit','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(7,'config.value.edit','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(8,'folder.add','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(9,'folder.delete','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(10,'folder.edit','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(11,'folder.move','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(12,'folder.remove','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(13,'plugin.manage','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(14,'reference.add','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(15,'reference.batchedit','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(16,'reference.delete','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(17,'reference.edit','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(18,'reference.export','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(19,'reference.import','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(20,'reference.move','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(21,'reference.remove','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(22,'reference.search','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(23,'reference.view','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(24,'system.manage','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(25,'system.menu.view','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(26,'trash.empty','2017-10-19 21:53:27','2017-10-19 21:53:27',NULL,NULL,1),(27,'backup.create','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(28,'backup.restore','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(29,'backup.delete','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(30,'backup.download','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(31,'backup.upload','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(32,'debug.showLogFile','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(33,'debug.selectFilters','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(34,'debug.allowDebug','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(35,'isbnscanner.import','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(36,'nnforum.view','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(37,'rssfolder.view','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1),(38,'z3950.manage','2017-10-19 21:53:29','2017-10-19 21:53:29',NULL,NULL,1);
/*!40000 ALTER TABLE `data_Permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_Plugin`
--

DROP TABLE IF EXISTS `data_Plugin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_Plugin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_Plugin`
--

LOCK TABLES `data_Plugin` WRITE;
/*!40000 ALTER TABLE `data_Plugin` DISABLE KEYS */;
INSERT INTO `data_Plugin` VALUES (1,'backup','2017-10-19 21:53:29','2017-10-19 21:53:29','Backup','Backup plugin that works with all database backends.','a:1:{s:4:\"part\";s:13:\"plugin_backup\";}',1),(2,'bibutils','2017-10-19 21:53:29','2017-10-19 21:53:29','Bibutils Plugin','A plugin providing import and export filters using the bibutils binaries','a:0:{}',1),(3,'csl','2017-10-19 21:53:29','2017-10-19 21:53:29','Citation Style Language (CSL) Plugin','...','a:1:{s:4:\"part\";s:10:\"plugin_csl\";}',1),(4,'debug','2017-10-19 21:53:29','2017-10-19 21:53:29','Debug tools','Provides debugging tools, such as backend logfile, selection of log filters, and JSONRPC traffic recording','a:1:{s:4:\"part\";s:12:\"plugin_debug\";}',1),(5,'isbnscanner','2017-10-19 21:53:29','2017-10-19 21:53:29','ISBN Scanner Plugin','A plugin providing the backend for the mobile ISBN scanner application','a:1:{s:4:\"part\";s:18:\"plugin_isbnscanner\";}',1),(6,'nnforum','2017-10-19 21:53:29','2017-10-19 21:53:29','No-Nonsense Forum Plugin','This integrates a forum for user questions','a:1:{s:4:\"part\";s:14:\"plugin_nnforum\";}',1),(7,'rssfolder','2017-10-19 21:53:29','2017-10-19 21:53:29','RSS-Folders','This plugin provides exporting folders as RSS feeds and importing from these feeds.','a:1:{s:4:\"part\";s:16:\"plugin_rssfolder\";}',1),(8,'z3950','2017-10-19 21:53:29','2017-10-19 21:53:29','Z39.50 Plugin','A plugin providing models for a Z39.50 connection','a:1:{s:4:\"part\";s:12:\"plugin_z3950\";}',1);
/*!40000 ALTER TABLE `data_Plugin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_Role`
--

DROP TABLE IF EXISTS `data_Role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_Role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_Role`
--

LOCK TABLES `data_Role` WRITE;
/*!40000 ALTER TABLE `data_Role` DISABLE KEYS */;
INSERT INTO `data_Role` VALUES (1,'admin','2017-10-19 21:53:27','2017-10-19 21:53:27','Administrator role',NULL,0),(2,'anonymous','2017-10-19 21:53:27','2017-10-19 21:53:27','Anonymous user',NULL,0),(3,'manager','2017-10-19 21:53:27','2017-10-19 21:53:27','Manager role',NULL,0),(4,'user','2017-10-19 21:53:27','2017-10-19 21:53:27','Normal user',NULL,0);
/*!40000 ALTER TABLE `data_Role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_Session`
--

DROP TABLE IF EXISTS `data_Session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_Session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `parentSessionId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `UserId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`),
  UNIQUE KEY `session_index` (`namedId`,`ip`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_Session`
--

LOCK TABLES `data_Session` WRITE;
/*!40000 ALTER TABLE `data_Session` DISABLE KEYS */;
INSERT INTO `data_Session` VALUES (2,'11e34f67637dddfc9eb1b735784a77d6','2017-10-19 21:55:52','2017-10-19 22:19:04',NULL,'172.17.0.1',3),(4,'24ac09ebfd6f7fbf8f64daa123d0f6b0','2018-02-13 15:24:18','2018-02-13 15:26:01',NULL,'172.17.0.1',6);
/*!40000 ALTER TABLE `data_Session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_User`
--

DROP TABLE IF EXISTS `data_User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namedId` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `anonymous` int(1) NOT NULL DEFAULT '0',
  `ldap` int(1) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '1',
  `lastAction` timestamp NULL DEFAULT NULL,
  `confirmed` int(1) NOT NULL DEFAULT '0',
  `online` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_namedId` (`namedId`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_User`
--

LOCK TABLES `data_User` WRITE;
/*!40000 ALTER TABLE `data_User` DISABLE KEYS */;
INSERT INTO `data_User` VALUES (1,'admin','2017-10-19 21:53:26','2017-10-19 21:53:28','Administrator','d005581067c8f90d919745576fbe675e761551cadfbf7df3a','nobody@example.com',0,0,1,NULL,0,0),(2,'manager','2017-10-19 21:53:26','2017-10-19 21:53:28','Manager','ad52b0e7879f0cc54957c88216b6a7575c497696ffb2e00d5','nobody@example.com',0,0,1,NULL,0,0),(3,'user','2017-10-19 21:53:27','2017-10-19 22:19:04','User','541f8f272d769dafb9b8e48b7795ff50bf15ce0bbb1491f71','nobody@example.com',0,0,1,'2017-10-19 22:19:04',0,0),(4,'setup','2017-10-19 21:53:27','2017-10-19 21:53:27','Setup User (do not delete)','',NULL,0,0,0,NULL,0,0),(6,'anonymous_151853545804.05','2018-02-13 15:24:18','2018-02-13 15:26:01','Anonyme/r Benutzer/in',NULL,NULL,1,0,1,'2018-02-13 15:26:01',0,0);
/*!40000 ALTER TABLE `data_User` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_UserConfig`
--

DROP TABLE IF EXISTS `data_UserConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_UserConfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `UserId` int(11) DEFAULT NULL,
  `ConfigId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_UserConfig`
--

LOCK TABLES `data_UserConfig` WRITE;
/*!40000 ALTER TABLE `data_UserConfig` DISABLE KEYS */;
INSERT INTO `data_UserConfig` VALUES (4,'en','2018-02-13 15:24:19','2018-02-13 15:24:19',6,2),(3,'z3950_U-KBV90','2017-10-19 21:56:02','2017-10-19 21:57:12',3,18),(5,'chicago-author-date','2018-02-13 15:24:23','2018-02-13 15:24:23',6,1);
/*!40000 ALTER TABLE `data_UserConfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `join_Datasource_Group`
--

DROP TABLE IF EXISTS `join_Datasource_Group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `join_Datasource_Group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `DatasourceId` int(11) DEFAULT NULL,
  `GroupId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_Datasource_Group` (`DatasourceId`,`GroupId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `join_Datasource_Group`
--

LOCK TABLES `join_Datasource_Group` WRITE;
/*!40000 ALTER TABLE `join_Datasource_Group` DISABLE KEYS */;
/*!40000 ALTER TABLE `join_Datasource_Group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `join_Datasource_Role`
--

DROP TABLE IF EXISTS `join_Datasource_Role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `join_Datasource_Role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `DatasourceId` int(11) DEFAULT NULL,
  `RoleId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_Datasource_Role` (`DatasourceId`,`RoleId`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `join_Datasource_Role`
--

LOCK TABLES `join_Datasource_Role` WRITE;
/*!40000 ALTER TABLE `join_Datasource_Role` DISABLE KEYS */;
INSERT INTO `join_Datasource_Role` VALUES (1,'2017-10-19 21:53:28','2017-10-19 21:53:28',2,2),(2,'2017-10-19 21:53:28','2017-10-19 21:53:28',2,4),(3,'2017-10-19 21:53:28','2017-10-19 21:53:28',2,1),(4,'2017-10-19 21:53:28','2017-10-19 21:53:28',3,4),(5,'2017-10-19 21:53:28','2017-10-19 21:53:28',3,1);
/*!40000 ALTER TABLE `join_Datasource_Role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `join_Datasource_User`
--

DROP TABLE IF EXISTS `join_Datasource_User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `join_Datasource_User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `DatasourceId` int(11) DEFAULT NULL,
  `UserId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_Datasource_User` (`DatasourceId`,`UserId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `join_Datasource_User`
--

LOCK TABLES `join_Datasource_User` WRITE;
/*!40000 ALTER TABLE `join_Datasource_User` DISABLE KEYS */;
/*!40000 ALTER TABLE `join_Datasource_User` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `join_Group_User`
--

DROP TABLE IF EXISTS `join_Group_User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `join_Group_User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `UserId` int(11) DEFAULT NULL,
  `GroupId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_Group_User` (`GroupId`,`UserId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `join_Group_User`
--

LOCK TABLES `join_Group_User` WRITE;
/*!40000 ALTER TABLE `join_Group_User` DISABLE KEYS */;
/*!40000 ALTER TABLE `join_Group_User` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `join_Permission_Role`
--

DROP TABLE IF EXISTS `join_Permission_Role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `join_Permission_Role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `RoleId` int(11) DEFAULT NULL,
  `PermissionId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_Permission_Role` (`PermissionId`,`RoleId`)
) ENGINE=MyISAM AUTO_INCREMENT=49 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `join_Permission_Role`
--

LOCK TABLES `join_Permission_Role` WRITE;
/*!40000 ALTER TABLE `join_Permission_Role` DISABLE KEYS */;
INSERT INTO `join_Permission_Role` VALUES (1,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,1),(2,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,2),(3,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,3),(4,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,4),(5,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,5),(6,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,6),(7,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,7),(8,'2017-10-19 21:53:27','2017-10-19 21:53:27',2,7),(9,'2017-10-19 21:53:27','2017-10-19 21:53:27',3,7),(10,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,7),(11,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,8),(12,'2017-10-19 21:53:27','2017-10-19 21:53:27',3,9),(13,'2017-10-19 21:53:27','2017-10-19 21:53:27',3,10),(14,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,11),(15,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,12),(16,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,13),(17,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,14),(18,'2017-10-19 21:53:27','2017-10-19 21:53:27',3,15),(19,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,16),(20,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,17),(21,'2017-10-19 21:53:27','2017-10-19 21:53:27',2,18),(22,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,18),(23,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,19),(24,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,20),(25,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,21),(26,'2017-10-19 21:53:27','2017-10-19 21:53:27',2,22),(27,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,22),(28,'2017-10-19 21:53:27','2017-10-19 21:53:27',2,23),(29,'2017-10-19 21:53:27','2017-10-19 21:53:27',4,23),(30,'2017-10-19 21:53:27','2017-10-19 21:53:27',3,25),(31,'2017-10-19 21:53:27','2017-10-19 21:53:27',3,26),(32,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,27),(33,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,28),(34,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,29),(35,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,30),(36,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,31),(37,'2017-10-19 21:53:29','2017-10-19 21:53:29',3,27),(38,'2017-10-19 21:53:29','2017-10-19 21:53:29',3,28),(39,'2017-10-19 21:53:29','2017-10-19 21:53:29',3,29),(40,'2017-10-19 21:53:29','2017-10-19 21:53:29',3,30),(41,'2017-10-19 21:53:29','2017-10-19 21:53:29',3,31),(42,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,32),(43,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,33),(44,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,34),(45,'2017-10-19 21:53:29','2017-10-19 21:53:29',4,36),(46,'2017-10-19 21:53:29','2017-10-19 21:53:29',4,37),(47,'2017-10-19 21:53:29','2017-10-19 21:53:29',1,38),(48,'2017-10-19 21:53:29','2017-10-19 21:53:29',3,38);
/*!40000 ALTER TABLE `join_Permission_Role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `join_User_Role`
--

DROP TABLE IF EXISTS `join_User_Role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `join_User_Role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `UserId` int(11) DEFAULT NULL,
  `RoleId` int(11) DEFAULT NULL,
  `GroupId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_User_Role` (`GroupId`,`RoleId`,`UserId`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `join_User_Role`
--

LOCK TABLES `join_User_Role` WRITE;
/*!40000 ALTER TABLE `join_User_Role` DISABLE KEYS */;
INSERT INTO `join_User_Role` VALUES (1,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,1,NULL),(2,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,3,NULL),(3,'2017-10-19 21:53:27','2017-10-19 21:53:27',2,3,NULL),(4,'2017-10-19 21:53:27','2017-10-19 21:53:27',1,4,NULL),(5,'2017-10-19 21:53:27','2017-10-19 21:53:27',2,4,NULL),(6,'2017-10-19 21:53:27','2017-10-19 21:53:27',3,4,NULL),(8,'2018-02-13 15:24:18','2018-02-13 15:24:18',6,2,NULL);
/*!40000 ALTER TABLE `join_User_Role` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `z3950_BVB01MCZ_data_z3950_RecordModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_BVB01MCZ_data_z3950_RecordModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `citekey` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reftype` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `abstract` text COLLATE utf8_unicode_ci,
  `address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `affiliation` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `annote` text COLLATE utf8_unicode_ci,
  `author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `booktitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contents` text COLLATE utf8_unicode_ci,
  `copyright` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `crossref` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `doi` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `edition` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `editor` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `howpublished` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `institution` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isbn` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `issn` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `journal` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `key` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keywords` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lccn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `month` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8_unicode_ci,
  `number` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `organization` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pages` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `price` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publisher` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `school` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `series` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `volume` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `year` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SearchId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_BVB01MCZ_data_z3950_RecordModel`
--

LOCK TABLES `z3950_BVB01MCZ_data_z3950_RecordModel` WRITE;
/*!40000 ALTER TABLE `z3950_BVB01MCZ_data_z3950_RecordModel` DISABLE KEYS */;
/*!40000 ALTER TABLE `z3950_BVB01MCZ_data_z3950_RecordModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_BVB01MCZ_data_z3950_ResultModel`
--

DROP TABLE IF EXISTS `z3950_BVB01MCZ_data_z3950_ResultModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_BVB01MCZ_data_z3950_ResultModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `firstRow` int(11) DEFAULT NULL,
  `lastRow` int(11) DEFAULT NULL,
  `firstRecordId` int(11) DEFAULT NULL,
  `lastRecordId` int(11) DEFAULT NULL,
  `SearchId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_BVB01MCZ_data_z3950_ResultModel`
--

LOCK TABLES `z3950_BVB01MCZ_data_z3950_ResultModel` WRITE;
/*!40000 ALTER TABLE `z3950_BVB01MCZ_data_z3950_ResultModel` DISABLE KEYS */;
/*!40000 ALTER TABLE `z3950_BVB01MCZ_data_z3950_ResultModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_BVB01MCZ_data_z3950_SearchModel`
--

DROP TABLE IF EXISTS `z3950_BVB01MCZ_data_z3950_SearchModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_BVB01MCZ_data_z3950_SearchModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `query` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hits` int(11) DEFAULT NULL,
  `UserId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_BVB01MCZ_data_z3950_SearchModel`
--

LOCK TABLES `z3950_BVB01MCZ_data_z3950_SearchModel` WRITE;
/*!40000 ALTER TABLE `z3950_BVB01MCZ_data_z3950_SearchModel` DISABLE KEYS */;
/*!40000 ALTER TABLE `z3950_BVB01MCZ_data_z3950_SearchModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_NEBIS_data_z3950_SearchModel`
--

DROP TABLE IF EXISTS `z3950_NEBIS_data_z3950_SearchModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_NEBIS_data_z3950_SearchModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `query` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hits` int(11) DEFAULT NULL,
  `UserId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_NEBIS_data_z3950_SearchModel`
--

LOCK TABLES `z3950_NEBIS_data_z3950_SearchModel` WRITE;
/*!40000 ALTER TABLE `z3950_NEBIS_data_z3950_SearchModel` DISABLE KEYS */;
/*!40000 ALTER TABLE `z3950_NEBIS_data_z3950_SearchModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_U-KBV90_data_z3950_RecordModel`
--

DROP TABLE IF EXISTS `z3950_U-KBV90_data_z3950_RecordModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_U-KBV90_data_z3950_RecordModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `citekey` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reftype` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `abstract` text COLLATE utf8_unicode_ci,
  `address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `affiliation` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `annote` text COLLATE utf8_unicode_ci,
  `author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `booktitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contents` text COLLATE utf8_unicode_ci,
  `copyright` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `crossref` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `doi` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `edition` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `editor` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `howpublished` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `institution` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isbn` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `issn` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `journal` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `key` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keywords` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lccn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `month` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8_unicode_ci,
  `number` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `organization` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pages` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `price` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publisher` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `school` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `series` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `volume` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `year` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SearchId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_U-KBV90_data_z3950_RecordModel`
--

LOCK TABLES `z3950_U-KBV90_data_z3950_RecordModel` WRITE;
/*!40000 ALTER TABLE `z3950_U-KBV90_data_z3950_RecordModel` DISABLE KEYS */;
INSERT INTO `z3950_U-KBV90_data_z3950_RecordModel` VALUES (1,'2017-10-19 21:57:13','2017-10-19 21:57:13','VBRDP2DPi383050277x0482','book',NULL,'Berlin',NULL,NULL,'Boulanger, Christian',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2., neu bearb. und erw. Aufl.',NULL,NULL,NULL,'3-8305-0277-X',NULL,NULL,NULL,'Todesstrafe; Rechtsvergleich',NULL,NULL,NULL,NULL,'Christian Boulanger ... (Hrsg.)',NULL,NULL,NULL,NULL,'Berlin-Verl. Spitz',NULL,NULL,NULL,'Zur Aktualität der Todesstrafe: interdisziplinäre und globale Perspektiven',NULL,NULL,NULL,'2002',1);
/*!40000 ALTER TABLE `z3950_U-KBV90_data_z3950_RecordModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_U-KBV90_data_z3950_ResultModel`
--

DROP TABLE IF EXISTS `z3950_U-KBV90_data_z3950_ResultModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_U-KBV90_data_z3950_ResultModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `firstRow` int(11) DEFAULT NULL,
  `lastRow` int(11) DEFAULT NULL,
  `firstRecordId` int(11) DEFAULT NULL,
  `lastRecordId` int(11) DEFAULT NULL,
  `SearchId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_U-KBV90_data_z3950_ResultModel`
--

LOCK TABLES `z3950_U-KBV90_data_z3950_ResultModel` WRITE;
/*!40000 ALTER TABLE `z3950_U-KBV90_data_z3950_ResultModel` DISABLE KEYS */;
INSERT INTO `z3950_U-KBV90_data_z3950_ResultModel` VALUES (1,'2017-10-19 21:57:13','2017-10-19 21:57:13',0,0,1,1,1);
/*!40000 ALTER TABLE `z3950_U-KBV90_data_z3950_ResultModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_U-KBV90_data_z3950_SearchModel`
--

DROP TABLE IF EXISTS `z3950_U-KBV90_data_z3950_SearchModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_U-KBV90_data_z3950_SearchModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `query` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hits` int(11) DEFAULT NULL,
  `UserId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_U-KBV90_data_z3950_SearchModel`
--

LOCK TABLES `z3950_U-KBV90_data_z3950_SearchModel` WRITE;
/*!40000 ALTER TABLE `z3950_U-KBV90_data_z3950_SearchModel` DISABLE KEYS */;
INSERT INTO `z3950_U-KBV90_data_z3950_SearchModel` VALUES (1,'2017-10-19 21:57:13','2017-10-19 21:57:13','all=\"boulanger, christian todesstrafe\"',1,3);
/*!40000 ALTER TABLE `z3950_U-KBV90_data_z3950_SearchModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_gvk_data_z3950_SearchModel`
--

DROP TABLE IF EXISTS `z3950_gvk_data_z3950_SearchModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_gvk_data_z3950_SearchModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `query` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hits` int(11) DEFAULT NULL,
  `UserId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_gvk_data_z3950_SearchModel`
--

LOCK TABLES `z3950_gvk_data_z3950_SearchModel` WRITE;
/*!40000 ALTER TABLE `z3950_gvk_data_z3950_SearchModel` DISABLE KEYS */;
/*!40000 ALTER TABLE `z3950_gvk_data_z3950_SearchModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_stabikat_data_z3950_SearchModel`
--

DROP TABLE IF EXISTS `z3950_stabikat_data_z3950_SearchModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_stabikat_data_z3950_SearchModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `query` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hits` int(11) DEFAULT NULL,
  `UserId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_stabikat_data_z3950_SearchModel`
--

LOCK TABLES `z3950_stabikat_data_z3950_SearchModel` WRITE;
/*!40000 ALTER TABLE `z3950_stabikat_data_z3950_SearchModel` DISABLE KEYS */;
/*!40000 ALTER TABLE `z3950_stabikat_data_z3950_SearchModel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z3950_voyager_data_z3950_SearchModel`
--

DROP TABLE IF EXISTS `z3950_voyager_data_z3950_SearchModel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z3950_voyager_data_z3950_SearchModel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `query` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hits` int(11) DEFAULT NULL,
  `UserId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z3950_voyager_data_z3950_SearchModel`
--

LOCK TABLES `z3950_voyager_data_z3950_SearchModel` WRITE;
/*!40000 ALTER TABLE `z3950_voyager_data_z3950_SearchModel` DISABLE KEYS */;
/*!40000 ALTER TABLE `z3950_voyager_data_z3950_SearchModel` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `database1_data_Folder`
--

DROP TABLE IF EXISTS `database1_data_Folder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `database1_data_Folder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `parentId` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `label` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `searchable` int(1) DEFAULT NULL,
  `searchfolder` int(1) DEFAULT NULL,
  `query` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `public` int(1) DEFAULT NULL,
  `opened` int(1) DEFAULT NULL,
  `locked` int(1) DEFAULT NULL,
  `path` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `owner` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hidden` int(1) DEFAULT NULL,
  `createdBy` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `markedDeleted` int(1) DEFAULT NULL,
  `childCount` int(11) DEFAULT NULL,
  `referenceCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `database1_data_Folder`
--

LOCK TABLES `database1_data_Folder` WRITE;
/*!40000 ALTER TABLE `database1_data_Folder` DISABLE KEYS */;
INSERT INTO `database1_data_Folder` VALUES (1,'2017-10-19 21:53:28','2017-10-19 21:57:16',0,0,'Hauptordner',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,0,NULL,0,0,3),(2,'2017-10-19 21:53:28','2017-10-19 21:53:42',0,1,'Mülleimer','trash',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,0,0);
/*!40000 ALTER TABLE `database1_data_Folder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `database1_data_Reference`
--

DROP TABLE IF EXISTS `database1_data_Reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `database1_data_Reference` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `citekey` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reftype` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `abstract` text COLLATE utf8_unicode_ci,
  `address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `affiliation` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `annote` text COLLATE utf8_unicode_ci,
  `author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `booktitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contents` text COLLATE utf8_unicode_ci,
  `copyright` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `crossref` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `doi` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `edition` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `editor` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `howpublished` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `institution` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isbn` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `issn` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `journal` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `key` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keywords` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lccn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `month` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8_unicode_ci,
  `number` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `organization` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pages` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `price` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publisher` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `school` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `series` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `translator` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `volume` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `year` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `createdBy` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `modifiedBy` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hash` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `markedDeleted` tinyint(1) NOT NULL DEFAULT '0',
  `attachments` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `fulltext` (`abstract`,`annote`,`author`,`booktitle`,`subtitle`,`contents`,`editor`,`howpublished`,`journal`,`keywords`,`note`,`publisher`,`school`,`title`,`year`),
  FULLTEXT KEY `basic` (`author`,`title`,`year`,`editor`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `database1_data_Reference`
--

LOCK TABLES `database1_data_Reference` WRITE;
/*!40000 ALTER TABLE `database1_data_Reference` DISABLE KEYS */;
INSERT INTO `database1_data_Reference` VALUES (1,'2017-10-19 21:56:28','2017-10-19 21:56:32','-2002-Aktualität','book',NULL,'Berlin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2., neu bearb. und erw. Aufl.',NULL,NULL,NULL,'383050277X',NULL,NULL,NULL,'Capital punishment; Moral and ethical aspects; Todesstrafe; Rechtsvergleich',NULL,NULL,NULL,NULL,'Christian Boulanger ... (Hrsg.)',NULL,NULL,NULL,NULL,'Berlin-Verl. Spitz',NULL,NULL,NULL,'Zur Aktualität der Todesstrafe: interdisziplinäre und globale Perspektiven',NULL,NULL,'http://bvbr.bib-bvb.de:8991/F?func=service&doc_library=BVB01&local_base=BVB01&doc_number=009751584&sequence=000001&line_number=0001&func_code=DB_RECORDS&service_type=MEDIA',NULL,'2002','user','user',NULL,0,NULL),(2,'2017-10-19 21:56:28','2017-10-19 21:56:37','-2002-Aktualität','book',NULL,'Berlin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2., neu bearb. und erw. Aufl.',NULL,NULL,NULL,'383050277X',NULL,NULL,NULL,'Capital punishment; Moral and ethical aspects; Todesstrafe; Rechtsvergleich',NULL,NULL,NULL,NULL,'Christian Boulanger ... (Hrsg.)',NULL,NULL,NULL,NULL,'Berlin-Verl. Spitz',NULL,NULL,NULL,'Zur Aktualität der Todesstrafe: interdisziplinäre und globale Perspektiven',NULL,NULL,'http://bvbr.bib-bvb.de:8991/F?func=service&doc_library=BVB01&local_base=BVB01&doc_number=009751584&sequence=000001&line_number=0001&func_code=DB_RECORDS&service_type=MEDIA',NULL,'2002','user','user',NULL,0,NULL),(3,'2017-10-19 21:57:13','2017-10-19 21:57:16','Boulanger-2002-Aktualität','book',NULL,'Berlin',NULL,NULL,'Boulanger, Christian',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2., neu bearb. und erw. Aufl.',NULL,NULL,NULL,'3-8305-0277-X',NULL,NULL,NULL,'Todesstrafe; Rechtsvergleich',NULL,NULL,NULL,NULL,'Christian Boulanger ... (Hrsg.)',NULL,NULL,NULL,NULL,'Berlin-Verl. Spitz',NULL,NULL,NULL,'Zur Aktualität der Todesstrafe: interdisziplinäre und globale Perspektiven',NULL,NULL,NULL,NULL,'2002','user','user',NULL,0,NULL);
/*!40000 ALTER TABLE `database1_data_Reference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `database1_data_Transaction`
--

DROP TABLE IF EXISTS `database1_data_Transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `database1_data_Transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datasource` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `class` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transactionId` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `datasource_class_index` (`datasource`,`class`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `database1_data_Transaction`
--

LOCK TABLES `database1_data_Transaction` WRITE;
/*!40000 ALTER TABLE `database1_data_Transaction` DISABLE KEYS */;
INSERT INTO `database1_data_Transaction` VALUES (1,'2017-10-19 21:53:41','2017-10-19 21:57:16','database1','bibliograph_model_FolderModel',5);
/*!40000 ALTER TABLE `database1_data_Transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `database1_join_Folder_Reference`
--

DROP TABLE IF EXISTS `database1_join_Folder_Reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `database1_join_Folder_Reference` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `FolderId` int(11) DEFAULT NULL,
  `ReferenceId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_Folder_Reference` (`FolderId`,`ReferenceId`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `database1_join_Folder_Reference`
--

LOCK TABLES `database1_join_Folder_Reference` WRITE;
/*!40000 ALTER TABLE `database1_join_Folder_Reference` DISABLE KEYS */;
INSERT INTO `database1_join_Folder_Reference` VALUES (1,'2017-10-19 21:56:32','2017-10-19 21:56:32',1,1),(2,'2017-10-19 21:56:37','2017-10-19 21:56:37',1,2),(3,'2017-10-19 21:57:16','2017-10-19 21:57:16',1,3);
/*!40000 ALTER TABLE `database1_join_Folder_Reference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `database2_data_Transaction`
--

DROP TABLE IF EXISTS `database2_data_Transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `database2_data_Transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datasource` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `class` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transactionId` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `datasource_class_index` (`datasource`,`class`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `database2_data_Transaction`
--

LOCK TABLES `database2_data_Transaction` WRITE;
/*!40000 ALTER TABLE `database2_data_Transaction` DISABLE KEYS */;
INSERT INTO `database2_data_Transaction` VALUES (1,'2017-10-19 21:53:28','2017-10-19 21:53:28','database1','bibliograph_model_FolderModel',3);
/*!40000 ALTER TABLE `database2_data_Transaction` ENABLE KEYS */;
UNLOCK TABLES;
