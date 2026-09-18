from functools import lru_cache
from sentence_transformers import SentenceTransformer
from app.config import EMBEDDING_MODEL

@lru_cache
def model():
    # The embedding model is downloaded during initial setup. Local-only mode
    # keeps routine ingestion available on restricted/offline deployments.
    return SentenceTransformer(EMBEDDING_MODEL, local_files_only=True)
def embed(values: list[str]): return model().encode(values, normalize_embeddings=True).tolist()
