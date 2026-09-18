from fastapi import APIRouter
from app.models import RagRequest
from app.services.checkpoint_service import explain
router = APIRouter(prefix='/v1/checkpoint')
@router.post('/explain')
async def endpoint(request: RagRequest): return await explain(request)
