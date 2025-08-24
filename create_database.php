<?php
// Database creation script for XAMPP
$servername = "localhost";
$username = "root";
$password = "";

try {
    // Create connection without specifying database
    $conn = new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to MySQL server successfully!<br>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS event_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "Database 'event_db' created successfully or already exists!<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }
    
    // Select the database
    $conn->select_db("event_db");
    echo "Database 'event_db' selected successfully!<br>";
    
    $conn->close();
    
    echo "<br>✅ Database setup complete!<br>";
    echo "<a href='init_db.php'>Click here to initialize tables</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
