<?php
require_once __DIR__ . '/db_config.php';

// Create required tables if they don't exist and add missing columns safely

// Participants table (users)
$conn->query("CREATE TABLE IF NOT EXISTS participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  referral VARCHAR(50) NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add optional columns if not present (for backward compatibility with admin pages)
try {
    $conn->query("ALTER TABLE participants ADD COLUMN payment_status VARCHAR(20) NULL");
} catch (Exception $e) {
    // Column already exists, ignore
}
try {
    $conn->query("ALTER TABLE participants ADD COLUMN transaction_id VARCHAR(100) NULL");
} catch (Exception $e) {
    // Column already exists, ignore
}

// Events table
$conn->query("CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  amount INT NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// User Event Registrations
$conn->query("CREATE TABLE IF NOT EXISTS user_event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  registration_code VARCHAR(50) NOT NULL UNIQUE,
  event_name VARCHAR(150) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  order_id VARCHAR(100) NULL,
  payment_id VARCHAR(100) NULL,
  signature VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES participants(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  INDEX (order_id),
  INDEX (payment_id),
  INDEX (registration_code)
)");

// Add registration_code column to existing table if it doesn't exist
try {
    $conn->query("ALTER TABLE user_event_registrations ADD COLUMN registration_code VARCHAR(50) NOT NULL UNIQUE AFTER event_id");
} catch (Exception $e) {
    // Column already exists, ignore
}

// Add event_name column to existing table if it doesn't exist
try {
    $conn->query("ALTER TABLE user_event_registrations ADD COLUMN event_name VARCHAR(150) NOT NULL AFTER registration_code");
} catch (Exception $e) {
    // Column already exists, ignore
}

// Add amount column to existing table if it doesn't exist
try {
    $conn->query("ALTER TABLE user_event_registrations ADD COLUMN amount DECIMAL(10,2) NOT NULL AFTER event_name");
} catch (Exception $e) {
    // Column already exists, ignore
}

// Seed some default events if none exist
$result = $conn->query("SELECT COUNT(*) as c FROM events");
$row = $result ? $result->fetch_assoc() : ['c' => 0];
if ((int)$row['c'] === 0) {
  $conn->query("INSERT INTO events (name, description, amount, currency, is_active) VALUES
    ('Innovation Conference Pass', 'Full access to all sessions and workshops', 100, 'INR', 1),
    ('Workshop: AI Trends', 'Deep dive into current AI trends and applications', 100, 'INR', 1),
    ('Networking Dinner', 'Evening networking with speakers and attendees', 100, 'INR', 1)
  ");
}

// Ensure default admin exists
$conn->query("CREATE TABLE IF NOT EXISTS admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
)");

$res = $conn->query("SELECT COUNT(*) as c FROM admin");
$rc = $res ? $res->fetch_assoc() : ['c' => 0];
if ((int)$rc['c'] === 0) {
  // default admin: admin / admin123 (md5 as per original script)
  $conn->query("INSERT INTO admin (username, password) VALUES ('admin', MD5('admin123'))");
}

?>

