<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a temporary connection without selecting a database
$temp_conn = new mysqli('localhost', 'root', '');
if ($temp_conn->connect_error) {
    die("Connection failed: " . $temp_conn->connect_error);
}

// Create database if it doesn't exist
$temp_conn->query("CREATE DATABASE IF NOT EXISTS property_db");
$temp_conn->close();

// Include the main database connection
require_once 'db.php';

echo "<h2>Database Setup</h2>";

// Drop existing tables
$conn->query("DROP TABLE IF EXISTS properties");
$conn->query("DROP TABLE IF EXISTS users");

// Create users table
$create_users = "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($create_users)) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create properties table
$create_properties = "CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($create_properties)) {
    echo "Properties table created successfully<br>";
} else {
    echo "Error creating properties table: " . $conn->error . "<br>";
}

// Insert admin user
$admin_password = '$2y$10$8K1p/a0WpYoKv2oPMy0pouqQQYqibwpGoCm1aQD3YfvgHNHjEEHK.'; // admin123
$insert_admin = "INSERT INTO users (username, email, password, role) 
                VALUES ('admin', 'admin@example.com', '$admin_password', 'admin')
                ON DUPLICATE KEY UPDATE id=id";

if ($conn->query($insert_admin)) {
    echo "Admin user created successfully<br>";
} else {
    echo "Error creating admin user: " . $conn->error . "<br>";
}

echo "<br><strong>Setup completed!</strong><br>";
echo "<a href='index.php'>Go to login page</a>";

$conn->close();
?> 