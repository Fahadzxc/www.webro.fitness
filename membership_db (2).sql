-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 10:34 AM
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
-- Database: `membership_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 23, 'member_reset', '4 members automatically reset from new status', '2025-05-06 21:39:39'),
(2, 23, 'member_reset', '4 members automatically reset from new status', '2025-05-14 15:03:19'),
(3, 23, 'member_reset', '2 members automatically reset from new status', '2025-05-16 13:30:15');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent') DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `member_id`, `attendance_date`, `status`) VALUES
(28, 23, '2025-04-12', 'Absent'),
(32, 23, '2025-04-13', 'Absent'),
(34, 23, '2025-04-14', 'Absent'),
(40, 23, '2025-04-15', 'Absent'),
(44, 23, '2025-04-21', 'Absent'),
(48, 52, '2025-04-21', 'Absent'),
(49, 52, '2025-04-22', 'Present'),
(50, 23, '2025-04-29', 'Absent'),
(54, 52, '2025-04-29', 'Absent'),
(57, 52, '2025-04-30', 'Present'),
(60, 23, '2025-05-01', 'Absent'),
(63, 52, '2025-05-01', 'Absent'),
(65, 23, '2025-05-02', 'Absent'),
(67, 52, '2025-05-02', 'Absent'),
(68, 23, '2025-05-04', 'Absent'),
(72, 52, '2025-05-04', 'Absent'),
(73, 53, '2025-05-04', 'Absent'),
(74, 53, '2025-05-05', 'Present'),
(75, 23, '2025-05-05', 'Present'),
(79, 52, '2025-05-05', 'Absent'),
(87, 57, '2025-05-05', 'Absent'),
(88, 57, '2025-05-06', 'Present'),
(89, 58, '2025-05-05', 'Absent'),
(90, 58, '2025-05-06', 'Present'),
(92, 23, '2025-05-08', 'Absent'),
(95, 52, '2025-05-08', 'Absent'),
(96, 53, '2025-05-08', 'Absent'),
(98, 57, '2025-05-08', 'Absent'),
(99, 58, '2025-05-08', 'Absent'),
(105, 61, '2025-05-08', 'Absent'),
(106, 61, '2025-05-09', 'Present'),
(107, 23, '2025-05-13', 'Absent'),
(110, 52, '2025-05-13', 'Absent'),
(111, 53, '2025-05-13', 'Absent'),
(113, 57, '2025-05-13', 'Absent'),
(114, 58, '2025-05-13', 'Absent'),
(116, 61, '2025-05-13', 'Absent'),
(119, 23, '2025-05-14', 'Absent'),
(120, 52, '2025-05-14', 'Absent'),
(121, 53, '2025-05-14', 'Absent'),
(122, 57, '2025-05-14', 'Absent'),
(123, 58, '2025-05-14', 'Absent'),
(125, 61, '2025-05-14', 'Absent'),
(127, 23, '2025-05-15', 'Absent'),
(129, 52, '2025-05-15', 'Absent'),
(130, 53, '2025-05-15', 'Absent'),
(131, 57, '2025-05-15', 'Absent'),
(132, 58, '2025-05-15', 'Absent'),
(134, 61, '2025-05-15', 'Absent'),
(143, 57, '2025-05-16', 'Present'),
(144, 62, '2025-05-15', 'Absent'),
(145, 62, '2025-05-16', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `member_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_date` date NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `invoice_number` varchar(255) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `member_id`, `amount`, `due_date`, `status`, `created_at`, `invoice_number`, `subscription_id`) VALUES
(1, 1, 250.00, '2025-04-09', 'Paid', '2025-04-09 11:19:41', NULL, NULL),
(2, 1, 25000.00, '2025-04-30', 'Paid', '2025-04-30 06:44:33', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('Active','Cancelled','Pending') DEFAULT 'Active',
  `joined_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `name`, `email`, `phone`, `status`, `joined_date`, `updated_at`) VALUES
(1, 'zyf', 'zyfkupal@gmail.com', '09551168026', 'Cancelled', '2025-04-09 11:19:22', '2025-04-30 06:18:04'),
(2, 'karma nagisa', 'fahadalalawi1815@gmail.com', '09188181186', 'Active', '2025-04-13 16:21:00', '2025-04-13 16:21:00'),
(3, 'corpuz', 'corpuzlovepetpeeves@gmail.com', '09383838338', 'Active', '2025-04-22 12:24:54', '2025-04-22 12:24:54'),
(4, 'zyftt', 'brincelatognaaso@gmail.com', '09383838338', 'Active', '2025-04-30 06:16:47', '2025-04-30 06:16:47');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`, `expires_at`) VALUES
(7, 'radrieljhon@gmail.com', '8da2938b62613f2b60fdb017b2b048aa', '2025-04-01 00:51:49', '2025-03-31 19:51:49'),
(8, 'radrieljhon@gmail.com', '02b7717621a18808cbb5ac41ddc6cb2e', '2025-04-01 00:52:56', '2025-03-31 19:52:56'),
(9, 'radrieljhon@gmail.com', '53f39d3e5ac5c22a8c33716d4f8c3e21', '2025-04-01 00:54:04', '2025-03-31 19:54:04'),
(10, 'radrieljhon@gmail.com', '8acd1c3f83e84c5460d4fca05eae57f1', '2025-04-01 00:54:08', '2025-03-31 19:54:08'),
(11, 'radrieljhon@gmail.com', '398496372246e3ceede5880fc574b5c4', '2025-04-01 00:56:49', '2025-03-31 19:56:49'),
(14, 'radrieljhon@gmail.com', '449ef5c0e1501104de7b994bbbd0b281', '2025-04-05 08:14:26', '2025-04-05 03:14:26'),
(15, 'radrieljhon@gmail.com', 'ed0314cb0607e1fd6de9dc27d17128d7', '2025-04-05 08:14:31', '2025-04-05 03:14:31');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `user_id`, `amount`, `payment_date`, `due_date`, `status`, `member_id`) VALUES
(4, NULL, 9999.00, '2025-04-30', NULL, NULL, 52),
(15, NULL, 9999.00, '2025-05-09', NULL, NULL, 61),
(30, NULL, 2999.00, '2025-05-16', NULL, NULL, 62);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `item`, `amount`, `sale_date`) VALUES
(1, 'subscription', 250.00, '2025-04-08 16:00:00'),
(2, 'membership', 250.00, '2025-04-12 16:00:00'),
(3, 'subscription', 250.00, '2025-04-21 16:00:00'),
(4, 'subscription', 250.00, '2025-04-29 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `member_id`, `start_date`, `end_date`, `amount`, `status`) VALUES
(40, 61, '2025-05-09', '2026-05-09', 9999.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `is_new_member` tinyint(1) DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `membership_status` varchar(50) NOT NULL DEFAULT 'Active',
  `creator_id` int(11) DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `firstName`, `lastName`, `email`, `is_new_member`, `password`, `created_at`, `role`, `profile_picture`, `membership_status`, `creator_id`, `joined_date`, `status`, `updated_at`) VALUES
(23, 'nagisa1', 'karma', 'nagisa', 'nagisa@gmail.com', 0, '$2y$10$QPZT6fWW1WBKxDCq2dJk9eGpksJ5ySre0XnTbZ/Vs5xDrTi4hr8Y2', '2025-03-30 05:56:52', 'admin', NULL, 'Active', NULL, NULL, NULL, NULL),
(52, 'fahad', 'admin', 'mohamad', 'fahadalalawi1815@gmail.com', 0, '$2y$10$/Jfio0UJNb3ig9aHXSOEr.5wzKxZD3RUdyO8yW3PgfGbDztTa7jnS', '2025-04-22 12:25:57', 'user', NULL, 'Active', NULL, '2025-04-22', NULL, NULL),
(53, 'ben', 'ivan', 'diga', 'brincelatognaaso@gmail.com', 0, '$2y$10$tSLIyyOk5uvDXEPBr/IGduNScf9T7SxiJq7UmyndRsvWi6z31CFsG', '2025-05-05 17:10:19', 'user', NULL, 'Active', NULL, '2025-05-06', NULL, NULL),
(57, 'admin', 'Fahad', 'hhahah', 'ha@gmail.com', 0, '$2y$10$Sg2GV3QHN69EwXp/0ij9WeK3iV3IHwRJAtUS9yjB5bBuWsir2OA6e', '2025-05-06 09:44:39', 'user', NULL, 'Active', NULL, '2025-05-06', NULL, NULL),
(58, 'nevercry4you', 'admin', 'corpuz', 'lala@gmail.com', 0, '$2y$10$fOdkLSvf.yJHQ1mnyOw4d.joZlQc0uqAL7lpyBspUuJNcnMIzNU8q', '2025-05-06 09:48:33', 'user', NULL, 'Active', NULL, '2025-05-06', NULL, NULL),
(61, 'borgar', 'borgar', 'borgar', 'borgar@gmail.com', 0, '$2y$10$3SJNoQIl26b45VLgS3gM6OrV/O5J5JpGvdFqwFgyuw5WD1NJ8GhuG', '2025-05-09 06:22:16', 'user', NULL, 'Active', NULL, NULL, NULL, NULL),
(62, 'esyot', 'zyf', 'mohamad', 'lalaa@gmail.com', 1, '$2y$10$288TDPcnEyemfroGWGMbmub/vHZgVtR3klc3OSA8qLy5chnF.y21a', '2025-05-16 06:43:36', 'user', NULL, 'Active', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_checkin` datetime DEFAULT NULL,
  `total_visits` int(11) DEFAULT 0,
  `active_days` int(11) DEFAULT 0,
  `remaining_sessions` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_stats`
--
ALTER TABLE `user_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `user_stats`
--
ALTER TABLE `user_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_stats`
--
ALTER TABLE `user_stats`
  ADD CONSTRAINT `user_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
