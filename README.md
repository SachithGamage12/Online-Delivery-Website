# Online Delivery Management System

A web-based delivery and order management platform built using PHP and MySQL.
The system supports store registration, order processing, delivery assignment,
and administrative control through role-based dashboards.

## Features

- Store and delivery user registration/login
- Order placement and checkout workflow
- Delivery assignment and tracking
- Admin approval and order management
- Role-based dashboards (Admin / Store / Delivery)

## Tech Stack

- PHP (Procedural with modular structure)
- MySQL Database
- PHPMailer (Email handling)
- HTML / CSS / JavaScript
- Apache (XAMPP/LAMP environment)

## Project Structure

/config      - Database configuration  
/api         - API endpoints  
/admin       - Admin panel files  
/public      - User-facing pages  

## Setup Instructions

1. Clone the repository
2. Import `lakway_delivery.sql` into MySQL
3. Update database credentials in `config/db_connection.php`
4. Place project in Apache root (htdocs)
5. Access via http://localhost/project-name

## Note

This repository is a demonstration project.
No production client data is included.
