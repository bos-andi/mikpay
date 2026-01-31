#!/bin/bash

# MIKPAY VPS Backup Script
# Script untuk backup data MIKPAY

set -e

# Configuration
BACKUP_DIR="/root/backups/mikpay"
RETENTION_DAYS=7
DATE=$(date +%Y%m%d_%H%M%S)

echo "=========================================="
echo "  MIKPAY Backup Script"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Please run as root (use sudo)"
    exit 1
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Backup include folder (contains JSON data and config)
echo "💾 Creating backup..."
tar -czf "$BACKUP_DIR/mikpay_$DATE.tar.gz" \
    /var/www/mikpay/include/*.json \
    /var/www/mikpay/include/config.php \
    2>/dev/null || true

# Check if backup was created
if [ -f "$BACKUP_DIR/mikpay_$DATE.tar.gz" ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_DIR/mikpay_$DATE.tar.gz" | cut -f1)
    echo "✅ Backup created: mikpay_$DATE.tar.gz ($BACKUP_SIZE)"
else
    echo "⚠️ Warning: Backup file was not created"
fi

# Cleanup old backups
echo "🧹 Cleaning up old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete
echo "✅ Old backups removed"

# List current backups
echo ""
echo "📦 Current backups:"
ls -lh "$BACKUP_DIR"/*.tar.gz 2>/dev/null | awk '{print $9, "("$5")"}' || echo "No backups found"

echo ""
echo "=========================================="
echo "  ✅ Backup Complete!"
echo "=========================================="
echo ""
