USE harvee_marketplace;

SET @has_latitude := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'latitude'
);

SET @sql := IF(
    @has_latitude = 0,
    'ALTER TABLE users ADD COLUMN latitude DECIMAL(10,8) NULL AFTER address',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_longitude := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'longitude'
);

SET @sql := IF(
    @has_longitude = 0,
    'ALTER TABLE users ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx_latlng := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_user_latlng'
);

SET @sql := IF(
    @has_idx_latlng = 0,
    'ALTER TABLE users ADD INDEX idx_user_latlng (latitude, longitude)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
