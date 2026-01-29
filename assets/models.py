from django.db import models
from django.utils import timezone


class Employee(models.Model):
    """Model to represent an employee"""
    name = models.CharField(max_length=255)
    email = models.EmailField(unique=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.name} ({self.email})"

    class Meta:
        ordering = ['name']


class Asset(models.Model):
    """Model to represent an asset"""
    name = models.CharField(max_length=255)
    brand = models.CharField(max_length=255)
    serial_number = models.CharField(max_length=255, unique=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.name} - {self.serial_number}"

    class Meta:
        ordering = ['name']


class AssetAssignment(models.Model):
    """Model to track asset assignments to employees"""
    asset = models.ForeignKey(Asset, on_delete=models.CASCADE, related_name='assignments')
    employee = models.ForeignKey(Employee, on_delete=models.CASCADE, related_name='asset_assignments')
    issue_date = models.DateTimeField(default=timezone.now)
    acknowledge_date = models.DateTimeField(null=True, blank=True)
    return_date = models.DateTimeField(null=True, blank=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        status = "Active" if self.is_active else "Returned"
        return f"{self.asset.name} -> {self.employee.name} ({status})"

    class Meta:
        ordering = ['-issue_date']
        indexes = [
            models.Index(fields=['is_active', 'employee']),
            models.Index(fields=['asset', 'is_active']),
        ]
