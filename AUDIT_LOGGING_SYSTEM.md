# Comprehensive Audit Logging System

## Overview
The Asset Management System now includes a complete audit trail that tracks **every significant action** performed in the system. This provides security, compliance, and accountability.

---

## What Gets Logged

### Authentication & Sessions
- **Login** - Successful user logins with IP address
- **Login Failed** - Failed login attempts (security tracking)
- **Logout** - User logout events

### Asset Operations
- **Create Asset** - New asset added to inventory
- **Update Asset** - Asset details modified
- **Delete Asset** - Asset removed from system

### Asset Assignment Operations
- **Create Assignment** - Asset assigned to employee
- **Return Asset** - Asset returned by employee
- **Swap Asset** - Asset transferred between employees

### Employee Operations
- **Create Employee** - New employee added
- **Update Employee** - Employee details modified
- **Change Employee Status** - Active/Inactive status changed
- **Change Employee Password** - Password reset by admin
- **Delete Employee** - Employee removed from system

### Asset Request Operations
- **Submit Asset Request** - Employee requests assets
- **Approve Asset Request** - Admin approves request
- **Reject Asset Request** - Admin rejects request

### Export Operations
- **Export Assignments** - Assignments exported to CSV
- **Export Audit Logs** - Audit logs exported to CSV

---

## Audit Log Data Captured

Each audit log entry contains:

| Field | Description | Example |
|-------|-------------|---------|
| **Timestamp** | When action occurred | 2026-01-29 14:30:22 |
| **User ID** | Database ID of user | 1 |
| **Username** | Username who performed action | admin |
| **Action** | What was done | create_asset |
| **Entity Type** | What was affected | asset |
| **Entity ID** | ID of affected record | 42 |
| **Details** | Additional information (JSON) | {"asset_tag":"LAP123","brand":"Dell"} |
| **IP Address** | Client IP address | 192.168.1.100 |
| **User Agent** | Browser/client info | Mozilla/5.0... |

---

## Admin Interface

### Accessing Audit Logs
1. Login as **admin**
2. Navigate to **Admin → Audit Logs** in the menu
3. View complete activity history

### Features

**1. Advanced Filtering**
- Filter by **Action Type** (login, create_asset, etc.)
- Filter by **User** (see specific user's activities)
- Filter by **Entity Type** (asset, employee, assignment, etc.)
- Filter by **Date Range** (from date to date)
- **Text Search** (search in username, action, details)

**2. Pagination**
- 50 records per page (configurable)
- Navigate through pages
- Total record count displayed

**3. Display**
- Chronological order (newest first)
- Color-coded action badges
- Truncated details with full view on hover
- IP address tracking

**4. CSV Export**
- Export with current filters applied
- All fields included
- Filename: `audit_logs_YYYY-MM-DD_HHmmss.csv`
- UTF-8 encoded for Excel compatibility

---

## Usage Examples

### Example 1: Track Who Modified an Asset

**Scenario:** Asset LAP001 was modified, need to know who changed it

**Steps:**
1. Go to Audit Logs page
2. Filter by Action: "Update Asset"
3. Search for "LAP001"
4. View results showing user, timestamp, and what changed

**Result:**
```
2026-01-29 10:15:00 | admin | Update Asset | asset | 5 | {"asset_tag":"LAP001","status":"maintenance"}
```

### Example 2: Security Review - Failed Login Attempts

**Scenario:** Check for unauthorized access attempts

**Steps:**
1. Go to Audit Logs page
2. Filter by Action: "Failed Login Attempt"
3. Filter by Date Range: Last 7 days
4. Export to CSV for security report

**Result:** List of all failed login attempts with IP addresses

### Example 3: Compliance Report - Asset Assignments

**Scenario:** Generate report of all asset assignments in Q1

**Steps:**
1. Go to Audit Logs page
2. Filter by Action: "Assign Asset"
3. Filter by Date: 2026-01-01 to 2026-03-31
4. Export to CSV

**Result:** Complete record of all assignments with dates and details

### Example 4: Employee Activity Audit

**Scenario:** Review what actions a specific employee performed

**Steps:**
1. Go to Audit Logs page
2. Filter by User: "john.smith"
3. Filter by Date Range: This month
4. View chronological activity

**Result:** Complete timeline of user's actions

---

## CSV Export Format

When you export audit logs, you get a CSV file with these columns:

```csv
Timestamp,User ID,Username,Action,Action Description,Entity Type,Entity ID,Details,IP Address,User Agent
2026-01-29 14:30:22,1,admin,login,User Login,user,1,"Successful login",192.168.1.100,"Mozilla/5.0..."
2026-01-29 14:31:05,1,admin,create_asset,Create Asset,asset,42,"{""asset_tag"":""LAP123""}",192.168.1.100,"Mozilla/5.0..."
```

**Features:**
- Standard CSV format (opens in Excel, Google Sheets)
- All fields included
- JSON details preserved
- Sorted by timestamp (newest first)
- Filters from UI applied to export

---

## Security Features

### IP Address Tracking
- Captures true client IP even behind proxies
- Handles X-Forwarded-For headers
- Logs "unknown" if IP cannot be determined

### Failed Login Tracking
- Records username of failed attempts
- Logs IP address of failed attempts
- Helps identify brute force attacks
- Pattern: Multiple failures from same IP

### Audit Trail Integrity
- Records cannot be deleted (only admins can view)
- Timestamps are server-generated (cannot be faked)
- User ID and username both recorded (redundancy)
- Foreign key to users table (referential integrity)

### Privacy Considerations
- Only admins can view audit logs
- Sensitive data (passwords) never logged
- IP addresses help identify location/device
- User agent helps identify device type

---

## Database Schema

```sql
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,                          -- References users(id)
    username VARCHAR(100) NULL,                -- Username (for records)
    action VARCHAR(100) NOT NULL,              -- Action code
    entity_type VARCHAR(50) NULL,              -- Type of entity affected
    entity_id INT NULL,                        -- ID of entity affected
    details TEXT NULL,                         -- JSON with extra info
    ip_address VARCHAR(45) NULL,               -- IPv4 or IPv6
    user_agent VARCHAR(255) NULL,              -- Browser/client info
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at),
    INDEX idx_username (username)
);
```

**Performance Optimizations:**
- Indexed on commonly filtered fields
- Indexed on timestamp for date range queries
- Indexed on user_id and username for user filtering
- Efficient pagination with LIMIT/OFFSET

---

## Action Codes & Descriptions

| Code | Description | Who Can Perform |
|------|-------------|-----------------|
| `login` | User Login | All users |
| `logout` | User Logout | All users |
| `login_failed` | Failed Login Attempt | Anyone (logged as system) |
| `create_asset` | Create Asset | Admin |
| `update_asset` | Update Asset | Admin |
| `delete_asset` | Delete Asset | Admin |
| `create_assignment` | Assign Asset | Admin |
| `return_asset` | Return Asset | Admin |
| `swap_asset` | Swap Asset | Admin |
| `create_employee` | Add Employee | Admin |
| `update_employee` | Update Employee | Admin |
| `delete_employee` | Delete Employee | Admin |
| `change_employee_status` | Change Employee Status | Admin |
| `change_employee_password` | Change Employee Password | Admin |
| `submit_asset_request` | Submit Asset Request | Employee |
| `approve_asset_request` | Approve Asset Request | Admin |
| `reject_asset_request` | Reject Asset Request | Admin |
| `export_assignments` | Export Assignments | Admin |
| `export_audit_logs` | Export Audit Logs | Admin |

---

## Best Practices

### For Administrators

1. **Regular Review**: Check audit logs weekly for unusual activity
2. **Security Monitoring**: Review failed login attempts regularly
3. **Compliance**: Export quarterly reports for compliance requirements
4. **Investigation**: Use filters to quickly find specific events
5. **Backup**: Export and archive audit logs regularly

### For Compliance

1. **Retention**: Keep audit logs for required period (e.g., 7 years)
2. **Evidence**: Audit logs serve as evidence of proper controls
3. **Non-Repudiation**: Timestamps and IPs prove who did what
4. **Access Control**: Only admins can view (separation of duties)
5. **Integrity**: Logs cannot be modified after creation

### For Security

1. **Monitor Failed Logins**: Alert on multiple failures from same IP
2. **Track Privileged Actions**: Review admin actions regularly
3. **IP Whitelist**: Use IP logs to identify authorized locations
4. **Anomaly Detection**: Look for unusual patterns (off-hours access, etc.)
5. **Incident Response**: Use logs to investigate security incidents

---

## Troubleshooting

### Audit Log Not Created

**Problem:** Action performed but no audit log entry

**Solutions:**
1. Check database connection
2. Verify `audit_logs` table exists
3. Check error logs in `/var/log/php/error.log`
4. Ensure user is logged in (session active)

### Cannot View Audit Logs

**Problem:** Audit Logs page doesn't load

**Solutions:**
1. Verify you're logged in as admin
2. Check database contains audit_logs table
3. Verify permissions on `/admin/audit-logs.php`
4. Check PHP error logs

### Export Not Working

**Problem:** CSV export button doesn't download file

**Solutions:**
1. Check browser pop-up blocker
2. Verify you're logged in as admin
3. Check server PHP memory limits
4. Try smaller date range if exporting many records

### Missing IP Address

**Problem:** IP address shows as "unknown"

**Solutions:**
1. This is normal for CLI/cron operations
2. Check reverse proxy configuration
3. Verify `$_SERVER['REMOTE_ADDR']` is set
4. Check firewall/WAF not blocking headers

---

## Technical Implementation

### Helper Function

The `logAudit()` function in `includes/audit.php`:

```php
logAudit($action, $entity_type = null, $entity_id = null, $details = null)
```

**Parameters:**
- `$action` (required): Action code (e.g., 'create_asset')
- `$entity_type` (optional): Type of entity (e.g., 'asset')
- `$entity_id` (optional): ID of entity (e.g., 42)
- `$details` (optional): Additional info (string or array)

**Example Usage:**
```php
// Simple logging
logAudit('login', 'user', $user_id, 'Successful login');

// With array details (auto-converted to JSON)
logAudit('create_asset', 'asset', $asset_id, [
    'asset_tag' => $asset_tag,
    'category' => $category_name,
    'brand' => $brand
]);
```

### Integration Pattern

Each operation follows this pattern:

```php
try {
    // Perform operation
    $stmt->execute();
    $id = $conn->insert_id;
    
    // Log success
    logAudit('action_name', 'entity_type', $id, $details);
    
    $message = 'Operation successful!';
} catch (Exception $e) {
    // Handle error (logging happens in exception)
    $message = 'Error: ' . $e->getMessage();
}
```

---

## Future Enhancements

Potential improvements (not yet implemented):

1. **Real-time Alerts**: Email/SMS on suspicious activity
2. **Log Retention Policy**: Auto-archive old logs
3. **Advanced Analytics**: Dashboard with charts/graphs
4. **Anomaly Detection**: ML-based unusual pattern detection
5. **Two-Factor Auth Logging**: Track 2FA events
6. **API Access Logs**: Track API usage
7. **Bulk Export**: Export specific date ranges in bulk
8. **Log Aggregation**: Integration with ELK/Splunk
9. **Compliance Reports**: Pre-built SOC2/ISO27001 reports
10. **User Activity Timeline**: Visual timeline per user

---

## Summary

The audit logging system provides:

✅ **Complete Activity Trail** - Every action logged
✅ **Security Monitoring** - Failed logins, IP tracking
✅ **Compliance Ready** - Audit reports, export capability
✅ **Easy Investigation** - Powerful filtering and search
✅ **Performance Optimized** - Indexed database, pagination
✅ **User Friendly** - Intuitive admin interface
✅ **Export Capability** - CSV export with filters

This system ensures **accountability**, **traceability**, and **compliance** for your asset management operations.
