#!/bin/bash

echo "Setting up Laravel 12 + Vue Starter with SQLite..."

# Create database directory and SQLite file
mkdir -p database
touch database/database.sqlite

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env file"
fi

# Update .env for SQLite and Redis
if ! grep -q "DB_CONNECTION=sqlite" .env; then
    echo "" >> .env
    echo "# SQLite Database Configuration" >> .env
    echo "DB_CONNECTION=sqlite" >> .env
    echo "DB_DATABASE=/var/www/database/database.sqlite" >> .env
    echo "" >> .env
    echo "# Redis Configuration for Docker" >> .env
    echo "REDIS_HOST=redis" >> .env
    echo "REDIS_PORT=6379" >> .env
    echo "" >> .env
    echo "Updated .env for Docker with SQLite"
fi

# Update Vite config to allow external connections
if [ -f "vite.config.js" ]; then
    if ! grep -q "host: '0.0.0.0'" vite.config.js; then
        echo "Adding host configuration to vite.config.js..."
        # Backup original file
        cp vite.config.js vite.config.js.backup
        
        # Add server configuration
        sed -i "/export default defineConfig({/a\\
    server: {\\
        host: '0.0.0.0',\\
        port: 5173,\\
        hmr: {\\
            host: 'localhost'\\
        }\\
    }," vite.config.js
    fi
fi

# Build and start containers
echo "Building Docker containers..."
docker-compose up --build -d

# Wait for containers to be ready
echo "Waiting for containers to start..."
sleep 10

# Run initial Laravel setup commands
echo "Setting up Laravel..."
docker-compose exec laravel php artisan key:generate
docker-compose exec laravel php artisan migrate
docker-compose exec laravel php artisan storage:link

echo ""
echo "🎉 Setup complete!"
echo ""
echo "Your Laravel 12 + Vue app is now running!"
echo ""
echo "Access your app:"
echo "  - Laravel: http://localhost:8000"
echo "  - Vite:    http://localhost:5173"
echo ""
echo "To stop: docker-compose down"
echo "To restart: docker-compose up"
echo "To view logs: docker-compose logs -f"
echo ""
echo "Both Laravel (composer run dev) and Vite (npm run dev) are running automatically!"