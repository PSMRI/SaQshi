from fastapi import APIRouter
from app.models import CQIRequest
from app.services.cqi_service import suggest
router = APIRouter(prefix='/v1/cqi')
@router.post('/suggest-action')
async def endpoint(request: CQIRequest): return await suggest(request)
