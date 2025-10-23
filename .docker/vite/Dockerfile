# Development container for Node.js and Vite
FROM node:22-alpine

# Use the existing node user (UID/GID 1000)
USER node

# Set working directory
WORKDIR /var/www

# Copy source code
COPY . .

# Expose Vite dev server port
EXPOSE 5173

# Start Vite dev server with host binding for Docker
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]