#!/bin/bash

# Contract Sama - Render.com Start Script
echo "🚀 Starting Contract Sama PHP Server..."

# Navigate to the PHP app directory
cd php_app

# Check if we're in development or production
if [ "$RENDER" = "true" ]; then
    echo "🌐 Production mode detected"
    export APP_ENV=production
    export APP_DEBUG=false
else
    echo "🔧 Development mode"
    export APP_ENV=development
    export APP_DEBUG=true
fi

# Start PHP built-in server
echo "🔥 Starting PHP server on port $PORT..."
exec php -S 0.0.0.0:$PORT -t public/