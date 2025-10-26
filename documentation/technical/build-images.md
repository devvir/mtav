# Production Build System

This directory contains production Docker build configurations and orchestration files for the MTAV application.

## Overview

The production build system creates optimized Docker images for deployment using a multi-stage approach that:

- Compiles frontend assets with Vite
- Installs production-only PHP dependencies
- Creates minimal, secure container images
- Pushes to GitHub Container Registry for deployment

## Build Process

### App Container (Multi-stage Build)

1. **Frontend Compilation Stage**:
   - Uses Node.js 22 to install npm dependencies
   - Compiles frontend assets with Vite for production
   - Optimizes assets for minimal size and maximum performance

2. **Backend Production Stage**:
   - Uses PHP 8.4-FPM as base image
   - Installs only production Composer dependencies (`--no-dev --optimize-autoloader`)
   - Copies compiled frontend assets from stage 1
   - Enables OPcache for PHP performance
   - Sets proper file permissions and security

3. **Final Image**:
   - Single optimized container with PHP-FPM + compiled frontend
   - Health checks included
   - Proper user permissions (non-root)
   - Production-tuned PHP configuration

### Version Management

Every build requires a version tag following semantic versioning:

```bash
./mtav build php 1.2.0      # Creates tag:
                             # - ghcr.io/devvir/mtav-php:1.2.0

./mtav build assets 1.2.0   # Creates tag:
                             # - ghcr.io/devvir/mtav-assets:1.2.0

./mtav build nginx 1.2.0    # Creates tag:
                             # - ghcr.io/devvir/mtav-nginx:1.2.0

./mtav build mysql 1.2.0    # Creates tag:
                             # - ghcr.io/devvir/mtav-mysql:1.2.0
```

### Production Architecture

- **PHP Service**: PHP-FPM container handling dynamic content only
- **Assets Service**: Nginx container serving static files (CSS, JS, images)
- **Nginx Service**: Pure reverse proxy routing traffic to php and assets services
- **MySQL Service**: Database container
- **Nginx Service**: Production-optimized web server with custom configuration
- **MySQL Service**: Production-tuned database with optimized settings

## Registry Integration

### GitHub Container Registry (ghcr.io)

All production images are pushed to GitHub Container Registry:

- **Free**: Unlimited storage and bandwidth for public repositories
- **Integrated**: Native GitHub integration for access management
- **Reliable**: Enterprise-grade infrastructure
- **Naming**: `ghcr.io/devvir/mtav-{service}:{version}`

### Authentication

```bash
# Login to GitHub Container Registry
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin
```

## File Structure

```
.docker/build/
├── README.md             # This file
├── compose.yml      # Production orchestration (registry images only)
├── app/
│   ├── Dockerfile        # Multi-stage app build
│   └── php.ini           # Production PHP configuration
├── mysql/
│   ├── Dockerfile        # MySQL production image
│   └── my.cnf            # Symlink to ../../mysql/my.cnf
└── nginx/
    ├── Dockerfile        # Nginx production image
    └── nginx.conf        # Production nginx configuration
```

## Configuration Differences

### Development vs Production

- **Development**: Live code mounting, separate containers for debugging
- **Production**: Compiled assets, optimized containers, minimal attack surface

### Nginx Configuration

Production nginx config differs from development:

- Optimized for performance over development convenience
- Security headers included
- Gzip compression enabled
- Static asset caching configured

### PHP Configuration

Production PHP settings:

- OPcache enabled for performance
- Error reporting disabled
- Memory limits optimized
- Security-focused directives

### Service Deployment

```bash
# MySQL database (start first)
docker run -d \
  --name prod-mysql-1 \
  --restart unless-stopped \
  -v mtav_mysql:/var/lib/mysql \
  -e MYSQL_ROOT_PASSWORD=rootsecret \
  -e MYSQL_DATABASE=mtav \
  -e MYSQL_USER=mtav \
  -e MYSQL_PASSWORD=secret \
  --network prod \
  ghcr.io/devvir/mtav-mysql:1.0.0

# PHP service (requires storage volume)
docker run -d \
  --name prod-php-1 \
  --restart unless-stopped \
  -v mtav_storage:/var/www/storage \
  --network prod \
  ghcr.io/devvir/mtav-php:1.2.0

# Assets service (static files)
docker run -d \
  --name prod-assets-1 \
  --restart unless-stopped \
  --network prod \
  ghcr.io/devvir/mtav-assets:1.1.0

# Nginx reverse proxy (start last)
docker run -d \
  --name mtav-nginx \
  --restart unless-stopped \
  -p 8080:80 -p 8443:443 \
  --network prod \
  ghcr.io/devvir/mtav-nginx:1.0.1
```

**Critical**: Never run the PHP service without the storage volume - user uploads and application data will be lost on container restart.

## Build Script Integration

Production builds are integrated with the main MTAV command:

```bash
./mtav build <service> <version>
```

This executes `.docker/scripts/build.sh` with:

- Docker build execution with proper context
- Automatic tagging with specific version
- Registry push optional (will ask for confirmation)

## Deployment Repository

Actual deployment is handled by the separate [mtav-deploy](https://github.com/devvir/mtav-deploy) repository, which:

- Contains deployment scripts for production servers
- References the built images from the registry
- Provides simple deployment commands for operations teams
- Requires no access to the main codebase
- Handles environment-specific configuration

For more information on deployment instructions, see the mtav-deploy repo's main [README.md](../deploy/README.md).
