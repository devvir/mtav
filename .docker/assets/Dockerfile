# Development container for Node.js and Vite
FROM node:22-alpine

# Use the existing node user (UID/GID 1000)
USER node

# Set working directory
WORKDIR /var/www/html

# Expose Vite dev server port
EXPOSE 5173

# Install dependencies and start Vite dev server with host binding for Docker
CMD ["sh", "-c", "npm install && npm run dev -- --host 0.0.0.0"]