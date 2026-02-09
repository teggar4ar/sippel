#!/bin/sh
set -e

# Set default PORT if not provided (Cloud Run and Heroku provide this)
export PORT="${PORT:-8080}"

# Parse DATABASE_URL if provided (Heroku-style)
if [ -n "$DATABASE_URL" ]; then
    echo "Parsing DATABASE_URL for database configuration..."
    # Extract database type, user, password, host, port, and database name from URL
    # Format: postgres://user:pass@host:port/dbname or mysql://user:pass@host:port/dbname
    DB_TYPE=$(echo "$DATABASE_URL" | sed -n 's/^\([^:]*\):\/\/.*/\1/p')
    DB_USER=$(echo "$DATABASE_URL" | sed -n 's/^[^:]*:\/\/\([^:]*\):.*/\1/p')
    DB_PASS=$(echo "$DATABASE_URL" | sed -n 's/^[^:]*:\/\/[^:]*:\([^@]*\)@.*/\1/p')
    DB_HOST=$(echo "$DATABASE_URL" | sed -n 's/^[^:]*:\/\/[^@]*@\([^:]*\):.*/\1/p')
    DB_PORT=$(echo "$DATABASE_URL" | sed -n 's/^[^:]*:\/\/[^@]*@[^:]*:\([^/]*\)\/.*/\1/p')
    DB_NAME=$(echo "$DATABASE_URL" | sed -n 's/^[^:]*:\/\/[^/]*\/\(.*\)/\1/p')

    # Map database type
    case "$DB_TYPE" in
        postgres|postgresql)
            export DB_CONNECTION="pgsql"
            ;;
        mysql)
            export DB_CONNECTION="mysql"
            ;;
    esac

    export DB_HOST="$DB_HOST"
    export DB_PORT="$DB_PORT"
    export DB_DATABASE="$DB_NAME"
    export DB_USERNAME="$DB_USER"
    export DB_PASSWORD="$DB_PASS"

    echo "Database configured: $DB_CONNECTION @ $DB_HOST:$DB_PORT/$DB_DATABASE"
fi

# Ensure required directories exist
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

# Optimize Laravel for production
php artisan config:cache
# Note: route:cache disabled because routes/web.php contains closures
# Convert closure routes to controllers before enabling route caching
# php artisan route:cache
php artisan view:cache

# If PORT env variable changed at runtime (Cloud Run dynamic port), update nginx config
if [ "$PORT" != "8080" ] && [ -w /etc/nginx/conf.d/default.conf ]; then
    echo "Updating nginx to listen on port $PORT..."
    sed -i "s/listen [0-9]\+;/listen $PORT;/" /etc/nginx/conf.d/default.conf
fi

exec "$@"
