-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Oct 11, 2026 at 09:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `portaljazro`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_name` varchar(150) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment`
--

CREATE TABLE `assessment` (
  `assessment_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `total_marks` decimal(8,2) DEFAULT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `attendance_status` varchar(30) NOT NULL,
  `remark` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blooket`
--

CREATE TABLE `blooket` (
  `blooket_id` int(11) NOT NULL,
  `blooket_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `branch_id` int(11) NOT NULL,
  `branch_code` varchar(20) NOT NULL,
  `branch_location` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `branch_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate`
--

CREATE TABLE `certificate` (
  `certificate_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `certificate_type` varchar(50) DEFAULT NULL,
  `certificate_name` varchar(200) DEFAULT NULL,
  `certificate_url` varchar(500) DEFAULT NULL,
  `issue_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `class_code` varchar(50) NOT NULL,
  `class_name` varchar(150) DEFAULT NULL,
  `class_type` enum('Physical','Online') NOT NULL,
  `class_category` enum('BAU','B2B') NOT NULL,
  `status` varchar(30) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_session`
--

CREATE TABLE `class_session` (
  `session_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrollment_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_material`
--

CREATE TABLE `learning_material` (
  `material_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `material_title` varchar(200) NOT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `upload_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

CREATE TABLE `module` (
  `module_id` int(11) NOT NULL,
  `module_code` varchar(50) NOT NULL,
  `module_name` varchar(150) NOT NULL,
  `module_category` varchar(100) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization`
--

CREATE TABLE `organization` (
  `organization_id` int(11) NOT NULL,
  `organization_name` varchar(150) NOT NULL,
  `organization_type` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `ctc_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent`
--

CREATE TABLE `parent` (
  `parent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `parent_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recording`
--

CREATE TABLE `recording` (
  `recording_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `recording_url` varchar(500) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportcard_softskill`
--

CREATE TABLE `reportcard_softskill` (
  `report_skill_id` int(11) NOT NULL,
  `reportcard_id` int(11) NOT NULL,
  `soft_skill_id` int(11) NOT NULL,
  `score` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_card`
--

CREATE TABLE `report_card` (
  `reportcard_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `academic_term` varchar(50) DEFAULT NULL,
  `academic_year` year(4) DEFAULT NULL,
  `attendance_score` decimal(6,2) DEFAULT NULL,
  `skills_score` decimal(6,2) DEFAULT NULL,
  `softskills_score` decimal(6,2) DEFAULT NULL,
  `portal_assessment_score` decimal(6,2) DEFAULT NULL,
  `overall_score` decimal(6,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_card_topic`
--

CREATE TABLE `report_card_topic` (
  `report_topic_id` int(11) NOT NULL,
  `reportcard_id` int(11) NOT NULL,
  `topic_name` varchar(200) NOT NULL,
  `achievement_score` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_assessment`
--

CREATE TABLE `result_assessment` (
  `result_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(8,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `completed_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soft_skill`
--

CREATE TABLE `soft_skill` (
  `soft_skill_id` int(11) NOT NULL,
  `skill_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `student_name` varchar(150) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `school_name` varchar(150) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_booklet`
--

CREATE TABLE `student_booklet` (
  `stu_booklet_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `booklet_id` int(11) NOT NULL,
  `status` varchar(30) DEFAULT NULL,
  `date_received` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('PARENT','ADMIN') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `assessment`
--
ALTER TABLE `assessment`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `session_id` (`session_id`,`student_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `blooket`
--
ALTER TABLE `blooket`
  ADD PRIMARY KEY (`blooket_id`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `certificate`
--
ALTER TABLE `certificate`
  ADD PRIMARY KEY (`certificate_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `class_session`
--
ALTER TABLE `class_session`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `learning_material`
--
ALTER TABLE `learning_material`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`module_id`);

--
-- Indexes for table `organization`
--
ALTER TABLE `organization`
  ADD PRIMARY KEY (`organization_id`);

--
-- Indexes for table `parent`
--
ALTER TABLE `parent`
  ADD PRIMARY KEY (`parent_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `recording`
--
ALTER TABLE `recording`
  ADD PRIMARY KEY (`recording_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `reportcard_softskill`
--
ALTER TABLE `reportcard_softskill`
  ADD PRIMARY KEY (`report_skill_id`),
  ADD UNIQUE KEY `reportcard_id` (`reportcard_id`,`soft_skill_id`),
  ADD KEY `soft_skill_id` (`soft_skill_id`);

--
-- Indexes for table `report_card`
--
ALTER TABLE `report_card`
  ADD PRIMARY KEY (`reportcard_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `report_card_topic`
--
ALTER TABLE `report_card_topic`
  ADD PRIMARY KEY (`report_topic_id`),
  ADD KEY `reportcard_id` (`reportcard_id`);

--
-- Indexes for table `result_assessment`
--
ALTER TABLE `result_assessment`
  ADD PRIMARY KEY (`result_id`),
  ADD UNIQUE KEY `assessment_id` (`assessment_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `soft_skill`
--
ALTER TABLE `soft_skill`
  ADD PRIMARY KEY (`soft_skill_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `student_booklet`
--
ALTER TABLE `student_booklet`
  ADD PRIMARY KEY (`stu_booklet_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`booklet_id`),
  ADD KEY `booklet_id` (`booklet_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment`
--
ALTER TABLE `assessment`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blooket`
--
ALTER TABLE `blooket`
  MODIFY `blooket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificate`
--
ALTER TABLE `certificate`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_session`
--
ALTER TABLE `class_session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_material`
--
ALTER TABLE `learning_material`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module`
--
ALTER TABLE `module`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organization`
--
ALTER TABLE `organization`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parent`
--
ALTER TABLE `parent`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recording`
--
ALTER TABLE `recording`
  MODIFY `recording_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reportcard_softskill`
--
ALTER TABLE `reportcard_softskill`
  MODIFY `report_skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_card`
--
ALTER TABLE `report_card`
  MODIFY `reportcard_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_card_topic`
--
ALTER TABLE `report_card_topic`
  MODIFY `report_topic_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result_assessment`
--
ALTER TABLE `result_assessment`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soft_skill`
--
ALTER TABLE `soft_skill`
  MODIFY `soft_skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_booklet`
--
ALTER TABLE `student_booklet`
  MODIFY `stu_booklet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `assessment`
--
ALTER TABLE `assessment`
  ADD CONSTRAINT `assessment_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `assessment_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`),
  ADD CONSTRAINT `assessment_ibfk_3` FOREIGN KEY (`module_id`) REFERENCES `module` (`module_id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_session` (`session_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `certificate`
--
ALTER TABLE `certificate`
  ADD CONSTRAINT `certificate_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `certificate_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `certificate_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`organization_id`) ON DELETE SET NULL;

--
-- Constraints for table `class_session`
--
ALTER TABLE `class_session`
  ADD CONSTRAINT `class_session_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `class_session_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `module` (`module_id`);

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `learning_material`
--
ALTER TABLE `learning_material`
  ADD CONSTRAINT `learning_material_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `learning_material_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `parent`
--
ALTER TABLE `parent`
  ADD CONSTRAINT `parent_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `class_session` (`session_id`);

--
-- Constraints for table `recording`
--
ALTER TABLE `recording`
  ADD CONSTRAINT `recording_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_session` (`session_id`),
  ADD CONSTRAINT `recording_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `reportcard_softskill`
--
ALTER TABLE `reportcard_softskill`
  ADD CONSTRAINT `reportcard_softskill_ibfk_1` FOREIGN KEY (`reportcard_id`) REFERENCES `report_card` (`reportcard_id`),
  ADD CONSTRAINT `reportcard_softskill_ibfk_2` FOREIGN KEY (`soft_skill_id`) REFERENCES `soft_skill` (`soft_skill_id`);

--
-- Constraints for table `report_card`
--
ALTER TABLE `report_card`
  ADD CONSTRAINT `report_card_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `report_card_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `report_card_topic`
--
ALTER TABLE `report_card_topic`
  ADD CONSTRAINT `report_card_topic_ibfk_1` FOREIGN KEY (`reportcard_id`) REFERENCES `report_card` (`reportcard_id`);

--
-- Constraints for table `result_assessment`
--
ALTER TABLE `result_assessment`
  ADD CONSTRAINT `result_assessment_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`),
  ADD CONSTRAINT `result_assessment_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  ADD CONSTRAINT `student_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`organization_id`) ON DELETE SET NULL;

--
-- Constraints for table `student_booklet`
--
ALTER TABLE `student_booklet`
  ADD CONSTRAINT `student_booklet_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `student_booklet_ibfk_2` FOREIGN KEY (`booklet_id`) REFERENCES `blooket` (`blooket_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
