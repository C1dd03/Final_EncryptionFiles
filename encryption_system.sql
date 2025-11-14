-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2025 at 08:42 AM
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
(4, '', '', '', '', '', '', '');

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
(3, 'Who is your favorite teacher in high school?');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_number`, `first_name`, `middle_name`, `last_name`, `extension`, `birthdate`, `gender`, `age`, `username`, `password_hash`, `created_at`) VALUES
('', '', '', '', '', '0000-00-00', '', 0, '', '$2y$10$FuJ0CmqjHmVyKFj1Upd.muFgrsSBPe.opmgxq6TZMLZFJKY3UTd86', '2025-10-08 05:40:43'),
('1111-2222', 'Dodo', 'Salazar', 'Acido', '', '1899-12-23', 'male', 125, 'dodo', '$2y$10$QZZzEQkJHsY1ek.YbZSUw.WmRmBlitxY2qWPslBkdXaC/GqkCfoBK', '2025-09-19 14:18:21'),
('1234-1234', 'Dodo', 'S.', 'Acido', '', '2000-02-22', 'male', 25, 'Justin', '$2y$10$zyE2q.bmV5VMeST3A8zU3.EKCNHUp3p3xLqTe9bDnkJqqSjmIjbLe', '2025-09-19 13:54:20'),
('12345', 'Jan', '', 'Acido', '', '2025-09-17', 'male', 0, 'jan', '$2y$10$SewzTr7hOTqZzDBLnhMfrOcNaa.SMjlVjL3qTnSBViNPJjrcB7oSi', '2025-09-24 08:03:48'),
('132131', 'Cris', 'Justin', 'Acdio', '', '2001-07-25', 'male', 24, 'james', '$2y$10$/ISb.8BX/vV9.cE38Q6EXu6xbA10TiXYcrYlnlbOKSsHL2hwCRv0m', '2025-09-24 07:09:48'),
('2323-1232', 'Cris', '', 'Acido', '', '2000-12-23', 'male', 24, 'jstin', '$2y$10$4G18rQjzifqnKBqPrJK6f.NIsMGZhKmAv6.z4EijG/4BaHQYAIZRK', '2025-09-19 14:11:59'),
('4234-1234', 'Cri Justin', 'Salazar', 'Acido', '', '2003-09-03', 'male', 22, 'cris', '$2y$10$9SRX7R4dC4p9WZC4bZj/zeNrHSrw6whqaE.AM..FXSCqXBAokqzLu', '2025-09-16 16:11:57');

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
(10, '', 3, '$2y$10$tkhict0kfiAG4T0jPhE3e.uNscyTZwuwvwqIxOIOf3Y7QCowm7G7m');

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `log_id` int(11) NOT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('SUCCESS','FAILED') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `auth_questions`
--
ALTER TABLE `auth_questions`
  ADD PRIMARY KEY (`question_id`);

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
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `id_number` (`id_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `auth_questions`
--
ALTER TABLE `auth_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_auth_answers`
--
ALTER TABLE `user_auth_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

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

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`id_number`) REFERENCES `users` (`id_number`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
