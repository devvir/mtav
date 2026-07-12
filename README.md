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
   - Initializes git submodules (local packages)
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

# Rebuild container images
./mtav rebuild         # Rebuild all images
./mtav rebuild php     # Rebuild only php image (and restart container)

# Quick break (containers preserved)
./mtav stop

# Stop everything (clean shutdown, containers stopped and removed)
./mtav down

# Check what's running
./mtav status

# Run all tests (frontend + backend)
./mtav test

# Run only frontend tests (Vitest)
./mtav vitest

# Run only backend tests (Pest)
./mtav pest

# Pass arguments to Pest
./mtav pest --filter="UserTest"
./mtav pest --stop-on-failure

# View logs
./mtav logs
./mtav logs php    # specific service

# Nuclear option: fresh rebuild
./mtav fresh

# Build and preview production images locally
./mtav prod build
./mtav prod up

# Development shortcuts
./mtav artisan migrate
./mtav composer require package/name
./mtav pnpm add vue-package
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

- **[Docker & Environments](docker/README.md)** — The full infrastructure spec: environments, commands, gotchas, internals
- **[Testing & Git Hooks](documentation/technical/testing.md)** — Test workflows and quality checks
- **[Documentation Index](documentation/README.md)** — All developer guides
