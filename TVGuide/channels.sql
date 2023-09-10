-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Nov 08, 2019 at 02:40 PM
-- Server version: 8.0.16
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tvguide`
--

--
-- Dumping data for table `channel`
--

INSERT INTO `channel` (`id`, `data_id`, `name`, `url_name`, `order`, `allowed`, `radio`) VALUES
(1, 'r50', 'Etelä-Karjalan Radio', 'etel-karjalan-radio', 255, b'1', b'1'),
(2, 'r61', 'Etelä-Savon Radio', 'etel-savon-radio', 255, b'1', b'1'),
(3, 'r81', 'Kainuun Radio', 'kainuun-radio', 255, b'1', b'1'),
(4, 'r51', 'Kymenlaakson Radio', 'kymenlaakson-radio', 255, b'1', b'1'),
(5, 'r41', 'Lahden Radio', 'lahden-radio', 255, b'1', b'1'),
(6, 'r90', 'Lapin Radio', 'lapin-radio', 255, b'1', b'1'),
(7, 'r80', 'Oulu-Radio', 'oulu-radio', 255, b'1', b'1'),
(8, 'r70', 'Pohjanmaan Radio', 'pohjanmaan-radio', 255, b'1', b'1'),
(9, 'r62', 'Pohjois-Karjalan Radio', 'pohjois-karjalan-radio', 255, b'1', b'1'),
(10, 'r42', 'Radio Häme', 'radio-hame', 255, b'1', b'1'),
(11, 'r71', 'Radio Keski-Pohjanmaa', 'radio-keski-pohjanmaa', 255, b'1', b'1'),
(12, 'r30', 'Radio Keski-Suomi', 'radio-keski-suomi', 255, b'1', b'1'),
(13, 'r91', 'Radio Perämeri', 'radio-per-meri', 255, b'1', b'1'),
(14, 'r60', 'Radio Savo', 'radio-savo', 255, b'1', b'1'),
(15, 'r21', 'Satakunnan Radio', 'satakunnan-radio', 255, b'1', b'1'),
(16, 'r40', 'Tampereen Radio', 'tampereen-radio', 255, b'1', b'1'),
(17, 'r20', 'Turun Radio', 'turun-radio', 255, b'1', b'1'),
(18, 'r17', 'Yle Klassinen', 'yle-klassinen', 255, b'1', b'1'),
(19, 'r19', 'Yle Mondo', 'yle-mondo', 255, b'1', b'1'),
(20, 'r48', 'Yle Puhe', 'yle-puhe', 255, b'1', b'1'),
(21, 'r01', 'Yle Radio 1', 'yle-radio-1', 255, b'1', b'1'),
(22, 'r03', 'Yle Radio Suomi', 'yle-radio-suomi', 255, b'1', b'1'),
(23, 'r93', 'Yle Sámi Radio', 'yle-sami-radio', 255, b'1', b'1'),
(24, 'r04', 'Yle Vega', 'yle-vega', 255, b'1', b'1'),
(25, 'r55', 'Yle Vega Åboland', 'yle-vega-boland', 255, b'1', b'1'),
(26, 'r58', 'Yle Vega Huvudstadsregionen', 'yle-vega-huvudstadsregionen', 255, b'1', b'1'),
(27, 'r54', 'Yle Vega Österbotten', 'yle-vega-sterbotten', 255, b'1', b'1'),
(28, 'r59', 'Yle Vega Östnyland', 'yle-vega-stnyland', 255, b'1', b'1'),
(29, 'r57', 'Yle Vega Västnyland', 'yle-vega-v-stnyland', 255, b'1', b'1'),
(30, 'r44', 'Yle X3M', 'yle-x3m', 255, b'1', b'1'),
(31, 'r10', 'Ylen Aikainen', 'ylen-aikainen', 255, b'1', b'1'),
(32, 'r02', 'YleX', 'ylex', 255, b'1', b'1'),
(33, 'alf', 'AlfaTV', 'alfatv', 16, b'1', b'0'),
(34, 'ava', 'AVA', 'ava', 12, b'1', b'0'),
(35, 'jun', 'C More Juniori', 'c-more-juniori', 19, b'1', b'0'),
(36, 'mtm', 'C More Max', 'c-more-max', 19, b'1', b'0'),
(37, 'ms1', 'C More Sport 1', 'c-more-sport-1', 17, b'1', b'0'),
(38, 'ms2', 'C More Sport 2', 'c-more-sport-2', 18, b'1', b'0'),
(39, 'stv', 'FOX', 'fox', 8, b'1', b'0'),
(40, 'fri', 'FRII', 'frii', 14, b'1', b'0'),
(41, 'her', 'Hero', 'hero', 13, b'1', b'0'),
(42, 'nep', 'JIM', 'jim', 10, b'1', b'0'),
(43, 'voi', 'Kutonen', 'kutonen', 11, b'1', b'0'),
(44, 'liv', 'Liv', 'liv', 9, b'1', b'0'),
(45, 'mtv', 'MTV3', 'mtv3', 3, b'1', b'0'),
(46, 'nag', 'National Geographic', 'national-geographic', 15, b'1', b'0'),
(47, 'nel', 'Nelonen', 'nelonen', 4, b'1', b'0'),
(48, 'tvt', 'Sub', 'sub', 6, b'1', b'0'),
(49, 'tlc', 'TLC', 'tlc', 16, b'1', b'0'),
(50, 'tvf', 'TV Finland', 'tv-finland', 16, b'1', b'0'),
(51, 'tv5', 'TV5', 'tv5', 7, b'1', b'0'),
(52, 'fsd', 'Yle Teema & Fem', 'yle-teema-fem', 5, b'1', b'0'),
(53, 'tv1', 'Yle TV1', 'yle-tv1', 1, b'1', b'0'),
(54, 'tv2', 'Yle TV2', 'yle-tv2', 2, b'1', b'0'),
(55, 'fi.viasat.sport', 'Viasat Sport', 'viasat-sport', 255, b'1', b'0'),
(56, 'fi.viasat.jalkapallo.hd', 'Viasat Jalkapallo HD', 'viasat-jalkapallo-hd', 255, b'1', b'0'),
(57, 'fi.viasat.sport.premium', 'Viasat Sport Premium', 'viasat-sport-premium', 255, b'1', b'0'),
(58, 'fi.viasat.hockey', 'Viasat Hockey', 'viasat-hockey', 255, b'1', b'0'),
(59, 'fi.viasat.premiere', 'Viasat Premiere', 'viasat-premiere', 255, b'1', b'0'),
(60, 'fi.viasat.film.action', 'Viasat Film Action', 'viasat-film-action', 255, b'1', b'0'),
(61, 'fi.viasat.film.hits', 'Viasat Film Hits', 'viasat-film-hits', 255, b'1', b'0'),
(62, 'fi.viasat.film.family', 'Viasat Film Family', 'viasat-film-family', 255, b'1', b'0'),
(63, 'fi.viasat.nature', 'Viasat Nature', 'viasat-nature', 255, b'1', b'0'),
(64, 'fi.viasat.extra.1', 'Viasat Extra 1', 'viasat-extra-1', 255, b'0', b'0'),
(65, 'fi.viasat.extra.3', 'Viasat Extra 3', 'viasat-extra-3', 255, b'0', b'0'),
(66, 'fi.viasat.extra.4', 'Viasat Extra 4', 'viasat-extra-4', 255, b'0', b'0'),
(67, 'fi.viasat.jaakiekko.hd', 'Viasat Jääkiekko HD', 'viasat-jaakiekko-hd', 255, b'1', b'0'),
(68, 'fi.viasat.golf', 'Viasat Golf', 'viasat-golf', 255, b'1', b'0'),
(69, 'fi.viasat.esport.tv', 'eSportsTV', 'esportstv', 255, b'1', b'0'),
(70, 'fi.viasat.history', 'Viasat History', 'viasat-history', 255, b'1', b'0'),
(71, 'fi.viasat.extra.2', 'Viasat Extra 2', 'viasat-extra-2', 255, b'0', b'0'),
(72, 'fi.viasat.extra.5', 'Viasat Extra 5', 'viasat-extra-5', 255, b'0', b'0'),
(73, 'fi.viasat.fotboll', 'Viasat Fotboll', 'viasat-fotboll', 255, b'1', b'0'),
(74, 'fi.viasat.ultra.hd', 'Viasat Ultra HD', 'viasat-ultra-hd', 255, b'1', b'0'),
(75, 'fi.viasat.explore', 'Viasat Explore', 'viasat-explore', 255, b'1', b'0'),
(76, 'HIS.SD.Fin', 'History', 'history', 23, b'1', b'0'),
(77, 'H2.EU.Fin', 'History 2', 'history-2', 24, b'1', b'0'),
(78, 'es1', 'Eurosport 1', 'eurosport-1', 21, b'1', b'0'),
(79, 'es2', 'Eurosport 2', 'eurosport-2', 22, b'1', b'0'),
(80, 'APEUFIN', 'Animal Planet', 'animal-planet', 20, b'1', b'0'),
(81, 'DCFIFIN', 'Discovery', 'discovery', 20, b'1', b'0'),
(82, 'paramount-network-finland', 'Paramount Network', 'paramount-network', 15, b'1', b'0'),
(83, 'mtv-rocks-uk', 'MTV Rocks', 'mtv-rocks', 255, b'1', b'0'),
(84, 'mtv-hits-uk', 'MTV Hits', 'mtv-hits', 255, b'1', b'0'),
(85, 'mtv-finland', 'MTV', 'mtv', 16, b'1', b'0'),
(86, 'club-mtv', 'MTV Dance', 'club-mtv', 255, b'1', b'0'),
(87, 'nick-jr-global', 'Nick Jr.', 'nick-jr', 255, b'1', b'0'),
(203, 'fi.viasat.urheilu.hd', 'Viasat Urheilu Hd', 'viasat-urheilu-hd', 255, b'0', b'0');

--
-- Dumping data for table `group`
--

INSERT INTO `group` (`id`, `name`) VALUES
(1, 'Peruskanavat'),
(2, 'Viasat'),
(3, 'Urheilu'),
(4, 'C More'),
(5, 'Musiikki'),
(6, 'Lastenohjelmat'),
(7, 'Elokuvat'),
(8, 'Dokumentit');

--
-- Dumping data for table `group_channel`
--

INSERT INTO `group_channel` (`group_id`, `channel_id`) VALUES
(1, 53),
(1, 54),
(2, 69),
(2, 75),
(2, 64),
(2, 71),
(2, 65),
(2, 66),
(2, 72),
(2, 60),
(2, 62),
(2, 61),
(2, 73),
(2, 68),
(2, 70),
(2, 58),
(2, 67),
(2, 56),
(2, 63),
(2, 59),
(2, 55),
(2, 57),
(2, 74),
(2, 203),
(5, 85),
(5, 84),
(5, 83),
(3, 37),
(3, 38),
(1, 45),
(1, 47),
(1, 52),
(1, 43),
(4, 35),
(4, 37),
(4, 38),
(4, 36),
(6, 87),
(1, 48),
(1, 46),
(1, 34),
(1, 41),
(1, 82),
(1, 40),
(6, 35),
(3, 69),
(3, 73),
(3, 68),
(3, 58),
(3, 203),
(3, 55),
(3, 57),
(3, 67),
(3, 56),
(7, 60),
(7, 62),
(7, 61),
(8, 77),
(8, 76),
(8, 81),
(8, 80);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
