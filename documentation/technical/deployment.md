# MTAV Production Docker Images

This repository contains production-ready Docker configurations for the MTAV application.

## Environment Naming Conventions

MTAV uses environment-specific project names for clean separation:

- **Production**: `--project-name prod` → Containers: `prod-mysql-1`, `prod-nginx-1`, etc.
- **Testing**: `--project-name test` → Containers: `test-mysql-1`, `test-nginx-1`, etc.
- **Development**: `--project-name dev` → Containers: `dev-mysql-1`, `dev-nginx-1`, etc.

This allows multiple environments to run simultaneously without conflicts.

## Quick Deployment

```bash
# Clone this repo
git clone <mtav-docker-build-repo-url>
cd mtav-docker-build

# Deploy with your version tag
./deploy.sh --tag=v1.0.0
```

## Environment Configuration

1. Copy the environment template:

   ```bash
   cp .env.template .env
   ```

2. Edit `.env` with your production values:
   - `APP_URL` - Your domain
   - Database credentials
   - Mail configuration
   - Any other environment-specific settings

## Deployment Commands

### Build New Version

```bash
./deploy.sh --prepare --tag=v1.2.0
```

### Deploy Existing Version

```bash
./deploy.sh --tag=v1.2.0
```

### Health Check

Visit `https://yourdomain.com/health` to verify deployment.

## Version Management

- Use semantic versioning: `v1.0.0`, `v1.1.0`, `v2.0.0`
- Images are tagged and pushed to registry
- Each deployment creates a new tagged image

## Architecture

This production setup uses a **cloud-native multi-container architecture** similar to the development environment but with production-optimized images:

### 🏗️ **Production Services**

- **PHP Container** (`prod-php-1`): PHP 8.4-FPM with Laravel application
  - Production-optimized with OPcache
  - Multi-stage build with Composer dependency caching
  - Asset compilation integrated
  - Health checks via Laravel Artisan

- **Nginx Container** (`prod-nginx-1`): Nginx 1.26 web server
  - Optimized configuration for Laravel
  - Static asset serving
  - Health endpoint routing

- **MariaDB Container** (`prod-mysql-1`): MariaDB 12 database
  - Production-tuned configuration
  - Persistent data volumes
  - Automatic health monitoring

- **Redis Container** (`prod-redis-1`): Optional caching layer
  - Available via `--profile redis` flag
  - Session and cache storage
  - Password-protected

### 🚀 **Scaling Benefits**

- **Independent scaling**: Scale each service based on load
- **Service isolation**: Issues in one container don't affect others
- **Rolling updates**: Update services independently
- **Resource optimization**: Allocate resources per service needs

## Requirements

- Docker 24.0+
- Docker Compose 2.0+
- 2GB+ RAM
- 10GB+ storage
