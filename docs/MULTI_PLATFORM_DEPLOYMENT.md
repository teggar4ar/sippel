# Multi-Platform Deployment Guide

This guide covers deploying SIPPEL to multiple cloud platforms with different database backends.

## ✅ Portability Features

Your application is now **fully portable** with:

- ✅ **Both MySQL and PostgreSQL drivers** installed in Docker image
- ✅ **Dynamic PORT configuration** (works with Cloud Run, Heroku, etc.)
- ✅ **DATABASE_URL parsing** (Heroku-style connection strings)
- ✅ **Environment-based configuration** (no hardcoded values)
- ✅ **Standard Laravel migrations** (compatible with both databases)

---

## 🚀 GCP Cloud Run Deployment (MySQL)

### Prerequisites
- Google Cloud SDK installed
- Cloud SQL MySQL instance created
- Project ID and region configured

### 1. Build and Push Docker Image

```bash
# Set variables
PROJECT_ID="your-gcp-project-id"
REGION="asia-southeast1"
SERVICE_NAME="sippel"

# Build image
docker build -t gcr.io/$PROJECT_ID/$SERVICE_NAME:latest .

# Push to Google Container Registry
docker push gcr.io/$PROJECT_ID/$SERVICE_NAME:latest
```

### 2. Deploy to Cloud Run

```bash
# Get Cloud SQL connection name
CLOUDSQL_CONNECTION=$(gcloud sql instances describe YOUR_INSTANCE_NAME --format="value(connectionName)")

# Deploy with MySQL connection
gcloud run deploy $SERVICE_NAME \
  --image gcr.io/$PROJECT_ID/$SERVICE_NAME:latest \
  --platform managed \
  --region $REGION \
  --allow-unauthenticated \
  --add-cloudsql-instances $CLOUDSQL_CONNECTION \
  --set-env-vars="APP_ENV=production" \
  --set-env-vars="APP_DEBUG=false" \
  --set-env-vars="DB_CONNECTION=mysql" \
  --set-env-vars="DB_HOST=/cloudsql/$CLOUDSQL_CONNECTION" \
  --set-env-vars="DB_PORT=3306" \
  --set-env-vars="DB_DATABASE=sippel" \
  --set-secrets="APP_KEY=app-key:latest" \
  --set-secrets="DB_PASSWORD=db-password:latest" \
  --set-secrets="DB_USERNAME=db-username:latest" \
  --min-instances=0 \
  --max-instances=10 \
  --memory=512Mi \
  --cpu=1
```

### 3. Run Migrations

```bash
# Create a one-off Cloud Run job for migrations
gcloud run jobs create $SERVICE_NAME-migrate \
  --image gcr.io/$PROJECT_ID/$SERVICE_NAME:latest \
  --region $REGION \
  --add-cloudsql-instances $CLOUDSQL_CONNECTION \
  --set-env-vars="APP_ENV=production" \
  --set-env-vars="DB_CONNECTION=mysql" \
  --set-env-vars="DB_HOST=/cloudsql/$CLOUDSQL_CONNECTION" \
  --set-env-vars="DB_PORT=3306" \
  --set-env-vars="DB_DATABASE=sippel" \
  --set-secrets="APP_KEY=app-key:latest" \
  --set-secrets="DB_PASSWORD=db-password:latest" \
  --set-secrets="DB_USERNAME=db-username:latest" \
  --command="php" \
  --args="artisan,migrate,--force"

# Execute the migration
gcloud run jobs execute $SERVICE_NAME-migrate --region $REGION
```

### 4. Environment Variables (Cloud Run)

Store secrets in Secret Manager:

```bash
# Create secrets
echo -n "your-app-key" | gcloud secrets create app-key --data-file=-
echo -n "your-db-username" | gcloud secrets create db-username --data-file=-
echo -n "your-db-password" | gcloud secrets create db-password --data-file=-
```

---

## 🎯 Heroku Deployment (PostgreSQL)

### Prerequisites
- Heroku CLI installed
- Heroku account created

### 1. Create Heroku App

```bash
# Login to Heroku
heroku login

# Create app
heroku create sippel-app --region us

# Add PostgreSQL addon
heroku addons:create heroku-postgresql:essential-0 --app sippel-app
```

### 2. Deploy via Git

```bash
# Initialize git (if not already)
git init
git add .
git commit -m "Initial commit"

# Add Heroku remote
heroku git:remote -a sippel-app

# Deploy
git push heroku main
```

### 3. Configure Environment

```bash
# Set Laravel environment
heroku config:set APP_ENV=production --app sippel-app
heroku config:set APP_DEBUG=false --app sippel-app
heroku config:set DB_CONNECTION=pgsql --app sippel-app

# Generate app key
heroku run php artisan key:generate --app sippel-app

# Set school info
heroku config:set SCHOOL_NAME="Your School Name" --app sippel-app
heroku config:set SCHOOL_ADDRESS="Your School Address" --app sippel-app
```

### 4. Run Migrations

Migrations run automatically via `Procfile` release phase, but you can also run manually:

```bash
heroku run php artisan migrate --force --app sippel-app
```

### 5. Alternative: Deploy via Docker

Create `heroku.yml`:

```yaml
build:
  docker:
    web: Dockerfile
run:
  web: /usr/bin/supervisord -c /etc/supervisord.conf
```

Then deploy:

```bash
# Set stack to container
heroku stack:set container --app sippel-app

# Deploy
git push heroku main
```

---

## 🔄 Database URL Parsing (Automatic)

Your entrypoint now automatically parses `DATABASE_URL` format used by Heroku:

```bash
# Heroku provides this format:
# postgres://user:pass@host:port/database

# Entrypoint converts it to Laravel env vars:
# DB_CONNECTION=pgsql
# DB_HOST=host
# DB_PORT=port
# DB_DATABASE=database
# DB_USERNAME=user
# DB_PASSWORD=pass
```

---

## 📝 Environment Variables Summary

### Required for All Platforms

| Variable | Description | Cloud Run | Heroku |
|----------|-------------|-----------|--------|
| `APP_KEY` | Laravel encryption key | Secret Manager | Auto-generated |
| `APP_ENV` | Environment (production) | ✅ | ✅ |
| `APP_DEBUG` | Debug mode (false) | ✅ | ✅ |
| `DB_CONNECTION` | Database driver | `mysql` | `pgsql` |
| `PORT` | HTTP port | Auto (8080) | Auto |

### Cloud Run (MySQL)

```env
DB_CONNECTION=mysql
DB_HOST=/cloudsql/PROJECT:REGION:INSTANCE
DB_PORT=3306
DB_DATABASE=sippel
DB_USERNAME=(from Secret Manager)
DB_PASSWORD=(from Secret Manager)
```

### Heroku (PostgreSQL)

```env
DB_CONNECTION=pgsql
DATABASE_URL=(auto-provided by Heroku)
# OR individually:
DB_HOST=(from DATABASE_URL)
DB_PORT=5432
DB_DATABASE=(from DATABASE_URL)
DB_USERNAME=(from DATABASE_URL)
DB_PASSWORD=(from DATABASE_URL)
```

---

## 🧪 Testing Locally

### Test with MySQL

```bash
docker build -t sippel:latest .
docker run -p 8080:8080 \
  -e APP_KEY="base64:YOUR_KEY" \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=sippel \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=secret \
  sippel:latest
```

### Test with PostgreSQL

```bash
docker run -p 8080:8080 \
  -e APP_KEY="base64:YOUR_KEY" \
  -e DATABASE_URL="postgres://user:pass@host.docker.internal:5432/sippel" \
  sippel:latest
```

### Test with Custom Port

```bash
docker run -p 9000:9000 \
  -e PORT=9000 \
  -e APP_KEY="base64:YOUR_KEY" \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  sippel:latest
```

---

## ✅ Compatibility Checklist

Your codebase is now compatible because:

- ✅ **Database drivers**: Both `pdo_mysql` and `pdo_pgsql` installed
- ✅ **Migrations**: Using standard `$table->id()` (works on both)
- ✅ **Text fields**: `text()`, `mediumText()`, `longText()` (compatible)
- ✅ **No raw SQL**: All queries use Eloquent ORM
- ✅ **Dynamic PORT**: nginx listens on `${PORT}` env variable
- ✅ **DATABASE_URL parsing**: Automatic conversion to Laravel format
- ✅ **Cloud-native**: 12-factor app compliance

---

## 🚨 Important Notes

### For PostgreSQL (Heroku)

1. **Case sensitivity**: PostgreSQL is case-sensitive for table/column names
   - Your migrations use lowercase, so ✅ compatible

2. **Boolean fields**: Use `true`/`false` instead of `1`/`0`
   - Laravel handles this automatically ✅

3. **Full-text search**: Different syntax than MySQL
   - Not currently used in your app ✅

### For MySQL (Cloud Run)

1. **Connection socket**: Use Unix socket for Cloud SQL
   ```env
   DB_HOST=/cloudsql/PROJECT:REGION:INSTANCE
   ```

2. **SSL connections**: Optional but recommended
   ```env
   MYSQL_ATTR_SSL_CA=/path/to/server-ca.pem
   ```

---

## 📚 Additional Resources

- [Cloud Run Documentation](https://cloud.google.com/run/docs)
- [Cloud SQL for MySQL](https://cloud.google.com/sql/docs/mysql)
- [Heroku PHP Documentation](https://devcenter.heroku.com/categories/php-support)
- [Heroku PostgreSQL](https://devcenter.heroku.com/articles/heroku-postgresql)
- [Laravel Deployment](https://laravel.com/docs/12.x/deployment)

---

## 🆘 Troubleshooting

### Issue: Database connection fails on Cloud Run

**Solution**: Ensure Cloud SQL connection name is correct:
```bash
gcloud sql instances describe INSTANCE_NAME --format="value(connectionName)"
```

### Issue: Port binding error

**Solution**: Check if `PORT` env variable is set:
```bash
echo $PORT
```

### Issue: DATABASE_URL not parsed

**Solution**: Check entrypoint logs:
```bash
heroku logs --tail --app sippel-app
```

### Issue: Missing PHP extensions

**Solution**: Verify Dockerfile includes required extensions:
```dockerfile
docker-php-ext-install -j$(nproc) bcmath gd intl opcache pdo_mysql pdo_pgsql zip
```
