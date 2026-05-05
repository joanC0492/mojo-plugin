-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-07-2025 a las 02:43:47
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `test`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_booking`
--

CREATE TABLE IF NOT EXISTS `cs_booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calendar_id` int(11) NOT NULL,
  `start_date` varchar(255) NOT NULL,
  `end_date` varchar(255) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `owner_position` int(11) NOT NULL,
  `in_round` int(11) DEFAULT NULL,
  `type` enum('for rent','for personal use','','') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_id` (`calendar_id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_calendar`
--

CREATE TABLE IF NOT EXISTS `cs_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `owners_priority` varchar(255) DEFAULT NULL,
  `round` int(11) DEFAULT NULL,
  `turn` int(11) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `colors_order` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_contacts`
--

CREATE TABLE IF NOT EXISTS `cs_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(255) DEFAULT NULL,
  `admin_email` varchar(255) DEFAULT NULL,
  `admin_phone` varchar(255) DEFAULT NULL,
  `sale_name` varchar(255) DEFAULT NULL,
  `sale_email` varchar(255) DEFAULT NULL,
  `sale_phone` varchar(255) DEFAULT NULL,
  `request_email` varchar(255) DEFAULT NULL,
  `rent_email` varchar(255) DEFAULT NULL,
  `contact_us_email` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `phone_footer` varchar(255) DEFAULT NULL,
  `mail_footer` varchar(255) DEFAULT NULL,
  `direction_footer` varchar(255) DEFAULT NULL,
  `bgcolor_pdf` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `cs_contacts`
--

INSERT INTO `cs_contacts` (`id`, `admin_name`, `admin_email`, `admin_phone`, `sale_name`, `sale_email`, `sale_phone`, `request_email`, `rent_email`, `contact_us_email`, `facebook`, `instagram`, `linkedin`, `youtube`, `phone_footer`, `mail_footer`, `direction_footer`, `bgcolor_pdf`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '#ff0000');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_notifications`
--

CREATE TABLE IF NOT EXISTS `cs_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `notification` varchar(255) NOT NULL,
  `datetime` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_owners`
--

CREATE TABLE IF NOT EXISTS `cs_owners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `visible_info` varchar(4) NOT NULL DEFAULT '1',
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `cs_owners`
--

INSERT INTO `cs_owners` (`id`, `name`, `email`, `password`, `phone`, `visible_info`, `is_active`) VALUES
(1, 'Mojo Sharing', '-', '-', NULL, '0', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_owner_property`
--

CREATE TABLE IF NOT EXISTS `cs_owner_property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `owner_position` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=633 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_properties`
--

CREATE TABLE IF NOT EXISTS `cs_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `share_qty` int(10) NOT NULL,
  `facebook_group` varchar(255) DEFAULT NULL,
  `whatsapp_group` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `resell_shares` int(10) DEFAULT NULL,
  `property_type` varchar(255) DEFAULT NULL,
  `bedroom` int(10) DEFAULT NULL,
  `bathroom` int(10) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `gallery` longtext DEFAULT NULL,
  `key_features` text DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `slug` varchar(255) DEFAULT NULL,
  `show_shares` tinyint(4) NOT NULL DEFAULT 0,
  `rental_booking_page` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_property_operation`
--

CREATE TABLE IF NOT EXISTS `cs_property_operation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `operation_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('fixed','temporary','','') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_seasons`
--

CREATE TABLE IF NOT EXISTS `cs_seasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` varchar(255) NOT NULL,
  `type` enum('high','middle','low','14-day','') NOT NULL,
  `year` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cs_templates`
--

CREATE TABLE IF NOT EXISTS `cs_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `email_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `message` varchar(255) DEFAULT NULL,
  `push_enabled` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Estructura de tabla para la tabla `cs_comments`
--

CREATE TABLE IF NOT EXISTS `cs_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calendar_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_id` (`calendar_id`),
  CONSTRAINT `fk_cs_comments_calendar`
    FOREIGN KEY (`calendar_id`)
    REFERENCES `cs_calendar` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


--
-- Volcado de datos para la tabla `cs_templates`
--

INSERT INTO `cs_templates` (`id`, `subject`, `body`, `email_enabled`, `message`, `push_enabled`) VALUES
(1, NULL, NULL, 0, NULL, 0),
(2, NULL, NULL, 0, NULL, 0),
(3, NULL, NULL, 0, NULL, 0),
(4, NULL, NULL, 0, NULL, 0),
(5, NULL, NULL, 0, NULL, 0);
(6, NULL, NULL, 1, NULL, 0);

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cs_booking`
--
ALTER TABLE `cs_booking`
  ADD CONSTRAINT `cs_booking_ibfk_1` FOREIGN KEY (`calendar_id`) REFERENCES `cs_calendar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cs_booking_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `cs_owners` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cs_calendar`
--
ALTER TABLE `cs_calendar`
  ADD CONSTRAINT `cs_calendar_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `cs_properties` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cs_notifications`
--
ALTER TABLE `cs_notifications`
  ADD CONSTRAINT `cs_notifications_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `cs_owners` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cs_owner_property`
--
ALTER TABLE `cs_owner_property`
  ADD CONSTRAINT `cs_owner_property_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `cs_properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cs_owner_property_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `cs_owners` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cs_property_operation`
--
ALTER TABLE `cs_property_operation`
  ADD CONSTRAINT `cs_property_operation_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `cs_properties` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
