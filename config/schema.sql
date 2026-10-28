-- ========================================================
-- BASE DE DONNÉES : VENTE EN LIGNE (PROJET E-COMMERCE)
-- Script compatible MySQL 5.7+ / MariaDB / phpMyAdmin
-- ========================================================

CREATE DATABASE IF NOT EXISTS `vente_en_ligne` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vente_en_ligne`;

-- 1. Table Utilisateurs (Clients & Administrateurs)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(120) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin') DEFAULT 'customer',
    `phone` VARCHAR(30) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(80) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table Catégories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(80) NOT NULL,
    `slug` VARCHAR(80) UNIQUE NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'bi-tag',
    `description` TEXT DEFAULT NULL,
    `image_url` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table Produits
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(160) UNIQUE NOT NULL,
    `short_desc` VARCHAR(255) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `old_price` DECIMAL(10, 2) DEFAULT NULL,
    `stock` INT DEFAULT 10,
    `rating` DECIMAL(3, 1) DEFAULT 4.5,
    `reviews_count` INT DEFAULT 0,
    `image_url` TEXT NOT NULL,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_new` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table Commandes
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `order_number` VARCHAR(30) UNIQUE NOT NULL,
    `tracking_code` VARCHAR(30) UNIQUE NOT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `shipping_fee` DECIMAL(10, 2) DEFAULT 0.00,
    `tax_amount` DECIMAL(10, 2) DEFAULT 0.00,
    `status` ENUM('pending', 'paid', 'preparing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    `shipping_name` VARCHAR(120) NOT NULL,
    `shipping_email` VARCHAR(120) NOT NULL,
    `shipping_phone` VARCHAR(30) NOT NULL,
    `shipping_address` TEXT NOT NULL,
    `shipping_city` VARCHAR(80) NOT NULL,
    `payment_method` VARCHAR(40) NOT NULL,
    `payment_status` ENUM('paid', 'pending', 'failed') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table Détails des Commandes
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT DEFAULT NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `total` DECIMAL(10, 2) NOT NULL,
    `image_url` TEXT DEFAULT NULL,
    CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table Avis Clients
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `rating` INT NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
