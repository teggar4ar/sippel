# Heroku Deployment Guide

This guide covers how to deploy and manage the SIPPEL application on Heroku using container stack.

## Prerequisites

- Heroku CLI installed and logged in
- Git repository with `heroku` remote configured
- App name: `sippel-prod-1` (adjust as needed)

## Quick Deployy

When you've made changes and want to deploy:

```bash
# 1. Commit your changess
git add .
git commit -m "fix: your bug fix or feature"

# 2. Push to Herokuu
git push heroku update:main
```

Heroku will automatically:
1. Build a new Docker image from `Dockerfile`
2. Run release commands (migrations, caching)
3. Deploy the new container

## What Happens on Deploy

The "heroku.yml" file controls the deployment:

| Phase | Commands |
|-------|----------|
| **Build** | Docker image built from `Dockerfile` |
| **Release** | `migrate --force`, `config:cache`, `route:cache`, `view:cache`, `storage:link` |
| **Run** | Starts supervisord (nginx + php-fpm) |

## Common Commands

### Deployment

```bash
# Deploy latest changes
git push heroku update:main

# Restart the app
heroku restart -a sippel-prod-1

# Rollback to previous release
heroku rollback -a sippel-prod-1

# Rollback to specific version
heroku rollback v10 -a sippel-prod-1
```

### Logs & Monitoring

```bash
# Stream live logs
heroku logs --tail -a sippel-prod-1

# View recent logs
heroku logs -n 100 -a sippel-prod-1

# View release history
heroku releases -a sippel-prod-1
```

### Running Artisan Commands

```bash
# Run any artisan command
heroku run php artisan <command> -a sippel-prod-1

# Examples:
heroku run php artisan tinker -a sippel-prod-1
heroku run php artisan migrate:status -a sippel-prod-1
heroku run php artisan db:seed --class=UserSeeder -a sippel-prod-1
heroku run php artisan queue:work --once -a sippel-prod-1
```

### Environment Variables

```bash
# View all config vars
heroku config -a sippel-prod-1

# Set a config var
heroku config:set KEY=value -a sippel-prod-1

# Set multiple vars
heroku config:set KEY1=value1 KEY2=value2 -a sippel-prod-1

# Remove a config var
heroku config:unset KEY -a sippel-prod-1
```

### Database

```bash
# Check database info
heroku pg:info -a sippel-prod-1

# Access database console
heroku pg:psql -a sippel-prod-1

# Create a backup
heroku pg:backups:capture -a sippel-prod-1

# Download latest backup
heroku pg:backups:download -a sippel-prod-1
```

## Required Config Vars

These environment variables must be set on Heroku:

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_KEY` | Laravel app key | `base64:...` |
| `DB_CONNECTION` | Database driver | `pgsql` |
| `FORCE_HTTPS` | Force HTTPS URLs | `true` |
| `DEFAULT_USER_EMAIL` | Admin email for seeder | `admin@example.com` |
| `DEFAULT_USER_PASSWORD` | Admin password for seeder | `your-secure-password` |

The `DATABASE_URL` is automatically set by the Heroku PostgreSQL addon.

## Troubleshooting

### Release Failed

1. Check the build logs: `heroku logs --tail -a sippel-prod-1`
2. If migration failed, run manually: `heroku run php artisan migrate:status -a sippel-prod-1`
3. Rollback if needed: `heroku rollback -a sippel-prod-1`

### Mixed Content Errors

Ensure `FORCE_HTTPS=true` is set:
```bash
heroku config:set FORCE_HTTPS=true -a sippel-prod-1
```

### Database Connection Issues

1. Verify `DB_CONNECTION=pgsql` is set
2. Check if DATABASE_URL exists: `heroku config -a sippel-prod-1`
3. Verify addon is provisioned: `heroku addons -a sippel-prod-1`

### App Not Starting

1. Check logs: `heroku logs --tail -a sippel-prod-1`
2. Verify the container is running: `heroku ps -a sippel-prod-1`
3. Try restarting: `heroku restart -a sippel-prod-1`

## Scaling

```bash
# View current dynos
heroku ps -a sippel-prod-1

# Scale web dynos
heroku ps:scale web=1 -a sippel-prod-1

# Add a worker dyno (if using queues)
heroku ps:scale worker=1 -a sippel-prod-1
```

## Maintenance Mode

```bash
# Enable maintenance mode
heroku maintenance:on -a sippel-prod-1

# Disable maintenance mode
heroku maintenance:off -a sippel-prod-1
```
