-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 11, 2026 at 07:51 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elibrary_db`
--

CREATE DATABASE IF NOT EXISTS `elibrary_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `elibrary_db`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'register', 'user', 2, 'New user registered', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-19 06:52:12'),
(2, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-19 06:54:14'),
(3, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-19 06:57:25'),
(4, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-19 06:59:20'),
(5, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 07:18:21'),
(6, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 07:27:41'),
(7, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 07:28:00'),
(8, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 07:53:16'),
(9, 3, 'register', 'user', 3, 'New user registered', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 07:53:54'),
(10, 3, 'login', 'user', 3, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 07:54:02'),
(11, 3, 'logout', 'user', 3, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 08:00:19'),
(12, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 08:24:47'),
(13, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 08:24:54'),
(14, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 08:30:16'),
(15, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 08:32:44'),
(16, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 08:32:58'),
(17, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-19 09:36:17'),
(18, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:03:06'),
(19, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:05:50'),
(20, 4, 'login', 'user', 4, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:07:24'),
(21, 4, 'logout', 'user', 4, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:24:41'),
(22, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:24:47'),
(23, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 OPR/125.0.0.0', '2026-01-19 10:47:46'),
(24, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 OPR/125.0.0.0', '2026-01-19 10:48:53'),
(25, 5, 'register', 'user', 5, 'New user registered', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 OPR/125.0.0.0', '2026-01-19 10:49:17'),
(26, 5, 'login', 'user', 5, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 OPR/125.0.0.0', '2026-01-19 10:49:28'),
(27, 5, 'logout', 'user', 5, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 OPR/125.0.0.0', '2026-01-19 10:49:47'),
(28, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 OPR/125.0.0.0', '2026-01-19 10:53:15'),
(29, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2026-01-19 10:54:03'),
(30, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-19 11:01:13'),
(31, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-19 11:01:23'),
(32, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-02-15 13:41:50'),
(33, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-02-15 13:44:18'),
(34, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-05 02:12:43'),
(35, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-05 02:12:52'),
(36, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-05 04:51:57'),
(37, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-05 04:52:09'),
(38, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-05 04:52:14'),
(39, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-05 04:52:51'),
(40, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-05 04:52:58'),
(41, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-03-05 10:10:39'),
(42, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 11:08:33'),
(43, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-09 14:28:59'),
(44, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-09 14:29:05'),
(45, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 14:45:24'),
(46, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-09 14:52:31'),
(47, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-09 14:52:38'),
(48, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 16:04:49'),
(49, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-09 16:25:12'),
(50, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 16:25:30'),
(51, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-09 16:28:24'),
(52, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-09 16:34:08'),
(53, 4, 'login', 'user', 4, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-09 16:34:19'),
(54, 4, 'download', 'ebook', 5, 'Downloaded: Ms. Rachel Animals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 13:45:30'),
(55, 4, 'logout', 'user', 4, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 13:48:15'),
(56, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 13:48:23'),
(57, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 13:50:14'),
(58, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 13:50:43'),
(59, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 13:52:50'),
(60, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 14:46:39'),
(61, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 21:48:37'),
(62, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-12 21:49:25'),
(63, 6, 'register', 'user', 6, 'New user registered', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 23:53:44'),
(64, 6, 'login', 'user', 6, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 23:54:01'),
(65, 6, 'logout', 'user', 6, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 23:55:24'),
(66, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 23:55:32'),
(67, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 23:56:49'),
(68, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 23:56:58'),
(69, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 00:06:15'),
(70, 6, 'login', 'user', 6, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 00:32:03'),
(71, 6, 'logout', 'user', 6, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 01:02:55'),
(72, 6, 'login', 'user', 6, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 01:04:22'),
(73, 2, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-11 04:33:45'),
(74, 2, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-11 04:34:19'),
(75, 6, 'login', 'user', 6, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-11 04:34:39'),
(76, 6, 'logout', 'user', 6, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-11 04:35:19'),
(77, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 07:34:20');

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

DROP TABLE IF EXISTS `bookmarks`;
CREATE TABLE IF NOT EXISTS `bookmarks` (
  `bookmark_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ebook_id` int NOT NULL,
  `page_number` int DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bookmark_id`),
  KEY `ebook_id` (`ebook_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `icon`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'English', 'English language and literature books', 'book', 1, 1, '2025-12-19 05:11:38'),
(2, 'Mathematics', 'Mathematics textbooks and workbooks', 'calculator', 2, 1, '2025-12-19 05:11:38'),
(3, 'Science', 'Science books and experiments', 'flask', 3, 1, '2025-12-19 05:11:38'),
(4, 'Filipino', 'Filipino language and literature', 'flag', 4, 1, '2025-12-19 05:11:38'),
(5, 'Araling Panlipunan', 'Social studies and history', 'globe', 5, 1, '2025-12-19 05:11:38'),
(6, 'MAPEH', 'Music, Arts, PE and Health books', 'music', 6, 1, '2025-12-19 05:11:38'),
(9, 'Storybooks', 'Fiction and storybooks for children', 'fas fa-book', 7, 1, '2025-12-19 07:08:25'),
(10, 'Reference', 'Reference materials and guides', 'fas fa-bookmark', 8, 1, '2025-12-19 07:08:25');

-- --------------------------------------------------------

--
-- Table structure for table `downloads`
--

DROP TABLE IF EXISTS `downloads`;
CREATE TABLE IF NOT EXISTS `downloads` (
  `download_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ebook_id` int NOT NULL,
  `download_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`download_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ebook_id` (`ebook_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ebooks`
--

DROP TABLE IF EXISTS `ebooks`;
CREATE TABLE IF NOT EXISTS `ebooks` (
  `ebook_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grade_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `content_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'book',
  `section_id` int DEFAULT NULL,
  `language` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'English',
  `publisher` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `page_count` int DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `download_count` int DEFAULT '0',
  `view_count` int DEFAULT '0',
  `uploaded_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ebook_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_category` (`category`),
  KEY `idx_subject` (`subject`),
  KEY `idx_grade_level` (`grade_level`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_title` (`title`(250))
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ebooks`
--

INSERT INTO `ebooks` (`ebook_id`, `title`, `author`, `isbn`, `description`, `cover_image`, `file_path`, `file_type`, `file_size`, `category`, `subject`, `grade_level`, `content_type`, `section_id`, `language`, `publisher`, `publication_year`, `page_count`, `is_featured`, `is_active`, `download_count`, `view_count`, `uploaded_by`, `approved_by`, `is_approved`, `created_at`, `updated_at`) VALUES
(6, 'Ginger The Giraffe', 'T. Albert', NULL, 'Kids book', '69d98f77824b7_1775865719.png', '69d98f77835f1_1775865719.pdf', NULL, NULL, 'Storybooks', 'English', 'kindergarten', 'book', NULL, 'English', NULL, NULL, NULL, 0, 1, 0, 0, 1, NULL, 1, '2026-04-11 00:00:52', '2026-04-11 00:01:59'),
(7, 'Doing My Chores', 'T. Albert', NULL, '', '69d99050ae1f8_1775865936.png', '69d99050af02c_1775865936.pdf', NULL, NULL, 'Storybooks', 'English', 'grade1', 'book', NULL, 'English', NULL, NULL, NULL, 0, 1, 0, 0, 1, NULL, 1, '2026-04-11 00:05:36', '2026-04-11 00:05:36'),
(8, 'ABE THE SERVICE DOG', 'T. Albert', NULL, 'Abe was a real Service Dog who dedicated his life assisting BJ, a good family friend. Service Dogs are smart, well trained, well behaved, dedicated, and committed to ensuring their master is safe. This book is intended to bring an awareness of their importance to early readers.', '69d9fb0df2d16_1775893261.png', '69d9fb0df3e22_1775893261.pdf', NULL, NULL, 'Storybooks', 'English', 'kindergarten', 'book', NULL, 'English', NULL, NULL, NULL, 0, 1, 0, 0, 1, NULL, 1, '2026-04-11 07:41:02', '2026-04-11 07:41:02'),
(5, 'Ms. Rachel Animals', 'Youtube', NULL, 'Ms. Rachel', '69b2f48618664_1773335686.png', '69b2c34e03c61_1773323086.mp4', NULL, NULL, 'English', 'English', 'kindergarten', 'video', NULL, 'English', NULL, NULL, NULL, 0, 1, 0, 0, 4, NULL, 1, '2026-03-12 13:44:46', '2026-03-12 17:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `used` tinyint(1) DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_otp` (`otp_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `otp_code`, `verified`, `used`, `expires_at`, `created_at`) VALUES
(5, 'kurtrussel644@gmail.com', '634954', 1, 1, '2025-12-19 08:39:17', '2025-12-19 08:24:17');

-- --------------------------------------------------------

--
-- Table structure for table `reading_history`
--

DROP TABLE IF EXISTS `reading_history`;
CREATE TABLE IF NOT EXISTS `reading_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ebook_id` int NOT NULL,
  `last_page` int DEFAULT '0',
  `total_pages` int DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT '0.00',
  `reading_time_minutes` int DEFAULT '0',
  `is_completed` tinyint(1) DEFAULT '0',
  `first_opened` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_accessed` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  UNIQUE KEY `unique_user_ebook` (`user_id`,`ebook_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ebook_id` (`ebook_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reading_progress`
--

DROP TABLE IF EXISTS `reading_progress`;
CREATE TABLE IF NOT EXISTS `reading_progress` (
  `progress_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ebook_id` int NOT NULL,
  `current_page` int DEFAULT '1',
  `total_pages` int DEFAULT '1',
  `progress_percentage` decimal(5,2) DEFAULT '0.00',
  `last_accessed` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`progress_id`),
  UNIQUE KEY `unique_user_book` (`user_id`,`ebook_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_ebook` (`ebook_id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reading_progress`
--

INSERT INTO `reading_progress` (`progress_id`, `user_id`, `ebook_id`, `current_page`, `total_pages`, `progress_percentage`, `last_accessed`) VALUES
(1, 2, 1, 1, 1, 100.00, '2026-04-11 07:56:24'),
(2, 5, 2, 1, 1, 100.00, '2026-01-19 18:49:38'),
(4, 2, 3, 15, 17, 88.00, '2026-03-09 22:28:40'),
(27, 4, 2, 1, 1, 100.00, '2026-03-10 00:34:28'),
(28, 4, 3, 1, 17, 5.00, '2026-03-12 21:34:57'),
(31, 4, 5, 1, 1, 100.00, '2026-03-12 21:46:52'),
(36, 4, 4, 1, 7, 14.00, '2026-03-12 21:47:22'),
(37, 2, 5, 1, 1, 100.00, '2026-03-12 21:49:48'),
(42, 6, 5, 1, 1, 100.00, '2026-04-11 09:04:26'),
(46, 1, 5, 1, 1, 100.00, '2026-04-11 08:02:22'),
(49, 2, 7, 1, 20, 5.00, '2026-04-11 12:33:52'),
(50, 6, 6, 1, 17, 5.00, '2026-04-11 12:35:05'),
(52, 1, 7, 1, 20, 5.00, '2026-04-11 15:34:32'),
(53, 1, 6, 1, 17, 5.00, '2026-04-11 15:34:33'),
(54, 1, 8, 1, 25, 4.00, '2026-04-11 15:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
CREATE TABLE IF NOT EXISTS `sections` (
  `section_id` int NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `unique_section_grade` (`section_name`,`grade_level`),
  KEY `idx_teacher` (`teacher_id`),
  KEY `idx_grade` (`grade_level`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_name`, `grade_level`, `teacher_id`, `is_active`, `created_at`) VALUES
(2, 'Sampaguita', 'kindergarten', 4, 1, '2026-03-09 16:32:56'),
(3, 'Makabayan', 'grade1', NULL, 1, '2026-03-09 16:33:11');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'San Roque Elementary School E-Library', 'Website name', '2025-12-19 05:11:38'),
(2, 'max_downloads_per_user', '10', 'Maximum concurrent downloads per user', '2025-12-19 05:11:38'),
(3, 'enable_offline_reading', '1', 'Enable offline reading feature', '2025-12-19 05:11:38'),
(4, 'enable_teacher_uploads', '1', 'Allow teachers to upload materials', '2025-12-19 05:11:38'),
(5, 'items_per_page', '12', 'Number of books per page', '2025-12-19 05:11:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('student','teacher','parent','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_level` enum('kindergarten','grade1','grade2','grade3','grade4','grade5','grade6','n/a') COLLATE utf8mb4_unicode_ci DEFAULT 'n/a',
  `section` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_grade_level` (`grade_level`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `user_type`, `grade_level`, `section`, `profile_image`, `is_active`, `created_at`, `last_login`, `section_id`) VALUES
(1, 'admin', 'admin@sres.edu.ph', '$2y$10$/U2dgb.P2tdx9BdUNnfIC.sQb8ZEgZOsW.ZqLzv4ObYtGR7VYQYhe', 'System Administrator', 'admin', 'n/a', '', NULL, 1, '2025-12-19 05:11:38', '2026-04-11 07:34:20', NULL),
(2, 'kurtrussel31', 'kurtrussel644@gmail.com', '$2y$10$P/WnpEIZT0uejuqvYKdA7OljYEjsGTBceq/RZQTwpGY9kdBEqNXfu', 'Kurt Russel De Asis', 'student', 'grade1', 'Sampaguita', NULL, 1, '2025-12-19 06:52:12', '2026-04-11 04:33:45', NULL),
(3, 'Sofia', 'sofia@gmail.com', '$2y$10$fqcS40f2JJoX4BTwMH0MNOP2q55Ts5evthmlEMMWdt4a55remJ3yu', 'Sofia Dacasin', 'student', 'grade2', 'Section A', NULL, 1, '2025-12-19 07:53:54', '2025-12-19 07:54:02', NULL),
(4, 'Margarita', 'margaritateacher@gmail.com', '$2y$10$CaHhOTAwREsDK73u4Tg5Qe6lU.6J4R7CbsjhVBngn6vFblQeRynZq', 'Margarita Casipit', 'teacher', 'n/a', '', NULL, 1, '2025-12-19 08:31:53', '2026-03-09 16:34:19', NULL),
(5, 'kurtrussel0731', 'keifferlance17@gmail.com', '$2y$10$fZFZvh0pxDVzpkCpl/ZotelP62xC//lPzchJoYJo3JKc9WiXBJvXu', 'Kurt Russel De Asis', 'parent', 'kindergarten', '', NULL, 1, '2026-01-19 10:49:17', '2026-01-19 10:49:28', 0),
(6, 'burget123', 'brigette@gmail.com', '$2y$10$jnUyNofFDTDJVbsA/OTB3eJRZg3V/Syywyta/pvgULkLa3gS2VVYa', 'Brigette Bertuldo', 'student', 'kindergarten', '', NULL, 1, '2026-04-10 23:53:44', '2026-04-11 04:34:39', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ebooks`
--
ALTER TABLE `ebooks` ADD FULLTEXT KEY `idx_search` (`title`,`author`,`description`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
