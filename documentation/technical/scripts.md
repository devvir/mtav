# Docker Scripts Documentation

This directory contains individual scripts for specific development tasks. Each script handles Docker Compose execution for a particular service or function.

## 📋 Available Scripts

### artisan.sh - Laravel Artisan Commands

Run Laravel Artisan commands inside the PHP container.

```bash
# Database operations
.docker/scripts/artisan.sh migrate
.docker/scripts/artisan.sh migrate:fresh --seed
.docker/scripts/artisan.sh migrate:rollback

# Code generation
.docker/scripts/artisan.sh make:controller UserController
.docker/scripts/artisan.sh make:model Post -m
.docker/scripts/artisan.sh make:migration create_posts_table

# Maintenance
.docker/scripts/artisan.sh cache:clear
.docker/scripts/artisan.sh config:clear
.docker/scripts/artisan.sh route:list

# Interactive
.docker/scripts/artisan.sh tinker
.docker/scripts/artisan.sh queue:work
```

### composer.sh - PHP Package Manager

Manage PHP dependencies via Composer.

```bash
# Install dependencies
.docker/scripts/composer.sh install
.docker/scripts/composer.sh install --no-dev  # Production install

# Add packages
.docker/scripts/composer.sh require laravel/sanctum
.docker/scripts/composer.sh require --dev phpunit/phpunit

# Update packages
.docker/scripts/composer.sh update
.docker/scripts/composer.sh update laravel/framework

# Other operations
.docker/scripts/composer.sh dump-autoload
.docker/scripts/composer.sh show                # List installed packages
.docker/scripts/composer.sh outdated           # Check for updates
```

### npm.sh - Node.js Package Manager

Manage JavaScript dependencies and run build tasks.

```bash
# Install dependencies
.docker/scripts/npm.sh install
.docker/scripts/npm.sh ci                      # Clean install from lockfile

# Add packages
.docker/scripts/npm.sh add vue@latest
.docker/scripts/npm.sh add --save-dev @types/node

# Development tasks
.docker/scripts/npm.sh run dev                 # Start Vite dev server
.docker/scripts/npm.sh run build               # Build for production
.docker/scripts/npm.sh run test                # Run tests

# Package management
.docker/scripts/npm.sh update
.docker/scripts/npm.sh list                    # List installed packages
.docker/scripts/npm.sh outdated               # Check for updates
```

### migrate.sh - Quick Database Migration

Shortcut for the most common migration command.

```bash
# Run pending migrations
.docker/scripts/migrate.sh

# With additional flags
.docker/scripts/migrate.sh --force
.docker/scripts/migrate.sh --seed
```

### db.sh - Database Access

Connect to the MySQL database directly.

```bash
# Connect to database (will prompt for password: secret)
.docker/scripts/db.sh

# Once connected, you can run SQL commands:
# mysql> SHOW TABLES;
# mysql> SELECT * FROM users LIMIT 5;
# mysql> EXIT;
```

### shell.sh - Container Shell Access

Open a shell inside any container for debugging or manual operations.

```bash
# Default: PHP container
.docker/scripts/shell.sh
.docker/scripts/shell.sh php

# Other containers
.docker/scripts/shell.sh nginx
.docker/scripts/shell.sh mysql
.docker/scripts/shell.sh assets

# Once inside, you can run commands directly:
# php -v
# ls -la /var/www
# cd /var/www && php artisan --version
```

### compose.sh - Docker Compose Wrapper & Utilities

Dual-purpose script that can be used in two ways:

**1. Direct Docker Compose commands:**

```bash
# View container status
.docker/scripts/compose.sh ps

# View logs
.docker/scripts/compose.sh logs
.docker/scripts/compose.sh logs -f nginx

# Manual container management
.docker/scripts/compose.sh restart php
.docker/scripts/compose.sh exec php php -v
```

**2. Sourced by other scripts for shared utilities:**

```bash
source "$(dirname "$0")/compose.sh"
docker_exec php php artisan migrate
```

## 🛠️ Common Development Workflows

### Setting up a new feature

```bash
# Create migration and model
.docker/scripts/artisan.sh make:model Product -m

# Edit the migration file, then run it
.docker/scripts/migrate.sh

# Create controller
.docker/scripts/artisan.sh make:controller ProductController
```

### Adding a new package

```bash
# PHP package
.docker/scripts/composer.sh require vendor/package

# JavaScript package
.docker/scripts/npm.sh add package-name

# Update autoloader if needed
.docker/scripts/composer.sh dump-autoload
```

### Debugging issues

```bash
# Check logs
.docker/scripts/compose.sh logs php
.docker/scripts/compose.sh logs nginx

# Access container for investigation
.docker/scripts/shell.sh php

# Check Laravel logs inside container
.docker/scripts/shell.sh php
# tail -f storage/logs/laravel.log

# Database debugging
.docker/scripts/db.sh
# SHOW PROCESSLIST;
```

### Database operations

```bash
# Fresh database
.docker/scripts/artisan.sh migrate:fresh --seed

# Backup database (from host)
docker exec $(docker compose -f .docker/compose.yml ps -q mysql) \
  mysqldump -u mtav -psecret mtav > backup.sql

# Restore database (from host)
docker exec -i $(docker compose -f .docker/compose.yml ps -q mysql) \
  mysql -u mtav -psecret mtav < backup.sql
```

## 💡 Tips

- All scripts automatically use the correct Docker Compose configuration
- Scripts can be run from anywhere in the project (they auto-detect paths)
- For repetitive tasks, consider creating aliases in your shell
- Use `.docker/scripts/compose.sh` for direct Docker Compose access when needed
- Container names: `dev-php-1`, `dev-nginx-1`, `dev-mysql-1`, `dev-assets-1`, `dev-mailhog-1`

## 🔧 Script Internals

Each script:

1. Sources `compose.sh` for shared utilities
2. Uses `docker_compose` and `docker_exec` functions for consistent behavior
3. Automatically handles TTY detection for Git hooks and CI environments
4. Provides helpful usage information when called without arguments

## 🔧 Common Utilities (`compose.sh`)

All scripts use a shared base script that provides:

- **Dual Purpose**: Can be executed directly for Docker Compose commands or sourced for utilities
- **TTY Detection**: Automatically uses `-T` flag in non-interactive environments
- **Standard Docker Compose**: `docker_compose` function with consistent configuration
- **Smart Exec**: `docker_exec` function that handles TTY and container selection
