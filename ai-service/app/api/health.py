from fastapi import APIRouter
from app.services.health_service import status
router = APIRouter()
@router.get('/health')
def health(): return status()
