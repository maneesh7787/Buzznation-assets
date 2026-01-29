# Buzznation Assets Management System

A Django-based asset management system for tracking employee assets with swap and CSV export functionality.

## Features

- **Employee Management**: Create and manage employee records
- **Asset Management**: Track assets with details (name, brand, serial number)
- **Asset Assignment**: Assign assets to employees with issue and acknowledge dates
- **Asset Swap**: Transfer assets from one employee to another
- **CSV Export**: Export all asset assignments to CSV format

## Installation

1. Clone the repository:
```bash
git clone https://github.com/maneesh7787/Buzznation-assets.git
cd Buzznation-assets
```

2. Install dependencies:
```bash
pip install -r requirements.txt
```

3. Run migrations:
```bash
python manage.py migrate
```

4. Create a superuser (for admin access):
```bash
python manage.py createsuperuser
```

5. Run the development server:
```bash
python manage.py runserver
```

## API Endpoints

### Base URL: `/api/`

### Employees
- `GET /api/employees/` - List all employees
- `POST /api/employees/` - Create a new employee
- `GET /api/employees/{id}/` - Get employee details
- `PUT /api/employees/{id}/` - Update employee
- `DELETE /api/employees/{id}/` - Delete employee

**Employee Schema:**
```json
{
    "name": "John Doe",
    "email": "john.doe@example.com"
}
```

### Assets
- `GET /api/assets/` - List all assets
- `POST /api/assets/` - Create a new asset
- `GET /api/assets/{id}/` - Get asset details
- `PUT /api/assets/{id}/` - Update asset
- `DELETE /api/assets/{id}/` - Delete asset

**Asset Schema:**
```json
{
    "name": "Laptop",
    "brand": "Dell",
    "serial_number": "ABC123XYZ"
}
```

### Asset Swap
- `POST /api/assets/swap/` - Swap asset from one employee to another

**Request Body:**
```json
{
    "asset_id": 1,
    "from_employee_id": 2,
    "to_employee_id": 3,
    "acknowledge_date": "2024-01-29T10:00:00Z"
}
```

**Response:**
```json
{
    "message": "Asset Laptop successfully swapped from Employee1 to Employee2",
    "previous_assignment": { ... },
    "new_assignment": { ... }
}
```

### CSV Export
- `GET /api/assets/export_csv/` - Export all active asset assignments to CSV

**CSV Format:**
```
Employee Name, Email, Asset Name, Brand, Serial Number, Issue Date, Acknowledge Date
John Doe, john@example.com, Laptop, Dell, ABC123, 2024-01-29 10:00:00, 2024-01-29 11:00:00
```

### Asset Assignments
- `GET /api/assignments/` - List all assignments
- `POST /api/assignments/` - Create a new assignment
- `GET /api/assignments/{id}/` - Get assignment details
- `PUT /api/assignments/{id}/` - Update assignment
- `DELETE /api/assignments/{id}/` - Delete assignment

**Assignment Schema:**
```json
{
    "asset": 1,
    "employee": 2,
    "issue_date": "2024-01-29T10:00:00Z",
    "acknowledge_date": "2024-01-29T11:00:00Z",
    "is_active": true
}
```

## Admin Interface

Access the Django admin panel at `/admin/` to manage:
- Employees
- Assets
- Asset Assignments

## Usage Examples

### 1. Create an Employee
```bash
curl -X POST http://localhost:8000/api/employees/ \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john.doe@example.com"
  }'
```

### 2. Create an Asset
```bash
curl -X POST http://localhost:8000/api/assets/ \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laptop",
    "brand": "Dell",
    "serial_number": "ABC123XYZ"
  }'
```

### 3. Assign Asset to Employee
```bash
curl -X POST http://localhost:8000/api/assignments/ \
  -H "Content-Type: application/json" \
  -d '{
    "asset": 1,
    "employee": 1,
    "acknowledge_date": "2024-01-29T10:00:00Z"
  }'
```

### 4. Swap Asset Between Employees
```bash
curl -X POST http://localhost:8000/api/assets/swap/ \
  -H "Content-Type: application/json" \
  -d '{
    "asset_id": 1,
    "from_employee_id": 1,
    "to_employee_id": 2,
    "acknowledge_date": "2024-01-29T12:00:00Z"
  }'
```

### 5. Export Assets to CSV
```bash
curl http://localhost:8000/api/assets/export_csv/ -o asset_details.csv
```

## Database Schema

### Employee
- `id`: Primary Key
- `name`: CharField(255)
- `email`: EmailField (unique)
- `created_at`: DateTimeField
- `updated_at`: DateTimeField

### Asset
- `id`: Primary Key
- `name`: CharField(255)
- `brand`: CharField(255)
- `serial_number`: CharField(255, unique)
- `created_at`: DateTimeField
- `updated_at`: DateTimeField

### AssetAssignment
- `id`: Primary Key
- `asset`: ForeignKey(Asset)
- `employee`: ForeignKey(Employee)
- `issue_date`: DateTimeField
- `acknowledge_date`: DateTimeField (nullable)
- `return_date`: DateTimeField (nullable)
- `is_active`: BooleanField
- `created_at`: DateTimeField
- `updated_at`: DateTimeField

## License

MIT License
