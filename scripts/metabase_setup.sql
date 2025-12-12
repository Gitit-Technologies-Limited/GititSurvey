-- Metabase MySQL Setup Script
-- This script creates:
-- 1. Metabase application database and user (for storing Metabase's own data)
-- 2. Read-only user for Metabase to access survey data
--
-- IMPORTANT: Update the passwords below to match your .env file:
--   - Line 21: METABASE_MYSQL_PASSWORD
--   - Line 26: METABASE_READONLY_PASSWORD
--
-- This script runs automatically on first container startup via:
--   /docker-entrypoint-initdb.d/metabase_setup.sql
--
-- Manual execution (if needed):
--   docker exec -i mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} < scripts/metabase_setup.sql

-- 1. Create Metabase application database
CREATE DATABASE IF NOT EXISTS `metabase` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Create Metabase application user (for Metabase's own tables)
-- Using mysql_native_password for compatibility with Metabase's MySQL driver
CREATE USER IF NOT EXISTS 'metabase_user'@'%' IDENTIFIED WITH mysql_native_password BY 'metabase_secure_pass';
GRANT ALL PRIVILEGES ON `metabase`.* TO 'metabase_user'@'%';

-- 3. Create read-only user for Metabase to access survey data
CREATE USER IF NOT EXISTS 'metabase_readonly'@'%' IDENTIFIED BY 'metabase_readonly_pass';
GRANT SELECT ON `surveyapp`.* TO 'metabase_readonly'@'%';
GRANT SHOW VIEW ON `surveyapp`.* TO 'metabase_readonly'@'%';

-- Flush privileges to apply changes
FLUSH PRIVILEGES;

-- Verify users were created
SELECT User, Host FROM mysql.user WHERE User LIKE 'metabase%';

-- Show granted permissions
SHOW GRANTS FOR 'metabase_user'@'%';
SHOW GRANTS FOR 'metabase_readonly'@'%';
