# Asset Management System - Installation Guide

## Prerequisites

Before installing the Asset Management System, ensure you have:

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher with the following extensions:
  - mysqli
  - session
  - json
  - mbstring
- **MySQL**: Version 5.7 or higher (or MariaDB 10.2+)
- **Browser**: Modern web browser (Chrome, Firefox, Safari, Edge)

## Step-by-Step Installation

### Step 1: Download/Clone the Repository

```bash
git clone https://github.com/maneesh7787/Buzznation-assets.git
cd Buzznation-assets
```

Or download the ZIP file and extract it to your web server directory.

### Step 2: Create MySQL Database

1. Login to MySQL:
```bash
mysql -u root -p
```

2. Create the database:
```sql
CREATE DATABASE asset_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Step 3: Import Database Schema

Import the database schema using the SQL file:

```bash
mysql -u root -p asset_management < database_schema.sql
```

This will create all necessary tables and insert:
- Default admin user (username: `admin`, password: `admin123`)
- Default asset categories (Laptop, Desktop, Charger, Mouse, Keyboard, Monitor, Other Peripherals)

### Step 4: Configure Database Connection

Edit the file `config/database.php` and update with your database credentials:

```php
define('DB_HOST', 'localhost');      // Your MySQL host
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', 'your_password');  // Your MySQL password
define('DB_NAME', 'asset_management');
```

### Step 5: Configure Web Server

#### For Apache:

1. Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

2. Update your virtual host configuration or `.htaccess` file.

3. Example Virtual Host configuration:
```apache
<VirtualHost *:80>
    ServerName asset-management.local
    DocumentRoot /path/to/Buzznation-assets
    
    <Directory /path/to/Buzznation-assets>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/asset-management-error.log
    CustomLog ${APACHE_LOG_DIR}/asset-management-access.log combined
</VirtualHost>
```

#### For Nginx:

Example server block configuration:
```nginx
server {
    listen 80;
    server_name asset-management.local;
    root /path/to/Buzznation-assets;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

### Step 6: Set File Permissions

Set appropriate permissions for security:

```bash
# For Apache
sudo chown -R www-data:www-data /path/to/Buzznation-assets
sudo chmod -R 755 /path/to/Buzznation-assets

# For Nginx
sudo chown -R nginx:nginx /path/to/Buzznation-assets
sudo chmod -R 755 /path/to/Buzznation-assets
```

### Step 7: Verify Installation

Before attempting to log in, verify your environment is properly configured:

1. Open your web browser
2. Navigate to: `http://your-domain.com/setup-check.php` or `http://localhost/Buzznation-assets/setup-check.php`
3. Review all checks - they should all pass with green checkmarks
4. If any checks fail, follow the instructions on the page to resolve them

**Important**: The setup-check.php page displays detailed error information. For security, you should remove or restrict access to this file in production.

### Step 8: Test Application Access

Once all checks pass in setup-check.php:

1. Navigate to: `http://your-domain.com/login.php` or `http://localhost/Buzznation-assets/login.php`
2. Login with default credentials:
   - Username: `admin`
   - Password: `admin123`

### Step 9: Post-Installation Security

**IMPORTANT**: After successful login, immediately:

1. **Change Admin Password**:
   - Go to Profile → Change Password
   - Set a strong password

2. **Update Database User** (Optional but recommended):
   - Create a dedicated MySQL user for the application
   - Grant only necessary permissions
   ```sql
   CREATE USER 'asset_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON asset_management.* TO 'asset_user'@'localhost';
   FLUSH PRIVILEGES;
   ```
   - Update `config/database.php` with new credentials

3. **Enable HTTPS** (Production):
   - Install SSL certificate
   - Force HTTPS redirection

## Initial Configuration

### 1. Verify Asset Categories

Navigate to Admin → Categories and verify the default categories:
- Laptop
- Desktop
- Charger
- Mouse
- Keyboard
- Monitor
- Other Peripherals

Add more categories as needed for your organization.

### 2. Add Employees

1. Navigate to Admin → Employees
2. Click "Add Employee"
3. Fill in employee details:
   - Employee ID (unique)
   - Name
   - Department
   - Designation
   - Date of Joining
   - Work Location
   - Email (optional)
   - Phone (optional)
   - Username (for login)
   - Password

### 3. Add HR Users (Optional)

To create HR user accounts:

1. First create an employee record for the HR personnel
2. Then manually update the user role in the database:
```sql
INSERT INTO users (username, password, role, employee_id) 
VALUES ('hr_username', '$2y$10$hashedpassword', 'hr', employee_id);
```

Or update existing employee user:
```sql
UPDATE users SET role = 'hr' WHERE username = 'hr_username';
```

### 4. Add Assets

1. Navigate to Admin → Assets
2. Click "Add Asset"
3. Fill in asset details:
   - Asset Tag (unique identifier)
   - Category
   - Brand
   - Model
   - Serial Number
   - Purchase Date
   - Purchase Cost
   - Condition

### 5. Assign Assets to Employees

1. Navigate to Admin → Assignments
2. Click "Assign Asset"
3. Select employee and asset
4. Set issue date and condition
5. Add any notes

## Troubleshooting

### Database Connection Errors

If you see "Connection failed" errors:
1. Verify MySQL is running: `sudo systemctl status mysql`
2. Check database credentials in `config/database.php`
3. Verify database exists: `SHOW DATABASES;`
4. Check user permissions

### Login Issues

If you cannot login:
1. Verify the database was imported correctly
2. Check if the users table has the admin user:
   ```sql
   SELECT * FROM users;
   ```
3. If needed, reset admin password:
   ```sql
   UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
   ```
   (This sets password to: admin123)

### Permission Denied Errors

1. Check file ownership and permissions
2. Ensure web server user has read access
3. Check SELinux settings (if enabled)

### 404 Errors

1. Verify web server configuration
2. Check DocumentRoot path
3. Ensure mod_rewrite is enabled (Apache)

## Backup and Maintenance

### Regular Backups

Create automated backups of the database:

```bash
# Daily backup script
mysqldump -u root -p asset_management > backup_$(date +%Y%m%d).sql
```

### Updates

To update the system:
1. Backup database first
2. Pull latest changes from repository
3. Run any database migrations if provided
4. Clear browser cache

## System Requirements Summary

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 7.4 | 8.0+ |
| MySQL | 5.7 | 8.0+ |
| RAM | 512MB | 2GB+ |
| Disk Space | 100MB | 500MB+ |
| Web Server | Apache 2.4 or Nginx 1.18 | Latest stable |

## Support

For installation issues or questions:
1. Check the README.md file
2. Review error logs
3. Contact IT support team

## Next Steps

After installation:
1. ✅ Change default admin password
2. ✅ Add asset categories
3. ✅ Add employees
4. ✅ Add assets
5. ✅ Start assigning assets
6. ✅ Train users on the system
7. ✅ Set up regular database backups

---

Installation complete! You now have a fully functional Asset Management System.
