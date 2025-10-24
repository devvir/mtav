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

# Build new images for production
./mtav build <service> <tag>

# Development shortcuts
./mtav artisan migrate
./mtav composer require package/name
./mtav npm add vue-package
./mtav shell php
```

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

---

## 📚 Documentation

- **[Testing & Git Hooks](documentation/testing.md)** — Test workflows and quality checks
- **[Troubleshooting](documentation/troubleshooting.md)** — Common issues and configuration
- **[Production Builds](documentation/builds.md)** — Building and deploying images
- **[Docker Setup](documentation/docker.md)** — Advanced Docker operations and container details
