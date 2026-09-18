from fastapi import APIRouter
from app.models import RagRequest
from app.services.checkpoint_service import explain
router = APIRouter(prefix='/v1')
@router.post('/ask')
async def ask(request: RagRequest): return await explain(request)
