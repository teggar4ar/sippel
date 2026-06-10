#!/bin/sh
set -e

# Set default PORT if not provided (Heroku provides this dynamically)
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

# Generate nginx config from template with actual PORT (Heroku provides this dynamically)
echo "Generating nginx config for port $PORT..."
envsubst '$PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Ensure required directories exist
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

# Optimize Laravel for production
# Ensure package manifest is built (safety net if build-time scripts were skipped)
php artisan package:discover --ansi

# Publish filament assets (JS/CSS to public/)
php artisan filament:assets

# Cache config for production (includes package manifest)
php artisan config:cache
# Note: route:cache disabled because routes/web.php contains closures
# Convert closure routes to controllers before enabling route caching
# php artisan route:cache
# Note: view:cache is skipped — Flux/Livewire components are resolved dynamically

exec "$@"