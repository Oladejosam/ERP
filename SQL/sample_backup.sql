-- Sample backup export placeholder for ERP_db
-- This file can be generated from phpMyAdmin or MySQL Workbench.
CREATE TABLE IF NOT EXISTS backup_manifest (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_name VARCHAR(150) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
