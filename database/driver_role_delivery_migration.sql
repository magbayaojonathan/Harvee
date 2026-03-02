USE harvee_marketplace;

-- 1) Add driver role to users.role enum
ALTER TABLE users
MODIFY COLUMN role ENUM('customer', 'farmer', 'driver', 'admin') DEFAULT 'customer';

-- 2) Add delivery columns to orders (safe re-run)
SET @has_delivery_driver_id := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_driver_id'
);
SET @sql := IF(@has_delivery_driver_id = 0,
    'ALTER TABLE orders ADD COLUMN delivery_driver_id INT NULL AFTER farmer_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_delivery_claimed_at := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_claimed_at'
);
SET @sql := IF(@has_delivery_claimed_at = 0,
    'ALTER TABLE orders ADD COLUMN delivery_claimed_at DATETIME NULL AFTER delivery_driver_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_delivered_proof_image := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivered_proof_image'
);
SET @sql := IF(@has_delivered_proof_image = 0,
    'ALTER TABLE orders ADD COLUMN delivered_proof_image VARCHAR(255) NULL AFTER actual_delivery_date',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_delivered_notes := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivered_notes'
);
SET @sql := IF(@has_delivered_notes = 0,
    'ALTER TABLE orders ADD COLUMN delivered_notes TEXT NULL AFTER delivered_proof_image',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Add index and foreign key for driver relation (safe re-run)
SET @has_idx_delivery_driver := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_delivery_driver'
);
SET @sql := IF(@has_idx_delivery_driver = 0,
    'ALTER TABLE orders ADD INDEX idx_delivery_driver (delivery_driver_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_fk_delivery_driver := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND CONSTRAINT_NAME = 'fk_orders_delivery_driver'
);
SET @sql := IF(@has_fk_delivery_driver = 0,
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_delivery_driver FOREIGN KEY (delivery_driver_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
