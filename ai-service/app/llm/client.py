import httpx
from app.config import OLLAMA_BASE_URL, OLLAMA_MODEL

async def generate(prompt: str, max_tokens: int = 800, language: str = 'en') -> str:
    async with httpx.AsyncClient(timeout=120) as client:
        response = await client.post(f'{OLLAMA_BASE_URL}/api/generate', json={
            'model': OLLAMA_MODEL,
            'prompt': prompt,
            'stream': False,
            'think': False,
            'keep_alive': '10m',
            # The model advertises a 40k context. 4k is ample for Phase 1
            # retrieval and substantially lowers RAM use on development PCs.
            'options': {
                'temperature': 0.1 if language == 'hi' else 0.2,
                'repeat_penalty': 1.2,
                'num_ctx': 4096,
                'num_predict': max(1, min(int(max_tokens), 350 if language == 'hi' else 800))
            }
        })
        response.raise_for_status(); return str(response.json().get('response', '')).strip()
