#!/bin/bash

# SEPA WebApp Installation Script
echo "===================================="
echo "SEPA WebApp Installation Script"
echo "===================================="
echo ""

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;' 2>/dev/null)
if [ $? -ne 0 ]; then
    echo "Error: PHP is not installed"
    exit 1
fi

echo "PHP Version: $PHP_VERSION"
echo ""

# Check for SQLite support
php -r 'if (!extension_loaded("sqlite3")) { exit(1); }' 2>/dev/null
if [ $? -ne 0 ]; then
    echo "Error: PHP SQLite3 extension is not installed"
    echo "Please install: sudo apt-get install php-sqlite3"
    exit 1
fi
echo "✓ SQLite3 support found"

# Check for PDO support
php -r 'if (!extension_loaded("pdo_sqlite")) { exit(1); }' 2>/dev/null
if [ $? -ne 0 ]; then
    echo "Error: PHP PDO SQLite extension is not installed"
    echo "Please install: sudo apt-get install php-sqlite3"
    exit 1
fi
echo "✓ PDO SQLite support found"

# Create required directories
echo ""
echo "Creating directories..."
mkdir -p database
mkdir -p vendor/tecnickcom/tcpdf
mkdir -p uploads
mkdir -p temp

# Set permissions
echo "Setting permissions..."
chmod 755 database/
chmod 755 uploads/
chmod 755 temp/

# Check if composer is installed
if command -v composer &> /dev/null; then
    echo ""
    echo "Composer found. Installing dependencies..."
    composer install --no-dev --optimize-autoloader
else
    echo ""
    echo "Composer not found."
    echo "Please download TCPDF manually:"
    echo "1. Download from: https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.5.tar.gz"
    echo "2. Extract to vendor/tecnickcom/tcpdf/"
    echo ""
    read -p "Press Enter when TCPDF is installed, or Ctrl+C to exit..."
fi

# Create .htaccess for security
echo "Creating .htaccess files..."
cat > database/.htaccess << 'EOF'
Order allow,deny
Deny from all
EOF

cat > config/.htaccess << 'EOF'
Order allow,deny
Deny from all
EOF

cat > includes/.htaccess << 'EOF'
Order allow,deny
Deny from all
EOF

# Create main .htaccess
cat > .htaccess << 'EOF'
# Enable URL rewriting
RewriteEngine On

# Protect sensitive directories
RewriteRule ^(config|database|includes|vendor)/ - [F,L]

# Security headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"

# PHP settings
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 60
php_value memory_limit 128M
EOF

# Test database connection
echo ""
echo "Testing database connection..."
php -r '
try {
    $db = new PDO("sqlite:database/sepa_manager.db");
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
'

echo ""
echo "===================================="
echo "Installation completed!"
echo "===================================="
echo ""
echo "Default login credentials:"
echo "Username: admin"
echo "Password: admin123"
echo ""
echo "IMPORTANT: Change the password after first login!"
echo ""
echo "You can now access the application at:"
echo "http://your-server/sepa_webapp/"
echo ""
