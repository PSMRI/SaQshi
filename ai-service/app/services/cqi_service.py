from app.rag.retriever import retrieve
from app.llm.client import generate
from app.llm.prompts import cqi
from app.services.checkpoint_service import sources
from app.config import MAX_CONTEXT_CHARS
async def suggest(request):
    dump = request.model_dump if hasattr(request, 'model_dump') else request.dict
    rows = retrieve(f'{request.checkpoint_text} {request.finding}', dump(include={'framework','facility_type','department'}))
    reference = '\n\n'.join(row['text'] for row in rows)[:MAX_CONTEXT_CHARS] or 'No approved reference was retrieved.'
    return {'success': True, 'advisory': True, 'answer': await generate(cqi(request.finding, reference, request.language)), 'sources': sources(rows)}
