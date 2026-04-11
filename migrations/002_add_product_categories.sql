-- ============================================================
-- Sales Tracker - Add Product Categories
-- Migration: 002_add_product_categories.sql
-- ============================================================

-- Table: categories
-- Product categories for organization and filtering
CREATE TABLE IF NOT EXISTS `categories` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL COMMENT 'Category name',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Product categories';

-- Update: products table - Add category_id column
ALTER TABLE `products` ADD COLUMN `category_id` INT UNSIGNED NULL COMMENT 'FK -> categories.id' AFTER `name`;

-- Update: products table - Add foreign key constraint
ALTER TABLE `products` ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- Update: products table - Add index on category_id
ALTER TABLE `products` ADD KEY `idx_products_category` (`category_id`);

-- Seed: Default categories
INSERT IGNORE INTO `categories` (`name`) VALUES ('ไม่ระบุ');
INSERT IGNORE INTO `categories` (`name`) VALUES ('TCL');
INSERT IGNORE INTO `categories` (`name`) VALUES ('Carrier');
INSERT IGNORE INTO `categories` (`name`) VALUES ('อื่น ๆ');
