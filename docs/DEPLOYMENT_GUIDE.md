# Production Deployment Guide - SIPPEL

## Pre-Deployment Checklist

### 1. Server Requirements

- **OS**: Ubuntu 22.04 LTS (recommended)
- **PHP**: 8.3+
- **MySQL**: 8.0+ / MariaDB 10.6+
- **Nginx**: Latest stable
- **Node.js**: 18+ (untuk build assets)
- **Composer**: 2.x
- **RAM**: Minimum 2GB
- **Storage**: Minimum 20GB

### 2. Required PHP Extensions

```bash
sudo apt install php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
    php8.3-bcmath php8.3-curl php8.3-zip php8.3-gd php8.3-intl \
    php8.3-redis php8.3-opcache
```

### 3. Environment Configuration

Buat file `.env` di server dengan konfigurasi berikut:

```env
# Application
APP_NAME=SIPPEL
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY
APP_DEBUG=false
APP_URL=https://your-domain.com

# Locale
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta
APP_FALLBACK_LOCALE=en

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sippel_production
DB_USERNAME=sippel_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Session (Secure)
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=your-domain.com

# Cache
CACHE_STORE=file
# Untuk high-traffic, gunakan Redis:
# CACHE_STORE=redis

# Queue
QUEUE_CONNECTION=database

# Logging
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

# Mail (sesuaikan dengan provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="SIPPEL"

# Security
BCRYPT_ROUNDS=12

# Debugbar (HARUS false di production!)
DEBUGBAR_ENABLED=false
```

---

## Deployment Steps

### Step 1: Clone Repository

```bash
cd /var/www
git clone https://github.com/your-repo/sippel.git
cd sippel
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies (tanpa dev packages)
composer install --no-dev --optimize-autoloader

# Install Node dependencies dan build assets
npm ci
npm run build
```

### Step 3: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env dengan konfigurasi production
nano .env
```

### Step 4: Setup Database

```bash
# Buat database
mysql -u root -p
CREATE DATABASE sippel_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sippel_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON sippel_production.* TO 'sippel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force

# Seed initial data (roles, admin user)
php artisan db:seed --force
```

### Step 5: Storage & Permissions

```bash
# Create storage link
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data /var/www/sippel
sudo chmod -R 755 /var/www/sippel
sudo chmod -R 775 /var/www/sippel/storage
sudo chmod -R 775 /var/www/sippel/bootstrap/cache
```

### Step 6: Optimize Application

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache
php artisan filament:cache-components

# Optimize composer autoloader
composer dump-autoload --optimize --classmap-authoritative
```

### Step 7: Configure Nginx

Buat file `/etc/nginx/sites-available/sippel`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    root /var/www/sippel/public;

    # SSL Configuration (gunakan certbot untuk Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    index index.php;
    charset utf-8;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to sensitive files
    location ~ /\.(env|git|htaccess) {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/sippel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 8: Setup SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

### Step 9: Configure Queue Worker (Systemd)

Buat file `/etc/systemd/system/sippel-worker.service`:

```ini
[Unit]
Description=SIPPEL Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/sippel
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable sippel-worker
sudo systemctl start sippel-worker
```

### Step 10: Configure Scheduler (Cron)

```bash
sudo crontab -e -u www-data
```

Tambahkan:
```
* * * * * cd /var/www/sippel && php artisan schedule:run >> /dev/null 2>&1
```

---

## Post-Deployment

### Verify Deployment

```bash
# Check application status
php artisan about

# Check routes are cached
php artisan route:list

# Test database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected!';"

# Check logs
tail -f storage/logs/laravel.log
```

### Save Admin Credentials

Setelah seeding, **SIMPAN** kredensial admin yang ditampilkan di console!

---

## Maintenance Commands

```bash
# Clear all cache
php artisan optimize:clear

# Re-cache everything
php artisan optimize

# Run migrations (update)
php artisan migrate --force

# Restart queue workers
php artisan queue:restart

# Enter maintenance mode
php artisan down --secret="your-secret-token"

# Exit maintenance mode
php artisan up
```

---

## Backup Strategy

### Database Backup (Daily)

```bash
# Add to crontab
0 2 * * * mysqldump -u sippel_user -p'PASSWORD' sippel_production | gzip > /backups/db/sippel_$(date +\%Y\%m\%d).sql.gz
```

### File Backup

```bash
# Backup storage folder
tar -czf /backups/files/storage_$(date +%Y%m%d).tar.gz /var/www/sippel/storage/app
```

---

## Troubleshooting

### 500 Error
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Nginx logs
tail -f /var/log/nginx/error.log

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Session Issues
```bash
php artisan session:table
php artisan migrate
```

### Clear All Cache
```bash
php artisan optimize:clear
composer dump-autoload
```

---

## Security Checklist

- [ ] `APP_DEBUG=false`
- [ ] `DEBUGBAR_ENABLED=false`
- [ ] Strong database password
- [ ] SSL/HTTPS enabled
- [ ] Firewall configured (UFW)
- [ ] Regular security updates
- [ ] Backup strategy implemented
- [ ] Log monitoring enabled
