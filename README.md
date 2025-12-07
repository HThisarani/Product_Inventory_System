# Product Inventory System
# How to Run
1. Download the project.
2. Copy the folder into xampp/htdocs or wamp/www.
3. Start Apache and MySQL.
4. Import database.sql from the database folder.
5. Open http://localhost/product_inventory/pages/login.php

## Login Credentials
username: Admin

password: 1234

## Screenshots
Screenshots are available in the /screenshots folder.

## Technologies Used
- PHP
- HTML
- JavaScript
- Inline CSS
- Bootstrap
- 
# API Endpoints
GET      /dashboard.php          → User dashboard
GET      /item_add.php           → Show add item form
POST     /item_add.php           → Create new item
GET      /item_list.php          → List all items
GET      /item_view.php?id=      → View single item
GET      /item_edit.php?id=      → Show edit item form
PUT      /item_edit.php?id=      → Update item 
DELETE   /item_delete.php?id=    → Delete item 
GET      /login.php              → Show login form
POST     /login.php              → Process login
GET      /logout.php             → Log out user
GET      /profile.php            → View profile
PUT      /profile.php            → Update profile 
GET      /register.php           → Show registration form
POST     /register.php           → Process registration
