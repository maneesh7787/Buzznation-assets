import csv
from django.http import HttpResponse
from django.utils import timezone
from rest_framework import viewsets, status
from rest_framework.decorators import action
from rest_framework.response import Response
from .models import Employee, Asset, AssetAssignment
from .serializers import (
    EmployeeSerializer,
    AssetSerializer,
    AssetAssignmentSerializer,
    AssetSwapSerializer
)


class EmployeeViewSet(viewsets.ModelViewSet):
    """ViewSet for Employee operations"""
    queryset = Employee.objects.all()
    serializer_class = EmployeeSerializer


class AssetViewSet(viewsets.ModelViewSet):
    """ViewSet for Asset operations"""
    queryset = Asset.objects.all()
    serializer_class = AssetSerializer

    @action(detail=False, methods=['post'])
    def swap(self, request):
        """
        Swap asset from one employee to another.
        
        Request body:
        {
            "asset_id": 1,
            "from_employee_id": 2,
            "to_employee_id": 3,
            "acknowledge_date": "2024-01-29T10:00:00Z"  # optional
        }
        """
        serializer = AssetSwapSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)

        # Get validated data
        asset = serializer.validated_data['asset']
        from_employee = serializer.validated_data['from_employee']
        to_employee = serializer.validated_data['to_employee']
        active_assignment = serializer.validated_data['active_assignment']
        acknowledge_date = serializer.validated_data.get('acknowledge_date')

        # Mark the current assignment as inactive and set return date
        active_assignment.is_active = False
        active_assignment.return_date = timezone.now()
        active_assignment.save()

        # Create new assignment for the new employee
        new_assignment = AssetAssignment.objects.create(
            asset=asset,
            employee=to_employee,
            issue_date=timezone.now(),
            acknowledge_date=acknowledge_date,
            is_active=True
        )

        return Response({
            'message': f'Asset {asset.name} successfully swapped from {from_employee.name} to {to_employee.name}',
            'previous_assignment': AssetAssignmentSerializer(active_assignment).data,
            'new_assignment': AssetAssignmentSerializer(new_assignment).data
        }, status=status.HTTP_200_OK)

    @action(detail=False, methods=['get'])
    def export_csv(self, request):
        """
        Export asset details to CSV.
        
        CSV includes: Employee name, email, asset name, brand, serial number,
        issue date, acknowledge date
        
        Data is grouped by employee name for employees with multiple assets.
        """
        # Create HTTP response with CSV content type
        response = HttpResponse(content_type='text/csv')
        response['Content-Disposition'] = 'attachment; filename="asset_details.csv"'

        writer = csv.writer(response)
        
        # Write CSV header
        writer.writerow([
            'Employee Name',
            'Email',
            'Asset Name',
            'Brand',
            'Serial Number',
            'Issue Date',
            'Acknowledge Date'
        ])

        # Get all active assignments ordered by employee name
        active_assignments = AssetAssignment.objects.filter(
            is_active=True
        ).select_related('employee', 'asset').order_by('employee__name', 'asset__name')

        # Write data rows
        for assignment in active_assignments:
            writer.writerow([
                assignment.employee.name,
                assignment.employee.email,
                assignment.asset.name,
                assignment.asset.brand,
                assignment.asset.serial_number,
                assignment.issue_date.strftime('%Y-%m-%d %H:%M:%S') if assignment.issue_date else '',
                assignment.acknowledge_date.strftime('%Y-%m-%d %H:%M:%S') if assignment.acknowledge_date else ''
            ])

        return response


class AssetAssignmentViewSet(viewsets.ModelViewSet):
    """ViewSet for AssetAssignment operations"""
    queryset = AssetAssignment.objects.all()
    serializer_class = AssetAssignmentSerializer

