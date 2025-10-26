# Troubleshooting & Configuration

This document covers common issues and how to customize your development environment.

## 🐛 Common Issues

### Quick Fixes

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

## ⚙️ Configuration

For detailed configuration options and Docker setup information, see [Docker documentation](docker.md).

## 🔧 Advanced Troubleshooting

For more detailed troubleshooting steps and container-level debugging, see the [Docker documentation](docker.md).
