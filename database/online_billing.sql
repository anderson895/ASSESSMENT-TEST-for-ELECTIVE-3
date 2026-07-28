-- =====================================================================
-- MRA STORE - Online Billing System
-- Database: online_billing
-- ---------------------------------------------------------------------
-- ITO ANG SQL NA I-I-IMPORT. Isa lang ang file — ito na iyon.
--
-- PAANO I-IMPORT SA phpMyAdmin:
--   1. Buksan ang http://localhost/phpmyadmin
--   2. Gumawa ng database na ang pangalan ay:  online_billing
--      (kung meron na, pindutin lang ito sa kaliwa)
--   3. Pindutin ang "Import" tab
--   4. Piliin ang file na ito, tapos "Go"
--
-- BABALA: FRESH INSTALL ito. Buburahin nito ang lahat ng dating laman
-- (products, customers, orders) at papalitan ng bago. Mag-backup muna
-- kung may mahalaga kayong naitalang order.
--
-- ANG LALABAS PAGKATAPOS:
--   3 kategorya, 18 produkto (may larawan at stock),
--   5 cashier, 10 customer, at 0 order (malinis na simula).
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Tanggalin muna ang luma (anak muna bago magulang).
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `customers`;

-- ---------------------------------------------------------------------
-- categories - kategorya ng produkto, may sariling larawan
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
  `category_id`   int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(60)  NOT NULL,
  `image`         varchar(160) DEFAULT NULL,
  `sort_order`    int(11)      NOT NULL DEFAULT 0,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`, `category_name`, `image`, `sort_order`) VALUES
(1, 'Beauty & Personal Care', 'assets/img/categories/beauty.svg',    1),
(2, 'Grocery',                'assets/img/categories/grocery.svg',   2),
(3, 'Beverages',              'assets/img/categories/beverages.svg', 3);

-- ---------------------------------------------------------------------
-- employees - ang mga cashier. Ito ang lumalabas sa resibo.
-- ---------------------------------------------------------------------
CREATE TABLE `employees` (
  `employee_id`   int(11) NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(20)  NOT NULL,
  `employee_name` varchar(120) NOT NULL,
  `position`      varchar(60)  NOT NULL DEFAULT 'Cashier',
  `shift`         varchar(60)  NOT NULL DEFAULT 'Morning Shift',
  `is_active`     tinyint(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `employee_code` (`employee_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `employees` (`employee_id`, `employee_code`, `employee_name`, `position`, `shift`, `is_active`) VALUES
(1, 'MRA-001', 'Mica Dela Cruz',   'Head Cashier',      'Morning Shift  (6:00 AM - 2:00 PM)',   1),
(2, 'MRA-002', 'Ricky Diestro',    'Cashier',           'Afternoon Shift (2:00 PM - 10:00 PM)', 1),
(3, 'MRA-003', 'Angeline Bautista','Cashier',           'Night Shift    (10:00 PM - 6:00 AM)',  1),
(4, 'MRA-004', 'Joshua Ramirez',   'Assistant Cashier', 'Morning Shift  (6:00 AM - 2:00 PM)',   1),
(5, 'MRA-005', 'Kate Villanueva',  'Supervisor',        'Afternoon Shift (2:00 PM - 10:00 PM)', 1);

-- ---------------------------------------------------------------------
-- customers
-- Ang `order_number` dito ay LUMANG column lamang (legacy). Ang totoong
-- order number ay nasa `orders` table na at automatic nang gawa.
-- ---------------------------------------------------------------------
CREATE TABLE `customers` (
  `customer_id`    int(11) NOT NULL AUTO_INCREMENT,
  `customer_name`  varchar(120) NOT NULL,
  `contact_number` varchar(30)  NOT NULL,
  `order_number`   varchar(30)  DEFAULT NULL,
  `created_at`     timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `contact_number` (`contact_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `customers` (`customer_id`, `customer_name`, `contact_number`, `order_number`) VALUES
(1,  'Sophia Reyes',      '09171234567', NULL),
(2,  'Emma Santos',       '09182345678', NULL),
(3,  'Olivia Cruz',       '09193456789', NULL),
(4,  'Isabella Garcia',   '09214567890', NULL),
(5,  'Mia Flores',        '09225678901', NULL),
(6,  'Charlotte Mendoza', '09236789012', NULL),
(7,  'Ava Ramos',         '09247890123', NULL),
(8,  'Amelia Torres',     '09258901234', NULL),
(9,  'Harper Villanueva', '09269012345', NULL),
(10, 'Ella Navarro',      '09270123456', NULL);

-- ---------------------------------------------------------------------
-- products - may larawan (`image`) at natitirang stock (`stock`)
-- ---------------------------------------------------------------------
CREATE TABLE `products` (
  `product_id`   int(11) NOT NULL AUTO_INCREMENT,
  `category`     varchar(60)  NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `price`        decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock`        int(11)      NOT NULL DEFAULT 0,
  `image`        varchar(160) DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`product_id`, `category`, `product_name`, `price`, `stock`, `image`) VALUES
(1,  'Beauty & Personal Care', 'Facial Cleanser', 149.75, 40, 'assets/img/products/facial-cleanser.svg'),
(2,  'Beauty & Personal Care', 'Shampoo',         129.50, 55, 'assets/img/products/shampoo.svg'),
(3,  'Beauty & Personal Care', 'Conditioner',     135.00, 48, 'assets/img/products/conditioner.svg'),
(4,  'Beauty & Personal Care', 'Body Wash',       119.25, 36, 'assets/img/products/body-wash.svg'),
(5,  'Beauty & Personal Care', 'Body Lotion',     164.00, 30, 'assets/img/products/body-lotion.svg'),
(6,  'Beauty & Personal Care', 'Toothpaste',       89.50, 72, 'assets/img/products/toothpaste.svg'),
(7,  'Grocery',                'Rice',             52.00, 120,'assets/img/products/rice.svg'),
(8,  'Grocery',                'Eggs',              8.50, 300,'assets/img/products/eggs.svg'),
(9,  'Grocery',                'Bread',            65.00, 45, 'assets/img/products/bread.svg'),
(10, 'Grocery',                'Coffee',          145.75, 60, 'assets/img/products/coffee.svg'),
(11, 'Grocery',                'Sugar',            58.00, 80, 'assets/img/products/sugar.svg'),
(12, 'Grocery',                'Cooking Oil',     110.00, 52, 'assets/img/products/cooking-oil.svg'),
(13, 'Beverages',              'Mineral Water',    20.00, 200,'assets/img/products/mineral-water.svg'),
(14, 'Beverages',              'Orange Juice',     45.50, 64, 'assets/img/products/orange-juice.svg'),
(15, 'Beverages',              'Iced Tea',         30.00, 90, 'assets/img/products/iced-tea.svg'),
(16, 'Beverages',              'Coffee Drink',     55.00, 70, 'assets/img/products/coffee-drink.svg'),
(17, 'Beverages',              'Energy Drink',     48.75, 58, 'assets/img/products/energy-drink.svg'),
(18, 'Beverages',              'Soda',             32.00, 110,'assets/img/products/soda.svg');

-- ---------------------------------------------------------------------
-- orders
-- `order_number` = AUTOMATIC na (hal. ORD-20260728-0001), unique.
-- Naka-record din dito kung sinong cashier, anong bayad, at discount.
-- ---------------------------------------------------------------------
CREATE TABLE `orders` (
  `order_id`        int(11) NOT NULL AUTO_INCREMENT,
  `order_number`    varchar(30) NOT NULL,
  `customer_id`     int(11) DEFAULT NULL,
  `employee_id`     int(11) DEFAULT NULL,
  `payment_method`  varchar(20)   NOT NULL DEFAULT 'Cash',
  `discount_type`   varchar(30)   NOT NULL DEFAULT 'None',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal`        decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_tax`       decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total`     decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_received` decimal(12,2) NOT NULL DEFAULT 0.00,
  `change_due`      decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at`      timestamp     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `fk_orders_customer` (`customer_id`),
  KEY `fk_orders_employee` (`employee_id`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- order_items
-- ---------------------------------------------------------------------
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id`      int(11) NOT NULL,
  `product_id`    int(11) NOT NULL,
  `quantity`      int(11) NOT NULL DEFAULT 0,
  `unit_price`    decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price`   decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`order_item_id`),
  KEY `fk_items_order` (`order_id`),
  KEY `fk_items_product` (`product_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
