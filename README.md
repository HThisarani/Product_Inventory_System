# Product Inventory System
# How to Run
1. Download the project.
2. Copy the folder into xampp/htdocs or wamp/www.
3. Start Apache and MySQL.
4. Import database.sql(nventory_db.sql) from the database folder.
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
  
## API Endpoints
GET      /pages/dashboard.php            → User dashboard
GET      /pages/item_add.php             → Show add item form
POST     /pages/item_add.php             → Create new item
GET      /pages/item_list.php            → List all items
GET      /pages/item_view.php?id=        → View single item
GET      /pages/item_edit.php?id=        → Show edit item form
PUT      /pages/item_edit.php?id=        → Update item
DELETE   /pages/item_delete.php?id=      → Delete item
GET      /pages/login.php                → Show login form
POST     /pages/login.php                → Process login
GET      /pages/logout.php               → Log out user
GET      /pages/profile.php              → View profile
PUT      /pages/profile.php              → Update profile
GET      /pages/register.php             → Show registration form
POST     /pages/register.php             → Process registration
