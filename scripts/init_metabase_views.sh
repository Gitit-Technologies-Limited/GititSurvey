#!/bin/bash
# ============================================
# Initialize Metabase Views on Startup
# ============================================
# This runs automatically when container starts
# Creates global views if they don't exist
# ============================================

set -e

DB_USER="${DB_USER:-surveyapp_user}"
DB_PASS="${DB_PASSWORD:-1234567}"
DB_NAME="${DB_NAME:-surveyapp}"
DB_HOST="${DB_HOST:-mysql}"

# Function to execute MySQL commands
exec_mysql() {
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "$1" 2>&1 | grep -v "Warning" || true
}

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
until exec_mysql "SELECT 1" | grep -q "1" 2>/dev/null; do
    echo "  Retrying..."
    sleep 2
done

echo "MySQL is ready. Checking Metabase views..."

# Check if views exist
VIEW_EXISTS=$(exec_mysql "SHOW TABLES LIKE 'v_survey_overview';" | wc -l | tr -d ' ')

if [ "$VIEW_EXISTS" -eq 0 ]; then
    echo "Creating Metabase global views..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/metabase_setup_views.sql 2>&1 | grep -v "Warning" || true
    echo "✓ Global views created"

    # Grant permissions
    ROOT_PASS=$(grep "^MYSQL_ROOT_PASSWORD=" /var/www/html/.env | cut -d'=' -f2 || echo "rootpassword")
    mysql -h "$DB_HOST" -u root -p"$ROOT_PASS" "$DB_NAME" <<SQL 2>&1 | grep -v "Warning" || true
        GRANT SELECT ON $DB_NAME.v_question_details TO 'metabase_readonly'@'%';
        GRANT SELECT ON $DB_NAME.v_survey_overview TO 'metabase_readonly'@'%';
        FLUSH PRIVILEGES;
SQL
    echo "✓ Permissions granted"
else
    echo "✓ Metabase global views already exist"
fi

# Auto-create views for all active surveys
echo "Checking for active surveys..."
ACTIVE_SURVEYS=$(exec_mysql "
    SELECT s.sid
    FROM surveys s
    WHERE s.active = 'Y'
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = '$DB_NAME'
        AND table_name = CONCAT('survey_', s.sid)
    )
    AND NOT EXISTS (
        SELECT 1 FROM information_schema.views
        WHERE table_schema = '$DB_NAME'
        AND table_name = CONCAT('v_survey_responses_', s.sid)
    );
")

if [ -n "$ACTIVE_SURVEYS" ]; then
    echo "Found active surveys without views. Creating them..."

    ROOT_PASS=$(grep "^MYSQL_ROOT_PASSWORD=" /var/www/html/.env | cut -d'=' -f2 || echo "rootpassword")

    for SID in $ACTIVE_SURVEYS; do
        echo "  Processing survey $SID..."

        VIEW_NAME="v_survey_responses_${SID}"
        TABLE_NAME="survey_${SID}"

        # Check if token column exists
        TOKEN_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '$DB_NAME'
              AND TABLE_NAME = '$TABLE_NAME'
              AND COLUMN_NAME = 'token';
        " 2>&1 | grep -v "Warning" | tail -1)

        if [ "$TOKEN_EXISTS" = "1" ]; then
            TOKEN_COLUMN="token,"
        else
            TOKEN_COLUMN="NULL as token,"
        fi

        # Get question columns for this survey
        QUESTION_COLUMNS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "
            SELECT GROUP_CONCAT(
              CONCAT('\`', COLUMN_NAME, '\` as q_', COLUMN_NAME)
              SEPARATOR ', '
            )
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '$DB_NAME'
              AND TABLE_NAME = '$TABLE_NAME'
              AND COLUMN_NAME REGEXP '^[0-9]+X[0-9]+X[0-9]+\$';
        " 2>&1 | grep -v "Warning" | tail -1)

        if [ -z "$QUESTION_COLUMNS" ] || [ "$QUESTION_COLUMNS" = "NULL" ]; then
            QUESTION_COLUMNS_SQL=""
        else
            QUESTION_COLUMNS_SQL=", $QUESTION_COLUMNS"
        fi

        # Create view
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<SQL 2>&1 | grep -v "Warning" || true
DROP VIEW IF EXISTS $VIEW_NAME;

CREATE VIEW $VIEW_NAME AS
SELECT
  id as response_id,
  $TOKEN_COLUMN
  submitdate,
  lastpage,
  startlanguage,
  $SID as survey_id,
  CASE
    WHEN submitdate IS NOT NULL THEN 'Completed'
    WHEN lastpage > 0 THEN 'In Progress'
    ELSE 'Not Started'
  END as response_status,
  CASE WHEN submitdate IS NOT NULL THEN 1 ELSE 0 END as is_completed,
  DATE(submitdate) as submission_date,
  HOUR(submitdate) as submission_hour,
  DAYNAME(submitdate) as submission_day_of_week,
  DAYOFWEEK(submitdate) as submission_day_number,
  WEEK(submitdate) as submission_week,
  MONTH(submitdate) as submission_month,
  YEAR(submitdate) as submission_year$QUESTION_COLUMNS_SQL
FROM $TABLE_NAME;
SQL

        # Grant permissions
        mysql -h "$DB_HOST" -u root -p"$ROOT_PASS" "$DB_NAME" <<SQL 2>&1 | grep -v "Warning" || true
GRANT SELECT ON $DB_NAME.$VIEW_NAME TO 'metabase_readonly'@'%';
FLUSH PRIVILEGES;
SQL

        echo "    ✓ Created $VIEW_NAME"
    done
    echo "✓ All active surveys processed"
else
    echo "✓ All active surveys already have views"
fi

echo "Metabase initialization complete!"
