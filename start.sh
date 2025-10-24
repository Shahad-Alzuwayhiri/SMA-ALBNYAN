#!/bin/bash

# Contract Sama - Render.com Start Script
echo "🚀 Starting Contract Sama PHP Server..."

# Set default port if not provided
if [ -z "$PORT" ]; then
    export PORT=8000
fi

# Navigate to the PHP app directory
cd php_app

# Check if we're in development or production
if [ "$RENDER" = "true" ]; then
    echo "🌐 Production mode detected on port $PORT"
    export APP_ENV=production
    export APP_DEBUG=false
else
    echo "🔧 Development mode on port $PORT"
    export APP_ENV=development  
    export APP_DEBUG=true
fi

# Ensure database exists
if [ ! -f "database/contracts.db" ]; then
    echo "🗄️ Creating database..."
    php setup_database.php 2>/dev/null || echo "⚠️ Database setup skipped"
fi

# Start PHP built-in server
echo "🔥 Starting PHP server on 0.0.0.0:$PORT..."
exec php -S 0.0.0.0:$PORT -t public/