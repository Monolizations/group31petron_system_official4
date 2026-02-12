-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 04:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `petron_pos_db_secure`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `reference`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 1, 'Login', 'User logged in', NULL, '::1', '2026-02-07 20:15:11', '2026-02-07 20:15:11'),
(2, 2, 'Login', 'User logged in', NULL, '::1', '2026-02-07 20:15:59', '2026-02-07 20:15:59'),
(3, 1, 'Login', 'User logged in', NULL, '::1', '2026-02-07 20:16:37', '2026-02-07 20:16:37'),
(4, 1, 'Create Station Admin', 'Created admin \'amie\' for station \'1 UNANG HAKBANG ST. COR. BAYANI ST., SAN ISIDRO, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION\'', NULL, '::1', '2026-02-07 20:19:20', '2026-02-07 20:19:20'),
(5, 14, 'Login', 'User logged in', NULL, '::1', '2026-02-07 20:19:50', '2026-02-07 20:19:50'),
(6, 14, 'Add User', 'Created user altea (staff)', NULL, '::1', '2026-02-07 20:41:54', '2026-02-07 20:41:54'),
(7, 14, 'Add User', 'Created user sandara (manager)', NULL, '::1', '2026-02-07 20:42:17', '2026-02-07 20:42:17'),
(8, 16, 'Login', 'User logged in', NULL, '::1', '2026-02-07 20:42:46', '2026-02-07 20:42:46'),
(9, 1, 'Login', 'User logged in', NULL, '::1', '2026-02-07 20:43:35', '2026-02-07 20:43:35'),
(10, 15, 'Login', 'User logged in', NULL, '::1', '2026-02-07 20:45:16', '2026-02-07 20:45:16'),
(11, 15, 'Login', 'User logged in', NULL, '::1', '2026-02-09 09:47:04', '2026-02-09 09:47:04'),
(12, 15, 'Clock In', 'Clocked in at station 226', NULL, '::1', '2026-02-09 09:47:50', '2026-02-09 09:47:50'),
(13, 15, 'Create Job Order', 'Job order created by staff', NULL, '::1', '2026-02-09 10:39:46', '2026-02-09 10:39:46'),
(14, 14, 'Login', 'User logged in', NULL, '::1', '2026-02-09 10:40:39', '2026-02-09 10:40:39'),
(15, 14, 'Admin Review Approved', 'okiee kayow', NULL, '::1', '2026-02-09 11:34:14', '2026-02-09 11:34:14'),
(16, 15, 'Login', 'User logged in', NULL, '::1', '2026-02-09 11:34:23', '2026-02-09 11:34:23'),
(17, 14, 'Login', 'User logged in', NULL, '::1', '2026-02-09 12:12:09', '2026-02-09 12:12:09'),
(18, 16, 'Login', 'User logged in', NULL, '::1', '2026-02-09 12:21:58', '2026-02-09 12:21:58'),
(19, 15, 'Login', 'User logged in', NULL, '::1', '2026-02-09 12:44:32', '2026-02-09 12:44:32'),
(20, 14, 'Login Failed', 'Failed login attempt for username: amie', NULL, '::1', '2026-02-10 08:46:48', '2026-02-10 08:46:48'),
(21, 14, 'Login Failed', 'Failed login attempt for username: amie', NULL, '::1', '2026-02-10 08:46:54', '2026-02-10 08:46:54'),
(22, 14, 'Login', 'User logged in', NULL, '::1', '2026-02-10 08:47:08', '2026-02-10 08:47:08'),
(23, 15, 'Login', 'User logged in', NULL, '::1', '2026-02-10 08:48:47', '2026-02-10 08:48:47'),
(24, 15, 'Record Pump Reading', 'Recorded pump #4: 20 → 30 = 0 L (Morning)', NULL, '::1', '2026-02-10 09:03:08', '2026-02-10 09:03:08'),
(25, 14, 'Login', 'User logged in', NULL, '::1', '2026-02-10 09:03:23', '2026-02-10 09:03:23'),
(26, 16, 'Login', 'User logged in', NULL, '::1', '2026-02-10 09:05:41', '2026-02-10 09:05:41'),
(27, 16, 'Manager Approved Reading', 'Reading #1 approved and moved to reconciliation | Ready for Admin finalization', NULL, '::1', '2026-02-10 09:16:54', '2026-02-10 09:16:54'),
(28, 16, 'Login', 'User logged in', NULL, '::1', '2026-02-10 09:17:03', '2026-02-10 09:17:03'),
(29, 16, 'Manager Approved Reading', 'Reading #1 approved and moved to reconciliation | Ready for Admin finalization', NULL, '::1', '2026-02-10 09:17:34', '2026-02-10 09:17:34'),
(30, 15, 'Login', 'User logged in', NULL, '::1', '2026-02-10 09:19:01', '2026-02-10 09:19:01'),
(31, 15, 'Record Pump Reading', 'Recorded pump #5: 10 → 0 = -12 L (Afternoon)', NULL, '::1', '2026-02-10 09:19:35', '2026-02-10 09:19:35'),
(32, 16, 'Login', 'User logged in', NULL, '::1', '2026-02-10 09:19:45', '2026-02-10 09:19:45'),
(33, 16, 'Manager Approved Reading', 'Reading #2 approved and moved to reconciliation | Ready for Admin finalization', NULL, '::1', '2026-02-10 09:20:18', '2026-02-10 09:20:18'),
(34, 16, 'Manager Approved Reading', 'Reading #1 approved and moved to reconciliation | Ready for Admin finalization', NULL, '::1', '2026-02-10 09:20:22', '2026-02-10 09:20:22'),
(35, 16, 'Manager Approved Reading', 'Reading #1 approved and moved to reconciliation | Ready for Admin finalization', NULL, '::1', '2026-02-10 09:23:23', '2026-02-10 09:23:23'),
(36, 16, 'Manager Approved Reading', 'Reading #2 approved and moved to reconciliation | Ready for Admin finalization', NULL, '::1', '2026-02-10 09:23:33', '2026-02-10 09:23:33'),
(37, 14, 'Login', 'User logged in', NULL, '::1', '2026-02-10 09:23:41', '2026-02-10 09:23:41'),
(38, 14, 'Reconciliation Finalized', 'ID: 2 | Fuel: 2 | Physical: 10L | Variance: 20L | LOCKED', NULL, '::1', '2026-02-10 10:04:22', '2026-02-10 10:04:22'),
(39, 14, 'Login Failed', 'Failed login attempt for username: amie', NULL, '::1', '2026-02-10 10:15:31', '2026-02-10 10:15:31'),
(40, 14, 'Login', 'User logged in', NULL, '::1', '2026-02-10 10:16:30', '2026-02-10 10:16:30'),
(41, 14, 'Reconciliation Finalized', 'ID: 3 | Fuel: 2 | Physical: 12L | Variance: 22L | LOCKED', NULL, '::1', '2026-02-10 10:20:49', '2026-02-10 10:20:49'),
(42, 14, 'Reconciliation Finalized', 'ID: 4 | Fuel: 1 | Physical: 2L | Variance: 16L | LOCKED', NULL, '::1', '2026-02-10 10:24:32', '2026-02-10 10:24:32');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `log_type` varchar(50) NOT NULL COMMENT 'user, transaction, inventory, system',
  `action_type` varchar(100) NOT NULL COMMENT 'Login, Logout, Create, Update, Delete, View',
  `action_details` text DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL COMMENT 'users, sales, inventory, customers, etc',
  `entity_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL COMMENT 'Success, Failed, Pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `type` enum('cash','credit') DEFAULT 'cash',
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `current_balance` decimal(12,2) DEFAULT 0.00,
  `points` int(11) DEFAULT 0,
  `status` enum('active','suspended','inactive') DEFAULT 'active',
  `station_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `type`, `credit_limit`, `current_balance`, `points`, `status`, `station_id`, `created_at`) VALUES
(9, 'Sandara Pagaling', NULL, NULL, NULL, NULL, 'cash', 0.00, 0.00, 0, 'active', 226, '2026-02-09 10:39:46');

-- --------------------------------------------------------

--
-- Table structure for table `customer_credit_transactions`
--

CREATE TABLE `customer_credit_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `transaction_id` varchar(64) DEFAULT NULL,
  `transaction_type` enum('Sale','Payment','Adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `running_balance` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `station_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_ledger`
--

CREATE TABLE `customer_ledger` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `type` enum('Debit','Credit','Adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance` decimal(12,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_statements`
--

CREATE TABLE `customer_statements` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `statement_date` date NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_payments` decimal(12,2) NOT NULL DEFAULT 0.00,
  `closing_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Generated','Sent','Paid') DEFAULT 'Generated',
  `generated_by` int(11) NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_reconciliation`
--

CREATE TABLE `daily_reconciliation` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `reconciliation_date` date NOT NULL,
  `shift_report_id` int(11) DEFAULT NULL,
  `total_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `card_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_fuel_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_merch_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_service_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `variance_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('Pending','Verified','finalized') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_adjustments`
--

CREATE TABLE `fuel_adjustments` (
  `id` int(11) NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `adjustment_date` date DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `adjustment_type` varchar(50) DEFAULT NULL,
  `liters` decimal(10,2) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_daily_readings`
--

CREATE TABLE `fuel_daily_readings` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,
  `reading_date` date NOT NULL,
  `shift` enum('Morning','Afternoon','Evening') NOT NULL,
  `previous_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `calibration` decimal(10,2) DEFAULT 0.00,
  `sales_liters` decimal(10,2) DEFAULT 0.00,
  `user_id` int(11) NOT NULL,
  `status` enum('Pending','Verified','finalized') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_daily_readings`
--

INSERT INTO `fuel_daily_readings` (`id`, `station_id`, `pump_id`, `reading_date`, `shift`, `previous_reading`, `current_reading`, `calibration`, `sales_liters`, `user_id`, `status`, `notes`, `created_at`) VALUES
(1, 226, 4, '2026-02-10', 'Morning', 20.00, 30.00, 10.00, 0.00, 15, '', 'TEST', '2026-02-10 09:03:08'),
(2, 226, 5, '2026-02-10', 'Afternoon', 10.00, 0.00, 2.00, -12.00, 15, '', 'testing', '2026-02-10 09:19:35');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_deliveries`
--

CREATE TABLE `fuel_deliveries` (
  `id` int(11) NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `delivery_liters` decimal(10,2) DEFAULT NULL,
  `tanker_number` varchar(50) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_pumps`
--

CREATE TABLE `fuel_pumps` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `pump_number` varchar(20) NOT NULL,
  `fuel_type_id` int(11) NOT NULL,
  `capacity` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Inactive','Maintenance') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_pumps`
--

INSERT INTO `fuel_pumps` (`id`, `station_id`, `pump_number`, `fuel_type_id`, `capacity`, `status`, `created_at`) VALUES
(1, 1, 'Pump 1', 1, 0.00, 'Active', '2026-02-10 08:52:44'),
(2, 1, 'Pump 2', 1, 0.00, 'Active', '2026-02-10 08:52:44'),
(3, 1, 'Pump 3', 2, 0.00, 'Active', '2026-02-10 08:52:44'),
(4, 226, 'Pump 1', 2, 0.00, 'Active', '2026-02-10 08:59:35'),
(5, 226, 'Pump 2', 1, 0.00, 'Active', '2026-02-10 08:59:35'),
(6, 226, 'Pump 3', 3, 0.00, 'Active', '2026-02-10 08:59:35');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_reconciliation`
--

CREATE TABLE `fuel_reconciliation` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `reconciliation_date` date NOT NULL,
  `fuel_type_id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,
  `previous_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `present_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `calibration` decimal(10,2) DEFAULT 0.00,
  `price_per_liter` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sales_liters` decimal(10,2) GENERATED ALWAYS AS (case when `present_reading` - `previous_reading` - `calibration` > 0 then `present_reading` - `previous_reading` - `calibration` else 0 end) STORED,
  `sales_amount` decimal(12,2) GENERATED ALWAYS AS (case when `present_reading` - `previous_reading` - `calibration` > 0 then (`present_reading` - `previous_reading` - `calibration`) * `price_per_liter` else 0 end) STORED,
  `physical_stock` decimal(10,2) DEFAULT NULL,
  `variance_liters` decimal(10,2) GENERATED ALWAYS AS (coalesce(`sales_liters`,0) - coalesce(`physical_stock`,0)) STORED,
  `variance_percent` decimal(5,2) GENERATED ALWAYS AS (case when `sales_liters` > 0 then (`sales_liters` - coalesce(`physical_stock`,0)) / `sales_liters` * 100 else 0 end) STORED,
  `status` enum('Pending','Verified','finalized') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `variance` decimal(10,2) DEFAULT NULL,
  `finalized_by` int(11) DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `admin_notes` longtext DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_reconciliation`
--

INSERT INTO `fuel_reconciliation` (`id`, `station_id`, `reconciliation_date`, `fuel_type_id`, `pump_id`, `previous_reading`, `present_reading`, `calibration`, `price_per_liter`, `physical_stock`, `status`, `verified_by`, `verified_at`, `notes`, `created_at`, `variance`, `finalized_by`, `finalized_at`, `admin_notes`, `is_locked`) VALUES
(1, 1, '2026-02-10', 1, 1, 4500.00, 5000.00, -10.00, 65.50, NULL, 'Pending', NULL, NULL, NULL, '2026-02-10 09:07:58', NULL, NULL, NULL, NULL, 0),
(2, 226, '2026-02-10', 2, 4, 20.00, 30.00, 20.00, 65.50, 10.00, 'finalized', 14, '2026-02-10 10:04:22', '', '2026-02-10 09:16:54', NULL, NULL, NULL, NULL, 0),
(3, 226, '2026-02-10', 2, 4, 20.00, 30.00, 20.00, 65.50, 12.00, 'finalized', 14, '2026-02-10 10:20:49', '', '2026-02-10 09:17:34', NULL, NULL, NULL, NULL, 0),
(4, 226, '2026-02-10', 1, 5, 10.00, 0.00, 4.00, 65.50, 2.00, 'finalized', 14, '2026-02-10 10:24:32', '', '2026-02-10 09:20:18', NULL, NULL, NULL, NULL, 0),
(5, 226, '2026-02-10', 2, 4, 20.00, 30.00, 10.00, 65.50, NULL, 'Verified', NULL, NULL, '', '2026-02-10 09:20:22', NULL, NULL, NULL, NULL, 0),
(6, 226, '2026-02-10', 2, 4, 20.00, 30.00, 10.00, 65.50, NULL, 'Verified', NULL, NULL, '', '2026-02-10 09:23:23', NULL, NULL, NULL, NULL, 0),
(7, 226, '2026-02-10', 1, 5, 10.00, 0.00, 3.00, 65.50, NULL, 'Verified', NULL, NULL, 'testing', '2026-02-10 09:23:33', NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `fuel_types`
--

CREATE TABLE `fuel_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_types`
--

INSERT INTO `fuel_types` (`id`, `name`, `description`) VALUES
(1, 'Gasoline', 'Gasoline fuel'),
(2, 'Diesel', 'Diesel fuel'),
(3, 'LPG', 'Liquefied Petroleum Gas'),
(4, 'Premium', 'Premium fuel'),
(5, 'Unleaded', 'Unleaded fuel');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stock_level` decimal(12,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 0,
  `capacity` decimal(12,2) DEFAULT 10000.00,
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `station_id`, `product_id`, `stock_level`, `reorder_level`, `capacity`, `unit`, `status`, `last_updated`) VALUES
(1, 1, 1, 1000.00, 100, 10000.00, 'liters', 'active', '2026-02-07 12:14:22'),
(2, 1, 2, 500.00, 50, 5000.00, 'pieces', 'active', '2026-02-07 12:14:22'),
(3, 1, 3, 200.00, 20, 2000.00, 'pieces', 'active', '2026-02-07 12:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('addition','deduction','adjustment','transfer') NOT NULL DEFAULT 'deduction',
  `quantity` decimal(10,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'job_order, sale, etc',
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of reference record',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_orders`
--

CREATE TABLE `job_orders` (
  `id` int(11) NOT NULL,
  `job_order_number` varchar(50) NOT NULL,
  `station_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `service_category_id` int(11) DEFAULT NULL,
  `assigned_mechanic_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `service_description` text NOT NULL,
  `estimated_duration` int(11) DEFAULT 60,
  `status` enum('Pending','Reviewed','In Progress','Completed','Verified','finalized','Cancelled','Rejected') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `requires_approval` tinyint(1) DEFAULT 0 COMMENT 'Whether job requires admin approval',
  `reviewed_by` int(11) DEFAULT NULL COMMENT 'Admin who reviewed the job',
  `reviewed_at` datetime DEFAULT NULL COMMENT 'When job was reviewed',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Admin who gave final approval',
  `approved_at` datetime DEFAULT NULL COMMENT 'When job was approved',
  `admin_remarks` text DEFAULT NULL COMMENT 'Admin review remarks',
  `estimated_labor_cost` decimal(10,2) DEFAULT 0.00 COMMENT 'Estimated labor cost',
  `estimated_parts_cost` decimal(10,2) DEFAULT 0.00 COMMENT 'Estimated parts cost',
  `actual_labor_cost` decimal(10,2) DEFAULT 0.00 COMMENT 'Actual labor cost after completion',
  `actual_parts_cost` decimal(10,2) DEFAULT 0.00 COMMENT 'Actual parts cost after completion',
  `total_cost` decimal(10,2) DEFAULT 0.00 COMMENT 'Total job cost',
  `actual_duration` int(11) DEFAULT NULL COMMENT 'Actual duration in minutes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_orders`
--

INSERT INTO `job_orders` (`id`, `job_order_number`, `station_id`, `user_id`, `customer_id`, `vehicle_plate`, `vehicle_type`, `service_category_id`, `assigned_mechanic_id`, `assigned_by`, `service_description`, `estimated_duration`, `status`, `notes`, `started_at`, `completed_at`, `created_at`, `updated_at`, `requires_approval`, `reviewed_by`, `reviewed_at`, `approved_by`, `approved_at`, `admin_remarks`, `estimated_labor_cost`, `estimated_parts_cost`, `actual_labor_cost`, `actual_parts_cost`, `total_cost`, `actual_duration`) VALUES
(9, 'JO-2026-02-09-0001', 226, NULL, 9, '12345', 'HONDA', 7, 16, 15, 'General Service', 60, 'Reviewed', 'gwapo', NULL, NULL, '2026-02-09 10:39:46', '2026-02-09 11:34:14', 0, 14, '2026-02-09 11:34:14', NULL, NULL, 'okiee kayow', 600.00, 800.00, 0.00, 0.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_order_parts`
--

CREATE TABLE `job_order_parts` (
  `id` int(11) NOT NULL,
  `job_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_used` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `labor_sessions`
--

CREATE TABLE `labor_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `labor_sessions`
--

INSERT INTO `labor_sessions` (`id`, `user_id`, `station_id`, `start_time`, `end_time`, `hours_worked`, `created_at`) VALUES
(1, 15, 226, '2026-02-09 09:47:50', NULL, 0.00, '2026-02-09 01:47:50');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `type` enum('earn','redeem') NOT NULL,
  `points` int(11) NOT NULL,
  `reference_id` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mechanics`
--

CREATE TABLE `mechanics` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `hire_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mechanics`
--

INSERT INTO `mechanics` (`id`, `station_id`, `full_name`, `specialization`, `contact_number`, `status`, `hire_date`, `created_at`) VALUES
(1, 1, 'Juan Dela Cruz', 'Engine Repair', '09123456789', 'active', NULL, '2026-02-07 12:14:22'),
(2, 1, 'Maria Santos', 'Transmission', '09123456790', 'active', NULL, '2026-02-07 12:14:22'),
(3, 1, 'Jose Reyes', 'Brake Service', '09123456791', 'active', NULL, '2026-02-07 12:14:22'),
(11, 226, 'Paolo Reyes', 'Engine Specialist', '0917-100-2001', 'active', NULL, '2026-02-09 10:34:14'),
(12, 226, 'Liza Cruz', 'Brake & Suspension', '0917-100-2002', 'active', NULL, '2026-02-09 10:34:14'),
(13, 226, 'Marco Dizon', 'Electrical & Diagnostics', '0917-100-2003', 'active', NULL, '2026-02-09 10:34:14'),
(14, 226, 'Ana Santos', 'Tire & Vulcanizing', '0917-100-2004', 'active', NULL, '2026-02-09 10:34:14'),
(16, 226, 'EDCHEL', NULL, NULL, 'active', NULL, '2026-02-09 10:39:46');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('success','warning','error','info') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('read','unread') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `type_id`, `category_id`, `cost`, `price`, `created_at`, `updated_at`) VALUES
(1, 'FUEL001', 'Gasoline Premium', 'Premium gasoline fuel', 1, 1, 45.00, 55.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(2, 'MERCH001', 'Engine Oil 5W-30', 'Synthetic engine oil 5W-30', 2, 4, 250.00, 350.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(3, 'SERVICE001', 'Oil Change Service', 'Complete oil change service', 3, 3, 0.00, 500.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(4, 'OLL-PAIL-18L', 'PAIL/18 Liters', '18L pail lubricant/oil', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(5, 'OLL-HD10', 'HD 10', 'Heavy duty engine oil HD 10', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(6, 'OLL-HD30', 'HD 30', 'Heavy duty engine oil HD 30', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(7, 'OLL-HD40', 'HD 40', 'Heavy duty engine oil HD 40', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(8, 'OLL-GEP90', 'GEP 90', 'Gear oil GEP 90', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(9, 'OLL-GEP140', 'GEP 140', 'Gear oil GEP 140', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(10, 'OLL-MP-GREASE', 'MP GREASE', 'Multipurpose grease', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(11, 'OLL-HYDROTUR', 'HYDROTUR', 'Hydraulic oil HYDROTUR', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(12, 'OLL-TREKKER', 'TREKKER', 'Engine oil TREKKER', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(13, 'OLL-GALLON-4L', 'GALLON / 4L', '4L gallon lubricant', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(14, 'OLL-TOURING', 'TOURING', 'Touring motorcycle oil', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(15, 'OLL-EXTRA', 'EXTRA', 'Extra grade lubricant', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(16, 'OLL-BLAZE-RF-FS', 'BLAZE RACING FS', 'Blaze Racing full synthetic', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(17, 'OLL-2T-AUTO-200', '2T AUTOLUBE (60/200ML)', '2-stroke autolube 200ml', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(18, 'OLL-2T-PB-200', '2T POWERBURN (60/200ML)', '2-stroke Powerburn 200ml', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(19, 'OLL-SPRINT-200', 'SPRINT 4T RIDER (60/200ML)', 'Sprint 4T rider oil 200ml', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(20, 'OLL-2T-AUTO-24', '2T AUTOLUBE (24/1)', '2-stroke autolube carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(21, 'OLL-2T-PB-24', '2T POWERBURN (24/1)', '2T Powerburn carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(22, 'OLL-REVX-FS-12', 'REV-X FS ALLTERRAIN (12/1)', 'REV-X all-terrain synthetic blend', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(23, 'OLL-REVX-FS-SB-12', 'REV-X FS ALLTERRAIN SYNTHETIC BLEND (12/1)', 'REV-X synthetic blend 12-pack', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(24, 'OLL-TOURING-12', 'TOURING (12/1)', 'Touring oil 12-pack', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(25, 'OLL-BLAZE-RS-SB-12', 'BLAZE RACING SYNTHETIC BLEND (12/1)', 'Blaze Racing synthetic blend', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(26, 'OLL-BLAZE-RS-SYN-12', 'BLAZE RACING SYNTHETIC (12/1)', 'Blaze Racing synthetic 12-pack', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(27, 'OLL-BLAZE-EX-12', 'BLAZE RACING EXTRA (12/1)', 'Blaze Racing extra grade', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(28, 'OLL-TREKKER-12', 'TREKKER (12/1)', 'Trekker 12-pack', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(29, 'OLL-REVX-4X-12', 'REV-X 4X (12/1)', 'REV-X 4X oil 12-pack', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(30, 'OLL-HD30-24', 'HD 30 (24/1)', 'HD30 bulk carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(31, 'OLL-HD40-24', 'HD 40 (24/1)', 'HD40 bulk carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(32, 'OLL-MO30-24', 'MO 30 (24/1)', 'Motor oil MO30 carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(33, 'OLL-MO40-24', 'MO 40 (24/1)', 'Motor oil MO40 carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(34, 'OLL-ATF-PREM-24', 'ATF PREMIUM (24/1)', 'Automatic transmission fluid - premium', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(35, 'OLL-ATF-HTP-24', 'ATF HTP (24/1)', 'High temperature ATF carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(36, 'OLL-GEP90-24', 'GEP 90 (24/1)', 'GEP90 gear oil carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(37, 'OLL-GEP140-24', 'GEP 140 (24/1)', 'GEP140 gear oil carton', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(38, 'OLL-SPRINT-12', 'SPRINT 4T RIDER (12/1)', 'Sprint 4T rider 12-pack', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(39, 'OLL-ENDURO-12', 'ENDURO (12/1)', 'Enduro oil 12-pack', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(40, 'OLL-MPGR-0_5', 'MP GREASE (0.5 KG)', 'Multipurpose grease 0.5kg', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(41, 'OLL-MPGR-2', 'MP GREASE (2 KG)', 'Multipurpose grease 2kg', 2, 4, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(42, 'ACC-OIL-SAVER-1L', 'OIL SAVER (1L)', 'Oil treatment additive 1L', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(43, 'ACC-WHIZ-425', 'OIL TREATMENT - WHIZ (425ML)', 'Engine oil treatment WHIZ 425ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(44, 'ACC-ENG-FLUSH-443', 'ENGINE FLUSH (443ML)', 'Engine flush cleaner 443ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(45, 'ACC-ENG-FLUSH-H-500', 'ENGINE FLUSH - HARDEX (500ML)', 'Hardex engine flush 500ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(46, 'ACC-WASHER-300', 'BLUE SPRAY WASHER FLUID (300ML)', 'Windshield washer spray 300ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(47, 'ACC-RAD-100', 'RADIATOR COOLANT (100ML)', 'Radiator coolant 100ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(48, 'ACC-RAD-500', 'RADIATOR COOLANT (500ML)', 'Radiator coolant 500ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(49, 'ACC-RAD-GR-1L', 'RADIATOR COOLANT (GREEN) (1L)', 'Green radiator coolant 1L', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(50, 'ACC-RAD-PK-1L', 'RADIATOR COOLANT (PINK) (1L)', 'Pink radiator coolant 1L', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(51, 'ACC-PEN-190', 'PETROMATE PENETRATING OIL (190ML)', 'Penetrating oil 190ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(52, 'ACC-PEN-450', 'PETROMATE PENETRATING OIL (450ML)', 'Penetrating oil 450ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(53, 'ACC-WD40-BIG', 'WD-40 (BIG)', 'WD-40 multipurpose lubricant large', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(54, 'ACC-WD40-SM', 'WD-40 (SMALL)', 'WD-40 multipurpose lubricant small', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(55, 'ACC-TIRE-BLK-BIG', 'TIRE BLACK (BIG)', 'Tire dressing black large', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(56, 'ACC-TIRE-BLK-SM', 'TIRE BLACK (SMALL)', 'Tire dressing black small', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(57, 'ACC-TW-PASTE', 'TURTLE WAX SOFT PASTE', 'Turtle Wax soft paste', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(58, 'ACC-TW-LIQ', 'TURTLE WAX LIQUID WAX', 'Turtle Wax liquid finish', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(59, 'ACC-LUBRITOP', 'LUBRITOP', 'Lubritop protectant', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(60, 'ACC-PB-150', 'POWER BOOSTER (150ML)', 'Power booster additive 150ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(61, 'ACC-CNS-SHAMPOO', 'CLEAN N\' SHINE SHAMPOO', 'Car shampoo Clean N\' Shine', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(62, 'ACC-VS1-SM', 'VS1 PROTECTOR (SMALL)', 'VS1 interior protector small', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(63, 'ACC-VS1-BIG', 'VS1 PROTECTOR (BIG)', 'VS1 interior protector large', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(64, 'ACC-AA-SM', 'ARMOR ALL (SMALL)', 'Armor All protectant small', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(65, 'ACC-AA-BIG', 'ARMOR ALL (BIG)', 'Armor All protectant large', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(66, 'ACC-STP-300', 'STP OIL TREATMENT (300ML)', 'STP oil treatment 300ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(67, 'ACC-GAS-SAVER', 'GAS SAVER', 'Fuel economy additive', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(68, 'ACC-NEO-SH', 'NEO SHALDAN', 'Air freshener - Neo Shaldan', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(69, 'ACC-TOPIAS', 'TOPIAS FRESHENER', 'Car freshener - Topias', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(70, 'ACC-LT', 'LITTLE TREES', 'Little Trees air freshener', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(71, 'ACC-CAL-SCENT', 'CALIFORNIA SCENT', 'California Scents air freshener', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(72, 'ACC-GLADE-SP', 'GLADE SPRAY', 'Glade spray air freshener', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(73, 'ACC-BF-900', 'BRAKE FLUID (900ML)', 'Brake fluid 900ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(74, 'ACC-BF-MED', 'BRAKE FLUID (MEDIUM)', 'Brake fluid medium', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(75, 'ACC-BF-SM', 'BRAKE FLUID (SMALL)', 'Brake fluid small', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(76, 'ACC-BC-H-400', 'BRAKE CLEANER - HARDEX (400ML)', 'Brake cleaner Hardex 400ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(77, 'ACC-BC', 'BRAKE CLEANER', 'Brake cleaner general', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(78, 'ACC-TVR', 'TIRE VALVE RUBBER', 'Rubber tire valve replacement', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(79, 'ACC-TVS', 'TIRE VALVE STEEL', 'Steel tire valve core', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(80, 'ACC-RZ-AEAL', 'RZ AUTO TIRE AEAL', 'Tire sealing compound', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(81, 'ACC-GASKET-MK', 'GASKET MAKER', 'Gasket maker adhesive', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(82, 'ACC-CHAMOIS', 'CHAMOIS', 'Chamois leather cloth', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(83, 'ACC-FLANELA', 'FLANELA', 'Cleaning flannel cloth', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(84, 'ACC-PATCH-11', 'PATCH # 11', 'Tire repair patch #11', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(85, 'ACC-PATCH-12', 'PATCH # 12', 'Tire repair patch #12', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(86, 'ACC-BRST-DBL', 'BACKREST DOUBLE', 'Double backrest cushion', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(87, 'ACC-BRST-SGL', 'BACKREST SINGLE', 'Single backrest cushion', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(88, 'ACC-WT-1_25', 'WHEEL WEIGHTS CLIP TYRE (1 1/4 OZ)', 'Clip-on wheel weight 1.25oz', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(89, 'ACC-WT-0_5', 'WHEEL WEIGHTS CLIP TYRE (1/2 OZ)', 'Clip-on wheel weight 0.5oz', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(90, 'ACC-WT-0_75', 'WHEEL WEIGHTS CLIP TYRE (3/4 OZ)', 'Clip-on wheel weight 0.75oz', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(91, 'ACC-WT-1IN', 'WHEEL WEIGHTS CLIP TYRE (1\")', 'Clip-on wheel weight 1 inch', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(92, 'ACC-WT-1_5', 'WHEEL WEIGHTS CLIP TYRE (1 1/2)', 'Clip-on wheel weight 1.5', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(93, 'ACC-WT-ADH', 'WHEEL WEIGHTS ADHESIVE', 'Adhesive wheel weights', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(94, 'ACC-MP1-MED', 'MP 1(MED) PATCH', 'Medium tire patch MP1', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(95, 'ACC-MP2-LG', 'MP2 (LARGE) PATCH', 'Large tire patch MP2', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(96, 'ACC-CT20', 'CT 20 RADIAL PATCH', 'Radial tire patch CT20', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(97, 'ACC-WW-16', 'WIPER WASH (16ML)', 'Wiper wash 16ml', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(98, 'ACC-CLUTCH-OIL', 'SELECON/CLUTCH OIL', 'Selecon clutch oil', 2, 5, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(199, 'FLT-SAK-F1508', 'SAKURA F1508', 'Sakura oil filter F1508', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(200, 'FLT-SAK-FC-1510', 'SAKURA FC - 1510', 'Sakura filter FC-1510', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(201, 'FLT-OF-SPK-95985730', 'OIL FILTER SPARK -95985730', 'Oil filter for Spark', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(202, 'FLT-FES-5342', 'FUEL FILTER FES 5342', 'Fuel filter FES 5342', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(203, 'FLT-94797406', 'FILTER 94797406', 'Generic filter 94797406', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(204, 'FLT-C-223', 'C- 223', 'Filter C-223', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(205, 'FLT-C-509A', 'C- 509A', 'Filter C-509A', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(206, 'FLT-C-510A', 'C- 510A', 'Filter C-510A', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(207, 'FLT-FC-322', 'FC- 322', 'Filter FC-322', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(208, 'FLT-DAI-581', 'OIL FILTER DAI - WA DU 581', 'Oil filter DAI-WA DU 581', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(209, 'FLT-O-1012-S', 'OIL FILTER O- 1012 S', 'Oil filter O-1012S', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(210, 'FLT-FUJ-5262313', 'FUJILITO 5262313', 'Fujilito filter 5262313', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(211, 'FLT-FUJ-5266016', 'FUJILITO 5266016', 'Fujilito filter 5266016', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(212, 'FLT-FUJ-5262311', 'FUJILITO 5262311', 'Fujilito filter 5262311', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(213, 'FLT-FUJ-5264870', 'FUJILITO 5264870', 'Fujilito filter 5264870', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(214, 'FLT-C-65400', 'OIL FILTER C- 65400', 'Oil filter C-65400', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(215, 'FLT-FESS-5715', 'FUEL FILTER FESS - 5715', 'Fuel filter FESS-5715', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(216, 'FLT-FESS-5714', 'FUEL FILTER FESS - 5714', 'Fuel filter FESS-5714', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(217, 'FLT-FESS-5708', 'FUEL FILTER FESS - 5708', 'Fuel filter FESS-5708', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(218, 'FLT-FFS-1501', 'FUEL FILTER FFS - 1501', 'Fuel filter FFS-1501', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(219, 'FLT-FFS-1478', 'FUEL FILTER FFS - 1478', 'Fuel filter FFS-1478', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(220, 'FLT-FC-017', 'FUEL FILTER FC - 017', 'Fuel filter FC-017', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(221, 'FLT-C-419', 'OIL FILTER C-419', 'Oil filter C-419', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(222, 'FLT-O-010', 'OIL FILTER O- 010', 'Oil filter O-010', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(223, 'FLT-NOM-NLT-060', 'NOMIS OIL FILTER NLT - 060', 'Nomis oil filter NLT-060', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(224, 'FLT-FES-5583', 'OIL FILTER FES - 5583', 'Oil filter FES-5583', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(225, 'FLT-C-117', 'OIL FILTER C-117', 'Oil filter C-117', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(226, 'FLT-VG-1560080012', 'VG 1560080012', 'VG filter 1560080012', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(227, 'FLT-HOWO-186-1012000', 'OIL FILTER 186-1012000 (HOWO)', 'HOWO oil filter 186-1012000', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(228, 'FLT-FC-326', 'FUEL FILTER FC - 326', 'Fuel filter FC-326', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(229, 'FLT-F-197', 'FUEL FILTER F- 197', 'Fuel filter F-197', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(230, 'FLT-C-525', 'OIL FILTER C - 525', 'Oil filter C-525', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(231, 'FLT-SAK-F-1111', 'SAKURA FUEL FILTER F-1111', 'Sakura fuel filter F-1111', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(232, 'FLT-FES-5617', 'OIL FILTER - FES 5617', 'Oil filter FES-5617', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(233, 'FLT-MC-0078', 'OIL FILTER - MC - 0078', 'Oil filter MC-0078', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(234, 'FLT-MC-0010', 'OIL FILTER - MC - 0010', 'Oil filter MC-0010', 2, 6, 0.00, 0.00, '2026-02-07 12:14:22', '2026-02-07 12:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Fuel Products', 'All fuel types and gasoline products', '2026-02-07 12:14:22'),
(2, 'Merchandise', 'Store merchandise and accessories', '2026-02-07 12:14:22'),
(3, 'Services', 'Vehicle services and maintenance', '2026-02-07 12:14:22'),
(4, 'Oils/Lubes/Grease', 'Motor oils, gear oils, greases', '2026-02-07 12:14:22'),
(5, 'Car Accessories', 'Car care and accessories', '2026-02-07 12:14:22'),
(6, 'Filters', 'Oil and fuel filters', '2026-02-07 12:14:22'),
(7, 'Drinks/Food', 'Beverages and convenience food', '2026-02-07 12:14:22'),
(8, 'Snacks', 'Packaged snack items', '2026-02-07 12:14:22'),
(9, 'VIC Filters', 'VIC brand filters and parts', '2026-02-07 12:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `product_types`
--

CREATE TABLE `product_types` (
  `id` int(11) NOT NULL,
  `name` enum('fuel','merch','service') NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_types`
--

INSERT INTO `product_types` (`id`, `name`, `description`) VALUES
(1, 'fuel', 'Fuel products'),
(2, 'merch', 'Merchandise products'),
(3, 'service', 'Service products');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `station_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('Pending','Confirmed','Received','Cancelled') DEFAULT 'Pending',
  `expected_delivery_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `quantity_received` int(11) DEFAULT 0,
  `received_at` datetime DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports_cache`
--

CREATE TABLE `reports_cache` (
  `id` int(11) NOT NULL,
  `report_type` varchar(50) NOT NULL COMMENT 'daily, shift_am, shift_pm',
  `station_id` int(11) DEFAULT NULL,
  `report_date` date NOT NULL,
  `report_time` time NOT NULL,
  `data` longtext DEFAULT NULL COMMENT 'JSON report data',
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` varchar(64) NOT NULL,
  `sale_date` date NOT NULL,
  `sale_time` time NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `station_id` int(11) DEFAULT NULL,
  `payment_method` varchar(32) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `amount_received` decimal(12,2) DEFAULT 0.00,
  `change_amount` decimal(12,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Completed',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` varchar(64) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `default_parts_cost` decimal(10,2) DEFAULT 0.00,
  `default_labor_cost` decimal(10,2) DEFAULT 0.00,
  `default_duration` int(11) DEFAULT 60,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `description`, `default_parts_cost`, `default_labor_cost`, `default_duration`, `is_active`, `created_at`) VALUES
(1, 'Oil Change', 'Complete oil and filter change', 500.00, 300.00, 45, 1, '2026-02-07 12:14:22'),
(2, 'Tire Rotation', 'Rotate all four tires', 0.00, 400.00, 30, 1, '2026-02-07 12:14:22'),
(3, 'Car Wash', 'Exterior and interior cleaning', 0.00, 250.00, 60, 1, '2026-02-07 12:14:22'),
(4, 'Brake Service', 'Brake pad replacement and service', 1500.00, 800.00, 120, 1, '2026-02-07 12:14:22'),
(5, 'Engine Tune-up', 'Spark plugs, filters, diagnostics', 1200.00, 1000.00, 90, 1, '2026-02-07 12:14:22'),
(6, 'Battery Replacement', 'Remove old battery, install new', 1800.00, 200.00, 30, 1, '2026-02-07 12:14:22'),
(7, 'AC Service', 'AC cleaning and refrigerant recharge', 800.00, 600.00, 60, 1, '2026-02-07 12:14:22'),
(8, 'Other', 'Custom service request', 0.00, 0.00, 60, 1, '2026-02-07 12:14:22'),
(9, 'Change Oil', 'Engine oil change and filter replacement', 500.00, 200.00, 30, 1, '2026-02-09 10:08:09'),
(10, 'Vulcanizing', 'Tire repair and patching services', 200.00, 150.00, 20, 1, '2026-02-09 10:08:09'),
(11, 'Battery Check', 'Battery testing and replacement if needed', 1500.00, 150.00, 20, 1, '2026-02-09 10:08:09'),
(12, 'Air Filter Replacement', 'Air filter inspection and replacement', 300.00, 100.00, 15, 1, '2026-02-09 10:08:09'),
(13, 'Wheel Alignment', 'Wheel alignment and balancing', 500.00, 400.00, 60, 1, '2026-02-09 10:08:09'),
(14, 'Transmission Service', 'Transmission fluid change and inspection', 1800.00, 1000.00, 90, 1, '2026-02-09 10:08:09'),
(15, 'General Inspection', 'Complete vehicle safety inspection', 0.00, 300.00, 45, 1, '2026-02-09 10:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `service_entries`
--

CREATE TABLE `service_entries` (
  `id` int(11) NOT NULL,
  `service_number` varchar(50) NOT NULL,
  `station_id` int(11) NOT NULL,
  `service_category_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `service_description` text NOT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `mechanic_id` int(11) DEFAULT NULL,
  `parts_cost` decimal(10,2) DEFAULT 0.00,
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `estimated_duration` int(11) DEFAULT 60,
  `status` enum('Pending','In Progress','Completed','Verified','finalized','Cancelled') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `finalized_by` int(11) DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_parts_used`
--

CREATE TABLE `service_parts_used` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_rates`
--

CREATE TABLE `service_rates` (
  `id` int(11) NOT NULL,
  `service_category_id` int(11) NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `rate_name` varchar(100) NOT NULL,
  `flat_rate` decimal(10,2) NOT NULL,
  `estimated_duration` int(11) DEFAULT 60,
  `is_active` tinyint(1) DEFAULT 1,
  `effective_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_rates`
--

INSERT INTO `service_rates` (`id`, `service_category_id`, `station_id`, `rate_name`, `flat_rate`, `estimated_duration`, `is_active`, `effective_date`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Oil Change - Standard', 500.00, 45, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(2, 1, NULL, 'Oil Change - Premium', 800.00, 60, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(3, 2, NULL, 'Tire Rotation', 400.00, 30, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(4, 3, NULL, 'Car Wash - Basic', 250.00, 60, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(5, 3, NULL, 'Car Wash - Premium', 500.00, 90, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(6, 4, NULL, 'Brake Service - Front', 1500.00, 120, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(7, 4, NULL, 'Brake Service - Full', 2500.00, 180, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(8, 5, NULL, 'Engine Tune-up', 1200.00, 90, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(9, 6, NULL, 'Battery Replacement', 1800.00, 30, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(10, 7, NULL, 'AC Service', 800.00, 60, 1, NULL, '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(11, 1, NULL, 'Oil Change - Standard', 500.00, 45, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(12, 1, NULL, 'Oil Change - Premium', 800.00, 60, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(13, 2, NULL, 'Tire Rotation', 400.00, 30, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(14, 3, NULL, 'Car Wash - Basic', 250.00, 60, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(15, 3, NULL, 'Car Wash - Premium', 500.00, 90, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(16, 4, NULL, 'Brake Service - Front', 1500.00, 120, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(17, 4, NULL, 'Brake Service - Full', 2500.00, 180, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(18, 5, NULL, 'Engine Tune-up', 1200.00, 90, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(19, 6, NULL, 'Battery Replacement', 1800.00, 30, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23'),
(20, 7, NULL, 'AC Service', 800.00, 60, 1, NULL, '2026-02-07 12:14:23', '2026-02-07 12:14:23');

-- --------------------------------------------------------

--
-- Table structure for table `shift_reports`
--

CREATE TABLE `shift_reports` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `shift` enum('AM','PM','Night') NOT NULL DEFAULT 'AM',
  `status` enum('open','pending_verification','finalized') NOT NULL DEFAULT 'open',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `finalized_by` int(11) DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `manager_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stations`
--

CREATE TABLE `stations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stations`
--

INSERT INTO `stations` (`id`, `name`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PETRON CDO -Kauswagan', 'CDO', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(2, 'DAANG MAHARLIKA HI-WAY, PINAGBARILA, SANTO CRISTO, BALIUAG, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(3, 'BALAGTAS BMA RD., POBLACION, SAN RAFAEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(4, 'CAGAYAN VALLEY HIGHWAY, BAGONG SILANG, SAN MIGUEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(5, 'PLARIDEL - PULILAN DIVERSION RD., SANTO CRISTO, PULILAN, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(6, 'RIZAL ST., SAN JOSE, BALIUAG, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(7, 'DRT HIGHWAY, ULINGAO, SAN RAFAEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(8, 'GEN. ALEJO G. SANTOS HIGHWAY, PARULAN, PLARIDEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(9, 'M. VALTE RD., PINAOD, SAN ILDEFONSO, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(10, 'MAHARLIKA HIGHWAY GALAS MAASIM, MAASIM, SAN RAFAEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(11, 'NATIONAL HIGHWAY, CRUZ NA DAAN, SAN RAFAEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(12, 'LAZARO ST., BATIA, BOCAUE, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(13, 'PLARIDEL BYPASS RD., MALAMIG, BUSTOS, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(14, 'GENERAL ALEJO SANTOS ROAD , DONACION, ANGAT, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(15, 'VILLARARIT ST. POBLACION, POBLACION, NORZAGARAY, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(16, 'BYPASS RD., SANTA CLARA, SANTA MARIA, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(17, 'NEW BYPASS ROAD, BAGBAGUIN, SANTA MARIA, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(18, 'PUROK 1, TANAWAN, BUSTOS, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(19, 'LANDICHO ST. , BALASING, SANTA MARIA, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(20, 'LUCERO STREET, BAGONG BAYAN, CITY OF MALOLOS , BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(21, 'MALHAKAN ROAD, MALHACAN, CITY OF MEYCAUAYAN, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(22, 'MAIN STREET, SAN JUAN, CITY OF MALOLOS , BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(23, 'MC ARTHUR HIWAY, WAWA (POB.), BALAGTAS, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(24, 'PUROK 1 MABINI STREET, SANTISIMA TRINIDAD, CITY OF MALOLOS , BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(25, 'BO. TURO, BIÑANG 2ND, BOCAUE, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(26, 'BUNSURAN II, CUTCUT, GUIGUINTO, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(27, 'MC ARTHUR HI WAY, CALUMPANG, CALUMPIT, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(28, 'MCARTHUR HIGHWAY, POBLACION, GUIGUINTO, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(29, 'PAN PHILIPPINE HIGHWAY, BANGA I, PLARIDEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(30, 'J. GARCIA, POBLACION, PLARIDEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(31, 'MCARTHUR HIGHWAY, DAKILA, CITY OF MALOLOS , BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(32, 'MC ARTHUR HIWAY, BANGA, CITY OF MEYCAUAYAN, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(33, 'MCARTHUR HIGHWAY, GUINHAWA, CITY OF MALOLOS , BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(34, 'DIVERSION ROAD, BULIHAN, CITY OF MALOLOS , BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(35, 'MCARTHUR HIGHWAY , BUNLO, BOCAUE, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(36, 'NATIONAL ROAD CORNER METRO GATE 2, BAHAY PARE, CITY OF MEYCAUAYAN, BULACAN NCR BULACAN SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(37, 'IGAY ROAD, SANTO CRISTO, CITY OF SAN JOSE DEL MONTE, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(38, 'ACASIA ST., TUNGKONG MANGGA, CITY OF SAN JOSE DEL MONTE, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(39, 'QUIRINO HIGHWAY, TUNGKONG MANGGA, CITY OF SAN JOSE DEL MONTE, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(40, 'QUIRINO HIGHWAY, FRANCISCO HOMES-MULAWIN, CITY OF SAN JOSE DEL MONTE, BULACAN NCR BULACAN SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(41, 'GROTTO, GRACEVILLE, CITY OF SAN JOSE DEL MONTE, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(42, 'NORTH LUZON SOUTH BOUND, TAAL, BOCAUE, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(43, 'KM 23 NLEX, LIAS, MARILAO, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(44, 'KM 42 NLEX NORTHBOUND LANE, SANTO NIÑO, PLARIDEL, BULACAN NCR BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(45, 'DEPARO ROAD, BARANGAY 168, CITY OF CALOOCAN, NCR, (THIRD DISTRICT) NCR CALOOCA CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(46, 'GEN. SAN MIGUEL ST., BARANGAY 4, CITY OF CALOOCAN, NCR, (THIRD DISTRICT) NCR CALOOCA CITY SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(47, 'C-3 COR.ROAD TAWILIS , BARANGAY 22, CITY OF CALOOCAN, NCR, (THIRD DISTRICT) NCR CALOOCA CITY SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(48, 'A. MABINI ST., BARANGAY 5, CITY OF CALOOCAN, NCR, (THIRD DISTRICT) NCR CALOOCA CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(49, 'TULLAHAN ROAD QUIRINO CORNER STA QUITERIA, BARANGAY 162, CITY OF CALOOCAN, NCR, (THIRD DISTRICT) NCR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(50, 'REAL ST., ZAPOTE, CITY OF LAS PIÑAS, NCR NCR LAS PIÑAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(51, 'CRM AVENUE, ALMANZA DOS, CITY OF LAS PIÑAS, NCR, FOURTH DISTRICT NCR LAS PIÑAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(52, 'REAL ST., PAMPLONA TRES, CITY OF LAS PIÑAS, NCR NCR LAS PIÑAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(53, 'MARCOS ALVAREZ AVE., TALON SINGKO, CITY OF LAS PIÑAS, NCR NCR LAS PIÑAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(54, 'ALABANG ZAPOTE ROAD, TALON TRES, CITY OF LAS PIÑAS, NCR, FOURTH DISTRICT NCR LAS PIÑAS SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(55, 'LOT 2A DAANG HARI CORNER DAANG REYNA, ALMANZA DOS, CITY OF LAS PIÑAS, NCR, FOURTH DISTRICT NCR LAS P', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(56, 'J. AGUILAR AVENUE, TALON TRES, CITY OF LAS PIÑAS, NCR, FOURTH DISTRICT NCR LAS PIÑAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(57, 'ZAPOTE-ALABANG RD. FIRST METROGAS, PAMPLONA UNO, CITY OF LAS PIÑAS, NCR, FOURTH DISTRICT NCR LAS PIÑ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(58, 'C5 EXT. COR. S. MARQUEZ ST., MANUYO UNO, CITY OF LAS PIÑAS, NCR, FOURTH DISTRICT NCR LAS PIÑAS SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(59, 'C5 EXT. COR. VILLASEAL ST., MANUYO UNO, CITY OF LAS PIÑAS, NCR, FOURTH DISTRICT NCR LAS PIÑAS SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(60, 'ALABANG ZAPOTE RD., PAMPLONA UNO, CITY OF LAS PIÑAS, NCR, FOURTH DISTRICT NCR LAS PIÑAS SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(61, 'ZOBEL ROXAS AVE. COR. DIAN ST., PALANAN, CITY OF MAKATI, NCR NCR MAKATI CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(62, 'KAMAGONG COR. V. CRUZ EXT., SAN ANTONIO, CITY OF MAKATI, NCR NCR MAKATI CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(63, 'PABLO OCAMPO ST. COR. ZAPOTE RD., SANTA CRUZ, CITY OF MAKATI, NCR, FOURTH DISTRICT NCR MAKATI CITY S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(64, 'G PUYAT COR P TAMO AVE, SAN ANTONIO, CITY OF MAKATI, NCR, FOURTH DISTRICT NCR MAKATI CITY SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(65, 'SEN. GIL PUYAT AVE. COR MAKATI, BEL-AIR, CITY OF MAKATI, NCR NCR MAKATI CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(66, 'EDSA CORNER ARNAIZ AVE., DASMARIÑAS, CITY OF MAKATI, NCR NCR MAKATI CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(67, 'OSMENA HIGHWAY COR. CALHOUN ST., PIO DEL PILAR, CITY OF MAKATI, NCR NCR MAKATI CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(68, 'EDSA COR. DANLIG ST. COR IRAN ST., PINAGKAISAHAN, CITY OF MAKATI, NCR, FOURTH DISTRICT NCR MAKATI CI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(69, 'GIL PUYAT AVE. NEAR COR. DIAN, SAN ISIDRO, CITY OF MAKATI, NCR NCR MAKATI CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(70, 'BONI AVE. COR MAYSILO ST., PLAINVIEW, CITY OF MANDALUYONG, NCR, (SECOND DISTRICT) NCR MAKATI CITY SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(71, 'LOT 18 BLOCK 76 SEN. GIL PUYAT AVE., PALANAN, CITY OF MAKATI, NCR NCR MAKATI CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(72, 'EVANGELISTA COR GEN. ARGUELLES, PIO DEL PILAR, CITY OF MAKATI, NCR NCR MAKATI CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(73, 'KAMAGONG AND METROPOLITAN AVE COR. EPIFANIO DE LOS SANTOS AVE., SAN ANTONIO, CITY OF MAKATI, NCR, FO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(74, 'GOV PASCUAL AVE, POTRERO, CITY OF MALABON, NCR, (THIRD DISTRICT) NCR MALABON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(75, 'MCARTHUR HIGHWAY COR ANONAS ROAD, POTRERO, CITY OF MALABON, NCR, (THIRD DISTRICT) NCR MALABON SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(76, 'GEN. LUNA COR SACRISTIA ST, SAN AGUSTIN, CITY OF MALABON, NCR, (THIRD DISTRICT) NCR MALABON SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(77, 'M.H. DEL PILAR, MAYSILO, CITY OF MALABON, NCR, (THIRD DISTRICT) NCR MALABON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(78, 'C4 ROAD DAGAT-DAGATAN, LONGOS, CITY OF MALABON, NCR, (THIRD DISTRICT) NCR MALABON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(79, 'GOV. PASCUAL AVE., CATMON, CITY OF MALABON, NCR, (THIRD DISTRICT) NCR MALABON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(80, 'EDSA AVE. CORNER CONNECTICUT ST., WACK-WACK GREENHILLS, CITY OF MANDALUYONG, NCR, (SECOND DISTRICT) ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(81, 'SMC COMPOUND SAN MIGUEL AVENUE, WACK-WACK GREENHILLS, CITY OF MANDALUYONG, NCR, (SECOND DISTRICT) NC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(82, 'BARANGKA DRIVE COR MA CLARA ST., BARANGKA DRIVE, CITY OF MANDALUYONG, NCR, (SECOND DISTRICT) NCR MAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(83, 'SHAW BLVD. CORNER OLD WACK-WACK, WACK-WACK GREENHILLS, CITY OF MANDALUYONG, NCR NCR MANDALUYONG CITY', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(84, 'SIERRA MADRE ST. EDSA AVE., HIGHWAY HILLS, CITY OF MANDALUYONG, NCR, (SECOND DISTRICT) NCR MANDALUYO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(85, 'ST. FRANCIS AVE. WACK WACK GREEN, WACK-WACK GREENHILLS, CITY OF MANDALUYONG, NCR, (SECOND DISTRICT) ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(86, 'R MAGSAYSAY BLVD. COR. ALTURA ST., BARANGAY 581, SAMPALOC, NCR, CITY OF MANILA NCR MANILA SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(87, 'OLD STA. MESAST., BARANGAY 591, SAMPALOC, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(88, 'RIZAL AVENUE EXT, BARANGAY 196, TONDO I/II, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(89, 'C.M. RECTO AVENUE CORNER DELPAN, BARANGAY 275, SAN NICOLAS, NCR, CITY OF MANILA, (FRIST DISTRICT) NC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(90, 'RADIAL ROAD 10, BARANGAY 129, TONDO I/II, NCR, CITY OF MANILA NCR MANILA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(91, 'HONORIO LOPEZ ST. COR JUAN LUNA ST., BARANGAY 148, TONDO I/II, NCR, CITY OF MANILA, (FRIST DISTRICT)', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(92, 'ESPAÑA COR IBARRA AND SISA STS., BARANGAY 526, SAMPALOC, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR M', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(93, 'RIZAL AVE. COR. MALABON, BARANGAY 336, SANTA CRUZ, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(94, 'M. DELA FUENTE COR. G. TUAZON, BARANGAY 417, SAMPALOC, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(95, 'J. LUNA ST. COR. SANDE, BARANGAY 61, TONDO I/II, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(96, 'PAZ GUAZON CORNER CALLE SANCIANCO ST., BARANGAY 829, PACO, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(97, 'H. LOPEZ COR. KAUNLARAN, BARANGAY 124, TONDO I/II, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(98, 'DIMASALANG COR TIAGO, BARANGAY 368, SANTA CRUZ, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(99, 'PRES. QUIRINO AVE., BARANGAY 832, PACO, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(100, 'PRES. SERGIO OSMENA HIGHWAY, BARANGAY 747, SANTA ANA, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(101, 'UNITED NATIONS AVE. COR ROMUALDEZ ST., BARANGAY 674, PACO, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(102, 'JOSE ABAD SANTOS AVE., BARANGAY 206, TONDO I/II, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(103, 'CLARO M. RECTO COR. ASUNCION STS., BARANGAY 270, SAN NICOLAS, NCR, CITY OF MANILA, (FRIST DISTRICT) ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(104, 'RIZAL AVE. CORNER PAMPANGA ST., BARANGAY 382, SANTA CRUZ, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(105, 'V. MAPA COR. 1ST ST., BARANGAY 601, SAMPALOC, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(106, 'TANDUAY CORNER ARLEGUI STS., BARANGAY 386, QUIAPO, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(107, 'JESUS COR. PALUM PONG, BARANGAY 834, PANDACAN, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(108, 'A.H. LACSON AVENUE, BARANGAY 343, SANTA CRUZ, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(109, 'PEDRO GIL COR TAFT AVENUE, BARANGAY 696, MALATE, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(110, 'BONIFACIO DRIVE COR ADUAN ST., BARANGAY 666, ERMITA, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANIL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(111, 'SAN MARCELINO ST., BARANGAY 676, PACO, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MANILA SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(112, 'MADRID CORNER SAN FERNANDO STS., BARANGAY 284, SAN NICOLAS, NCR, CITY OF MANILA, (FRIST DISTRICT) NC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(113, 'PEDRO GIL CORNER TEJERON STS., BARANGAY 877, SANTA ANA, NCR, CITY OF MANILA, (FRIST DISTRICT) NCR MA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(114, 'TEJERON COR. SYQUIA STS., BARANGAY 874, SANTA ANA, NCR, CITY OF MANILA NCR MANILA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(115, 'F.B. HARRISON CORNER PABLO OCAMPO ST., BARANGAY 719, MALATE, NCR, CITY OF MANILA NCR MANILA SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(116, 'GIL FERNANDO AVE., SAN ROQUE, CITY OF MARIKINA, NCR, (SECOND DISTRICT) NCR MARIKINA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(117, 'G. FERNANDO AVE., SANTO NIÑO, CITY OF MARIKINA, NCR, (SECOND DISTRICT) NCR MARIKINA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(118, 'GIL FERNANDO ST. COR SUMULONG HIWAY, SANTO NIÑO, CITY OF MARIKINA, NCR, (SECOND DISTRICT) NCR MARIKI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(119, 'GEN. MOLINA ST., PARANG, CITY OF MARIKINA, NCR, (SECOND DISTRICT) NCR MARIKINA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(120, 'BAYAN BAYANAN AVENUE, CONCEPCION UNO, CITY OF MARIKINA, NCR, (SECOND DISTRICT) NCR MARIKINA SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(121, 'JP RIZAL ST COR. BAYAN-BAYANAN, CONCEPCION UNO, CITY OF MARIKINA, NCR, (SECOND DISTRICT) NCR MARIKIN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(122, 'JP RIZAL AVE. COR. SPAIN ST., CONCEPCION UNO, CITY OF MARIKINA, NCR, (SECOND DISTRICT) NCR MARIKINA ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(123, 'EAST SERVICE ROAD, ALABANG, CITY OF MUNTINLUPA, NCR NCR MUNTINLUPA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(124, 'NATIONAL ROAD, TUNASAN, CITY OF MUNTINLUPA, NCR NCR MUNTINLUPA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(125, 'PUROK 5 M.L. QUEZON, SUCAT, CITY OF MUNTINLUPA, NCR, FOURTH DISTRICT NCR MUNTINLUPA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(126, 'PACIFIC RIM CORNER COMMERCE AVENUE, ALABANG, CITY OF MUNTINLUPA, NCR, FOURTH DISTRICT NCR MUNTINLUPA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(127, 'KM 70 NATIONAL ROAD, TUNASAN, CITY OF MUNTINLUPA, NCR NCR MUNTINLUPA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(128, 'NATIONAL ROAD, ALABANG, CITY OF MUNTINLUPA, NCR NCR MUNTINLUPA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(129, 'INDUSTRIAL BUILDING I LAPU-LAPU STREET, NORTH BAY BOULEVARD NORTH, CITY OF NAVOTAS, NCR, (THIRD DIST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(130, 'NINOY AQUINO AVE., SAN DIONISIO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(131, 'KM 15 WEST SERVICE RD., SUN VALLEY, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(132, 'QUIRINO AVE., TAMBO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(133, 'C5 EXTENSION, BRGY. MOONWALK, PARAÑAQUE CITY, METRO MANILA NCR PARAÑAQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(134, 'DR. A. SANTOS AVE., SAN ANTONIO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(135, 'SMPI CMPD DR. A. SANTOS. AVE., B. F. HOMES, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(136, 'DR. A. SANTOS AVE., SAN ISIDRO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(137, 'DR. A. SANTOS AVE., SAN DIONISIO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(138, 'BLK8 DOÑA SOLEDAD BETTER LIVING, DON BOSCO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(139, 'QUIRINO AVE CORNER KABIHASNAN, SAN DIONISIO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(140, 'DOMESTIC ROAD COR. AIRPORT ROAD, SANTO NIÑO, CITY OF PARAÑAQUE, NCR NCR PARAÑAQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(141, 'LOPES AVE, SAN ISIDRO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(142, 'D. MACAPAGAL/NORTH PERIMETER RD., TAMBO, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(143, 'IBA COR NARRA ST UNITED HILLS VILLAGE, SAN MARTIN DE PORRES, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(144, 'KM 18 EAST SERVICE ROAD, SAN MARTIN DE PORRES, CITY OF PARAÑAQUE, NCR, FOURTH DISTRICT NCR PARAÑAQUE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(145, 'KM21, WEST SERVICE ROAD COR. CIELIT, CUPANG, MUNTINLUPA, NCR NCR PARAÑAQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(146, 'F.B. HARRISON COR. CUNETA STS., BARANGAY 75, PASAY CITY, NCR, FOURTH DISTRICT NCR PASAY SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(147, 'SOUTHBOUND EDSA, BARANGAY 144, PASAY CITY, NCR, FOURTH DISTRICT NCR PASAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(148, 'FB HARIZON ST., BARANGAY 13, PASAY CITY, NCR, FOURTH DISTRICT NCR PASAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(149, 'F.B. HARRISON CORNER SAN JUAN, BARANGAY 21, PASAY CITY, NCR, FOURTH DISTRICT NCR PASAY SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(150, 'MIA ROAD NAIA COMPLEX DOMESTIC ROAD, BARANGAY 191, PASAY CITY, NCR NCR PASAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(151, 'DIOSDADO MACAPAGAL CORNER EDSA EXT, BARANGAY 76, PASAY CITY, NCR, FOURTH DISTRICT NCR PASAY SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(152, 'METROPOLITAN PARK ROXAS BLVD., BARANGAY 76, PASAY CITY, NCR, FOURTH DISTRICT NCR PASAY SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(153, 'DONA AURORA VILLAGE MARCOS HIGHWAY, SANTOLAN, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(154, 'AMANG RODRIGUEZ AVE., MANGGAHAN, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(155, 'MARCOS HIGHWAY, DELA PAZ, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(156, 'CANLEY ROAD COR KAMAGONG ST, BAGONG ILOG, CITY OF PASIG, NCR NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(157, 'MERALCO AVE. B, SAN ANTONIO, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(158, 'EUSEBIO AVENUE EUSEBIO ST, MAYBUNGA, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(159, 'ORTIGAS AVE. EXTENSION, ROSARIO, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(160, 'C. RAYMUNDO, CANIOGAN, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(161, 'M. CONCEPCION ST., SAN JOAQUIN, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(162, 'SHAW BOULEVARD, ORANBO, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(163, 'C. RAYMUNDO AVENUE, MAYBUNGA, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(164, 'ORTIGAS AVENUE EXT., SANTO NIÑO, CAINTA, RIZAL NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(165, 'F.P. FELIX AVE., DELA PAZ, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(166, 'PASIG BLVD COR SAN IGNACIO ST., PINEDA, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(167, 'E. RODRIGUEZ AVE., UGONG, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(168, 'C. RAYMUNDO AVE. GREENLAND , ROSARIO, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(169, 'MERCEDES AVENUE COR. MARKET AVE., CANIOGAN, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(170, 'SHAW BLVD. COR. CAPITOL DRIVE, KAPITOLYO, CITY OF PASIG, NCR, (SECOND DISTRICT) NCR PASIG CITY SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(171, 'M. ALMEDA COR. BAGONG KALSADA, MARTIRES DEL 96, PATEROS, NCR, FOURTH DISTRICT NCR PATEROS SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(172, 'REGALADO AVE., GREATER LAGRO, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(173, 'QUIRINO HIGHWAY, KALIGAYAHAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(174, 'REGALADO AVE., NORTH FAIRVIEW, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(175, 'NORTH FAIRVIEW COMMONWEALTH AVE., GREATER LAGRO, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(176, 'GEN. LUIS CORNER AMBROSIA ST., NAGKAISANG NAYON, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(177, 'KM. 21 QUIRINO HIGHWAY, GREATER LAGRO, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(178, 'OLD ZABARTE ROAD, KALIGAYAHAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(179, 'QUIRINO HIGHWAY, BAGBAG, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(180, 'MINDANAO AVE. EXT., TALIPAPA, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(181, 'MINDANAO AVENUE, PROJECT 6, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(182, 'CONGRESSINAL AVENUE CORNER APRIL ST, BAHAY TORO, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(183, 'QUIRINO HIWAY COR. TANDANG SORA, SANGANDAAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(184, 'BATASAN SAN MATEO ROAD, BATASAN HILLS, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(185, 'PAYATAS ROAD, BRGY. COMMONWEALTH, QUEZON CITY NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(186, 'COMMONWEALTH AVE., FAIRVIEW, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(187, 'KAMUNING ROAD CORNER KALAYAAN, MALAYA, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(188, 'CENTRAL AVENUE , NEW ERA, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(189, 'REGALADO AVE. COR. PONTIAC ST., FAIRVIEW, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(190, 'EAST AVENUE CORNER NIA ROAD, PINYAHAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(191, 'TANDANG SORA AVENUE, TANDANG SORA, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(192, 'EAST AVENUE, PINYAHAN, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(193, 'CONGERSSIONAL AVE COR VIRGINIA ST., BAHAY TORO, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(194, 'COMMONWEALTH AVENUE CORNER ARBORETUM, U.P. CAMPUS, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(195, 'DALHIA COR LILAC ST., FAIRVIEW, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(196, 'COMONWEALTH AV. COR. ATHERTHON ST., NORTH FAIRVIEW, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON C', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(197, 'PAYATAS ROAD, BAGONG SILANGAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(198, 'KALAYAAN AVE., CENTRAL, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(199, 'DON ANTONIO HTS. CORNER COMMONWEALT, HOLY SPIRIT, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CIT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(200, 'REGALADO AVENUE, FAIRVIEW, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(201, 'VISAYAS AVE. COR DANR ST. PROJECT 6, VASRA, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(202, 'COMMONWEALTH AVE COR ZUZUARREGI ST., MATANDANG BALARA, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(203, 'CONGRESSIONAL AVE EXT, PASONG TAMO, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(204, 'VISAYAS AVENUE CORNER CONGRESSIONAL AVENUE, BAHAY TORO, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(205, 'TANDANG SORA AVE., CULIAT, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(206, 'KATIPUNAN AVENUE CORNER MANGYAN ST., PANSOL, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(207, 'V.LUNA AVENUE CORNER MASIKAP, PINYAHAN, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(208, 'SGT ESGUERRA AVE COR TIMOG AVE, SOUTH TRIANGLE, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(209, 'WEST AVENUE CORNER DEL MONTE, NAYONG KANLURAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(210, 'G. ARANETA AVE, TATALON, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(211, 'DAPITAN COR DR. ALEJOS STS., SANTA TERESITA, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(212, 'QUEZON AVE. CORNER APO ST., SANTA TERESITA, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(213, 'MAYON COR. CALAMBA ST., SAN ISIDRO LABRADOR, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(214, 'A.BONIFACIO COR.DELMONTE AVE, SAN JOSE, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(215, 'KAMUNING RD COR. SCT. YBARDOLAZA, SACRED HEART, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(216, 'NICANOR ROXAS ST. CORNER HALCON, SANTA TERESITA, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(217, 'QUEZON AVE. ROXAS DISTRICT, SANTA CRUZ, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(218, 'AURORA BLVD CORNER BALETE DRIVE, MARIANA, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(219, 'KANLAON COR. LAON-LAANG ST., LOURDES, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(220, 'QUEZON AVE. COR. SMA AVE, TATALON, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(221, 'ROOSEVELT AVE. COR. DEL MONTE AVE., DAMAYAN, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(222, 'AURORA BLVD. COR. DONA JUANA RODRIGUEZ, MARIANA, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(223, 'G.ARANETA AVE., SANTO DOMINGO, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(224, 'TIMOG MORATO COR. TOMAS MORATO, LAGING HANDA, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(225, 'E RODRIGUEZ SR. AVE. COR. T, KRISTONG HARI, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(226, '1 UNANG HAKBANG ST. COR. BAYANI ST., SAN ISIDRO, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(227, 'A. BONIFACIO, PAG-IBIG SA NAYON, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(228, 'EDSA SOUTH TRIANGLE (SOUTHBOUND), SOUTH TRIANGLE, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CIT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(229, 'E. RODRIGUEZ STREET, TATALON, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(230, 'E. RODRIGUEZ SR. , IMMACULATE CONCEPCION, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(231, 'EDSA COR MAIN AVE, SOCORRO, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(232, 'BONI SERRANO COR 4TH AVE., BAGONG LIPUNAN NG CRAME, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON C', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(233, 'AURORA BLVD. CORNER KATIPUNAN AVENUE, LOYOLA HEIGHTS, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(234, 'E. RODRIGUEZ JR., BAGUMBAYAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(235, 'NO. 188 E. RODRIQUIEZ JR. AVENUE, BAGUMBAYAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(236, 'N. DOMINGO COR. P. TUAZON, KAUNLARAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(237, 'EDSA CORNER MAIN STREET, BAGONG LIPUNAN NG CRAME, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CIT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(238, 'KATIPUNAN COR. BONNY SERRANO AVE., BAYANIHAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(239, 'XAVIERVILLE AVE., LOYOLA HEIGHTS, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(240, 'AURORA BLVD. COR. LAUAN ST., DUYAN-DUYAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(241, 'DON MARIANO MARCOS HI-WAY , BAGONG SILANGAN, QUEZON CITY, NCR, (SECOND DISTRICT) NCR QUEZON CITY SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(242, 'GEN. LUNA ST., AMPID II, SAN MATEO, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(243, 'SUMULONG MEMORIAL CIRCUMFERENTIAL RD., SAN ISIDRO (POB.), CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(244, 'MALINTA STREET, SAN ROQUE (POB.), CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(245, 'NATIONAL RD., WAWA (POB.), PILILLA, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(246, 'NATIONAL ROAD, SIPSIPIN, JALA-JALA, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(247, 'P. OLIVEROS ST., SAN ROQUE (POB.), CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(248, 'GENERAL LUNA COR P OLIVEROS ST, DELA PAZ (POB.), CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(249, 'NATIONAL ROAD, SAN JOSE (POB.), MORONG, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(250, 'T. CLAUDIO ST., SAN PEDRO (POB.), MORONG, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(251, 'GP MARCOS HIGHWAY, SANTA CRUZ, CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(252, 'CIRCUMFERENTIAL ROAD, SAN ROQUE (POB.), CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(253, 'NATIONAL HIGHWAY, MABINI, BARAS, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(254, 'M.H. DEL PILAR ST. COR. RODRIGUEZ, KATIPUNAN-BAYAN (POB.), TANAY, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(255, 'SUMULONG HIGHWAY, MAYAMOT, CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(256, 'KASIGLAHAN VILLAGE, SAN JOSE, RODRIGUEZ, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(257, 'J.P. RIZAL ST. COR. DAANGHARI ST., MANGGAHAN, RODRIGUEZ, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(258, 'MANILA EAST ROAD, HULO (POB.), PILILLA, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(259, 'A MABINI ST., BURGOS, RODRIGUEZ, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(260, 'MARCOS HIGHWAY, BAGONG NAYON, CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(261, 'SUMULONG HIGHWAY, MAMBUGAN, CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(262, 'SUMULONG HIGHWAY, SANTA CRUZ, CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(263, 'J SUMULONG HIGHWAY, SAN GUILLERMO, MORONG, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(264, 'SUMULONG HIGHWAY , SAN ISIDRO, CAINTA, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(265, 'SUMULONG HIGHWAY, DELA PAZ (POB.), CITY OF ANTIPOLO , RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(266, 'PBWY COMMERCIAL CENTER EVANGELISTAST., SAN JUAN, TAYTAY, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(267, 'NATIONAL RD., TAYUMAN, BINANGONAN, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(268, 'RIZAL AVENUE CORNER PALUMBARIT ST., SAN ISIDRO, TAYTAY, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(269, 'FELIX AVENUE, SAN ISIDRO, CAINTA, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(270, 'KM 21 ORTIGAS AVE EXT , SAN ISIDRO, TAYTAY, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(271, 'ORTIGAS AVE EXT, SAN JUAN, CAINTA, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(272, 'QUEZON AVE., PAG-ASA, TAYUMAN, ANGONO, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(273, 'RI NATIONAL ROAD, LIBIS (POB.), BINANGONAN, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(274, 'MANILA EAST ROAD CORNER VELASQUES ST., MUZON, TAYTAY, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(275, 'KM. 20 ORTIGAS AVENUE EXTENSION, SANTO DOMINGO, CAINTA, RIZAL NCR RIZAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(276, 'N DOMINGO COR M PATERNO ST., CORAZON DE JESUS, CITY OF SAN JUAN, NCR, (SECOND DISTRICT) NCR SAN JUAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(277, 'VALENZUELA COR. F. BLUMENTRITT, BATIS, CITY OF SAN JUAN, NCR, (SECOND DISTRICT) NCR SAN JUAN CITY SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(278, 'N. DOMINGO CORNER SAN GABRIEL, SAN PERFECTO, CITY OF SAN JUAN, NCR, (SECOND DISTRICT) NCR SAN JUAN C', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(279, 'F BLUMENTRITT COR. SAN LUIS ST., TIBAGAN, CITY OF SAN JUAN, NCR, (SECOND DISTRICT) NCR SAN JUAN CITY', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(280, 'P. GUEVARRA COR. V. CRUZ, SANTA LUCIA, CITY OF SAN JUAN, NCR NCR SAN JUAN CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(281, 'ORTIGAS AVE. COR. SANTOLAN ST., GREENHILLS, CITY OF SAN JUAN, NCR, (SECOND DISTRICT) NCR SAN JUAN CI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(282, 'ORTIGAS AVENUE CORNER CONNECTICUT, GREENHILLS, SAN JUAN CITY NCR SAN JUAN CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(283, 'DR. NATIVIDAD ST., IBAYO-TIPAS, CITY OF TAGUIG, NCR, FOURTH DISTRICT NCR TAGUIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(284, 'C5 ROAD MCKINLEY HILL FORT BONIFACIO, FORT BONIFACIO, CITY OF TAGUIG, NCR, FOURTH DISTRICT NCR TAGUI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(285, 'PASONG TAMO EXT. BONIFACIO GLOBAL C, FORT BONIFACIO, CITY OF TAGUIG, NCR, FOURTH DISTRICT NCR TAGUIG', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(286, 'M.L. QUEZON, LOWER BICUTAN, CITY OF TAGUIG, NCR, FOURTH DISTRICT NCR TAGUIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(287, 'LEVI MARIANO AVE, USUSAN, CITY OF TAGUIG, NCR, FOURTH DISTRICT NCR TAGUIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(288, 'C5 ROAD, USUSAN, CITY OF TAGUIG, NCR NCR TAGUIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(289, 'MAYOR TANYAG AVE., SOUTH SIGNAL VILLAGE, CITY OF TAGUIG, NCR NCR TAGUIG CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(290, 'HULO ST., BIGNAY, CITY OF VALENZUELA, NCR, (THIRD DISTRICT) NCR VALENZUELA CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(291, 'PASEO DE BLAS ROAD, PASO DE BLAS, CITY OF VALENZUELA, NCR, (THIRD DISTRICT) NCR VALENZUELA CITY SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(292, 'MCARTHUR HI-WAY, MARULAS, CITY OF VALENZUELA, NCR, (THIRD DISTRICT) NCR VALENZUELA CITY SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(293, 'MAYSAN ROAD, MAYSAN, CITY OF VALENZUELA, NCR, (THIRD DISTRICT) NCR VALENZUELA CITY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(294, 'GOV. SANTIAGO MC.ARTHUR HIGHWAY, MALINTA, CITY OF VALENZUELA, NCR, (THIRD DISTRICT) NCR VALENZUELA C', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(295, 'J. P. RIZAL COR REPOSO STS., VALENZUELA, CITY OF MAKATI, NCR, FOURTH DISTRICT NCR VALENZUELA CITY SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(296, 'CABUGAO LUNE PUDTOL ROAD, QUIRINO, LUNA, APAYAO NORTH LUZON APAYAO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(297, 'NATIONAL RD. CORNER BATARA, IMELDA, PUDTOL, APAYAO NORTH LUZON APAYAO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(298, 'SAN ISIDRO SUR, QUIRINO, LUNA, APAYAO NORTH LUZON APAYAO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(299, 'PROVINCIAL ROAD, GLORIA STREET, BARANGAY II (POB.), BALER , AURORA NORTH LUZON AURORA SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(300, 'PROVINCIAL ROAD, BALER, DIMANPUDSO, MARIA AURORA, AURORA NORTH LUZON AURORA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(301, 'HARRISON ROAD, RIZAL MONUMENT AREA, CITY OF BAGUIO, BENGUET NORTH LUZON BAGUIO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(302, 'KM.4.5 GREEN VALLEY, MARCOS HIGHWAY, DONTOGAN, CITY OF BAGUIO, BENGUET NORTH LUZON BAGUIO SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(303, 'NAGUILLAN RD. FERGUSON R, QUEZON HILL PROPER, CITY OF BAGUIO, BENGUET NORTH LUZON BAGUIO SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(304, 'LEGARDA RD. CAMP PROPER, MRR-QUEEN OF PEACE, CITY OF BAGUIO, BENGUET NORTH LUZON BAGUIO SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(305, 'BOKAWKAN RD., GUISAD CENTRAL, CITY OF BAGUIO, BENGUET NORTH LUZON BAGUIO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(306, 'BAGUIO-BUA-ITOGON RD., GUMATDANG, CITY OF BAGUIO, BENGUET NORTH LUZON BAGUIO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(307, 'HALSEMA HIGHWAY, POBLACION, LA TRINIDAD , BENGUET NORTH LUZON BAGUIO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(308, 'GOV. J.J. LINAO, NATIONAL ROAD, NAGBALAYONG, MORONG, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(309, 'SBMA - MORONG RD., UN AVE., SABANG, MORONG, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(310, 'NATIONAL ROAD, ATILANO L. RICARDO, BAGAC, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(311, 'SAN RAMON, KATAASAN, DINALUPIHAN, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22');
INSERT INTO `stations` (`id`, `name`, `location`, `status`, `created_at`, `updated_at`) VALUES
(312, 'ARGONAUT HIGHWAY, SUBIC FREEPORT, MABAYO, MORONG, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(313, 'PROVINCIAL ROAD PUROK 2, SAN ROQUE DAU, LUBAO, PAMPANGA NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(314, 'ARELLANO AVENUE, PUKSUAN, ORANI, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(315, 'ROMAN SUPER HIGHWAY, ALA-ULI, PILAR, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(316, 'ROMAN HIGHWAY, PANDATUNG, HERMOSA, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(317, 'PROVINCIAL HIGHWAY, OMBOY, ABUCAY, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(318, 'OLD HIGHWAY, BURGOS-SOLIMAN (POB.), HERMOSA, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(319, 'OLD HIGHWAY, JUDGE ROMAN CRUZ SR., HERMOSA, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(320, 'NATIONAL ROAD, MABATANG, ABUCAY, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(321, 'ROMAN HIGHWAY, ALANGAN, LIMAY, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(322, 'NATIONAL HIGHWAY, COR. MINDANAO AVE., MALIGAYA, MARIVELES, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(323, 'ROMAN SUPER HIGHWAY, TUYO, CITY OF BALANGA , BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(324, 'J. P. RIZAL ST. , TALISAY, CITY OF BALANGA , BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(325, 'ROMAN SUPER HIGHWAY, MULAWIN ROAD, MULAWIN, ORANI, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(326, 'ROMAN HIGHWAY, BILOLO, ORION, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(327, 'NATIONAL ROAD, ALA-ULI, PILAR, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(328, 'ROMAN HIGHWAY, COR. TUNDOL, REFORMISTA, LIMAY, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(329, 'NATIONAL ROAD, IBABA (POB.), SAMAL, BATAAN NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(330, 'RIZAL ST;, SAN FERNANDO (POB.), VICTORIA, TARLAC NORTH LUZON BATAAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(331, 'PICO ROAD KM.5, PICO, LA TRINIDAD , BENGUET NORTH LUZON BENGUET SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(332, 'AMBUKLAO RD., BECKEL, LA TRINIDAD , BENGUET NORTH LUZON BENGUET SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(333, 'MAC ARTHUR HIGHWAY, SAN NICOLAS (POB.), MINALIN, PAMPANGA NORTH LUZON BULACAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(334, 'NATIONAL HIGHWAY, CENTRO SUR (POB.), CAMALANIUGAN, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(335, 'NATIONAL ROAD, CENTRO (POB.), SANTA ANA, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(336, 'NATIONAL ROAD, CASAMBALANGAN, SANTA ANA, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(337, 'PUROK PARAISO, BAUA, GONZAGA, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(338, 'NATIONAL HIGHWAY, BULO, ALLACAPAN, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(339, 'DUGO SAN VICENTE ROAD, NATIONAL RD., PATTAO, BUGUEY, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(340, 'DUGO SAN VICENTE ROAD, BULALA, CAMALANIUGAN, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(341, 'POBLACION ROAD, SANTA CRUZ, BALLESTEROS, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(342, 'GENERAL LUNA ST., NATIONAL RD., MACANAYA, APARRI, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(343, 'MAHARLIKA HIGHWAY, DUMPAO, IGUIG, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(344, 'MAHARLIKA HIGHWAY, TUPANG, ALCALA, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(345, 'MAHARLIKA HIGHWAY, PENGUE, LEONARDA, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(346, 'MAHARLIKA HIGHWAY, BRGY. BUNTUN, TUGUEGARAO CITY, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(347, 'NATIONAL ROAD, CAGGAY, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(348, 'BARRIO CAPATAN , LIBAG NORTE, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(349, 'MAHARLIKA HIGHWAY, SAN ROQUE ST., PENGUE, LEONARDA, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(350, 'NATIONAL HIGHWAY, PATA, TUAO, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(351, 'BAGAY ROAD, CARITAN CENTRO, CARITAN NORTE, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(352, 'MAHARLIKA HIGHWAY, CENTRO, AMULUNG, CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(353, 'BUNTUN HIGHWAY, BUNTUN, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(354, 'BONIFACIO ST, CENTRO 8 (POB.), TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(355, 'DIVERSION ROAD, CARITAN NORTE, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(356, 'TRAMO ROAD, BAGAY, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(357, 'MAHARLIKA HIGHWAY, CARIG, TUGUEGARAO CITY , CAGAYAN NORTH LUZON CAGAYAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(358, 'NATIONAL HIGHWAY, MABATOBATO, LAMUT, IFUGAO NORTH LUZON IFUGAO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(359, 'NATIONAL HIGHWAY, BGY. NO. 51-A, NANGALISAN EAST, CITY OF LAOAG , ILOCOS NORTE NORTH LUZON ILOCOS NO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(360, 'RIZAL ST., BGY. NO. 51-A, NANGALISAN EAST, CITY OF LAOAG , ILOCOS NORTE NORTH LUZON ILOCOS NORTE SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(361, 'NATIONAL HIGHWAY, PAN PHILIPPINE HIGHWAY, BANI, BACARRA, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(362, 'VALDEZ CENTER, BRGY. 2, SAN BALTAZAR (POB.), SAN NICOLAS, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(363, 'NATIONAL HIGHWAY, 20-A, GABUT NORTE, BADOC, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(364, 'NATIONAL HIGHWAY, PUSSUAC, SANTO DOMINGO, ILOCOS SUR NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(365, 'LAOAG BYPASS ROAD, BGY. NO. 55-C, VIRA, CITY OF LAOAG , ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(366, 'GEN. SEGUNDA AVE., BGY. NO. 55-B, SALET-BULANGON, CITY OF LAOAG , ILOCOS NORTE NORTH LUZON ILOCOS NO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(367, 'GEN. LUNA ST., COR. VILLANUEVA ST., BGY. NO. 16, SAN JACINTO (POB.), CITY OF LAOAG , ILOCOS NORTE NO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(368, 'SAN MIGUEL ST., BRGY. 10, SAN NICOLAS, SARRAT, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(369, 'MARCOS AVE. CABANGARAN, CABAGOAN, PAOAY, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(370, 'AGLIPAY RD., SANTA MARIA (POB.), VINTAR, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(371, 'NATIONAL HIGHWAY, ANAO (POB.), PIDDIG, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(372, 'NATIONAL HIGHWAY, ARUA-AY, PIDDIG, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(373, 'NATIONAL HIGHWAY, MADAMBA (POB.), DINGRAS, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(374, 'NATIONAL HIGHWAY, ABACA, BANGUI, ILOCOS NORTE NORTH LUZON ILOCOS NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(375, 'NATIONAL HIGHWAY, BANNUAR (POB.), SAN JUAN, ILOCOS SUR NORTH LUZON ILOCOS SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(376, 'NATIONAL HIGHWAY, SALAPASAP, CABUGAO, ILOCOS SUR NORTH LUZON ILOCOS SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(377, 'JOSE SINGSON ST., BARANGAY VIII, CITY OF VIGAN , ILOCOS SUR NORTH LUZON ILOCOS SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(378, 'NATIONAL HIGHWAY, BARANGAY 5 (POB.), BANTAY, ILOCOS SUR NORTH LUZON ILOCOS SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(379, 'NATIONAL HIGHWAY, SANTA MONICA, MAGSINGAL, ILOCOS SUR NORTH LUZON ILOCOS SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(380, 'NATIONAL HIGHWAY, BAGANI CAMPOSA, SAN JUAN (POB.), CITY OF CANDON, ILOCOS SUR NORTH LUZON ILOCOS SUR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(381, 'NATIONAL HIGHWAY, POBLACION SUR, SANTIAGO, ILOCOS SUR NORTH LUZON ILOCOS SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(382, 'MAC ARTHHUR HIGHWAY, LIBTONG, BITALAG, TAGUDIN, ILOCOS SUR NORTH LUZON ILOCOS SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(383, 'NATIONAL HIGHWAY, BALIGATAN, CITY OF ILAGAN , ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(384, 'MAHARLIKA HIGHWAY, SANTIAGO-TUGUEGARAO ROAD, SAN ANTONIO, ROXAS, ISABELA NORTH LUZON ISABELA SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(385, 'NATIONAL ROAD, IMBIAO, ROXAS, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(386, 'SAN BERNABE, LUNA, MASIGUN, ROXAS, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(387, 'NATIONAL HIGHWAY, BINGUANG, SAN PABLO, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(388, 'NATIONAL HIGHWAY, MASIGUN, ROXAS, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(389, 'NATIONAL HIGHWAY, SAN PEDRO, MALLIG, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(390, 'SANTIAGO-TUGUEGARAO ROAD, SAN JUAN, QUEZON, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(391, 'MAHARLIKA HIWAY, NUNGNUNGAN II, CITY OF CAUAYAN, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(392, 'NATIONAL HIGHWAY, MAGDALENA, CABATUAN, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(393, 'NATIONAL HIGHWAY, SANTA RITA, AURORA, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(394, 'MAHARLIKA HIWAY, MAGSAYSAY (POB.), NAGUILIAN, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(395, 'NATIONAL HIGHWAH, ALICIA-SAN MATEO ROAD , MAGSAYSAY (POB.), ALICIA, ISABELA NORTH LUZON ISABELA SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(396, 'MAHARLIKA HIWAY, DISTRICT II (POB.), CITY OF CAUAYAN, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(397, 'NATIONAL HIGHWAY, CENTRO II (POB.), ANGADANAN, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(398, 'MAHARLIKA HIWAY, SAN FABIAN, ECHAGUE, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(399, 'PUROK 6, SANTIAGO-SAN AGUSTIN RD., SANTOS, SAN AGUSTIN, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(400, 'MAHARLIKA HIGHWAY, SAN ANTONIO, RAMON, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(401, 'SANTIAGO-TUGUEGARAO ROAD, MAHARLIKA HIGHWAY, BURGOS, RAMON, ISABELA NORTH LUZON ISABELA SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(402, 'PROVINCIAL ROAD, RIZAL, CITY OF SANTIAGO, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(403, 'MAHARLIKA HIGHWAY, CALAO EAST (POB.), CITY OF SANTIAGO, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(404, 'MAHARLIKA HIGHWAY, BATAL, CITY OF SANTIAGO, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(405, 'NATIONAL ROAD, ROSARIO, CITY OF SANTIAGO, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(406, 'RC MIRANDA BLVD., CENTRO WEST (POB.), CITY OF SANTIAGO, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(407, 'NATIONAL ROAD, BALUARTE, CITY OF SANTIAGO, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(408, 'MAHARLIKA HIGHWAY, QUIRINO, CORDON, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(409, 'NATIONAL HIGHWAY, GUNDAWAY (POB.), CABARROGUIS , QUIRINO NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(410, 'MAHARLIKA HIWAY, CAPIRPIRIWAN, CORDON, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(411, 'NATIONAL ROAD, RIZAL, CITY OF SANTIAGO, ISABELA NORTH LUZON ISABELA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(412, 'PROVINCIALROAD COR. MOLINTAS, DAGUPAN ST., BULANAO NORTE, CITY OF TABUK , KALINGA NORTH LUZON KALING', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(413, 'QUEZON CORNER BURGOS, DAGUPAN CENTRO (POB.), CITY OF TABUK , KALINGA NORTH LUZON KALINGA SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(414, 'QUEZON AVE., CATBANGEN, CITY OF SAN FERNANDO , LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(415, 'NATIONAL HIGHWAY, SUGUIDAN SUR, NAGUILIAN, LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(416, 'BY-PASS ROAD, TANQUI, CITY OF SAN FERNANDO , LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(417, 'NATIONAL HIGHWAY, QUINAVITE, BAUANG, LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(418, 'MAC ARTHUR HIGHWAY, SEVILLA, CITY OF SAN FERNANDO , LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(419, 'NATIONAL HIGHWAY, LINGSAT, CITY OF SAN FERNANDO , LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(420, 'NATIONAL HIGHWAY, BRGY. SAN JOSE SUR, AGOO, LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(421, 'NATIONAL HIGHWAY, CENTRAL EAST (POB.), BAUANG, LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(422, 'PROVINCIAL ROAD, SANTIA, CABAROAN, CITY OF SAN FERNANDO , LA UNION NORTH LUZON LA UNION SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(423, 'MABINI ST, CATBANGEN, CITY OF SAN FERNANDO , LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(424, 'PUGO ROSARIO RD., SAN LUIS, PUGO, LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(425, 'NATIONAL HIGHWAY, CAMP 1 UDIAO, CAMP ONE, ROSARIO, LA UNION NORTH LUZON LA UNION SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(426, 'SUB-ANG, BONTOC, BONTOC, MOUNTAIN PROVINCE NORTH LUZON MOUNTAIN PROVINCE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(427, 'PAN PHILIPPINE HIGHWAY, CAANAWAN, SAN JOSE CITY, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(428, 'NATIONAL ROAD, LINGLINGAY, SCIENCE CITY OF MUÑOZ, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(429, 'MAHARLIKA HIGHWAY COR BONIFACIO ST., CANUTO RAMOS POB., SAN JOSE, NUEVA ECIJA NORTH LUZON NUEVA ECIJ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(430, 'MAHARLIKA HIGWAY, MALASIN, SAN JOSE CITY, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(431, 'SAN JOSE CITY-RIZAL PROVINCIAL ROAD, SAN AGUSTIN, SAN JOSE CITY, NUEVA ECIJA NORTH LUZON NUEVA ECIJA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(432, 'MAHARLIKHA HIGHWAY, BALOC, SANTO DOMINGO, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(433, 'MAHARLIKA HIGHWAY, ABAR IST, SAN JOSE, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(434, 'MAHARLIKHA HIGHWAY, SAN MIGUEL NA MUNTI, UMANGAN, ALIAGA, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(435, 'PANGASINAN - NUEVA ECIJA ROAD, SAN ROQUE, GUIMBA, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(436, 'PROVINCIAL ROAD, CABIANGAN, TALUGTUG, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(437, 'PUROK 3, BALOC, SANTO DOMINGO, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(438, 'PANTABANGAN - CANILI - BASAL - BALER ROAD, LIBERTY, PANTABANGAN, NUEVA ECIJA NORTH LUZON NUEVA ECIJA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(439, 'ZONE 1, PARISTA, CORDERO, LUPAO, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(440, 'MAHARLIKA HIGHWAY, ZAMORA (POB.), SANTA ROSA, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(441, 'MAHARLIKA HIGHWAY, BAYANIHAN, PAN PHIL., SAN NICOLAS, CITY OF GAPAN, NUEVA ECIJA NORTH LUZON NUEVA E', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(442, 'STO DOMINGO-LICAB ROAD, DULONG BAYAN, QUEZON, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(443, 'NATIONAL HIGHWAY, SOUTH POBLACION, GABALDON, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(444, 'MAHARLIKA HIGHWAY, SUMACAB ESTE, CITY OF CABANATUAN, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(445, 'ZULUETA STREET, VIJANDRE DISTRICT (POB.), CITY OF CABANATUAN, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(446, 'PIROK 7 EMILIO VERGARA HIGHWAY COR MABINI ST. EXTENSION , SAN JOSEF SUR, CITY OF CABANATUAN, NUEVA E', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(447, 'MAHARLIKA HIGHWAY DEL PILAR ST. COR , SANGITAN EAST, CITY OF CABANATUAN, NUEVA ECIJA NORTH LUZON NUE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(448, 'BANGAD-FORT MAGSAYSAY RD.,, MALACAÑANG, SANTA ROSA, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(449, 'VICTORIA-LICAB ROAD, POBLACION NORTE, VILLAROSA, LICAB, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(450, 'MAHARLIKHA HIGHWAY, MAYAPYAP SUR, CITY OF CABANATUAN, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(451, 'GEN. LUNA ST., GENERAL LUNA (POB.), CITY OF CABANATUAN, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(452, 'EMILIO VERGARA BOULEVARD COR. A MABINI ST. , SANTA ARCADIA, CITY OF CABANATUAN, NUEVA ECIJA NORTH LU', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(453, 'MAHARLIKHA HIGHWAY, MAGSAYSAY DISTRICT, MARIA THERESA, CITY OF CABANATUAN, NUEVA ECIJA NORTH LUZON N', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(454, 'MAHARLIKHA HIGHWAY, HERMOGENES C. CONCEPCION, SR., CITY OF CABANATUAN, NUEVA ECIJA NORTH LUZON NUEVA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(455, 'EMILIO VERGARA HIWAY, CABANATUAN CITY, NUEVA ECIJA NORTH LUZON NUEVA ECIJA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(456, 'MAHARLIKA HIGHWAY, BANGGOT (POB.), BAMBANG, NUEVA VIZCAYA NORTH LUZON NUEVA VIZCAYA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(457, 'NATIONAL ROAD, SALVACION, BAYOMBONG , NUEVA VIZCAYA NORTH LUZON NUEVA VIZCAYA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(458, 'NATIONAL HIGHWAY, ROXAS, SOLANO, NUEVA VIZCAYA NORTH LUZON NUEVA VIZCAYA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(459, 'NATIONAL HIGHWAY, POBLACION, ARITAO, NUEVA VIZCAYA NORTH LUZON NUEVA VIZCAYA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(460, 'BARRIO MADDIANGAT, MADDIANGAT, QUEZON, NUEVA VIZCAYA NORTH LUZON NUEVA VIZCAYA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(461, 'ATIHAN ROAD NATIONAL HIGHWAY ANIINGW, MATAIN, SUBIC, PAMPANGA NORTH LUZON OLONGAPO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(462, 'GAPAN-OLONGAPO ROAD, SAN MATIAS, GUAGUA, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(463, 'PROVINCIAL ROAD, SAN MATIAS, SANTA RITA, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(464, 'PROVINCIAL ROAD, MABICAL, FORTUNA, FLORIDABLANCA, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(465, 'JASA ROAD, PRADO SIONGCO, LUBAO, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(466, 'NATIONAL ROAD, TENEJERO, CITY OF BALANGA , BATAAN NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(467, 'OLONGAPO-GAPAN ROAD, SAN ANTONIO, GUAGUA, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(468, 'GAPAN-SAN FERNANDO-OLONGAPO ROAD, SAN MATIAS, GUAGUA, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(469, 'J. GONZALES BLVD., VIRGEN DE LOS REMEDIOS, CITY OF ANGELES, PAMPANGA NORTH LUZON PAMPANGA SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(470, 'MACARTHUR HIGHWAY, DAU, LAKANDULA, MABALACAT CITY, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(471, 'MCARTHUR HIGHWAY (NORTHBOUND), BALIBAGO, CITY OF ANGELES, PAMPANGA NORTH LUZON PAMPANGA SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(472, 'CLARK PERIMETER RD., DON JUICO AVE, MALABANIAS, CITY OF ANGELES, PAMPANGA NORTH LUZON PAMPANGA SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(473, 'MACARTHUR HIGHWAY, CALUMPANG, MABALACAT CITY, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(474, 'TOWN CENTER D, CLARK, POBLACION, MABALACAT CITY, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(475, 'SAN FRANCISCO ST. COR ARAYAT BLVD, PAMPANG, CITY OF ANGELES, PAMPANGA NORTH LUZON PAMPANGA SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(476, 'ROQUE LANE SUNSET ESTATE, PULUNG MARAGUL, CITY OF ANGELES, PAMPANGA NORTH LUZON PAMPANGA SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(477, 'MACARTHUR HIGHWAY, STO. DOMINGO, TELABASTAGAN, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(478, 'MC ARTHUR HIGHWAY, STO. CRISTO, PULUNGBULU, CITY OF ANGELES, PAMPANGA NORTH LUZON PAMPANGA SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(479, 'PUROK 6 STA. INES INTERCHANGE, SAN JOAQUIN, MABALACAT CITY, PAMPANGA NORTH LUZON PAMPANGA SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(480, 'COR. CLARK SOUTH INTERCHANGE & MA. ROXAS ST., POBLACION, MABALACAT CITY, PAMPANGA NORTH LUZON PAMPAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(481, 'GEN. LUNA ST., CANGATBA, PORAC, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(482, 'MAC ARTHUR HIGHWAY, SAN MATIAS, MORAS DE LA PAZ, SANTO TOMAS, PAMPANGA NORTH LUZON PAMPANGA SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(483, 'ROBINSON\'S STARMILLS, LAGUNDI, SAN JOSE, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(484, 'MAC ARTHUR HIGHWAY, SAN VICENTE, APALIT, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(485, 'MAC ARTHUR HIGHWAY, SINDALAN, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(486, 'MAC ARTHUR HIGHWAY, SAN ISIDRO, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(487, 'MAC ARTHUR HIGHWAY, TAGULUD, DEL PILAR, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(488, 'MAC ARTHUR HIGHWAY, DEL ROSARIO, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(489, 'JASA ROAD, DOLORES, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(490, 'CASTOR COR., MACKINLEY ST., SAN AGUSTIN (POB.), CANDABA, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(491, 'CALULUT ROAD, BULAON, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(492, 'PROVINCIAL ROAD, SAN PEDRO II, MAGALANG, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(493, 'PROVINCIAL ROAD, SAN ROQUE, MAGALANG, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(494, 'KM 71, NLEX, LAGU, PANIPUAN, MEXICO, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(495, 'CANDABA BALIUAG RD., PULONG PALAZAN, CANDABA, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(496, 'CANDABA BALIUAG RD., MANGGA, CANDABA, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(497, 'OLONGAPO-GAPAN RD., SAN JOSE MESULO, ARAYAT, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(498, 'GAPAN-OLONGAPO RD., NATIVIDAD SOUTH (POB.), CABIAO, NUEVA ECIJA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(499, 'JASA RD., LAGUNDI, MEXICO, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(500, 'MAC ARTHUR HIGHWAY, DOLORES, JULIANA, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(501, 'CAPITOL BLVD., SANTO NIÑO, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(502, 'LAZATIN BLVD., DOLORES, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(503, 'CENTRO 3 SAN JUAN, SANTA CRUZ, MEXICO, PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(504, 'NEW BARRIO ROAD, CALULUT, CITY OF SAN FERNANDO , PAMPANGA NORTH LUZON PAMPANGA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(505, 'SAN JACINTO-MANAOAG ROAD, BABASIT, MANAOAG, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(506, 'MACARTHUR HIGHWAY, CARMEN EAST, ROSALES, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(507, 'URDANETA-MANAOAG ROAD, LELEMAAN, MANAOAG, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(508, 'L. SOLORIA, POBLACION EAST, ASINGAN, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(509, 'NATIONAL HIGHWAY, PUGOT, SANTA MARIA, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(510, 'MC ARTHUR MAHARLIKA HIGHWAY, PUELAY, VILLASIS, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(511, 'NATIONAL HIGHWAY STA. MARIA SUR, CANARVACANAN, BINALONAN, PANGASINAN NORTH LUZON PANGASINAN SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(512, 'NATIONAL HIGHWAY, ASAN SUR, SISON, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(513, 'PUROK 3 MACARTHUR HIGHWAY, NANCAYASAN, CITY OF URDANETA, PANGASINAN NORTH LUZON PANGASINAN SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(514, 'MACARTHUR HIGHWAY, SAN VICENTE, POBLACION, CITY OF URDANETA, PANGASINAN NORTH LUZON PANGASINAN SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(515, 'MAGILAS TRAIL, TOBOY, ASINGAN, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(516, 'RIZAL ST., ASINGAN BYPASS ROAD, BANTOG, ASINGAN, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(517, 'DULONG NORTE 1, PAYAR, MALASIQUI, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(518, 'NATIONAL HIGHWAY, CARMEN WEST, ROSALES, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(519, 'MACARTHUR HIGHWAY, ANONAS, CITY OF URDANETA, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(520, 'BINALOAN-DAGUPAN HIGHWAY, PAO, MANAOAG, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(521, 'RIZAL ST, POBLACION ZONE I, SAN QUINTIN, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(522, 'ROMULO HIGHWAY, BOCBOC WEST, AGUILAR, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(523, 'NATIONAL HIGHWAY, DON MATIAS, TAMBACAN, BURGOS, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(524, 'NATIONAL HIGHWAY, ARWAS, BANI, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(525, 'CALASIAO-DAGUPAN ROAD, MH DEL PILAR ST., MAYOMBO, CITY OF DAGUPAN, PANGASINAN NORTH LUZON PANGASINAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(526, 'BALINGASAY ROAD, BALINGASAY, BOLINAO, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(527, 'OLONGAPO-BUGALLON ROAD, PALAMIS, CITY OF ALAMINOS, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(528, 'OLONGAPO-BUGALLON ROAD, MAGSAYSAY, CITY OF ALAMINOS, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(529, 'EAST A.B. FERNADEZ AVE., TAMBAC, CITY OF DAGUPAN, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(530, 'NABLE ST. ARELLANO STREET, GUESET, CITY OF DAGUPAN, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(531, 'QUIBAOL-NANSANGAAN ROAD, LOMBOY, BINMALEY, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(532, 'NATIONAL HIGHWAY, LIWA-LIWA, BOLINAO, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(533, 'QUEZON AVE., POBLACION, CITY OF ALAMINOS, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(534, 'DAGUPAN-BINMALEY ROAD, LUCAO, CITY OF DAGUPAN, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(535, 'LABRADOR-SUAL HIGHWAY, TOBUAN, UYONG, LABRADOR, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(536, 'PEREZ BLVD., MALUED, CITY OF DAGUPAN, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(537, 'MCARTHUR HIGHWAY, NALSIAN, CALASIAO, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(538, 'AVENIDA RIZAL EAST, LIBSONG WEST, LINGAYEN , PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(539, 'POBLACION EAST, TANDOC, QUINTONG, CITY OF SAN CARLOS, PANGASINAN NORTH LUZON PANGASINAN SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(540, 'SAN CARLOS-CALASIAO ROAD, CRUZ, CITY OF SAN CARLOS, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(541, 'PROVINCIAL ROAD, ANDANGIN, BARACBAC, MANGATAREM, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(542, 'PROVINCIAL ROAD, APONIT, CITY OF SAN CARLOS, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(543, 'MALASIQUI-SAN CARLOS ROAD, MAGTAKING, CITY OF SAN CARLOS, PANGASINAN NORTH LUZON PANGASINAN SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(544, 'CARMEN-ALCALA ROAD, POBLACION EAST, ALCALA, PANGASINAN NORTH LUZON PANGASINAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(545, 'NATIONAL HIGHWAY, RIZAL (POB.), SAGUDAY, QUIRINO NORTH LUZON QUIRINO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(546, 'CORDON-DIFFUN, MADDELA ROAD, AURORA WEST (POB.), DIFFUN, QUIRINO NORTH LUZON QUIRINO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(547, 'CALABTANGAN ROAD, POBLACION SUR, MAYANTOC, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(548, 'ROMULO HIGHWAY, PUROK 1, POBLACION EAST, SANTA IGNACIA, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(549, 'MCARTHUR HIGHWAY, POBLACION 2, MONCADA, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(550, 'MCARTHUR HIGHWAY, SAMPUT, PANIQUI, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(551, 'KM 134 TPLEX NORTHBOUND, SAN FRANCISCO, VICTORIA, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(552, 'MCARTHUR HIGHWAY, ABAGON, POBLACION 3, GERONA, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(553, 'QUEZON AVE., PAO 1ST, CAMILING, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(554, 'ROMULO HIGHWAY, DALDALAYAP, SAN CLEMENTE, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(555, 'ROMULO HIGHWAY, SURGUI 2ND, CAMILING, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(556, 'BAYAMBANG-CAMILING ROAD, BILAD, CANIAG, CAMILING, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(557, 'DON BASILIO SAN TIAGO ST., POBLACION 1, GERONA, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(558, 'KM 134 TPLEX SOUTHBOUND, SAN FRANCISCO, VICTORIA, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(559, 'LAPAZ-TARLAC ROAD, RIZAL, LA PAZ, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(560, 'MACARTHUR HIGHWAY,, SAN FRANCISCO, CITY OF TARLAC , TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(561, 'MACARTHUR HIGHWAY, SAN ROQUE ST., SAN VICENTE, CITY OF TARLAC , TARLAC NORTH LUZON TARLAC SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(562, 'SANTA ROSA - TARLAC ROAD, LA PAZ-ZA, BINAUGANAN, CITY OF TARLAC , TARLAC NORTH LUZON TARLAC SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(563, 'SOUTHERN BYPASS ROAD, SAN VICENTE, CITY OF TARLAC , TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(564, 'MACARTHUR HIGHWAY, SAN NICOLAS (POB.), BAMBAN, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(565, 'MACARTHUR HIGHWAY, SAN RAFAEL, SAN VICENTE, CITY OF TARLAC , TARLAC NORTH LUZON TARLAC SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(566, 'ROMULO HIGHWAY, SAN PABLO, CITY OF TARLAC , TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(567, 'ROMULO HIGHWAY, SAPANG MARAGUL, CITY OF TARLAC , TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(568, 'CONCEPCION-LAPAZ RD., SANTO DOMINGO 1ST, CAPAS, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(569, 'L. CORTEZ ST., SAN NICOLAS (POB.), CONCEPCION, TARLAC NORTH LUZON TARLAC SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(570, 'FINONES ST, AMAGNA (POB.), SAN FELIPE, ZAMBALES NORTH LUZON ZAMBALES SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(571, 'RIZAL AVENUE COR. WEST 1ST, ASINAN, CITY OF OLONGAPO, ZAMBALES NORTH LUZON ZAMBALES SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(572, 'PUROK 1 NATIONAL HIGHWAY, BARACA-CAMACHILE (POB.), SUBIC, ZAMBALES NORTH LUZON ZAMBALES SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(573, 'NATIONAL HIGHWAY, WEST DIRITA, SAN ANTONIO, ZAMBALES NORTH LUZON ZAMBALES SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(574, 'RIZAL HIGHWAY, ASINAN, CITY OF OLONGAPO, ZAMBALES NORTH LUZON ZAMBALES SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(575, 'RIZAL BLVD. CORNER ARGONAUT HIGHWAY, MABAYO, MORONG, ZAMBALES NORTH LUZON ZAMBALES SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(576, 'HALFMOON BEACH NAT\'L HIGHWAY, BARRETO, CITY OF OLONGAPO, ZAMBALES NORTH LUZON ZAMBALES SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(577, 'NATIONAL HIGHWAY, BRGY. DEL PILAR, CASTILLEJOS, ZAMBALES NORTH LUZON ZAMBALES SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(578, 'RIZAL ST., IRAYA, GUINOBATAN, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(579, 'ZIGA AVENUE BASUD, DIVINO ROSTRO (POB.), CITY OF TABACO, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(580, 'AGUINALDO ST., BARANGAY 14 (POB.), BACACAY, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(581, 'PIER SITE, SANTO CRISTO (POB.), CITY OF TABACO, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(582, 'PRADO VERDE CORPORATION PROPERTY, BGY. 49 - BIGAA, CITY OF LEGAZPI , ALBAY SOUTH LUZON ALBAY SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(583, 'NATIONAL ROAD, LIDONG, SANTO DOMINGO, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(584, 'ZIGA AVENUE, SAN JUAN (POB.), CITY OF TABACO, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(585, 'NATIONAL RD. GAJO ST., CORO-CORO, TIWI, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(586, 'PAN PHILIPPINE HIGHWAY, SANTA CRUZ (POB.), CITY OF LIGAO, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(587, 'LAKANDULA DRIVE, BGY. 39 - BONOT (POB.), CITY OF LEGAZPI , ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(588, 'MAHARLIKA HIGHWAY, NAMANTAO, DARAGA, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(589, 'P3 RIZAL ST., BGY. 23 - IMPERIAL COURT SUBD. (POB.), CITY OF LEGAZPI , ALBAY SOUTH LUZON ALBAY SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(590, 'RIZAL COR. REGIDOR ST., SAGPON, DARAGA, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(591, 'BINITAYAN CORNER LAKANDULA ST., BINITAYAN, DARAGA, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(592, 'MAHARLIKA HIGHWAY, BASCARAN, DARAGA, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(593, 'WASHINGTON DRIVE, BGY. 8 - BAGUMBAYAN (POB.), CITY OF LEGAZPI , ALBAY SOUTH LUZON ALBAY SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(594, 'PAN PHILIPNE HIGHWAY, BUSAY, DARAGA, ALBAY SOUTH LUZON ALBAY SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(595, 'KM 72 NATIONAL HIGHWAY, KAYLAWAY, NASUGBU, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(596, 'PAN-PHILIPPINE HIGHWAY ST. LAZARUS VILLAGE, SANTIAGO, CITY OF STO. TOMAS, BATANGAS SOUTH LUZON BATAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(597, 'BALETE ROAD, SAMBAT, CITY OF TANAUAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(598, 'KM 79 STAR TOLLWAY NORTHBOUND, TIBIG, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(599, 'TIAONG-LIPA ROAD P TORRES ST., ANTIPOLO DEL NORTE, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(600, 'J.P. LAUREL NATIONAL HIGHWAY, MATAAS NA LUPA, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(601, 'PROVINCIAL RD. COR. HI WOOD ST., BAGONG POOK, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(602, 'KM 86 STAR TOLLWAY, BRGY AYA SAN JOSE, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(603, 'JOSE P. LAUREL HI-WAY PUROK 3, SICO, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(604, 'PROVINCIAL ROAD, BANAYBANAY, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(605, 'AYALA HIGHWAY, MATAAS NA LUPA, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(606, 'TALISAY - TANAUAN RD, SANTOR, CITY OF TANAUAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(607, 'NATIONAL ROAD, BARANGAY II (POB.), CITY OF STO. TOMAS, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(608, 'KM 75 STAR TOLLWAYS, SAN ANDRES, MALVAR, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(609, 'MAHOGANY ST. COR LIPA-ALAMINOS RD, DAGATAN, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(610, 'KM 82 PRESIDENT LAUREL HIGHWAY, BARANGAY 12 (POB.), CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(611, 'PAN PHILIPPINE HIGHWAY, SANTA ANASTACIA, CITY OF STO. TOMAS, BATANGAS SOUTH LUZON BATANGAS SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(612, 'PROVINCIAL ROAD, POBLACION, PADRE GARCIA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(613, 'SAN JUAN-LAIYA ROAD, MABALANOY, SAN JUAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(614, 'KM. 81 GEN LUNA ST., SABANG, CITY OF LIPA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(615, 'NATIONAL HIGHWAY, TAYSAN, SAN JOSE, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(616, 'GOV. CARPIO RD., GULOD ITAAS, BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(617, 'NEW BY PASS ROAD SAMPAGA, BATANGAS CITY, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(618, 'TALAIBON NATIONAL ROAD, POBLACION, IBAAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(619, 'PALIPANDAN ROAD, PALINDAN, IBAAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(620, 'DIVERSION ROAD, BOLBOK, BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(621, 'PASTOR AVE., BARANGAY CUTA, BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(622, 'KUMINTANG IALAYA, BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(623, 'NATIONAL HIGHWAY COR P. BURGOS ST., BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22');
INSERT INTO `stations` (`id`, `name`, `location`, `status`, `created_at`, `updated_at`) VALUES
(624, 'KM 103 P. BURGOS ST., BOLBOK, BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(625, 'CALTEX ROAD, BANABA IBABA, BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(626, 'NATIONAL HIGHWAY, BALAGTAS, BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(627, 'SITIO 7 BALAGTAS, BANABA KANLURAN, BATANGAS CITY , BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(628, 'SAN JOSE - IBAAN - BATANGAS ROAD, PALINDAN, IBAAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(629, 'BATANGAS - TABANGAO - LOBO RD., FABRICA, LOBO, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(630, 'GOV. ANTONIO CARPIO RD., MAPULO, TAYSAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(631, 'PUROK 4 STA. RITA KARSADA BAUAN-BATANGAS ROAD, SANTA RITA KARSADA, BATANGAS CITY , BATANGAS SOUTH LU', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(632, 'NASUGBU - TERNATE HIGHWAY, WAWA, NASUGBU, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(633, 'CALACA - LEMERY HIGHWAY, SANGALANG, LEMERY, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(634, 'NATIONAL HIGHWAY MANGOBOS ST., BARANGAY I (POB.), BAUAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(635, 'NATIONAL HIGHWAY, CAMASTILISAN, CALACA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(636, 'ILUSTRE AVE., BRGY. DISTRICT II, LEMERY, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(637, 'KM94 NATIONAL HIGHWAY, BARANGAY 1 (POB.), CUENCA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(638, 'PAZ CORNER ANTORCHA, BARANGAY 12 (POB.), BALAYAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(639, 'NATIONAL HIGHWAY PALICO-BALAYAN-BATANGAS RD., CALOOCAN, BALAYAN, BATANGAS SOUTH LUZON BATANGAS SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(640, 'NATIONAL HIGHWAY, SAN ROQUE, BAUAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(641, 'CALATAGAN-LIAN HIGHWAY, BINUBUSAN, LIAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(642, 'J.P. RIZAL ST., SAN DIEGO, LIAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(643, 'NATIONAL RD., LABAC, CUENCA, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(644, 'MAKALINTAL AVE., SAMBAT, SAN PASCUAL, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(645, 'NATIONAL HIGHWAY, JP LAUREL ST., BUNGAHAN, LIAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(646, 'PALICO - BALAYAN - BATANGAS RD., MUZON, SAN LUIS, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(647, 'J.J.ZOBEL ST., BARANGAY 2 (POB.), CALATAGAN, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(648, 'TAAL - SAN LUIS RD., BUTONG, TAAL, BATANGAS SOUTH LUZON BATANGAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(649, 'NATIONAL ROADVINSONS AVE., BARANGAY IV (POB.), DAET , CAMARINES NORTE SOUTH LUZON CAMARINES NORTE SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(650, 'VINZONS AVE., BARANGAY II (POB.), VINZONS, CAMARINES NORTE SOUTH LUZON CAMARINES NORTE SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(651, 'NATIONAL ROAD, POBLACION NORTE, PARACALE, CAMARINES NORTE SOUTH LUZON CAMARINES NORTE SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(652, 'REGINO DIAS ST. NATIONAL ROAD, SANTA ELENA (POB.), SANTA ELENA, CAMARINES NORTE SOUTH LUZON CAMARINE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(653, 'NATIONAL ROAD PIMENTEL AVE., BARANGAY VI (POB.), DAET , CAMARINES NORTE SOUTH LUZON CAMARINES NORTE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(654, 'ROXAS AVE. DIVERSION RD. PAN PHILIPPINE HIGHWAY, TABUCO, CITY OF NAGA, CAMARINES SUR SOUTH LUZON CAM', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(655, 'ROXAS AVE CORNER NINOY AND CORY AQUINO, TRIANGULO, CITY OF NAGA, CAMARINES SUR SOUTH LUZON CAMARINES', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(656, 'ALMEDA HIGHWA , CONCEPCION PEQUENA, CITY OF NAGA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(657, 'NATIONAL ROAD, TARA, SIPOCOT, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(658, 'PANGANIBAN STREET, LERMA, CITY OF NAGA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(659, 'MAGSAYSAY AVE., CONCEPCION PEQUENA, CITY OF NAGA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(660, 'NATIONAL ROAD, CONCEPCION PEQUENA, CITY OF NAGA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(661, 'PANGANIBAN DRIVE, TINAGO, CITY OF NAGA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(662, 'MAHARLIKA HIGHWAY, TAMBO, PAMPLONA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(663, 'ZONE 6 MAHARLIKA HIGHWAY, DEL ROSARIO, CITY OF NAGA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(664, 'NATIONAL ROAD, SAN AGUSTIN, PILI , CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(665, 'ZONE 3 NATIONAL HIGHWAY COR PILI DIVERSION RD., SAN AGUSTIN, PILI , CAMARINES SUR SOUTH LUZON CAMARI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(666, 'GUEVARRA ST., SAN FRANCISCO (POB.), CITY OF IRIGA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(667, 'NATIONAL ROAD, BAGUMBAYAN PEQUEÑO (POB.), GOA, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(668, 'NATIONAL ROAD, TALOJONGON, TIGAON, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(669, 'SAGNAY PROVINCIAL RD., MABCA, SAGÑAY, CAMARINES SUR SOUTH LUZON CAMARINES SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(670, 'BINAKAYAN, COVELANDIA RD., KAWIT, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(671, 'SMYPC E. AGUINALDO HIGHWAY, ANABU II-F, CITY OF IMUS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(672, 'L4967B MOLINO BLVD., LIGAS II, BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(673, 'KM 17 AGUINALDO HIGHWAY, PALICO IV, CITY OF IMUS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(674, 'KM. 16 AGUINALDO HIGHWAY, NIOG I, BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(675, 'EVANGELISTA ST., KAINGIN (POB.), BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(676, 'COR PEDRO REYES ST, ALAPAN II-B, CITY OF IMUS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(677, 'DAANG HARI ROAD, PASONG BUAYA I, CITY OF IMUS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(678, 'TEJERO-BACAO DIVERSION RD., TEJERO, CITY OF GENERAL TRIAS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(679, 'KM 23 AGUINALDO HIGHWAY BUCAL, SAMPALOC II, CITY OF DASMARIÑAS, CAVITE SOUTH LUZON CAVITE SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(680, 'AGUINALDO HIGHWAY, SAN AGUSTIN II, CITY OF DASMARIÑAS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(681, 'MOLINO BOULEVARD, MOLINO II, BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(682, 'DAANG HARI RD., MOLINO IV, BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(683, 'BUHAY NA TUBIG ST., BUHAY NA TUBIG, CITY OF IMUS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(684, 'AGUINALDO HIGHWAY PANAPAAN, P.F. ESPIRITU I, CITY OF BACOOR, CAVITE SOUTH LUZON CAVITE SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(685, 'NATIONAL ROAD LAS PIÑAS BOUND, ZAPOTE V, BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(686, 'P BURGOS AVE. CARIDAD, BARANGAY 34, CITY OF CAVITE, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(687, 'NEW DIVERSION RD., MAGDALO, KAWIT, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(688, 'NEW BYPASS ROAD, BACAO II, CITY OF GENERAL TRIAS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(689, 'KALAYAAN ROAD, SAN SEBASTIAN, KAWIT, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(690, 'GEN. TRIAS DRIVE, TEJEROS CONVENTION, ROSARIO, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(691, 'TIRONA HIGHWAY, HABAY II, BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(692, 'CENTENNIAL RD., GAHAK, KAWIT, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(693, 'MARCELLA ST., SALCEDO II, NOVELETA, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(694, 'MOLINO RD., MOLINO III, BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(695, 'MANILA CAVITE DAHALICAN, BARANGAY 8, CITY OF CAVITE, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(696, 'AGUINALDO HIGHWAY, ZONE I-B, CITY OF DASMARIÑAS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(697, 'MOLINO PALIPARAN RD., MOLINO IV, BACOOR CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(698, 'TRECE TANZA RD, DE OCAMPO, CITY OF TRECE MARTIRES , CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(699, 'GOVERNOR\'S DRIVE COR. DASMARIÑAS , SAN FRANCISCO, CITY OF GENERAL TRIAS, CAVITE SOUTH LUZON CAVITE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(700, 'GOVERNOR\'S DRIVE, CABILANG BAYBAY, CARMONA, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(701, 'F-1 A. SORIANO HIGHWAY, DAANG AMAYA III, TANZA, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(702, 'A. SORIANO HIGHWAY BE SAMPAGUITA ST., DAANG AMAYA III, TANZA, CAVITE SOUTH LUZON CAVITE SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(703, 'BIÑAN-CARMONA RD., MADUYA, CARMONA, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(704, 'JM LOYOLA ST., BARANGAY 8 (POB.), CARMONA, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(705, 'GOVERNOR\'S DRIVE COR. ARNALDO HIGHWAY, SAN FRANCISCO, CITY OF GENERAL TRIAS, CAVITE SOUTH LUZON CAVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(706, 'MANCILLA PROPERTY GOV. FERRER DRIVE, MANGGAHAN, CITY OF GENERAL TRIAS, CAVITE SOUTH LUZON CAVITE SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(707, 'MUNTING ILOG ST., IBA, SILANG, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(708, 'GORVERNOR\'S DRIVE, MABUHAY, CARMONA, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(709, 'AGUINALDO HIGHWAY, SABUTAN, SILANG, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(710, 'GOV. DRIVE COR CONGRESSIONAL AVE., RAMON CRUZ, GEN. MARIANO ALVAREZ, CAVITE SOUTH LUZON CAVITE SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(711, 'AGUINALDO HIGHWAY, LALAAN I, SILANG, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(712, 'GOVERNOR\'S DRIVE, PALIPARAN I, CITY OF DASMARIÑAS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(713, 'P3 BROOKSIDE LANE, SAN FRANCISCO, CITY OF GENERAL TRIAS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(714, 'ARNALDO HIGHWAY, SANTIAGO, CITY OF GENERAL TRIAS, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(715, 'ARNALDO HIGHWAY, PASONG CAMACHILE II, CITY OF GENERAL TRIAS, CAVITE SOUTH LUZON CAVITE SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(716, 'KM 29 SOUTH LUZON EXPRESSWAY, SAN ANTONIO, CITY OF SAN PEDRO, LAGUNA SOUTH LUZON CAVITE SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(717, 'GOV. DRIVE DASMARINAS CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(718, 'PUROK 3 BRGY. PANGIL , BANAYBANAY, AMADEO, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(719, 'KM 668 BURGOS COR DE OCAMPO, BARANGAY 4 (POB.), INDANG, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(720, 'NORTH BOUND SMC TRAINING CENTER, KAYLAWAY, NASUGBU, BATANGAS SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(721, 'NATIONAL ROAD, ALULOD, INDANG, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(722, 'TRECE INDANG RD, INOCENCIO, CITY OF TRECE MARTIRES , CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(723, 'PUROK 3, ALULOD, INDANG, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(724, 'AGUINALDO HIGHWAY, MENDEZ CROSSING EAST, CITY OF TAGAYTAY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(725, 'CRISANTO M. DE LOS REYES AVE, BANAYBANAY, AMADEO, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(726, 'KAYTITINGA - MAGALLANES RD., BARANGAY 5 (POB.), MAGALLANES, CAVITE SOUTH LUZON CAVITE SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(727, 'GOVERNORS DRIVE, GARITA I A, MARAGONDON, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(728, 'LUKSUHIN IBABA ST., LUKSUHIN ILAYA, ALFONSO, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(729, 'CRISANTO M. DELOS REYES, GEN. TRIAS CITY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(730, 'MARAHAN-ALFONSO RD., MARAHAN II, ALFONSO, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(731, 'C BAYANI ST., BARANGAY VII (POB.), AMADEO, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(732, 'NAIC-TANZA BY-PASS RD., IBAYO ESTACION, NAIC, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(733, 'AGUINALDO HIGHWAY, MAHARLIKA EAST, CITY OF TAGAYTAY, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(734, 'CRISANTO M. DE LOS REYES AVE., GALICIA III, MENDEZ, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(735, 'TAGAYTAY-STA ROSA ROAD, TARTARIA, SILANG, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(736, 'TAGAYTAY-STA.ROSA RD COR TAHIBO ST., PUTING KAHOY, SILANG, CAVITE SOUTH LUZON CAVITE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(737, 'MALVAR ST. J.P. LAUREL, TUBIGAN, CITY OF BIÑAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(738, 'TIMBAO ROAD, TIMBAO, CITY OF BIÑAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(739, 'KM 44 NORTHBOUND, MAPAGONG, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(740, 'KM 44 SOUTHBOUND, CANLUBANG, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(741, 'PUROK 1 SOUTH CITY DRIVE, ZAPOTE, CITY OF BIÑAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(742, 'HALANG RD. SOUTHWOODS CITY, SAN FRANCISCO, CITY OF BIÑAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(743, 'WALK 3 ETON CITY, MALITLIT, CITY OF SANTA ROSA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(744, 'STA. ROSA TAGAYTAY RD. LAGUNA BEL AIR, DON JOSE, CITY OF SANTA ROSA, LAGUNA SOUTH LUZON LAGUNA SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(745, 'SANTA ROSA -TAGAYTAY RD., PULONG SANTA CRUZ, CITY OF SANTA ROSA, LAGUNA SOUTH LUZON LAGUNA SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(746, 'SMC COMPLEX, PULONG SANTA CRUZ, CITY OF SANTA ROSA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(747, 'TATLONG HARI ST., APLAYA, CITY OF SANTA ROSA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(748, 'PULO DIEZMO RD., DIEZMO, CABUYAO CITY, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(749, 'C.A. YULO AVENUE SILANGAN INDUSTRIAL PARK RD., CANLUBANG, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(750, 'JP RIZAL AVE., SALA, CABUYAO CITY, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(751, 'NATIONAL HIGHWAY, LANDAYAN, CITY OF SAN PEDRO, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(752, 'OLD NATIONAL HIGHWAY, PARIAN, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(753, 'OLD NATIONAL HIGHWAY, REAL, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(754, 'DON BOSCO AVE., MAYAPA, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(755, 'BRGY. BUNGGO, CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(756, 'NATIONAL HIGHWAY, BUCAL, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(757, 'OLD NATIONAL HIGHWAY, SANTO NIÑO, CITY OF BIÑAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(758, 'OLD NATIONAL HIGHWAY, DE LA PAZ, CITY OF BIÑAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(759, 'OLD NATIONAL HIGHWAY, TAGAPO, CITY OF SANTA ROSA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(760, 'MAHARLIKA HIGHWAY, TURBINA, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(761, 'CHIPECO AVENUE, BARANGAY 3 (POB.), CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(762, 'MAHARLIKA HIGHWAY NORTHBOUND, MAKILING, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(763, 'NATIONAL HIGHWAY, REAL, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(764, 'MANILA S RD., BANAYBANAY, CABUYAO CITY, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(765, 'NATIONAL HIGHWAY, BAGONG KALSADA, CITY OF CALAMBA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(766, 'F. REYES ST. BALIBAGO ROAD, BALIBAGO, CITY OF SANTA ROSA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(767, 'NATIONAL HIGHWAY, BULILAN NORTE (POB.), PILA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(768, 'MAHARLIKA HIGHWAY, SAN AGUSTIN (POB.), BAY, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(769, 'NATIONAL HIGHWAY, LONGOS, KALAYAAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(770, 'NATIONAL HIGHWAY, MAAHAS, LOS BAÑOS, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(771, 'CPDO CMPD UP LOS BAÑOS, BATONG MALAKE, LOS BAÑOS, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(772, 'NATIONAL HIGHWAY, MASIIT, CALAUAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(773, 'NATIONAL HIGHWAY, MAYTALANG I, LUMBAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(774, 'NATIONAL HIGHWAY, MASAPANG, VICTORIA, LAGUNA SOUTH LUZON lAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(775, 'SINILOAN-FAMY-REAL INFANTA RD., MENDIOLA, SINILOAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(776, 'NATIONAL HWAY BIÑAN, PAGSAWITAN, SANTA CRUZ , LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(777, 'NATIONAL ROAD, BAGUMBAYAN, SANTA CRUZ , LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(778, 'G. REDOR STREET, G. REDOR (POB.), SINILOAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(779, 'NATIONAL ROAD CASA REAL, MABATO-AZUFRE, PANGIL, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(780, 'PUROK 3 MAHARLIKA HIGHWAY, SAN FRANCISCO, CITY OF SAN PABLO, LAGUNA SOUTH LUZON LAGUNA SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(781, 'MAHARLIKA HIGHWAY, SAN ROQUE, CITY OF SAN PABLO, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(782, 'MAHARLIKA HIGHWAY COR. CALABARZON , SAN AGUSTIN, ALAMINOS, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(783, 'NATIONAL ROAD, MALINAO, NAGCARLAN, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(784, 'NATIONAL HIGHWAY 45 BUNGKOL ST., HALAYHAYIN, MAGDALENA, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(785, 'MAHARLIKA HI-WAY, SAN JUAN, ALAMINOS, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(786, 'COLAGO AVENUE, SAN ROQUE, CITY OF SAN PABLO, LAGUNA SOUTH LUZON LAGUNA SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(787, 'RIZAL AVE., BAGONG POOK VI-C (POB.), CITY OF SAN PABLO, LAGUNA SOUTH LUZON MARINDUQUE SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(788, 'KASILANG ST., MATAAS NA BAYAN (POB.), BOAC , MARINDUQUE SOUTH LUZON MARINDUQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(789, 'QUEZON ST., ANAPOG-SIBUCAO, MOGPOG, MARINDUQUE SOUTH LUZON MARINDUQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(790, 'NATIONAL HIWAY, KATIPUNAN, PLACER, MASBATE SOUTH LUZON MASBATE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(791, 'NATIONAL RD., TUGBO, CITY OF MASBATE , MASBATE SOUTH LUZON MASBATE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(792, 'NATIONAL ROAD, POBLACION, BALUD, MASBATE SOUTH LUZON MASBATE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(793, 'GOVERNOR IGNACIO ST., CAMILMIL, CITY OF CALAPAN , ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(794, 'NAUTICAL HIGHWAY, SANTA ISABEL, CITY OF CALAPAN , ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(795, 'QUEZON DRIVE , CALERO (POB.), CITY OF CALAPAN , ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(796, 'WESTERN NAUTICAL HIGHWAY, MALAYA, NAUJAN, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(797, 'STRONG REPUBLIC NAUTICAL HIGHWAY , BARCENAGA, NAUJAN, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(798, 'NATIONAL ROAD, NEW DAGUPAN, CALINTAAN, OCCIDENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(799, 'SITIO CROSSING, POBLACION, SAN TEODORO, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(800, 'WESTERN NAUTICAL HIGHWAY, POBLACION, BACO, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(801, 'J.P. RIZAL ST., SAN VICENTE SOUTH (POB.), CITY OF CALAPAN , ORIENTAL MINDORO SOUTH LUZON ORIENTAL MI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(802, 'NATIONAL ROAD, BGY. 3, POBLACION 3, MAMBURAO , OCCIDENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(803, 'NATIONAL ROAD HONDURA ST., POBLACION, PUERTO GALERA, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(804, 'WESTERN NAUTICAL HIWAY MANILA NORTH ROAD, SAN ISIDRO, PUERTO GALERA, ORIENTAL MINDORO SOUTH LUZON OR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(805, 'NATIONAL HIGHWAY, MALIGAYA (POB.), GLORIA, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(806, 'NAUJAN RD., SANTIAGO, NAUJAN, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(807, 'STRONG REPUBLIC NAUTICAL HIGHWAY, PALAYAN, PINAMALAYAN, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDOR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(808, 'PUERTO PRINCESA SOUTH ROAD, SANTA MONICA, CITY OF PUERTO PRINCESA , PALAWAN SOUTH LUZON PALAWAN SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(809, 'PUROK UNITED HOMEOWNERS, TINIGUIBAN, CITY OF PUERTO PRINCESA , PALAWAN SOUTH LUZON PALAWAN SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(810, 'NATIONAL RD., BARANGAY VI (POB.), CORON, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(811, 'TAYTAY-EL NIDO NATIONAL HIGHWAY, VILLA LIBERTAD, EL NIDO, PALAWAN SOUTH LUZON PALAWAN SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(812, 'NATIOL ROAD, SAN PEDRO, CITY OF PUERTO PRINCESA , PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(813, 'RIZAL AVENUE, BANCAO-BANCAO, CITY OF PUERTO PRINCESA , PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(814, 'MALVAR ST., MATAHIMIK (POB.), CITY OF PUERTO PRINCESA , PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(815, 'PUERTO PRINCESA NORTH ROAD, BARANGAY II (POB.), ROXAS, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(816, 'TAYTAY RD., POBLACION, TAYTAY, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(817, 'NATIONAL HIGHWAY, SANDOVAL, NARRA, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(818, 'NATIONAL HI-WAY, MARANGAS (POB.), BATARAZA, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(819, 'NATIONAL HIGHWAY, NARRA (POB.), NARRA, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(820, 'NATIONAL HIGHWAY, ALFONSO XIII (POB.), QUEZON, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(821, 'QUEZON - PUNTA BAJA RD., ALFONSO XIII (POB.), QUEZON, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(822, 'POBLACION - LONG BEACH RD., NEW AGUTAYA, SAN VICENTE, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(823, 'SANDOVAL STREET, BARANGAY II (POB.), ROXAS, PALAWAN SOUTH LUZON PALAWAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(824, 'NATIONAL ROAD, BATICAN, INFANTA, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(825, 'UNGOS-CAWAYAN RD., UNGOS, REAL, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(826, 'LUCBAN - TAYABAS ROAD, TINAMNAN, LUCBAN, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(827, 'MAHARLIKA ROAD, CALUMPANG, CITY OF TAYABAS, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(828, 'PAN PHILIPPINE HIGHWAY, MANGILAG SUR, CANDELARIA, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(829, 'NATIONAL ROAD, LALIG, TIAONG, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(830, 'NATIONAL HIGHWAY, BUKAL SUR, CANDELARIA, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(831, 'NATIONAL HIGHWAY, ABANG, LUCBAN, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(832, 'AN PHILIPPINE HIGHWAY, TALISAY, TIAONG, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(833, 'NATIONAL ROAD, PAIISA, TIAONG, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(834, 'PAN PHILIPPINE HIGHWAY GOV. RODRIGUEZ ST., BARANGAY 4 (POB.), SARIAYA, QUEZON SOUTH LUZON QUEZON SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(835, 'MAHARLIKA HIGHWAY, CALANTIPAYAN, LOPEZ, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(836, 'MAHARLIKA HIGHWAY, SANTA MARIA, CALAUAG, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(837, 'GULANG-GULANG AVENUE, GULANG-GULANG, CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(838, 'MERCHAN COR JUAREZ ST., BARANGAY 6 (POB.), CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(839, 'OLD MANILA SOUTH ROAD, IBABANG DUPAY, CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(840, 'MAHARLIKA HIGHWAY, BRGY. PANIKIHAN, GUMACA, QUEZON PROVINCE SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(841, 'TAYABAS-MAUBAN ROA , POLO, MAUBAN, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(842, 'QUEZON AVENUE. COR ZAMORA ST., BARANGAY 7 (POB.), CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(843, 'ML TAGARAO AVE., ILAYANG IYAM, CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(844, 'MAHARLIKA HIGHWAY, BUTAGUIN, GUMACA, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(845, 'LUCENA-TAYABAS ROAD, GULANG-GULANG, CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(846, 'MAHARLIKA HIGHWAY, ROSARIO, GUMACA, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(847, 'PFDA, DALAHICAN, CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(848, 'MAHARLIKA HIGHWAY, BUKAL, PAGBILAO, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(849, 'PAN PHILIPPINE HIGHWAY, MAYAO SILANGAN, CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(850, 'GOMEZ EXT., IBABANG DUPAY, CITY OF LUCENA , QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(851, 'ROLANDO R. ANDAYA HIGHWAY, SANTA CECILIA, TAGKAWAYAN, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(852, 'PUROK CENTRO, DINAHICAN, INFANTA, QUEZON SOUTH LUZON QUEZON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(853, 'MAHARLIKA HIGHWAY, SAN PEDRO, IROSIN, SORSOGON SOUTH LUZON SORSOGON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(854, 'RIZAL ST.BURABOD, TALISAY (POB.), CITY OF SORSOGON , SORSOGON SOUTH LUZON SORSOGON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(855, 'SORSOGON DIVERSION ROAD, CABID-AN, CITY OF SORSOGON , SORSOGON SOUTH LUZON SORSOGON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(856, 'CATICLAN, MALAY, AKLAN VISAYAS AKLAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(857, 'L.MAGNABIJON ST., POBLACION, NUMANCIA, AKLAN VISAYAS AKLAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(858, 'OSMEÑA AVENUE, ESTANCIA, KALIBO , AKLAN VISAYAS AKLAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(859, 'WESTERN NAUTICAL HIGHWAY, LAGUINBANUA EAST, NUMANCIA, AKLAN VISAYAS AKLAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(860, 'NATIONAL HIGHWAY, POBLACION, IBAJAY, AKLAN VISAYAS AKLAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(861, 'SITIO CAGMAN HIGHWAY, MANOC-MANOC, MALAY, AKLAN VISAYAS AKLAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(862, 'ROXAS AVE. CORNER MABINI ST., POBLACION, KALIBO , AKLAN VISAYAS AKLAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(863, 'TOBIAS - FORNIER - ANINI Y RD., VILLAVERT-JIMENEZ, HAMTIC, ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(864, 'NATIONAL HIGHWAY, CARIDAD, HAMTIC, ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(865, 'NATIONAL HIGHWAY, IMPORTANTE, TIBIAO, ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(866, 'REAL ST., PADANG, PATNONGON, ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(867, 'NATIONAL HIGHWAY PATNONGON RD., MAGSAYSAY, PATNONGON, ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(868, 'PROVINCIAL ROAD RIZAL ST., ATABAY, TOBIAS FORNIER, ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(869, 'NATIONAL ROAD, ILAURES, BUGASONG, ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(870, 'PC BARRACKS RD., SANTA FE, PANDAN, ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(871, 'CORNER T.A FORNIER AND SAN ANTONIO STS., BARANGAY 1 (POB.), SAN JOSE , ANTIQUE VISAYAS ANTIQUE SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(872, 'GENERAL FULLON STREET, BARANGAY 8 (POB.), SAN JOSE , ANTIQUE VISAYAS ANTIQUE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(873, 'NATIONAL HIGHWAY, SAN ROQUE (POB.), BILIRAN, BILIRAN VISAYAS BILIRAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(874, 'SAN ISIDRO STREET, CULABA CENTRAL (POB.), CULABA, BILIRAN VISAYAS BILIRAN SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(875, 'J.A. CLARIN ST., DAMPAS, CITY OF TAGBILARAN , BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(876, 'SON-OC ST. , POBLACION, UBAY, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(877, 'CPG AVENUE, COGON, CITY OF TAGBILARAN , BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(878, 'TAGBILARAN NORTH ROAD, LUCOB, CALAPE, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(879, 'PROVINCIAL HIGHWAY COR.T.L. RULIDA ST., POBLACION, CATIGBIAN, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(880, 'CPG NORTH AVENUE, UBUJAN, CITY OF TAGBILARAN , BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(881, 'PROVINCIAL HIGHWAY COR. SALAZAR ST., MOTO NORTE (POB.), LOON, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(882, 'BOHOL-NORTH CIRCUMFERENTIAL ROAD, POTOHAN, TUBIGON, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(883, 'MA. CLARA STREET, COGON, CITY OF TAGBILARAN , BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(884, 'J.S. TORRALBA STREET, POBLACION II, CITY OF TAGBILARAN , BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(885, 'NATIONAL HIGHWAY, DEL CARMEN SUR (POB.), BALILIHAN, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(886, 'NATIONAL HIGHWAY, TAGUIHON, BACLAYON, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(887, 'TAGBILARAN NORTH ROAD C.P. GARCIA AVE., BOOY, CITY OF TAGBILARAN , BOHOL VISAYAS BOHOL SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(888, 'NEW TAGBILARAN INTEGRATED BUS TERMINAL J.A. CLARIN, DAMPAS, CITY OF TAGBILARAN , BOHOL VISAYAS BOHOL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(889, 'BRUNEI ST., DAO, CITY OF TAGBILARAN , BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(890, 'NATIONAL HIGHWAY J.A. CLARIN ST., SAMBOG, CORELLA, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(891, 'CPG AVENUE, TAGUIHON, BACLAYON, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(892, 'NATIONAL HIGHWAY, DESAMPARADOS (POB.), CALAPE, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(893, 'NATIONAL HIGHWAY TAGBILARAN EAST RD., TAONGON CABATUAN, DIMIAO, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(894, 'NATIONAL HIGHWAY CORELLA BALILIHAN ROAD, POBLACION, CORELLA, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(895, 'PROVINCIAL HIGHWAY, CABULIHAN, TUBIGON, BOHOL VISAYAS BOHOL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(896, 'CALLE REVOLUCION ST., POBLACION ILAYA, PANAY, CAPIZ VISAYAS CAPIZ SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(897, 'NATIONAL ROAD, POBLACION, JAMINDAN, CAPIZ VISAYAS CAPIZ SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(898, 'ROXAS AVENUE, POBLACION IX, CITY OF ROXAS , CAPIZ VISAYAS CAPIZ SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(899, 'HUGHES CORNER BURGOS STREET, TANQUE, CITY OF ROXAS , CAPIZ VISAYAS CAPIZ SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(900, 'SITIO POOK, CULASI, CITY OF ROXAS , CAPIZ VISAYAS CAPIZ SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(901, 'KM 1 NATIONAL ROAD, LAWA-AN, CITY OF ROXAS , CAPIZ VISAYAS CAPIZ SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(902, 'CORNER M.H DEL PILAR & M.L. ROXAS STS., POBLACION NORTE, IVISAN, CAPIZ VISAYAS CAPIZ SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(903, 'PROVINCIAL HIGHWAY POOC, POBLACION, SANTA FE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(904, 'NATIONAL ROAD, BINAOBAO (POB.), BANTAYAN, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(905, 'NATIONAL HIGHWAY OSMEÑIA ST., POBLACION, DAANBANTAYAN, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(906, 'DE LA VIÑA ST. COR. NEW BOGO MRK, GAIRAN, CITY OF BOGO, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(907, 'NORTH ROAD, LABOGON, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(908, 'NORTH ROAD, BASAK, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(909, 'CEBU NORTH COASTAL ROAD, PAKNA-AN, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(910, 'ALIWANAY RD, SANTA CRUZ-SANTO NINO (POB.), BALAMBAN, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(911, 'ANTONIO Y DE PIO NATIONAL HIGHWAY, BUANOY, BALAMBAN, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(912, 'NATIONAL HIGHWAY SITIO FERMINA, MAYA, DAANBANTAYAN, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(913, 'CEBU NORTH HAGNAYA WHARF RD., POLAMBATO, CITY OF BOGO, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(914, 'NATIONAL ROAD, POBLACION OCCIDENTAL, CONSOLACION, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(915, 'CONSOLACION-TAYUD-LILOAN RD., CANSAGA, CONSOLACION, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(916, 'BASAK-BUAYA ST., BASAK, CITY OF LAPU-LAPU, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(917, 'OUANO AVE. NORTH RECLAMATION AREA, TIPOLO, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(918, 'NATIONAL HIWAY ML QUEZON AVE., PUSOK, CITY OF LAPU-LAPU, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(919, 'M.V. PATALINGHUD JR AVE., BASAK, CITY OF LAPU-LAPU, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(920, 'M.V. PATALINGHUG JR. AVE., PAJO, CITY OF LAPU-LAPU, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(921, 'A.S. FORTUNA ST., BAKILID, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(922, 'GOV. M. CUENCO STREET, TALAMBAN, CITY OF LAPU-LAPU, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(923, 'GOV. M. CUENCO AVE. BORBAJO ST., TALAMBAN, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(924, 'NATIONAL HIGHWAY H. ABELLANA ST., CANDUMAN, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(925, 'MINI MARKET, DAPITAN, CORDOVA, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(926, 'M.L.QUEZON, MAGUIKAY, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(927, 'ML QUEZON STREET, CASUNTINGAN, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(928, 'M. L. QUEZON NATIONAL HIGHWAY, PAJO, CITY OF LAPU-LAPU, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(929, 'S.OSMEÑA BLVD. T. PADILLA ST., TEJERO, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(930, 'NATIONAL ROAD OSMEÑA ST., LOOC, CITY OF LAPU-LAPU, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(931, 'NATIONAL HIGHWAY SITIO HAWAYAN UNO, MARIGONDON, CITY OF LAPU-LAPU, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(932, 'G. LOPEZ JAENA AVE., SUBANGDAKU, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(933, 'G. LOPES JAENA STREET, TIPOLO, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(934, 'NATIONAL HIGHWAY, TIPOLO, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(935, 'NBT AREA M LOGARTA AVE., SUBANGDAKU, CITY OF MANDAUE, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(936, 'F. CABAHUG ST., MABOLO, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(937, 'GOV. M. CUENCO AVENUE, KASAMBAGAN, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(938, 'NATIONAL HIGHWAY, LAWAAN I, CITY OF TALISAY, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(939, 'SOUTH ROAD PROPERTIES CORNER LARAY, SAN ROQUE, CITY OF TALISAY, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(940, 'ESCARIO ST., CAMPUTHAW (POB.), CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(941, 'VICENTE RAMA AVENUE, CALAMBA, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22');
INSERT INTO `stations` (`id`, `name`, `location`, `status`, `created_at`, `updated_at`) VALUES
(942, 'V. RAMA AVENUE, GUADALUPE, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(943, 'SRP ENTRY ROAD, MAMBALING, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(944, 'B RODRIGUEZ S.T, CAPITOL SITE (POB.), CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(945, 'SOUTHROAD NATIONAL HIGHWAY, CALAJO-AN, MINGLANILLA, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(946, 'SOUTHROAD NATIONAL HIGHWAY, POBLACION WARD III, MINGLANILLA, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(947, 'CEBU SOUTH ROAD, TUNGHAAN, MINGLANILLA, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(948, 'CEBU SOUTH RD., TUNGHAAN, MINGLANILLA, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(949, 'F.LLAMAS ST., TISA, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(950, 'ARCHBISHOP REYES AVE. JUAN LUNA COR., LUZ, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(951, 'A. APOSTOL ST., TULAY, MINGLANILLA, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(952, 'KATIPUNAN STREET, TISA, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(953, 'SOUTHROAD NATIONAL HIGHWAY, INAYAGAN, CITY OF NAGA, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(954, 'N. BACALSO AVENUE, LABANGON, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(955, 'NATALIO B. BACALSO AVENUE, MAMBALING, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(956, 'CANDIDO PADILLA CORNER T. ABELLA ST. , TABOAN, CEBU CITY, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(957, 'N. BACALSO AVE , TABUNOC, CITY OF TALISAY, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(958, 'R. DUTERTE ST., GUADALUPE, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(959, 'N. BACALSO AVE., SAN NICOLAS CENTRAL, CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(960, 'RAFAEL C RABAYA ST., TABUNOC, CITY OF TALISAY, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(961, 'LEGASPI ST. CORNER JAKOSALEM, CENTRAL (POB.), CITY OF CEBU , CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(962, 'SALINAS DR, APAS, CEBU CITY, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(963, 'NATIONAL HIGHWAY, BITOON, DUMANJUG, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(964, 'PROVINCIAL HIGHWAY, POBLACION, GINATILAN, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(965, 'SOUTHROAD NATIONAL HIWAY PATROCINIO ST., POBLACION, BOLJOON, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(966, 'NATIONAL HIGHWAY, POBLACION, ALCOY, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(967, 'SOUTHROAD NATIONAL HIGHWAY, POBLACION, SANTANDER, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(968, 'SOUTHROAD NATIONAL HIGHWAY SITIO PAJO, POBLACION, SAMBOAN, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(969, 'DR JOSE RIZAL ST., POBLACION, CARCAR CITY, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(970, 'PROVINCIAL HIGHWAY SITIO LATID, POBLACION, PINAMUNGAJAN, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(971, 'PROVINCIAL HIGHWAY SITIO LATID, BATO, CITY OF TOLEDO, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(972, 'S. OSMEÑA ST., SANGI, CITY OF TOLEDO, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(973, 'CEBU-TOLEDO WHARF RD, JUAN CLIMACO, SR., CITY OF TOLEDO, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(974, 'DIOSDADO MACAPAGAL HIGHWAY, LURAY II, CITY OF TOLEDO, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(975, 'NATIONAL HIGHWAY, POBLACION, CITY OF TOLEDO, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(976, 'NATIONAL HIGHWAY, POBLACION EAST, MOALBOAL, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(977, 'NATIONAL HIGHWAY, POBLACION CENTRAL, DUMANJUG, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(978, 'SOUTHROAD NATIONAL HIGHWAY, TINAAN, CITY OF NAGA, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(979, 'NATIONAL HIGHWAY BO. AWAYAN, VALLADOLID, CITY OF CARCAR, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(980, 'NATIONAL HIGHWAY, TALO-OT, ARGAO, CEBU VISAYAS CEBU SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(981, 'NATIONAL HIGHWAY, BARANGAY 8 (POB.), SALCEDO, EASTERN SAMAR VISAYAS EASTERN SAMAR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(982, 'NATIONAL HIGHWAY ALIBHON, SAN MIGUEL, JORDAN , GUIMARAS VISAYAS GUIMARAS SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(983, 'NATIONAL ROAD TUPAS ST., TAN PAEL, TIGBAUAN, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(984, 'NATIONAL HIGHWAY, IGTUBA, MIAGAO, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(985, 'NATIONAL HIGHWAY, CABATAC, MAASIN, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(986, 'OLD ILOILO-CAPIZ RD., BARANGAY ZONE I (POB.), SANTA BARBARA, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(987, 'PANAY NEWS COMPOUND, MALI-AO, PAVIA, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(988, 'CIRCUMFERENTIAL RD. 1, PANDAC, PAVIA, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(989, 'ILOILO CITY- ALEOSAN RD., GUZMAN-JESENA, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(990, 'NATIONAL HIGHWAY, UNGKA II, PAVIA, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(991, 'NATIONAL HIGHWAY RIZAL ILAWOD ST., ZONE IX POB., CABATUAN, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(992, 'OLD ILOILO-CAPIZ RD., AYAMAN, CABATUAN, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(993, 'CORNER LUNA & HUERVANA STS., LUNA, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(994, 'M.H. DEL PILAR ST., TAAL, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(995, 'NATIONAL HIGHWAY ABETO ST., ABETO MIRASOL TAFT SOUTH, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(996, 'DIVERSION ROAD AQUINO AVENUE, BUHANG TAFT NORTH, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(997, 'R. MAPA ST., TABUCAN, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(998, 'CORNER STA ISABEL & LOPEZ JAENA STS., ARGUELLES, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(999, 'DONATO PISON AVENUE, TABUCAN, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1000, 'WEST DIVERSION ROAD, BOLILAO, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1001, 'TIMAWA STREET, WEST TIMAWA, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1002, 'NATIONAL HIGHWAY JC ZULUETA ST., POBLACION WEST, OTON, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1003, 'LUNA ST., RIZAL, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1004, 'NATIONAL HIGHWAY, QUINTIN SALAS, CITY OF ILOILO , ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1005, 'CORNER ARIMAS - MELLIZA STS., JALAUD NORTE, ZARRAGA, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1006, 'NATIONAL HIGGHWAY , BURGOS-REGIDOR (POB.), DUMANGAS, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1007, 'BAROTAC NUEVO - DUMANGAS RD., PD MONFORT NORTH, DUMANGAS, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1008, 'BAROTAC NUEVO-ZARRAGA RD, ILAYA POBLACION, BAROTAC NUEVO, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1009, 'NATIONAL ROAD, TABUCAN, BAROTAC NUEVO, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1010, 'NATIONAL ROAD, RUMBANG, POTOTAN, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1011, 'NATIONAL HIGHWAY, BUNTATALA, LEGANES, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1012, 'NATIONAL HIGHWAY, POBLACION ILAWOD, LAMBUNAO, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1013, 'DON VICTORINO SALCEDO STS., POBLACION MARKET, SARA, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1014, 'ILOILO RADIAL BYPASS ROAD 4, BUNTATALA, LEGANES, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1015, 'NATIONAL HIGHWAY GUSTILO ST., GUIHAMAN, LEGANES, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1016, 'F. PALMARES SR. ST., POBLACION ILAWOD, CITY OF PASSI, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1017, 'NATIONAL ROAD, MACALBANG, CONCEPCION, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1018, 'E. REYES AVE., POBLACION ZONE II, ESTANCIA, ILOILO VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1019, 'Coastal Road, Brgy. Camangay, Leganes, Iloilo VISAYAS ILOILO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1020, 'REAL ST., BARANGAY 50, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1021, 'RIZAL CORNER AVENIDA VETERANOS, BARANGAY 43, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1022, 'JUSTICE ROMUALDEZ ST. CORNER PATERNO ST., BARANGAY 25, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1023, 'NATIONAL HIGHWAY HOLLYWOOD SUBD., NULA-TULA, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1024, 'TABUAN NATIONAL HIGHWAY, BARANGAY 79, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1025, 'MAHARLIKA HIGHWAY, BARANGAY 92, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1026, 'EASTERN NAUTICAL HIGHWAY, PICAS NORTE, JAVIER, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1027, 'NATIONAL HIGHWAY, BARANGAY 96, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1028, 'SAGKAHAN - SAN JOSE JUNCTION, BARANGAY 83-C, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1029, 'MAHARLIKA HIGHWAY PUROK IV, BARANGAY 91, CITY OF TACLOBAN , LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1030, 'COR.CAMPETIC - PAWING ST., CAMPETIK, PALO, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1031, 'REAL ST. COR. OSMEÑA ST. , BARANGAY 19 (POB.), ORMOC CITY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1032, 'LILIA AVE., COGON COMBADO, ORMOC CITY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1033, 'D. VELOSO ST., COGON COMBADO, ORMOC CITY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1034, 'NATIONAL ROAD, SAN JOSE, SOGOD, SOUTHERN LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1035, 'JOSE P. RIZAL ST., TINAGO DISTRICT (POB.), BATO, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1036, 'PALO - CARIGARA - ORMOC CITY RD., VALENCIA, ORMOC CITY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1037, 'R.V. FULACHE STREET, EASTERN BARANGAY (POB.), HILONGOS, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1038, 'OSMEÑA ST., LIBERTAD, ORMOC CITY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1039, 'NATIONAL HIGHWAY, DANHUG, ORMOC CITY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1040, 'NATIONAL HIGHWAY SITIO PANALI-AN, DANHUG, ORMOC CITY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1041, 'CORNER MAGSAYSAY AVE. & TRESE MARTERES ST., POBLACION ZONE 15, CITY OF BAYBAY, LEYTE VISAYAS LEYTE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1042, 'NATIONAL HIGHWAY, SANTO ROSARIO, CITY OF BAYBAY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1043, 'NATIONAL HIGHWAY PASAY ROAD, PASAY, CITY OF MAASIN , SOUTHERN LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1044, 'REAL ST., CAYARE, SAN MIGUEL, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1045, 'NATIONAL HIGHWAY, SAN PEDRO, TUNGA, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1046, 'NATIONAL ROAD, SAN PABLO, ORMOC CITY, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1047, 'MAHARLIKA HIGHWAY, CAMPETIK PALO, LEYTE VISAYAS LEYTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1048, 'MABINI ST., BATA, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1049, 'ARANETA ST., TANGUB, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1050, 'BS AQUINO DRIVE, BARANGAY 5 (POB.), CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1051, 'THE SHOPHOUSE HERITAGE BS AQUINO DRIVE, VILLAMONTE, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1052, 'CIRCUMFERENTIAL ROAD, VILLAMONTE, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1053, 'ARANETA CORNER LIZARES ST., BARANGAY 39 (POB.), CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS O', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1054, 'GENERAL LUNA ST., POBLACION, CITY OF BAGO, NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1055, 'LIBERTAD EXTENSION CORNER VISTA ALEGRE, MANSILINGAN, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEG', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1056, 'NATIONAL HIGHWAY ALIJIS MURCIA ROAD, ALIJIS, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1057, 'LOPEZ JAENA ST., BARANGAY 27 (POB.), CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1058, 'KM 8 88 ARANETA ST. SUM AG ROAD, SUM-AG, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1059, 'BUENA PARK SUBDIVISION BURGOS AVE., VILLAMONTE, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS O', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1060, 'MONTELIBANO AVE., VILLAMONTE, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1061, 'COR. LACSON - MAGSAYSAY RD., TACULING, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1062, 'CIRCUMFERENTIAL ROAD, BATA, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1063, 'NGC GROUNDS CIRCUMFERENTIAL RD., VILLAMONTE, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1064, 'RIZAL COR LOCSIN STS., BARANGAY 11 (POB.), CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1065, 'NATIONAL HIGHWAY ARANETA ST., SUM-AG, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1066, '13.5 KM. NEGROS SOUTH ROAD, TALOC, CITY OF BAGO, NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1067, '16TH ST. COR LACSON ST., BARANGAY 4 (POB.), CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCID', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1068, 'NATIONAL HI-WAY ALIJIS RD., MANSILINGAN, CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1069, '24TH LACSON, BARANGAY 5 (POB.), CITY OF BACOLOD , NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1070, 'RIZAL STREET, BARANGAY 7 (POB.), CITY OF KABANKALAN, NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1071, 'CORNER BROCE - CARMONA ST., BARANGAY III (POB.), CITY OF SAN CARLOS, NEGROS OCCIDENTAL VISAYAS NEGRO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1072, 'JESUS PEREZ COR. GUANZON STS., BARANGAY 2 (POB.), CITY OF KABANKALAN, NEGROS OCCIDENTAL VISAYAS NEGR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1073, 'NATIONAL ROAD, GARGATO, HINIGARAN, NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1074, 'POBLACION, HIMAMAYLAN CITY NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1075, 'NATIONAL ROAD MABINAY ST., BATO, MABINAY, NEGROS ORIENTAL VISAYAS NEGROS OCCIDENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1076, 'NATIONAL ROAD, ZONE 12 (POB.), ENRIQUE B. MAGALONA, NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1077, 'NATIONAL ROAD RIZAL ST., BARANGAY II (POB.), CITY OF SILAY, NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDEN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1078, 'NATIONAL ROAD HDA AMIGOS 3, TINAMPA-AN, CITY OF CADIZ, NEGROS OCCIDENTAL VISAYAS NEGROS OCCIDENTAL S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1079, 'JOSE ROMERO RD. COR. LAMBERTO MACIAS RD., TABUCTUBIG, CITY OF DUMAGUETE , NEGROS ORIENTAL VISAYAS NE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1080, 'NATIONAL HIGHWAY, WEST POBLACION, BACONG, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1081, 'NATIONAL ROAD, SAN MIGUEL, BACONG, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1082, 'NATIONAL ROAD, POBLACION, SAN JOSE, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1083, 'PUROK 4 NATIONAL HIGHWAY, POBLACION, SANTA CATALINA, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1084, 'NATIONAL ROAD, MANGNAO-CANAL, CITY OF DUMAGUETE , NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1085, 'NATIONAL ROAD, POBLACION III, DAUIN, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1086, 'NATIONAL HIGHWAY QUEZON ST., TAMOGONG, CITY OF BAIS, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1087, 'REAL CORNER SAN JOSE STS., POBLACION NO. 7, CITY OF DUMAGUETE , NEGROS ORIENTAL VISAYAS NEGROS ORIEN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1088, 'S VILLEGAS CORNER MAGSAYSAY BLVD., POBLACION, CITY OF GUIHULNGAN, NEGROS ORIENTAL VISAYAS NEGROS ORI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1089, 'NATIONAL ROAD CORNER DIVINAGRACIA ST., POBLACION, SIBULAN, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1090, 'NATIONAL HGHWAY, TINAGO (POB.), CITY OF BAYAWAN, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1091, 'NATIONAL ROAD, AGAN-AN, SIBULAN, NEGROS ORIENTAL VISAYAS NEGROS ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1092, 'DEL ROSARIO STREET, POBLACION 8, CITY OF CATBALOGAN , SAMAR VISAYAS SAMAR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1093, 'NATIONAL ROAD, MACAGTAS, CATARMAN , NORTHERN SAMAR VISAYAS SAMAR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1094, 'NATIONAL HIGHWAY, SAN JORGE II (POB.), SAN JORGE, SAMAR (WESTERN SAMAR) VISAYAS SAMAR SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1095, 'PAN PHILIPPINE HIGHWAY, TINAMBACAN NORTE, CITY OF CALBAYOG, SAMAR VISAYAS SAMAR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1096, 'NATIONAL ROAD, TRINIDAD, CITY OF CALBAYOG, SAMAR VISAYAS SAMAR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1097, 'MAHARLIKA HIGHWAY, CAPOOCAN, CITY OF CALBAYOG, SAMAR VISAYAS SAMAR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1098, 'ALLEN AVE.COR SAN FRANCISCO ST., GUINDAPONAN, CITY OF CATBALOGAN , SAMAR (WESTERN SAMAR) VISAYAS SAM', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1099, 'NATIONAL HIGHWAY, CAN-ABONG, CITY OF BORONGAN , EASTERN SAMAR VISAYAS SAMAR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1100, 'NATIONAL HIGHWAY, POBLACION, SAN JUAN, SIQUIJOR VISAYAS SIQUIJOR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1101, 'SAYRE HIGHWAY, LINABO, CITY OF MALAYBALAY , BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1102, 'SAYRE HIGHWAY FORTICH ST., BARANGAY 1 (POB.), CITY OF MALAYBALAY , BUKIDNON MINDANAO BUKIDNON SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1103, 'RECTO AVENUE-OSMEÑA ST., BARANGAY 24 (POB.), CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1104, 'COR. QUEZON AVE. & ECHAVEZ ST., CENTRAL (POB.), CITY OF DIPOLOG , ZAMBOANGA DEL NORTE MINDANAO ZAMBO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1105, 'COR. ARAULLO & JAMISOLA STS., GATAS (POB.), CITY OF PAGADIAN , ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1106, 'PUROK 4 APOKON ROAD COR.TIMOG AVE., APOKON, CITY OF TAGUM , DAVAO DEL NORTE MINDANAO AGUSAN DEL NORT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1107, 'NATIONAL HIGHWAY BAAN, MAHAY, CITY OF BUTUAN , AGUSAN DEL NORTE MINDANAO AGUSAN DEL NORTE SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1108, 'J. C. AQUINO AVE. COR. DOONGAN ROAD, BONBON, CITY OF BUTUAN , AGUSAN DEL NORTE MINDANAO AGUSAN DEL N', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1109, 'PUROK 2 BUTUAN-CAGAYAN DE ORO-ILIGAN RD., BARANGAY 19 (POB.), CITY OF GINGOOG, MISAMIS ORIENTAL MIND', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1110, 'SALVADOR CALO ST., LIBERTAD, CITY OF BUTUAN , AGUSAN DEL NORTE MINDANAO AGUSAN DEL NORTE SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1111, 'NATIONAL HIGHWAY, BANCASI, CITY OF BUTUAN , AGUSAN DEL NORTE MINDANAO AGUSAN DEL NORTE SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1112, 'VILLANUEVA ST COR. G. FLORES AVE., VILLA KANANGA, CITY OF BUTUAN , AGUSAN DEL NORTE MINDANAO AGUSAN ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1113, 'NATIONAL HIGHWAY PUROK 4 AMPAYON , LEMON, CITY OF BUTUAN , AGUSAN DEL NORTE MINDANAO AGUSAN DEL NORT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1114, 'S. CALO ST., VILLA KANANGA, CITY OF BUTUAN , AGUSAN DEL NORTE MINDANAO AGUSAN DEL NORTE SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1115, 'JT DOMINGO COR. ROMULO ROSALES(LANGIHAN) ST., BAYANIHAN POB., CITY OF BUTUAN , AGUSAN DEL NORTE MIND', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1116, 'NATIONAL HIGHWAY, KAUSWAGAN, CITY OF CABADBARAN, AGUSAN DEL NORTE MINDANAO AGUSAN DEL NORTE SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1117, 'PUROK 5 NATIONAL HIGHWAY, MABINI, CITY OF CABADBARAN, AGUSAN DEL NORTE MINDANAO AGUSAN DEL NORTE SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1118, 'NATIONAL HIGHWAY PUROK GUMAMELA, SANTA CRUZ, ROSARIO, AGUSAN DEL SUR MINDANAO AGUSAN DEL SUR SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1119, 'NATIONAL HIGHWAY, BARANGAY 4 (POB.), SAN FRANCISCO, AGUSAN DEL SUR MINDANAO AGUSAN DEL SUR SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1120, 'PAN PHILIPPINE HIGHWAY PUROK 5, CUEVAS, TRENTO, AGUSAN DEL SUR MINDANAO AGUSAN DEL SUR SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1121, 'NATIONAL HIGHWAY, BARANGAY 3 (POB.), SAN FRANCISCO, AGUSAN DEL SUR MINDANAO AGUSAN DEL SUR SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1122, 'NATIONAL HIGHWAY, CAMP I, MARAMAG, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1123, 'SAYRE HIGHWAY, EAST KIBAWE (POB.), KIBAWE, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1124, 'PUROK 12 SAYRE HIGHWAY, POBLACION, CITY OF VALENCIA, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1125, 'PUROK 17 C SAYRE HIGHWAY, POBLACION, CITY OF VALENCIA, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1126, 'NATIONAL ROAD, BARANGAY 4 (POB.), TALAKAG, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1127, 'NATIONAL HIGHWAY, BARANGAY 2 (POB.), TALAKAG, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1128, 'CAMP PHILIPS, AGUSAN CANYON, MANOLO FORTICH, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1129, 'DIVERSION ROAD CROSSING LANDING, BARANGAY 4 (POB.), CITY OF MALAYBALAY , BUKIDNON MINDANAO BUKIDNON ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1130, 'PUROK 5SAYRE NATIONAL HIGHWAY, SAN JOSE, CITY OF MALAYBALAY , BUKIDNON MINDANAO BUKIDNON SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1131, 'SAYRE NATIONAL HIGHWAY P-1, LUMBO, CITY OF VALENCIA, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1132, 'NATIONAL ROAD SAYRE HIGHWAY, BAGONTAAS, CITY OF VALENCIA, BUKIDNON MINDANAO BUKIDNON SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1133, 'CATALUNA ST., KADI, SEN. NINOY AQUINO, SULTAN KUDARAT MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1134, 'PUBLIC MARKET AREA, BUAL, TULUNAN, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1135, 'NATIONAL HIGHWAY, POBLACION, CARMEN, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1136, 'NATIONAL HIGHWAY, NEW CULASI, TULUNAN, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1137, 'NATIONAL HIGHWAY MATALAM-SIBSIB RD., LIKA, M\'LANG, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1138, 'NATIONAL HIGHWAY, POBLACION, KABACAN, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1139, 'NATIONAL HWAY COR ZAMORA ST., POBLACION, KABACAN, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1140, 'BLK 9, ROSARY HEIGHTS 9 NATIONAL HIGHWAY, ROSARY HEIGHTS VI, CITY OF COTABATO, COTABATO CITY MINDANA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1141, 'BY-PASS ROAD BO. MALAGAPAS, POBLACION VIII, CITY OF COTABATO, COTABATO MINDANAO COTABATO SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1142, 'NATIONAL HIGHWAY, POBLACION, MIDSAYAP, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1143, 'SINSUAT AVE., ROSARY HEIGHTS VII, CITY OF COTABATO, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1144, 'COR. SINSUAT & DON SERO ROSARY HEIGHTS, ROSARY HEIGHTS II, CITY OF COTABATO, COTABATO MINDANAO COTAB', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1145, 'NATIONAL HIGHWAY, POBLACION, PRESIDENT ROXAS, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1146, 'NATIONAL HIGHWAY, LANAO, CITY OF KIDAPAWAN , COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1147, 'NATIONAL HIGHWAY, POBLACION, MAKILALA, COTABATO MINDANAO COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1148, 'MC ARTHUR HIGHWAY, MATINA CROSSING, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1149, 'NATIONAL HIGHWAY, POBLACION, MONKAYO, DAVAO DE ORO MINDANAO DAVAO DE ORO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1150, 'NATIONAL HIGHWAY PUROK 7, POBLACION, NABUNTURAN , DAVAO DE ORO MINDANAO DAVAO DE ORO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1151, 'NATIONAL HIGHWAY PUROK 2, POBLACION, NABUNTURAN , DAVAO DE ORO MINDANAO DAVAO DE ORO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1152, 'MONTEVISTA - COMPOSTELA - MATI BOUNDARY RD., BAGONGON, COMPOSTELA, DAVAO DE ORO MINDANAO DAVAO DE OR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1153, 'NATIONAL HIGHWAY, LITTLE PANAY, CITY OF PANABO, DAVAO DEL NORTE MINDANAO DAVAO DEL NORTE SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1154, 'PANABO WHARF ROAD, J.P. LAUREL, CITY OF PANABO, DAVAO DEL NORTE MINDANAO DAVAO DEL NORTE SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1155, 'NATIONAL HIGHWAY, TAGPORE, CITY OF PANABO, DAVAO DEL NORTE MINDANAO DAVAO DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1156, 'NATIONAL HIGHWAY, SINDATON, CITY OF PANABO, DAVAO DEL NORTE MINDANAO DAVAO DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1157, 'NATIONAL HIGHWAY PUROK CATTLEYA, VISAYAN VILLAGE, CITY OF TAGUM , DAVAO DEL NORTE MINDANAO DAVAO DEL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1158, 'CAPITOL RD., MANKILAM, CITY OF TAGUM , DAVAO DEL NORTE MINDANAO DAVAO DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1159, 'GAZMEN COMPOUND PUROK TALISAY CAPITOL CIRCUMFERENTIAL RD., MAGUGPO WEST, CITY OF TAGUM , DAVAO DEL N', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1160, 'PUROK 13 CAPITOL ROAD, SAN MIGUEL, CITY OF TAGUM , DAVAO DEL NORTE MINDANAO DAVAO DEL NORTE SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1161, 'NATIONAL HIGHWAY PUROK 6, ISING (POB.), CARMEN, DAVAO DEL NORTE MINDANAO DAVAO DEL NORTE SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1162, 'NATIONAL HIGHWAY COR NORTH CIRCUMFERENTIAL RD., MAGUGPO NORTH, CITY OF TAGUM , DAVAO DEL NORTE MINDA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1163, 'NATIONAL HIGHWAY, MAGUGPO POBLACION, CITY OF TAGUM , DAVAO DEL NORTE MINDANAO DAVAO DEL NORTE SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1164, 'NATIONAL HIGHWAY PUROK NARRA , VISAYAN VILLAGE, CITY OF TAGUM , DAVAO DEL NORTE MINDANAO DAVAO DEL N', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1165, 'MC ARTHUR HIGHWAY, BARANGAY 5-A (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1166, 'NATIONAL HIGHWAY, MINTAL, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1167, 'MCARTHUR NATIONAL HIGHWAY, TORIL (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1168, 'PAN PHILIPPINE HIGHWAY MCARTHUR HIGHWAY, TORIL (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO D', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1169, 'JULIAN RODRIGUEZ SR. AVE. MA-A , MATINA CROSSING, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SU', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1170, 'DAANG MAHARLIKA HIGHWAY KM 9, SASA, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1171, 'TIGATTO ROAD, BUHANGIN (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1172, 'KM 8 MCARTHUR HIGHWAY ULAS, TALOMO (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1173, 'KM 5 BUHANGIN ROAD, BUHANGIN (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1174, 'QUIMPO BLVD. COR. ACACIA ECOLAND, BUCANA, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1175, 'MC ARTHUR HIGHWAY, MATINA CROSSING, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1176, 'NATIONAL HIGHWAY POBLACION, CALINAN (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1177, 'PAN-PHILIPPINE HIGHWAY LIBBY ROAD PUAN, BAGO GALLERA, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1178, 'PUROK 4 RAMBUTAN ST., TUGBOK (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1179, 'PUROK 10 CALINAN-CAWAYAN COR. CALINAN-WANGAN RD., SUBASTA, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAV', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1180, 'J. P. LAUREL AVE. BAJADA, BARANGAY 20-B (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1181, 'MCARTHUR HIGHWAY BANGKAL, TALOMO (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1182, 'CATALUNAN GRANDE RD., CATALUNAN GRANDE, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1183, 'MAGYSAYSAY AVE. COR. LEON GUERRERO ST., BARANGAY 30-C (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1184, 'DAVA0-PANABO CITY RD. KM 10, SASA, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1185, 'PUROK 5 SAN JUAN, TIBUNGCO, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1186, 'R. CATILLO ST., GOV. VICENTE DUTERTE, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1187, 'COR. GEMESAW & MONTEVERDE STS., BARANGAY 27-C (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1188, 'DIVERSION ROAD BUHANGIN, COMMUNAL, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1189, 'JP LAUREL AVENUE LANANG SAN ANTONIO, RAFAEL CASTILLO, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1190, 'TALISAY COR. R. CASTILLO ST., SAN ANTONIO, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1191, 'KM13 DIVERSION ROAD, PANACAN, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1192, 'TIGATTO DIVERSION ROAD, BUHANGIN (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1193, 'KM18 NATIONAL HIGHWAY, TIBUNGCO, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1194, 'BACACA ROAD, BARANGAY 19-B (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1195, 'MANDUG RD. BUHANGIN DISTRICT, MANDUG, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1196, 'CABANTIAN DIVERSION ROAD, BUHANGIN (POB.), CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1197, 'KM 13 DAVAO - AGUSAN RD., BUNAWAN, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1198, 'COUNTRYVILLE EXECUTIVE HOMES, CABANTIAN, CITY OF DAVAO, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1199, 'NATIONAL HIGHWAY, BALUTAKAY, HAGONOY, DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1200, 'JOSE ABAD SANTOS ST., ZONE 3 (POB.), CITY OF DIGOS , DAVAO DEL SUR MINDANAO DAVAO DEL SUR SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1201, 'J.P. RIZAL AVE.NATIONAL HIGHWAY, ZONE 3 (POB.), CITY OF DIGOS , DAVAO DEL SUR MINDANAO DAVAO DEL SUR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1202, 'NATIONAL HIGHWAY, KINANGA, DON MARCELINO, DAVAO OCCIDENTAL MINDANAO DAVAO OCCIDENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1203, 'DEMOLOC - LITTLE BAGUIO - ALABEL RD., POBLACION, MALITA , DAVAO OCCIDENTAL MINDANAO DAVAO OCCIDENTAL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1204, 'NATIONAL HIGHWAY, DAHICAN, CITY OF MATI , DAVAO ORIENTAL MINDANAO DAVAO ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1205, 'QUEZON AVE. COR. SINSUAT AVE., POBLACION, CITY OF KIDAPAWAN , COTABATO MINDANAO KIDAPAWAN, COTABATO ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1206, 'NATIONAL HIGHWAY QUEZON BLVD., SUDAPIN, CITY OF KIDAPAWAN , COTABATO MINDANAO KIDAPAWAN, COTABATO SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1207, 'NAT\'IONAL HIGHWAY, MARANDING, LALA, LANAO DEL NORTE MINDANAO LANAO DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1208, 'NATIONAL HIGHWAY, SAGADAN , BAROY, LANAO DEL NORTE MINDANAO LANAO DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1209, 'NATIONAL HIGHWAY, VILLA VERDE, CITY OF ILIGAN, LANAO DEL NORTE MINDANAO LANAO DEL NORTE SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1210, 'NATIONAL HIGHWAY, TUBOD, CITY OF ILIGAN, LANAO DEL NORTE MINDANAO LANAO DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1211, 'NATIONAL HIGHWAY COR. VICENTA SHEAK, SAN MIGUEL, CITY OF ILIGAN, LANAO DEL NORTE MINDANAO LANAO DEL ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1212, 'J. LUNA - GEN. LLUCH STS., POBLACION, CITY OF ILIGAN, LANAO DEL NORTE MINDANAO LANAO DEL NORTE SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1213, 'R. JEFFREY RD. EXT. VILLA QUEZON AVE., PALAO, CITY OF ILIGAN, LANAO DEL NORTE MINDANAO LANAO DEL NOR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1214, 'TOMAS CABILI, CENAURI VILLAGE, TOMINOBO PROPER PUROK 5-A, ILIGAN CITY MINDANAO LANAO DEL NORTE SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1215, 'NATIONAL HIGHWAY, BRGY. TOMINOBO, ILIGAN CITY MINDANAO LANAO DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1216, 'NATIONAL HIGHWAY, POBLACION, LUGAIT, MISAMIS ORIENTAL MINDANAO LUGAIT, MISAMIS ORIENTAL SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1217, 'JP LIM COR. VILO STS., CALSADA, SULTAN KUDARAT, MAGUINDANAO MINDANAO MAGUINDANAO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1218, 'NATIONAL HIGHWAY, GADUNGANPEDPANDARAN, PARANG, MAGUINDANAO MINDANAO MAGUINDANAO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1219, 'HAYES-VICENTE ROA STS., NAZARETH, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1220, 'COR. LUNA & WASHINGTON STS., POBLACION II, CITY OF OROQUIETA , MISAMIS OCCIDENTAL MINDANAO MISAMIS O', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1221, 'CAPITOL DRIVE COR. SEN.J.OZAMIS ST., LAMAC LOWER, CITY OF OROQUIETA , MISAMIS OCCIDENTAL MINDANAO MI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1222, 'NATIONAL HIGHWAY, TALIC, CITY OF OROQUIETA , MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1223, 'NATIONAL HIGHWAY, POBLACION, BONIFACIO, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1224, 'NATIONAL HIGHWAY, LIBERTAD BAJO, SINACABAN, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1225, 'NATIONAL HIGHWAY, GANGO, CITY OF OZAMIZ, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1226, 'NATIONAL HIGHWAY, SANTA MARIA, CITY OF TANGUB, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1227, 'NATIONAL HIGHWAY PUROK 1, SANTA CRUZ (POB.), JIMENEZ, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1228, 'NATIONAL HIGHWAY, SINONOC, SINACABAN, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1229, 'NATIONAL HIGHWAY, LAM-AN, CITY OF OZAMIZ, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1230, 'NATIONAL HIGHWAY RIZAL AVENUE P-1 , LAM-AN, CITY OF OZAMIZ, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1231, 'CIRCUMFERENTIAL ROAD, BACOLOD, CITY OF OZAMIZ, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1232, 'RIZAL AVENUE CORNER BURGOS ST., 50TH DISTRICT (POB.), CITY OF OZAMIZ, MISAMIS OCCIDENTAL MINDANAO MI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1233, 'NATIONAL HIGHWAY, TAGUIMA, TUDELA, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1234, 'NATIONAL HIGHWAY. BERNAD AVE., BACOLOD, CITY OF OZAMIZ, MISAMIS OCCIDENTAL MINDANAO MISAMIS OCCIDENT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1235, 'NATIONAL HIGHWAY, QUEZON, GITAGUM, MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1236, 'BUTUAN CAGAYAN DE ORO ILIGAN RD, POBLACION, MANTICAO, MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1237, 'NATIONAL HIWAY BUTUAN-CAGAYAN DE ORO-ILIGAN RD. P3, POBLACION, NAAWAN, MISAMIS ORIENTAL MINDANAO MIS', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1238, 'NATIONAL HIGHWAY, BULUA, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1239, 'ZONE 3 BUTUAN-CAGAYAN DE ORO-ILIGAN RD., TABOC, OPOL, MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1240, 'NATIONAL ROAD ALUBA, MACASANDIG, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1241, 'JR BORJA EXTENSION, GUSA, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1242, 'FR MASTERSON AVE. XAVIER STATES, BALULANG, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1243, 'NATIONAL HIGHWAY, PUERTO, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1244, 'NATIONAL HIGHWAY ALA-E, PUERTO, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1245, 'NATIONAL HIGHWAY PUROK 4, TABLON, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1246, 'NATIONAL HIGHWAY KINASANGHAN ST., IPONAN, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1247, 'SM CITY AREA, BALULANG, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1248, 'NATIONAL HIGHWAY, CUGMAN, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22');
INSERT INTO `stations` (`id`, `name`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1249, 'ZAYAZ ROAD, CARMEN, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1250, 'P-3 NHA HIGHWAY ZONE 5, KAUSWAGAN, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1251, 'VAMENTA BLVD.COR. MAX SUNIEL ST., CARMEN, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1252, 'NATIONAL HIGHWAY, GUSA, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1253, 'VAMENTA BLVD., CARMEN, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1254, 'NATIONAL HIGHWAY ZONE 11, POBLACION, LAGUINDINGAN, MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1255, 'ZONE 7 , CARMEN, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1256, 'LAPASAN GAABUCAYAN ST. EXTENSION DISTRICT, PUNTOD, CITY OF CAGAYAN DE ORO , MISAMIS ORIENTAL MINDANA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1257, 'NATIONAL HIGHWAY, SAN ALONZO, BALINGOAN, MISAMIS ORIENTAL MINDANAO MISAMIS ORIENTAL SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1258, 'PUROK ROSAS, POBLACION, KIAMBA, SARANGANI MINDANAO SARANGANI SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1259, 'COR. BULAONG TERMINAL, CITY HEIGHTS, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABATO ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1260, 'NLSA ROAD PUROK BAYANIHAN, SAN ISIDRO, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1261, 'NLSA ROAD COR. VILLAREAL ST., LAGAO, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABATO ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1262, 'NATIONAL HIGHWAY, CITY HEIGHTS, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1263, 'PAN PHILIPPINE HIGHWAY, CITY HEIGHTS, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABATO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1264, 'PUROK 13 MANUHAY RD., SAN ISIDRO, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABATO SER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1265, 'SANTIAGO AVE. COR. J. CATOLICO SR., LAGAO, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1266, 'LEON LLIDO ST., LAGAO, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1267, 'J. CATOLICO AVENUE, LAGAO, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE ST', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1268, 'P. ACHARON BLVD., DADIANGAS EAST (POB.), CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTAB', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1269, 'SINAWAL ROAD, SINAWAL, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1270, 'NATIONAL HIHWAY LABOS ST., CITY HEIGHTS, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH COTAB', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1271, 'NATIONAL HIGHWAY COR. RIVERA ST., CALUMPANG, CITY OF GENERAL SANTOS, SOUTH COTABATO MINDANAO SOUTH C', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1272, 'AVD ZONE 5, LIBERTAD (POB.), SURALLAH, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1273, 'NATIONAL HIGHWAY SAMPAGUITA ST. , POBLACION, POLOMOLOK, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1274, 'NATIONAL HIGHWAY, SAN ISIDRO, SANTO NIÑO, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1275, 'G.P. SANTOS ST., POBLACION, NORALA, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1276, 'NATIONAL HIGHWAY, PARAISO, CITY OF KORONADAL , SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1277, 'NATIONAL HIGHWAY, LINAN, TUPI, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1278, 'PUROK SAN MIGUEL, POBLACION, POLOMOLOK, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1279, 'GENSAN DRIVE ZONE 3 , GENERAL PAULINO SANTOS, CITY OF KORONADAL , SOUTH COTABATO MINDANAO SOUTH COTA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1280, 'PUROK 10 NATIONAL HIGHWAY, POBLACION, TUPI, SOUTH COTABATO MINDANAO SOUTH COTABATO SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1281, 'MARKET AREA, POBLACION, ESPERANZA, SULTAN KUDARAT MINDANAO SULTAN KUDARAT SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1282, 'NATIONAL HIGHWAY, DANSULI, ISULAN , SULTAN KUDARAT MINDANAO SULTAN KUDARAT SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1283, 'NATIONAL HIGHWAY, CHUA, BAGUMBAYAN, SULTAN KUDARAT MINDANAO SULTAN KUDARAT SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1284, 'NATIONAL HIGHWAY, UPPER KATUNGAL, CITY OF TACURONG, SULTAN KUDARAT MINDANAO SULTAN KUDARAT SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1285, 'ALUNAN HIGHWAY, POBLACION, CITY OF TACURONG, SULTAN KUDARAT MINDANAO SULTAN KUDARAT SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1286, 'NATIONAL HIGHWAY, KALAWAG III (POB.), ISULAN , SULTAN KUDARAT MINDANAO SULTAN KUDARAT SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1287, 'NATIONAL HIGHWAY KM4, LUNA, CITY OF SURIGAO , SURIGAO DEL NORTE MINDANAO SURIGAO DEL NORTE SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1288, 'COR. NAVARRO & SPINA STS., CANLANIPA, CITY OF SURIGAO , SURIGAO DEL NORTE MINDANAO SURIGAO DEL NORTE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1289, 'NATIONAL HIGHWAY, BAD-AS, PLACER, SURIGAO DEL NORTE MINDANAO SURIGAO DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1290, 'NATIONAL HIGHWAY, WASHINGTON (POB.), CITY OF SURIGAO , SURIGAO DEL NORTE MINDANAO SURIGAO DEL NORTE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1291, 'DAPA-GENERAL LUNA ROAD RIZAL ST., SAN JOSE (POB.), DEL CARMEN, SURIGAO DEL NORTE MINDANAO SURIGAO DE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1292, 'NATIONAL HIGHWAY, MANGAGOY, CITY OF BISLIG, SURIGAO DEL SUR MINDANAO SURIGAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1293, 'CABRERA ST. COR. SURIGAO-DAVAO COASTAL RD., BAGONG LUNGSOD (POB.), CITY OF TANDAG , SURIGAO DEL SUR ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1294, 'NATIONAL HIGHWAY, LINIBONAN, MADRID, SURIGAO DEL SUR MINDANAO SURIGAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1295, 'NATIONAL HIGHWAY, PURISIMA (POB.), TAGO, SURIGAO DEL SUR MINDANAO SURIGAO DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1296, 'NATIONAL HIGHWAY, SACA (POB.), CARRASCAL, SURIGAO DEL SUR MINDANAO SURIGAO, DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1297, 'NATIONAL HGIHWAY, MABUA, CITY OF TANDAG , SURIGAO DEL SUR MINDANAO SURIGAO, DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1298, 'NATIONAL HIGHWAY, BARANGAY UNO (POB.), KATIPUNAN, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL NORTE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1299, 'NATIONAL HIGHWAY PUNTA ST., DAANGLUNGSOD, KATIPUNAN, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL NORT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1300, 'NATIONAL HIGHWAY DON JOSE CARREON ST., POLO, CITY OF DAPITAN, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1301, 'DIPOLOG - POLANCO -OROQUIETA RD., VILLAHERMOSA, POLANCO, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1302, 'DIPOLOG - ZAMBOANGA HIGHWAY, SANTA FILOMENA, CITY OF DIPOLOG , ZAMBOANGA DEL NORTE MINDANAO ZAMBOANG', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1303, 'HOSPICIO OCHOTERENA ST., BAGTING (POB.), CITY OF DAPITAN, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1304, 'NATIONAL HIGHWAY, OBAY, POLANCO, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL NORTE SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1305, 'NATIONAL HIGHWAY, TURNO, CITY OF DIPOLOG , ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL NORTE SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1306, 'CARPITANOS BROS. RD. COR. SANTA ISABEL, SANTA FILOMENA, CITY OF DIPOLOG , ZAMBOANGA DEL NORTE MINDAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1307, 'NATIONAL HIGHWAY, BRGY. DISUD, SINDANGAN, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL NORTE SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1308, 'NATIONAL HIGHWAY, BANTAYAN, SINDANGAN, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL NORTE SERVICE STAT', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1309, 'NATIONAL HIGHWAY, LA ROCHE SAN MIGUEL, SINDANGAN, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL NORTE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1310, 'NATIONAL HIGHWAY, BACUNGAN (POB.), BACUNGAN, ZAMBOANGA DEL NORTE MINDANAO ZAMBOANGA DEL NORTE SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1311, 'NATIONAL HIGHWAY, BUENAVISTA, CITY OF PAGADIAN , ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR SERVIC', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1312, 'NATIONAL HIGHWAY RIZAL ST. PUROK 5, POBLACION B, MIDSALIP, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1313, 'NATIONAL HIGHWAY PUROK 9 ALANG ALANG, POBLACION, RAMON MAGSAYSAY, ZAMBOANGA DEL SUR MINDANAO ZAMBOAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1314, 'PAGADIAN CITY - ZAMBOANGA CITY RD., NEW LABANGAN, LABANGAN, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1315, 'PUROK ROSAS COR. SABELLANO & PULM, SAN PEDRO (POB.), CITY OF PAGADIAN , ZAMBOANGA DEL SUR MINDANAO Z', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1316, 'NATIONAL HIGHWAY PUROK 1, LOWER SALUG DAKU, MAHAYAG, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR SE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1317, 'NATIONAL HIGHWAY, BOLOBOLO, CITY OF EL SALVADOR, MISAMIS ORIENTAL MINDANAO ZAMBOANGA DEL SUR SERVICE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1318, 'GOV. RAMOS AVE., STA MARIA, ZAMBOANGA CITY, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR SERVICE STA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1319, 'GOVERNOR RAMOS ST. STA MARIA , CANELAR, CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1320, 'NATIONAL HIGHWAY, LOWER TAWAY, IPIL , ZAMBOANGA SIBUGAY MINDANAO ZAMBOANGA DEL SUR SERVICE STATION', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1321, 'NATIONAL HIGHWAY GOV. CAMINS AVE., BARANGAY ZONE III (POB.), CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1322, 'CADENA DE AMOR ST., TETUAN, CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR SERVICE ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1323, 'MCLL HIGHWAY, SANGALI, CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR SERVICE STATI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1324, 'MERCEDES RD., TETUAN, CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1325, 'NATIONAL HIGHWAY SUTTERVILLE ST., CAMPO ISLAM, CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOAN', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1326, 'SAN JOSE ROAD ZONE 2 CORNER G.E LADESMA ST.& BUENAVISTA ST., SANTO NIÑO, CITY OF ZAMBOANGA, ZAMBOANG', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1327, 'GOV. LIM AVENUE, BARANGAY ZONE IV (POB.), CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1328, 'NUNEZ EXTENSION, BARANGAY ZONE I (POB.), CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1329, 'MCLL HIGHWAY, TETUAN, CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1330, 'NATIONAL ROAD, CAWIT, CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR SERVICE STATIO', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1331, 'VETERANS AVE. CORNER DON TORIBIO ST., SANTA BARBARA, CITY OF ZAMBOANGA, ZAMBOANGA DEL SUR MINDANAO Z', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1332, 'PUROK 5 NATIONAL HIGHWAY PUROK LILANG, POBLACION, SOMINOT, ZAMBOANGA DEL SUR MINDANAO ZAMBONGAL DEL ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1333, 'NATIONAL HIGHWAY, RIVERSIDE (POB.), TAMBULIG, ZAMBOANGA DEL SUR MINDANAO ZAMBONGAL DEL SUR SERVICE S', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1334, 'E RODRIGUEZ JR AVE UGONG PASIG CITY 1604 NCR PASIG TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1335, 'SHAW BOULEVARD BRGY. WACK WACK MANDALUYONG CITY NCR MANDALUYONG TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1336, 'KM 44 NORTHBOUND BRGY MAPAGONG CALAMBA LAGUNA (MATES) NCR LAGUNA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1337, 'KM 44 SOUTHBOUND BRGY MAPAGONG CALAMBA LAGUNA (PNCC) NCR LAGUNA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1338, 'KM. 22 NORTH DIVERSION ROAD, LIAS MARILAO, BULACAN NCR BULACAN TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1339, 'LA VISTA KATIPUNAN AVE CORNER MANGYAN RD, QUEZON CITY NCR KATIPUNAN TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1340, 'MAKATI AVE., CORNER SEN. GIL PUYAT AVE., MAKATI CITY NCR MAKATI TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1341, 'PACIFIC RIM CORNER COMMERCE AVENUE FILINVEST, MUNTINLUPA NCR MUNTINLUPA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1342, 'PASONG TAMO EXTENSION, MAKATI CITY NCR MAKATI TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1343, 'PETRON GOV. FERRER PINAGTIPUNAN GEN TRIAS CAVITE NCR CAVITE TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1344, 'PETRON JP RIZAL, JP RIZAL COR. SPAIN ST. CONCEPCION UNO MARIKINA CITY NCR MARIKINA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1345, 'PETRON VIOLAGO HOMES PARKWOOD AREA B LITEX ROAD PAYATAS QC NCR QUEZON CITY TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1346, 'SMC HOC 40 SAN MIGUEL AVE. MANDALUYONG CITY NCR MANDALUYONG TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1347, 'SOUTH EXPRESSWAY, SAN ANTONIO, SAN PEDRO, LAGUNA NCR LAGUNA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1348, 'TREATS NAIA, TERMINDANAOAL 3 BARANGAY 184, PASAY CITY NCR PASAY TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1349, '30KM SOUTHBOUND LANE, BOCAUE, BULACAN NORTH LUZON BULACAN TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1350, 'CLARK FREEPORT ZONE NORTH LUZON PAMPANGA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1351, 'GIMIKAN-PORTION OF FORMER GIMIKAN, FREEPORT AREA, RIZAL HIGHWAY, CENTRAL BUSINESS DISTRICT NORTH LUZ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1352, 'HARRISON ROAD, BAGUIO CITY NORTH LUZON BAGUIO TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1353, 'KM 42 BRGY. STO NINO NLEX,PLARIDEL BULACAN NORTH LUZON PLARIDEL, BULACAN TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1354, 'KM 71 NORTH LUZON EXPRESSWAY, MEXICO PAMPANGA NORTH LUZON PAMPANGA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1355, 'MAHARLIKA HIGHWAY, BRGY. CAANAWAN, SAN JOSE CITY, NUEVA ECIJA NORTH LUZON NUEVA ECIJA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1356, 'NATIONAL HIGHWAY, BRGY. SISON, ROSARIO, LA UNION NORTH LUZON LA UNION TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1357, 'PBR ROMAN HI-WAY, ALANGAN LIMAY, BATAAN NORTH LUZON BATAAN TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1358, 'PETRON MALASIN, MAHARLIKA RD MALASIN, SAN JOSE CITY NORTH LUZON NUEVA ECIJA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1359, 'PETRON MC ARTHUR HIWAY TABUN MABALACAT PAMPANGA NORTH LUZON PAMPANGA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1360, 'PETRON TPLEX KM 134 SOUTH BOUND, POROC PURA TARLAC NORTH LUZON TARLAC TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1361, 'PETRON TPLEX NORTH BOUND, POROC PURA TARLAC NORTH LUZON TARLAC TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1362, 'RIZAL BLVD. COR. ARGONAUT HIGHWAY, SUBIC BAY FREEPORT ZONE, OLONGAPO, ZAMBALES NORTH LUZON ZAMBALES ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1363, 'SAN FERNANDO CITY LA UNION NORTH LUZON LA UNION TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1364, 'BRGY. TIBIG, STAR TOLLWAYS, LIPA CITY, BATANGAS SOUTH LUZON BATANGAS TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1365, 'KAYBAGAL SOUTH, TAGAYTAY CITY, CAVITE SOUTH LUZON TAGAYTAY TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1366, 'PETRON CONCEPCION, NATIONAL HIWAY CONCEPCION NAGA PEQUENA NAGA C SOUTH LUZON NAGA CITY TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1367, 'PETRON DAANG HARI, LOT 1 VERSAILLES SUBD DAANG HARI RD ALMANZA DOS LAS PIÑAS CITY SOUTH LUZON LAS PI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1368, 'PETRON ETON CITY, BLK 11 LOT 1 WALK 3 ETON CITY MALITLIT STA. ROSA CITY LAGUNA SOUTH LUZON LAGUNA TR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1369, 'PETRON KM 74-75 STAR TOLLWAY SAN ANDRES MALVAR BATANGAS SOUTH LUZON LIPA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1370, 'PETRON NAGA, MAGSAYSAY AVE., CONCEPCION PEQUENA NAGA CITY SOUTH LUZON NAGA CITY TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1371, 'PETRON NAGA, PANGANIBAN DRIVE TINAGO NAGA CITY SOUTH LUZON NAGA CITY TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1372, 'PETRON SAN PABLO, KM81 MAHARLIKA HIGHWAY BRGY. SAN ROQUE SAN PABLO SOUTH LUZON LAGUNA TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1373, 'STAR TOLLWAYS, SAN JOSE, BATANGAS SOUTH LUZON BATANGAS TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1374, 'CATICLAN AIRPORT INTERIM BUILDING, CATICLAN, MALAY, AKLAN VISAYAS AKLAN TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1375, 'PETRON DAMPAS TAGBILARAN CITY 6300 VISAYAS TAGBILARAN TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1376, '50TH DISTRICT OZAMIZ CITY MISC OCC 7200 MINDANAO OZAMIS TREATS STORE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1377, 'XAVIERVILLE AVE., LOYOLA HIGHTS, QUEZON CITY NCR QUEZON CITY CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1378, 'P-3 NHA HIGHWAY, ZONE 5, KAUSWAGAN, CAGAYAN DE ORO CITY NCR CAGAYAN DE ORO CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1379, 'COVERED PARKING AREA 3, SM CITY, SAN MIGUEL ST., LAGAO, GEN. SANTOS CITY, SOUTH COTABATO NCR SOUTH C', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1380, 'BRGY. BOQUIG, BANTAY, ILOCOS SUR NORTH LUZON ILOCOS SUR CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1381, 'MAHARLIKA HIGHWAY, BATAL, SANTIAGO CITY, ISABELA NORTH LUZON ISABELA CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1382, 'ABANGAN NORTE, MARILAO, BULACAN NORTH LUZON BULACAN CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1383, 'QUIRINO HIGHWAY, SJDM, BULACAN NORTH LUZON BULACAN CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1384, 'FMC-LTO CMPD. TALON 1, LAS PIÑAS CITY NORTH LUZON LAS PIÑAS CITY CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1385, 'BRGY. TIGAYON, KALIBO, AKLAN NORTH LUZON AKLAN CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1386, '13TH COR. HILADO ST., BACOLOD CITY, NEGROS OCCIDENTAL NORTH LUZON NEGROS OCCIDENTAL CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1387, 'JOCSON ST., BRGY. DULONAN, AREVALO, ILOILO CITY NORTH LUZON ILOILO CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1388, 'C.P. GARCIA HIGHWAY, BUHANGIN, DAVAO CITY NORTH LUZON DAVAO CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1389, 'NATIONAL HIGHWAY, BRGY. CITY HEIGHTS, GEN. SANTOS CITY, SOUTH COTABATO NORTH LUZON SOUTH COTABATO CA', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1390, 'BUNTUN HIGHWAY, TUGUEGARAO CITY, CAGAYAN SOUTH LUZON CAGAYAN CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1391, 'MANGOBO ST.MANGHINAO BAUAN, BATANGAS SOUTH LUZON BATANGAS CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1392, '9092 GEN. TIRONA HIGHWAY, BACOOR CITY, CAVITE SOUTH LUZON CAVITE CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1393, 'NATIONAL HIGHWAY, BRGY. BARCENAGA, NAUJAN, ORIENTAL MINDORO SOUTH LUZON ORIENTAL MINDORO CAR CARE CE', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1394, '123 MCARTHUR HIGWAY, BRGY MATINA CROSSING DAVAO CITY SOUTH LUZON DAVAO CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1395, 'FS DIZON ST., EL RIO, BACACA, DAVAO CITY SOUTH LUZON DAVAO CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1396, 'NATIONAL HIGHWAY, BRGY. BO. LANAO, KIDAPAWAN, SOUTH LUZON NORTH COTOBATO CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1397, 'BUHAY NA TUBIG, IMUS CAVITE SOUTH LUZON CAVITE CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1398, 'CALASIAO-DAGUPAN ROAD, BRGY. NALSIAN, CALASIAO, PANGASINAN VISAYAS PANGASINAN CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1399, 'UN AVE. COR ROMUALDEZ ST., PACO, MANILA VISAYAS MANILA CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1400, 'MAHARLIKA EAST, TAGAYTAY CITY, CAVITE VISAYAS CAVITE CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1401, 'CURVADA, NATIONAL HIGHWAY, TAGUM CITY, DAVAO DEL NORTE VISAYAS DAVAO DEL NORTE CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1402, 'BRGY. DELA PAZ, MARCOS HIGHWAY, PASIG CITY MINDANAO PASIG CITY CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1403, 'DIVERSION HIWAY, BRGY. BANGKUSAY, SAN FERNANDO, LA UNION MINDANAO QUEZON CITY CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1404, '354 STA. RITA RD., STA. RITA, OLONGAPO CITY, ZAMBALES MINDANAO ZAMBALES CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1405, 'ROTONDA, DINALUPIHAN, BATAAN MINDANAO BATAAN CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1406, '69 D. TUAZON ST. LOURDES 1, QUEZON CITY MINDANAO QUEZON CITY CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1407, 'PASIG BLVD/SAN IGNACIO,CAPITOL 8 SUBD, PASIG CITY MINDANAO PASIG CITY CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1408, 'METROWALK COMPLEX, MERALCO AVE., BRGY. UGONG, PASIG CITY MINDANAO QUEZON CITY CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1409, 'ELIJAH PETRON SERVICE STATION, MOLINO BLVD., BACOOR, CAVITE MINDANAO CAVITE CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1410, 'MOLINO – PALIPARAN ROAD, PROMENADE SOUTH, BRGY SALAWAG, DASMARINAS CITY MINDANAO DASMARINAS CITY CAR', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1411, 'CORNER HUGHES AND BURGOS ST., ROXAS CITY, CAPIZ MINDANAO CAPIZ CAR CARE CENTER', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1412, 'NEW VILLANUEVA COMMERCIAL DISTRICT CORP., NATIONAL HIGHWAY, VILLANUEVA, MISAMIS ORIENTAL MINDANAO MI', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22'),
(1413, 'FS PAJARES AVE., SAN JOSE DISTRICT, PAGADIAN CITY, ZAMBOANGA DEL SUR MINDANAO ZAMBOANGA DEL SUR CAR ', '', 'active', '2026-02-07 12:14:22', '2026-02-07 12:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `station_inventory`
--

CREATE TABLE `station_inventory` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stock_level` decimal(12,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 0,
  `capacity` decimal(12,2) DEFAULT 10000.00,
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_confirmations`
--

CREATE TABLE `supplier_confirmations` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `confirmed_by` varchar(255) NOT NULL,
  `confirmation_date` datetime NOT NULL,
  `delivery_schedule` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Confirmed','Rescheduled','Cancelled') DEFAULT 'Confirmed',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','manager','operations_staff','staff') NOT NULL DEFAULT 'staff',
  `hourly_rate` decimal(10,2) DEFAULT 150.00,
  `email` varchar(150) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `station_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `emp_id`, `username`, `password`, `role`, `hourly_rate`, `email`, `name`, `station_id`, `status`, `is_deleted`, `deleted_at`, `deleted_by`, `created_at`) VALUES
(1, NULL, 'superadmin', '$2y$10$vIH4dgr29FVfXbKyaa7U1OrCMx4ZuetW1ZwRDwuQbsYLLwdqA/36i', 'superadmin', 150.00, 'superadmin@petron.com', 'Super Admin', NULL, 'active', 0, NULL, NULL, '2026-02-06 01:37:41'),
(2, NULL, 'admin', 'amie', 'admin', 150.00, 'admin@petron.com', 'Admin', NULL, 'active', 0, NULL, NULL, '2026-02-06 01:37:41'),
(3, NULL, 'manager', 'manager123', 'manager', 150.00, 'manager@petron.com', 'Station Manager', 1, 'active', 0, NULL, NULL, '2026-02-06 01:37:41'),
(4, NULL, 'operations', 'operations123', 'operations_staff', 150.00, 'operations@petron.com', 'Operations Staff', 1, 'active', 0, NULL, NULL, '2026-02-06 01:37:41'),
(5, NULL, 'juan.carlo', '$2y$10$FPAZIKierCzy9Tr/PqVa.u.sicyWM9r8SAy7JO5994UyoVXQx3BZS', 'staff', 150.00, NULL, 'Juan Carlo', NULL, 'active', 0, NULL, NULL, '2026-02-07 20:16:27'),
(6, NULL, 'carla', '$2y$10$vJkcoK1ekWO3iNtL7P5BI.uY4piTKR4nMGL5PCFRP.Y1rk7robiNG', 'staff', 150.00, NULL, 'Carla', NULL, 'active', 0, NULL, NULL, '2026-02-07 20:16:27'),
(7, NULL, 'miguel', '$2y$10$E75FtVdHlD3vW1x.sM6Ec..MdvemI7f6O7QrYjptofXftaKHe9/x2', 'staff', 150.00, NULL, 'Miguel', NULL, 'active', 0, NULL, NULL, '2026-02-07 20:16:27'),
(8, NULL, 'andrea', '$2y$10$fpazu0ufqlPUCUM416C4m.17bc9dPNWvoVQ1c8.0HsSk.OEjO.Aem', 'staff', 150.00, NULL, 'Andrea', NULL, 'active', 0, NULL, NULL, '2026-02-07 20:16:27'),
(9, NULL, 'mark', '$2y$10$R67TPE3vadfh1pmYJ33ruOKeroOWs42AbCXAxyD4wUvkvSJlBmpMK', 'staff', 150.00, NULL, 'Mark', NULL, 'active', 0, NULL, NULL, '2026-02-07 20:16:28'),
(14, NULL, 'amie', '$2y$10$J6iLJbFR3vr2OUKgoyYgmen2A3BXDgBDW4qAs2IDPM8ijzT.kNaNa', 'admin', 150.00, 'amie@gmail.com', 'amie cabahug', 226, 'active', 0, NULL, NULL, '2026-02-07 20:19:20'),
(15, NULL, 'altea', '$2y$10$1Enh9TVIihLWCzAmBb9YHeZiGnj6u.FSY7VgeG5zCy4TM5vLt0/hO', 'staff', 150.00, 'altea@gmail.com', 'ALTEA PAGALING', 226, 'active', 0, NULL, NULL, '2026-02-07 20:41:54'),
(16, NULL, 'sandara', '$2y$10$4.LhjZIYKEOb051MFO172uoQYdR8L2fVCkbceof15EAKA2LyKL0O.', 'manager', 150.00, 'sandara@gmail.com', 'pagaling,sandara jane m.', 226, 'active', 0, NULL, NULL, '2026-02-07 20:42:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_customer_station` (`station_id`);

--
-- Indexes for table `customer_credit_transactions`
--
ALTER TABLE `customer_credit_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cct_customer` (`customer_id`),
  ADD KEY `fk_cct_station` (`station_id`),
  ADD KEY `fk_cct_creator` (`created_by`);

--
-- Indexes for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ledger_customer` (`customer_id`);

--
-- Indexes for table `customer_statements`
--
ALTER TABLE `customer_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cs_customer` (`customer_id`),
  ADD KEY `fk_cs_generator` (`generated_by`);

--
-- Indexes for table `daily_reconciliation`
--
ALTER TABLE `daily_reconciliation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_station_date` (`station_id`,`reconciliation_date`),
  ADD KEY `fk_daily_station` (`station_id`),
  ADD KEY `fk_daily_shift_report` (`shift_report_id`),
  ADD KEY `fk_daily_verifier` (`verified_by`);

--
-- Indexes for table `fuel_adjustments`
--
ALTER TABLE `fuel_adjustments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fuel_daily_readings`
--
ALTER TABLE `fuel_daily_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reading_station` (`station_id`),
  ADD KEY `fk_reading_pump` (`pump_id`),
  ADD KEY `fk_reading_user` (`user_id`);

--
-- Indexes for table `fuel_deliveries`
--
ALTER TABLE `fuel_deliveries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fuel_pumps`
--
ALTER TABLE `fuel_pumps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_station_pump` (`station_id`,`pump_number`),
  ADD KEY `fk_pump_station` (`station_id`),
  ADD KEY `fk_pump_fuel_type` (`fuel_type_id`);

--
-- Indexes for table `fuel_reconciliation`
--
ALTER TABLE `fuel_reconciliation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fuel_rec_station` (`station_id`),
  ADD KEY `fk_fuel_rec_fuel_type` (`fuel_type_id`),
  ADD KEY `fk_fuel_rec_pump` (`pump_id`),
  ADD KEY `fk_fuel_rec_verifier` (`verified_by`);

--
-- Indexes for table `fuel_types`
--
ALTER TABLE `fuel_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_fuel_type_name` (`name`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_station_product` (`station_id`,`product_id`),
  ADD KEY `fk_inventory_station` (`station_id`),
  ADD KEY `fk_inventory_product` (`product_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_trans_station` (`station_id`),
  ADD KEY `fk_inv_trans_product` (`product_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `job_orders`
--
ALTER TABLE `job_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_job_order_number` (`job_order_number`),
  ADD KEY `fk_job_station` (`station_id`),
  ADD KEY `fk_job_customer` (`customer_id`),
  ADD KEY `fk_job_service_category` (`service_category_id`),
  ADD KEY `fk_job_mechanic` (`assigned_mechanic_id`),
  ADD KEY `fk_job_assigner` (`assigned_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_requires_approval` (`requires_approval`),
  ADD KEY `idx_station_status` (`station_id`,`status`),
  ADD KEY `fk_job_reviewed_by` (`reviewed_by`),
  ADD KEY `fk_job_approved_by` (`approved_by`);

--
-- Indexes for table `job_order_parts`
--
ALTER TABLE `job_order_parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jop_job_order` (`job_order_id`),
  ADD KEY `fk_jop_product` (`product_id`);

--
-- Indexes for table `labor_sessions`
--
ALTER TABLE `labor_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_labor_user` (`user_id`),
  ADD KEY `fk_labor_station` (`station_id`);

--
-- Indexes for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_loyalty_customer` (`customer_id`);

--
-- Indexes for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mechanic_station` (`station_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notification_user` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sku` (`sku`),
  ADD KEY `fk_product_type` (`type_id`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_category_name` (`name`);

--
-- Indexes for table `product_types`
--
ALTER TABLE `product_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_type_name` (`name`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_po_number` (`po_number`),
  ADD KEY `fk_po_station` (`station_id`),
  ADD KEY `fk_po_supplier` (`supplier_id`),
  ADD KEY `fk_po_creator` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_poi_po` (`po_id`),
  ADD KEY `fk_poi_product` (`product_id`),
  ADD KEY `fk_poi_receiver` (`received_by`);

--
-- Indexes for table `reports_cache`
--
ALTER TABLE `reports_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type_date` (`report_type`,`report_date`),
  ADD KEY `idx_station_date` (`station_id`,`report_date`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sale_customer` (`customer_id`),
  ADD KEY `fk_sale_user` (`user_id`),
  ADD KEY `fk_sale_station` (`station_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sale_item_sale` (`sale_id`),
  ADD KEY `fk_sale_item_product` (`product_id`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_service_category_name` (`name`);

--
-- Indexes for table `service_entries`
--
ALTER TABLE `service_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_service_number` (`service_number`),
  ADD KEY `fk_service_station` (`station_id`),
  ADD KEY `fk_service_category` (`service_category_id`),
  ADD KEY `fk_service_customer` (`customer_id`),
  ADD KEY `fk_service_staff` (`assigned_staff_id`),
  ADD KEY `fk_service_mechanic` (`mechanic_id`);

--
-- Indexes for table `service_parts_used`
--
ALTER TABLE `service_parts_used`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_service_part_service` (`service_id`),
  ADD KEY `fk_service_part_product` (`product_id`);

--
-- Indexes for table `service_rates`
--
ALTER TABLE `service_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rate_service_category` (`service_category_id`),
  ADD KEY `fk_rate_station` (`station_id`);

--
-- Indexes for table `shift_reports`
--
ALTER TABLE `shift_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_shift_station` (`station_id`),
  ADD KEY `fk_shift_creator` (`created_by`),
  ADD KEY `fk_shift_finalizer` (`finalized_by`);

--
-- Indexes for table `stations`
--
ALTER TABLE `stations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_station_name` (`name`);

--
-- Indexes for table `station_inventory`
--
ALTER TABLE `station_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_station_product` (`station_id`,`product_id`),
  ADD KEY `fk_station_product_station` (`station_id`),
  ADD KEY `fk_station_product_product` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_supplier_name` (`name`);

--
-- Indexes for table `supplier_confirmations`
--
ALTER TABLE `supplier_confirmations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sc_po` (`po_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_username` (`username`),
  ADD UNIQUE KEY `uk_emp_id` (`emp_id`),
  ADD KEY `fk_user_station` (`station_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `customer_credit_transactions`
--
ALTER TABLE `customer_credit_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_statements`
--
ALTER TABLE `customer_statements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_reconciliation`
--
ALTER TABLE `daily_reconciliation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_adjustments`
--
ALTER TABLE `fuel_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_daily_readings`
--
ALTER TABLE `fuel_daily_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fuel_deliveries`
--
ALTER TABLE `fuel_deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_pumps`
--
ALTER TABLE `fuel_pumps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fuel_reconciliation`
--
ALTER TABLE `fuel_reconciliation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fuel_types`
--
ALTER TABLE `fuel_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_orders`
--
ALTER TABLE `job_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `job_order_parts`
--
ALTER TABLE `job_order_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `labor_sessions`
--
ALTER TABLE `labor_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mechanics`
--
ALTER TABLE `mechanics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=235;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_types`
--
ALTER TABLE `product_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports_cache`
--
ALTER TABLE `reports_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `service_entries`
--
ALTER TABLE `service_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_parts_used`
--
ALTER TABLE `service_parts_used`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_rates`
--
ALTER TABLE `service_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `shift_reports`
--
ALTER TABLE `shift_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stations`
--
ALTER TABLE `stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1414;

--
-- AUTO_INCREMENT for table `station_inventory`
--
ALTER TABLE `station_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_confirmations`
--
ALTER TABLE `supplier_confirmations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customer_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_credit_transactions`
--
ALTER TABLE `customer_credit_transactions`
  ADD CONSTRAINT `fk_cct_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_cct_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_cct_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Constraints for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  ADD CONSTRAINT `fk_ledger_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `customer_statements`
--
ALTER TABLE `customer_statements`
  ADD CONSTRAINT `fk_cs_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_cs_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `daily_reconciliation`
--
ALTER TABLE `daily_reconciliation`
  ADD CONSTRAINT `fk_daily_shift_report` FOREIGN KEY (`shift_report_id`) REFERENCES `shift_reports` (`id`),
  ADD CONSTRAINT `fk_daily_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `fk_daily_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `fuel_daily_readings`
--
ALTER TABLE `fuel_daily_readings`
  ADD CONSTRAINT `fk_reading_pump` FOREIGN KEY (`pump_id`) REFERENCES `fuel_pumps` (`id`),
  ADD CONSTRAINT `fk_reading_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `fk_reading_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `fuel_pumps`
--
ALTER TABLE `fuel_pumps`
  ADD CONSTRAINT `fk_pump_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`),
  ADD CONSTRAINT `fk_pump_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Constraints for table `fuel_reconciliation`
--
ALTER TABLE `fuel_reconciliation`
  ADD CONSTRAINT `fk_fuel_rec_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`),
  ADD CONSTRAINT `fk_fuel_rec_pump` FOREIGN KEY (`pump_id`) REFERENCES `fuel_pumps` (`id`),
  ADD CONSTRAINT `fk_fuel_rec_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `fk_fuel_rec_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventory_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `fk_inv_trans_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_inv_trans_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Constraints for table `job_orders`
--
ALTER TABLE `job_orders`
  ADD CONSTRAINT `fk_job_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_job_assigner` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_job_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_job_mechanic` FOREIGN KEY (`assigned_mechanic_id`) REFERENCES `mechanics` (`id`),
  ADD CONSTRAINT `fk_job_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_job_service_category` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`),
  ADD CONSTRAINT `fk_job_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Constraints for table `job_order_parts`
--
ALTER TABLE `job_order_parts`
  ADD CONSTRAINT `fk_jop_job_order` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jop_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `labor_sessions`
--
ALTER TABLE `labor_sessions`
  ADD CONSTRAINT `fk_labor_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `fk_labor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD CONSTRAINT `fk_loyalty_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD CONSTRAINT `fk_mechanic_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`),
  ADD CONSTRAINT `fk_product_type` FOREIGN KEY (`type_id`) REFERENCES `product_types` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_po_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_poi_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_poi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_poi_receiver` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sale_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_sale_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `fk_sale_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_sale_item_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_entries`
--
ALTER TABLE `service_entries`
  ADD CONSTRAINT `fk_service_category` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`),
  ADD CONSTRAINT `fk_service_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_service_mechanic` FOREIGN KEY (`mechanic_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_service_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_service_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Constraints for table `service_parts_used`
--
ALTER TABLE `service_parts_used`
  ADD CONSTRAINT `fk_service_part_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_service_part_service` FOREIGN KEY (`service_id`) REFERENCES `service_entries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_rates`
--
ALTER TABLE `service_rates`
  ADD CONSTRAINT `fk_rate_service_category` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`),
  ADD CONSTRAINT `fk_rate_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Constraints for table `shift_reports`
--
ALTER TABLE `shift_reports`
  ADD CONSTRAINT `fk_shift_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_shift_finalizer` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_shift_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Constraints for table `station_inventory`
--
ALTER TABLE `station_inventory`
  ADD CONSTRAINT `fk_station_product_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_station_product_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_confirmations`
--
ALTER TABLE `supplier_confirmations`
  ADD CONSTRAINT `fk_sc_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
