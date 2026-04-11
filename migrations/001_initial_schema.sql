-- ============================================================
-- Sales Tracker - Initial Database Schema
-- Migration: 001_initial_schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ------------------------------------------------------------
-- Table: migrations
-- Tracks which migration files have been executed
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `migrations` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `migration`   VARCHAR(255)    NOT NULL COMMENT 'Migration filename',
    `executed_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_migrations_migration` (`migration`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks executed database migrations';

-- ------------------------------------------------------------
-- Table: work_types
-- Predefined work/sale categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `work_types` (
    `id`         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)     NOT NULL COMMENT 'Work type label',
    `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Types of sale/service work';

-- Seed: 2 predefined work types
INSERT INTO `work_types` (`id`, `name`) VALUES
    (1, 'ขายเฉพาะเครื่อง'),
    (2, 'ขายพร้อมติดตั้ง')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ------------------------------------------------------------
-- Table: products
-- Product catalog managed by staff
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL COMMENT 'Product name',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_name` (`name`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Product catalog';

-- ------------------------------------------------------------
-- Table: customers
-- Customer / buyer records
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL COMMENT 'Customer full name',
    `phone`      VARCHAR(20)      NULL COMMENT 'Contact phone number',
    `note`       TEXT             NULL COMMENT 'Additional notes',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer / buyer records';

-- ------------------------------------------------------------
-- Table: sales
-- Daily sales transactions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sales` (
    `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `sale_date`    DATE             NOT NULL COMMENT 'Date the sale occurred',
    `customer_id`  INT UNSIGNED     NOT NULL COMMENT 'FK -> customers.id',
    `product_id`   INT UNSIGNED     NOT NULL COMMENT 'FK -> products.id',
    `work_type_id` TINYINT UNSIGNED NOT NULL COMMENT 'FK -> work_types.id',
    `quantity`     INT UNSIGNED     NOT NULL DEFAULT 1 COMMENT 'Number of units sold',
    `price`        DECIMAL(12, 2)   NOT NULL COMMENT 'Total sale price (THB)',
    `note`         TEXT                 NULL COMMENT 'Optional remark',
    `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sales_date`        (`sale_date`),
    KEY `idx_sales_month_year`  (`sale_date`),
    KEY `idx_sales_product`     (`product_id`),
    KEY `idx_sales_customer`    (`customer_id`),
    KEY `idx_sales_work_type`   (`work_type_id`),
    CONSTRAINT `fk_sales_customer`  FOREIGN KEY (`customer_id`)  REFERENCES `customers` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_sales_product`   FOREIGN KEY (`product_id`)   REFERENCES `products`  (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_sales_work_type` FOREIGN KEY (`work_type_id`) REFERENCES `work_types`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Daily sales transactions';

-- ------------------------------------------------------------
-- (Optional) Demo seed data — remove before production use
-- ------------------------------------------------------------

-- Sample products
INSERT IGNORE INTO `products` (`name`) VALUES
    ('สินค้า A'),
    ('สินค้า B');

-- Sample customers
INSERT IGNORE INTO `customers` (`name`, `phone`) VALUES
    ('ลูกค้าทั่วไป', NULL);

SET FOREIGN_KEY_CHECKS = 1;
