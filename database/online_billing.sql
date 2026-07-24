-- =====================================================================
--  ONLINE BILLING SYSTEM  -  Database Schema and Seed Data
--  Stack: MySQL (XAMPP)
--  Import this file in phpMyAdmin, or run:
--     mysql -u root -p < online_billing.sql
-- =====================================================================

DROP DATABASE IF EXISTS online_billing;
CREATE DATABASE online_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE online_billing;

-- ---------------------------------------------------------------------
--  customers
-- ---------------------------------------------------------------------
CREATE TABLE customers (
    customer_id     INT AUTO_INCREMENT PRIMARY KEY,
    customer_name   VARCHAR(120)  NOT NULL,
    contact_number  VARCHAR(30)   NOT NULL,
    order_number    VARCHAR(30)   NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  products
-- ---------------------------------------------------------------------
CREATE TABLE products (
    product_id      INT AUTO_INCREMENT PRIMARY KEY,
    category        VARCHAR(60)   NOT NULL,
    product_name    VARCHAR(120)  NOT NULL,
    price           DECIMAL(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  orders
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    order_id        INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NULL,
    subtotal        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_tax       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    grand_total     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  order_items
-- ---------------------------------------------------------------------
CREATE TABLE order_items (
    order_item_id   INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL,
    product_id      INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 0,
    total_price     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_items_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Products : three categories (see details.md, not copied from sample)
INSERT INTO products (category, product_name, price) VALUES
-- Beauty & Personal Care
('Beauty & Personal Care', 'Facial Cleanser', 149.75),
('Beauty & Personal Care', 'Shampoo',         129.50),
('Beauty & Personal Care', 'Conditioner',      135.00),
('Beauty & Personal Care', 'Body Wash',        119.25),
('Beauty & Personal Care', 'Body Lotion',      164.00),
('Beauty & Personal Care', 'Toothpaste',        89.50),
-- Grocery
('Grocery', 'Rice',        52.00),
('Grocery', 'Eggs',         8.50),
('Grocery', 'Bread',       65.00),
('Grocery', 'Coffee',     145.75),
('Grocery', 'Sugar',       58.00),
('Grocery', 'Cooking Oil',110.00),
-- Beverages
('Beverages', 'Mineral Water', 20.00),
('Beverages', 'Orange Juice',  45.50),
('Beverages', 'Iced Tea',      30.00),
('Beverages', 'Coffee Drink',  55.00),
('Beverages', 'Energy Drink',  48.75),
('Beverages', 'Soda',          32.00);

-- Sample customers (for testing the Find button)
INSERT INTO customers (customer_name, contact_number, order_number) VALUES
('Ricky Diestro',  '09171234567', 'ORD-1001'),
('Maria Santos',   '09209876543', 'ORD-1002'),
('Juan Dela Cruz', '09331112222', 'ORD-1003');
