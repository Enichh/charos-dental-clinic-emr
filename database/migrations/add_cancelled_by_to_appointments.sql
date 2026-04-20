-- Add cancelled_by field to track who cancelled the appointment
-- Note: admin and dentist are the same person (admin_id in appointments table)
ALTER TABLE appointments 
ADD COLUMN cancelled_by ENUM('patient', 'admin') NULL AFTER status,
ADD COLUMN cancellation_reason TEXT NULL AFTER cancelled_by,
ADD INDEX idx_cancelled_by (cancelled_by);
