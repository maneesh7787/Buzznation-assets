from django.contrib import admin
from .models import Employee, Asset, AssetAssignment


@admin.register(Employee)
class EmployeeAdmin(admin.ModelAdmin):
    list_display = ['name', 'email', 'created_at']
    search_fields = ['name', 'email']
    ordering = ['name']


@admin.register(Asset)
class AssetAdmin(admin.ModelAdmin):
    list_display = ['name', 'brand', 'serial_number', 'created_at']
    search_fields = ['name', 'brand', 'serial_number']
    ordering = ['name']


@admin.register(AssetAssignment)
class AssetAssignmentAdmin(admin.ModelAdmin):
    list_display = ['asset', 'employee', 'issue_date', 'acknowledge_date', 'is_active']
    list_filter = ['is_active', 'issue_date']
    search_fields = ['asset__name', 'employee__name']
    ordering = ['-issue_date']
    date_hierarchy = 'issue_date'

