-- Drop existing tables (optional: only if you want a clean setup)
DROP TABLE IF EXISTS user_event_registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS participants;
DROP TABLE IF EXISTS admin;

-- Participants table (users)
CREATE TABLE participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  referral VARCHAR(50) NULL,
  payment_status VARCHAR(20) NULL,
  transaction_id VARCHAR(100) NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  amount INT NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Event Registrations table
-- Modified: replaced user_id with username
CREATE TABLE user_event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  event_id INT NOT NULL,
  registration_code VARCHAR(50) NOT NULL UNIQUE,
  event_name VARCHAR(150) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  order_id VARCHAR(100) NULL,
  payment_id VARCHAR(100) NULL,
  signature VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  INDEX (order_id),
  INDEX (payment_id),
  INDEX (registration_code)
);

-- Admin table
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

-- Insert default admin (password = admin123, MD5 hashed)
INSERT INTO admin (username, password) VALUES ('admin', MD5('admin123'));

-- Seed some default events
INSERT INTO events (name, description, amount, currency, is_active) VALUES
  ('Innovation Conference Pass', 'Full access to all sessions and workshops', 100, 'INR', 1),
  ('Workshop: AI Trends', 'Deep dive into current AI trends and applications', 100, 'INR', 1),
  ('Networking Dinner', 'Evening networking with speakers and attendees', 100, 'INR', 1);

ALTER TABLE participants ADD COLUMN user_code VARCHAR(50) UNIQUE;
ALTER TABLE participants ADD COLUMN profile_image VARCHAR(255) NULL;

