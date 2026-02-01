#!/bin/bash

echo "=========================================="
echo "Fixing Superadmin Login Issues on VPS"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Get web directory
WEB_DIR="/var/www/mikpay"
if [ ! -d "$WEB_DIR" ]; then
    echo -e "${YELLOW}Web directory not found at $WEB_DIR${NC}"
    read -p "Enter web directory path: " WEB_DIR
    if [ ! -d "$WEB_DIR" ]; then
        echo -e "${RED}Directory not found!${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}Using web directory: $WEB_DIR${NC}"
echo ""

# Fix ownership
echo "1. Fixing file ownership..."
chown -R www-data:www-data "$WEB_DIR"
echo -e "${GREEN}✓ Ownership fixed${NC}"

# Fix permissions
echo "2. Fixing file permissions..."
find "$WEB_DIR" -type d -exec chmod 755 {} \;
find "$WEB_DIR" -type f -exec chmod 644 {} \;
chmod 600 "$WEB_DIR/include/config.php"
chmod 755 "$WEB_DIR/superadmin"
chmod 755 "$WEB_DIR/superadmin/index.php"
echo -e "${GREEN}✓ Permissions fixed${NC}"

# Create logs directory
echo "3. Creating logs directory..."
mkdir -p "$WEB_DIR/logs"
chmod 755 "$WEB_DIR/logs"
chown www-data:www-data "$WEB_DIR/logs"
echo -e "${GREEN}✓ Logs directory created${NC}"

# Fix session directory
echo "4. Fixing PHP session directory..."
SESSION_DIR="/var/lib/php/sessions"
if [ ! -d "$SESSION_DIR" ]; then
    mkdir -p "$SESSION_DIR"
fi
chmod 777 "$SESSION_DIR"
chown www-data:www-data "$SESSION_DIR"
echo -e "${GREEN}✓ Session directory fixed${NC}"

# Check PHP version
echo "5. Checking PHP version..."
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
echo -e "${GREEN}✓ PHP version: $PHP_VERSION${NC}"

# Restart PHP-FPM
echo "6. Restarting PHP-FPM..."
if systemctl is-active --quiet php8.1-fpm; then
    systemctl restart php8.1-fpm
    echo -e "${GREEN}✓ PHP 8.1-FPM restarted${NC}"
elif systemctl is-active --quiet php8.0-fpm; then
    systemctl restart php8.0-fpm
    echo -e "${GREEN}✓ PHP 8.0-FPM restarted${NC}"
elif systemctl is-active --quiet php-fpm; then
    systemctl restart php-fpm
    echo -e "${GREEN}✓ PHP-FPM restarted${NC}"
else
    echo -e "${YELLOW}⚠ PHP-FPM service not found${NC}"
fi

# Restart Nginx
echo "7. Restarting Nginx..."
if systemctl is-active --quiet nginx; then
    systemctl restart nginx
    echo -e "${GREEN}✓ Nginx restarted${NC}"
else
    echo -e "${YELLOW}⚠ Nginx service not found${NC}"
fi

# Check error logs
echo ""
echo "8. Checking error logs..."
if [ -f "$WEB_DIR/logs/php_errors.log" ]; then
    echo -e "${GREEN}✓ Error log exists: $WEB_DIR/logs/php_errors.log${NC}"
    echo "Last 5 lines:"
    tail -n 5 "$WEB_DIR/logs/php_errors.log"
else
    echo -e "${YELLOW}⚠ Error log not found${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}Done!${NC}"
echo "=========================================="
echo ""
echo "Superadmin Login Credentials:"
echo "  Email: ndiandie@gmail.com"
echo "  Password: MikPayandidev.id"
echo ""
echo "Access URL: https://your-domain.com/superadmin/"
echo ""
echo "If still having issues, check:"
echo "  - Error logs: $WEB_DIR/logs/php_errors.log"
echo "  - Nginx logs: /var/log/nginx/mikpay-error.log"
echo "  - PHP-FPM logs: /var/log/php*-fpm.log"
echo ""
