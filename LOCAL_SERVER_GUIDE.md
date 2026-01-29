# Running on Local Server - Complete Guide

This guide provides step-by-step instructions for running the Asset Management System on your local computer.

## Table of Contents

1. [Quick Start (PHP Built-in Server)](#quick-start-php-built-in-server) - **Easiest Method** ⭐
2. [Using XAMPP (Windows/Mac/Linux)](#using-xampp)
3. [Using WAMP (Windows)](#using-wamp)
4. [Using MAMP (Mac)](#using-mamp)
5. [Using Apache/Nginx Manually](#using-apachenginx-manually)
6. [Troubleshooting](#troubleshooting)

---

## Quick Start (PHP Built-in Server) ⭐

**This is the easiest and fastest way to run the application for development/testing.**

### Prerequisites

- PHP 7.4 or higher installed on your computer (**PHP 8.0+ recommended** for security)
- MySQL or MariaDB installed

**Security Note**: PHP 7.4 reached End of Life in November 2022. For production use or if security is a concern, use PHP 8.0 or higher which receives active security updates.

### Step 1: Check if PHP is Installed

Open your terminal/command prompt and run:

```bash
php --version
```

You should see PHP version 7.4 or higher. If not, [download and install PHP](https://www.php.net/downloads.php).

### Step 2: Check if MySQL is Installed

```bash
mysql --version
```

If not installed, download from:
- **MySQL**: https://dev.mysql.com/downloads/mysql/
- **MariaDB**: https://mariadb.org/download/

### Step 3: Clone/Download the Repository

```bash
# Clone the repository
git clone https://github.com/maneesh7787/Buzznation-assets.git
cd Buzznation-assets
```

Or download the ZIP file from GitHub and extract it.

### Step 4: Create the Database

**Option A: Using Command Line**

```bash
# Login to MySQL (you may be prompted for password)
mysql -u root -p

# In MySQL prompt, run:
CREATE DATABASE asset_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

**Option B: Using phpMyAdmin**

1. Open phpMyAdmin in your browser
2. Click "New" to create a database
3. Name it `asset_management`
4. Select `utf8mb4_unicode_ci` as collation
5. Click "Create"

### Step 5: Import the Database Schema

```bash
# From the project directory
mysql -u root -p asset_management < database_schema.sql
```

This creates all tables and adds default data including:
- Admin user: username `admin`, password `admin123`
- Default asset categories

### Step 6: Configure Database Connection

Edit the file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', '');               // Your MySQL password (empty if no password set)
define('DB_NAME', 'asset_management');
```

**Security Recommendation**: While an empty password works for local development, it's recommended to set a password for your MySQL installation, even locally. This prevents accidentally deploying an insecure configuration.

### Step 7: Start the PHP Development Server

```bash
# From the project directory
php -S localhost:8080
```

You should see:
```
PHP 8.x Development Server (http://localhost:8080) started
```

### Step 8: Verify Setup

1. Open your browser and go to: **http://localhost:8080/setup-check.php**
2. All checks should show green checkmarks ✅
3. If any checks fail, follow the instructions on the page

### Step 9: Access the Application

1. Go to: **http://localhost:8080**
2. You'll be redirected to the login page
3. Login with:
   - **Username**: `admin`
   - **Password**: `admin123`

⚠️ **SECURITY WARNING**: These are default credentials. **You must change the admin password immediately** after first login by going to Profile → Change Password. Using default credentials is a serious security risk.

### Step 10: Success! 🎉

You're now running the Asset Management System on your local server!

**Important**: Press `Ctrl+C` in the terminal to stop the server when you're done.

---

## Using XAMPP

XAMPP is a popular all-in-one package for Windows, Mac, and Linux.

### Installation

1. **Download XAMPP**
   - Visit: https://www.apachefriends.org/
   - Download for your operating system
   - Install XAMPP

2. **Start Apache and MySQL**
   - Open XAMPP Control Panel
   - Click "Start" for Apache
   - Click "Start" for MySQL

### Setup

1. **Copy Project Files**
   ```
   Copy the Buzznation-assets folder to:
   - Windows: C:\xampp\htdocs\
   - Mac/Linux: /opt/lampp/htdocs/
   ```

2. **Create Database**
   - Open browser: http://localhost/phpmyadmin
   - Click "New" to create database
   - Name: `asset_management`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import Schema**
   - In phpMyAdmin, select `asset_management` database
   - Click "Import" tab
   - Choose file: `database_schema.sql`
   - Click "Go"

4. **Configure Database**
   - Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Usually empty for XAMPP
   define('DB_NAME', 'asset_management');
   ```
   
   **Security Note**: XAMPP defaults to no MySQL password. Consider setting a password for better security, even in development.

5. **Access Application**
   - Open browser: http://localhost/Buzznation-assets/
   - Login with: `admin` / `admin123`

---

## Using WAMP

WAMP is designed for Windows users.

### Installation

1. **Download WAMP**
   - Visit: https://www.wampserver.com/
   - Download and install

2. **Start WAMP**
   - Click WAMP icon in system tray
   - Wait for icon to turn green

### Setup

1. **Copy Project Files**
   ```
   Copy Buzznation-assets folder to:
   C:\wamp64\www\
   ```

2. **Create Database**
   - Left-click WAMP icon → phpMyAdmin
   - Or visit: http://localhost/phpmyadmin
   - Create database `asset_management` with `utf8mb4_unicode_ci` collation

3. **Import Schema**
   - Select database
   - Import `database_schema.sql`

4. **Configure Database**
   - Edit `config/database.php` with your credentials

5. **Access Application**
   - Browser: http://localhost/Buzznation-assets/

---

## Using MAMP

MAMP is popular for Mac users.

### Installation

1. **Download MAMP**
   - Visit: https://www.mamp.info/
   - Download and install

2. **Start MAMP**
   - Open MAMP application
   - Click "Start Servers"

### Setup

1. **Copy Project Files**
   ```
   Copy Buzznation-assets folder to:
   /Applications/MAMP/htdocs/
   ```

2. **Create Database**
   - Click "Open WebStart page" in MAMP
   - Navigate to phpMyAdmin
   - Create database `asset_management`

3. **Import Schema**
   - Select database
   - Import `database_schema.sql`

4. **Configure Database**
   - Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'root');  // MAMP default password
   define('DB_NAME', 'asset_management');
   ```

5. **Access Application**
   - Browser: http://localhost:8888/Buzznation-assets/
   - (Port 8888 is MAMP default, check MAMP preferences)

---

## Using Apache/Nginx Manually

For advanced users who want to configure Apache or Nginx manually.

### Apache

1. **Install Apache and PHP**
   ```bash
   # Ubuntu/Debian
   sudo apt update
   sudo apt install apache2 php libapache2-mod-php php-mysqli
   
   # Enable mod_rewrite
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

2. **Configure Virtual Host**
   ```apache
   <VirtualHost *:80>
       ServerName asset-management.local
       DocumentRoot /var/www/html/Buzznation-assets
       
       <Directory /var/www/html/Buzznation-assets>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Add to hosts file**
   ```bash
   # Edit /etc/hosts (or C:\Windows\System32\drivers\etc\hosts on Windows)
   127.0.0.1 asset-management.local
   ```

4. **Access**: http://asset-management.local

### Nginx

1. **Install Nginx and PHP-FPM**
   ```bash
   sudo apt install nginx php-fpm php-mysqli
   ```

2. **Configure Server Block**
   ```nginx
   server {
       listen 80;
       server_name asset-management.local;
       root /var/www/html/Buzznation-assets;
       index index.php index.html;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           # Adjust the socket path based on your PHP version
           # Common paths: php7.4-fpm.sock, php8.0-fpm.sock, php8.1-fpm.sock, php8.2-fpm.sock
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # Update version as needed
           fastcgi_index index.php;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           include fastcgi_params;
       }
   }
   ```
   
   **Note**: Update the PHP-FPM socket path to match your installed PHP version. Find your socket with: `find /var/run/php/ -name "*.sock"`

3. **Restart Nginx**
   ```bash
   sudo systemctl restart nginx
   ```

---

## Troubleshooting

### Problem: "php: command not found"

**Solution**: PHP is not installed or not in PATH.
- **Windows**: Download from https://windows.php.net/download/ and add to PATH
- **Mac**: Use Homebrew: `brew install php`
- **Linux**: `sudo apt install php` or `sudo yum install php`

### Problem: "mysql: command not found"

**Solution**: MySQL is not installed.
- Download from https://dev.mysql.com/downloads/mysql/
- Or use XAMPP/WAMP/MAMP which includes MySQL

### Problem: "Access denied for user 'root'@'localhost'"

**Solution**: Wrong MySQL credentials.
1. Check your MySQL username and password
2. Update `config/database.php` with correct credentials
3. Reset MySQL root password if needed

### Problem: Database connection error

**Solution**: 
1. Ensure MySQL service is running
   ```bash
   # Check status
   sudo systemctl status mysql
   
   # Start if not running
   sudo systemctl start mysql
   ```
2. Verify database exists
   ```bash
   mysql -u root -p -e "SHOW DATABASES;"
   ```
3. Run setup-check.php to diagnose: http://localhost:8080/setup-check.php

### Problem: "Internal Server Error" or blank page

**Solution**:
1. Check PHP error logs
2. Ensure PHP version is 7.4+
3. Verify all required PHP extensions are installed:
   ```bash
   php -m | grep -E "mysqli|session|json|mbstring"
   ```
4. Check `.htaccess` compatibility (if using Apache)

### Problem: CSS/JavaScript not loading

**Solution**:
1. Check browser console for errors (F12)
2. Verify file paths in browser
3. Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)

### Problem: "Can't access from another device on network"

**Solution**: When using PHP built-in server:
```bash
# Instead of localhost, use 0.0.0.0 to accept connections from any IP
php -S 0.0.0.0:8080

# Find your local IP address
# Windows: ipconfig
# Mac/Linux: ifconfig or ip addr

# Access from other device: http://YOUR_LOCAL_IP:8080
```

⚠️ **SECURITY WARNING**: Binding to 0.0.0.0 exposes your application to all network interfaces. Only do this on trusted, private networks (like your home WiFi). **Never do this on public or shared networks** as it exposes your application to anyone on the network. For production use, configure proper firewall rules and use Apache/Nginx with SSL.

### Problem: Port 8080 already in use

**Solution**: Use a different port:
```bash
php -S localhost:8000
# Or any available port: 8081, 8082, 3000, etc.
```

### Problem: Changes not reflecting

**Solution**:
1. Hard refresh browser: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
2. Clear browser cache
3. Check if you're editing the correct file
4. Restart PHP server if using built-in server

---

## Quick Reference Commands

### Start PHP Server
```bash
cd /path/to/Buzznation-assets
php -S localhost:8080
```

### Check if MySQL is Running
```bash
# Linux/Mac
sudo systemctl status mysql

# Windows (XAMPP)
# Check XAMPP Control Panel
```

### Import Database
```bash
mysql -u root -p asset_management < database_schema.sql
```

### Reset Admin Password
```sql
mysql -u root -p
USE asset_management;
# Generate a new password hash using PHP's password_hash() function
# The hash below is for 'admin123' - DO NOT use this in production!
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
```

⚠️ **SECURITY WARNING**: The password hash above resets the password to the default `admin123`. After using this reset command, **immediately log in and change the password** to a strong, unique password. Never use default credentials in any environment.

### Check Setup
Open in browser: http://localhost:8080/setup-check.php

---

## Next Steps

After successfully running the application:

1. **Change Default Password**
   - Login with admin/admin123
   - Go to Profile → Change Password
   - Set a strong password

2. **Read the Quick Start Guide**
   - See QUICKSTART.md for user guide
   - Learn how to add employees, assets, etc.

3. **Explore Features**
   - Admin Dashboard
   - Employee Management
   - Asset Management
   - Assignment Tracking

4. **Backup Database Regularly**
   ```bash
   mysqldump -u root -p asset_management > backup.sql
   ```

---

## Getting Help

If you encounter issues not covered here:

1. Check **setup-check.php** for environment diagnostics
2. Review **INSTALLATION.md** for detailed installation steps
3. Read **SECURITY.md** for security best practices
4. Check the [GitHub Issues](https://github.com/maneesh7787/Buzznation-assets/issues)

---

**Happy Asset Managing! 📦**
