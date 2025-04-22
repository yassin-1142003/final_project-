-- Script to create database user with access from localhost
-- Run this on your local MySQL server

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS mydb;

-- Create user with access from localhost only
CREATE USER 'mydb_admin'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password';

-- Grant all privileges to the database
GRANT ALL PRIVILEGES ON mydb.* TO 'mydb_admin'@'localhost';

-- Optional: Grant specific privileges if needed
-- Example: GRANT SELECT, INSERT ON mydb.* TO 'readonly_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES; 