# Quick Start Guide

## For First-Time Users

### 1. Access the System
- Open your browser and navigate to the application URL
- You'll see the login page

### 2. Login
- **Username**: `admin`
- **Password**: `admin123`
- Click "Login"

### 3. First Steps (Admin)

#### Change Your Password
1. Click on your username in the top right corner
2. Select "Profile"
3. Fill in the "Change Password" form
4. Click "Change Password"

#### Add Asset Categories
1. Click "Categories" in the navigation
2. Click "Add Category"
3. Enter category name (e.g., "Laptop", "Desktop")
4. Add description (optional)
5. Click "Add Category"

Repeat for all asset types you need to manage.

#### Add Employees
1. Click "Employees" in the navigation
2. Click "Add Employee"
3. Fill in all employee details:
   - Employee ID (must be unique)
   - Name, Department, Designation
   - Date of Joining, Work Location
   - Email and Phone (optional)
   - Username and Password (for their login)
4. Click "Add Employee"

#### Add Assets
1. Click "Assets" in the navigation
2. Click "Add Asset"
3. Fill in asset details:
   - Asset Tag (unique identifier)
   - Category (select from dropdown)
   - Brand, Model, Serial Number
   - Purchase Date and Cost
   - Condition
4. Click "Add Asset"

#### Assign Assets to Employees
1. Click "Assignments" in the navigation
2. Click "Assign Asset"
3. Select the employee
4. Select an available asset
5. Set the issue date (defaults to today)
6. Record the condition at time of issue
7. Add any notes
8. Click "Assign Asset"

### 4. For HR Users

HR users can:
- View all employees and their details
- Track all asset assignments
- Monitor asset allocation status
- View dashboard statistics

### 5. For Employees

Employees can:
- View their profile information
- See all assets assigned to them
- View assignment history
- Review asset responsibility guidelines

## Common Tasks

### Export Asset Data
1. Go to Admin → Assets
2. Click "Export CSV" button
3. Save the downloaded file

### Return an Asset
1. Go to Admin → Assignments
2. Find the active assignment
3. Click "Return" button
4. Enter return date and condition
5. Add return notes
6. Click "Process Return"

### Change Employee Password (Admin)
1. Go to Admin → Employees
2. Find the employee
3. Click the key icon (🔑)
4. Enter new password
5. Click "Change Password"

### Deactivate an Employee
1. Go to Admin → Employees
2. Find the employee
3. Click the status toggle button (ban icon)
4. Employee status changes to "Inactive"

## User Roles & Permissions

| Feature | Admin | HR | Employee |
|---------|-------|-----|----------|
| Dashboard | ✅ | ✅ | ✅ |
| Manage Employees | ✅ | ❌ | ❌ |
| View Employees | ✅ | ✅ | ❌ |
| Manage Categories | ✅ | ❌ | ❌ |
| Manage Assets | ✅ | ❌ | ❌ |
| View Assets | ✅ | ✅ | My Assets Only |
| Assign Assets | ✅ | ❌ | ❌ |
| View Assignments | ✅ | ✅ | My Assignments |
| Export Data | ✅ | ❌ | ❌ |
| Change Own Password | ✅ | ✅ | ✅ |
| Change Others' Passwords | ✅ | ❌ | ❌ |

## Tips & Best Practices

1. **Asset Tags**: Use a consistent naming convention (e.g., LAP-001, DSK-001)
2. **Regular Audits**: Periodically verify physical assets against system records
3. **Backups**: Backup the database regularly
4. **Documentation**: Record detailed notes when assigning or returning assets
5. **Training**: Ensure all users understand their responsibilities

## Troubleshooting

### Can't Login?
- Verify username and password
- Check with IT admin to reset password
- Clear browser cache and cookies

### Don't See Expected Menu Items?
- Verify you're logged in with the correct role
- HR and Employee users have limited access

### Asset Not in Dropdown?
- Asset must be in "Available" status to assign
- Check Admin → Assets to verify status

### Can't Delete Category?
- Categories with assigned assets cannot be deleted
- First remove or reassign all assets in that category

## Getting Help

For system support:
1. Check this Quick Start Guide
2. Review the full README.md
3. Contact your IT administrator
4. Check the INSTALLATION.md for technical issues

---

**Remember**: Always keep track of company assets and report any issues immediately!
