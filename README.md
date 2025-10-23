# MTAV Development Environment

A Laravel + Vue.js + Inertia.js app with a streamlined Docker development setup.

## 🚀 Developer Quick Start

1. **Clone the repository**

   ```bash
   git clone https://github.com/devvir/mtav
   cd mtav
   ```

2. **Start development environment**

   ```bash
   ./mtav up
   ```

   This command automatically:
   - Creates your `.env` file
   - Builds all Docker containers
   - Installs PHP dependencies (Composer)
   - Installs JS dependencies (NPM)
   - Generates Laravel app key
   - Runs database migrations + seeding
   - Starts all services

3. **Open the application**
   - http://localhost:8000

That's it! You're ready to develop.

## 📋 Daily Development Commands

```bash
# Start your development day
./mtav up

# Update dependencies and run migrations
./mtav update

# Quick break (containers preserved)
./mtav stop

# Stop everything (clean shutdown, containers stopped and removed)
./mtav down

# Check what's running
./mtav status

# Run all tests (frontend + backend)
./mtav test

# View logs
./mtav logs
./mtav logs php    # specific service

# Nuclear option: fresh rebuild
./mtav fresh

# Development shortcuts
./mtav artisan migrate
./mtav composer require package/name
./mtav npm add vue-package
./mtav shell php
```

_For detailed examples and advanced usage, see [.docker/scripts/README.md](.docker/scripts/README.md)_

## 🌐 Application URLs

- **Main Application**: http://localhost:8000
- **Vite Dev Server**: http://localhost:5173 (auto-reloads on changes)
- **Email Testing (MailHog)**: http://localhost:8025
- **Database**: localhost:3307 (user: mtav, password: secret)

### Development Workflow

- **Code changes**: Auto-reload via Vite HMR
- **PHP changes**: No restart needed, just refresh browser
- **Config changes**: May require `./mtav down && ./mtav up`
- **New dependencies**: Run `./mtav update`

## 🧪 Testing & Git Hooks

### Running Tests

```bash
# Run all tests (frontend + backend) in watch mode
./mtav test

# Run all tests once and exit
./mtav test --once

# Run individual test suites
./mtav npm test        # Frontend tests only (Vitest)
./mtav artisan test    # Backend tests only (Pest)
```

### Git Hooks & Commits

**Important**: This project uses Git hooks that automatically run both backend and frontend tests before commits and pushes.

- Pre-commit hook: runs tests and frontend linter (`eslint`)
- Pre-push hook: runs `php artisan insights` (PHP code quality)

If tests, linting or insights fail during commit/push, the operation will be blocked. To bypass hooks (not recommended):

```bash
# ⚠️ Skip hooks (use only when necessary)
git commit --no-verify -m "Emergency fix"
git push --no-verify
```

## 🐛 Troubleshooting

```bash
./mtav fresh       # Nuclear option: rebuild everything
./mtav logs        # Check all logs
./mtav logs php    # Check specific service logs
```

### Port Conflicts

If ports are already in use, customize them in your `.env` file (don't edit `compose.yml` directly):

```bash
# Port customization (avoid conflicts with other projects)
DOCKER_NGINX_PORT=9000          # Main app (default: 8000)
DOCKER_VITE_PORT=3000           # Vite dev server (default: 5173)
DOCKER_MYSQL_PORT=3308          # Database (default: 3307)
DOCKER_MAILHOG_SMTP_PORT=2025   # SMTP port (default: 1025)
DOCKER_MAILHOG_WEB_PORT=9025    # Web UI (default: 8025)
```

Then restart: `./mtav down && ./mtav up`

### File Permissions

Run `./mtav fresh` to recreate containers with correct user mapping.

You can also customize user/group mapping in `.env`:

```bash
# User/group mapping (usually auto-detected)
PUID=1001                       # Your user ID
PGID=1001                       # Your group ID
```

For detailed troubleshooting and configuration options, see [.docker/README.md](.docker/README.md).

## 📋 Important Notes

### InertiaUI Version Synchronization

Keep InertiaUI packages in sync:

- Backend: `./mtav composer update inertiaui/modal`
- Frontend: `./mtav npm update @inertiaui/modal-vue`

Update both to the same version to avoid runtime issues.

---

**Need more details?**

- Docker configuration: [.docker/README.md](.docker/README.md)
- Script reference: [.docker/scripts/README.md](.docker/scripts/README.md)
