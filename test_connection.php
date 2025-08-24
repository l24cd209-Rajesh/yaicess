<?php
// Test database connection
echo "<h2>🔍 Testing Database Connection</h2>";

try {
    // Test basic connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    
    echo "<p>Testing connection to MySQL server...</p>";
    $conn = new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>❌ Connection failed: " . $conn->error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Connected to MySQL server successfully!</p>";
        
        // Test database creation
        echo "<p>Testing database creation...</p>";
        $sql = "CREATE DATABASE IF NOT EXISTS event_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color: green;'>✅ Database 'event_db' created/verified successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error creating database: " . $conn->error . "</p>";
        }
        
        // Test database selection
        if ($conn->select_db("event_db")) {
            echo "<p style='color: green;'>✅ Database 'event_db' selected successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error selecting database: " . $conn->error . "</p>";
        }
        
        $conn->close();
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📋 Next Steps:</h3>";
echo "<p>1. If connection is successful, <a href='init_db.php'>click here to initialize tables</a></p>";
echo "<p>2. If connection fails, make sure XAMPP MySQL service is running</p>";
echo "<p>3. Check XAMPP Control Panel and start MySQL service if needed</p>";
?>
