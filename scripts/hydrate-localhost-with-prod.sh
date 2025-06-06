#!/bin/bash

# Load environment variables from .env file
if [ ! -f .env ]; then
    echo "Error: .env file not found. Please create it from .env.example"
    exit 1
fi

# Source .env file
export $(grep -v '^#' .env | xargs)

# Production database config
# REPLACE with your database credentials
PROD_HOST=""
PROD_USER=""
PROD_DB=""


# Local database config from .env
LOCAL_USER=$DB_USERNAME
LOCAL_DB=$DB_NAME
LOCAL_PASS=$DB_PASSWORD

# Validate local database connection
echo "Validating local database connection..."
if ! docker-compose exec -T -e MYSQL_PWD=$LOCAL_PASS db mysql -u $LOCAL_USER -e "USE $LOCAL_DB;" 2>/dev/null; then
    echo "Error: Cannot connect to local database. Make sure Docker containers are running."
    exit 1
fi
echo "Local database connection successful."

# Prompt for production password securely
echo "Enter production DB password:"
read -s PROD_PASS

# Create dump (without --databases flag to avoid CREATE DATABASE statements)
echo "Dumping production database..."
mysqldump --no-tablespaces -h $PROD_HOST -u $PROD_USER -p$PROD_PASS $PROD_DB > production_dump.sql

if [ ! -s production_dump.sql ]; then
    echo "Error: Dump file is empty or wasn't created"
    exit 1
fi
echo "Dump file created successfully ($(wc -l < production_dump.sql) lines)"

# Import into Docker MySQL container
echo "Importing dump into Docker MySQL container (database: $LOCAL_DB)..."
docker-compose exec -T -e MYSQL_PWD=$LOCAL_PASS db mysql -u $LOCAL_USER $LOCAL_DB < production_dump.sql

if [ $? -eq 0 ]; then
    echo "Import successful!"
    docker-compose exec -T -e MYSQL_PWD=$LOCAL_PASS db mysql -u $LOCAL_USER -e "SELECT COUNT(*) as 'Total Families' FROM $LOCAL_DB.family;"
else
    echo "Error: Import failed"
    exit 1
fi

# Clean up dump file
echo "Cleaning up dump file..."
rm production_dump.sql

echo "Done!"
