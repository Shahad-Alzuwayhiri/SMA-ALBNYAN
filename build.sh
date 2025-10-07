#!/bin/bash

# Contract Sama - Render.com Build Script
# This script sets up the PHP environment and dependencies

echo "🚀 Starting Contract Sama build process..."

# Update package manager
echo "📦 Updating package manager..."
apt-get update

# Install PHP and required extensions
echo "🐘 Installing PHP 8.1 and extensions..."
apt-get install -y php8.1 php8.1-cli php8.1-fpm php8.1-common \
  php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring \
  php8.1-curl php8.1-xml php8.1-bcmath php8.1-sqlite3 \
  php8.1-json php8.1-intl

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Navigate to PHP app directory
cd php_app

# Install PHP dependencies
echo "📚 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Set up database
echo "🗄️ Setting up database..."
php setup_database.php

# Set permissions
echo "🔐 Setting up permissions..."
chmod -R 755 storage/
chmod -R 755 database/
chmod -R 755 public/

echo "✅ Build completed successfully!"
echo "🌐 Contract Sama is ready to serve!"