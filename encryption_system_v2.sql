-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 07, 2026 at 07:09 AM
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
-- Database: `encryption_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int(11) NOT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `purok_street` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city_municipality` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `zip_code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`address_id`, `id_number`, `purok_street`, `barangay`, `city_municipality`, `province`, `country`, `zip_code`) VALUES
(1, '4234-1234', 'tapok', 'baranagay 4', 'Cabadbaran City', 'ADN', 'Philippines', '1506'),
(2, '132131', 'cabadbaran city', 'barang4', 'dsadasd', 'sadsadas', 'Philippines', '123131'),
(3, '12345', 'cabadbaran city', 'barang4', 'dsadasd', 'sadsadas', 'Philippines', '123131'),
(4, '', '', '', '', '', '', ''),
(5, '2026-0001', 'Purok 1C', 'Ampayon', 'Butuan City', 'Agusan Del Norte', 'Philippines', '8600'),
(6, '2026-0000', 'Admin HQ', 'Central', 'Cabadbaran City', 'Agusan Del Norte', 'Philippines', '8605');


-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `id_number` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `role` enum('user','admin','superadmin') NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_questions`
--

CREATE TABLE `auth_questions` (
  `question_id` int(11) NOT NULL,
  `question_text` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_questions`
--

INSERT INTO `auth_questions` (`question_id`, `question_text`) VALUES
(1, 'Who is your best friend in Elementary?'),
(2, 'What is the name of your favorite pet?'),
(3, 'Who is your favorite teacher in high school?'),
(4, 'What is your mother\'s maiden name?'),
(5, 'What city were you born in?'),
(6, 'What is your favorite color?'),
(7, 'What is your favorite food?'),
(8, 'What was the name of your first school?'),
(9, 'What is your father\'s middle name?');

-- --------------------------------------------------------

--
-- Table structure for table `block_list`
--

CREATE TABLE `block_list` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `role` enum('user','admin','superadmin') NOT NULL,
  `status` enum('blocked','unblocked') NOT NULL DEFAULT 'blocked',
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `birthdate` date NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `age` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin','superadmin') NOT NULL DEFAULT 'user',
  `status` enum('block','active') NOT NULL DEFAULT 'active',
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_number`, `first_name`, `middle_name`, `last_name`, `extension`, `birthdate`, `gender`, `age`, `username`, `email`, `password_hash`, `created_at`, `role`, `status`, `id`) VALUES
('', '', '', '', '', '0000-00-00', '', 0, '', NULL, '$2y$10$FuJ0CmqjHmVyKFj1Upd.muFgrsSBPe.opmgxq6TZMLZFJKY3UTd86', '2025-10-08 05:40:43', 'user', 'active', 0),
('1111-2222', 'Dodo', 'Salazar', 'Acido', '', '1899-12-23', 'male', 125, 'dodo', NULL, '$2y$10$QZZzEQkJHsY1ek.YbZSUw.WmRmBlitxY2qWPslBkdXaC/GqkCfoBK', '2025-09-19 14:18:21', 'user', 'active', 0),
('1234-1234', 'Dodo', 'S.', 'Acido', '', '2000-02-22', 'male', 25, 'Justin', NULL, '$2y$10$zyE2q.bmV5VMeST3A8zU3.EKCNHUp3p3xLqTe9bDnkJqqSjmIjbLe', '2025-09-19 13:54:20', 'user', 'active', 0),
('12345', 'Jan', '', 'Acido', '', '2025-09-17', 'male', 0, 'jan', NULL, '$2y$10$SewzTr7hOTqZzDBLnhMfrOcNaa.SMjlVjL3qTnSBViNPJjrcB7oSi', '2025-09-24 08:03:48', 'user', 'active', 0),
('132131', 'Cris', 'Justin', 'Acdio', '', '2001-07-25', 'male', 24, 'james', NULL, '$2y$10$/ISb.8BX/vV9.cE38Q6EXu6xbA10TiXYcrYlnlbOKSsHL2hwCRv0m', '2025-09-24 07:09:48', 'user', 'active', 0),
('2026-0000', 'Super', 'System', 'Admin', NULL, '2000-01-01', 'male', 26, 'superadmin', 'superadmin@system.com', '$2y$10$n4mPdxosAJPsYkbcY6KizeUi1bRwM1zpEPwzHMOACf6.1i0X7IkYC', '2026-08-07 13:00:00', 'superadmin', 'active', 0),
('2026-0001', 'Jerwil', '', 'Umpad', NULL, '2007-01-10', 'male', 19, 'jerwil.umpad@csucc.edu.ph', 'jerwil.umpad@csucc.edu.ph', '$2y$10$UjjJNoclC/xHZFIVpw532Op85gLm52VhayLjj9JxVR8u.NnF.85xe', '2026-08-06 06:10:15', 'user', 'active', 0),
('2323-1232', 'Cris', '', 'Acido', '', '2000-12-23', 'male', 24, 'jstin', NULL, '$2y$10$4G18rQjzifqnKBqPrJK6f.NIsMGZhKmAv6.z4EijG/4BaHQYAIZRK', '2025-09-19 14:11:59', 'user', 'active', 0),
('4234-1234', 'Cri Justin', 'Salazar', 'Acido', '', '2003-09-03', 'male', 22, 'cris', NULL, '$2y$10$9SRX7R4dC4p9WZC4bZj/zeNrHSrw6whqaE.AM..FXSCqXBAokqzLu', '2025-09-16 16:11:57', 'user', 'active', 0);


-- --------------------------------------------------------

--
-- Table structure for table `user_auth_answers`
--

CREATE TABLE `user_auth_answers` (
  `answer_id` int(11) NOT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `answer_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_auth_answers`
--

INSERT INTO `user_auth_answers` (`answer_id`, `id_number`, `question_id`, `answer_hash`) VALUES
(1, '4234-1234', 1, '$2y$10$MjKciT65k/pW5t5KvljOhufWepE6I4pHwSil/0pB0YEBhYY407AeO'),
(2, '132131', 1, '$2y$10$aZVfWs6.OENcO8lrEh2q..CEhP7pAhCJhyibYe8Y8m8HHrdRUca82'),
(3, '132131', 2, '$2y$10$fXA7DRofFWIMREWjWOUc1O4M0m86oQNs2N8qnjxz.Izfz.cpQ0N/S'),
(4, '132131', 3, '$2y$10$g3qTWR7FKE5NmhXp49glN.5Fe7KAOyL.ftoMPF3zL9XkAcIuJiWYu'),
(5, '12345', 1, '$2y$10$Lt9.WXAYsjmkX2RWU4t8UOwzWoY.2I.JJLDQOHP90EA2e1wnPJtii'),
(6, '12345', 2, '$2y$10$oGbGZnlRBjSmgaYtzPvNjOaBpErWoD0q7P794TIsoW1q7pqr20w26'),
(7, '12345', 3, '$2y$10$OlqXU4rdip/FHVPjM79MlObTEWUwGnc.7.Smfe/OhBIDShIM0gud6'),
(8, '', 1, '$2y$10$ca87Fg5dbZ3xpMzOWI.INeM6d6DimEnu7krOW9FORUJB54NyT1skW'),
(9, '', 2, '$2y$10$.OKB7iIzcVCl7Qka9QwZ8uYh55r.0gAJeg2A3ybyAsOMcCaABFZ5O'),
(10, '', 3, '$2y$10$tkhict0kfiAG4T0jPhE3e.uNscyTZwuwvwqIxOIOf3Y7QCowm7G7m'),
(11, '2026-0001', 1, '$2y$10$4MB2eElg9Fgy6kmPFCqLDe8cXi1YNrUr5yXazgHpmEhDfnm01pzV.'),
(12, '2026-0001', 6, '$2y$10$fKeFqwZ41GsPJTFiEsbp5e2i1kCZ8qjGcBawXqu2R41RnQoE1jyrC'),
(13, '2026-0001', 7, '$2y$10$EDRHCU24wccylzj0YGWEw.seOgBpoUd1aINm3QEuz8xciMUiRWjja');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `id_number` (`id_number`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auth_questions`
--
ALTER TABLE `auth_questions`
  ADD PRIMARY KEY (`question_id`);

--
-- Indexes for table `block_list`
--
ALTER TABLE `block_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_number`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_auth_answers`
--
ALTER TABLE `user_auth_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `id_number` (`id_number`),
  ADD KEY `question_id` (`question_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_questions`
--
ALTER TABLE `auth_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `block_list`
--
ALTER TABLE `block_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_auth_answers`
--
ALTER TABLE `user_auth_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`id_number`) REFERENCES `users` (`id_number`);

--
-- Constraints for table `user_auth_answers`
--
ALTER TABLE `user_auth_answers`
  ADD CONSTRAINT `user_auth_answers_ibfk_1` FOREIGN KEY (`id_number`) REFERENCES `users` (`id_number`),
  ADD CONSTRAINT `user_auth_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `auth_questions` (`question_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
