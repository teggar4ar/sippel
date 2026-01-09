# SIPPEL - Google Cloud Platform Deployment Guide

> **Stack:** Cloud Run + Cloud SQL + Cloud Storage + FrankenPHP + Terraform  
> **Last Updated:** January 2026

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [GCP Project Setup](#2-gcp-project-setup)
3. [Terraform Infrastructure](#3-terraform-infrastructure)
4. [Application Preparation](#4-application-preparation)
5. [Build & Deploy](#5-build--deploy)
6. [Post-Deployment Tasks](#6-post-deployment-tasks)
7. [Custom Domain Setup](#7-custom-domain-setup)
8. [CI/CD with Cloud Build](#8-cicd-with-cloud-build)
9. [Monitoring & Maintenance](#9-monitoring--maintenance)
10. [Cost Optimization](#10-cost-optimization)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. Prerequisites

### Local Development Machine

- [x] Google Cloud SDK (`gcloud`) installed and configured
- [x] Terraform installed (v1.5+)
- [ ] Docker installed and running
- [ ] Git installed
- [ ] Node.js 18+ (for building assets)
- [ ] Composer 2.x

### Verify Installation

```bash
# Verify gcloud
gcloud --version
gcloud auth list

# Verify Terraform
terraform --version
```

### GCP Requirements

- [ ] GCP Account with billing enabled
- [ ] Project Owner or Editor role
- [ ] APIs to enable (covered in setup)

### Application Requirements

- [ ] SIPPEL codebase ready
- [ ] All tests passing locally
- [ ] Assets built (`npm run build`)

---

## 2. GCP Project Setup

### 2.1 Create New Project

```bash
# Set your project ID (must be globally unique)
export PROJECT_ID="sippel-production"
export REGION="asia-southeast2"  # Jakarta

# Create project
gcloud projects create $PROJECT_ID --name="SIPPEL Production"

# Set as active project
gcloud config set project $PROJECT_ID

# Link billing account (get ID from console or list)
gcloud billing accounts list
gcloud billing projects link $PROJECT_ID --billing-account=BILLING_ACCOUNT_ID
```

### 2.2 Enable Required APIs

```bash
gcloud services enable \
    run.googleapis.com \
    sqladmin.googleapis.com \
    storage.googleapis.com \
    secretmanager.googleapis.com \
    cloudbuild.googleapis.com \
    artifactregistry.googleapis.com \
    vpcaccess.googleapis.com \
    compute.googleapis.com
```

### 2.3 Authenticate Terraform

```bash
# Login for Terraform to use
gcloud auth application-default login

# Verify authentication
gcloud auth application-default print-access-token
```

---

## 3. Terraform Infrastructure

All infrastructure is managed via Terraform in the `terraform/` directory.

### 3.1 Directory Structure

```
terraform/
├── main.tf              # Main configuration & providers
├── variables.tf         # Input variables
├── outputs.tf           # Output values
├── terraform.tfvars     # Variable values (git-ignored)
├── cloud-sql.tf         # Cloud SQL instance & database
├── cloud-storage.tf     # Storage bucket
├── cloud-run.tf         # Cloud Run service & jobs
├── networking.tf        # VPC connector
├── secrets.tf           # Secret Manager
└── iam.tf               # Service accounts & permissions
```

### 3.2 Create Terraform Files

#### `terraform/main.tf`

```hcl
terraform {
  required_version = ">= 1.5.0"

  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 5.0"
    }
    google-beta = {
      source  = "hashicorp/google-beta"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.5"
    }
  }

  # Optional: Configure remote state in GCS
  # backend "gcs" {
  #   bucket = "sippel-terraform-state"
  #   prefix = "terraform/state"
  # }
}

provider "google" {
  project = var.project_id
  region  = var.region
}

provider "google-beta" {
  project = var.project_id
  region  = var.region
}

# Enable required APIs
resource "google_project_service" "apis" {
  for_each = toset([
    "run.googleapis.com",
    "sqladmin.googleapis.com",
    "storage.googleapis.com",
    "secretmanager.googleapis.com",
    "cloudbuild.googleapis.com",
    "artifactregistry.googleapis.com",
    "vpcaccess.googleapis.com",
    "compute.googleapis.com",
  ])

  service            = each.value
  disable_on_destroy = false
}
```

#### `terraform/variables.tf`

```hcl
variable "project_id" {
  description = "GCP Project ID"
  type        = string
}

variable "region" {
  description = "GCP Region"
  type        = string
  default     = "asia-southeast2" # Jakarta
}

variable "app_name" {
  description = "Application name"
  type        = string
  default     = "sippel"
}

variable "environment" {
  description = "Environment (dev, staging, production)"
  type        = string
  default     = "production"
}

variable "db_tier" {
  description = "Cloud SQL instance tier"
  type        = string
  default     = "db-f1-micro" # Use db-g1-small for production
}

variable "db_password" {
  description = "Database password for application user"
  type        = string
  sensitive   = true
}

variable "app_key" {
  description = "Laravel APP_KEY (base64:...)"
  type        = string
  sensitive   = true
}

variable "app_url" {
  description = "Application URL"
  type        = string
  default     = ""
}

variable "min_instances" {
  description = "Minimum Cloud Run instances"
  type        = number
  default     = 0
}

variable "max_instances" {
  description = "Maximum Cloud Run instances"
  type        = number
  default     = 10
}
```

#### `terraform/outputs.tf`

```hcl
output "cloud_run_url" {
  description = "Cloud Run service URL"
  value       = google_cloud_run_v2_service.app.uri
}

output "cloud_sql_connection_name" {
  description = "Cloud SQL connection name"
  value       = google_sql_database_instance.main.connection_name
}

output "storage_bucket" {
  description = "Cloud Storage bucket name"
  value       = google_storage_bucket.app.name
}

output "artifact_registry_url" {
  description = "Artifact Registry repository URL"
  value       = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.app.repository_id}"
}

output "db_instance_ip" {
  description = "Cloud SQL private IP"
  value       = google_sql_database_instance.main.private_ip_address
  sensitive   = true
}
```

#### `terraform/cloud-sql.tf`

```hcl
# Cloud SQL Instance
resource "google_sql_database_instance" "main" {
  name             = "${var.app_name}-db"
  database_version = "MYSQL_8_0"
  region           = var.region

  settings {
    tier              = var.db_tier
    availability_type = var.environment == "production" ? "REGIONAL" : "ZONAL"
    disk_type         = "PD_SSD"
    disk_size         = 10
    disk_autoresize   = true

    backup_configuration {
      enabled            = true
      binary_log_enabled = var.environment == "production"
      start_time         = "03:00"
      backup_retention_settings {
        retained_backups = 7
      }
    }

    ip_configuration {
      ipv4_enabled    = false
      private_network = google_compute_network.main.id
    }

    database_flags {
      name  = "character_set_server"
      value = "utf8mb4"
    }
  }

  deletion_protection = var.environment == "production"

  depends_on = [
    google_project_service.apis,
    google_service_networking_connection.private_vpc_connection
  ]
}

# Database
resource "google_sql_database" "app" {
  name      = var.app_name
  instance  = google_sql_database_instance.main.name
  charset   = "utf8mb4"
  collation = "utf8mb4_unicode_ci"
}

# Database User
resource "google_sql_user" "app" {
  name     = "${var.app_name}_user"
  instance = google_sql_database_instance.main.name
  password = var.db_password
}
```

#### `terraform/networking.tf`

```hcl
# VPC Network
resource "google_compute_network" "main" {
  name                    = "${var.app_name}-network"
  auto_create_subnetworks = false

  depends_on = [google_project_service.apis]
}

# Subnet
resource "google_compute_subnetwork" "main" {
  name          = "${var.app_name}-subnet"
  ip_cidr_range = "10.0.0.0/24"
  region        = var.region
  network       = google_compute_network.main.id
}

# Private IP range for Cloud SQL
resource "google_compute_global_address" "private_ip_range" {
  name          = "${var.app_name}-private-ip"
  purpose       = "VPC_PEERING"
  address_type  = "INTERNAL"
  prefix_length = 16
  network       = google_compute_network.main.id
}

# Private VPC connection for Cloud SQL
resource "google_service_networking_connection" "private_vpc_connection" {
  network                 = google_compute_network.main.id
  service                 = "servicenetworking.googleapis.com"
  reserved_peering_ranges = [google_compute_global_address.private_ip_range.name]
}

# VPC Access Connector for Cloud Run
resource "google_vpc_access_connector" "main" {
  name          = "${var.app_name}-connector"
  region        = var.region
  ip_cidr_range = "10.8.0.0/28"
  network       = google_compute_network.main.name

  depends_on = [google_project_service.apis]
}
```

#### `terraform/cloud-storage.tf`

```hcl
# Storage Bucket for uploads and reports
resource "google_storage_bucket" "app" {
  name     = "${var.project_id}-${var.app_name}-storage"
  location = var.region

  uniform_bucket_level_access = true
  public_access_prevention    = "enforced"

  versioning {
    enabled = var.environment == "production"
  }

  lifecycle_rule {
    condition {
      age = 365
    }
    action {
      type = "Delete"
    }
  }

  depends_on = [google_project_service.apis]
}
```

#### `terraform/secrets.tf`

```hcl
# APP_KEY Secret
resource "google_secret_manager_secret" "app_key" {
  secret_id = "${var.app_name}-app-key"

  replication {
    auto {}
  }

  depends_on = [google_project_service.apis]
}

resource "google_secret_manager_secret_version" "app_key" {
  secret      = google_secret_manager_secret.app_key.id
  secret_data = var.app_key
}

# DB Password Secret
resource "google_secret_manager_secret" "db_password" {
  secret_id = "${var.app_name}-db-password"

  replication {
    auto {}
  }

  depends_on = [google_project_service.apis]
}

resource "google_secret_manager_secret_version" "db_password" {
  secret      = google_secret_manager_secret.db_password.id
  secret_data = var.db_password
}
```

#### `terraform/iam.tf`

```hcl
# Cloud Run Service Account
resource "google_service_account" "cloud_run" {
  account_id   = "${var.app_name}-run-sa"
  display_name = "SIPPEL Cloud Run Service Account"
}

# Grant Cloud Run access to Cloud SQL
resource "google_project_iam_member" "cloud_run_sql" {
  project = var.project_id
  role    = "roles/cloudsql.client"
  member  = "serviceAccount:${google_service_account.cloud_run.email}"
}

# Grant Cloud Run access to Storage
resource "google_storage_bucket_iam_member" "cloud_run_storage" {
  bucket = google_storage_bucket.app.name
  role   = "roles/storage.objectAdmin"
  member = "serviceAccount:${google_service_account.cloud_run.email}"
}

# Grant Cloud Run access to Secrets
resource "google_secret_manager_secret_iam_member" "app_key_access" {
  secret_id = google_secret_manager_secret.app_key.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${google_service_account.cloud_run.email}"
}

resource "google_secret_manager_secret_iam_member" "db_password_access" {
  secret_id = google_secret_manager_secret.db_password.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${google_service_account.cloud_run.email}"
}

# Cloud Build Service Account permissions
data "google_project" "current" {}

resource "google_project_iam_member" "cloudbuild_run_admin" {
  project = var.project_id
  role    = "roles/run.admin"
  member  = "serviceAccount:${data.google_project.current.number}@cloudbuild.gserviceaccount.com"
}

resource "google_project_iam_member" "cloudbuild_sa_user" {
  project = var.project_id
  role    = "roles/iam.serviceAccountUser"
  member  = "serviceAccount:${data.google_project.current.number}@cloudbuild.gserviceaccount.com"
}
```

#### `terraform/cloud-run.tf`

```hcl
# Artifact Registry Repository
resource "google_artifact_registry_repository" "app" {
  location      = var.region
  repository_id = "${var.app_name}-repo"
  format        = "DOCKER"
  description   = "SIPPEL Docker images"

  depends_on = [google_project_service.apis]
}

# Cloud Run Service
resource "google_cloud_run_v2_service" "app" {
  name     = var.app_name
  location = var.region
  ingress  = "INGRESS_TRAFFIC_ALL"

  template {
    service_account = google_service_account.cloud_run.email

    scaling {
      min_instance_count = var.min_instances
      max_instance_count = var.max_instances
    }

    vpc_access {
      connector = google_vpc_access_connector.main.id
      egress    = "PRIVATE_RANGES_ONLY"
    }

    containers {
      image = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.app.repository_id}/${var.app_name}:latest"

      ports {
        container_port = 8080
      }

      resources {
        limits = {
          cpu    = "1"
          memory = "512Mi"
        }
        cpu_idle = true
        startup_cpu_boost = true
      }

      env {
        name  = "APP_NAME"
        value = "SIPPEL"
      }
      env {
        name  = "APP_ENV"
        value = var.environment
      }
      env {
        name  = "APP_DEBUG"
        value = "false"
      }
      env {
        name  = "APP_URL"
        value = var.app_url != "" ? var.app_url : "https://${var.app_name}-${random_id.url_suffix.hex}-${var.region}.a.run.app"
      }
      env {
        name  = "APP_LOCALE"
        value = "id"
      }
      env {
        name  = "APP_TIMEZONE"
        value = "Asia/Jakarta"
      }
      env {
        name  = "DB_CONNECTION"
        value = "mysql"
      }
      env {
        name  = "DB_HOST"
        value = "/cloudsql/${google_sql_database_instance.main.connection_name}"
      }
      env {
        name  = "DB_PORT"
        value = "3306"
      }
      env {
        name  = "DB_DATABASE"
        value = google_sql_database.app.name
      }
      env {
        name  = "DB_USERNAME"
        value = google_sql_user.app.name
      }
      env {
        name = "DB_PASSWORD"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.db_password.secret_id
            version = "latest"
          }
        }
      }
      env {
        name = "APP_KEY"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.app_key.secret_id
            version = "latest"
          }
        }
      }
      env {
        name  = "SESSION_DRIVER"
        value = "database"
      }
      env {
        name  = "CACHE_STORE"
        value = "database"
      }
      env {
        name  = "QUEUE_CONNECTION"
        value = "sync"
      }
      env {
        name  = "FILESYSTEM_DISK"
        value = "gcs"
      }
      env {
        name  = "GOOGLE_CLOUD_PROJECT"
        value = var.project_id
      }
      env {
        name  = "GOOGLE_CLOUD_STORAGE_BUCKET"
        value = google_storage_bucket.app.name
      }

      volume_mounts {
        name       = "cloudsql"
        mount_path = "/cloudsql"
      }
    }

    volumes {
      name = "cloudsql"
      cloud_sql_instance {
        instances = [google_sql_database_instance.main.connection_name]
      }
    }
  }

  depends_on = [
    google_project_service.apis,
    google_secret_manager_secret_version.app_key,
    google_secret_manager_secret_version.db_password,
  ]

  lifecycle {
    ignore_changes = [
      template[0].containers[0].image,
      client,
      client_version,
    ]
  }
}

# Random suffix for URL
resource "random_id" "url_suffix" {
  byte_length = 4
}

# Allow unauthenticated access
resource "google_cloud_run_v2_service_iam_member" "public" {
  location = google_cloud_run_v2_service.app.location
  name     = google_cloud_run_v2_service.app.name
  role     = "roles/run.invoker"
  member   = "allUsers"
}

# Cloud Run Job for Migrations
resource "google_cloud_run_v2_job" "migrate" {
  name     = "${var.app_name}-migrate"
  location = var.region

  template {
    template {
      service_account = google_service_account.cloud_run.email

      vpc_access {
        connector = google_vpc_access_connector.main.id
        egress    = "PRIVATE_RANGES_ONLY"
      }

      containers {
        image   = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.app.repository_id}/${var.app_name}:latest"
        command = ["php"]
        args    = ["artisan", "migrate", "--force"]

        env {
          name  = "APP_ENV"
          value = var.environment
        }
        env {
          name  = "DB_CONNECTION"
          value = "mysql"
        }
        env {
          name  = "DB_HOST"
          value = "/cloudsql/${google_sql_database_instance.main.connection_name}"
        }
        env {
          name  = "DB_DATABASE"
          value = google_sql_database.app.name
        }
        env {
          name  = "DB_USERNAME"
          value = google_sql_user.app.name
        }
        env {
          name = "DB_PASSWORD"
          value_source {
            secret_key_ref {
              secret  = google_secret_manager_secret.db_password.secret_id
              version = "latest"
            }
          }
        }
        env {
          name = "APP_KEY"
          value_source {
            secret_key_ref {
              secret  = google_secret_manager_secret.app_key.secret_id
              version = "latest"
            }
          }
        }

        volume_mounts {
          name       = "cloudsql"
          mount_path = "/cloudsql"
        }
      }

      volumes {
        name = "cloudsql"
        cloud_sql_instance {
          instances = [google_sql_database_instance.main.connection_name]
        }
      }
    }
  }

  lifecycle {
    ignore_changes = [
      template[0].template[0].containers[0].image,
    ]
  }
}

# Cloud Run Job for Seeding
resource "google_cloud_run_v2_job" "seed" {
  name     = "${var.app_name}-seed"
  location = var.region

  template {
    template {
      service_account = google_service_account.cloud_run.email

      vpc_access {
        connector = google_vpc_access_connector.main.id
        egress    = "PRIVATE_RANGES_ONLY"
      }

      containers {
        image   = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.app.repository_id}/${var.app_name}:latest"
        command = ["php"]
        args    = ["artisan", "db:seed", "--force"]

        env {
          name  = "APP_ENV"
          value = var.environment
        }
        env {
          name  = "DB_CONNECTION"
          value = "mysql"
        }
        env {
          name  = "DB_HOST"
          value = "/cloudsql/${google_sql_database_instance.main.connection_name}"
        }
        env {
          name  = "DB_DATABASE"
          value = google_sql_database.app.name
        }
        env {
          name  = "DB_USERNAME"
          value = google_sql_user.app.name
        }
        env {
          name = "DB_PASSWORD"
          value_source {
            secret_key_ref {
              secret  = google_secret_manager_secret.db_password.secret_id
              version = "latest"
            }
          }
        }
        env {
          name = "APP_KEY"
          value_source {
            secret_key_ref {
              secret  = google_secret_manager_secret.app_key.secret_id
              version = "latest"
            }
          }
        }

        volume_mounts {
          name       = "cloudsql"
          mount_path = "/cloudsql"
        }
      }

      volumes {
        name = "cloudsql"
        cloud_sql_instance {
          instances = [google_sql_database_instance.main.connection_name]
        }
      }
    }
  }

  lifecycle {
    ignore_changes = [
      template[0].template[0].containers[0].image,
    ]
  }
}
```

#### `terraform/terraform.tfvars.example`

```hcl
# Copy this file to terraform.tfvars and fill in the values
# DO NOT commit terraform.tfvars to version control!

project_id  = "your-project-id"
region      = "asia-southeast2"
app_name    = "sippel"
environment = "production"

# Database
db_tier     = "db-f1-micro"  # Use db-g1-small for production
db_password = "GENERATE_STRONG_PASSWORD"

# Laravel
app_key = "base64:GENERATE_WITH_PHP_ARTISAN_KEY_GENERATE"
app_url = ""  # Leave empty to use Cloud Run generated URL

# Scaling
min_instances = 0   # Set to 1 for production
max_instances = 10
```

### 3.3 Initialize and Apply Terraform

```bash
# Navigate to terraform directory
cd terraform

# Create tfvars file
cp terraform.tfvars.example terraform.tfvars

# Generate Laravel APP_KEY
php artisan key:generate --show
# Copy the output and paste into terraform.tfvars

# Generate a strong database password
openssl rand -base64 32
# Copy and paste into terraform.tfvars

# Edit terraform.tfvars with your values
nano terraform.tfvars  # or use your preferred editor

# Initialize Terraform
terraform init

# Preview changes
terraform plan

# Apply infrastructure
terraform apply

# Save outputs for later use
terraform output -json > ../terraform-outputs.json
```

### 3.4 Configure Docker Authentication

After Terraform creates the Artifact Registry:

```bash
# Get the registry URL from Terraform output
export REGISTRY_URL=$(terraform output -raw artifact_registry_url)

# Configure Docker authentication
gcloud auth configure-docker ${REGION}-docker.pkg.dev
```

---

## 4. Application Preparation

### 4.1 Create Dockerfile (FrankenPHP)

Create `Dockerfile` in project root:

```dockerfile
# ============================================
# Stage 1: Build assets
# ============================================
FROM node:20-alpine AS assets-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY tailwind.config.js* ./
COPY postcss.config.js* ./

RUN npm run build

# ============================================
# Stage 2: Install PHP dependencies
# ============================================
FROM composer:2 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# ============================================
# Stage 3: Production image with FrankenPHP
# ============================================
FROM dunglas/frankenphp:1-php8.3-alpine AS production

# Install PHP extensions
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    gd \
    intl \
    zip \
    opcache \
    pcntl

# Install additional tools
RUN apk add --no-cache \
    su-exec \
    libpng \
    libjpeg-turbo \
    freetype \
    fontconfig \
    ttf-dejavu

# Set working directory
WORKDIR /app

# Copy application from builders
COPY --from=composer-builder /app/vendor ./vendor
COPY --from=assets-builder /app/public/build ./public/build
COPY . .

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache

# Create Caddyfile for FrankenPHP
RUN echo '{\n\
    frankenphp\n\
    order php_server before file_server\n\
}\n\
\n\
:8080 {\n\
    root * /app/public\n\
    encode zstd gzip\n\
    php_server\n\
    file_server\n\
}' > /etc/caddy/Caddyfile

# Expose port (Cloud Run expects 8080)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

# Start FrankenPHP
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
```

### 4.2 Create .dockerignore

Create `.dockerignore` in project root:

```
.git
.github
.gitignore
.env
.env.*
!.env.example
node_modules
tests
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
storage/debugbar
vendor
docker-compose*.yml
Dockerfile*
*.md
phpunit.xml
phpstan.neon
pint.json
rector.php
.editorconfig
.DS_Store
Thumbs.db
```

### 4.3 Create Health Check Route

Add to `routes/web.php`:

```php
// Health check for Cloud Run
Route::get('/up', fn () => response('OK', 200));
```

### 4.4 Configure Laravel for Cloud Run

Create `config/google.php`:

```php
<?php

return [
    'project_id' => env('GOOGLE_CLOUD_PROJECT'),
    'storage' => [
        'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
    ],
];
```

Update `config/filesystems.php` - add GCS disk:

```php
'disks' => [
    // ... existing disks ...
    
    'gcs' => [
        'driver' => 'gcs',
        'project_id' => env('GOOGLE_CLOUD_PROJECT'),
        'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
        'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', ''),
        'visibility' => 'private',
    ],
],
```

### 4.5 Install Google Cloud Storage Package

```bash
composer require spatie/laravel-google-cloud-storage
```

### 4.6 Update Session Configuration

In `.env.production` (or Cloud Run env vars):

```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Ensure session table migration exists:

```bash
php artisan session:table
php artisan migrate
```

---

## 5. Build & Deploy

Since Terraform already created the Cloud Run service, you only need to build and push the Docker image.

### 5.1 Set Environment Variables

```bash
# Load values from Terraform outputs
cd terraform
export PROJECT_ID=$(terraform output -raw project_id 2>/dev/null || echo "your-project-id")
export REGION="asia-southeast2"
export REGISTRY_URL=$(terraform output -raw artifact_registry_url)
cd ..
```

### 5.2 Build Docker Image

```bash
# Build image
docker build -t ${REGISTRY_URL}/sippel:latest .

# Test locally (optional)
docker run -p 8080:8080 \
    -e APP_KEY=base64:test \
    -e APP_ENV=local \
    ${REGISTRY_URL}/sippel:latest
```

### 5.3 Push Image to Artifact Registry

```bash
docker push ${REGISTRY_URL}/sippel:latest
```

### 5.4 Update Cloud Run Service

After the first push, Cloud Run will automatically use the new image. For subsequent deployments:

```bash
# Force Cloud Run to pull the latest image
gcloud run services update sippel \
    --region=$REGION \
    --image=${REGISTRY_URL}/sippel:latest
```

### 5.5 Get Service URL

```bash
# From Terraform
cd terraform && terraform output cloud_run_url && cd ..

# Or via gcloud
gcloud run services describe sippel --region=$REGION --format="value(status.url)"
```

---

## 6. Post-Deployment Tasks

### 6.1 Run Database Migrations

Terraform already created the Cloud Run Jobs. Simply execute them:

```bash
# Run migrations
gcloud run jobs execute sippel-migrate --region=$REGION --wait

# Check job status
gcloud run jobs executions list --job=sippel-migrate --region=$REGION
```

### 6.2 Seed Initial Data

```bash
# Run seeders
gcloud run jobs execute sippel-seed --region=$REGION --wait
```

### 6.3 Alternative: Run via Cloud SQL Proxy (Local)

For debugging or manual database operations:

```bash
# Download Cloud SQL Proxy (Windows)
# Download from: https://cloud.google.com/sql/docs/mysql/sql-proxy#install

# For Linux/macOS:
curl -o cloud-sql-proxy https://storage.googleapis.com/cloud-sql-connectors/cloud-sql-proxy/v2.8.0/cloud-sql-proxy.linux.amd64
chmod +x cloud-sql-proxy

# Get connection name from Terraform
cd terraform
export SQL_CONNECTION=$(terraform output -raw cloud_sql_connection_name)
cd ..

# Start proxy
./cloud-sql-proxy $SQL_CONNECTION &

# Run migrations locally pointing to proxy
DB_HOST=127.0.0.1 DB_PORT=3306 php artisan migrate --force
```

---

## 7. Custom Domain Setup

### 7.1 Map Custom Domain

```bash
# Map domain to Cloud Run service
gcloud run domain-mappings create \
    --service=sippel \
    --domain=sippel.yourdomain.com \
    --region=$REGION
```

### 7.2 Configure DNS

Add the following DNS records at your domain registrar:

| Type | Name | Value |
|------|------|-------|
| CNAME | sippel | ghs.googlehosted.com |

Or for apex domain, add A records:
```
216.239.32.21
216.239.34.21
216.239.36.21
216.239.38.21
```

### 7.3 Update APP_URL via Terraform

Update `terraform/terraform.tfvars`:

```hcl
app_url = "https://sippel.yourdomain.com"
```

Then apply:

```bash
cd terraform
terraform apply -target=google_cloud_run_v2_service.app
cd ..
```

---

## 8. CI/CD with Cloud Build

### 8.1 Create cloudbuild.yaml

Create `cloudbuild.yaml` in project root:

```yaml
steps:
  # Build Docker image
  - name: 'gcr.io/cloud-builders/docker'
    args:
      - 'build'
      - '-t'
      - '${_REGION}-docker.pkg.dev/${PROJECT_ID}/${_REPO}/sippel:${COMMIT_SHA}'
      - '-t'
      - '${_REGION}-docker.pkg.dev/${PROJECT_ID}/${_REPO}/sippel:latest'
      - '.'

  # Push to Artifact Registry
  - name: 'gcr.io/cloud-builders/docker'
    args:
      - 'push'
      - '--all-tags'
      - '${_REGION}-docker.pkg.dev/${PROJECT_ID}/${_REPO}/sippel'

  # Deploy to Cloud Run
  - name: 'gcr.io/google.com/cloudsdktool/cloud-sdk'
    entrypoint: gcloud
    args:
      - 'run'
      - 'deploy'
      - 'sippel'
      - '--image=${_REGION}-docker.pkg.dev/${PROJECT_ID}/${_REPO}/sippel:${COMMIT_SHA}'
      - '--region=${_REGION}'
      - '--platform=managed'

  # Run migrations
  - name: 'gcr.io/google.com/cloudsdktool/cloud-sdk'
    entrypoint: gcloud
    args:
      - 'run'
      - 'jobs'
      - 'execute'
      - 'sippel-migrate'
      - '--region=${_REGION}'
      - '--wait'

substitutions:
  _REGION: asia-southeast2
  _REPO: sippel-repo

options:
  logging: CLOUD_LOGGING_ONLY

images:
  - '${_REGION}-docker.pkg.dev/${PROJECT_ID}/${_REPO}/sippel:${COMMIT_SHA}'
  - '${_REGION}-docker.pkg.dev/${PROJECT_ID}/${_REPO}/sippel:latest'
```

### 8.2 Create Build Trigger

```bash
# Connect GitHub repository first via Console, then:
gcloud builds triggers create github \
    --name="sippel-deploy" \
    --repo-name="SIPPEL" \
    --repo-owner="YOUR_GITHUB_USERNAME" \
    --branch-pattern="^main$" \
    --build-config="cloudbuild.yaml"
```

### 8.3 Grant Cloud Build Permissions

Note: Terraform already grants these permissions via `iam.tf`. This is only needed if you skipped Terraform.

```bash
# Get Cloud Build service account
export CLOUDBUILD_SA="$(gcloud projects describe ${PROJECT_ID} --format='value(projectNumber)')@cloudbuild.gserviceaccount.com"

# Grant Cloud Run Admin
gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:${CLOUDBUILD_SA}" \
    --role="roles/run.admin"

# Grant Service Account User
gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:${CLOUDBUILD_SA}" \
    --role="roles/iam.serviceAccountUser"
```

---

## 9. Monitoring & Maintenance

### 9.1 View Logs

```bash
# Stream logs
gcloud run services logs read sippel --region=$REGION --tail=100

# Or use Cloud Logging
gcloud logging read "resource.type=cloud_run_revision AND resource.labels.service_name=sippel" --limit=50
```

### 9.2 Set Up Alerts

```bash
# Create notification channel (email)
gcloud alpha monitoring channels create \
    --display-name="Admin Email" \
    --type=email \
    --channel-labels=email_address=admin@yourdomain.com

# Create alert for high error rate
gcloud alpha monitoring policies create \
    --display-name="SIPPEL High Error Rate" \
    --condition-display-name="Error rate > 5%" \
    --condition-filter='resource.type="cloud_run_revision" AND metric.type="run.googleapis.com/request_count" AND metric.labels.response_code_class="5xx"'
```

### 9.3 View Metrics

```bash
# Check service status
gcloud run services describe sippel --region=$REGION

# View revisions
gcloud run revisions list --service=sippel --region=$REGION
```

---

## 10. Cost Optimization

### 10.1 Development/Staging Environment

```bash
# Use smaller resources for non-production
gcloud run deploy sippel-staging \
    --cpu=0.5 \
    --memory=256Mi \
    --min-instances=0 \
    --max-instances=2
```

### 10.2 Production Recommendations

| Setting | Development | Production |
|---------|-------------|------------|
| `--cpu` | 0.5 | 1-2 |
| `--memory` | 256Mi | 512Mi-1Gi |
| `--min-instances` | 0 | 1 |
| `--max-instances` | 2 | 10-100 |
| Cloud SQL Tier | db-f1-micro | db-g1-small+ |

### 10.3 Enable CPU Boost (Reduces Cold Start)

```bash
gcloud run services update sippel \
    --region=$REGION \
    --cpu-boost
```

---

## 11. Troubleshooting

### Common Issues

#### Container fails to start

```bash
# Check logs
gcloud run services logs read sippel --region=$REGION --limit=100

# Common causes:
# - Missing APP_KEY
# - Database connection failed
# - Port not set to 8080
```

#### Database connection refused

```bash
# Verify Cloud SQL instance is running
gcloud sql instances describe sippel-db

# Check VPC connector
gcloud compute networks vpc-access connectors describe sippel-connector --region=$REGION

# Ensure connection string is correct
# DB_HOST=/cloudsql/PROJECT:REGION:INSTANCE (Unix socket)
```

#### Slow cold starts

```bash
# Set minimum instances
gcloud run services update sippel --min-instances=1 --region=$REGION

# Enable CPU boost
gcloud run services update sippel --cpu-boost --region=$REGION
```

#### Storage permission denied

```bash
# Verify service account has storage access
gcloud projects get-iam-policy $PROJECT_ID \
    --flatten="bindings[].members" \
    --filter="bindings.role:roles/storage"
```

### Useful Commands

```bash
# Rollback to previous revision
gcloud run services update-traffic sippel --to-revisions=REVISION_NAME=100 --region=$REGION

# View all revisions
gcloud run revisions list --service=sippel --region=$REGION

# Delete old revisions
gcloud run revisions delete REVISION_NAME --region=$REGION

# SSH into Cloud SQL (via proxy)
./cloud-sql-proxy ${PROJECT_ID}:${REGION}:sippel-db &
mysql -h 127.0.0.1 -u sippel_user -p sippel
```

---

## Quick Reference

### Terraform Commands

```bash
cd terraform

# Initialize
terraform init

# Preview changes
terraform plan

# Apply changes
terraform apply

# Destroy all resources (CAUTION!)
terraform destroy

# Show outputs
terraform output

# Refresh state
terraform refresh
```

### Environment Variables Summary

All environment variables are managed via Terraform in `cloud-run.tf`. The configuration is automatically applied.

| Variable | Value | Source |
|----------|-------|--------|
| `APP_NAME` | SIPPEL | Terraform |
| `APP_ENV` | production | Terraform |
| `APP_DEBUG` | false | Terraform |
| `APP_KEY` | base64:xxx | Secret Manager |
| `APP_URL` | https://your-domain | Terraform |
| `DB_CONNECTION` | mysql | Terraform |
| `DB_HOST` | /cloudsql/PROJECT:REGION:INSTANCE | Terraform |
| `DB_DATABASE` | sippel | Terraform |
| `DB_USERNAME` | sippel_user | Terraform |
| `DB_PASSWORD` | xxx | Secret Manager |
| `SESSION_DRIVER` | database | Terraform |
| `CACHE_STORE` | database | Terraform |
| `QUEUE_CONNECTION` | sync | Terraform |
| `FILESYSTEM_DISK` | gcs | Terraform |
| `GOOGLE_CLOUD_PROJECT` | project-id | Terraform |
| `GOOGLE_CLOUD_STORAGE_BUCKET` | bucket-name | Terraform |

### Estimated Monthly Costs (Low Traffic)

| Service | Specification | Est. Cost |
|---------|---------------|-----------|
| Cloud Run | 1 vCPU, 512Mi, ~100k requests | $5-15 |
| Cloud SQL | db-f1-micro, 10GB | $10-15 |
| Cloud Storage | 1GB | $0.02 |
| Secret Manager | 2 secrets | $0.06 |
| VPC Connector | 2 instances (min) | $0 (free tier) |
| **Total** | | **~$15-30/month** |

---

## Next Steps

1. [x] Install gcloud CLI and configure account
2. [x] Install Terraform
3. [x] Create GCP project and link billing
4. [ ] Create `terraform/` directory with all `.tf` files
5. [ ] Configure `terraform.tfvars` with your values
6. [ ] Run `terraform init` and `terraform apply`
7. [ ] Prepare application (Dockerfile, health check, GCS config)
8. [ ] Build and push Docker image
9. [ ] Run migrations: `gcloud run jobs execute sippel-migrate`
10. [ ] Run seeders: `gcloud run jobs execute sippel-seed`
11. [ ] Test application functionality
12. [ ] Configure custom domain (optional)
13. [ ] Set up CI/CD with Cloud Build (optional)

---

## Files to Create

| File | Location | Purpose |
|------|----------|---------|
| `main.tf` | `terraform/` | Provider & API configuration |
| `variables.tf` | `terraform/` | Input variables |
| `outputs.tf` | `terraform/` | Output values |
| `terraform.tfvars` | `terraform/` | Your variable values (git-ignored) |
| `cloud-sql.tf` | `terraform/` | Database resources |
| `cloud-storage.tf` | `terraform/` | Storage bucket |
| `cloud-run.tf` | `terraform/` | Cloud Run service & jobs |
| `networking.tf` | `terraform/` | VPC & connector |
| `secrets.tf` | `terraform/` | Secret Manager |
| `iam.tf` | `terraform/` | Service accounts & permissions |
| `Dockerfile` | Project root | FrankenPHP container |
| `.dockerignore` | Project root | Docker build exclusions |
| `cloudbuild.yaml` | Project root | CI/CD pipeline (optional) |

---

**Need Help?**

- [Terraform GCP Provider](https://registry.terraform.io/providers/hashicorp/google/latest/docs)
- [Cloud Run Documentation](https://cloud.google.com/run/docs)
- [Cloud SQL Documentation](https://cloud.google.com/sql/docs)
- [FrankenPHP Documentation](https://frankenphp.dev/docs/)
- [Laravel Deployment](https://laravel.com/docs/deployment)
