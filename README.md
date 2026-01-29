# Asset Management System

A comprehensive web-based asset management system built with PHP, MySQL, HTML, CSS, jQuery, and Bootstrap.

## Features

### Multi-Role Authentication System
- **Admin/IT Role**: Full system access
- **HR Role**: View and track employees and assets
- **Employee Role**: View assigned assets and acknowledgements

### Admin/IT Capabilities
- Employee Management (Add, Edit, Delete, Update, Active/Inactive, Change Password)
- Asset Category Management (Add, Edit, Delete, Update)
- Asset Management (Add, Edit, Delete, Update)
- Asset Assignment Management
- Export asset master sheet to CSV
- Dashboard with real-time statistics

### HR Capabilities
- View all employees
- Track asset assignments
- Monitor asset allocation status
- Dashboard with statistics

### Employee Capabilities
- View personal information
- View assigned assets (current and historical)
- Asset responsibility acknowledgement
- Dashboard with asset summary

### Security Features
- Secure password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection
- Session-based authentication
- Role-based access control

### Asset Lifecycle Management
- New employee asset allocation
- Internal asset transfer/replacement
- Asset return and recovery
- Condition tracking (excellent, good, fair, poor)
- Status tracking (available, assigned, maintenance, retired)

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/maneesh7787/Buzznation-assets.git
cd Buzznation-assets
```

### 2. Database Setup
1. Create a MySQL database named `asset_management`
2. Import the database schema:
```bash
mysql -u root -p asset_management < database_schema.sql
```

### 3. Configure Database Connection
Edit `config/database.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'asset_management');
```

### 4. Web Server Configuration

#### Apache
Ensure `.htaccess` support is enabled and `mod_rewrite` is active.

#### Nginx
Configure your server block to route requests through `index.php`.

### 5. Set Permissions
```bash
chmod -R 755 /path/to/asset-management
chown -R www-data:www-data /path/to/asset-management
```

## Default Login Credentials

- **Username**: admin
- **Password**: admin123

**Important**: Change the default password after first login!

## Usage

### First-Time Setup
1. Login with default admin credentials
2. Change the default admin password
3. Add asset categories (Laptop, Desktop, Mouse, Keyboard, etc.)
4. Add employees with user accounts
5. Add assets to inventory
6. Assign assets to employees

### Employee Management
1. Navigate to Admin → Employees
2. Click "Add Employee" to create new employee
3. Fill in employee details and create user account
4. Manage employee status (Active/Inactive)
5. Change employee passwords as needed

### Asset Management
1. Navigate to Admin → Categories to manage asset types
2. Navigate to Admin → Assets to add/edit assets
3. Click "Add Asset" and fill in asset details
4. Track asset status and condition

### Asset Assignment
1. Navigate to Admin → Assignments
2. Click "Assign Asset" to allocate asset to employee
3. Select employee and available asset
4. Record condition and issue date
5. Process returns when needed

### Exporting Data
1. Navigate to Admin → Assets
2. Click "Export CSV" to download asset master sheet
3. The export includes all asset details in CSV format

## Database Schema

### Main Tables
- **users**: Authentication and user accounts
- **employees**: Employee information
- **asset_categories**: Asset type definitions
- **assets**: Asset inventory
- **asset_assignments**: Asset allocation tracking

### Key Relationships
- Users are linked to employees (for employee role)
- Assets belong to categories
- Assignments link assets to employees

## Security Considerations

1. **Change Default Password**: Immediately change the default admin password
2. **Regular Backups**: Backup the database regularly
3. **HTTPS**: Use SSL/TLS for production deployment
4. **Update Dependencies**: Keep Bootstrap, jQuery, and other libraries updated
5. **Access Control**: Ensure proper file permissions

## File Structure
```
asset-management/
├── admin/              # Admin-specific pages
├── hr/                 # HR-specific pages
├── employee/           # Employee-specific pages
├── api/                # API endpoints
├── config/             # Configuration files
├── includes/           # Shared PHP includes
├── css/                # Custom stylesheets
├── js/                 # JavaScript files
├── assets/             # Static assets
├── database_schema.sql # Database schema
├── login.php           # Login page
├── index.php           # Entry point
└── README.md           # This file
```

## Features Highlights

### Responsive Design
- Mobile-friendly interface
- Bootstrap 5 framework
- Optimized for all screen sizes

### Data Tables
- Sortable columns
- Search functionality
- Pagination
- Export capabilities

### User Experience
- Clean and intuitive interface
- Real-time form validation
- Success/error notifications
- Modal-based forms

## Support

For issues and questions, please contact the IT department or create an issue in the repository.

## License

This project is proprietary software for internal use only.

## Version

Version 1.0.0 - Initial Release
