-- Metabase MySQL Read-Only User Setup Script
-- This script creates a read-only user for Metabase to access LimeSurvey data
--
-- Usage:
--   docker exec -i mysql mysql -uroot -prootpassword < scripts/metabase_setup.sql
--
-- Or connect to MySQL container and run manually:
--   docker exec -it mysql mysql -uroot -prootpassword
--   source /var/www/html/scripts/metabase_setup.sql

-- Create read-only user for Metabase
-- Password: metabase_readonly_pass (change this in production!)
CREATE USER IF NOT EXISTS 'metabase_readonly'@'%' IDENTIFIED BY 'metabase_readonly_pass';

-- Grant SELECT (read-only) privileges on the surveyapp database
GRANT SELECT ON `surveyapp`.* TO 'metabase_readonly'@'%';

-- Grant permission to view table structures and metadata
GRANT SHOW VIEW ON `surveyapp`.* TO 'metabase_readonly'@'%';

-- Flush privileges to apply changes
FLUSH PRIVILEGES;

-- Verify user was created
SELECT User, Host FROM mysql.user WHERE User = 'metabase_readonly';

-- Show granted permissions
SHOW GRANTS FOR 'metabase_readonly'@'%';
