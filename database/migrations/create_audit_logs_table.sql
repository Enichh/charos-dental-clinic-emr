-- Audit Logs Table for HIPAA Compliance
-- Retention: 6 years per HIPAA requirements
-- Created: 2026-04-20

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_id VARCHAR(255) NULL,
    user_email VARCHAR(255) NULL,
    user_role VARCHAR(50) NULL,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100) NULL,
    resource_id VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    details JSON NULL,
    success TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_retention_cleanup (timestamp),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_date_range (timestamp, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create view for recent audit activity (last 30 days)
CREATE OR REPLACE VIEW recent_audit_logs AS
SELECT
    id,
    timestamp,
    user_id,
    user_email,
    user_role,
    action,
    resource_type,
    resource_id,
    ip_address,
    success
FROM audit_logs
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY timestamp DESC;

-- Create view for security events (failed logins, unauthorized access)
CREATE OR REPLACE VIEW security_events AS
SELECT
    id,
    timestamp,
    user_id,
    user_email,
    action,
    ip_address,
    details
FROM audit_logs
WHERE
    success = 0
    OR action IN ('LOGIN_ATTEMPT', 'UNAUTHORIZED_ACCESS', 'DATA_BREACH_ATTEMPT')
ORDER BY timestamp DESC;
