# Online Billing System

An **Online Billing System** built for the ELECTIVE 3 assessment test, applying
**Object-Oriented Design (OOD)**.

## Development Stack
| Layer | Technology |
|-------|------------|
| Paradigm | Object-Oriented Programming (OOP) |
| Frontend | HTML5, CSS3, JavaScript |
| Backend  | PHP (OOP, PDO) |
| Database | MySQL |
| Server   | XAMPP (Apache + MySQL) |
| Editor   | Visual Studio Code |

## Color Theme
| Element | Color |
|---------|-------|
| Background | `#FFF7FA` |
| Cards / Panels | `#FFFFFF` |
| Header | `#D81B60` |
| Buttons | `#EC407A` |
| Button Hover | `#C2185B` |
| Text | `#374151` |
| Border | `#F8BBD0` |

## Setup (XAMPP)
1. **Copy the project** into the XAMPP web root, e.g.
   `C:\xampp\htdocs\OnlineBillingSystem`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. **Import the database**: open <http://localhost/phpmyadmin>, click *Import*,
   choose `database/online_billing.sql`, and run it.
   (This creates the `online_billing` database with sample products &
   customers.)
4. If your MySQL user/password differ from the XAMPP default
   (`root` / empty), edit `config/Database.php`.
5. Open <http://localhost/OnlineBillingSystem/> in the browser.

## How to Use (System Flow)
1. **Enter Customer Details** (Name, Contact Number, Order Number).
2. Click **Find** to auto-fill an existing customer by Contact / Order Number.
   Try `09171234567` or `ORD-1001` from the seed data.
3. **Enter the quantity** for each product (integer values).
4. Click **Total** to compute the category totals.
5. Click **Bill** to compute taxes, subtotal, total tax, grand total — and
   save the order to the database.
6. Click **Print** to generate a printable receipt.
7. Click **E-Mail** to display (or send) the billing details.
8. Click **Clear** to reset all fields.

## Object-Oriented Design
| Class | Responsibility |
|-------|----------------|
| `Database` | PDO connection handler |
| `Customer` | Find / save customers |
| `Product`  | Load products, grouped by category |
| `Bill`     | Category totals, taxes (float), subtotal, total tax, grand total |
| `Order`    | Persist an order + its line items (transaction) |

## Database Tables
- **customers** — `customer_id, customer_name, contact_number, order_number`
- **products** — `product_id, category, product_name, price`
- **orders** — `order_id, customer_id, subtotal, total_tax, grand_total`
- **order_items** — `order_item_id, order_id, product_id, quantity, total_price`

## Product Categories
- **Beauty & Personal Care** — Facial Cleanser, Shampoo, Conditioner, Body Wash, Body Lotion, Toothpaste
- **Grocery** — Rice, Eggs, Bread, Coffee, Sugar, Cooking Oil
- **Beverages** — Mineral Water, Orange Juice, Iced Tea, Coffee Drink, Energy Drink, Soda

## Tax Rates (float / double)
| Category | Rate |
|----------|------|
| Beauty & Personal Care | 12% |
| Grocery | 2% |
| Beverages | 5% |
