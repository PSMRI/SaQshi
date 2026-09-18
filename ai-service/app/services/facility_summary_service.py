import json
from app.llm.client import generate
from app.llm.prompts import summary
async def create(request):
    dump = request.model_dump if hasattr(request, 'model_dump') else request.dict
    data = dump(exclude={'language'})
    return {'success': True, 'advisory': True, 'answer': await generate(summary(json.dumps(data, ensure_ascii=False)[:12000], request.language)), 'sources': []}
