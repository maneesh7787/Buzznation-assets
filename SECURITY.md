# Security Considerations

## Important Security Notes for Production Deployment

This document outlines security considerations and recommendations for deploying the Asset Management System in a production environment.

## Critical Security Actions Required Before Production

### 1. Change Default Credentials ⚠️
- **Default Admin Password**: Change immediately after first login
  - Current: admin / admin123
  - Navigate to Profile → Change Password
  - Use a strong password (12+ characters, mixed case, numbers, special characters)

### 2. Database Configuration 🔒
- **Never use 'root' user in production**
- Create a dedicated MySQL user:
  ```sql
  CREATE USER 'asset_user'@'localhost' IDENTIFIED BY 'strong_random_password';
  GRANT SELECT, INSERT, UPDATE, DELETE ON asset_management.* TO 'asset_user'@'localhost';
  FLUSH PRIVILEGES;
  ```
- Update `config/database.php` with the new credentials
- Use `config/database.php.example` as a template

### 3. Remove Login Page Hints 🚫
The login page displays default credentials for development convenience.

**For Production**: Remove or comment out lines 132-135 in `login.php`:
```php
// Remove this section in production:
<div class="text-center text-muted small">
    <p class="mb-1">Default Login Credentials:</p>
    <p class="mb-0">Username: <strong>admin</strong> | Password: <strong>admin123</strong></p>
</div>
```

### 4. Enable HTTPS 🔐
- Install SSL/TLS certificate
- Force HTTPS redirection in .htaccess:
  ```apache
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  ```

### 5. File Permissions 📁
```bash
# Set appropriate ownership
chown -R www-data:www-data /path/to/asset-management

# Set directory permissions
find /path/to/asset-management -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/asset-management -type f -exec chmod 644 {} \;

# Protect configuration files
chmod 600 /path/to/asset-management/config/database.php
```

### 6. Move Sensitive Directories 📂
**Recommended**: Move these directories outside the web root:
- `config/` → `/var/www/config/`
- Update paths in files accordingly

### 7. Database Backups 💾
Set up automated database backups:
```bash
# Daily backup script
#!/bin/bash
mysqldump -u backup_user -p'password' asset_management > /backups/asset_management_$(date +%Y%m%d).sql

# Keep only last 30 days
find /backups -name "asset_management_*.sql" -mtime +30 -delete
```

Add to crontab:
```bash
0 2 * * * /path/to/backup-script.sh
```

## Security Features Already Implemented ✅

### 1. Authentication & Authorization
- ✅ Secure password hashing (bcrypt, cost 10)
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Session regeneration on login (prevents session fixation)

### 2. Input Validation & Sanitization
- ✅ SQL injection prevention (prepared statements throughout)
- ✅ XSS protection (htmlspecialchars on all outputs)
- ✅ Input sanitization functions
- ✅ Email validation (server-side)

### 3. Security Headers (.htaccess)
- ✅ X-Frame-Options (clickjacking prevention)
- ✅ X-XSS-Protection
- ✅ X-Content-Type-Options (MIME sniffing prevention)
- ✅ Referrer-Policy
- ✅ Directory listing disabled

### 4. Access Control
- ✅ Sensitive directory protection (.git, config, includes)
- ✅ Hidden file protection
- ✅ SQL file access blocking

### 5. Error Handling
- ✅ Generic error messages (no sensitive info leaked)
- ✅ Error logging (not displayed to users)

## Additional Security Recommendations

### 1. Implement Rate Limiting ⚡
Add rate limiting for login attempts to prevent brute force attacks.

Consider using:
- PHP session-based rate limiting
- Fail2ban for server-level protection
- CAPTCHA after failed attempts

### 2. Session Security 🔐
Current session configuration is basic. For enhanced security, add to `includes/auth.php`:

```php
// At the start of auth.php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Only if using HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
```

### 3. Implement CSRF Protection 🛡️
CSRF token functions exist but are not yet implemented in forms.

To implement:
1. Add to all forms:
   ```php
   <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
   ```

2. Validate in POST handlers:
   ```php
   if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
       die('CSRF token validation failed');
   }
   ```

### 4. Content Security Policy 📋
Add CSP headers to prevent XSS attacks:

In .htaccess:
```apache
Header set Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com https://cdn.datatables.net; style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; img-src 'self' data:;"
```

### 5. Database User Privileges 🔑
Grant minimum required privileges:
```sql
-- Revoke ALL first
REVOKE ALL PRIVILEGES ON asset_management.* FROM 'asset_user'@'localhost';

-- Grant only what's needed
GRANT SELECT, INSERT, UPDATE, DELETE ON asset_management.* TO 'asset_user'@'localhost';

-- Do NOT grant: DROP, CREATE, ALTER, INDEX, etc.
FLUSH PRIVILEGES;
```

### 6. Regular Security Updates 🔄
- Keep PHP updated
- Update Bootstrap, jQuery, DataTables regularly
- Monitor for security advisories
- Test updates in staging before production

### 7. Logging & Monitoring 📊
Implement comprehensive logging:
- Failed login attempts
- Successful logins
- Data modifications
- Access to sensitive pages
- Error logs

Example logging:
```php
error_log(date('Y-m-d H:i:s') . " - Failed login attempt for user: " . $username . " from IP: " . $_SERVER['REMOTE_ADDR']);
```

### 8. Password Policy 🔒
Current minimum: 8 characters

**Recommended enhancements**:
- Minimum 12 characters
- Require: uppercase, lowercase, number, special character
- Password expiry (90 days)
- Prevent password reuse
- Password strength meter

### 9. Audit Trail 📝
Consider adding an audit log table:
```sql
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50),
    table_name VARCHAR(50),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 10. Two-Factor Authentication (Future) 🔐
Consider implementing 2FA for admin users:
- Google Authenticator
- SMS codes
- Email verification

## Security Checklist

Before going live:

- [ ] Changed default admin password
- [ ] Updated database credentials
- [ ] Removed login page credential hints
- [ ] Enabled HTTPS
- [ ] Set proper file permissions
- [ ] Configured automated backups
- [ ] Reviewed error logging
- [ ] Tested all security features
- [ ] Updated PHP to latest stable version
- [ ] Reviewed and hardened server configuration
- [ ] Implemented rate limiting (recommended)
- [ ] Added CSRF protection (recommended)
- [ ] Set up monitoring and alerts
- [ ] Documented security procedures
- [ ] Trained users on security best practices

## Incident Response

If you suspect a security breach:

1. **Immediately**:
   - Change all passwords
   - Review access logs
   - Disable affected user accounts

2. **Investigate**:
   - Check error logs
   - Review database for unauthorized changes
   - Analyze access patterns

3. **Remediate**:
   - Fix vulnerability
   - Restore from backup if needed
   - Update security measures

4. **Document**:
   - What happened
   - How it was discovered
   - Actions taken
   - Lessons learned

## Support & Questions

For security-related questions or to report vulnerabilities:
- Contact your IT security team
- Review application logs
- Check documentation

## Regular Security Reviews

Schedule quarterly security reviews:
- Review user accounts (remove inactive)
- Check file permissions
- Update dependencies
- Review logs for anomalies
- Test backup restoration
- Verify SSL certificate validity

---

**Remember**: Security is an ongoing process, not a one-time setup. Stay vigilant and keep the system updated!
