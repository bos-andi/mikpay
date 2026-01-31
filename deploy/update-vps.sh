#!/bin/bash

# MIKPAY VPS Update Script
# Script untuk update MIKPAY dari GitHub

set -e

echo "=========================================="
echo "  MIKPAY Update Script"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Please run as root (use sudo)"
    exit 1
fi

# Check if directory exists
if [ ! -d "/var/www/mikpay" ]; then
    echo "❌ MIKPAY directory not found at /var/www/mikpay"
    exit 1
fi

# Backup current config
echo "💾 Backing up config.php..."
if [ -f "/var/www/mikpay/include/config.php" ]; then
    cp /var/www/mikpay/include/config.php /var/www/mikpay/include/config.php.backup
    echo "✅ Backup created: config.php.backup"
fi

# Navigate to directory
cd /var/www/mikpay

# Pull latest changes
echo "📥 Pulling latest changes from GitHub..."
sudo -u www-data git pull origin main

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/mikpay
chmod -R 755 /var/www/mikpay
chmod -R 775 /var/www/mikpay/include
chmod -R 775 /var/www/mikpay/img

# Restore config if needed
if [ -f "/var/www/mikpay/include/config.php.backup" ]; then
    if [ ! -f "/var/www/mikpay/include/config.php" ]; then
        echo "📋 Restoring config.php from backup..."
        mv /var/www/mikpay/include/config.php.backup /var/www/mikpay/include/config.php
    else
        rm /var/www/mikpay/include/config.php.backup
    fi
fi

# Reload Nginx
echo "🔄 Reloading Nginx..."
systemctl reload nginx

echo ""
echo "=========================================="
echo "  ✅ Update Complete!"
echo "=========================================="
echo ""
