-- ============================================================
-- DokoBites — Full Database Schema
-- Reconstructed from every column referenced across the codebase.
-- Import this in phpMyAdmin (or via `mysql -u root -p food_ordering_system < dokobites_schema.sql`)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Uncomment these two lines if you need to create the database itself
-- (InfinityFree creates it for you automatically, so usually skip this part there)
-- CREATE DATABASE IF NOT EXISTS food_ordering_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE food_ordering_system;

-- ------------------------------------------------------------
-- Table: users
-- Holds customers, restaurant owners, and admins in one table,
-- distinguished by `role`.
-- ------------------------------------------------------------

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'restaurant', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    `profile_image` VARCHAR(255) NULL DEFAULT NULL,
    `reset_token` VARCHAR(255) NULL DEFAULT NULL,
    `reset_expires` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_reset_token` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: restaurants
-- One row per restaurant, owned by a `restaurant`-role user.
-- ------------------------------------------------------------
CREATE TABLE `restaurants` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `address` VARCHAR(255) NULL DEFAULT NULL,
    `image` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_restaurants_user_id` (`user_id`),
    KEY `idx_restaurants_status` (`status`),
    CONSTRAINT `fk_restaurants_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: foods
-- Menu items belonging to a restaurant.
-- ------------------------------------------------------------
CREATE TABLE `foods` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `restaurant_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `image` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_foods_restaurant_id` (`restaurant_id`),
    KEY `idx_foods_status` (`status`),
    CONSTRAINT `fk_foods_restaurant`
        FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: orders
-- One row per placed order. `transaction_uuid` stores the eSewa
-- transaction_uuid or Khalti pidx, used to verify payment callbacks.
-- ------------------------------------------------------------
CREATE TABLE `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `restaurant_id` INT UNSIGNED NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('cod', 'esewa', 'khalti') NOT NULL DEFAULT 'cod',
    `payment_status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    `status` ENUM('pending', 'confirmed', 'preparing', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    `notes` VARCHAR(300) NULL DEFAULT NULL,
    `delivery_address` VARCHAR(255) NOT NULL,
    `delivery_lat` DECIMAL(10,7) NULL DEFAULT NULL,
    `delivery_lng` DECIMAL(10,7) NULL DEFAULT NULL,
    `transaction_uuid` VARCHAR(191) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_transaction_uuid` (`transaction_uuid`),
    KEY `idx_orders_user_id` (`user_id`),
    KEY `idx_orders_restaurant_id` (`restaurant_id`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_payment_status` (`payment_status`),
    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_orders_restaurant`
        FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: order_items
-- Line items for each order. Deleting an order cascades and
-- removes its items automatically (see admin/manage_orders.php).
-- ------------------------------------------------------------
CREATE TABLE `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `food_id` INT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `price` DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order_id` (`order_id`),
    KEY `idx_order_items_food_id` (`food_id`),
    CONSTRAINT `fk_order_items_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_food`
        FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-

-- Admin account — email: admin@dokobites.com / password: password123
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Admin', 'admin@dokobites.com', '$2y$10$nT5AX0/zhWAddCW1Ks7LveOL.n.V0A8NsoH9lBaR97woJF7jjFKC.', 'admin', 'active');

-- Restaurant owner accounts — password for all: password123
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Sanjay Shrestha', 'kathmandukitchen@dokobites.com', '$2y$10$nT5AX0/zhWAddCW1Ks7LveOL.n.V0A8NsoH9lBaR97woJF7jjFKC.', 'restaurant', 'active'),
('Bindu Maharjan', 'himalayanspice@dokobites.com',  '$2y$10$nT5AX0/zhWAddCW1Ks7LveOL.n.V0A8NsoH9lBaR97woJF7jjFKC.', 'restaurant', 'active'),
('Rajesh Manandhar', 'bhojanghar@dokobites.com',    '$2y$10$nT5AX0/zhWAddCW1Ks7LveOL.n.V0A8NsoH9lBaR97woJF7jjFKC.', 'restaurant', 'active'),
('Sunita Bajracharya', 'aaganrestaurant@dokobites.com', '$2y$10$nT5AX0/zhWAddCW1Ks7LveOL.n.V0A8NsoH9lBaR97woJF7jjFKC.', 'restaurant', 'active'),
('Prakash Tuladhar', 'everestfoodcorner@dokobites.com', '$2y$10$nT5AX0/zhWAddCW1Ks7LveOL.n.V0A8NsoH9lBaR97woJF7jjFKC.', 'restaurant', 'active');

-- Sample customer account — email: customer@dokobites.com / password: password123
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Anil Sarkar', 'customer@dokobites.com', '$2y$10$nT5AX0/zhWAddCW1Ks7LveOL.n.V0A8NsoH9lBaR97woJF7jjFKC.', 'user', 'active');

-- Restaurants (linked to the owner accounts above, ids 2-6)
INSERT INTO `restaurants` (`user_id`, `name`, `description`, `address`, `image`, `status`) VALUES
(2, 'Kathmandu Kitchen', 'Traditional Newari thali and everyday Nepali comfort food.', 'Ason, Kathmandu', 'KathmanduKitchen.png', 'approved'),
(3, 'Himalayan Spice', 'Bold mountain flavors — sekuwa, thukpa, and momo done right.', 'Thamel, Kathmandu', 'HimalayanSpice.png', 'approved'),
(4, 'Bhojan Ghar', 'Home-style dal bhat sets and Newari specialties.', 'Patan, Lalitpur', 'BhojanGhar.png', 'approved'),
(5, 'Aagan Restaurant', 'Newari feast dishes served in a cozy courtyard setting.', 'Bhaktapur Durbar Square', 'AaganRestaurant.png', 'approved'),
(6, 'Everest Food Corner', 'Quick bites and classic Nepali street food favorites.', 'Baneshwor, Kathmandu', 'EverestFoodCorner.png', 'approved');

-- Foods — a small sample menu per restaurant (uses image files already
-- present in assets/uploads/ in the uploaded project)
INSERT INTO `foods` (`restaurant_id`, `name`, `description`, `price`, `image`, `status`) VALUES
(1, 'Newari Thali', 'A full Newari feast platter with rice, meat, and pickles.', 450.00, 'Newari_Thali.png', 'available'),
(1, 'Samay Baji', 'Classic Newari snack platter: beaten rice, meat, and egg.', 350.00, 'Samay_Baji.png', 'available'),
(1, 'Jhol Momo', 'Steamed momo served in a tangy, spiced broth.', 220.00, 'Jhol_Momo.png', 'available'),
(2, 'Grilled Sekuwa', 'Smoky charcoal-grilled marinated meat skewers.', 380.00, 'Grilled_Sekuwa.png', 'available'),
(2, 'Mutton Curry', 'Slow-cooked mutton curry with traditional spices.', 420.00, 'Mutton_Curry.png', 'available'),
(2, 'Chatamari', 'Rice flour crepe topped with egg, meat, and vegetables.', 250.00, 'Chatamari.png', 'available'),
(3, 'Dal Bhat Set', 'Classic Nepali staple: lentils, rice, vegetables, and pickle.', 300.00, 'Dal_Bhat_Set.png', 'available'),
(3, 'Chicken Curry', 'Home-style chicken curry with steamed rice.', 350.00, 'Chicken_Curry.png', 'available'),
(3, 'Gundruk Achar', 'Fermented leafy greens pickle, a Nepali staple side.', 120.00, 'Gundruk_Achar.png', 'available'),
(4, 'Yomari', 'Steamed rice-flour dumpling filled with sweet molasses.', 180.00, 'Yomari.png', 'available'),
(4, 'Bara', 'Savory lentil flour pancake, a Newari classic.', 200.00, 'Bara.png', 'available'),
(4, 'Buff Sukuti', 'Sun-dried, spiced buffalo meat — a popular Newari snack.', 320.00, 'Buff_Sukuti.png', 'available'),
(4, 'Juju Dhau', 'Bhaktapur''s famous "king of yogurt" dessert.', 150.00, 'Juju_Dhau.png', 'available'),
(5, 'Kothey Momo', 'Pan-fried momo, crispy on the bottom, juicy inside.', 240.00, 'Kothey_Momo.png', 'available'),
(5, 'Chatpate', 'Spicy-tangy puffed rice street snack.', 100.00, 'Chatpate.png', 'available'),
(5, 'Fried Rice', 'Wok-tossed vegetable or chicken fried rice.', 220.00, 'Fried_Rice.png', 'available'),
(5, 'Veg Chowmein', 'Stir-fried noodles with fresh vegetables.', 200.00, 'Veg_Chowmein.png', 'available'),
(5, 'Aloo Achar', 'Spiced potato salad, a beloved Nepali side dish.', 130.00, 'Aloo_Achar.png', 'available');