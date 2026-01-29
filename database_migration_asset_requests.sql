-- Migration: Add asset_requests table for employee-initiated asset requests
-- This table stores asset requests made by employees that need admin approval

USE asset_management;

-- Create asset_requests table
CREATE TABLE IF NOT EXISTS asset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    requested_asset_ids TEXT NOT NULL, -- Comma-separated list of asset IDs
    request_reason TEXT,
    acknowledgement TINYINT(1) DEFAULT 0,
    acknowledgement_date DATETIME NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT NULL,
    approved_by INT NULL,
    approved_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add comment to document the table purpose
ALTER TABLE asset_requests COMMENT = 'Stores employee asset requests pending admin approval';
