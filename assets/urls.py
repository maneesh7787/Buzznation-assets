from django.urls import path, include
from rest_framework.routers import DefaultRouter
from .views import EmployeeViewSet, AssetViewSet, AssetAssignmentViewSet

router = DefaultRouter()
router.register(r'employees', EmployeeViewSet, basename='employee')
router.register(r'assets', AssetViewSet, basename='asset')
router.register(r'assignments', AssetAssignmentViewSet, basename='assignment')

urlpatterns = [
    path('', include(router.urls)),
]
