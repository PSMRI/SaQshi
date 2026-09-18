from app.config import OLLAMA_MODEL
from app.rag.vector_store import collection
def status():
    try: count = collection().count(); vector_store = {'available': True, 'documents': count}
    except Exception as exc: vector_store = {'available': False, 'error': str(exc)}
    return {
        'success': True,
        'service': 'saqshi-ai',
        'build': 'source-filter-20260913',
        'model': OLLAMA_MODEL,
        'vector_store': vector_store
    }
