from rest_framework import serializers
from .models import Employee, Asset, AssetAssignment


class EmployeeSerializer(serializers.ModelSerializer):
    class Meta:
        model = Employee
        fields = ['id', 'name', 'email', 'created_at', 'updated_at']
        read_only_fields = ['created_at', 'updated_at']


class AssetSerializer(serializers.ModelSerializer):
    current_employee = serializers.SerializerMethodField()

    class Meta:
        model = Asset
        fields = ['id', 'name', 'brand', 'serial_number', 'current_employee', 'created_at', 'updated_at']
        read_only_fields = ['created_at', 'updated_at']

    def get_current_employee(self, obj):
        """Get the current employee who has this asset"""
        active_assignment = obj.assignments.filter(is_active=True).first()
        if active_assignment:
            return {
                'id': active_assignment.employee.id,
                'name': active_assignment.employee.name,
                'email': active_assignment.employee.email
            }
        return None


class AssetAssignmentSerializer(serializers.ModelSerializer):
    asset_details = AssetSerializer(source='asset', read_only=True)
    employee_details = EmployeeSerializer(source='employee', read_only=True)

    class Meta:
        model = AssetAssignment
        fields = [
            'id', 'asset', 'employee', 'asset_details', 'employee_details',
            'issue_date', 'acknowledge_date', 'return_date', 'is_active',
            'created_at', 'updated_at'
        ]
        read_only_fields = ['created_at', 'updated_at']


class AssetSwapSerializer(serializers.Serializer):
    """Serializer for swapping asset from one employee to another"""
    asset_id = serializers.IntegerField(required=True)
    from_employee_id = serializers.IntegerField(required=True)
    to_employee_id = serializers.IntegerField(required=True)
    acknowledge_date = serializers.DateTimeField(required=False, allow_null=True)

    def validate(self, data):
        """Validate the swap request"""
        # Check if asset exists
        try:
            asset = Asset.objects.get(id=data['asset_id'])
        except Asset.DoesNotExist:
            raise serializers.ValidationError({"asset_id": "Asset not found"})

        # Check if from_employee exists
        try:
            from_employee = Employee.objects.get(id=data['from_employee_id'])
        except Employee.DoesNotExist:
            raise serializers.ValidationError({"from_employee_id": "Employee not found"})

        # Check if to_employee exists
        try:
            to_employee = Employee.objects.get(id=data['to_employee_id'])
        except Employee.DoesNotExist:
            raise serializers.ValidationError({"to_employee_id": "Employee not found"})

        # Check if from_employee and to_employee are different
        if data['from_employee_id'] == data['to_employee_id']:
            raise serializers.ValidationError("Cannot swap asset to the same employee")

        # Check if asset is currently assigned to from_employee
        active_assignment = AssetAssignment.objects.filter(
            asset=asset,
            employee=from_employee,
            is_active=True
        ).first()

        if not active_assignment:
            raise serializers.ValidationError(
                f"Asset is not currently assigned to employee {from_employee.name}"
            )

        data['asset'] = asset
        data['from_employee'] = from_employee
        data['to_employee'] = to_employee
        data['active_assignment'] = active_assignment

        return data
