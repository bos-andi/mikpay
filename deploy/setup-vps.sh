#!/bin/bash

# MIKPAY VPS Setup Script
# Script untuk setup awal MIKPAY di VPS Ubuntu/Debian

set -e

echo "=========================================="
echo "  MIKPAY VPS Setup Script"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Please run as root (use sudo)"
    exit 1
fi

# Update system
echo "📦 Updating system packages..."
apt update
apt upgrade -y

# Install PHP and extensions
echo "📦 Installing PHP 7.4 and extensions..."
apt install -y php7.4-fpm php7.4-cli php7.4-common php7.4-curl php7.4-json php7.4-mbstring php7.4-xml php7.4-zip

# Install Nginx
echo "📦 Installing Nginx..."
apt install -y nginx

# Install Git
echo "📦 Installing Git..."
apt install -y git curl wget unzip

# Clone or update repository
if [ -d "/var/www/mikpay" ]; then
    echo "📥 Updating existing repository..."
    cd /var/www/mikpay
    git pull origin main
else
    echo "📥 Cloning repository from GitHub..."
    cd /var/www
    git clone https://github.com/bos-andi/mikpay.git
fi

# Set ownership and permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/mikpay
chmod -R 755 /var/www/mikpay
chmod -R 775 /var/www/mikpay/include
chmod -R 775 /var/www/mikpay/img

# Create required directories
echo "📁 Creating required directories..."
mkdir -p /var/www/mikpay/logs
mkdir -p /var/www/mikpay/voucher/temp
mkdir -p /var/www/mikpay/img
chown -R www-data:www-data /var/www/mikpay/logs
chown -R www-data:www-data /var/www/mikpay/voucher
chown -R www-data:www-data /var/www/mikpay/img
chmod -R 755 /var/www/mikpay/logs
chmod -R 755 /var/www/mikpay/voucher
chmod -R 755 /var/www/mikpay/img

# Create config.php if not exists
if [ ! -f "/var/www/mikpay/include/config.php" ]; then
    echo "⚙️ Creating config.php from template..."
    cp /var/www/mikpay/include/config.php.example /var/www/mikpay/include/config.php
    chmod 644 /var/www/mikpay/include/config.php
    chown www-data:www-data /var/www/mikpay/include/config.php
    echo "✅ config.php created. Please edit it with your router settings!"
fi

# Setup Nginx configuration
echo "⚙️ Setting up Nginx configuration..."
read -p "Enter your domain name or IP address: " DOMAIN

cat > /etc/nginx/sites-available/mikpay <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    
    root /var/www/mikpay;
    index index.php admin.php;
    
    access_log /var/log/nginx/mikpay-access.log;
    error_log /var/log/nginx/mikpay-error.log;
    
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php\$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)\$;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }
    
    location ~ /\. {
        deny all;
    }
    
    location ~ /include/config\.php\$ {
        deny all;
    }
    
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/mikpay /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
echo "🧪 Testing Nginx configuration..."
nginx -t

# Enable and start services
echo "🚀 Starting services..."
systemctl enable nginx
systemctl enable php7.4-fpm
systemctl restart nginx
systemctl restart php7.4-fpm

# Setup firewall
echo "🔥 Setting up firewall..."
if command -v ufw &> /dev/null; then
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    echo "y" | ufw enable
    echo "✅ Firewall configured"
fi

echo ""
echo "=========================================="
echo "  ✅ Setup Complete!"
echo "=========================================="
echo ""
echo "📝 Next steps:"
echo "1. Edit /var/www/mikpay/include/config.php with your router settings"
echo "2. Access your application at: http://$DOMAIN"
echo "3. Login with default credentials: mikpay/mikpay"
echo "4. Change password immediately after first login!"
echo ""
echo "📚 For detailed instructions, see DEPLOY_VPS.md"
echo ""
