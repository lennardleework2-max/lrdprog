-- Adminer 4.7.7 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

CREATE DATABASE `db_name_appsystem`;
USE `db_name_appsystem`;

DROP TABLE IF EXISTS `db_name_companyfile`;
CREATE TABLE `db_name_companyfile` (
  `comp_desc` varchar(100) NOT NULL,
  `comp_code` varchar(100) NOT NULL,
  `db_dbname` varchar(100) NOT NULL,
  `db_username` varchar(100) NOT NULL,
  `db_pass` varchar(100) NOT NULL,
  `db_host` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `db_name_companyfile` (`comp_desc`, `comp_code`, `db_dbname`, `db_username`, `db_pass`, `db_host`) VALUES
('Traditional Medicine',	'SMIC',	'db_name',	'lstuser_lennard',	'lstV@2021',	'localhost');

-- 2021-12-11 13:46:50