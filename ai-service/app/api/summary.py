from fastapi import APIRouter
from app.models import SummaryRequest
from app.services.facility_summary_service import create
router = APIRouter(prefix='/v1/facility')
@router.post('/summary')
async def endpoint(request: SummaryRequest): return await create(request)
