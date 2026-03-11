-- Adminer 4.7.7 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE `db_sample` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `db_sample`;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `employeefile`;
CREATE TABLE `employeefile` (
  `recid` int(11) NOT NULL AUTO_INCREMENT,
  `advisorID` varchar(100) DEFAULT NULL,
  `advisorname` varchar(100) DEFAULT NULL,
  `is_vaccinated` int(1) DEFAULT NULL,
  `employee_birthday` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT '0.00',
  `address` varchar(100) DEFAULT NULL,
  `position_code` varchar(100) DEFAULT NULL,
  `telnum` int(11) DEFAULT NULL,
  PRIMARY KEY (`recid`),
  KEY `position_code` (`position_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus` (
  `recid` int(11) NOT NULL AUTO_INCREMENT,
  `mencap` varchar(30) NOT NULL,
  `menprogram` varchar(30) NOT NULL,
  `menlogo` varchar(30) NOT NULL,
  `menidx` varchar(30) DEFAULT NULL,
  `mennum` decimal(10,2) DEFAULT NULL,
  `mensub` varchar(30) NOT NULL,
  `mengrp` varchar(10) NOT NULL,
  `is_removed` varchar(100) NOT NULL,
  `is_disabled` varchar(100) NOT NULL,
  `has_crud` varchar(100) NOT NULL,
  PRIMARY KEY (`recid`),
  UNIQUE KEY `mencap` (`mencap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `menus` (`recid`, `mencap`, `menprogram`, `menlogo`, `menidx`, `mennum`, `mensub`, `mengrp`, `is_removed`, `is_disabled`, `has_crud`) VALUES
(1,	'Home',	'main.php',	'fas fa-home',	'1.00',	0.00,	'',	'1',	'',	'',	''),
(2,	'Sample MF Program',	'employeefile.php',	'fab fa-adversal',	'2.00',	0.00,	'',	'1',	'',	'',	'Y'),
(3,	'Utilities',	'',	'fas fa-cogs',	'3.00',	1.00,	'UTL',	'3',	'',	'',	''),
(4,	'Users',	'utl_users.php',	'fas fa-users-cog',	'x',	1.00,	'',	'UTL',	'',	'',	''),
(5,	'Password',	'utl_password.php',	'fas fa-key',	'x',	2.00,	'',	'UTL',	'',	'',	'N'),
(6,	'User Activity Log',	'utl_useractivitylog.php',	'fas fa-file-contract',	'x',	1.00,	'',	'UTL',	'',	'',	'N');

DROP TABLE IF EXISTS `mf_positionfile`;
CREATE TABLE `mf_positionfile` (
  `recid` int(11) NOT NULL AUTO_INCREMENT,
  `position_code` varchar(100) DEFAULT NULL,
  `position_desc` varchar(100) NOT NULL,
  PRIMARY KEY (`recid`),
  UNIQUE KEY `position_desc` (`position_desc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `mf_positionfile` (`recid`, `position_code`, `position_desc`) VALUES
(1,	'SLS',	'Sales Representative'),
(2,	'MKTNG',	'Marketing'),
(3,	'CSR',	'Customer Support');

DROP TABLE IF EXISTS `syspar`;
CREATE TABLE `syspar` (
  `userlogmaxrec` bigint(11) DEFAULT '0',
  `landing_page` varchar(100) DEFAULT NULL,
  `system_name` varchar(100) DEFAULT NULL,
  `version` varchar(100) DEFAULT NULL,
  `logo_dir` varchar(100) DEFAULT NULL,
  `logo_height` varchar(100) DEFAULT NULL,
  `logo_width` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `syspar` (`userlogmaxrec`, `landing_page`, `system_name`, `version`, `logo_dir`, `logo_height`, `logo_width`) VALUES
(0,	'employeefile.php',	'Sample Program Name',	'v1.2',	'images/logo_long.png',	'auto',	'150px');

DROP TABLE IF EXISTS `useractivitylogfile`;
CREATE TABLE `useractivitylogfile` (
  `usrcde` varchar(15) DEFAULT NULL,
  `usrname` varchar(20) DEFAULT NULL,
  `usrdte` date DEFAULT NULL,
  `usrtim` varchar(15) DEFAULT NULL,
  `trndte` datetime DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `activity` varchar(100) DEFAULT NULL,
  `empcode` varchar(50) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `remarks` varchar(150) DEFAULT NULL,
  `linenum` int(11) DEFAULT '0',
  `parameter` varchar(50) DEFAULT NULL,
  `trncde` varchar(3) DEFAULT NULL,
  `trndsc` varchar(50) DEFAULT NULL,
  `recid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `compname` varchar(30) DEFAULT NULL,
  `usrnam` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`recid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `recid` int(11) NOT NULL AUTO_INCREMENT,
  `userdesc` varchar(30) NOT NULL,
  `usercode` varchar(30) NOT NULL,
  `full_name` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`recid`),
  UNIQUE KEY `usercode` (`usercode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`recid`, `userdesc`, `usercode`, `full_name`, `password`) VALUES
(1,	'admin',	'USR-00001',	'admin_fullname',	'$2y$10$uMsUuLRvVqLSc4y6SSuaQe0dWXRVxvlXoVMEraWJ6XkeOthUTrTOW');

DROP TABLE IF EXISTS `user_menus`;
CREATE TABLE `user_menus` (
  `recid` int(11) NOT NULL AUTO_INCREMENT,
  `usercode` varchar(30) NOT NULL,
  `mencap` varchar(30) NOT NULL,
  `menprogram` varchar(30) NOT NULL,
  `menlogo` varchar(30) NOT NULL,
  `menidx` varchar(30) NOT NULL,
  `mennum` decimal(10,2) DEFAULT NULL,
  `mensub` varchar(30) DEFAULT NULL,
  `mengrp` varchar(10) NOT NULL,
  `is_removed` varchar(100) DEFAULT NULL,
  `add` varchar(1) DEFAULT NULL,
  `edit` varchar(1) DEFAULT NULL,
  `view` varchar(1) DEFAULT NULL,
  `delete` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`recid`),
  KEY `usercode` (`usercode`),
  KEY `mencap` (`mencap`),
  CONSTRAINT `user_menus_ibfk_1` FOREIGN KEY (`usercode`) REFERENCES `users` (`usercode`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `user_menus_ibfk_2` FOREIGN KEY (`mencap`) REFERENCES `menus` (`mencap`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2022-01-28 14:26:51