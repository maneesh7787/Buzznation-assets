from django.test import TestCase
from django.utils import timezone
from rest_framework.test import APITestCase
from rest_framework import status
from .models import Employee, Asset, AssetAssignment


class EmployeeModelTest(TestCase):
    """Test cases for Employee model"""

    def setUp(self):
        self.employee = Employee.objects.create(
            name="Test Employee",
            email="test@example.com"
        )

    def test_employee_creation(self):
        """Test employee is created correctly"""
        self.assertEqual(self.employee.name, "Test Employee")
        self.assertEqual(self.employee.email, "test@example.com")
        self.assertTrue(self.employee.id is not None)

    def test_employee_str(self):
        """Test employee string representation"""
        self.assertEqual(str(self.employee), "Test Employee (test@example.com)")


class AssetModelTest(TestCase):
    """Test cases for Asset model"""

    def setUp(self):
        self.asset = Asset.objects.create(
            name="Test Laptop",
            brand="Dell",
            serial_number="TEST-001"
        )

    def test_asset_creation(self):
        """Test asset is created correctly"""
        self.assertEqual(self.asset.name, "Test Laptop")
        self.assertEqual(self.asset.brand, "Dell")
        self.assertEqual(self.asset.serial_number, "TEST-001")

    def test_asset_str(self):
        """Test asset string representation"""
        self.assertEqual(str(self.asset), "Test Laptop - TEST-001")


class AssetAssignmentModelTest(TestCase):
    """Test cases for AssetAssignment model"""

    def setUp(self):
        self.employee = Employee.objects.create(
            name="Test Employee",
            email="test@example.com"
        )
        self.asset = Asset.objects.create(
            name="Test Laptop",
            brand="Dell",
            serial_number="TEST-001"
        )
        self.assignment = AssetAssignment.objects.create(
            asset=self.asset,
            employee=self.employee,
            is_active=True
        )

    def test_assignment_creation(self):
        """Test assignment is created correctly"""
        self.assertEqual(self.assignment.asset, self.asset)
        self.assertEqual(self.assignment.employee, self.employee)
        self.assertTrue(self.assignment.is_active)

    def test_assignment_str(self):
        """Test assignment string representation"""
        self.assertIn("Test Laptop", str(self.assignment))
        self.assertIn("Test Employee", str(self.assignment))


class AssetSwapAPITest(APITestCase):
    """Test cases for asset swap API endpoint"""

    def setUp(self):
        # Create employees
        self.emp1 = Employee.objects.create(name="Employee 1", email="emp1@example.com")
        self.emp2 = Employee.objects.create(name="Employee 2", email="emp2@example.com")

        # Create asset
        self.asset = Asset.objects.create(
            name="Laptop",
            brand="Dell",
            serial_number="DELL-001"
        )

        # Create initial assignment
        self.assignment = AssetAssignment.objects.create(
            asset=self.asset,
            employee=self.emp1,
            is_active=True
        )

    def test_successful_swap(self):
        """Test successful asset swap"""
        url = '/api/assets/swap/'
        data = {
            'asset_id': self.asset.id,
            'from_employee_id': self.emp1.id,
            'to_employee_id': self.emp2.id
        }
        response = self.client.post(url, data, format='json')

        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertIn('message', response.data)

        # Verify old assignment is inactive
        self.assignment.refresh_from_db()
        self.assertFalse(self.assignment.is_active)
        self.assertIsNotNone(self.assignment.return_date)

        # Verify new assignment exists
        new_assignment = AssetAssignment.objects.filter(
            asset=self.asset,
            employee=self.emp2,
            is_active=True
        ).first()
        self.assertIsNotNone(new_assignment)

    def test_swap_with_invalid_asset(self):
        """Test swap with non-existent asset"""
        url = '/api/assets/swap/'
        data = {
            'asset_id': 9999,
            'from_employee_id': self.emp1.id,
            'to_employee_id': self.emp2.id
        }
        response = self.client.post(url, data, format='json')
        self.assertEqual(response.status_code, status.HTTP_400_BAD_REQUEST)

    def test_swap_with_same_employee(self):
        """Test swap to the same employee"""
        url = '/api/assets/swap/'
        data = {
            'asset_id': self.asset.id,
            'from_employee_id': self.emp1.id,
            'to_employee_id': self.emp1.id
        }
        response = self.client.post(url, data, format='json')
        self.assertEqual(response.status_code, status.HTTP_400_BAD_REQUEST)

    def test_swap_unassigned_asset(self):
        """Test swap asset not assigned to from_employee"""
        url = '/api/assets/swap/'
        data = {
            'asset_id': self.asset.id,
            'from_employee_id': self.emp2.id,  # Asset is not with emp2
            'to_employee_id': self.emp1.id
        }
        response = self.client.post(url, data, format='json')
        self.assertEqual(response.status_code, status.HTTP_400_BAD_REQUEST)


class CSVExportAPITest(APITestCase):
    """Test cases for CSV export API endpoint"""

    def setUp(self):
        # Create employees
        self.emp1 = Employee.objects.create(name="John Doe", email="john@example.com")
        self.emp2 = Employee.objects.create(name="Jane Smith", email="jane@example.com")

        # Create assets
        self.asset1 = Asset.objects.create(name="Laptop", brand="Dell", serial_number="DELL-001")
        self.asset2 = Asset.objects.create(name="Monitor", brand="Samsung", serial_number="SAM-001")

        # Create assignments
        AssetAssignment.objects.create(
            asset=self.asset1,
            employee=self.emp1,
            is_active=True
        )
        AssetAssignment.objects.create(
            asset=self.asset2,
            employee=self.emp2,
            is_active=True
        )

    def test_csv_export(self):
        """Test CSV export returns correct format"""
        url = '/api/assets/export_csv/'
        response = self.client.get(url)

        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(response['Content-Type'], 'text/csv')
        self.assertIn('attachment', response['Content-Disposition'])

        # Check CSV content
        content = response.content.decode('utf-8')
        self.assertIn('Employee Name', content)
        self.assertIn('Email', content)
        self.assertIn('Asset Name', content)
        self.assertIn('John Doe', content)
        self.assertIn('Jane Smith', content)
        self.assertIn('Laptop', content)
        self.assertIn('Monitor', content)

    def test_csv_export_ordering(self):
        """Test CSV export is ordered by employee name"""
        url = '/api/assets/export_csv/'
        response = self.client.get(url)

        content = response.content.decode('utf-8')
        lines = content.split('\n')

        # Skip header and get data lines
        data_lines = [line for line in lines[1:] if line.strip()]

        # Jane Smith should come before John Doe (alphabetical order)
        jane_index = next(i for i, line in enumerate(data_lines) if 'Jane Smith' in line)
        john_index = next(i for i, line in enumerate(data_lines) if 'John Doe' in line)

        self.assertLess(jane_index, john_index)

