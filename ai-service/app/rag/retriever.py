from .embeddings import embed
from .vector_store import collection
from app.config import TOP_K, MAX_RETRIEVAL_DISTANCE
import re

STOP_WORDS = {
    'who', 'what', 'when', 'where', 'why', 'how', 'is', 'was', 'are', 'the', 'a', 'an', 'and', 'or', 'but', 'that', 'this', 'these', 'those', 'with', 'from', 'about', 'of', 'to', 'in', 'on', 'for', 'his', 'her', 'their',
    'explain', 'current', 'assessment', 'checkpoint', 'evidence', 'should', 'could', 'would', 'must', 'need', 'requires', 'require',
    'facility', 'available', 'provide', 'provides', 'information', 'guideline', 'guidelines', 'details', 'verified', 'verify'
}

def keyword_grounded(question: str, text: str) -> bool:
    terms = [term for term in re.findall(r"[a-z0-9']+", question.lower()) if len(term) > 2 and term not in STOP_WORDS]
    content = text.lower()
    return any(re.search(rf'\b{re.escape(term)}\b', content) for term in terms)

def retrieve(question: str, filters: dict) -> list[dict]:
    where = {k: v for k, v in filters.items() if v and k in {'framework', 'facility_type', 'department'}}
    args = {'query_embeddings': embed([question]), 'n_results': TOP_K, 'include': ['documents', 'metadatas', 'distances']}
    if len(where) == 1: args['where'] = where
    elif len(where) > 1: args['where'] = {'$and': [{key: value} for key, value in where.items()]}
    result = collection().query(**args)
    documents = result.get('documents', [[]])[0] or []
    metadatas = result.get('metadatas', [[]])[0] or []
    distances = result.get('distances', [[]])[0] or []
    rows = []
    for text, meta, distance in zip(documents, metadatas, distances):
        # Chroma cosine distance: lower is more relevant. Abstain rather than
        # answering from a merely nearest, unrelated document.
        if distance is not None and float(distance) > MAX_RETRIEVAL_DISTANCE and not keyword_grounded(question, text):
            continue
        rows.append({'text': text, 'distance': distance, **(meta or {})})
    return rows
