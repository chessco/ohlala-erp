<?php
include(__DIR__ . '/../../../../conexion.php');

$queries = [
    "CREATE TABLE IF NOT EXISTS `pur_requests` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `tenant_id` INT NOT NULL,
      `requester_id` INT NOT NULL,
      `total_amount` DECIMAL(15,2) DEFAULT 0.00,
      `status` ENUM('draft', 'pending', 'approved', 'rejected', 'ordered') DEFAULT 'draft',
      `current_level` INT DEFAULT 1,
      `description` TEXT,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX (`tenant_id`),
      INDEX (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `pur_request_items` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `request_id` INT NOT NULL,
      `description` VARCHAR(255) NOT NULL,
      `quantity` DECIMAL(15,2) NOT NULL,
      `unit_price` DECIMAL(15,2) DEFAULT 0.00,
      `total` DECIMAL(15,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
      FOREIGN KEY (`request_id`) REFERENCES `pur_requests`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `pur_approval_steps` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `request_id` INT NOT NULL,
      `level` INT NOT NULL,
      `approver_id` INT NOT NULL,
      `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
      `comments` TEXT,
      `processed_at` TIMESTAMP NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`request_id`) REFERENCES `pur_requests`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $sql) {
    if (mysqli_query($conexion, $sql)) {
        echo "Query executed successfully.\n";
    } else {
        echo "Error: " . mysqli_error($conexion) . "\n";
    }
}
?>
