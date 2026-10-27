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
   - Installs JS dependencies (pnpm)
   - Generates Laravel app key
   - Runs database migrations + seeding
   - Initializes git submodules (local packages)
   - Starts all services

3. **Open the application**
   - http://localhost:8000

That's it! You're ready to develop.

## 📋 Common Commands

The essentials for day-to-day work. Run `./mtav help` for the full list, or see
the **[Docker & Environments guide](docker/README.md)** for everything the CLI
can do (all environments, `staging`, `prod`, `update`, `rebuild`, `compose`, …).

```bash
# Environment lifecycle
./mtav up            # Start dev (first run builds, migrates, and seeds)
./mtav down          # Stop and remove containers (database volume is kept)
./mtav restart       # Down, then up
./mtav fresh         # Rebuild from scratch — WIPES and reseeds the database

# Tests
./mtav test          # Everything: Pest + Vitest + E2E
./mtav pest [args]   # Backend tests (Pest)   e.g. ./mtav pest --filter=UserTest
./mtav vitest [args] # Frontend tests (Vitest)
./mtav e2e [args]    # Browser journey suite  (see tests/Browser/README.md)

# Everyday shortcuts (run inside the dev containers)
./mtav artisan <cmd>    # Laravel Artisan   e.g. ./mtav artisan migrate
./mtav composer <cmd>   # Composer
./mtav pnpm <cmd>       # pnpm (package manager)
./mtav shell [service]  # Shell into a container (default: php)
./mtav logs [service]   # Follow logs
```

## 🌐 Application URLs

- **Main Application**: http://localhost:8000
- **Vite Dev Server**: http://localhost:5173 (auto-reloads on changes)
- **Email Testing (Mailpit)**: http://localhost:8025
- **Database**: localhost:3307 (user: mtav, password: secret)

### Development Workflow

- **Code changes**: Auto-reload via Vite HMR
- **PHP changes**: No restart needed, just refresh browser
- **Config changes**: May require `./mtav down && ./mtav up`
- **New dependencies**: Run `./mtav update`

---

## 📚 Documentation

- **[Docker & Environments](docker/README.md)** — The full infrastructure spec: environments, the complete `mtav` command set, gotchas, internals
- **[Testing & Git Hooks](documentation/technical/testing.md)** — Test workflows and quality checks
- **[E2E Journey Suite](tests/Browser/README.md)** — What the browser (`mtav e2e`) journeys cover
- **[Documentation Index](documentation/README.md)** — All developer guides
