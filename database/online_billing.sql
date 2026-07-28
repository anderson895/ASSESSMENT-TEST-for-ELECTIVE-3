-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2026 at 09:48 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_billing`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(60) NOT NULL,
  `image` varchar(160) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `image`, `sort_order`) VALUES
(1, 'Beauty & Personal Care', 'assets/img/categories/beauty.svg', 1),
(2, 'Grocery', 'assets/img/categories/grocery.svg', 2),
(3, 'Beverages', 'assets/img/categories/beverages.svg', 3);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `contact_number` varchar(30) NOT NULL,
  `order_number` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `contact_number`, `order_number`, `created_at`) VALUES
(1, 'Sophia Reyes', '09171234567', NULL, '2026-07-28 06:55:21'),
(2, 'Emma Santos', '09182345678', NULL, '2026-07-28 06:55:21'),
(3, 'Olivia Cruz', '09193456789', NULL, '2026-07-28 06:55:21'),
(4, 'Isabella Garcia', '09214567890', NULL, '2026-07-28 06:55:21'),
(5, 'Mia Flores', '09225678901', NULL, '2026-07-28 06:55:21'),
(6, 'Charlotte Mendoza', '09236789012', NULL, '2026-07-28 06:55:21'),
(7, 'Ava Ramos', '09247890123', NULL, '2026-07-28 06:55:21'),
(8, 'Amelia Torres', '09258901234', NULL, '2026-07-28 06:55:21'),
(9, 'Harper Villanueva', '09269012345', NULL, '2026-07-28 06:55:21'),
(10, 'Ella Navarro', '09270123456', NULL, '2026-07-28 06:55:21');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `employee_code` varchar(20) NOT NULL,
  `employee_name` varchar(120) NOT NULL,
  `position` varchar(60) NOT NULL DEFAULT 'Cashier',
  `shift` varchar(60) NOT NULL DEFAULT 'Morning Shift',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_code`, `employee_name`, `position`, `shift`, `is_active`) VALUES
(1, 'MRA-001', 'Mica Dela Cruz', 'Head Cashier', 'Morning Shift  (6:00 AM - 2:00 PM)', 1),
(2, 'MRA-002', 'Ricky Diestro', 'Cashier', 'Afternoon Shift (2:00 PM - 10:00 PM)', 1),
(3, 'MRA-003', 'Angeline Bautista', 'Cashier', 'Night Shift    (10:00 PM - 6:00 AM)', 1),
(4, 'MRA-004', 'Joshua Ramirez', 'Assistant Cashier', 'Morning Shift  (6:00 AM - 2:00 PM)', 1),
(5, 'MRA-005', 'Kate Villanueva', 'Supervisor', 'Afternoon Shift (2:00 PM - 10:00 PM)', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `payment_method` varchar(20) NOT NULL DEFAULT 'Cash',
  `discount_type` varchar(30) NOT NULL DEFAULT 'None',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_received` decimal(12,2) NOT NULL DEFAULT 0.00,
  `change_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category` varchar(60) NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(160) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category`, `product_name`, `price`, `stock`, `image`) VALUES
(1, 'Beauty & Personal Care', 'Facial Cleanser', 149.75, 40, 'assets/img/products/facial-cleanser.svg'),
(2, 'Beauty & Personal Care', 'Shampoo', 129.50, 55, 'assets/img/products/shampoo.svg'),
(3, 'Beauty & Personal Care', 'Conditioner', 135.00, 48, 'assets/img/products/conditioner.svg'),
(4, 'Beauty & Personal Care', 'Body Wash', 119.25, 36, 'assets/img/products/body-wash.svg'),
(5, 'Beauty & Personal Care', 'Body Lotion', 164.00, 30, 'assets/img/products/body-lotion.svg'),
(6, 'Beauty & Personal Care', 'Toothpaste', 89.50, 72, 'assets/img/products/toothpaste.svg'),
(7, 'Grocery', 'Rice', 52.00, 120, 'assets/img/products/rice.svg'),
(8, 'Grocery', 'Eggs', 8.50, 300, 'assets/img/products/eggs.svg'),
(9, 'Grocery', 'Bread', 65.00, 45, 'assets/img/products/bread.svg'),
(10, 'Grocery', 'Coffee', 145.75, 60, 'assets/img/products/coffee.svg'),
(11, 'Grocery', 'Sugar', 58.00, 80, 'assets/img/products/sugar.svg'),
(12, 'Grocery', 'Cooking Oil', 110.00, 52, 'assets/img/products/cooking-oil.svg'),
(13, 'Beverages', 'Mineral Water', 20.00, 200, 'assets/img/products/mineral-water.svg'),
(14, 'Beverages', 'Orange Juice', 45.50, 64, 'assets/img/products/orange-juice.svg'),
(15, 'Beverages', 'Iced Tea', 30.00, 90, 'assets/img/products/iced-tea.svg'),
(16, 'Beverages', 'Coffee Drink', 55.00, 70, 'assets/img/products/coffee-drink.svg'),
(17, 'Beverages', 'Energy Drink', 48.75, 58, 'assets/img/products/energy-drink.jpg'),
(18, 'Beverages', 'Soda', 32.00, 110, 'assets/img/products/soda.svg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `contact_number` (`contact_number`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `fk_orders_customer` (`customer_id`),
  ADD KEY `fk_orders_employee` (`employee_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_items_order` (`order_id`),
  ADD KEY `fk_items_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category` (`category`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
