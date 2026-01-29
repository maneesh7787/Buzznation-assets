# Asset Swap and CSV Export Features

## Overview
This document describes two new administrative features added to the Asset Management System:
1. **Asset Swap**: Transfer assets between employees
2. **CSV Export**: Export assignment data to CSV file

---

## Feature 1: Asset Swap

### Purpose
Allows administrators to transfer an asset from one employee to another without manually returning and reassigning.

### How to Use

1. **Navigate to Assignments**
   - Login as admin
   - Go to Admin → Assignments

2. **Locate Active Assignment**
   - Find the asset assignment you want to swap
   - The assignment must have status "Active"

3. **Click Swap Button**
   - Click the blue "Swap" button next to the asset
   - A modal dialog will open

4. **Select New Employee**
   - Current employee and asset details are shown
   - Select the new employee from the dropdown
   - Note: Current employee is disabled in the dropdown
   - Optionally add a reason for the swap

5. **Confirm Swap**
   - Click "Swap Asset" button
   - The system will:
     - Mark the current assignment as returned
     - Create a new assignment to the selected employee
     - Add notes documenting the transfer
     - Maintain complete audit trail

### What Happens Behind the Scenes

**Old Assignment:**
- Status changed to "returned"
- Date returned set to current date
- Notes updated: "Asset swapped to [New Employee Name]. Reason: [Your reason]"

**New Assignment:**
- Created with current date as issue date
- Same condition as previous assignment
- Notes: "Asset transferred from [Old Employee Name]. Reason: [Your reason]"
- Acknowledgement date set to now

**Asset Status:**
- Remains "assigned" (no interruption)

### Example Use Case

**Scenario:** Employee 1 (John) is leaving, Employee 2 (Jane) is taking over his laptop.

**Steps:**
1. Go to Assignments page
2. Find John's laptop assignment
3. Click "Swap"
4. Select Jane from dropdown
5. Enter reason: "Employee transition - John leaving company"
6. Click "Swap Asset"

**Result:**
- John's assignment shows as "returned" with swap notes
- Jane now has the laptop with new active assignment
- Complete history maintained for both employees

---

## Feature 2: CSV Export

### Purpose
Export all asset assignment data to a CSV file for reporting, analysis, or record-keeping.

### How to Use

1. **Navigate to Assignments**
   - Login as admin
   - Go to Admin → Assignments

2. **Click Export Button**
   - Click the green "Export to CSV" button
   - File download will start automatically

3. **Open CSV File**
   - File name format: `asset_assignments_YYYY-MM-DD.csv`
   - Can be opened in Excel, Google Sheets, or any spreadsheet application

### CSV File Contents

The CSV includes the following columns:

| Column | Description | Example |
|--------|-------------|---------|
| Employee Name | Full name of employee | John Smith |
| Email ID | Employee email address | john.smith@company.com |
| Asset Name | Asset category/type | Laptop |
| Brand | Asset brand | Dell |
| Model | Asset model | Latitude 5420 |
| Serial Number | Asset serial number | SN001 |
| Issue Date | Date asset was issued | 2024-01-15 |
| Acknowledge Date | Date employee acknowledged | 2024-01-15 14:30:00 |
| Status | Assignment status | active/returned |

### Data Organization

- **Sorted by Employee Name**: All assets for the same employee appear together
- **Includes All Assignments**: Both active and historical (returned) assignments
- **Complete History**: Shows all asset assignments over time

### Example Output

```csv
Employee Name,Email ID,Asset Name,Brand,Model,Serial Number,Issue Date,Acknowledge Date,Status
Jane Doe,jane.doe@company.com,Mouse,Logitech,MX Master 3,SN003,2024-03-01,2024-03-01 10:00:00,active
Jane Doe,jane.doe@company.com,Laptop,HP,EliteBook 840,SN002,2024-02-15,2024-02-15 09:30:00,active
John Smith,john.smith@company.com,Laptop,Dell,Latitude 5420,SN001,2024-01-15,2024-01-15 14:15:00,active
```

### Use Cases

1. **Monthly Reports**: Generate monthly asset reports for management
2. **Audit Trails**: Maintain records of who has what assets
3. **Analysis**: Analyze asset distribution across departments
4. **Compliance**: Meet regulatory requirements for asset tracking
5. **Backup**: Keep offline backup of assignment records

### Technical Details

- **Format**: Standard CSV (Comma-Separated Values)
- **Encoding**: UTF-8 with BOM (Excel compatible)
- **File Size**: Varies based on number of assignments
- **Generation**: Server-side generation ensures all data is included
- **Performance**: Optimized query for fast export even with large datasets

---

## Security & Permissions

Both features require:
- Admin role authentication
- Active session
- Proper database permissions

### Audit Trail

All actions are logged:
- Swap operations record both old and new employee in notes
- CSV exports don't modify data (read-only)
- Timestamps track when actions occurred

---

## Troubleshooting

### Asset Swap Issues

**Problem:** Can't find Swap button
- **Solution**: Swap button only appears for "active" assignments. Returned assets can't be swapped.

**Problem:** Same employee appears in dropdown
- **Solution**: Current employee is automatically disabled. Look for "(Current)" label.

**Problem:** Swap failed error
- **Solution**: Check that new employee is active and assignment hasn't been returned.

### CSV Export Issues

**Problem:** CSV file is empty
- **Solution**: Ensure there are asset assignments in the system.

**Problem:** Special characters appear wrong in Excel
- **Solution**: File uses UTF-8 encoding. In Excel, use "Data → Get Data → From Text/CSV" and select UTF-8.

**Problem:** Can't download file
- **Solution**: Check browser pop-up blocker. Ensure you're logged in as admin.

---

## Best Practices

### Asset Swaps

1. **Always add a reason**: Helps with future audits
2. **Verify employee**: Double-check you're selecting correct new employee
3. **Check asset condition**: Consider inspecting asset before swap
4. **Document properly**: Use notes field for detailed transfer information

### CSV Exports

1. **Regular backups**: Export monthly for backup purposes
2. **Date tracking**: File name includes date for version control
3. **Secure storage**: Store exported files securely (contains employee data)
4. **Data validation**: Periodically verify exported data matches system

---

## Future Enhancements (Not Yet Implemented)

Potential improvements for consideration:
- Bulk swap (multiple assets at once)
- Swap approval workflow
- Email notifications on swap
- Filtered CSV export (by date range, employee, status)
- Multiple file format exports (Excel, PDF)
- Scheduled automatic exports

---

## Summary

These features streamline asset management:
- **Swap**: Quick, auditable asset transfers between employees
- **CSV Export**: Complete assignment data for reporting and analysis

Both features maintain data integrity and provide complete audit trails.
