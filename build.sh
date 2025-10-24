#!/bin/bash

# Contract Sama - Render.com Build Script
# This script sets up the PHP environment and dependencies

echo "🚀 Starting Contract Sama build process..."

# Navigate to PHP app directory first
cd php_app

# Check if Composer is available
if ! command -v composer &> /dev/null; then
    echo "🎼 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

# Install PHP dependencies
echo "📚 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p storage/logs
mkdir -p storage/cache
mkdir -p database

# Set up database if setup file exists
echo "🗄️ Setting up database..."
if [ -f "setup_database.php" ]; then
    php setup_database.php
else
    echo "⚠️ Setup database file not found, skipping database setup"
fi

# Set permissions
echo "🔐 Setting up permissions..."
chmod -R 755 storage/ 2>/dev/null || true
chmod -R 755 database/ 2>/dev/null || true
chmod -R 755 public/ 2>/dev/null || true

echo "✅ Build completed successfully!"
echo "🌐 Contract Sama is ready to serve!"