# Testing & Git Hooks

This document covers testing workflows and automated quality checks in the MTAV project.

## 🧪 Running Tests

### All Tests

```bash
# Run all tests (frontend + backend) in watch mode
./mtav test

# Run all tests once and exit
./mtav test --once
```

### Individual Test Suites

```bash
# Frontend tests only (Vitest)
./mtav npm test

# Backend tests only (Pest)
./mtav artisan test
```

## 🔗 Git Hooks & Quality Checks

**Important**: This project uses Git hooks that automatically run both backend and frontend tests before commits and pushes.

### Pre-commit Hook

- Runs tests and frontend linter (`eslint`)
- Blocks commit if tests or linting fail

### Pre-push Hook

- Runs `php artisan insights` (PHP code quality analysis)
- Blocks push if code quality checks fail

### Bypassing Hooks

If you need to bypass hooks (not recommended):

```bash
# ⚠️ Skip hooks (use only when necessary)
git commit --no-verify -m "Emergency fix"
git push --no-verify
```

## 📋 Important Notes

### InertiaUI Version Synchronization

Keep InertiaUI packages in sync to avoid runtime issues:

- Backend: `./mtav composer update inertiaui/modal`
- Frontend: `./mtav npm update @inertiaui/modal-vue`

Update both to the same version.
