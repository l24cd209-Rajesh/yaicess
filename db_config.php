<?php
$servername = "localhost";   // usually 'localhost'
$username   = "root";        // your database username
$password   = "";            // your database password
$dbname     = "event_db";    // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Set strict SQL mode for security
$conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

// Session settings should be set BEFORE session_start(), not in db_config.php
// Removed: ini_set('session.gc_maxlifetime', 3600);
// Removed: ini_set('session.cookie_lifetime', 3600);

// Don't close connection here - other scripts need it!
?>
