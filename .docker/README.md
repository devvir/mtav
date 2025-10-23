# Docker Development Setup

This directory contains a streamlined Docker Compose setup for the MTAV application, designed for development environments.

## 🏗️ Architecture

The Docker setup follows a layered architecture:

- **Layer 1 (Docker)**: Bare-bones containers with sensible defaults
- **Layer 2 (Scripts)**: Convenience wrappers in `.docker/scripts/`
- **Layer 3 (MTAV helper)**: High-level `./mtav` commands

Depending on the complexity of the tasks you pursue, you may need to resort to a higher layer.

Most everyday tasks should be achievable with the `mtav` command, while the availble scripts should provide most debugging/book-keeping functionality.

Finally, you're free to issue docker/docker-compose commands directly if needed. Check the rest of this README for important information relevant to direct docker commands, or see [scripts/README.md](scripts/README.md) for detailed usage of the provided scripts.

## 🐳 Services

- **PHP-FPM** (`php:8.4-fpm-alpine`) - Laravel application runtime
- **Vite** (`node:22-alpine`) - Frontend development server with HMR
- **Nginx** (`nginx:1.26-alpine`) - Web server and reverse proxy
- **MariaDB** (`mariadb:12`) - Database with persistent storage
- **MailHog** (`mailhog/mailhog`) - Email testing and debugging

## ⚡ Quick Start (Bare-bones)

For testing or when you need full control without wrapper scripts:

```bash
# 1. Copy environment template
cp .docker/.env.docker .env

# 2. Start all services
docker compose -f .docker/compose.yml --env-file .env up --build -d

# 3. Check status
docker compose -f .docker/compose.yml --env-file .env ps

# 4. View logs
docker compose -f .docker/compose.yml --env-file .env logs

# 5. Stop services
docker compose -f .docker/compose.yml --env-file .env down
```

**Note**: You may use `compose.sh <command>` to avoid repeating `docker compose -f .docker/compose.yml --env-file .env` with every command.

## 🔧 Other Container Operations

### Individual Service Management

```bash
# Start specific services
docker compose -f .docker/compose.yml --env-file .env up php mysql -d

# Restart a service
docker compose -f .docker/compose.yml --env-file .env restart nginx

# View logs for specific service
docker compose -f .docker/compose.yml --env-file .env logs -f php
```

### Development Commands

```bash
# Run Laravel commands
docker compose -f .docker/compose.yml --env-file .env exec php php artisan migrate
docker compose -f .docker/compose.yml --env-file .env exec php php artisan tinker

# Install dependencies
docker compose -f .docker/compose.yml --env-file .env exec php composer install
docker compose -f .docker/compose.yml --env-file .env exec vite npm install

# Database access
docker compose -f .docker/compose.yml --env-file .env exec mysql mysql -u mtav -p mtav
```

### Troubleshooting

```bash
# Rebuild containers from scratch
docker compose -f .docker/compose.yml build --no-cache

# Clean start (removes containers)
docker compose -f .docker/compose.yml --env-file .env down
docker compose -f .docker/compose.yml --env-file .env up --build -d

# Check container health
docker compose -f .docker/compose.yml --env-file .env ps
docker compose -f .docker/compose.yml --env-file .env top
```

## 📁 File Structure

```
.docker/
├── compose.yml          # Main Docker Compose configuration
├── .env.docker          # Environment template
├── nginx/
│   └── default.conf     # Nginx configuration
├── php/
│   └── Dockerfile       # PHP-FPM container
│   └── php.ini          # PHP configuration
└── vite/
    └── Dockerfile       # Node.js/Vite container
```

## 🚀 For Easier Development

This bare-bones approach gives you full control, but for daily development consider:

- **Scripts**: See [scripts/README.md](scripts/README.md) for convenience wrappers
- **MTAV Helper**: Use `./mtav` commands for the simplest development experience

## 🔧 Technical Details

### Architecture Stack

- **PHP 8.4-FPM** for Laravel backend
- **Node.js 22** for Vite development server
- **Nginx** as web server and proxy
- **MariaDB 12** as database
- **MailHog** for email testing

### User ID Mapping

The containers automatically map your host user ID to avoid permission issues:

- `PUID` and `PGID` are set from your environment
- Files created in containers have correct ownership on host
- No need for `sudo` when working with generated files

### File Permissions

If you encounter permission issues:

```bash
# Fix storage and cache permissions
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Or rebuild containers (recommended)
./mtav fresh
```
