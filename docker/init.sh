#!/bin/bash
set -e

if [ -f /docker/schema/schema.sql ]; then
    echo "Running schema.sql..."
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /docker/schema/schema.sql
else
    echo "No schema.sql found, skipping."
fi

if [ -f /docker/data/data.sql ]; then
    echo "Running data.sql..."
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /docker/data/data.sql
else
    echo "No data.sql found, skipping."
fi
