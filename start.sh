#!/bin/bash
# Quick Start Script for Online Bookstore
# Run this script to set up and start the system

echo "================================================"
echo "   Online Bookstore - Quick Start Setup"
echo "================================================"
echo ""

# Check if MySQL is running
echo "Step 1: Checking MySQL..."
if /Applications/XAMPP/xamppfiles/bin/mysql.server status &> /dev/null; then
    echo "✓ MySQL is running"
else
    echo "✗ MySQL is not running. Starting MySQL..."
    /Applications/XAMPP/xamppfiles/bin/mysql.server start
fi
echo ""

# Check if database exists
echo "Step 2: Checking database..."
DB_EXISTS=$(/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "SHOW DATABASES LIKE 'bookstore_db';" 2>/dev/null | grep bookstore_db)

if [ -z "$DB_EXISTS" ]; then
    echo "✗ Database not found. Creating database..."
    /Applications/XAMPP/xamppfiles/bin/mysql -u root < database.sql
    echo "✓ Database created successfully!"
else
    echo "✓ Database already exists"
fi
echo ""

# Create required directories
echo "Step 3: Creating required directories..."
mkdir -p uploads logs
chmod 755 uploads logs
echo "✓ Directories created"
echo ""

# Start PHP server
echo "Step 4: Starting PHP development server..."
echo ""
echo "================================================"
echo "   System is ready!"
echo "================================================"
echo ""
echo "📱 Customer Interface:"
echo "   Homepage:  http://localhost:8000/index.php"
echo "   Register:  http://localhost:8000/register.php"
echo "   Login:     http://localhost:8000/login.php"
echo ""
echo "🔧 Admin Panel:"
echo "   URL:       http://localhost:8000/admin/"
echo "   Email:     admin@bookstore.com"
echo "   Password:  admin123"
echo ""
echo "================================================"
echo ""
echo "Starting server on http://localhost:8000 ..."
echo "Press Ctrl+C to stop the server"
echo ""

/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000
