-- ============================================================================
-- Online Voting System — Database Schema
-- Version: 1.0.0
-- Compatible with: MySQL 5.7+ / MariaDB 10.3+
-- ============================================================================

-- Create database
CREATE DATABASE IF NOT EXISTS `voting_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `voting_system`;

-- ─── 1. ADMINS TABLE ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admins` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50)  NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `full_name`  VARCHAR(100) NOT NULL,
    `email`      VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_admin_username` (`username`),
    UNIQUE KEY `uk_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 2. VOTERS TABLE ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `voters` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `voter_id`   VARCHAR(20)  NOT NULL COMMENT 'Public-facing voter ID (e.g., VTR-00001)',
    `username`   VARCHAR(50)  NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `full_name`  VARCHAR(100) NOT NULL,
    `email`      VARCHAR(100) NOT NULL,
    `phone`      VARCHAR(20)  DEFAULT NULL,
    `address`    TEXT         DEFAULT NULL,
    `photo`      VARCHAR(255) DEFAULT NULL,
    `is_active`  TINYINT(1)   DEFAULT 1 COMMENT '1 = active, 0 = disabled',
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_voter_voter_id` (`voter_id`),
    UNIQUE KEY `uk_voter_username` (`username`),
    UNIQUE KEY `uk_voter_email` (`email`),
    INDEX `idx_voter_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 3. ELECTIONS TABLE ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `elections` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `title`       VARCHAR(200) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `start_date`  DATETIME     NOT NULL,
    `end_date`    DATETIME     NOT NULL,
    `status`      ENUM('upcoming','active','ended') DEFAULT 'upcoming',
    `created_by`  INT          DEFAULT NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_election_status` (`status`),
    INDEX `idx_election_dates` (`start_date`, `end_date`),
    CONSTRAINT `fk_election_admin` FOREIGN KEY (`created_by`)
        REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 4. CANDIDATES TABLE ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `candidates` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT          NOT NULL,
    `full_name`   VARCHAR(100) NOT NULL,
    `party`       VARCHAR(100) DEFAULT NULL,
    `symbol`      VARCHAR(100) DEFAULT NULL,
    `photo`       VARCHAR(255) DEFAULT NULL,
    `manifesto`   TEXT         DEFAULT NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_candidate_election` (`election_id`),
    CONSTRAINT `fk_candidate_election` FOREIGN KEY (`election_id`)
        REFERENCES `elections`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 5. VOTES TABLE ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `votes` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `election_id`  INT       NOT NULL,
    `candidate_id` INT       NOT NULL,
    `voter_id`     INT       NOT NULL,
    `voted_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_address`   VARCHAR(45) DEFAULT NULL,

    -- Prevent duplicate voting: one vote per voter per election
    UNIQUE KEY `uk_vote_election_voter` (`election_id`, `voter_id`),

    INDEX `idx_vote_candidate` (`candidate_id`),
    INDEX `idx_vote_voter` (`voter_id`),
    INDEX `idx_vote_time` (`voted_at`),

    CONSTRAINT `fk_vote_election` FOREIGN KEY (`election_id`)
        REFERENCES `elections`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vote_candidate` FOREIGN KEY (`candidate_id`)
        REFERENCES `candidates`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vote_voter` FOREIGN KEY (`voter_id`)
        REFERENCES `voters`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 6. AUDIT LOGS TABLE ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_type`   ENUM('admin','voter') NOT NULL,
    `user_id`     INT          NOT NULL,
    `action`      VARCHAR(100) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_audit_user` (`user_type`, `user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═════════════════════════════════════════════════════════════════════════════
-- SEED DATA
-- ═════════════════════════════════════════════════════════════════════════════

-- Default admin account
-- Username: admin
-- Password: admin123 (bcrypt hash)
INSERT INTO `admins` (`username`, `password`, `full_name`, `email`) VALUES
('admin', '$2y$10$8ZwAFHi3QivD6oluvG0OC..wMXynLGimEx4hEbv3lB67m9nLGyPRK', 'System Administrator', 'admin@votesecure.com');

-- Sample voters for testing
-- Password for all: password123
INSERT INTO `voters` (`voter_id`, `username`, `password`, `full_name`, `email`, `phone`, `is_active`) VALUES
('VTR-00001', 'john_doe',   '$2y$10$ohtq4Gn.ljXb3F0Hp66kfO6BpbNE2omyihQ3qodqYwrTTXwvDBWjG', 'John Doe',    'john@example.com',  '+1234567890', 1),
('VTR-00002', 'jane_smith', '$2y$10$ohtq4Gn.ljXb3F0Hp66kfO6BpbNE2omyihQ3qodqYwrTTXwvDBWjG', 'Jane Smith',  'jane@example.com',  '+1234567891', 1),
('VTR-00003', 'bob_wilson', '$2y$10$ohtq4Gn.ljXb3F0Hp66kfO6BpbNE2omyihQ3qodqYwrTTXwvDBWjG', 'Bob Wilson',  'bob@example.com',   '+1234567892', 1);

-- Sample election
INSERT INTO `elections` (`title`, `description`, `start_date`, `end_date`, `status`, `created_by`) VALUES
('Student Council Election 2026', 'Annual student council election for the academic year 2026-2027. Vote for your preferred candidates.', '2026-07-15 08:00:00', '2026-07-20 18:00:00', 'active', 1);

-- Sample candidates for the election
INSERT INTO `candidates` (`election_id`, `full_name`, `party`, `symbol`, `manifesto`) VALUES
(1, 'Alice Johnson',   'Progress Party',  '⭐', 'Committed to improving campus facilities, increasing scholarship opportunities, and creating more student-led initiatives for a better academic experience.'),
(1, 'Charlie Brown',   'Unity Alliance',  '🤝', 'Focused on building bridges between departments, promoting cultural diversity, and ensuring every student voice is heard in university decisions.'),
(1, 'Diana Martinez',  'Green Future',    '🌿', 'Dedicated to sustainability on campus, reducing waste, promoting green energy, and creating eco-friendly spaces for students to learn and grow.');
