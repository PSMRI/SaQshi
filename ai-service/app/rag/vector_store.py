import chromadb
from app.config import CHROMA_PATH

def collection():
    CHROMA_PATH.mkdir(parents=True, exist_ok=True)
    return chromadb.PersistentClient(path=str(CHROMA_PATH)).get_or_create_collection('saqshi_knowledge', metadata={'hnsw:space': 'cosine'})
