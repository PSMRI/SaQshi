import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from app.rag.loader import load
from app.rag.chunker import chunks
from app.rag.embeddings import embed
from app.rag.vector_store import collection

root = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(__file__).resolve().parents[1] / 'knowledge'
store = collection()
for path in root.rglob('*'):
    if not path.is_file() or path.suffix.lower() not in {'.pdf','.docx','.txt','.md','.json'}: continue
    pages = list(load(path)); digest = pages[0][1]['file_hash'] if pages else ''
    existing = store.get(where={'file_hash': digest}, limit=1)
    if existing.get('ids'): print(f'Skip unchanged: {path.name}'); continue
    old = store.get(where={'document_name': path.name});
    if old.get('ids'): store.delete(ids=old['ids'])
    texts=[]; metas=[]; ids=[]
    for text, meta in pages:
        for index, piece in enumerate(chunks(text)):
            texts.append(piece); metas.append(meta); ids.append(f"{digest}:{meta.get('page','doc')}:{index}")
    if texts: store.add(ids=ids, documents=texts, metadatas=metas, embeddings=embed(texts)); print(f'Indexed {path.name}: {len(texts)} chunks')
