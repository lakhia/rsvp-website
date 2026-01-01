#!/bin/bash

# Script to extract RSVP data for specified dates
# Usage: ./get_rsvp_data.sh <db_host> <db_user> <db_pass> <db_name> YYYY-MM-DD [YYYY-MM-DD ...]
# Output: JSON array of arrays suitable for Python/pandas processing
# Format: [[thaali, size, dish, name, area, date], ...]

set -e

# Check minimum arguments (4 db params + at least 1 date)
if [ $# -lt 5 ]; then
    echo "Error: Insufficient arguments" >&2
    echo "Usage: $0 <db_host> <db_user> <db_pass> <db_name> YYYY-MM-DD [YYYY-MM-DD ...]" >&2
    echo "Example: $0 localhost myuser mypass mydb 2024-12-25 2024-12-26" >&2
    exit 1
fi

# Parse database connection parameters
DB_HOST="$1"
DB_USER="$2"
DB_PASS="$3"
DB_NAME="$4"
shift 4

# Build SQL IN clause from dates
date_list=""
for date_arg in "$@"; do
    # Validate date format
    if ! [[ $date_arg =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}$ ]]; then
        echo "Error: Invalid date format '$date_arg'. Expected YYYY-MM-DD" >&2
        exit 1
    fi

    if [ -z "$date_list" ]; then
        date_list="'$date_arg'"
    else
        date_list="$date_list, '$date_arg'"
    fi
done

# Build MySQL query
# Output format: [thaali, size, dish, name, area, date]
SQL_QUERY="
SELECT
    r.thaali_id AS thaali,
    r.size,
    e.details AS dish,
    CONCAT(f.firstName, ' ', f.lastName) AS name,
    f.area,
    DATE_FORMAT(r.date, '%Y-%m-%d') AS date
FROM rsvps r
INNER JOIN events e ON r.date = e.date
INNER JOIN family f ON r.thaali_id = f.thaali
WHERE r.rsvp = 1
  AND r.date IN ($date_list)
ORDER BY r.date, r.thaali_id;
"

# Execute query and format as JSON array of arrays
mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
    --batch \
    --skip-column-names \
    --execute="${SQL_QUERY}" \
    2>/dev/null | \
awk 'BEGIN {
    FS = "\t"
    print "["
    first = 1
}
{
    if (!first) print ","
    first = 0
    # Escape quotes and backslashes for JSON
    gsub(/\\/, "\\\\", $2)
    gsub(/\\/, "\\\\", $3)
    gsub(/\\/, "\\\\", $4)
    gsub(/\\/, "\\\\", $5)
    gsub(/\\/, "\\\\", $6)
    gsub(/"/, "\\\"", $2)
    gsub(/"/, "\\\"", $3)
    gsub(/"/, "\\\"", $4)
    gsub(/"/, "\\\"", $5)
    gsub(/"/, "\\\"", $6)
    # Format: [thaali, "size", "dish", "name", "area", "date"]
    printf "  [%s, \"%s\", \"%s\", \"%s\", \"%s\", \"%s\"]", $1, $2, $3, $4, $5, $6
}
END {
    print ""
    print "]"
}'

# Check if query was successful
if [ $? -ne 0 ]; then
    echo "Error: Database query failed" >&2
    exit 1
fi
