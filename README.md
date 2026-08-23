 FoodOrdering — DokoBites Food Ordering System

A web-based **Food Ordering System** developed using **PHP, MySQL, HTML, CSS, and JavaScript**. The system allows customers to browse restaurants, view food menus, place orders, and manage their orders. Restaurants can manage their food items and orders, while administrators can manage users, restaurants, and the overall system.

📌 Project Overview

**FoodOrdering** is designed to provide a simple and convenient platform for ordering traditional Nepalese and local foods online.

The system supports three main user roles:

* 👨‍💼 **Admin**
* 🏪 **Restaurant**
* 👤 **Customer**

Each role has different permissions and features according to their responsibilities.

---

## ✨ Features

### 👤 Customer

* Customer registration and login
* Secure password authentication
* Browse available restaurants
* View restaurant food menus
* View food details and prices
* Add food to cart
* Place food orders
* View order history
* Track order status
* View payment status
* Manage profile

### 🏪 Restaurant

* Restaurant registration and login
* Restaurant dashboard
* Add food items
* Update food items
* Delete food items
* Upload food images
* Manage food prices and descriptions
* View customer orders
* Update order status
* Manage restaurant information

### 👨‍💼 Admin

* Admin dashboard
* Manage customers
* Manage restaurants
* Approve or reject restaurants
* Manage food items
* View orders
* Manage users
* Monitor system activities
* Manage restaurant status

---

## 🍛 Example Nepalese Foods

The system can contain a variety of traditional Nepalese and local dishes, such as:

| Food             |   Price |
| ---------------- | ------: |
| Chatamari        | Rs. 200 |
| Bara             | Rs. 150 |
| Choila           | Rs. 350 |
| Yomari           | Rs. 120 |
| Momo             | Rs. 180 |
| Thukpa           | Rs. 200 |
| Sekuwa           | Rs. 350 |
| Newari Khaja Set | Rs. 450 |

---

## 🛠️ Technologies Used

### Frontend

* HTML5
* CSS3
* JavaScript

### Backend

* PHP

### Database

* MySQL
* phpMyAdmin

### Development Environment

* XAMPP
* Apache
* MySQL
* Git
* GitHub

---

## 📂 Project Structure

```text
FoodOrdering/
│
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── restaurants.php
│   ├── foods.php
│   └── orders.php
│
├── restaurant/
│   ├── dashboard.php
│   ├── add_food.php
│   ├── manage_food.php
│   └── orders.php
│
├── user/
│   ├── dashboard.php
│   ├── restaurants.php
│   ├── menu.php
│   ├── cart.php
│   └── orders.php
│
├── includes/
│   ├── db.php
│   ├── auth_check.php
│   └── header.php
│
├── uploads/
│   ├── restaurants/
│   └── foods/
│
├── css/
│   └── style.css
│
├── js/
│   └── script.js
│
├── index.php
├── login.php
├── register.php
├── logout.php
└── README.md
```

> The exact folder structure may vary depending on the current project implementation.

---

## 🗄️ Database

The project uses **MySQL** as its database.

The database can contain tables such as:

```text
users
restaurants
foods
orders
order_items
payments
```

### Main Relationships

```text
Users
  │
  ├── Restaurants
  │
  └── Orders
        │
        └── Order Items
              │
              └── Foods
```

Restaurants are associated with users, while orders are associated with customers and contain the selected food items.

---

## ⚙️ Installation

### 1. Install XAMPP

Download and install XAMPP.

Start:

```text
Apache
MySQL
```

### 2. Clone the Repository

Open Git Bash and run:

```bash
cd /e/xampp/htdocs
git clone https://github.com/sarkar-11/FoodOrdering.git
```

Then enter the project directory:

```bash
cd FoodOrdering
```

### 3. Create the Database

Open:

```text
http://localhost/phpmyadmin
```

Create a database, for example:

```text
food_ordering_system
```

Import the project's SQL database file if one is included.

Alternatively, create the required tables using the provided SQL scripts.

### 4. Configure Database Connection

Open the database connection file, for example:

```text
includes/db.php
```

Configure the MySQL connection:

```php
<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "food_ordering_system";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
```

Update the database name, username, or password if your XAMPP/MySQL configuration is different.

### 5. Start the Website

Open your browser and visit:

```text
http://localhost/FoodOrdering/
```

If your local folder is named `food_ordering_system`, use:

```text
http://localhost/food_ordering_system/
```

---

## 🔐 Authentication

The application provides role-based authentication.

### User

Customers can register and log in to access customer features.

### Restaurant

Restaurant accounts can access restaurant management features.

### Admin

Administrators can access system management features.

Passwords should be stored using secure password hashing such as PHP's:

```php
password_hash()
```

and verified using:

```php
password_verify()
```

---

## 🛒 Order Process

The general ordering process is:

```text
Customer Login
      ↓
Browse Restaurants
      ↓
Select Restaurant
      ↓
View Food Menu
      ↓
Add Food to Cart
      ↓
Review Cart
      ↓
Place Order
      ↓
Restaurant Receives Order
      ↓
Restaurant Updates Status
      ↓
Customer Views Order Status
```

---

## 💳 Payment Status

Orders can maintain payment information such as:

```text
Paid
Unpaid
```

The system can also maintain order status such as:

```text
Pending
Confirmed
Preparing
Ready
Out for Delivery
Delivered
Cancelled
```

---

## 🔒 Security Features

The project should use:

* Password hashing
* Prepared SQL statements
* Session-based authentication
* Role-based authorization
* Input validation
* File upload validation
* Access control
* SQL injection prevention

---

## 🖥️ System Requirements

Minimum requirements:

```text
Operating System : Windows / Linux / macOS
Web Server       : Apache
PHP              : PHP 7.4+
Database         : MySQL 5.7+ / MariaDB
Browser          : Chrome / Firefox / Edge
Server Package   : XAMPP
```

---

## 🚀 Future Enhancements

Possible future improvements include:

* Online payment gateway
* Real-time order tracking
* Restaurant ratings and reviews
* Customer feedback
* Food search and filtering
* Favorite restaurants
* Discount coupons
* Delivery management
* Email notifications
* SMS notifications
* Mobile application
* REST API
* Advanced admin analytics
* Restaurant location/map integration

---

## 🎯 Project Objectives

The main objectives of this project are:

1. To develop an easy-to-use online food ordering platform.
2. To provide customers with convenient access to local food.
3. To allow restaurants to manage their menus and orders.
4. To provide administrators with centralized system management.
5. To reduce manual food ordering processes.
6. To demonstrate practical implementation of PHP and MySQL.
7. To implement authentication and role-based access control.

---

## 👨‍💻 Development

This project was developed as a web-based academic/software development project using:

```text
PHP
MySQL
HTML
CSS
JavaScript
XAMPP
Git
GitHub
```

---

## 📄 License

This project is intended for educational and development purposes.

You are free to modify and improve the project according to your requirements.

---

## ⭐ Support

If you find this project useful, consider giving the repository a ⭐ on GitHub.

**FoodOrdering — Fresh Food • Fast Delivery • Happy Customers 🇳🇵**

