--
-- Create a test database
--
CREATE DATABASE `dbbackuptest` DEFAULT COLLATE utf8mb4_unicode_520_ci;
GRANT ALL PRIVILEGES ON `dbbackuptest`.* to 'dbbackup'@'localhost' IDENTIFIED BY 'DBb@c|<uP';
