-- PUR_APPROVAL_STEPS: Tracks the progress of each level
CREATE TABLE IF NOT EXISTS `pur_approval_steps` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `level` INT NOT NULL, -- 1, 2, 3
  `approver_id` INT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `comments` TEXT,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`request_id`) REFERENCES `pur_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexing for performance and idempotency
CREATE INDEX idx_request_level ON `pur_approval_steps` (`request_id`, `level`);
CREATE INDEX idx_approver ON `pur_approval_steps` (`approver_id`, `status`);
